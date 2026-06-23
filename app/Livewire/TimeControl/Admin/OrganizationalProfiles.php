<?php

namespace App\Livewire\TimeControl\Admin;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class OrganizationalProfiles extends Component
{
    public ?int $userId = null;

    public ?int $jobPositionId = null;

    public ?int $physicalAreaId = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);
    }

    /**
     * Asigna un puesto/área al usuario usando SCD Tipo 2 (sección 5):
     * cierra el perfil activo anterior y crea uno nuevo, preservando historia.
     */
    public function assign(): void
    {
        abort_unless(Gate::allows('view-time-admin'), 403);

        $this->validate([
            'userId' => ['required', 'exists:users,id'],
            'jobPositionId' => ['required', 'exists:job_positions,id'],
            'physicalAreaId' => ['required', 'exists:physical_areas,id'],
        ], [], [
            'userId' => 'usuario',
            'jobPositionId' => 'puesto',
            'physicalAreaId' => 'área',
        ]);

        DB::transaction(function () {
            UserOrganizationalProfile::where('user_id', $this->userId)
                ->where('is_active', true)
                ->update(['is_active' => false, 'valid_to' => Carbon::now()->toDateString()]);

            UserOrganizationalProfile::create([
                'user_id' => $this->userId,
                'job_position_id' => $this->jobPositionId,
                'physical_area_id' => $this->physicalAreaId,
                'valid_from' => Carbon::now()->toDateString(),
                'valid_to' => null,
                'is_active' => true,
            ]);
        });

        $this->reset(['userId', 'jobPositionId', 'physicalAreaId']);
        session()->flash('success', 'Perfil organizacional asignado correctamente.');
    }

    public function render()
    {
        $users = User::with(['role', 'activeOrganizationalProfile.jobPosition', 'activeOrganizationalProfile.physicalArea'])
            ->orderBy('name')
            ->get();

        return view('livewire.time-control.admin.profiles', [
            'users' => $users,
            'jobPositions' => JobPosition::orderBy('name')->get(),
            'physicalAreas' => PhysicalArea::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
