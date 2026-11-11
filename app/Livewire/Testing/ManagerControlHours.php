<?php

namespace App\Livewire\Testing;

use App\Models\ManagerControlEmployeeLink;
use App\Models\User;
use App\Services\ManagerControl\HoursSummary;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use App\Services\ManagerControl\NameMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Pruebas: horas laboradas del legacy ManagerControl, en modo ESTRICTAMENTE de solo lectura.
 * Solo Administrador. No escribe nada, ni en el legacy ni en la base del CRM.
 */
class ManagerControlHours extends Component
{
    public string $employeeId = '';

    public string $from;

    public string $to;

    public bool $loaded = false;

    public ?string $error = null;

    // Periodo realmente consultado (el PDF y los encabezados usan este, no el que se está editando).
    public string $appliedFrom = '';

    public string $appliedTo = '';

    // El detalle por día tiene su propio periodo (independiente del de arriba), como en Mi productividad.
    public string $detailFrom;

    public string $detailTo;

    public string $appliedDetailFrom = '';

    public string $appliedDetailTo = '';

    public bool $detailLoaded = false;

    // Asignación usuario del CRM => colaborador de ManagerControl (id elegido en cada fila).
    public array $links = [];

    public ?string $linkNotice = null;

    public ?string $linkError = null;

    // Filtros del bloque de asignación: todos | assigned | unassigned, y búsqueda por nombre o correo.
    public string $linkFilter = 'all';

    public string $linkSearch = '';

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->to = $this->today();
        $this->from = Carbon::parse($this->to)->subDays(14)->toDateString(); // hoy y 14 días previos = 15 días
        $this->appliedFrom = $this->from;
        $this->appliedTo = $this->to;
        $this->detailFrom = $this->appliedDetailFrom = $this->from;
        $this->detailTo = $this->appliedDetailTo = $this->to;

