<?php

namespace App\Livewire\Administracion;

use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Services\Administracion\OrganizationChartService;
use Livewire\Component;

class IndexAdministracion extends Component
{
    public $totalUsers = 0;

    public $totalRoles = 0;

    public $totalPermissions = 0;

    public ?int $selectedPhysicalAreaId = null;

    public array $orgChartTree = [];

    public array $unassignedUsers = [];

    public array $orgChartStats = [];

    public function mount(OrganizationChartService $chartService): void
    {
        $this->totalUsers = User::count();
        $this->totalRoles = Role::count();
        $this->loadOrgChart($chartService);
    }

    public function updatedSelectedPhysicalAreaId(OrganizationChartService $chartService): void
    {
        if ($this->selectedPhysicalAreaId === '' || $this->selectedPhysicalAreaId === 0) {
            $this->selectedPhysicalAreaId = null;
        }

        $this->loadOrgChart($chartService);
    }

    private function loadOrgChart(OrganizationChartService $chartService): void
    {
        $data = $chartService->buildChartData($this->selectedPhysicalAreaId);

        $this->orgChartTree = $data['tree'];
        $this->unassignedUsers = $data['unassigned'];
        $this->orgChartStats = $data['stats'];
    }

    public function goToSecction($section)
    {
        return redirect()->to('/administracion/'.$section);
    }

    public function render()
    {
        return view('livewire.administracion.index-administracion', [
            'physicalAreas' => PhysicalArea::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
