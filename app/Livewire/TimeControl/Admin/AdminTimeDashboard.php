<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\User;
use App\Models\TimeEntry;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\TimeReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTimeDashboard extends Component
{
    public string $from;

    public string $to;

    public ?int $userId = null;

    public string $searchCollaborator = '';

    // 🆕 Propiedades añadidas para el control de Modificaciones y Bonos
    public bool $showModal = false;
    public ?int $selectedUserId = null;
    public string $selectedUserName = '';
    public string $entryDate;
    public $totalHours = 0;
    public $dailyBonus = 0;
    public string $bonusReason = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);
        // Rango predeterminado de fechas
        $this->from = $this->localToday();
        $this->to = $this->localToday();
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
            session()->flash('error', 'Error al procesar el ajuste: ' . $e->getMessage());
        }
    }

    public function export(string $format, TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $user = $this->userId ? User::find($this->userId) : null;
        $report = $reports->adminReport($user, $this->from, $this->to);

        return $exporter->download($format, $report);
    }

    public function render(TimeReportService $reports, ReportExportManager $exporter)
    {
        $data = $reports->adminSupervision($this->userId, $this->from, $this->to);
        $activityDetail = $reports->activityDetailByDay($data['entries'], $this->userId === null);

        return view('livewire.time-control.admin.dashboard', [
            'total' => $data['total'],
            'byCollaborator' => $data['byCollaborator'],
            'byCustomer' => $data['byCustomer'],
            'byPosition' => $data['byPosition'],
            'byArea' => $data['byArea'],
            'autoClosedCount' => $data['autoClosedCount'],
            'activityDetail' => $activityDetail,
            'users' => User::whereDoesntHave('role', fn ($q) => $q->where('role', 'Administrador'))
                ->orderBy('name')
                ->get(['id', 'name', 'last_name']),
            'exportFormats' => $exporter->formats(),
        ])->layout('layouts.app');
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
}
