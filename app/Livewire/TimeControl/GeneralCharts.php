<?php

namespace App\Livewire\TimeControl;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ManagerControl\ClockComparison;
use App\Services\ManagerControl\LegacyEmployeeResolver;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Gráficos Generales: dos tarjetas con el mismo diseño que Cronómetro y Manager Control.
 * La primera grafica el tiempo del Cronómetro (CRM) y la segunda el de ManagerControl; cada una tiene su propio periodo
 * y muestra una gráfica a la vez (por día, por cliente o por actividad). Solo lee datos: no modifica nada.
 */
class GeneralCharts extends Component
{
    /** Colaborador del CRM que se grafica (solo lo elige el administrador; los demás se ven a sí mismos). */
    public string $userId = '';

    public string $cronFrom = '';

    public string $cronTo = '';

    public string $mcFrom = '';

    public string $mcTo = '';

    // Filtros de la gráfica «Por día» de cada tarjeta (vacío = todos).
    public string $cronClient = '';

    public string $cronActivity = '';

    public string $mcClient = '';

    public string $mcActivity = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-productivity'), 403);

        $to = $this->today();
        $from = $this->defaultFrom();
        $this->cronFrom = $this->mcFrom = $from;
        $this->cronTo = $this->mcTo = $to;

        // El administrador empieza con el colaborador que más tiempo registró en el periodo.
        if ($this->isAdmin()) {
            $busiest = TimeEntry::query()
                ->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)
                ->selectRaw('user_id, SUM(total_duration_seconds) AS seconds')
                ->groupBy('user_id')
                ->orderByDesc('seconds')
                ->value('user_id');
            $this->userId = $busiest ? (string) $busiest : '';
        }
    }

    public function updatedCronFrom(): void
    {
        $this->cronFrom = $this->validDate($this->cronFrom, $this->defaultFrom());
    }

    public function updatedCronTo(): void
    {
        $this->cronTo = $this->validDate($this->cronTo, $this->today());
    }

    public function updatedMcFrom(): void
    {
        $this->mcFrom = $this->validDate($this->mcFrom, $this->defaultFrom());
    }

    public function updatedMcTo(): void
    {
        $this->mcTo = $this->validDate($this->mcTo, $this->today());
    }

    /** Al cambiar de colaborador se limpian los filtros (los clientes y actividades son otros). */
    public function updatedUserId(): void
    {
        if (! $this->isAdmin()) {
            $this->userId = '';
        }
        $this->cronClient = $this->cronActivity = $this->mcClient = $this->mcActivity = '';
    }

    public function applyCron(): void
    {
        if ($this->cronFrom > $this->cronTo) {
            [$this->cronFrom, $this->cronTo] = [$this->cronTo, $this->cronFrom];
        }
        $this->cronClient = $this->cronActivity = '';
    }

    public function applyMc(): void
    {
        if ($this->mcFrom > $this->mcTo) {
            [$this->mcFrom, $this->mcTo] = [$this->mcTo, $this->mcFrom];
        }
        $this->mcClient = $this->mcActivity = '';
        $employeeId = $this->mcEmployee()['employee']['id'] ?? null;
        if ($employeeId !== null) {
            Cache::forget($this->mcCacheKey((int) $employeeId, $this->mcFrom, $this->mcTo));
        }
    }

    public function render()
    {
        $subject = $this->subject();
        $users = $this->isAdmin()
            ? User::query()->orderBy('name')->orderBy('last_name')->get(['id', 'name', 'last_name'])
                ->map(fn (User $user) => ['value' => (string) $user->id, 'label' => trim($user->name.' '.($user->last_name ?? ''))])
                ->all()
            : [];

        return view('livewire.time-control.general-charts', [
            'isAdmin' => $this->isAdmin(),
            'users' => $users,
            'subject' => $subject,
            'cron' => $this->cronData($subject),
            'mc' => $this->mcData($subject),
        ])->layout('layouts.app');
    }

    /** Usuario del CRM que se grafica: el propio, o el que eligió el administrador. */
    private function subject(): ?User
    {
        if (! $this->isAdmin()) {
            return auth()->user();
        }

        return ctype_digit($this->userId) ? User::query()->find((int) $this->userId) : null;
    }

    /** @return array{rows: list<array{date: string, client_key: string, client: string, activity_key: string, activity: string, seconds: int}>} */
    private function cronRows(User $user): array
    {
        return TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', '>=', $this->cronFrom)->whereDate('entry_date', '<=', $this->cronTo)
            ->with(['intervals', 'customer', 'subService'])
            ->get()
            ->map(fn (TimeEntry $entry) => [
                'date' => $entry->entry_date->format('Y-m-d'),
                'client_key' => (string) $entry->customer_id,
                'client' => (string) ($entry->customer->name ?? 'Sin cliente'),
                'activity_key' => (string) $entry->sub_service_id,
                'activity' => (string) ($entry->subService->sub_service ?? 'Sin actividad'),
                'seconds' => (int) $entry->calculateEffectiveSeconds(),
            ])
            ->all();
    }

    private function cronData(?User $user): array
    {
        $rows = $user ? $this->cronRows($user) : [];
        $clock = $user ? $this->clockDays($user, $this->cronFrom, $this->cronTo) : ['state' => 'no_user', 'days' => []];

        return $this->charts($rows, $this->cronFrom, $this->cronTo, $this->cronClient, $this->cronActivity, $clock, 'Cronómetro')
            + ['state' => $user ? 'ok' : 'no_user'];
    }

    /**
     * Lo que marca el reloj checador cada día (mismo cálculo que el módulo Reloj Checador), para comparar con el
     * tiempo capturado en el Cronómetro o en ManagerControl. Solo lectura.
     *
     * @return array{state: string, days: array<string, array{seconds: int, review: bool, marks: int}>}
     */
    private function clockDays(?User $user, string $from, string $to): array
    {
        try {
            return app(ClockComparison::class)->days($user, $from, $to);
        } catch (\Throwable $e) {
            report($e);

            return ['state' => 'error', 'days' => []];
        }
    }

    /** @return array{status: string, employee: ?array{id: int, name: string}} */
    private function mcEmployee(): array
    {
        $subject = $this->subject();
        $allowed = $this->isAdmin() ? Gate::allows('view-legacy-managercontrol') : Gate::allows('view-legacy-own-hours');
        if (! $allowed) {
            return ['status' => 'no_access', 'employee' => null];
        }
        if ($subject === null) {
            return ['status' => 'no_user', 'employee' => null];
        }

        try {
            return app(LegacyEmployeeResolver::class)->forUser($subject);
        } catch (ManagerControlException $e) {
            report($e);

            return ['status' => 'error', 'employee' => null];
        }
    }

    private function mcCacheKey(int $employeeId, string $from, string $to): string
    {
        return 'general-charts:mc:'.hash('sha256', serialize([config('managercontrol.host'), config('managercontrol.database'), $employeeId, $from, $to]));
    }

    private function mcData(?User $user): array
    {
        $match = $this->mcEmployee();
        $employee = $match['employee'] ?? null;
        $state = $employee ? 'ok' : ($match['status'] === 'no_access' || $match['status'] === 'no_user' || $match['status'] === 'error' ? $match['status'] : 'no_employee');
        $rows = [];
        $clock = ['state' => 'no_user', 'days' => []];

        if ($employee) {
            try {
                $legacy = Cache::remember($this->mcCacheKey((int) $employee['id'], $this->mcFrom, $this->mcTo), now()->addSeconds(60),
                    fn (): array => app(ManagerControlRepository::class)->workedHours((int) $employee['id'], $this->mcFrom, $this->mcTo));
                $rows = array_map(fn (array $row) => [
                    'date' => $row['date'],
                    'client_key' => $row['customer'],
                    'client' => $row['customer'],
                    'activity_key' => $row['activity'],
                    'activity' => $row['activity'],
                    'seconds' => (int) $row['seconds'],
                ], $legacy);
                $clockUser = app(ClockComparison::class)->userForLegacyEmployee((int) $employee['id']);
                $clock = $this->clockDays($clockUser, $this->mcFrom, $this->mcTo);
            } catch (ManagerControlException $e) {
                report($e);
                $state = 'error';
            }
        }

        return $this->charts($rows, $this->mcFrom, $this->mcTo, $this->mcClient, $this->mcActivity, $clock, 'ManagerControl')
            + ['state' => $state, 'employee' => $employee['name'] ?? null];
    }

    /**
     * Las tres gráficas de una tarjeta y las opciones de sus filtros.
     *
     * @param  list<array{date: string, client_key: string, client: string, activity_key: string, activity: string, seconds: int}>  $rows
     * @param  array{state: string, days: array<string, array{seconds: int, review: bool, marks: int}>}  $clock
     */
    private function charts(array $rows, string $from, string $to, string $client, string $activity, array $clock, string $sourceLabel): array
    {
        $rows = collect($rows);
        $total = (int) $rows->sum('seconds');

        $clients = $rows->groupBy('client_key')
            ->map(fn ($group, $key) => ['value' => (string) $key, 'label' => $group->first()['client'], 'seconds' => (int) $group->sum('seconds')])
            ->sortByDesc('seconds')->values();
        $activities = $rows->groupBy('activity_key')
            ->map(fn ($group, $key) => ['value' => (string) $key, 'label' => $group->first()['activity'], 'seconds' => (int) $group->sum('seconds')])
            ->sortByDesc('seconds')->values();

        // «Por día»: todos los días del periodo (también los que no tienen tiempo), filtrados por cliente y actividad.
        $filtered = $rows
            ->when($client !== '', fn ($c) => $c->where('client_key', $client))
            ->when($activity !== '', fn ($c) => $c->where('activity_key', $activity));
        $byDate = $filtered->groupBy('date')->map(fn ($group) => (int) $group->sum('seconds'));
        $labels = [];
        $values = [];
        $dates = [];
        $isoDates = [];
        for ($day = Carbon::parse($from); $day->lte(Carbon::parse($to)); $day->addDay()) {
            $labels[] = $day->format('d/m');
            $dates[] = ucfirst($day->copy()->locale('es')->translatedFormat('D d/m/y'));
            $isoDates[] = $day->toDateString();
            $values[] = (int) ($byDate[$day->toDateString()] ?? 0);
        }
        $filteredTotal = array_sum($values);

        return [
            'total' => $total,
            'day' => [
                'kind' => 'day',
                'labels' => $labels,
                'titles' => $dates,
                'isoDates' => $isoDates,
                'values' => $values,
                'average' => count($values) > 0 ? (int) round($filteredTotal / count($values)) : 0,
                'total' => $filteredTotal,
                // Comparación con el reloj checador, igual que en Manager Control (se calcula aquí una sola vez).
                'clockState' => $clock['state'],
                'clockDays' => $clock['days'],
                'sourceLabel' => $sourceLabel,
            ],
            'clients' => $this->ranking($clients, $total),
            'activities' => $this->ranking($activities, $total),
            'clientOptions' => $clients->map(fn ($c) => ['value' => $c['value'], 'label' => $c['label']])->all(),
            'activityOptions' => $activities->map(fn ($a) => ['value' => $a['value'], 'label' => $a['label']])->all(),
        ];
    }

    /** Las 10 primeras posiciones y el resto sumado en «Otros», para que la gráfica se lea completa. */
    private function ranking($items, int $total): array
    {
        $top = $items->take(10);
        $rest = (int) $items->skip(10)->sum('seconds');
        $labels = $top->pluck('label')->all();
        $values = $top->pluck('seconds')->map(fn ($s) => (int) $s)->all();
        if ($rest > 0) {
            $labels[] = 'Otros ('.($items->count() - 10).')';
            $values[] = $rest;
        }

        return ['kind' => 'rank', 'labels' => $labels, 'values' => $values, 'total' => $total];
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    private function defaultFrom(): string
    {
        return Carbon::parse($this->today())->subDays(14)->toDateString();
    }

    private function validDate(string $value, string $fallback): string
    {
        return Carbon::hasFormat($value, 'Y-m-d') ? $value : $fallback;
    }

    private function today(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return Carbon::now($timezone === 'UTC' ? 'America/Mexico_City' : $timezone)->toDateString();
    }
}
