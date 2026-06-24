<?php

namespace App\Services\TimeControl;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportSection;
use Illuminate\Support\Carbon;
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
        $entries = $this->entriesQuery($from, $to)
            ->where('user_id', $userId)
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
        $entries = $this->entriesQuery($from, $to)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
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

    /**
     * Detalle de actividades agrupado por día (misma estructura en UI y exportación).
     *
     * @return array{columns: list<string>, groups: list<array{date: string, rows: list<list<string>>}>}
     */
    public function activityDetailByDay(Collection $entries, bool $includeCollaborator = false): array
    {
        $columns = $this->activityDetailColumns($includeCollaborator);
        $groups = $this->sortedEntries($entries)
            ->groupBy(fn (TimeEntry $e) => $e->entry_date->format('Y-m-d'))
            ->map(fn (Collection $dayEntries, string $dateKey) => [
                'date' => Carbon::parse($dateKey)->format('d/m/Y'),
                'rows' => $dayEntries
                    ->map(fn (TimeEntry $e) => $this->activityDetailRow($e, $includeCollaborator))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return compact('columns', 'groups');
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

        $detail = $this->activityDetailByDay($data['entries']);

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
                new ReportSection(
                    'Detalle de actividades por día',
                    $detail['columns'],
                    [],
                    $detail['groups'],
                ),
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

        $includeCollaborator = $user === null;
        $detail = $this->activityDetailByDay($data['entries'], $includeCollaborator);

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
                new ReportSection(
                    'Detalle de actividades por día',
                    $detail['columns'],
                    [],
                    $detail['groups'],
                ),
            ],
        );
    }

    /** Formato legible de una actividad para reportes TXT. */
    public function formatActivityLine(array $columns, array $row): string
    {
        /** @var array<string, string> $map */
        $map = array_combine($columns, $row);

        $line = sprintf(
            '%s - %s | %s | Cliente: %s | Tiempo: %s',
            $map['Inicio'] ?? '—',
            $map['Fin'] ?? '—',
            $map['Actividad'] ?? '—',
            $map['Cliente'] ?? '—',
            $map['Tiempo efectivo'] ?? '—',
        );

        foreach (['Colaborador', 'Puesto profesional', 'Área física', 'Observaciones'] as $label) {
            $value = trim((string) ($map[$label] ?? ''));
            if ($value !== '' && $value !== '—') {
                $line .= ' | '.$label.': '.$value;
            }
        }

        return $line;
    }

    private function entriesQuery(string $from, string $to)
    {
        return TimeEntry::whereDate('entry_date', '>=', $from)
            ->whereDate('entry_date', '<=', $to)
            ->with([
                'customer',
                'subService',
                'intervals',
                'user',
                'jobPositionSnapshot',
                'physicalAreaSnapshot',
            ]);
    }

    /** @return list<string> */
    private function activityDetailColumns(bool $includeCollaborator): array
    {
        $columns = ['Inicio', 'Fin', 'Actividad', 'Cliente', 'Tiempo efectivo', 'Puesto profesional', 'Área física', 'Observaciones'];

        if ($includeCollaborator) {
            array_unshift($columns, 'Colaborador');
        }

        return $columns;
    }

    /** @return list<string> */
    private function activityDetailRow(TimeEntry $entry, bool $includeCollaborator): array
    {
        [$start, $end] = $this->entryTimeRange($entry);

        $row = [
            $start,
            $end,
            $entry->subService->sub_service ?? '—',
            $entry->customer->name ?? '—',
            $this->hms((int) $entry->total_duration_seconds),
            $entry->jobPositionSnapshot->name ?? '—',
            $entry->physicalAreaSnapshot->name ?? '—',
            $this->activityObservations($entry),
        ];

        if ($includeCollaborator) {
            array_unshift($row, trim(($entry->user->name ?? '—').' '.($entry->user->last_name ?? '')));
        }

        return $row;
    }

    private function activityObservations(TimeEntry $entry): string
    {
        $description = trim((string) ($entry->subService->description ?? ''));

        return $description !== '' ? $description : '—';
    }

    /** @return array{0: string, 1: string} */
    private function entryTimeRange(TimeEntry $entry): array
    {
        $intervals = $entry->intervals->sortBy('started_at');

        if ($intervals->isEmpty()) {
            return ['—', '—'];
        }

        $start = $intervals->first()->started_at;
        $last = $intervals->sortByDesc('id')->first();
        $end = $last->ended_at ?? ($entry->status === TimeEntry::STATUS_IN_PROGRESS ? now() : null);

        return [
            $start?->format('H:i') ?? '—',
            $end?->format('H:i') ?? '—',
        ];
    }

    /** @param  Collection<int, TimeEntry>  $entries */
    private function sortedEntries(Collection $entries): Collection
    {
        return $entries->sortBy(function (TimeEntry $entry) {
            [$start] = $this->entryTimeRange($entry);

            return [
                $entry->entry_date->format('Y-m-d'),
                $start === '—' ? '99:99' : $start,
            ];
        })->values();
    }

    private function hms(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
