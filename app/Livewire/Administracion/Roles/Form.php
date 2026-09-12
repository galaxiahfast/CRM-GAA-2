<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\PermissionGroup;
use App\Models\Role;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class Form extends Component
{
    public $roles = null;

    public $role = null;

    public $description = null;

    public $mode = 'create';

    public array $permissionIds = [];

    public string $permissionProfile = Role::PROFILE_CUSTOM;

    public ?int $permissionGroupId = null;

    public bool $embedded = false;

    public bool $returnToManager = false;

    public function mount($role = null, bool $embedded = false, bool $returnToManager = false)
    {
        $this->embedded = $embedded;
        $this->returnToManager = $returnToManager;
        if ($role && $role->exists) {
            $this->roles = $role;
            $this->role = $role->role;
            $this->description = $role->description;
            $this->mode = 'edit';
            $this->permissionProfile = $role->permission_profile ?: Role::PROFILE_CUSTOM;
            $this->permissionGroupId = $role->permission_group_id ? (int) $role->permission_group_id : null;
            $this->permissionIds = $role->accessPermissions()
                ->pluck('access_permissions.id')
                ->map(fn ($permissionId) => (int) $permissionId)
                ->all();
            if ($this->permissionProfile === Role::PROFILE_CUSTOM && $this->permissionIds === []) {
                $this->permissionProfile = match ($role->role) {
                    'Administrador' => Role::PROFILE_ADMINISTRATOR,
                    'Auxiliar' => Role::PROFILE_AUXILIARY,
                    default => Role::PROFILE_CUSTOM,
                };
            }
        }
    }

    public function save(PermissionAccessService $permissions)
    {
        if ($this->mode === 'edit'
            && $this->roles
            && in_array($this->roles->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true)) {
            // Estos nombres forman parte de las reglas de acceso actuales.
            $this->role = $this->roles->role;
        }

        $rules = [
            'role' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role')->ignore($this->roles ? $this->roles->id : null),
            ],
            'description' => 'nullable|string|max:255',
            'permissionGroupId' => ['required', 'integer', 'exists:permission_groups,id'],
        ];

        $data = $this->validate($rules);

        try {
            DB::transaction(function () use ($data, $permissions): void {
                $roleData = [
                    'role' => $data['role'],
                    'description' => $data['description'] ?? null,
                ];

                if ($this->mode === 'create') {
                    $savedRole = Role::create($roleData);
                } elseif ($this->mode === 'edit' && $this->roles) {
                    $this->roles->update($roleData);
                    $savedRole = $this->roles;
                } else {
                    throw new \RuntimeException('Modo inválido o rol no encontrado.');
                }

                $permissions->syncRolePermissionGroup($savedRole, (int) $data['permissionGroupId']);
            });

            $references = app(ReferenceDataCache::class);
            $references->forgetAdministration();
            $references->forgetOrganizationDashboard();
            session()->flash('success', 'Rol guardado exitosamente.');

            if ($this->embedded) {
                $this->dispatch($this->returnToManager ? 'role-editor-saved' : 'role-form-saved');

                return;
            }

            return redirect()->to('/administracion/roles');
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al guardar el rol. Inténtalo nuevamente.');

            return;
        }
    }

    public function cancel()
    {
        if ($this->embedded) {
            $this->dispatch($this->returnToManager ? 'role-editor-closed' : 'role-form-closed');

            return;
        }

        return redirect()->route('administracion.index');
    }

    public function selectPermissionGroup(?int $groupId): void
    {
        $this->permissionGroupId = $groupId;
        $this->permissionProfile = Role::PROFILE_CUSTOM;
        $this->permissionIds = $groupId
            ? PermissionGroup::query()->findOrFail($groupId)->permissions()->active()->pluck('access_permissions.id')->map(fn ($id) => (int) $id)->all()
            : $this->permissionIds;
        $this->resetValidation(['permissionGroupId', 'permissionIds']);
    }

    public function render()
    {
        $permissionCatalog = app(ReferenceDataCache::class)->permissionCatalog();

        return view('livewire.administracion.roles.form', [
            'permissionProfiles' => config('access-permissions.profiles', []),
            'availablePermissions' => $permissionCatalog['permissions'],
            'permissionGroups' => $permissionCatalog['groups'],
        ]);
    }
}