        if (! $this->ownMode()) {
            // Todas las filas empiezan con valor ('' = sin asignar) para que el selector de cada fila pueda enlazarse.
            $this->links = User::query()->orderBy('name')->orderBy('last_name')->limit(300)->pluck('id')->mapWithKeys(fn ($id): array => [$id => ''])->all();
            foreach (ManagerControlEmployeeLink::query()->pluck('legacy_employee_id', 'user_id') as $userId => $legacyId) {
                $this->links[$userId] = (string) $legacyId;
            }
        }
    }

    public function saveLink(int $userId): void
    {
        $this->authorizeAdminOnly();
        $this->linkNotice = $this->linkError = null;

        $legacyId = (string) ($this->links[$userId] ?? '');
        if (! ctype_digit($legacyId) || (int) $legacyId < 1) {
            $this->linkError = 'Elija un colaborador de ManagerControl para asignar.';

            return;
        }
        $user = User::query()->find($userId);
        if ($user === null) {
            $this->linkError = 'El usuario del CRM ya no existe.';

            return;
        }

        try {
            $employee = collect(app(ManagerControlRepository::class)->employees())->firstWhere('id', (int) $legacyId);
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);

            return;
        }
        if ($employee === null) {
            $this->linkError = 'El colaborador elegido no existe en ManagerControl.';

            return;
        }
        if (ManagerControlEmployeeLink::query()->where('legacy_employee_id', $employee['id'])->where('user_id', '!=', $userId)->exists()) {
            $this->linkError = 'Ese colaborador ya está asignado a otro usuario del CRM.';

            return;
        }

        ManagerControlEmployeeLink::query()->updateOrCreate(
            ['user_id' => $userId],
            ['legacy_employee_id' => $employee['id'], 'legacy_employee_name' => $employee['name']],
        );
        $this->links[$userId] = (string) $employee['id'];
        $this->linkNotice = "Asignado: {$user->name} {$user->last_name} → {$employee['name']}.";
    }

    public function setLinkFilter(string $filter): void
    {
        $this->authorizeAdminOnly();
        $this->linkFilter = in_array($filter, ['all', 'assigned', 'unassigned'], true) ? $filter : 'all';
    }

    public function removeLink(int $userId): void
    {
        $this->authorizeAdminOnly();
        $this->linkNotice = $this->linkError = null;

        ManagerControlEmployeeLink::query()->where('user_id', $userId)->delete();
        $this->links[$userId] = '';
        $this->linkNotice = 'Asignación quitada.';
    }

    private function authorizeAdminOnly(): void
    {
        abort_unless(! $this->ownMode() && Gate::allows('view-legacy-managercontrol'), 403);
    }

    /** @return array{0: list<array<string, mixed>>, 1: array<int, string>, 2: array<string, int>} */
    private function linkRows(array $employees): array
    {
        $saved = ManagerControlEmployeeLink::query()->pluck('legacy_employee_name', 'user_id')->all();
        $matcher = new NameMatcher;
        $rows = [];
        foreach (User::query()->orderBy('name')->orderBy('last_name')->limit(300)->get(['id', 'name', 'last_name', 'email']) as $user) {
            $suggestion = null;
            if (! isset($saved[$user->id]) && $employees !== []) {
                $match = $matcher->bestMatch(trim($user->name.' '.$user->last_name), $employees);
                $suggestion = $match['employee'] ? ['id' => $match['employee']['id'], 'name' => $match['employee']['name'], 'score' => $match['score']] : null;
            }
            $rows[] = ['id' => $user->id, 'name' => trim($user->name.' '.$user->last_name), 'email' => $user->email, 'suggestion' => $suggestion];
        }

        $counts = ['all' => count($rows), 'assigned' => count(array_filter($rows, fn (array $r): bool => isset($saved[$r['id']])))];
        $counts['unassigned'] = $counts['all'] - $counts['assigned'];

        $needle = Str::lower(Str::ascii(trim($this->linkSearch)));
        $rows = array_values(array_filter($rows, function (array $row) use ($saved, $needle): bool {
            $isAssigned = isset($saved[$row['id']]);
            if (($this->linkFilter === 'assigned' && ! $isAssigned) || ($this->linkFilter === 'unassigned' && $isAssigned)) {
                return false;
            }

            return $needle === '' || str_contains(Str::lower(Str::ascii($row['name'].' '.$row['email'])), $needle);
        }));

        return [$rows, $saved, $counts];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(Gate::allows('view-legacy-managercontrol'), 403);
    }

    protected function ownMode(): bool
    {
        return false;
    }

    protected function matchedName(): ?string
    {
        return null;
    }

    /** Colaborador de ManagerControl a consultar (null si no hay uno válido). */
    protected function employeeForQuery(): ?int
    {
        return ctype_digit($this->employeeId) && (int) $this->employeeId >= 1 ? (int) $this->employeeId : null;
    }

    public function updatedDetailFrom(): void
    {
        $this->detailFrom = Carbon::hasFormat($this->detailFrom, 'Y-m-d') ? $this->detailFrom : Carbon::parse($this->detailTo)->subDays(14)->toDateString();
    }

    public function updatedDetailTo(): void
    {
        $this->detailTo = Carbon::hasFormat($this->detailTo, 'Y-m-d') ? $this->detailTo : $this->today();
    }

    public function applyDetailFilters(): void
    {
        $this->authorizeAccess();

        if ($this->detailFrom > $this->detailTo) {
            [$this->detailFrom, $this->detailTo] = [$this->detailTo, $this->detailFrom];
        }
        if ($this->employeeForQuery() === null) {
            $this->detailLoaded = false;
            $this->error = $this->ownMode() ? null : 'Seleccione un colaborador antes de consultar.';

            return;
        }

        $this->error = null;
        $this->appliedDetailFrom = $this->detailFrom;
        $this->appliedDetailTo = $this->detailTo;
        $this->detailLoaded = true;
    }

    public function updatedFrom(): void
    {
        $this->from = Carbon::hasFormat($this->from, 'Y-m-d') ? $this->from : Carbon::parse($this->to)->subDays(14)->toDateString();
    }

    public function updatedTo(): void
    {
        $this->to = Carbon::hasFormat($this->to, 'Y-m-d') ? $this->to : $this->today();
    }

    public function applyFilters(): void
    {
        $this->authorizeAccess();

        if ($this->from > $this->to) {
            [$this->from, $this->to] = [$this->to, $this->from];
        }
        if ($this->employeeForQuery() === null) {
            $this->loaded = false;
            $this->error = $this->ownMode() ? null : 'Seleccione un colaborador antes de consultar.';

            return;
        }

        $this->error = null;
        $this->appliedFrom = $this->from;
        $this->appliedTo = $this->to;
        $this->loaded = true;
    }

    #[Computed]
    public function employees(): array
    {
        try {
            return app(ManagerControlRepository::class)->employees();
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);

            return [];
        }
    }

    public function render()
    {
        $summary = HoursSummary::fromRows([]);
        $detail = HoursSummary::fromRows([]);
        $employeeId = $this->employeeForQuery();
        try {
            if ($employeeId !== null && $this->loaded) {
                $summary = HoursSummary::fromRows(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedFrom, $this->appliedTo));
            }
            if ($employeeId !== null && $this->detailLoaded) {
                $detail = HoursSummary::fromRows(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedDetailFrom, $this->appliedDetailTo));
            }
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);
            $this->loaded = $this->detailLoaded = false;
        }

        $employees = $this->ownMode() ? [] : $this->employees;
        [$linkRows, $linkSaved, $linkCounts] = $this->ownMode() ? [[], [], ['all' => 0, 'assigned' => 0, 'unassigned' => 0]] : $this->linkRows($employees);

        return view('livewire.testing.managercontrol-hours', [
            'linkRows' => $linkRows,
            'linkSaved' => $linkSaved,
            'linkCounts' => $linkCounts,
            'summary' => $summary,
            'detail' => $detail,
            'employees' => $employees,
            'ownMode' => $this->ownMode(),
            'matchedName' => $this->matchedName(),
            'pdfRoute' => $this->ownMode() ? 'testing.own-hours.pdf' : 'testing.managercontrol.pdf',
            'pdfExtra' => $this->ownMode() ? [] : ['employee' => $this->employeeId],
        ])->layout('layouts.app');
    }

    // Nunca se silencia un error: se muestra al administrador y se registra sin datos sensibles.
    protected function reportFailure(ManagerControlException $e): void
    {
        $this->error = $e->getMessage();
        Log::warning('ManagerControl (solo lectura): '.$e->getMessage());
    }

    private function today(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return Carbon::now($timezone === 'UTC' ? 'America/Mexico_City' : $timezone)->toDateString();
    }
}
