<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\User;
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

    public bool $showEmployeeIdModal = false;

    public ?int $editingEmployeeUserId = null;

    public string $editingEmployeeName = '';

    public string $editingEmployeeId = '';

    // Tarifas generales editables por el admin
    public $generalHourlyRate = 20.00;

    public $generalBonusAmount = 50.00;

    public $employeeId = '';

    // Modal de ajuste por día
    public $showAttendanceModal = false;

    public $selectedDate = '';

    public $selectedEmployeeName = '';

    public $modalDailyPay = 0.00;

    public $modalBonusAmount = 50.00;

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

    public function openEmployeeIdModal(int $userId): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $user = User::query()->findOrFail($userId, ['id', 'name', 'last_name', 'employee_id']);

        $this->editingEmployeeUserId = $user->id;
        $this->editingEmployeeName = trim($user->name.' '.$user->last_name);
        $this->editingEmployeeId = (string) $user->employee_id;
        $this->resetErrorBag('editingEmployeeId');
        $this->showEmployeeIdModal = true;
    }

    public function closeEmployeeIdModal(): void
    {
        $this->showEmployeeIdModal = false;
        $this->reset(['editingEmployeeUserId', 'editingEmployeeName', 'editingEmployeeId']);
        $this->resetErrorBag('editingEmployeeId');
    }

    public function saveEmployeeId(
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): void {
        abort_unless(Gate::allows('view-time-admin'), 403);
        abort_unless($this->editingEmployeeUserId, 404);

        $this->editingEmployeeId = trim($this->editingEmployeeId);
        $this->validate([
            'editingEmployeeId' => [
                'required',
                'string',
                'max:50',
                Rule::exists('control_de_horas', 'employeeID'),
                Rule::unique('users', 'employee_id')->ignore($this->editingEmployeeUserId),
            ],
        ], [
            'editingEmployeeId.required' => 'Selecciona o escribe el ID del checador.',
            'editingEmployeeId.exists' => 'El ID indicado no existe en los registros del checador.',
            'editingEmployeeId.unique' => 'Este ID de checador ya está relacionado con otra persona.',
        ]);

        $user = User::query()->findOrFail($this->editingEmployeeUserId);
        $user->update(['employee_id' => $this->editingEmployeeId]);

        $editedUserId = $user->id;
        $this->closeEmployeeIdModal();

        if ($this->activeReportUserId === $editedUserId || $this->userId === $editedUserId) {
            $this->userId = $editedUserId;
            $this->searchAttendance($attendanceService, $settingsService);
        }

        session()->flash('message', 'El ID del checador se actualizó y quedó sincronizado correctamente.');
    }

    public function generateSelectionReport(
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): void
    {
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
            $this->addError('selectedReportUserIds', 'Selecciona exactamente un colaborador para descargar el informe individual.');
            abort(422);
        }
        if (in_array($mode, ['group', 'general'], true) && $users->count() < 2) {
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

        $this->validate([
            'generalHourlyRate' => 'required|numeric|min:0',
            'generalBonusAmount' => 'required|numeric|min:0',
        ]);

        $settingsService->saveGeneral(
            $this->employeeId,
            (float) $this->generalHourlyRate,
            (float) $this->generalBonusAmount,
        );

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
        $this->modalDailyPay = (float) ($row['pago_base_raw'] ?? 0);
        $this->modalBonusAmount = (float) ($row['bono_raw'] ?? $this->generalBonusAmount);
        $this->modalMarks = DB::table('control_de_horas')
            ->where('employeeID', $this->employeeId)
            ->where('authDate', $fecha)
            ->orderBy('authDateTime')
            ->pluck('authDateTime')
            ->map(fn ($dateTime) => Carbon::parse($dateTime)->format('H:i:s'))
            ->values()
            ->all();
        $this->originalModalMarks = $this->modalMarks;
        $this->modalChangeComment = '';
        $this->resetValidation();
        $this->showAttendanceModal = true;
    }

    public function addAttendanceMark(): void
    {
        $this->modalMarks[] = '';
    }

    public function removeAttendanceMark(int $index): void
    {
        if (! array_key_exists($index, $this->modalMarks)) {
            return;
        }

        unset($this->modalMarks[$index]);
        $this->modalMarks = array_values($this->modalMarks);
        $this->resetValidation('modalMarks.'.$index);
    }

    public function closeModal(): void
    {
        $this->showAttendanceModal = false;
        $this->reset(['selectedDate', 'modalMarks', 'originalModalMarks', 'modalChangeComment']);
        $this->resetValidation();
    }

    public function saveDayAdjustment(AttendanceSettingsService $settingsService): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        if (empty($this->employeeId) || empty($this->selectedDate)) {
            return;
        }

        $this->modalChangeComment = trim($this->modalChangeComment);
        $this->validate([
            'modalDailyPay' => 'required|numeric|min:0',
            'modalBonusAmount' => 'required|numeric|min:0',
            'modalMarks' => ['required', 'array', 'min:1'],
            'modalMarks.*' => ['required', 'date_format:H:i:s', 'distinct'],
            'modalChangeComment' => ['required', 'string', 'min:5', 'max:500'],
        ], [], [
            'modalDailyPay' => 'pago del día',
            'modalBonusAmount' => 'bono del día',
            'modalMarks' => 'marcas o chequeos',
            'modalMarks.*' => 'marca o chequeo',
            'modalChangeComment' => 'comentario del cambio',
        ]);

        $marks = collect($this->modalMarks)->sort()->values()->all();
        $existing = DB::table('control_de_horas')
            ->where('employeeID', $this->employeeId)
            ->where('authDate', $this->selectedDate)
            ->orderBy('authDateTime')
            ->get();
        $template = $existing->first();

        DB::transaction(function () use ($settingsService, $marks, $template): void {
            DB::table('control_de_horas')
                ->where('employeeID', $this->employeeId)
                ->where('authDate', $this->selectedDate)
                ->delete();

            foreach ($marks as $index => $time) {
                DB::table('control_de_horas')->insert([
                    'employeeID' => $this->employeeId,
                    'personName' => $template?->personName ?? $this->selectedEmployeeName,
                    'authDateTime' => $this->selectedDate.' '.$time,
                    'authDate' => $this->selectedDate,
                    'authTime' => $time,
                    'direction' => $index % 2 === 0 ? 'IN' : 'OUT',
                    'deviceName' => $template?->deviceName ?? 'Ajuste administrativo',
                ]);
            }

            $settingsService->saveDayOverride(
                $this->employeeId,
                $this->selectedDate,
                (float) $this->modalDailyPay,
                (float) $this->modalBonusAmount,
                $this->modalChangeComment,
                auth()->id(),
                $this->originalModalMarks,
                $marks,
            );
        });

        $this->closeModal();
        session()->flash('message', count($marks) % 2 === 0
            ? 'Jornada corregida y recalculada correctamente.'
            : 'Jornada modificada; aún requiere revisión porque conserva un número impar de marcas.');
        $this->searchAttendance(app(AttendanceService::class), $settingsService);
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

    public function render()
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

        $employeeIdSuggestions = DB::table('control_de_horas')
            ->select('employeeID')
            ->selectRaw('MAX(personName) as personName')
            ->whereNotNull('employeeID')
            ->where('employeeID', '<>', '')
            ->whereNotIn('employeeID', User::query()
                ->select('employee_id')
                ->whereNotNull('employee_id')
                ->where('employee_id', '<>', '')
                ->when($this->editingEmployeeUserId, fn ($query) => $query->where('id', '<>', $this->editingEmployeeUserId)))
            ->groupBy('employeeID')
            ->orderBy('employeeID')
            ->get();

        return view('livewire.time-control.admin.attendance-management', [
            'reportUsers' => $reportUsers,
            'reportedUsers' => $reportedUsers,
            'selectedAreaCount' => $selectedAreaCount,
            'employeeIdSuggestions' => $employeeIdSuggestions,
        ])->layout('layouts.app');
    }
}
