<?php

namespace App\Livewire\Administracion;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserInterns;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    public string $activeTab = 'datos';

    public bool $showPermissionsModal = false;

    public bool $showJobPositionModal = false;

    public bool $showPhysicalAreaModal = false;

    public string $newJobPositionName = '';

    public string $newPhysicalAreaName = '';

    public string $jobPositionModalTab = 'crear';

    public ?int $selectedJobPositionId = null;

    public string $editJobPositionName = '';

    public string $physicalAreaModalTab = 'crear';

    public ?int $selectedPhysicalAreaManagementId = null;

    public string $editPhysicalAreaName = '';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function mount(OrganizationChartService $chartService): void
    {
        $this->totalUsers = User::count();
        $this->totalRoles = Role::count();
        $this->showPermissionsModal = request()->routeIs('administracion.permissions');

        if ($this->canManageOrganization()) {
            $this->loadOrgChart($chartService);
        }
    }

    public function openPermissionsModal(): void
    {
        $this->ensureOrganizationAdministrator();
        $this->showPermissionsModal = true;
    }

    public function closePermissionsModal(): void
    {
        $this->showPermissionsModal = false;
    }

    public function openJobPositionModal(): void
    {
        $this->ensureOrganizationAdministrator();
        $this->showPhysicalAreaModal = false;
        $this->showPermissionsModal = false;
        $this->newJobPositionName = '';
        $this->jobPositionModalTab = 'crear';
        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->resetValidation('newJobPositionName');
        $this->showJobPositionModal = true;
    }

    public function closeJobPositionModal(): void
    {
        $this->showJobPositionModal = false;
        $this->newJobPositionName = '';
        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->resetValidation(['newJobPositionName', 'selectedJobPositionId', 'editJobPositionName']);
    }

    public function saveJobPosition(): void
    {
        $this->ensureOrganizationAdministrator();
        $this->newJobPositionName = $this->normalizeCatalogName($this->newJobPositionName);

        $data = $this->validate([
            'newJobPositionName' => ['required', 'string', 'max:255', Rule::unique('job_positions', 'name')],
        ], [
            'newJobPositionName.required' => 'El nombre del puesto es obligatorio.',
            'newJobPositionName.max' => 'El nombre del puesto no debe exceder los 255 caracteres.',
            'newJobPositionName.unique' => 'Este puesto de trabajo ya existe.',
        ]);

        DB::transaction(function () use ($data): void {
            JobPosition::create(['name' => $data['newJobPositionName']]);
        });

        $this->closeJobPositionModal();
        session()->flash('success', 'Puesto de trabajo agregado correctamente.');
    }

    public function setJobPositionModalTab(string $tab): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        $this->jobPositionModalTab = $tab;
        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->resetValidation(['selectedJobPositionId', 'editJobPositionName']);
    }

    public function updatedSelectedJobPositionId($positionId): void
    {
        $this->ensureOrganizationAdministrator();

        $this->selectedJobPositionId = filled($positionId) ? (int) $positionId : null;
        $this->editJobPositionName = $this->selectedJobPositionId
            ? (string) JobPosition::findOrFail($this->selectedJobPositionId)->name
            : '';
        $this->resetValidation(['selectedJobPositionId', 'editJobPositionName']);
    }

    public function updateJobPosition(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();
        $this->editJobPositionName = $this->normalizeCatalogName($this->editJobPositionName);

        $data = $this->validate([
            'selectedJobPositionId' => ['required', 'integer', 'exists:job_positions,id'],
            'editJobPositionName' => ['required', 'string', 'max:255', Rule::unique('job_positions', 'name')->ignore($this->selectedJobPositionId)],
        ]);

        JobPosition::findOrFail($data['selectedJobPositionId'])->update(['name' => $data['editJobPositionName']]);

        $this->loadOrgChart($chartService);
        session()->flash('success', 'Puesto de trabajo actualizado correctamente.');
    }

    public function deleteJobPosition(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();

        $data = $this->validate([
            'selectedJobPositionId' => ['required', 'integer', 'exists:job_positions,id'],
        ]);

        $positionId = $data['selectedJobPositionId'];

        if (DB::table('time_entries')->where('job_position_id_snapshot', $positionId)->exists()) {
            $this->addError('selectedJobPositionId', 'No se puede eliminar este puesto porque forma parte del historial de horas.');

            return;
        }

        DB::transaction(function () use ($positionId): void {
            UserOrganizationalProfile::where('job_position_id', $positionId)->update(['job_position_id' => null]);
            UserHierarchyRelation::where('job_position_id', $positionId)->update(['job_position_id' => null]);
            JobPosition::findOrFail($positionId)->delete();
        });

        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->loadOrgChart($chartService);
        session()->flash('success', 'Puesto eliminado. Los usuarios relacionados quedaron sin puesto asignado.');
    }

    public function openPhysicalAreaModal(): void
    {
        $this->ensureOrganizationAdministrator();
        $this->showJobPositionModal = false;
        $this->showPermissionsModal = false;
        $this->newPhysicalAreaName = '';
        $this->physicalAreaModalTab = 'crear';
        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->resetValidation('newPhysicalAreaName');
        $this->showPhysicalAreaModal = true;
    }

    public function closePhysicalAreaModal(): void
    {
        $this->showPhysicalAreaModal = false;
        $this->newPhysicalAreaName = '';
        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->resetValidation(['newPhysicalAreaName', 'selectedPhysicalAreaManagementId', 'editPhysicalAreaName']);
    }

    public function savePhysicalArea(): void
    {
        $this->ensureOrganizationAdministrator();
        $this->newPhysicalAreaName = $this->normalizeCatalogName($this->newPhysicalAreaName);

        $data = $this->validate([
            'newPhysicalAreaName' => ['required', 'string', 'max:255', Rule::unique('physical_areas', 'name')],
        ], [
            'newPhysicalAreaName.required' => 'El nombre del área o departamento es obligatorio.',
            'newPhysicalAreaName.max' => 'El nombre del área no debe exceder los 255 caracteres.',
            'newPhysicalAreaName.unique' => 'Esta área o departamento ya existe.',
        ]);

        DB::transaction(function () use ($data): void {
            PhysicalArea::create(['name' => $data['newPhysicalAreaName']]);
        });

        $this->closePhysicalAreaModal();
        session()->flash('success', 'Área o departamento agregado correctamente.');
    }

    public function setPhysicalAreaModalTab(string $tab): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        $this->physicalAreaModalTab = $tab;
        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->resetValidation(['selectedPhysicalAreaManagementId', 'editPhysicalAreaName']);
    }

    public function updatedSelectedPhysicalAreaManagementId($areaId): void
    {
        $this->ensureOrganizationAdministrator();

        $this->selectedPhysicalAreaManagementId = filled($areaId) ? (int) $areaId : null;
        $this->editPhysicalAreaName = $this->selectedPhysicalAreaManagementId
            ? (string) PhysicalArea::findOrFail($this->selectedPhysicalAreaManagementId)->name
            : '';
        $this->resetValidation(['selectedPhysicalAreaManagementId', 'editPhysicalAreaName']);
    }

    public function updatePhysicalArea(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();
        $this->editPhysicalAreaName = $this->normalizeCatalogName($this->editPhysicalAreaName);

        $data = $this->validate([
            'selectedPhysicalAreaManagementId' => ['required', 'integer', 'exists:physical_areas,id'],
            'editPhysicalAreaName' => ['required', 'string', 'max:255', Rule::unique('physical_areas', 'name')->ignore($this->selectedPhysicalAreaManagementId)],
        ]);

        PhysicalArea::findOrFail($data['selectedPhysicalAreaManagementId'])->update(['name' => $data['editPhysicalAreaName']]);

        $this->loadOrgChart($chartService);
        session()->flash('success', 'Área o departamento actualizado correctamente.');
    }

    public function deletePhysicalArea(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();

        $data = $this->validate([
            'selectedPhysicalAreaManagementId' => ['required', 'integer', 'exists:physical_areas,id'],
        ]);

        $areaId = $data['selectedPhysicalAreaManagementId'];

        if (DB::table('time_entries')->where('physical_area_id_snapshot', $areaId)->exists()) {
            $this->addError('selectedPhysicalAreaManagementId', 'No se puede eliminar esta área porque forma parte del historial de horas.');

            return;
        }

        DB::transaction(function () use ($areaId): void {
            UserOrganizationalProfile::where('physical_area_id', $areaId)->update(['physical_area_id' => null]);
            UserHierarchyRelation::where('physical_area_id', $areaId)->update(['physical_area_id' => null]);
            PhysicalArea::findOrFail($areaId)->delete();
        });

        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->loadOrgChart($chartService);
        session()->flash('success', 'Área eliminada. Los usuarios relacionados quedaron sin área asignada.');
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
            'superior_ids' => $user->superiors->pluck('id')->all(),
            'subordinate_ids' => $user->subordinates->pluck('id')->all(),
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

    /**
     * Mantiene las dos listas jerárquicas como conjuntos excluyentes durante
     * la edición, antes de que se persista cualquier relación.
     *
     * @param  array<int, int|string>|int|string|null  $superiorIds
     */
    public function updatedUserFormSuperiorIds($superiorIds): void
    {
        $normalizedSuperiorIds = $this->normalizeHierarchyUserIds($superiorIds);
        $excludedSubordinateIds = $this->superiorLineageIds($normalizedSuperiorIds);

        $this->userForm['superior_ids'] = $normalizedSuperiorIds;
        $this->userForm['subordinate_ids'] = array_values(array_diff(
            $this->normalizeHierarchyUserIds($this->userForm['subordinate_ids'] ?? []),
            $excludedSubordinateIds
        ));
    }

    /**
     * Mantiene las dos listas jerárquicas como conjuntos excluyentes durante
     * la edición, antes de que se persista cualquier relación.
     *
     * @param  array<int, int|string>|int|string|null  $subordinateIds
     */
    public function updatedUserFormSubordinateIds($subordinateIds): void
    {
        $excludedSubordinateIds = $this->superiorLineageIds(
            $this->normalizeHierarchyUserIds($this->userForm['superior_ids'] ?? [])
        );
        $normalizedSubordinateIds = array_values(array_diff(
            $this->normalizeHierarchyUserIds($subordinateIds),
            $excludedSubordinateIds
        ));

        $this->userForm['subordinate_ids'] = $normalizedSubordinateIds;
        $this->userForm['superior_ids'] = array_values(array_diff(
            $this->normalizeHierarchyUserIds($this->userForm['superior_ids'] ?? []),
            $normalizedSubordinateIds
        ));
    }

    public function saveSelectedUser(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless($this->selectedUserId, 404);

        $user = User::findOrFail($this->selectedUserId);
        $selectedSuperiorIds = $this->normalizeHierarchyUserIds($this->userForm['superior_ids'] ?? []);
        $excludedSubordinateIds = $this->superiorLineageIds($selectedSuperiorIds);
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
            'userForm.is_auxiliar' => ['boolean'],
            'userForm.password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'userForm.superior_ids' => ['nullable', 'array'],
            'userForm.superior_ids.*' => [
                'integer',
                'exists:users,id',
                'distinct',
                'different:'.$user->id,
                Rule::in($this->hierarchyCandidateIds()),
            ],
            'userForm.subordinate_ids' => ['nullable', 'array'],
            'userForm.subordinate_ids.*' => [
                'integer',
                'exists:users,id',
                'distinct',
                'different:'.$user->id,
                Rule::notIn($excludedSubordinateIds),
            ],
        ];

        if (($this->userForm['is_auxiliar'] ?? false) === true) {
            $rules['userForm.hourly_rate'] = ['required', 'numeric', 'min:0'];
            $rules['userForm.food_allowance'] = ['required', 'numeric', 'min:0'];
        }

        $data = $this->validate($rules)['userForm'];

        $isAuxiliar = ($data['is_auxiliar'] ?? false) === true;

        DB::transaction(function () use ($user, $data, $isAuxiliar, $chartService) {
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
                    // SQL Server tiene estas columnas NOT NULL en instalaciones existentes.
                    // Los valores monetarios solo aplican a Auxiliar; los demás usan cero.
                    'hourly_rate' => $isAuxiliar ? $data['hourly_rate'] : 0,
                    'food_allowance' => $isAuxiliar ? $data['food_allowance'] : 0,
                    'valid_from' => now()->toDateString(),
                ]
            );

            $this->syncHierarchyRelations(
                $user,
                $data['superior_ids'] ?? [],
                $data['subordinate_ids'] ?? [],
                $chartService
            );
        });

        $this->loadOrgChart($chartService);
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

    private function normalizeCatalogName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    public function render()
    {
        $selectedSuperiorIds = $this->normalizeHierarchyUserIds($this->userForm['superior_ids'] ?? []);
        $selectedSubordinateIds = $this->normalizeHierarchyUserIds($this->userForm['subordinate_ids'] ?? []);
        $excludedSubordinateIds = $this->superiorLineageIds($selectedSuperiorIds);

        return view('livewire.administracion.index-administracion', [
            'physicalAreas' => PhysicalArea::orderBy('name')->get(['id', 'name']),
            'jobPositions' => JobPosition::orderBy('name')->get(['id', 'name']),
            'roles' => Role::orderBy('role')->get(['id', 'role']),
            'employeeIdSuggestions' => DB::table('control_de_horas')
                ->select('employeeID')
                ->selectRaw('MAX(personName) as personName')
                ->whereNotNull('employeeID')
                ->where('employeeID', '<>', '')
                ->groupBy('employeeID')
                ->orderBy('employeeID')
                ->get(),
            // Un jefe debe pertenecer ya al organigrama (tener alguna relación).
            'superiorCandidates' => User::query()
                ->whereKeyNot($this->selectedUserId ?: 0)
                ->whereIn('id', $this->hierarchyCandidateIds())
                ->when($selectedSubordinateIds !== [], fn ($query) => $query->whereNotIn('id', $selectedSubordinateIds))
                ->orderBy('name')
                ->get(['id', 'name', 'last_name', 'email']),
            // Un subordinado puede provenir de la lista general, excepto los
            // jefes seleccionados y todos los superiores de su línea de mando.
            'subordinateCandidates' => User::query()
                ->whereKeyNot($this->selectedUserId ?: 0)
                ->when($excludedSubordinateIds !== [], fn ($query) => $query->whereNotIn('id', $excludedSubordinateIds))
                ->orderBy('name')
                ->get(['id', 'name', 'last_name', 'email']),
        ])->layout('layouts.app');
    }

    /**
     * @param  array<int, int|string>|int|string|null  $userIds
     * @return array<int, int>
     */
    private function normalizeHierarchyUserIds($userIds): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', (array) $userIds),
            fn (int $userId) => $userId > 0 && $userId !== $this->selectedUserId
        )));
    }

    /**
     * @return array<int, int>
     */
    private function hierarchyCandidateIds(): array
    {
        return UserHierarchyRelation::query()
            ->pluck('superior_id')
            ->merge(UserHierarchyRelation::query()->pluck('subordinate_id'))
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Incluye los jefes directos seleccionados y toda su línea de mando.
     * Las relaciones están orientadas como subordinado -> superior.
     *
     * @param  array<int, int>  $superiorIds
     * @return array<int, int>
     */
    private function superiorLineageIds(array $superiorIds): array
    {
        if ($superiorIds === []) {
            return [];
        }

        $superiorsBySubordinate = [];

        foreach (UserHierarchyRelation::query()->get(['subordinate_id', 'superior_id']) as $relation) {
            $superiorsBySubordinate[(int) $relation->subordinate_id][] = (int) $relation->superior_id;
        }

        $lineage = [];
        $pendingIds = $superiorIds;

        while ($pendingIds !== []) {
            $currentUserId = array_pop($pendingIds);

            if (isset($lineage[$currentUserId])) {
                continue;
            }

            $lineage[$currentUserId] = true;

            foreach ($superiorsBySubordinate[$currentUserId] ?? [] as $superiorId) {
                if (! isset($lineage[$superiorId])) {
                    $pendingIds[] = $superiorId;
                }
            }
        }

        return array_map('intval', array_keys($lineage));
    }

    /**
     * Reemplaza únicamente las relaciones directas del usuario editado.
     * OrganizationChartService conserva la validación contra ciclos.
     *
     * @param  array<int, int|string>  $superiorIds
     * @param  array<int, int|string>  $subordinateIds
     */
    private function syncHierarchyRelations(
        User $user,
        array $superiorIds,
        array $subordinateIds,
        OrganizationChartService $chartService
    ): void {
        $superiorIds = array_values(array_unique(array_map('intval', $superiorIds)));
        $subordinateIds = array_values(array_unique(array_map('intval', $subordinateIds)));

        if (array_intersect($superiorIds, $subordinateIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'userForm.subordinate_ids' => 'Una persona no puede ser jefe y subordinado directo al mismo tiempo.',
            ]);
        }

        UserHierarchyRelation::query()
            ->where('subordinate_id', $user->id)
            ->orWhere('superior_id', $user->id)
            ->delete();

        foreach ($superiorIds as $superiorId) {
            $chartService->createRelation([
                'subordinate_id' => $user->id,
                'superior_id' => $superiorId,
                'job_position_id' => null,
                'physical_area_id' => null,
            ]);
        }

        foreach ($subordinateIds as $subordinateId) {
            $chartService->createRelation([
                'subordinate_id' => $subordinateId,
                'superior_id' => $user->id,
                'job_position_id' => null,
                'physical_area_id' => null,
            ]);
        }
    }
}
