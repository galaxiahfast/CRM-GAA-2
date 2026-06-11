<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\User;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\TimeReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTimeDashboard extends Component
{
    public string $from;

    public string $to;

    public ?int $userId = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);
        // El rango predeterminado es el día actual completo.
        $this->from = now()->toDateString();
        $this->to = now()->toDateString();
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

        return view('livewire.time-control.admin.dashboard', [
            'total' => $data['total'],
            'byCollaborator' => $data['byCollaborator'],
            'byCustomer' => $data['byCustomer'],
            'byPosition' => $data['byPosition'],
            'byArea' => $data['byArea'],
            'autoClosedCount' => $data['autoClosedCount'],
            'users' => User::whereDoesntHave('role', fn ($q) => $q->where('role', 'Administrador'))
                ->orderBy('name')
                ->get(['id', 'name', 'last_name']),
            'exportFormats' => $exporter->formats(),
        ])->layout('layouts.app');
    }
}
