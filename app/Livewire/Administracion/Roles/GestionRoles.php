<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class GestionRoles extends Component
{
    public $roles = null;

    public $search = '';

    #[Url(as: 'tab')]
    public string $activeTab = 'edit';

    public bool $embedded = false;

    public ?int $editingRoleId = null;

    /** Rol fijo cuando el formulario se abre desde un registro (editar/eliminar). */
    #[Locked]
    public ?int $lockedRoleId = null;

    public string $editingRoleName = '';

    public string $editingRoleDescription = '';

    public ?int $permissionGroupId = null;

    /** @var list<int> */
    public array $permissionIds = [];

    public string $deleteConfirmationName = '';

    public string $deleteConfirmationWord = '';

    public string $userSearch = '';

    /** @var list<int> */
    public array $selectedAssignedUserIds = [];

    /** @var list<int> */
    public array $selectedAvailableUserIds = [];

    public ?int $replacementRoleId = null;

    public function mount(bool $embedded = false, string $initialTab = 'edit', ?int $initialRoleId = null)
    {
        $this->embedded = $embedded;
        if ($embedded) {
            $this->activeTab = $initialTab === 'eliminar' ? 'delete' : 'edit';
        }
        if (! in_array($this->activeTab, ['edit', 'delete'], true)) {
            $this->activeTab = 'edit';
        }

        $this->loadRoles();
        if ($initialRoleId && Role::whereKey($initialRoleId)->exists()) {
            $this->selectRole($initialRoleId);
            if ($embedded && (int) $this->editingRoleId === (int) $initialRoleId) {
                $this->lockedRoleId = (int) $initialRoleId;
            }
        }
    }

    public function updatedSearch()
    {
        $this->loadRoles();
    }

    public function deleteRole($id)
    {
        try {
            if ($this->lockedRoleId !== null) {
                $id = $this->lockedRoleId;
                $this->editingRoleId = $this->lockedRoleId;
            }

            $role_id = Role::find($id);

            if (! $role_id) {
                throw new \Exception('Rol no encontrado.');
            }

            if (in_array($role_id->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true)
                || $role_id->users()->exists()) {
                session()->flash('error', 'No se puede eliminar un rol base ni un rol con usuarios asociados.');

                return;
            }

            if ((int) $this->editingRoleId !== (int) $role_id->id) {
                $this->addError('editingRoleId', 'Selecciona el rol antes de eliminarlo.');

                return;
            }

            if ($this->normalizeConfirmation($this->deleteConfirmationName) !== $this->normalizeConfirmation($role_id->role)) {
                $this->addError('deleteConfirmationName', 'Escribe exactamente el nombre del rol.');

                return;
            }

            if (mb_strtoupper(trim($this->deleteConfirmationWord)) !== 'ELIMINAR') {
                $this->addError('deleteConfirmationWord', 'Escribe la palabra ELIMINAR.');

                return;
            }

            $role_id->delete();
            $references = app(ReferenceDataCache::class);
            $references->forgetAdministration();
            $references->forgetOrganizationDashboard();
            session()->flash('success', 'Rol eliminado exitosamente.');

            $this->loadRoles();
            $this->clearRoleSelection();

            if (! $this->embedded) {
                return redirect()->route('administracion.role', ['tab' => 'delete']);
            }
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al eliminar el rol: '.$e->getMessage());

            return;
        }
    }

    public function cancel()
    {
        if ($this->embedded) {
            $this->dispatch('role-management-closed');

            return;
        }

        return redirect()->route('administracion.index');
    }

    public function editRole(int $roleId): void
    {
        $this->selectRole($roleId);
    }

    public function updatedEditingRoleId($roleId): void
    {
        if ($this->lockedRoleId !== null && (int) $roleId !== $this->lockedRoleId) {
            $this->editingRoleId = $this->lockedRoleId;

            return;
        }

        if (filled($roleId)) {
            $this->selectRole((int) $roleId);
        } else {
            $this->clearRoleSelection();
        }
    }

    public function selectRole(int $roleId): void
    {
        if ($this->lockedRoleId !== null && $roleId !== $this->lockedRoleId) {
            $roleId = $this->lockedRoleId;
        }

        $role = Role::query()->with('accessPermissions:id')->withCount('users')->findOrFail($roleId);

        if ($this->activeTab === 'delete'
            && in_array($role->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true)) {
            $this->clearRoleSelection();

            return;
        }

        $this->editingRoleId = $role->id;
        $this->editingRoleName = $role->role;
        $this->editingRoleDescription = (string) ($role->description ?? '');
        $this->permissionGroupId = $role->permission_group_id ? (int) $role->permission_group_id : null;
        $this->permissionIds = $role->accessPermissions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->deleteConfirmationName = '';
        $this->deleteConfirmationWord = '';
        $this->selectedAssignedUserIds = [];
        $this->selectedAvailableUserIds = [];
        $this->replacementRoleId = null;
        $this->resetValidation();
    }

    public function assignUsersToRole(): void
    {
        abort_unless($this->editingRoleId, 404);

        $data = $this->validate([
            'selectedAvailableUserIds' => ['required', 'array', 'min:1'],
            'selectedAvailableUserIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ], [
            'selectedAvailableUserIds.required' => 'Selecciona al menos un usuario para asignarlo.',
            'selectedAvailableUserIds.min' => 'Selecciona al menos un usuario para asignarlo.',
        ]);

        $userIds = collect($data['selectedAvailableUserIds'])
            ->map(fn ($userId): int => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->contains((int) auth()->id())) {
            $this->addError('selectedAvailableUserIds', 'No puedes cambiar tu propio rol desde esta pantalla.');

            return;
        }

        $updated = User::query()
            ->whereKey($userIds)
            ->where('role_id', '<>', $this->editingRoleId)
            ->update(['role_id' => $this->editingRoleId]);

        if ($updated === 0) {
            $this->addError('selectedAvailableUserIds', 'Los usuarios seleccionados ya tienen este rol.');

            return;
        }

        $this->refreshAfterUserAssignment();
        session()->flash('success', $updated === 1
            ? 'Usuario asignado al rol correctamente.'
            : $updated.' usuarios asignados al rol correctamente.');
    }

    public function moveUsersToAnotherRole(): void
    {
        abort_unless($this->editingRoleId, 404);

        $data = $this->validate([
            'selectedAssignedUserIds' => ['required', 'array', 'min:1'],
            'selectedAssignedUserIds.*' => ['integer', 'distinct', 'exists:users,id'],
            'replacementRoleId' => [
                'required',
                'integer',
                'exists:roles,id',
                Rule::notIn([$this->editingRoleId]),
            ],
        ], [
            'selectedAssignedUserIds.required' => 'Selecciona al menos un usuario para cambiarlo de rol.',
            'selectedAssignedUserIds.min' => 'Selecciona al menos un usuario para cambiarlo de rol.',
            'replacementRoleId.required' => 'Selecciona el nuevo rol de los usuarios.',
            'replacementRoleId.not_in' => 'Selecciona un rol diferente al actual.',
        ]);

        $userIds = collect($data['selectedAssignedUserIds'])
            ->map(fn ($userId): int => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->contains((int) auth()->id())) {
            $this->addError('selectedAssignedUserIds', 'No puedes cambiar tu propio rol desde esta pantalla.');

            return;
        }

        $updated = User::query()
            ->whereKey($userIds)
            ->where('role_id', $this->editingRoleId)
            ->update(['role_id' => (int) $data['replacementRoleId']]);

        if ($updated === 0) {
            $this->addError('selectedAssignedUserIds', 'Los usuarios seleccionados ya no pertenecen a este rol.');

            return;
        }

        $this->refreshAfterUserAssignment();
        session()->flash('success', $updated === 1
            ? 'Usuario cambiado de rol correctamente.'
            : $updated.' usuarios cambiados de rol correctamente.');
    }

    public function selectPermissionGroup(int $groupId): void
    {
        $group = PermissionGroup::query()->with('permissions:id')->findOrFail($groupId);

        $this->permissionGroupId = $group->id;
        $this->permissionIds = $group->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->resetValidation(['permissionGroupId']);
    }

    public function saveEditedRole(PermissionAccessService $permissions): void
    {
        if ($this->lockedRoleId !== null) {
            $this->editingRoleId = $this->lockedRoleId;
        }

        abort_unless($this->editingRoleId, 404);

        $role = Role::query()->findOrFail($this->editingRoleId);
        $isSystemRole = in_array($role->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
        if ($isSystemRole) {
            $this->editingRoleName = $role->role;
        }

        $data = $this->validate([
            'editingRoleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role')->ignore($role->id),
            ],
            'editingRoleDescription' => ['nullable', 'string', 'max:255'],
            'permissionGroupId' => ['required', 'integer', 'exists:permission_groups,id'],
        ], [
            'permissionGroupId.required' => 'Selecciona el grupo de permisos que tendrá este rol.',
        ]);

        try {
            DB::transaction(function () use ($role, $data, $permissions): void {
                $role->update([
                    'role' => $data['editingRoleName'],
                    'description' => $data['editingRoleDescription'] ?: null,
                ]);
                $permissions->syncRolePermissionGroup($role, (int) $data['permissionGroupId']);
            });

            $references = app(ReferenceDataCache::class);
            $references->forgetAdministration();
            $references->forgetOrganizationDashboard();
            $this->loadRoles();
            $this->selectRole($role->id);
            session()->flash('success', 'Rol y grupo de permisos actualizados correctamente.');
        } catch (Throwable $e) {
            report($e);
            $this->addError('editingRoleId', 'No pudimos actualizar el rol. Inténtalo nuevamente.');
        }
    }

    public function clearRoleSelection(): void
    {
        $this->editingRoleId = null;
        $this->editingRoleName = '';
        $this->editingRoleDescription = '';
        $this->permissionGroupId = null;
        $this->permissionIds = [];
        $this->deleteConfirmationName = '';
        $this->deleteConfirmationWord = '';
        $this->userSearch = '';
        $this->selectedAssignedUserIds = [];
        $this->selectedAvailableUserIds = [];
        $this->replacementRoleId = null;
        $this->resetValidation();
    }

    public function render()
    {
        $permissionCatalog = app(ReferenceDataCache::class)->permissionCatalog();
        $selectedRole = $this->editingRoleId
            ? Role::query()->withCount(['users', 'accessPermissions'])->find($this->editingRoleId)
            : null;
        $assignedUsers = collect();
        $availableUsers = collect();

        if ($selectedRole) {
            $search = trim($this->userSearch);
            $userQuery = fn ($query) => $query->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            });

            $assignedUsers = $userQuery(User::query())
                ->where('role_id', $selectedRole->id)
                ->orderBy('name')
                ->orderBy('last_name')
                ->limit(100)
                ->get(['id', 'name', 'last_name', 'email', 'role_id']);

            if ($this->activeTab !== 'delete') {
                $availableUsers = $userQuery(User::query()->with('role:id,role'))
                    ->where('role_id', '<>', $selectedRole->id)
                    ->orderBy('name')
                    ->orderBy('last_name')
                    ->limit(100)
                    ->get(['id', 'name', 'last_name', 'email', 'role_id']);
            }
        }

        return view('livewire.administracion.roles.gestion-roles', [
            'selectedRole' => $selectedRole,
            'assignedUsers' => $assignedUsers,
            'availableUsers' => $availableUsers,
            'permissionGroups' => $permissionCatalog['groups'],
            'availablePermissions' => $permissionCatalog['permissions'],
        ])->layout('layouts.app');
    }

    private function loadRoles(): void
    {
        $this->roles = Role::query()
            ->with('permissionGroup:id,name')
            ->withCount(['users', 'accessPermissions'])
            ->orderBy('role')
            ->get();
    }

    private function normalizeConfirmation(?string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        return mb_strtoupper($value, 'UTF-8');
    }

    private function refreshAfterUserAssignment(): void
    {
        $roleId = (int) $this->editingRoleId;
        $references = app(ReferenceDataCache::class);
        $references->forgetAdministration();
        $references->forgetOrganizationDashboard();
        $references->forgetManageableUsers();
        $this->loadRoles();
        $this->selectRole($roleId);
    }
}
