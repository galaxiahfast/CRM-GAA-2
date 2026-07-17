<?php

namespace App\Livewire\Administracion;

use App\Models\PhysicalArea;
use App\Models\JobPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInterns;
use App\Services\Administracion\OrganizationChartService;
use Illuminate\Support\Facades\DB;
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

    public ?array $selectedUserDetails = null;

    public ?int $selectedUserId = null;

    public bool $isEditingUser = false;

    public array $userForm = [];

    public string $deleteConfirmationName = '';

    public function mount(OrganizationChartService $chartService): void
    {
        $this->totalUsers = User::count();
        $this->totalRoles = Role::count();
        if ($this->canManageOrganization()) {
            $this->loadOrgChart($chartService);
        }
    }

    public function updatedSelectedPhysicalAreaId(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();
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
        $routes = [
            'users' => 'administracion.section',
            'roles' => 'administracion.role',
            'permissions' => 'administracion.permissions',
            'interns' => 'administracion.interns',
            'relationships' => 'administracion.relationships',
        ];

        abort_unless(isset($routes[$section]), 404);

        return redirect()->route($routes[$section]);
    }

    public function selectUser(int $userId): void
    {
        $this->ensureOrganizationAdministrator();

        $user = User::with([
            'role:id,role',
            'activeOrganizationalProfile.jobPosition:id,name',
            'activeOrganizationalProfile.physicalArea:id,name',
            'superiors:id,name,last_name,email',
            'subordinates:id,name,last_name,email',
        ])->findOrFail($userId);

        $profile = $user->activeOrganizationalProfile;
        $role = $user->role?->role;

        $this->deleteConfirmationName = '';
        $this->selectedUserId = $user->id;
        $this->isEditingUser = false;
        $this->userForm = [
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'employee_id' => $user->employee_id,
            'job_position_id' => $profile?->job_position_id,
            'physical_area_id' => $profile?->physical_area_id,
            'hourly_rate' => $profile?->hourly_rate,
            'food_allowance' => $profile?->food_allowance,
            'password' => '',
            'password_confirmation' => '',
            'is_auxiliar' => mb_strtolower((string) $role) === 'auxiliar',
        ];
        $this->selectedUserDetails = [
            'id' => $user->id,
            'name' => trim("{$user->name} {$user->last_name}"),
            'email' => $user->email,
            'role' => $role,
            'employee_id' => $user->employee_id,
            'created_at' => optional($user->created_at)->format('d/m/Y H:i'),
            'updated_at' => optional($user->updated_at)->format('d/m/Y H:i'),
            'job_position' => $profile?->jobPosition?->name,
            'physical_area' => $profile?->physicalArea?->name,
            'is_auxiliar' => mb_strtolower((string) $role) === 'auxiliar',
            'hourly_rate' => $profile?->hourly_rate,
            'food_allowance' => $profile?->food_allowance,
            'superiors' => $user->superiors->map(fn (User $person) => trim("{$person->name} {$person->last_name}"))->values()->all(),
            'subordinates' => $user->subordinates->map(fn (User $person) => trim("{$person->name} {$person->last_name}"))->values()->all(),
        ];
    }

    /** Compatibilidad con los nodos ya renderizados en caché. */
    public function showUserDetails(int $userId): void
    {
        $this->selectUser($userId);
    }

    public function beginEditingUser(): void
    {
        abort_unless($this->selectedUserId, 404);
        $this->ensureOrganizationAdministrator();
        $this->isEditingUser = true;
    }

    public function cancelEditingUser(): void
    {
        $this->isEditingUser = false;
    }

    public function updatedUserFormRoleId($roleId): void
    {
        $this->userForm['is_auxiliar'] = Role::whereKey($roleId)->where('role', 'Auxiliar')->exists();
    }

    public function saveSelectedUser(): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless($this->selectedUserId, 404);

        $user = User::findOrFail($this->selectedUserId);
        $rules = [
            'userForm.name' => ['required', 'string', 'max:255'],
            'userForm.last_name' => ['nullable', 'string', 'max:255'],
            'userForm.email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'userForm.role_id' => ['required', 'exists:roles,id'],
            'userForm.employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id,'.$user->id],
            'userForm.job_position_id' => ['required', 'exists:job_positions,id'],
            'userForm.physical_area_id' => ['required', 'exists:physical_areas,id'],
            'userForm.hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'userForm.food_allowance' => ['nullable', 'numeric', 'min:0'],
            'userForm.password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if (($this->userForm['is_auxiliar'] ?? false) === true) {
            $rules['userForm.hourly_rate'] = ['required', 'numeric', 'min:0'];
            $rules['userForm.food_allowance'] = ['required', 'numeric', 'min:0'];
        }

        $data = $this->validate($rules)['userForm'];

        DB::transaction(function () use ($user, $data) {
            $user->update(array_filter([
                'name' => $data['name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'role_id' => $data['role_id'],
                'employee_id' => $data['employee_id'] ?? null,
                'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            ], fn ($value, $key) => $key !== 'password' || $value !== null, ARRAY_FILTER_USE_BOTH));

            $user->activeOrganizationalProfile()->updateOrCreate(
                ['user_id' => $user->id, 'is_active' => true],
                [
                    'job_position_id' => $data['job_position_id'],
                    'physical_area_id' => $data['physical_area_id'],
                    'hourly_rate' => $data['hourly_rate'] ?? null,
                    'food_allowance' => $data['food_allowance'] ?? null,
                    'valid_from' => now()->toDateString(),
                ]
            );
        });

        $this->selectUser($user->id);
        $this->isEditingUser = false;
        session()->flash('success', 'Información del usuario actualizada.');
    }

    public function closeUserDetails(): void
    {
        $this->selectedUserDetails = null;
        $this->selectedUserId = null;
        $this->userForm = [];
        $this->isEditingUser = false;
        $this->deleteConfirmationName = '';
    }

    public function deleteSelectedUser(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless($this->selectedUserDetails, 404);

        $user = User::findOrFail($this->selectedUserDetails['id']);
        $fullName = trim("{$user->name} {$user->last_name}");

        $this->validate([
            'deleteConfirmationName' => ['required', function ($attribute, $value, $fail) use ($fullName) {
                if (mb_strtolower(trim(preg_replace('/\\s+/', ' ', $value))) !== mb_strtolower($fullName)) {
                    $fail('Debes escribir el nombre completo exacto del usuario para eliminarlo.');
                }
            }],
        ]);

        abort_if($user->id === auth()->id(), 422, 'No puedes eliminar tu propio usuario.');

        DB::transaction(function () use ($user, $chartService) {
            // Al borrar las relaciones donde era jefe, sus subordinados quedan sin jefe
            // y el servicio los presentará automáticamente como "sin asignar".
            $chartService->detachAllRelationsForUser($user->id);
            UserInterns::where('intern_id', $user->id)->delete();
            $user->delete();
        });

        $this->closeUserDetails();
        $this->loadOrgChart($chartService);
        session()->flash('success', 'Usuario eliminado. Sus subordinados directos quedaron sin jefe asignado.');
    }

    private function canManageOrganization(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    private function ensureOrganizationAdministrator(): void
    {
        abort_unless($this->canManageOrganization(), 403);
    }

    public function render()
    {
        return view('livewire.administracion.index-administracion', [
            'physicalAreas' => PhysicalArea::orderBy('name')->get(['id', 'name']),
            'jobPositions' => JobPosition::orderBy('name')->get(['id', 'name']),
            'roles' => Role::orderBy('role')->get(['id', 'role']),
        ])->layout('layouts.app');
    }
}
