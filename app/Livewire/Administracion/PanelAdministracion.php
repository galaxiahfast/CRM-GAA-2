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
use App\Services\ReferenceDataCache;
use App\Services\TimeControl\AttendanceSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class PanelAdministracion extends Component
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

    public bool $showUserManagementModal = false;

    public string $userManagementInitialTab = 'crear';

    public ?int $userManagementInitialUserId = null;

    public bool $showRoleManagementModal = false;

    public string $roleManagementInitialTab = 'crear';

    public ?int $roleManagementInitialRoleId = null;

    public bool $showAssignmentModal = false;

    public string $assignmentModalTab = 'crear';

    public bool $showPermissionsModal = false;

    public bool $showJobPositionModal = false;

    public bool $showPhysicalAreaModal = false;

    public string $newJobPositionName = '';

    public string $newJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;

    public string $newPhysicalAreaName = '';

    public string $jobPositionModalTab = 'crear';

    public ?int $selectedJobPositionId = null;

    public string $editJobPositionName = '';

    public string $editJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;

    public string $deleteJobPositionConfirmation = '';

    public string $physicalAreaModalTab = 'crear';

    public ?int $selectedPhysicalAreaManagementId = null;

    public string $editPhysicalAreaName = '';

    public string $deletePhysicalAreaConfirmation = '';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openUserManagement(string $tab, ?int $userId = null): void
    {
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);
        abort_unless(app(\App\Services\Authorization\PermissionAccessService::class)
            ->allows(auth()->user(), 'administration.users.manage'), 403);
        if ($userId !== null) {
            abort_unless($tab !== 'crear' && User::whereKey($userId)->exists(), 404);
        }

        $this->userManagementInitialTab = $tab;
        $this->userManagementInitialUserId = $userId;
        $this->showUserManagementModal = true;
    }

    #[On('user-management-closed')]
    public function closeUserManagement(): void
    {
        $this->showUserManagementModal = false;
        $this->userManagementInitialUserId = null;
    }

    public function openRoleManagement(string $tab, ?int $roleId = null): void
    {
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);
        abort_unless(app(\App\Services\Authorization\PermissionAccessService::class)
            ->allows(auth()->user(), 'administration.roles.manage'), 403);
        if ($roleId !== null) {
            abort_unless($tab !== 'crear' && Role::whereKey($roleId)->exists(), 404);
        }

        $this->roleManagementInitialTab = $tab;
        $this->roleManagementInitialRoleId = $roleId;
        $this->showPermissionsModal = false;
        $this->showRoleManagementModal = true;
    }

    public function openPermissionRoleEditor(int $roleId): void
    {
        abort_unless(app(\App\Services\Authorization\PermissionAccessService::class)
            ->allows(auth()->user(), 'administration.roles.manage'), 403);
        abort_unless(Role::whereKey($roleId)->exists(), 404);

        $this->showPermissionsModal = false;
        $this->roleManagementInitialTab = 'editar';
        $this->roleManagementInitialRoleId = $roleId;
        $this->showRoleManagementModal = true;
    }

    #[On('role-management-closed')]
    #[On('role-form-closed')]
    #[On('role-form-saved')]
    public function closeRoleManagement(): void
    {
        $this->showRoleManagementModal = false;
    }

    public function openAssignmentModal(string $tab = 'crear'): void
    {
        abort_unless(app(\App\Services\Authorization\PermissionAccessService::class)
            ->allows(auth()->user(), 'administration.assignments.manage'), 403);
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar', 'relationships', 'interns'], true), 404);
        $this->assignmentModalTab = $tab;
        $this->showAssignmentModal = true;
    }

    public function setAssignmentModalTab(string $tab): void
    {
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar', 'relationships', 'interns'], true), 404);
        $this->assignmentModalTab = $tab;
    }

    public function closeAssignmentModal(): void
    {
        $this->showAssignmentModal = false;
    }

    #[On('customer-catalog-updated')]
    #[On('activity-catalog-updated')]
    #[On('permission-catalog-updated')]
    #[On('assignments-updated')]
    public function refreshOrganizationDirectory(): void
    {
        // El render siguiente obtiene el directorio recién invalidado sin
        // desmontar los formularios reutilizados por las tarjetas.
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

    public function openJobPositionModal(string $tab = 'crear', ?int $positionId = null): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);
        $this->showPhysicalAreaModal = false;
        $this->showPermissionsModal = false;
        $this->newJobPositionName = '';
        $this->newJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;
        $this->jobPositionModalTab = $tab;
        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->editJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;
        $this->deleteJobPositionConfirmation = '';
        $this->resetValidation(['newJobPositionName', 'selectedJobPositionId', 'editJobPositionName', 'deleteJobPositionConfirmation']);
        if ($positionId !== null) {
            abort_unless($tab !== 'crear' && JobPosition::whereKey($positionId)->exists(), 404);
            $this->updatedSelectedJobPositionId($positionId);
        }
        $this->showJobPositionModal = true;
    }

    public function closeJobPositionModal(): void
    {
        $this->showJobPositionModal = false;
        $this->newJobPositionName = '';
        $this->newJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;
        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->editJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;
        $this->deleteJobPositionConfirmation = '';
        $this->resetValidation(['newJobPositionName', 'selectedJobPositionId', 'editJobPositionName', 'deleteJobPositionConfirmation']);
    }

    public function saveJobPosition(): void
    {
        $this->ensureOrganizationAdministrator();
        $this->newJobPositionName = $this->normalizeCatalogName($this->newJobPositionName);

        $data = $this->validate([
            'newJobPositionName' => ['required', 'string', 'max:255', Rule::unique('job_positions', 'name')],
            'newJobPositionPaymentType' => ['required', Rule::in(JobPosition::paymentTypes())],
        ], [
            'newJobPositionName.required' => 'El nombre del puesto es obligatorio.',
            'newJobPositionName.max' => 'El nombre del puesto no debe exceder los 255 caracteres.',
            'newJobPositionName.unique' => 'Este puesto de trabajo ya existe.',
        ]);

        DB::transaction(function () use ($data): void {
            JobPosition::create([
                'name' => $data['newJobPositionName'],
                'payment_type' => $data['newJobPositionPaymentType'],
            ]);
        });

        $this->closeJobPositionModal();
        $this->forgetOrganizationReferenceData();
        session()->flash('success', 'Puesto de trabajo agregado correctamente.');
    }

    public function setJobPositionModalTab(string $tab): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        $this->jobPositionModalTab = $tab;
        $this->selectedJobPositionId = null;
        $this->editJobPositionName = '';
        $this->editJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;
        $this->deleteJobPositionConfirmation = '';
        $this->resetValidation(['selectedJobPositionId', 'editJobPositionName', 'deleteJobPositionConfirmation']);
    }

    public function updatedSelectedJobPositionId($positionId): void
    {
        $this->ensureOrganizationAdministrator();

        $this->selectedJobPositionId = filled($positionId) ? (int) $positionId : null;
        $position = $this->selectedJobPositionId
            ? JobPosition::findOrFail($this->selectedJobPositionId)
            : null;
        $this->editJobPositionName = (string) ($position?->name ?? '');
        $this->editJobPositionPaymentType = (string) ($position?->payment_type ?? JobPosition::PAYMENT_FULL_TIME);
        $this->deleteJobPositionConfirmation = '';
        $this->resetValidation(['selectedJobPositionId', 'editJobPositionName', 'deleteJobPositionConfirmation']);
    }

    public function updateJobPosition(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();
        $this->editJobPositionName = $this->normalizeCatalogName($this->editJobPositionName);

        $data = $this->validate([
            'selectedJobPositionId' => ['required', 'integer', 'exists:job_positions,id'],
            'editJobPositionName' => ['required', 'string', 'max:255', Rule::unique('job_positions', 'name')->ignore($this->selectedJobPositionId)],
            'editJobPositionPaymentType' => ['required', Rule::in(JobPosition::paymentTypes())],
        ]);

        DB::transaction(function () use ($data): void {
            JobPosition::findOrFail($data['selectedJobPositionId'])->update([
                'name' => $data['editJobPositionName'],
                'payment_type' => $data['editJobPositionPaymentType'],
            ]);

            if ($data['editJobPositionPaymentType'] === JobPosition::PAYMENT_FULL_TIME) {
                UserOrganizationalProfile::query()
                    ->where('job_position_id', $data['selectedJobPositionId'])
                    ->where('is_active', true)
                    ->update(['hourly_rate' => 0, 'food_allowance' => 0]);
            }
        });

        $this->loadOrgChart($chartService);
        $this->forgetOrganizationReferenceData();
        session()->flash('success', 'Puesto de trabajo actualizado correctamente.');
    }

    public function deleteJobPosition(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();

        $data = $this->validate([
            'selectedJobPositionId' => ['required', 'integer', 'exists:job_positions,id'],
            'deleteJobPositionConfirmation' => ['required', 'string'],
        ], [
            'deleteJobPositionConfirmation.required' => 'Escribe manualmente el nombre exacto del puesto.',
        ]);

        $positionId = $data['selectedJobPositionId'];
        $position = JobPosition::findOrFail($positionId);

        if (mb_strtolower($this->normalizeCatalogName($data['deleteJobPositionConfirmation'])) !== mb_strtolower($this->normalizeCatalogName($position->name))) {
            $this->addError('deleteJobPositionConfirmation', 'El nombre escrito no coincide exactamente con el puesto seleccionado.');

            return;
        }

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
        $this->editJobPositionPaymentType = JobPosition::PAYMENT_FULL_TIME;
        $this->deleteJobPositionConfirmation = '';
        $this->loadOrgChart($chartService);
        $this->forgetOrganizationReferenceData();
        session()->flash('success', 'Puesto eliminado. Los usuarios relacionados quedaron sin puesto asignado.');
    }

    public function openPhysicalAreaModal(string $tab = 'crear', ?int $areaId = null): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);
        $this->showJobPositionModal = false;
        $this->showPermissionsModal = false;
        $this->newPhysicalAreaName = '';
        $this->physicalAreaModalTab = $tab;
        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->deletePhysicalAreaConfirmation = '';
        $this->resetValidation('newPhysicalAreaName');
        if ($areaId !== null) {
            abort_unless($tab !== 'crear' && PhysicalArea::whereKey($areaId)->exists(), 404);
            $this->updatedSelectedPhysicalAreaManagementId($areaId);
        }
        $this->showPhysicalAreaModal = true;
    }

    public function closePhysicalAreaModal(): void
    {
        $this->showPhysicalAreaModal = false;
        $this->newPhysicalAreaName = '';
        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->deletePhysicalAreaConfirmation = '';
        $this->resetValidation(['newPhysicalAreaName', 'selectedPhysicalAreaManagementId', 'editPhysicalAreaName', 'deletePhysicalAreaConfirmation']);
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
        $this->forgetOrganizationReferenceData();
        session()->flash('success', 'Área o departamento agregado correctamente.');
    }

    public function setPhysicalAreaModalTab(string $tab): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        $this->physicalAreaModalTab = $tab;
        $this->selectedPhysicalAreaManagementId = null;
        $this->editPhysicalAreaName = '';
        $this->deletePhysicalAreaConfirmation = '';
        $this->resetValidation(['selectedPhysicalAreaManagementId', 'editPhysicalAreaName', 'deletePhysicalAreaConfirmation']);
    }

    public function updatedSelectedPhysicalAreaManagementId($areaId): void
    {
        $this->ensureOrganizationAdministrator();

        $this->selectedPhysicalAreaManagementId = filled($areaId) ? (int) $areaId : null;
        $this->editPhysicalAreaName = $this->selectedPhysicalAreaManagementId
            ? (string) PhysicalArea::findOrFail($this->selectedPhysicalAreaManagementId)->name
            : '';
        $this->deletePhysicalAreaConfirmation = '';
        $this->resetValidation(['selectedPhysicalAreaManagementId', 'editPhysicalAreaName', 'deletePhysicalAreaConfirmation']);
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
        $this->forgetOrganizationReferenceData();
        session()->flash('success', 'Área o departamento actualizado correctamente.');
    }

    public function deletePhysicalArea(OrganizationChartService $chartService): void
    {
        $this->ensureOrganizationAdministrator();

        $data = $this->validate([
            'selectedPhysicalAreaManagementId' => ['required', 'integer', 'exists:physical_areas,id'],
            'deletePhysicalAreaConfirmation' => ['required', 'string'],
        ], [
            'deletePhysicalAreaConfirmation.required' => 'Escribe manualmente el nombre exacto del área.',
        ]);

        $areaId = $data['selectedPhysicalAreaManagementId'];
        $area = PhysicalArea::findOrFail($areaId);
        if (mb_strtolower(trim($data['deletePhysicalAreaConfirmation'])) !== mb_strtolower(trim((string) $area->name))) {
            $this->addError('deletePhysicalAreaConfirmation', 'El nombre escrito no coincide con el área seleccionada.');

            return;
        }

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
        $this->deletePhysicalAreaConfirmation = '';
        $this->loadOrgChart($chartService);
        $this->forgetOrganizationReferenceData();
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
            'activeOrganizationalProfile.jobPosition:id,name,payment_type',
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
            'is_hourly_position' => $profile?->jobPosition?->isHourly() ?? false,
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
            'is_hourly_position' => $profile?->jobPosition?->isHourly() ?? false,
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

    public function updatedUserFormJobPositionId($positionId): void
    {
        $this->userForm['is_hourly_position'] = JobPosition::query()
            ->whereKey((int) $positionId)
            ->where('payment_type', JobPosition::PAYMENT_HOURLY)
            ->exists();
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
        $normalizedSuperiorIds = array_slice($normalizedSuperiorIds, -1);
        $excludedSubordinateIds = $this->superiorLineageIds($normalizedSuperiorIds);

        $this->userForm['superior_ids'] = $normalizedSuperiorIds;
        $this->userForm['subordinate_ids'] = array_values(array_diff(
            $this->normalizeHierarchyUserIds($this->userForm['subordinate_ids'] ?? []),
            $excludedSubordinateIds
        ));
    }

    public function selectSuperior(int $superiorId): void
    {
        $this->updatedUserFormSuperiorIds([$superiorId]);
    }

    public function clearSuperiorSelection(): void
    {
        $this->updatedUserFormSuperiorIds([]);
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

    public function saveSelectedUser(
        OrganizationChartService $chartService,
        AttendanceSettingsService $settingsService,
    ): void
    {
        $this->ensureOrganizationAdministrator();
        abort_unless($this->selectedUserId, 404);

        $user = User::findOrFail($this->selectedUserId);
        $isHourlyPosition = JobPosition::query()
            ->whereKey((int) ($this->userForm['job_position_id'] ?? 0))
            ->where('payment_type', JobPosition::PAYMENT_HOURLY)
            ->exists();
        $this->userForm['is_hourly_position'] = $isHourlyPosition;
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
            'userForm.is_hourly_position' => ['boolean'],
            'userForm.password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'userForm.superior_ids' => ['nullable', 'array', 'max:1'],
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
                function ($attribute, $value, $fail) use ($user): void {
                    if (UserHierarchyRelation::query()
                        ->where('subordinate_id', (int) $value)
                        ->where('superior_id', '<>', $user->id)
                        ->exists()) {
                        $fail('El subordinado ya tiene un jefe directo asignado.');
                    }
                },
            ],
        ];

        if ($isHourlyPosition) {
            $rules['userForm.hourly_rate'] = ['required', 'numeric', 'min:0'];
            $rules['userForm.food_allowance'] = ['required', 'numeric', 'min:0'];
        }

        $data = $this->validate($rules)['userForm'];

        DB::transaction(function () use ($user, $data, $isHourlyPosition, $chartService) {
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
                    'hourly_rate' => $isHourlyPosition ? $data['hourly_rate'] : 0,
                    'food_allowance' => $isHourlyPosition ? $data['food_allowance'] : 0,
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

        if (filled($data['employee_id'] ?? null)) {
            $settingsService->saveGeneral(
                (string) $data['employee_id'],
                (float) ($isHourlyPosition ? $data['hourly_rate'] : 0),
                (float) ($isHourlyPosition ? $data['food_allowance'] : 0),
                preserveDayOverrides: true,
            );
        }

        $this->loadOrgChart($chartService);
        $this->selectUser($user->id);
        $this->isEditingUser = false;
        $references = app(ReferenceDataCache::class);
        $references->forgetManageableUsers();
        $references->forgetOrganizationDashboard();
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
        $references = app(ReferenceDataCache::class);
        $references->forgetManageableUsers();
        $references->forgetOrganizationDashboard();
        session()->flash('success', 'Usuario eliminado. Sus subordinados directos quedaron sin jefe asignado.');
    }

    private function canManageOrganization(): bool
    {
        return app(\App\Services\Authorization\PermissionAccessService::class)
            ->allows(auth()->user(), 'administration.organization.manage');
    }

    private function ensureOrganizationAdministrator(): void
    {
        abort_unless($this->canManageOrganization(), 403);
    }

    private function normalizeCatalogName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    private function forgetOrganizationReferenceData(): void
    {
        $references = app(ReferenceDataCache::class);
        $references->forgetAdministration();
        $references->forgetOrganizationDashboard();
    }

    public function render()
    {
        $selectedSuperiorIds = $this->normalizeHierarchyUserIds($this->userForm['superior_ids'] ?? []);
        $selectedSubordinateIds = $this->normalizeHierarchyUserIds($this->userForm['subordinate_ids'] ?? []);
        $needsHierarchyCandidates = $this->selectedUserDetails !== null || $this->selectedUserId !== null;
        $hierarchyRelations = $needsHierarchyCandidates
            ? UserHierarchyRelation::query()->get(['subordinate_id', 'superior_id'])
            : collect();
        $excludedSubordinateIds = $this->superiorLineageIds($selectedSuperiorIds, $hierarchyRelations);
        $references = app(ReferenceDataCache::class);
        $administrationReferences = $references->administration();
        $dashboard = $references->organizationDashboard();
        // Calienta el selector de usuarios durante la carga inicial para que
        // abrir Editar o Eliminar no dispare otra consulta remota.
        $references->manageableUsers();
        $onlineUserIds = $dashboard['onlineUserIds']
            ->push((int) auth()->id())
            ->filter()
            ->unique();
        $permissionAccess = app(\App\Services\Authorization\PermissionAccessService::class);
        $currentUser = auth()->user();
        $isAdministrator = $currentUser?->isAdmin() ?? false;
        $directoryAccess = array_filter([
            'users' => $permissionAccess->allows($currentUser, 'administration.users.manage'),
            'roles' => $permissionAccess->allows($currentUser, 'administration.roles.manage'),
            'positions' => $permissionAccess->allows($currentUser, 'administration.organization.manage'),
            'areas' => $permissionAccess->allows($currentUser, 'administration.organization.manage'),
            'permissions' => $permissionAccess->allows($currentUser, 'administration.permissions.manage'),
            'customers' => $isAdministrator && $permissionAccess->allows($currentUser, 'administration.organization.manage'),
            'assignments' => $permissionAccess->allows($currentUser, 'administration.assignments.manage'),
            'activities' => $isAdministrator && $permissionAccess->allows($currentUser, 'administration.organization.manage'),
        ]);
        $organizationDirectory = array_intersect_key(
            $references->organizationDirectory(),
            $directoryAccess,
        );

        $superiorCandidates = $needsHierarchyCandidates
            ? User::query()
                ->whereKeyNot($this->selectedUserId ?: 0)
                ->whereIn('id', $this->hierarchyCandidateIds($hierarchyRelations))
                ->when($selectedSubordinateIds !== [], fn ($query) => $query->whereNotIn('id', $selectedSubordinateIds))
                ->orderBy('name')
                ->get(['id', 'name', 'last_name', 'email'])
            : collect();
        $subordinateCandidates = $needsHierarchyCandidates
            ? User::query()
                ->whereKeyNot($this->selectedUserId ?: 0)
                ->when($excludedSubordinateIds !== [], fn ($query) => $query->whereNotIn('id', $excludedSubordinateIds))
                ->whereDoesntHave('hierarchyRelationsAsSubordinate', function ($query): void {
                    $query->where('superior_id', '<>', $this->selectedUserId ?: 0);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'last_name', 'email'])
            : collect();

        return view('livewire.administracion.panel-administracion', [
            'onlineUserCount' => $onlineUserIds->count(),
            'recentOrganizationUsers' => $dashboard['recentUsers'],
            'lastOrganizationUserCreatedAt' => $dashboard['recentUsers']->first()?->created_at,
            'organizationAreaUserCounts' => $dashboard['areaUserCounts'],
            'organizationRoleUserCounts' => $dashboard['roleUserCounts'],
            'organizationPositionUserCounts' => $dashboard['positionUserCounts'],
            'organizationCustomers' => $dashboard['customers'],
            'organizationCustomerCount' => $dashboard['customerCount'],
            'organizationActivities' => $dashboard['activities'],
            'organizationActivityCount' => $dashboard['activityCount'],
            'organizationAssignmentCounts' => $dashboard['assignmentCounts'],
            'organizationDirectory' => $organizationDirectory,
            'physicalAreas' => $administrationReferences['physicalAreas'],
            'jobPositions' => $administrationReferences['jobPositions'],
            'roles' => $administrationReferences['roles'],
            'basePermissionProfiles' => $administrationReferences['basePermissionProfiles'],
            // Se precarga con la página para que abrir el formulario no tenga
            // que esperar la agregación del reloj checador.
            'employeeIdSuggestions' => $references->employeeSuggestions(),
            // Un jefe debe pertenecer ya al organigrama (tener alguna relación).
            'superiorCandidates' => $superiorCandidates,
            // Un subordinado puede provenir de la lista general, excepto los
            // jefes seleccionados y todos los superiores de su línea de mando.
            'subordinateCandidates' => $subordinateCandidates,
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
    private function hierarchyCandidateIds(?Collection $relations = null): array
    {
        $relations ??= UserHierarchyRelation::query()->get(['subordinate_id', 'superior_id']);

        return $relations
            ->flatMap(fn (UserHierarchyRelation $relation): array => [
                $relation->superior_id,
                $relation->subordinate_id,
            ])
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
    private function superiorLineageIds(array $superiorIds, ?Collection $relations = null): array
    {
        if ($superiorIds === []) {
            return [];
        }

        $relations ??= UserHierarchyRelation::query()->get(['subordinate_id', 'superior_id']);

        $superiorsBySubordinate = [];

        foreach ($relations as $relation) {
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

        if (count($superiorIds) > 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'userForm.superior_ids' => 'Cada subordinado solo puede tener un jefe directo.',
            ]);
        }

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
