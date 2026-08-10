<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\User;
use App\Models\TimeEntry;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\TimeReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTimeDashboard extends Component
{
    public string $from;

    public string $to;

    public ?int $userId = null;

    public string $searchCollaborator = '';

    /** Estado aislado del nuevo informe consolidado. */
    public string $activeReportTab = 'group';

    /** @var list<int> */
    public array $selectedCollaboratorIds = [];

    /** @var list<int> Colaboradores de la última consulta grupal confirmada. */
    public array $reportedCollaboratorIds = [];

    public bool $groupReportIsCurrent = false;

    public int $groupReportVersion = 0;

    /**
     * Directorio liviano para el selector grupal. Se hidrata una vez al montar
     * el componente y se usa en memoria durante los cambios de casillas.
     *
     * @var list<array<string, mixed>>
     */
    public array $groupCollaboratorDirectory = [];

    // 🆕 Propiedades añadidas para el control de Modificaciones y Bonos
    public bool $showModal = false;
    public ?int $selectedUserId = null;
    public string $selectedUserName = '';
    public string $entryDate;
    public $totalHours = 0;
    public $dailyBonus = 0;
    public string $bonusReason = '';

    public bool $showActivityEditModal = false;

    public ?int $editingActivityId = null;

    public string $editingActivityName = '';

    public string $activityStartTime = '';

    public string $activityEndTime = '';

    public string $activityCorrectionComment = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);
        // Rango predeterminado de fechas
        $this->from = $this->localToday();
        $this->to = $this->localToday();
        $this->activeReportTab = 'group';
        $this->groupCollaboratorDirectory = $this->groupCollaborators()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => trim($user->name.' '.($user->last_name ?? '')),
            'area_id' => $user->activeOrganizationalProfile?->physical_area_id,
            'area_name' => $user->activeOrganizationalProfile?->physicalArea?->name ?? 'Sin área asignada',
            'position_id' => $user->activeOrganizationalProfile?->job_position_id,
            'position_name' => $user->activeOrganizationalProfile?->jobPosition?->name ?? 'Sin puesto asignado',
            'superiors' => $user->superiors->map(fn (User $superior) => [
                'id' => $superior->id,
                'name' => trim($superior->name.' '.($superior->last_name ?? '')),
            ])->values()->all(),
        ])->all();
    }

    public function selectCollaborator(int $id, string $fullName): void
    {
        $this->userId = $id;
        $this->searchCollaborator = $fullName;
    }

    public function clearCollaborator(): void
    {
        $this->reset(['userId', 'searchCollaborator']);
    }

    public function showIndividualReport(): void
    {
        $this->activeReportTab = 'individual';
    }

    public function showGroupReport(): void
    {
        $this->activeReportTab = 'group';
    }

    public function selectAllCollaborators(): void
    {
        $this->selectedCollaboratorIds = $this->groupDirectory()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->groupReportIsCurrent = false;
    }

    public function clearGroupSelection(): void
    {
        $this->selectedCollaboratorIds = [];
        $this->groupReportIsCurrent = false;
    }

    /**
     * Confirma una instantánea explícita del selector para el reporte.
     * Los IDs pueden venir directamente del estado local del selector para
     * evitar depender de la hidratación diferida de cientos de checkboxes.
     *
     * @param  list<int|string>|null  $collaboratorIds
     */
    public function generateGroupReport(?array $collaboratorIds = null, ?string $from = null, ?string $to = null): void
    {
        if ($from !== null) {
            $this->from = $from;
        }

        if ($to !== null) {
            $this->to = $to;
        }

        if ($collaboratorIds !== null) {
            $allowedIds = $this->groupDirectory()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->selectedCollaboratorIds = array_values(array_intersect(
                array_values(array_unique(array_filter(array_map('intval', $collaboratorIds)))),
                $allowedIds,
            ));
        }

        $this->groupReportIsCurrent = false;
        $this->reportedCollaboratorIds = [];
        $this->resetErrorBag('selectedCollaboratorIds');

        $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ]);

        $this->reportedCollaboratorIds = array_values(array_unique(array_map('intval', $this->selectedCollaboratorIds)));

        if ($this->reportedCollaboratorIds === []) {
            $this->addError('selectedCollaboratorIds', 'Selecciona al menos un colaborador para generar el informe.');
            return;
        }

        $this->groupReportVersion++;
        $this->groupReportIsCurrent = true;
    }

    public function selectCollaboratorsByArea(int|string $areaId): void
    {
        $areaId = (int) $areaId;
        if ($areaId <= 0) {
            return;
        }

        $this->addToGroupSelection($this->groupDirectory()
            ->where('area_id', $areaId)->pluck('id')->all());
    }

    public function toggleCollaboratorsByArea(int|string $areaId, bool|string $selected): void
    {
        $areaId = (int) $areaId;
        if ($areaId <= 0) {
            return;
        }

        $areaUserIds = $this->groupDirectory()->where('area_id', $areaId)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selected = filter_var($selected, FILTER_VALIDATE_BOOLEAN);

        $this->selectedCollaboratorIds = $selected
            ? array_values(array_unique(array_merge($this->selectedCollaboratorIds, $areaUserIds)))
            : array_values(array_diff(array_map('intval', $this->selectedCollaboratorIds), $areaUserIds));
        $this->groupReportIsCurrent = false;
    }

    public function selectCollaboratorsByPosition(int|string $positionId): void
    {
        $positionId = (int) $positionId;
        if ($positionId <= 0) {
            return;
        }

        $this->addToGroupSelection($this->groupDirectory()
            ->where('position_id', $positionId)->pluck('id')->all());
    }

    public function selectCollaboratorsBySuperior(int|string $superiorId): void
    {
        $superiorId = (int) $superiorId;
        if ($superiorId <= 0) {
            return;
        }

        $this->addToGroupSelection($this->groupDirectory()
            ->filter(fn (array $user) => collect($user['superiors'])->contains('id', $superiorId))->pluck('id')->all());
    }

    // 🆕 Abre el modal de ajuste cargando o sugiriendo datos iniciales
    public function openAdjustModal(int $userId): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $user = User::findOrFail($userId);
        $this->selectedUserId = $user->id;
        $this->selectedUserName = $user->name . ' ' . $user->last_name;
        
        $this->entryDate = $this->localToday();
        $this->totalHours = 8.00; 
        $this->dailyBonus = 0.00;
        $this->bonusReason = '';

        $this->showModal = true;
    }

    // 🆕 Guarda la corrección, horas manuales y bonos en la base de datos
    public function saveAdjustment(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'entryDate' => 'required|date',
            'totalHours' => 'required|numeric|min:0|max:24',
            'dailyBonus' => 'required|numeric|min:0',
            'bonusReason' => 'nullable|required_if:dailyBonus,>,0|string|max:255',
        ], [
            'bonusReason.required_if' => 'Debe especificar el motivo del bono asignado.',
        ]);

        try {
            DB::transaction(function () {
                TimeEntry::updateOrCreate(
                    [
                        'user_id' => $this->selectedUserId,
                        'date' => $this->entryDate,
                    ],
                    [
                        'total_hours' => $this->totalHours,
                        'bonus' => $this->dailyBonus,
                        'bonus_reason' => $this->bonusReason,
                    ]
                );
            });

            session()->flash('success', 'Ajuste operativo y bonificación aplicados correctamente.');
            $this->showModal = false;
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Error al procesar el ajuste: ' . $e->getMessage());
        }
    }

    public function openActivityEditModal(int $entryId): void
    {
        abort_unless(Gate::allows('correct-time-tracking'), 403);

        $entry = $this->editableReportedEntry($entryId);
        $intervals = $entry->intervals->sortBy('started_at')->values();
        $first = $intervals->first();
        $last = $intervals->sortByDesc('id')->first();

        if (! $first || ! $last || ! $last->ended_at) {
            $this->addError('activityEdit', 'La actividad debe estar finalizada antes de corregir sus horas.');
            return;
        }

        $this->resetValidation();
        $this->editingActivityId = $entry->id;
        $this->editingActivityName = $entry->subService?->sub_service ?? 'Actividad';
        $this->activityStartTime = $first->started_at->format('H:i:s');
        $this->activityEndTime = $last->ended_at->format('H:i:s');
        $this->activityCorrectionComment = '';
        $this->showActivityEditModal = true;
    }

    public function closeActivityEditModal(): void
    {
        $this->reset([
            'showActivityEditModal', 'editingActivityId', 'editingActivityName',
            'activityStartTime', 'activityEndTime', 'activityCorrectionComment',
        ]);
        $this->resetValidation();
    }

    public function saveActivityTimes(): void
    {
        abort_unless(Gate::allows('correct-time-tracking'), 403);

        $this->activityCorrectionComment = trim($this->activityCorrectionComment);

        $this->validate([
            'editingActivityId' => ['required', 'integer'],
            'activityStartTime' => ['required', 'date_format:H:i:s'],
            'activityEndTime' => ['required', 'date_format:H:i:s'],
            'activityCorrectionComment' => ['required', 'string', 'min:5', 'max:500'],
        ], [], [
            'activityStartTime' => 'hora de inicio',
            'activityEndTime' => 'hora de fin',
            'activityCorrectionComment' => 'comentario o motivo de la corrección',
        ]);

        $entry = $this->editableReportedEntry((int) $this->editingActivityId);
        $oldValues = $this->activitySnapshot($entry);

        DB::transaction(function () use ($entry, $oldValues): void {
            $entry->refresh()->load('intervals');
            $intervals = $entry->intervals->sortBy('started_at')->values();
            $first = $intervals->first();
            $last = $intervals->sortByDesc('id')->first();

            abort_unless($first && $last && $last->ended_at, 422, 'La actividad ya no está disponible para edición.');

            // Conserva la fecha y zona horaria con las que Eloquent hidrató el
            // intervalo; el input solamente reemplaza la porción de hora.
            $newStart = $first->started_at->copy()->setTimeFromTimeString($this->activityStartTime);
            $newEnd = $last->ended_at->copy()->setTimeFromTimeString($this->activityEndTime);

            if ($newEnd->lessThanOrEqualTo($newStart)) {
                $this->addError('activityEndTime', 'La hora de fin debe ser posterior a la hora de inicio.');
                return;
            }
            if ($first->ended_at && $newStart->greaterThanOrEqualTo($first->ended_at)) {
                $this->addError('activityStartTime', 'La hora de inicio debe ser anterior al cierre de su primer intervalo.');
                return;
            }
            if ($newEnd->lessThanOrEqualTo($last->started_at)) {
                $this->addError('activityEndTime', 'La hora de fin debe ser posterior al inicio de su último intervalo.');
                return;
            }

            $first->started_at = $newStart;
            $first->save();
            $last->ended_at = $newEnd;
            $last->save();

            $entry->load('intervals');
            $entry->total_duration_seconds = $entry->calculateEffectiveSeconds();
            $entry->save();

            $entry->audits()->create([
                'admin_id' => auth()->id(),
                'old_values' => $oldValues,
                'new_values' => $this->activitySnapshot($entry->fresh('intervals')),
                'reason' => $this->activityCorrectionComment,
            ]);
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->closeActivityEditModal();
        session()->flash('success', 'La actividad se actualizó y las horas efectivas fueron recalculadas.');
    }

    public function export(string $format, TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $user = $this->userId ? User::find($this->userId) : null;
        $report = $reports->adminReport($user, $this->from, $this->to);

        $this->skipRender();

        return $exporter->download($format, $report);
    }

    public function exportGroup(string $format, TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $ids = $this->reportedGroupDirectory()->pluck('id')->all();
        $users = User::whereIn('id', $ids)
            ->with([
                'activeOrganizationalProfile.jobPosition:id,name',
                'activeOrganizationalProfile.physicalArea:id,name',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'last_name']);
        if (! $this->groupReportIsCurrent || $users->isEmpty()) {
            $this->addError('selectedCollaboratorIds', 'Selecciona al menos un colaborador para descargar el informe grupal.');
            abort(422);
        }

        $this->skipRender();

        return $exporter->download($format, $reports->groupReport($users, $this->from, $this->to));
    }

    public function exportSelectedIndividualReport(TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        $users = $this->exportableReportedUsers();

        if ($users->count() !== 1) {
            $this->addError('selectedCollaboratorIds', 'Selecciona exactamente un colaborador para descargar su reporte individual.');
            abort(422);
        }

        $this->skipRender();

        return $exporter->download('pdf', $reports->adminReport($users->first(), $this->from, $this->to));
    }

    public function exportSelectedIndividualBatch(TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        $users = $this->exportableReportedUsers();
        $this->skipRender();

        return $exporter->download('pdf', $reports->individualReportsBatch($users, $this->from, $this->to));
    }

    public function exportSelectedGeneralReport(TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        $users = $this->exportableReportedUsers();
        $this->skipRender();

        return $exporter->download('pdf', $reports->groupReport($users, $this->from, $this->to));
    }

    public function render(TimeReportService $reports, ReportExportManager $exporter)
    {
        $groupUsers = $this->groupDirectory();
        $selectedGroupUsers = $this->selectedGroupDirectory();
        $reportedGroupUsers = $this->reportedGroupDirectory();
        $groupData = $this->groupReportIsCurrent && $reportedGroupUsers->isNotEmpty()
            ? $reports->adminSupervisionForUsers($reportedGroupUsers->pluck('id')->all(), $this->from, $this->to, $reportedGroupUsers)
            : [
                'entries' => collect(), 'total' => 0, 'byCollaborator' => collect(), 'byCustomer' => collect(),
                'byPosition' => collect(), 'byArea' => collect(), 'autoClosedCount' => 0,
            ];
        $groupActivityDetail = $this->groupReportIsCurrent && $reportedGroupUsers->isNotEmpty()
            ? $reports->activityDetailByDay($groupData['entries'], true, true)
            : ['columns' => [], 'groups' => []];

        return view('livewire.time-control.admin.dashboard', [
            'exportFormats' => $exporter->formats(),
            'groupUsers' => $groupUsers,
            'selectedGroupUsers' => $selectedGroupUsers,
            'reportedGroupUsers' => $reportedGroupUsers,
            'groupData' => $groupData,
            'groupActivityDetail' => $groupActivityDetail,
        ])->layout('layouts.app');
    }

    /** @return Collection<int, User> */
    private function groupCollaborators(): Collection
    {
        return User::query()
            ->whereDoesntHave('role', fn ($q) => $q->where('role', 'Administrador'))
            ->with([
                'activeOrganizationalProfile.jobPosition:id,name',
                'activeOrganizationalProfile.physicalArea:id,name',
                'superiors:id,name,last_name',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'last_name']);
    }

    /** @return Collection<int, array{id:int, name:string}> */
    private function groupDirectory(): Collection
    {
        return collect($this->groupCollaboratorDirectory);
    }

    /** @return Collection<int, array{id:int, name:string}> */
    private function selectedGroupDirectory(): Collection
    {
        $ids = array_map('intval', $this->selectedCollaboratorIds);

        return $this->groupDirectory()->whereIn('id', $ids)->values();
    }

    /** @return Collection<int, array{id:int, name:string}> */
    private function reportedGroupDirectory(): Collection
    {
        return $this->groupDirectory()->whereIn('id', array_map('intval', $this->reportedCollaboratorIds))->values();
    }

    /** @return Collection<int, User> */
    private function exportableReportedUsers(): Collection
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $ids = $this->reportedGroupDirectory()->pluck('id')->all();
        $users = User::whereIn('id', $ids)
            ->with([
                'activeOrganizationalProfile.jobPosition:id,name',
                'activeOrganizationalProfile.physicalArea:id,name',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'last_name']);

        if (! $this->groupReportIsCurrent || $users->isEmpty()) {
            $this->addError('selectedCollaboratorIds', 'Genera el informe con al menos un colaborador antes de descargarlo.');
            abort(422);
        }

        return $users;
    }

    /** @param list<int> $ids */
    private function addToGroupSelection(array $ids): void
    {
        $this->selectedCollaboratorIds = array_values(array_unique(array_merge(
            array_map('intval', $this->selectedCollaboratorIds),
            array_map('intval', $ids),
        )));
        $this->groupReportIsCurrent = false;
    }

    private function localToday(): string
    {
        return Carbon::now($this->moduleTimezone())->toDateString();
    }

    private function moduleTimezone(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return $timezone === 'UTC' ? 'America/Mexico_City' : $timezone;
    }

    private function editableReportedEntry(int $entryId): TimeEntry
    {
        abort_unless($this->groupReportIsCurrent && $this->reportedCollaboratorIds !== [], 404);

        $timezone = $this->moduleTimezone();
        $start = Carbon::parse($this->from, $timezone)->startOfDay();
        $end = Carbon::parse($this->to, $timezone)->endOfDay();

        return TimeEntry::query()
            ->whereKey($entryId)
            ->whereIn('user_id', array_map('intval', $this->reportedCollaboratorIds))
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereHas('intervals', fn ($intervals) => $intervals
                        ->where('started_at', '<=', $end->toDateTimeString())
                        ->where(fn ($range) => $range->whereNull('ended_at')->orWhere('ended_at', '>=', $start->toDateTimeString())));
            })
            ->with(['intervals', 'subService:id,sub_service'])
            ->firstOrFail();
    }

    private function activitySnapshot(TimeEntry $entry): array
    {
        return [
            'total_duration_seconds' => (int) $entry->total_duration_seconds,
            'status' => (int) $entry->status,
            'intervals' => $entry->intervals->map(fn ($interval) => [
                'id' => $interval->id,
                'started_at' => optional($interval->started_at)->toDateTimeString(),
                'ended_at' => optional($interval->ended_at)?->toDateTimeString(),
            ])->values()->all(),
        ];
    }
}
