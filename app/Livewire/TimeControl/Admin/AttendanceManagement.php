<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\User;
use App\Services\TimeControl\AttendanceExportService;
use App\Services\TimeControl\AttendanceService;
use App\Services\TimeControl\AttendanceSettingsService;
use Carbon\Carbon;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceManagement extends Component
{
    public $searchCollaborator = '';

    public $userId = null;

    public $from;

    public $to;

    public $searched = false;

    // Tarifas generales editables por el admin
    public $generalHourlyRate = 20.00;

    public $generalBonusAmount = 50.00;

    public $employeeId = '';

    // Modal de ajuste por día
    public $showAttendanceModal = false;

    public $selectedDate = '';

    public $selectedEmployeeName = '';

    public $modalHourlyRate = 20.00;

    public $modalBonusAmount = 50.00;

    /** @var array<int, array<string, mixed>> */
    public $payrollRows = [];

    /** @var array<string, string> */
    public $totalsFooter = [];

    public function mount(): void
    {
        $this->from = Carbon::now()->subDays(7)->toDateString();
        $this->to = Carbon::now()->toDateString();
    }

    public function clearCollaborator(): void
    {
        $this->userId = null;
        $this->searchCollaborator = '';
        $this->employeeId = '';
        $this->searched = false;
        $this->payrollRows = [];
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
        $row = collect($this->payrollRows)->firstWhere('fecha', $fecha);

        if (! $row) {
            return;
        }

        $this->selectedDate = $fecha;
        $this->modalHourlyRate = (float) ($row['hourly_rate'] ?? $this->generalHourlyRate);
        $this->modalBonusAmount = (float) ($row['bono_raw'] ?? $this->generalBonusAmount);
        $this->showAttendanceModal = true;
    }

    public function closeModal(): void
    {
        $this->showAttendanceModal = false;
    }

    public function saveDayAdjustment(AttendanceSettingsService $settingsService): void
    {
        if (empty($this->employeeId) || empty($this->selectedDate)) {
            return;
        }

        $this->validate([
            'modalHourlyRate' => 'required|numeric|min:0',
            'modalBonusAmount' => 'required|numeric|min:0',
        ]);

        $settingsService->saveDayOverride(
            $this->employeeId,
            $this->selectedDate,
            (float) $this->modalHourlyRate,
            (float) $this->modalBonusAmount,
        );

        $this->showAttendanceModal = false;
        session()->flash('message', 'Ajuste individual del día guardado.');
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
        $users = User::select('id', 'name', 'last_name', 'employee_id')->get();

        return view('livewire.time-control.admin.attendance-management', [
            'users' => $users,
        ])->layout('layouts.app');
    }
}
