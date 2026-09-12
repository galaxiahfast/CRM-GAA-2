<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\PermissionGroup;
use App\Models\Role;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    public string $editingRoleName = '';

    public string $editingRoleDescription = '';

    public ?int $permissionGroupId = null;

    /** @var list<int> */
    public array $permissionIds = [];

    public string $deleteConfirmationName = '';

    public string $deleteConfirmationWord = '';

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
        }
    }

    public function updatedSearch()
    {
        $this->loadRoles();
    }

    public function deleteRole($id)
    {
        try {
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
        if (filled($roleId)) {
            $this->selectRole((int) $roleId);
        } else {
            $this->clearRoleSelection();
        }
    }

    public function selectRole(int $roleId): void
    {
        $role = Role::query()->with('accessPermissions:id')->withCount('users')->findOrFail($roleId);

        if ($this->activeTab === 'delete'
            && (in_array($role->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true)
                || $role->users_count > 0)) {
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
        $this->resetValidation();
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
        $this->resetValidation();
    }

    public function render()
    {
        $permissionCatalog = app(ReferenceDataCache::class)->permissionCatalog();

        return view('livewire.administracion.roles.gestion-roles', [
            'selectedRole' => $this->editingRoleId
                ? Role::query()->withCount(['users', 'accessPermissions'])->find($this->editingRoleId)
                : null,
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
}
