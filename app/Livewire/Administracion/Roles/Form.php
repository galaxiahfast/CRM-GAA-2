<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\AccessPermission;
use App\Models\Role;
use App\Services\Authorization\PermissionAccessService;
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

    public function mount($role = null)
    {
        if ($role && $role->exists) {
            $this->roles = $role;
            $this->role = $role->role;
            $this->description = $role->description;
            $this->mode = 'edit';
            $this->permissionProfile = $role->permission_profile ?: Role::PROFILE_CUSTOM;
            $this->permissionIds = $role->accessPermissions()
                ->pluck('access_permissions.id')
                ->map(fn ($permissionId) => (int) $permissionId)
                ->all();
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

        if ($this->mode === 'edit' && $this->roles?->role === 'Administrador') {
            $this->permissionProfile = Role::PROFILE_ADMINISTRATOR;
        } elseif ($this->mode === 'edit' && $this->roles?->role === 'Auxiliar') {
            $this->permissionProfile = Role::PROFILE_AUXILIARY;
        }

        $rules = [
            'role' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role')->ignore($this->roles ? $this->roles->id : null),
            ],
            'description' => 'nullable|string|max:255',
            'permissionProfile' => ['required', Rule::in(Role::permissionProfiles())],
            'permissionIds' => [
                'array',
                Rule::requiredIf(fn (): bool => $this->permissionProfile === Role::PROFILE_CUSTOM),
                Rule::when($this->permissionProfile === Role::PROFILE_CUSTOM, ['min:1']),
            ],
            'permissionIds.*' => [
                'integer',
                Rule::exists('access_permissions', 'id')->where('is_active', true),
            ],
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

                $permissions->syncRoleAccess(
                    $savedRole,
                    $data['permissionProfile'],
                    $data['permissionIds'] ?? []
                );
            });

            session()->flash('success', 'Rol guardado exitosamente.');

            return redirect()->to('/administracion/roles');
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al guardar el rol. Inténtalo nuevamente.');

            return;
        }
    }

    public function cancel()
    {
        return redirect()->route('administracion.index');
    }

    public function render()
    {
        return view('livewire.administracion.roles.form', [
            'permissionProfiles' => config('access-permissions.profiles', []),
            'availablePermissions' => AccessPermission::query()
                ->active()
                ->orderBy('module')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'key', 'name', 'module', 'description']),
        ]);
    }
}
