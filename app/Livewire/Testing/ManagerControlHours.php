<?php

namespace App\Livewire\Testing;

use App\Models\ManagerControlEmployeeLink;
use App\Models\User;
use App\Services\ManagerControl\ClockComparison;
use App\Services\ManagerControl\HoursSummary;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use App\Services\ManagerControl\ManagerControlWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
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

    // Captura (agregar / editar) de una actividad en ManagerControl. Solo funciona con la escritura habilitada.
    public bool $formOpen = false;

    public ?int $editingId = null;

    /** Cómo estaba el registro al abrir «Editar» (para no sobrescribir cambios hechos desde el escritorio). */
    #[Locked]
    public ?array $editingOriginal = null;

    public string $formDate = '';

    public string $formCustomer = '';

    public string $formActivity = '';

    // «Cuenta/Concepto» (sección): agrupa las actividades como en la app de escritorio; solo sirve para elegir y no se guarda en el registro.
    public string $formSection = '';

    /** Campos que el formulario llenó solo (cliente, cuenta y actividad que más usa): se ven en gris hasta que la persona los elige. */
    #[Locked]
    public array $formAuto = [];

    /** Días (Y-m-d) en que el colaborador ya tiene actividades: se pintan en verde en el calendario del formulario. */
    #[Locked]
    public array $formMarks = [];

    public string $formHours = '';

    public string $formDescription = '';

    /** Ventana emergente (error o confirmación) con el diseño del panel; nunca se falla en silencio. */
    #[Locked]
    public ?array $dialog = null;

    #[Locked]
    public ?int $deleteId = null;

    /** Cada error nuevo lleva su propio aviso (de 5 segundos), aunque el anterior siga a la vista. */
    #[Locked]
    public int $dialogSeq = 0;

    public ?string $writeNotice = null;

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

            $this->selectBusiestEmployee();
        }
    }

    /** Al entrar se elige a quien más registros tiene en el periodo y se cargan las dos tablas (sin pulsar nada). */
    private function selectBusiestEmployee(): void
    {
        try {
            $repository = app(ManagerControlRepository::class);
            $known = array_column($this->employees, 'id');
            foreach ($repository->topEmployeeIds($this->from, $this->to) as $id) {
                if (in_array($id, $known, true)) {
                    $this->employeeId = (string) $id;
                    $this->applyFilters();
                    $this->applyDetailFilters();

                    return;
                }
            }
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);
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
        $rows = [];
        foreach (User::query()->with('role:id,role')->orderBy('name')->orderBy('last_name')->limit(300)->get(['id', 'name', 'last_name', 'email', 'role_id']) as $user) {
            $rows[] = ['id' => $user->id, 'name' => trim($user->name.' '.$user->last_name), 'email' => (string) $user->email, 'role' => (string) ($user->role?->role ?? '')];
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

    /** Al elegir un colaborador se consultan solos el periodo de arriba y el del detalle (no hay que pulsar Consultar). */
    public function updatedEmployeeId(): void
    {
        if ($this->ownMode()) {
            return;
        }
        if ($this->employeeForQuery() === null) {
            $this->loaded = $this->detailLoaded = false;
            $this->error = null;

            return;
        }

        $this->applyFilters();
        $this->applyDetailFilters();
    }

    /** ¿Se puede capturar para el colaborador que se está viendo? (escritura habilitada + permiso sobre ese colaborador) */
    protected function canWrite(): bool
    {
        $employeeId = $this->employeeForQuery();

        return $employeeId !== null
            && app(ManagerControlWriter::class)->enabled()
            && Gate::allows('edit-legacy-hours', $employeeId);
    }

    private function guardWrite(): ?int
    {
        $this->authorizeAccess();
        $employeeId = $this->employeeForQuery();
        if ($employeeId === null) {
            $this->fail('Falta elegir a la persona', 'Elige primero al colaborador para poder capturar su actividad.');

            return null;
        }
        abort_unless(Gate::allows('edit-legacy-hours', $employeeId), 403);
        if (! app(ManagerControlWriter::class)->enabled()) {
            $this->fail('ManagerControl está en solo lectura', 'La captura se activará cuando se den los permisos de escritura en ManagerControl.');

            return null;
        }

        return $employeeId;
    }

    public function openCreate(): void
    {
        $employeeId = $this->guardWrite();
        if ($employeeId === null) {
            return;
        }
        $this->editingId = null;
        $this->editingOriginal = null;
        $this->formDate = $this->today();
        $this->formCustomer = $this->formActivity = $this->formSection = $this->formHours = $this->formDescription = '';
        $this->dialog = null;
        $this->rememberLastCapture($employeeId);
        $this->formAuto = array_filter([
            'customer' => $this->formCustomer !== '',
            'section' => $this->formSection !== '',
            'activity' => $this->formActivity !== '',
        ]);
        $this->loadMarks($employeeId);
        $this->formOpen = true;
    }

    /** Deja elegidos el cliente, la cuenta y la actividad que más usa (la fecha, el tiempo y la descripción van en blanco). */
    private function rememberLastCapture(int $employeeId): void
    {
        try {
            $repository = app(ManagerControlRepository::class);
            $last = $repository->mostUsedCapture($employeeId);
            if ($last === null) {
                return;
            }
            $customerKnown = in_array($last['customer_id'], array_column($repository->catalog('customers'), 'id'), true);
            $activity = collect($repository->catalog('activities'))->firstWhere('id', $last['activity_id']);
            if ($customerKnown) {
                $this->formCustomer = (string) $last['customer_id'];
            }
            if ($activity !== null) {
                $this->formActivity = (string) $activity['id'];
                $this->formSection = (string) ($activity['section_id'] ?? '');
            }
        } catch (ManagerControlException $e) {
            // Es solo una comodidad: si falla, el formulario abre vacío y el error real se mostrará al guardar.
            Log::warning('ManagerControl (última captura): '.$e->getMessage());
        }
    }

    private function loadMarks(int $employeeId): void
    {
        try {
            $this->formMarks = app(ManagerControlRepository::class)->activityDates($employeeId, Carbon::parse($this->today())->subDays(400)->toDateString(), Carbon::parse($this->today())->addDays(60)->toDateString());
        } catch (ManagerControlException $e) {
            $this->formMarks = [];
            Log::warning('ManagerControl (días con actividad): '.$e->getMessage());
        }
    }

    public function updatedFormCustomer(): void
    {
        unset($this->formAuto['customer']);
    }

    /** Al elegir otra cuenta/concepto, la actividad elegida se limpia si ya no pertenece a ella. */
    public function updatedFormSection(): void
    {
        unset($this->formAuto['section']);
        if ($this->formSection === '' || $this->formActivity === '') {
            return;
        }
        $activity = collect($this->catalogSafe('activities'))->firstWhere('id', (int) $this->formActivity);
        if ($activity === null || (string) ($activity['section_id'] ?? '') !== $this->formSection) {
            $this->formActivity = '';
            unset($this->formAuto['activity']);
        }
    }

    /** Al elegir una actividad se ajusta su cuenta/concepto. */
    public function updatedFormActivity(): void
    {
        unset($this->formAuto['activity'], $this->formAuto['section']);
        $activity = collect($this->catalogSafe('activities'))->firstWhere('id', (int) $this->formActivity);
        if ($activity !== null) {
            $this->formSection = (string) ($activity['section_id'] ?? '');
        }
    }

    public function openEdit(int $recordId): void
    {
        $employeeId = $this->guardWrite();
        if ($employeeId === null) {
            return;
        }
        $row = collect(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedDetailFrom, $this->appliedDetailTo))->firstWhere('id', $recordId);
        if ($row === null) {
            $this->fail('Registro no disponible', 'Ese registro ya no está en el periodo consultado. Vuelve a consultar la tabla.');

            return;
        }
        $this->editingId = $recordId;
        $this->editingOriginal = ['date' => $row['date'], 'customer_id' => $row['customer_id'], 'activity_id' => $row['activity_id'], 'hours' => $row['hours'], 'description_raw' => $row['description_raw'] ?? $row['description']];
        $this->formDate = $row['date'];
        $this->formCustomer = (string) $row['customer_id'];
        $this->formActivity = (string) $row['activity_id'];
        $this->formSection = (string) (collect($this->catalogSafe('activities'))->firstWhere('id', $row['activity_id'])['section_id'] ?? '');
        $minutes = (int) round($row['hours'] * 60);
        $this->formHours = intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
        $this->formDescription = $row['description'];
        $this->dialog = null;
        $this->formAuto = [];
        $this->loadMarks($employeeId);
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->dialog = null;
    }

    public function closeDialog(): void
    {
        $this->dialog = null;
        $this->deleteId = null;
    }

    private function fail(string $title, string $message): void
    {
        $this->dialogSeq++;
        $this->dialog = ['kind' => 'error', 'title' => $title, 'subtitle' => 'Revisa el dato e inténtalo de nuevo.', 'message' => $message];
    }

    public function saveRecord(): void
    {
        $employeeId = $this->guardWrite();
        if ($employeeId === null) {
            return;
        }
        $data = ['date' => $this->formDate, 'customer_id' => $this->formCustomer, 'activity_id' => $this->formActivity, 'hours' => $this->formHours, 'description' => $this->formDescription];
        try {
            $writer = app(ManagerControlWriter::class);
            $this->editingId === null
                ? $writer->create($employeeId, $data, auth()->user())
                : $writer->update($this->editingId, $employeeId, $data, auth()->user(), $this->editingOriginal);
        } catch (ManagerControlException $e) {
            // Se muestra en una ventana emergente; nunca falla en silencio.
            $this->fail('No se pudo guardar la actividad', $e->getMessage());
            Log::warning('ManagerControl (captura): '.$e->getMessage());

            return;
        }
        $this->writeNotice = $this->editingId === null ? 'Actividad agregada en ManagerControl.' : 'Actividad actualizada en ManagerControl.';
        // La tabla muestra solo los encabezados de cada día: se despliega el día que se acaba de guardar para verlo.
        $this->dispatch('mc-day-open', date: $this->formDate);
        $this->formOpen = false;
        $this->editingId = null;
        $this->formMarks = [];
    }

    /** Pide confirmación (con el diseño del panel) antes de dar de baja una actividad. */
    public function askDelete(int $recordId): void
    {
        $employeeId = $this->guardWrite();
        if ($employeeId === null) {
            return;
        }
        $row = collect(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedDetailFrom, $this->appliedDetailTo))->firstWhere('id', $recordId);
        if ($row === null) {
            $this->fail('Registro no disponible', 'Ese registro ya no está en el periodo consultado. Vuelve a consultar la tabla.');

            return;
        }
        $repository = app(ManagerControlRepository::class);
        try {
            $activity = collect($repository->catalog('activities'))->firstWhere('id', $row['activity_id']);
            $sectionName = collect($repository->catalog('sections'))->firstWhere('id', $activity['section_id'] ?? 0)['name'] ?? '';
        } catch (ManagerControlException) {
            $sectionName = '';
        }
        $this->deleteId = $recordId;
        // La ventana se ve como el formulario de la actividad, pero bloqueado: solo se puede dar de baja o cancelar.
        $minutes = (int) round($row['seconds'] / 60);
        $this->dialog = [
            'kind' => 'confirm',
            'title' => 'Dar de baja la actividad',
            'subtitle' => 'Dejará de contar en ManagerControl y en los reportes.',
            'fields' => [
                'date' => Carbon::parse($row['date'])->format('d/m/y'),
                'time' => intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT),
                'customer' => $row['customer'],
                'section' => $sectionName,
                'activity' => $row['activity'],
                'description' => $row['description'],
            ],
        ];
    }

    public function confirmDelete(): void
    {
        $recordId = $this->deleteId;
        $this->closeDialog();
        $employeeId = $this->guardWrite();
        if ($employeeId === null || $recordId === null) {
            return;
        }
        try {
            app(ManagerControlWriter::class)->delete($recordId, $employeeId, auth()->user());
            $this->writeNotice = 'Actividad dada de baja en ManagerControl.';
        } catch (ManagerControlException $e) {
            $this->fail('No se pudo dar de baja la actividad', $e->getMessage());
            Log::warning('ManagerControl (baja): '.$e->getMessage());
        }
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
                $samePeriod = $this->loaded && $this->appliedFrom === $this->appliedDetailFrom && $this->appliedTo === $this->appliedDetailTo;
                $detail = $samePeriod
                    ? $summary
                    : HoursSummary::fromRows(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedDetailFrom, $this->appliedDetailTo));
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
            'canWrite' => $this->canWrite(),
            'writeEnabled' => app(ManagerControlWriter::class)->enabled(),
            'formCustomers' => $this->formOpen ? $this->catalogSafe('customers') : [],
            'formActivities' => $this->formOpen ? $this->activitiesForForm() : [],
            'formSections' => $this->formOpen ? $this->sectionsForForm() : [],
            'clock' => $this->clockComparison($employeeId),
        ])->layout('layouts.app');
    }

    /**
     * Tiempo del reloj checador por día en el periodo del detalle, para el globo informativo que lo compara con ManagerControl.
     * Si falla la lectura, el globo lo dice (nunca se queda en silencio).
     *
     * @return array{state: string, days: array<string, array{seconds: int, review: bool, marks: int}>}
     */
    private function clockComparison(?int $employeeId): array
    {
        if ($employeeId === null || ! $this->detailLoaded) {
            return ['state' => 'ok', 'days' => []];
        }
        try {
            $service = app(ClockComparison::class);
            $user = $this->ownMode() ? auth()->user() : $service->userForLegacyEmployee($employeeId);

            return $service->days($user, $this->appliedDetailFrom, $this->appliedDetailTo);
        } catch (\Throwable $e) {
            Log::warning('ManagerControl (reloj checador): '.$e->getMessage());

            return ['state' => 'error', 'days' => []];
        }
    }

    /** Actividades de la cuenta/concepto elegida (todas si aún no se elige cuenta). */
    private function activitiesForForm(): array
    {
        $all = $this->catalogSafe('activities');
        if ($this->formSection === '') {
            return $all;
        }

        return array_values(array_filter($all, fn (array $activity): bool => (string) ($activity['section_id'] ?? '') === $this->formSection));
    }

    /** Cuentas/conceptos que tienen actividades. */
    private function sectionsForForm(): array
    {
        $withActivities = array_unique(array_column($this->catalogSafe('activities'), 'section_id'));

        return array_values(array_filter($this->catalogSafe('sections'), fn (array $section): bool => in_array($section['id'], $withActivities, true)));
    }

    // Nunca se silencia un error: se muestra al administrador y se registra sin datos sensibles.
    /** @return list<array{id: int, name: string, section_id?: int}> */
    private function catalogSafe(string $key): array
    {
        try {
            return app(ManagerControlRepository::class)->catalog($key);
        } catch (ManagerControlException $e) {
            $this->fail('No se pudo leer ManagerControl', $e->getMessage());

            return [];
        }
    }

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
