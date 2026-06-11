<?php

namespace App\Services\TimeControl;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportSection;
use Illuminate\Support\Collection;

/**
 * Fuente única de los datos del módulo Control de Horas, tanto para la
 * pantalla (Livewire) como para la exportación. Centralizarlo garantiza que
 * el archivo descargado coincida exactamente con lo mostrado en pantalla.
 */
class TimeReportService
{
    /**
     * Productividad de un colaborador en un rango de fechas.
     *
     * @return array{entries: Collection<int, TimeEntry>, totalSeconds: int, byCustomer: Collection<string, int>, byActivity: Collection<string, int>}
     */
    public function userProductivity(int $userId, string $from, string $to): array
    {
        $entries = TimeEntry::where('user_id', $userId)
            ->whereDate('entry_date', '>=', $from)
            ->whereDate('entry_date', '<=', $to)
            ->with(['customer', 'subService'])
            ->orderByDesc('entry_date')
            ->get();

        return [
            'entries' => $entries,
            'totalSeconds' => (int) $entries->sum('total_duration_seconds'),
            'byCustomer' => $entries->groupBy(fn ($e) => $e->customer->name ?? '—')
                ->map(fn ($g) => (int) $g->sum('total_duration_seconds'))
                ->sortDesc(),
            'byActivity' => $entries->groupBy(fn ($e) => $e->subService->sub_service ?? '—')
                ->map(fn ($g) => (int) $g->sum('total_duration_seconds'))
                ->sortDesc(),
        ];
    }

    /**
     * Supervisión de horas (admin), opcionalmente acotada a un colaborador.
     *
     * @return array{entries: Collection<int, TimeEntry>, total: int, byCollaborator: Collection<int, array>, byCustomer: Collection<int, array>, byPosition: Collection<int, array>, byArea: Collection<int, array>, autoClosedCount: int}
     */
    public function adminSupervision(?int $userId, string $from, string $to): array
    {
        $entries = TimeEntry::whereDate('entry_date', '>=', $from)
            ->whereDate('entry_date', '<=', $to)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with(['user', 'customer', 'jobPositionSnapshot', 'physicalAreaSnapshot'])
            ->get();

        $group = fn (string $key, callable $name) => $entries->groupBy($key)
            ->map(fn ($g) => [
                'name' => $name($g->first()),
                'seconds' => (int) $g->sum('total_duration_seconds'),
            ])->sortByDesc('seconds')->values();

        return [
            'entries' => $entries,
            'total' => (int) $entries->sum('total_duration_seconds'),
            'byCollaborator' => $group('user_id', fn ($e) => trim(($e->user->name ?? '—').' '.($e->user->last_name ?? ''))),
            'byCustomer' => $group('customer_id', fn ($e) => $e->customer->name ?? '—'),
            'byPosition' => $group('job_position_id_snapshot', fn ($e) => $e->jobPositionSnapshot->name ?? '—'),
            'byArea' => $group('physical_area_id_snapshot', fn ($e) => $e->physicalAreaSnapshot->name ?? '—'),
            'autoClosedCount' => $entries->where('status', TimeEntry::STATUS_AUTO_CLOSED)->count(),
        ];
    }

    /** Construye el reporte exportable de Mi Productividad. */
    public function userReport(User $user, string $from, string $to): ReportData
    {
        $data = $this->userProductivity($user->id, $from, $to);
        $total = $data['totalSeconds'];

        $pct = fn (int $part) => $total > 0 ? round($part * 100 / $total).'%' : '0%';

        $byCustomer = $data['byCustomer']
            ->map(fn ($seconds, $name) => [$name, $this->hms($seconds), $pct($seconds)])
            ->values()->all();

        $byActivity = $data['byActivity']
            ->map(fn ($seconds, $name) => [$name, $this->hms($seconds), $pct($seconds)])
            ->values()->all();

        $detail = $data['entries']->map(fn (TimeEntry $e) => [
            optional($e->entry_date)->format('d/m/Y') ?? '—',
            $e->customer->name ?? '—',
            $e->subService->sub_service ?? '—',
            $e->status_label,
            $this->hms((int) $e->total_duration_seconds),
        ])->values()->all();

        return new ReportData(
            title: 'Mi productividad',
            filenameBase: 'mi-productividad_'.$from.'_'.$to,
            meta: [
                'Colaborador' => trim($user->name.' '.($user->last_name ?? '')),
                'Desde' => $from,
                'Hasta' => $to,
                'Horas efectivas en el periodo' => $this->hms($total),
            ],
            sections: [
                new ReportSection('Distribución por cliente', ['Cliente', 'Tiempo', '%'], $byCustomer),
                new ReportSection('Distribución por actividad', ['Actividad', 'Tiempo', '%'], $byActivity),
                new ReportSection('Detalle cronológico', ['Fecha', 'Cliente', 'Actividad', 'Estado', 'Tiempo'], $detail),
            ],
        );
    }

    /** Construye el reporte exportable de Supervisión de horas. */
    public function adminReport(?User $user, string $from, string $to): ReportData
    {
        $data = $this->adminSupervision($user?->id, $from, $to);

        $rows = fn (Collection $group) => $group
            ->map(fn ($row) => [$row['name'], $this->hms($row['seconds'])])
            ->all();

        return new ReportData(
            title: 'Supervisión de horas',
            filenameBase: 'supervision-horas_'.($user ? 'usuario-'.$user->id : 'todos').'_'.$from.'_'.$to,
            meta: [
                'Colaborador' => $user ? trim($user->name.' '.($user->last_name ?? '')) : 'Todos los colaboradores',
                'Desde' => $from,
                'Hasta' => $to,
                'Horas efectivas totales' => $this->hms($data['total']),
                'Cierres automáticos' => (string) $data['autoClosedCount'],
            ],
            sections: [
                new ReportSection('Por colaborador', ['Colaborador', 'Tiempo'], $rows($data['byCollaborator'])),
                new ReportSection('Por cliente', ['Cliente', 'Tiempo'], $rows($data['byCustomer'])),
                new ReportSection('Por puesto profesional', ['Puesto', 'Tiempo'], $rows($data['byPosition'])),
                new ReportSection('Por área física', ['Área', 'Tiempo'], $rows($data['byArea'])),
            ],
        );
    }

    private function hms(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
