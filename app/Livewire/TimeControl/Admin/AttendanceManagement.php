<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\User;
use App\Services\ReferenceDataCache;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\AttendanceExportService;
use App\Services\TimeControl\AttendanceService;
use App\Services\TimeControl\AttendanceSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceManagement extends Component
{
    public $searchCollaborator = '';

    public $userId = null;

    public $from;

    public $to;

    public $searched = false;

    /** @var list<int> */
    public array $selectedReportUserIds = [];

    /** @var list<int> */
    public array $reportedUserIds = [];

    public bool $selectionReportIsCurrent = false;

    public ?int $activeReportUserId = null;

    public ?string $lastReportGeneratedAt = null;

    public int $errorToastVersion = 0;

    public bool $showEmployeeIdModal = false;

    public bool $employeeIdSuggestionsRequested = false;

    public ?int $editingEmployeeUserId = null;

    public string $editingEmployeeName = '';

    public string $editingEmployeeId = '';

    public $editingHourlyRate = 20.00;

    public $editingFoodAllowance = 50.00;

    // Tarifas generales editables por el admin
    public $generalHourlyRate = 20.00;

    public $generalBonusAmount = 50.00;

    public $employeeId = '';

    // Modal de ajuste por día
    public $showAttendanceModal = false;

    public $selectedDate = '';

    public $selectedEmployeeName = '';

    public $modalHourlyRate = 0.00;

    public $modalBonusAmount = 50.00;

    public bool $selectedDateIsWeekend = false;

    public float $modalCalculatedBasePay = 0.0;

    public float $modalCalculatedTotal = 0.0;

    /** @var list<string> */
    public array $modalMarks = [];

    /** @var list<string> */
    public array $originalModalMarks = [];

    public string $modalChangeComment = '';

    /** @var array<int, array<string, mixed>> */
    public $payrollRows = [];

    /** @var array<string, string> */
    public $totalsFooter = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);
        $this->from = Carbon::now()->subDays(14)->toDateString();
        $this->to = Carbon::now()->toDateString();
        $this->lastReportGeneratedAt = Carbon::now()->subDay()->format('d/m/Y H:i');

    }

    public function clearCollaborator(): void
    {
        $this->userId = null;
        $this->searchCollaborator = '';
        $this->employeeId = '';
        $this->searched = false;
        $this->payrollRows = [];
        $this->totalsFooter = [];
    }

    public function selectAllReportUsers(): void
    {
        $this->selectedReportUserIds = User::query()
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->selectionReportIsCurrent = false;
    }

    public function clearReportSelection(): void
    {
        $this->selectedReportUserIds = [];
        $this->reportedUserIds = [];
        $this->activeReportUserId = null;
        $this->selectionReportIsCurrent = false;
        $this->clearCollaborator();
        $this->resetErrorBag('selectedReportUserIds');
    }

    public function updatedSelectedReportUserIds(): void
    {
        $this->selectionReportIsCurrent = false;
        $this->resetErrorBag('selectedReportUserIds');
    }

    public function updatedFrom(): void
    {
        $this->selectionReportIsCurrent = false;
    }

    public function updatedTo(): void
    {
        $this->selectionReportIsCurrent = false;
    }

    public function openEmployeeIdModal(int $userId, AttendanceSettingsService $settingsService): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $user = User::query()->with('activeOrganizationalProfile')->findOrFail($userId);

        $this->editingEmployeeUserId = $user->id;
        $this->editingEmployeeName = trim($user->name.' '.$user->last_name);
        $this->editingEmployeeId = (string) $user->employee_id;
        $profile = $user->activeOrganizationalProfile;
        $settings = filled($user->employee_id)
            ? $settingsService->getSettings(
                (string) $user->employee_id,
                $profile ? (float) $profile->hourly_rate : null,
                $profile ? (float) $profile->food_allowance : null,
            )
            : [
                'hourly_rate' => $profile ? (float) $profile->hourly_rate : AttendanceSettingsService::DEFAULT_HOURLY_RATE,
                'bonus_amount' => $profile ? (float) $profile->food_allowance : AttendanceSettingsService::DEFAULT_BONUS_AMOUNT,
            ];
        $this->editingHourlyRate = $settings['hourly_rate'];
        $this->editingFoodAllowance = $settings['bonus_amount'];
        $this->employeeIdSuggestionsRequested = false;
        $this->resetErrorBag(['editingEmployeeId', 'editingHourlyRate', 'editingFoodAllowance']);
        $this->showEmployeeIdModal = true;
    }

    public function loadEmployeeIdSuggestions(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        if ($this->showEmployeeIdModal) {
            $this->employeeIdSuggestionsRequested = true;
        }
    }

    public function closeEmployeeIdModal(): void
    {
        $this->showEmployeeIdModal = false;
        $this->employeeIdSuggestionsRequested = false;
        $this->reset(['editingEmployeeUserId', 'editingEmployeeName', 'editingEmployeeId']);
        $this->editingHourlyRate = AttendanceSettingsService::DEFAULT_HOURLY_RATE;
        $this->editingFoodAllowance = AttendanceSettingsService::DEFAULT_BONUS_AMOUNT;
        $this->resetErrorBag(['editingEmployeeId', 'editingHourlyRate', 'editingFoodAllowance']);
    }

    public function saveEmployeeId(
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): void {
        abort_unless(Gate::allows('view-time-admin'), 403);
        abort_unless($this->editingEmployeeUserId, 404);

        $this->editingEmployeeId = trim($this->editingEmployeeId);
        $this->errorToastVersion++;
        $this->validate([
            'editingEmployeeId' => [
                'required',
                'string',
                'max:50',
                Rule::exists('control_de_horas', 'employeeID'),
                Rule::unique('users', 'employee_id')->ignore($this->editingEmployeeUserId),
            ],
            'editingHourlyRate' => ['required', 'numeric', 'min:0'],
            'editingFoodAllowance' => ['required', 'numeric', 'min:0'],
        ], [
            'editingEmployeeId.required' => 'Selecciona o escribe el ID del checador.',
            'editingEmployeeId.exists' => 'El ID indicado no existe en los registros del checador.',
            'editingEmployeeId.unique' => 'Este ID de checador ya está relacionado con otra persona.',
            'editingHourlyRate.required' => 'Indica el pago por hora.',
            'editingHourlyRate.numeric' => 'El pago por hora debe ser un número válido.',
            'editingHourlyRate.min' => 'El pago por hora no puede ser menor a 0.',
            'editingFoodAllowance.required' => 'Indica el pago de comida.',
            'editingFoodAllowance.numeric' => 'El pago de comida debe ser un número válido.',
            'editingFoodAllowance.min' => 'El pago de comida no puede ser menor a 0.',
        ]);

        $user = User::query()->findOrFail($this->editingEmployeeUserId);
        DB::transaction(function () use ($user): void {
            $user->update(['employee_id' => $this->editingEmployeeId]);
            $user->activeOrganizationalProfile()->update([
                'hourly_rate' => (float) $this->editingHourlyRate,
                'food_allowance' => (float) $this->editingFoodAllowance,
            ]);
        });
        $settingsService->saveGeneral(
            $this->editingEmployeeId,
            (float) $this->editingHourlyRate,
            (float) $this->editingFoodAllowance,
            preserveDayOverrides: true,
        );

        $editedUserId = $user->id;
        $this->closeEmployeeIdModal();

        if ($this->activeReportUserId === $editedUserId || $this->userId === $editedUserId) {
            $this->userId = $editedUserId;
            $this->searchAttendance($attendanceService, $settingsService);
        }

        session()->flash('message', 'El ID, pago por hora y comida quedaron sincronizados correctamente.');
    }

    public function generateSelectionReport(
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): void {
        $this->errorToastVersion++;
        $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'selectedReportUserIds' => ['required', 'array', 'min:1'],
            'selectedReportUserIds.*' => ['integer'],
        ], [
            'selectedReportUserIds.required' => 'Selecciona al menos un colaborador para generar el informe.',
            'selectedReportUserIds.min' => 'Selecciona al menos un colaborador para generar el informe.',
        ]);

        $allowedIds = User::query()
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->reportedUserIds = array_values(array_intersect(
            array_values(array_unique(array_map('intval', $this->selectedReportUserIds))),
            $allowedIds,
        ));
        $this->selectionReportIsCurrent = $this->reportedUserIds !== [];

        if ($this->selectionReportIsCurrent) {
            $this->selectReportUser($this->reportedUserIds[0], $attendanceService, $settingsService);
            $this->lastReportGeneratedAt = now()->format('d/m/Y H:i');
        }
    }

    public function selectReportUser(
        int $userId,
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): void {
        abort_unless($this->selectionReportIsCurrent && in_array($userId, $this->reportedUserIds, true), 404);

        $this->activeReportUserId = $userId;
        $this->userId = $userId;
        $this->searchAttendance($attendanceService, $settingsService);
    }

    public function exportSelectionReport(
        string $mode,
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
        AttendanceExportService $attendanceExportService,
        ReportExportManager $exporter,
    ): StreamedResponse {
        abort_unless($this->selectionReportIsCurrent, 422);

        $users = User::query()
            ->whereIn('id', $this->reportedUserIds)
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->with('activeOrganizationalProfile')
            ->orderBy('name')
            ->orderBy('last_name')
            ->get();

        if ($mode === 'individual' && $users->count() !== 1) {
            $this->errorToastVersion++;
            $this->addError('selectedReportUserIds', 'Selecciona exactamente un colaborador para descargar el informe individual.');
            abort(422);
        }
        if (in_array($mode, ['group', 'general'], true) && $users->count() < 2) {
            $this->errorToastVersion++;
            $this->addError('selectedReportUserIds', 'Selecciona al menos dos colaboradores para descargar este informe.');
            abort(422);
        }
        abort_if($users->isEmpty(), 422);

        $report = $attendanceExportService->selectionReport(
            $mode,
            $users,
            $this->from,
            $this->to,
            $attendanceService,
            $settingsService,
        );
        $this->skipRender();

        return $exporter->download('pdf', $report);
    }

    public function searchAttendance(
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): void {
        if (! $this->userId) {
            return;
        }

        $user = User::with('activeOrganizationalProfile')->find($this->userId);

        if (! $user || empty($user->employee_id)) {
            session()->flash('error', 'El colaborador no tiene ID de checador asignado.');
            $this->searched = false;

            return;
        }

        $this->employeeId = $user->employee_id;
        $this->selectedEmployeeName = trim($user->name.' '.$user->last_name);

        $profile = $user->activeOrganizationalProfile;
        $profileHourly = $profile ? (float) $profile->hourly_rate : null;
        $profileBonus = $profile ? (float) $profile->food_allowance : null;

        $settings = $settingsService->getSettings($this->employeeId, $profileHourly, $profileBonus);
        $this->generalHourlyRate = $settings['hourly_rate'];
        $this->generalBonusAmount = $settings['bonus_amount'];

        $records = $attendanceService->fetchRecords($this->employeeId, $this->from, $this->to);
        $result = $attendanceService->processPayroll($records, $settings);

        $this->payrollRows = $result['resumen'];
        $this->totalsFooter = $result['totales_pie'];
        $this->searched = true;
    }

    public function saveGeneralRates(AttendanceSettingsService $settingsService): void
    {
        if (empty($this->employeeId)) {
            return;
        }

        $this->errorToastVersion++;
        $this->validate([
            'generalHourlyRate' => 'required|numeric|min:0',
            'generalBonusAmount' => 'required|numeric|min:0',
        ]);

        $settingsService->saveGeneral(
            $this->employeeId,
            (float) $this->generalHourlyRate,
            (float) $this->generalBonusAmount,
        );

        $user = User::query()->with('activeOrganizationalProfile')->find($this->userId);
        $user?->activeOrganizationalProfile?->update([
            'hourly_rate' => (float) $this->generalHourlyRate,
            'food_allowance' => (float) $this->generalBonusAmount,
        ]);

        session()->flash('message', 'Tarifas generales aplicadas. Los ajustes individuales previos fueron reemplazados.');
        $this->searchAttendance(app(AttendanceService::class), $settingsService);
    }

    public function editRow(string $fecha): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $row = collect($this->payrollRows)->firstWhere('fecha', $fecha);

        if (! $row) {
            return;
        }

        $this->selectedDate = $fecha;
        $this->selectedDateIsWeekend = Carbon::parse($fecha)->isWeekend();
        $this->modalHourlyRate = (float) ($row['hourly_rate'] ?? $this->generalHourlyRate);
        $this->modalBonusAmount = $this->selectedDateIsWeekend
            ? 0.0
            : (float) ($row['bono_raw'] ?? $this->generalBonusAmount);
        $this->modalMarks = array_values($row['marks'] ?? []);
        $this->originalModalMarks = $this->modalMarks;
        $this->modalChangeComment = '';
        $this->resetValidation();
        $this->recalculateModalAmounts();
        $this->showAttendanceModal = true;
    }

    public function addAttendanceMark(): void
    {
        $this->modalMarks[] = '';
        $this->recalculateModalAmounts();
    }

    public function removeAttendanceMark(int $index): void
    {
        if (! array_key_exists($index, $this->modalMarks)) {
            return;
        }

        unset($this->modalMarks[$index]);
        $this->modalMarks = array_values($this->modalMarks);
        $this->resetValidation('modalMarks.'.$index);
        $this->recalculateModalAmounts();
    }

    public function updatedModalMarks(): void
    {
        $this->recalculateModalAmounts();
    }

    public function updatedModalHourlyRate(): void
    {
        $this->recalculateModalAmounts();
    }

    public function updatedModalBonusAmount(): void
    {
        $this->recalculateModalAmounts();
    }

    public function closeModal(): void
    {
        $this->showAttendanceModal = false;
        $this->reset([
            'selectedDate', 'modalMarks', 'originalModalMarks', 'modalChangeComment',
            'selectedDateIsWeekend', 'modalCalculatedBasePay', 'modalCalculatedTotal',
        ]);
        $this->resetValidation();
    }

    public function saveDayAdjustment(AttendanceSettingsService $settingsService): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        if (empty($this->employeeId) || empty($this->selectedDate)) {
            return;
        }

        $this->modalChangeComment = trim($this->modalChangeComment);
        $this->errorToastVersion++;
        $this->validate([
            'modalHourlyRate' => 'required|numeric|min:0',
            'modalBonusAmount' => 'required|numeric|min:0',
            'modalMarks' => ['required', 'array', 'min:1'],
            'modalMarks.*' => ['required', 'date_format:H:i:s', 'distinct'],
            'modalChangeComment' => ['required', 'string', 'min:5', 'max:500'],
        ], [], [
            'modalHourlyRate' => 'pago por hora',
            'modalBonusAmount' => 'bono de comida',
            'modalMarks' => 'marcas o chequeos',
            'modalMarks.*' => 'marca o chequeo',
            'modalChangeComment' => 'comentario del cambio',
        ]);

        $marks = collect($this->modalMarks)->sort()->values()->all();
        $bonusAmount = Carbon::parse($this->selectedDate)->isWeekend()
            ? 0.0
            : (float) $this->modalBonusAmount;
        $settingsService->saveDayOverride(
            $this->employeeId,
            $this->selectedDate,
            (float) $this->modalHourlyRate,
            $bonusAmount,
            $this->modalChangeComment,
            auth()->id(),
            $this->originalModalMarks,
            $marks,
        );

        $this->closeModal();
        session()->flash('message', count($marks) % 2 === 0
            ? 'Jornada corregida y recalculada correctamente.'
            : 'Jornada modificada; aún requiere revisión porque conserva un número impar de marcas.');
        $this->searchAttendance(app(AttendanceService::class), $settingsService);
    }

    private function recalculateModalAmounts(): void
    {
        $marks = collect($this->modalMarks)
            ->filter(fn ($mark) => is_string($mark) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $mark))
            ->sort()
            ->values();

        if ($marks->isEmpty() || $marks->count() % 2 !== 0) {
            $this->modalCalculatedBasePay = 0.0;
            $this->modalCalculatedTotal = 0.0;

            return;
        }

        $seconds = 0;
        for ($index = 0; $index < $marks->count(); $index += 2) {
            try {
                $start = Carbon::createFromFormat('H:i:s', $marks[$index]);
                $end = Carbon::createFromFormat('H:i:s', $marks[$index + 1]);
            } catch (\Throwable) {
                $this->modalCalculatedBasePay = 0.0;
                $this->modalCalculatedTotal = 0.0;

                return;
            }
            if ($end->greaterThan($start)) {
                $seconds += $start->diffInSeconds($end);
            }
        }

        $decimalHours = round($seconds / 3600, 2);
        $this->modalCalculatedBasePay = round($decimalHours * max(0, (float) $this->modalHourlyRate), 2);
        $mealBonus = $this->selectedDateIsWeekend ? 0.0 : max(0, (float) $this->modalBonusAmount);
        $this->modalCalculatedTotal = round($this->modalCalculatedBasePay + $mealBonus, 2);
    }

    public function export(
        string $format,
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
        AttendanceExportService $exportService,
    ): StreamedResponse {
        abort_unless(! empty($this->employeeId) && $this->searched, 404);

        $user = User::find($this->userId);

        $profile = $user?->activeOrganizationalProfile;
        $settings = $settingsService->getSettings(
            $this->employeeId,
            $profile ? (float) $profile->hourly_rate : null,
            $profile ? (float) $profile->food_allowance : null,
        );

        $records = $attendanceService->fetchRecords($this->employeeId, $this->from, $this->to);
        $result = $attendanceService->processPayroll($records, $settings);

        $meta = [
            'Colaborador' => $this->selectedEmployeeName,
            'ID Checador' => $this->employeeId,
            'Periodo' => $this->from.' — '.$this->to,
            'Pago por hora general' => '$'.number_format($settings['hourly_rate'], 2),
            'Bono general (días correctos)' => '$'.number_format($settings['bonus_amount'], 2),
            'Total acumulado' => $result['total_general'],
        ];

        return $exportService->download($format, $result, $meta);
    }

    public function render(ReferenceDataCache $references)
    {
        $reportUsers = User::query()
            ->select('id', 'name', 'last_name', 'employee_id')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', '')
            ->with('activeOrganizationalProfile.physicalArea:id,name')
            ->orderBy('name')
            ->orderBy('last_name')
            ->get();

        $reportedUsers = $reportUsers
            ->whereIn('id', $this->reportedUserIds)
            ->values();
        $selectedAreaCount = $reportUsers
            ->whereIn('id', array_map('intval', $this->selectedReportUserIds))
            ->map(fn (User $user) => $user->activeOrganizationalProfile?->physicalArea?->name ?? 'Sin área asignada')
            ->unique()
            ->count();

        // La lista del biométrico no cambia al seleccionar colaboradores. Evita
        // agrupar toda control_de_horas en cada render y cárgala únicamente
        // cuando el editor realmente está abierto.
        $employeeIdSuggestions = collect();
        if ($this->showEmployeeIdModal && $this->employeeIdSuggestionsRequested) {
            $assignedEmployeeIds = $reportUsers
                ->reject(fn (User $user): bool => $user->id === $this->editingEmployeeUserId)
                ->pluck('employee_id')
                ->filter()
                ->map(fn ($employeeId): string => (string) $employeeId)
                ->flip();

            $employeeIdSuggestions = $references->employeeSuggestions()
                ->reject(fn ($suggestion): bool => $assignedEmployeeIds->has((string) $suggestion->employeeID))
                ->values();
        }

        return view('livewire.time-control.admin.attendance-management', [
            'reportUsers' => $reportUsers,
            'reportedUsers' => $reportedUsers,
            'selectedAreaCount' => $selectedAreaCount,
            'employeeIdSuggestions' => $employeeIdSuggestions,
        ])->layout('layouts.app');
    }
}
