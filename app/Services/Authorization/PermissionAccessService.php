<?php

namespace App\Services\Authorization;

use App\Models\AccessPermission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionAccessService
{
    public function permissionKeysForProfile(string $profile): array
    {
        return collect(config('access-permissions.catalog', []))
            ->filter(fn (array $definition): bool => in_array($profile, $definition['profiles'] ?? [], true))
            ->pluck('key')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Comprueba siempre contra el estado actual de la base de datos.
     * No usa la relación Eloquent precargada para evitar accesos obsoletos
     * después de desactivar o eliminar un permiso.
     */
    public function allows(?User $user, string $permissionKey): bool
    {
        $permissionKey = trim($permissionKey);

        if (! $user || ! $user->role_id || $permissionKey === '') {
            return false;
        }

        $role = $user->role;
        $profile = $role?->permission_profile;

        // Compatibilidad para instalaciones donde los roles base ya existían
        // antes de agregar permission_profile.
        if ($role?->role === 'Administrador') {
            $profile = Role::PROFILE_ADMINISTRATOR;
        } elseif ($role?->role === 'Auxiliar') {
            $profile = Role::PROFILE_AUXILIARY;
        }

        if (in_array($profile, [Role::PROFILE_ADMINISTRATOR, Role::PROFILE_AUXILIARY], true)) {
            if (! in_array($permissionKey, $this->permissionKeysForProfile($profile), true)) {
                return false;
            }

            return AccessPermission::query()
                ->active()
                ->where('key', $permissionKey)
                ->exists();
        }

        return DB::table('access_permissions as access_permission')
            ->join(
                'role_access_permission as role_permission',
                'role_permission.access_permission_id',
                '=',
                'access_permission.id'
            )
            ->where('role_permission.role_id', (int) $user->role_id)
            ->where('access_permission.key', $permissionKey)
            ->where('access_permission.is_active', true)
            ->exists();
    }

    /**
     * Sincroniza únicamente permisos activos y existentes.
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $normalizedIds = collect($permissionIds)
            ->filter(fn ($permissionId) => is_numeric($permissionId))
            ->map(fn ($permissionId) => (int) $permissionId)
            ->filter(fn (int $permissionId) => $permissionId > 0)
            ->unique()
            ->values();

        $validPermissionIds = AccessPermission::query()
            ->active()
            ->whereKey($normalizedIds)
            ->pluck('id')
            ->all();

        $role->accessPermissions()->sync($validPermissionIds);
        $role->unsetRelation('accessPermissions');
    }

    public function syncRoleAccess(Role $role, string $profile, array $permissionIds = []): void
    {
        $profile = in_array($profile, Role::permissionProfiles(), true)
            ? $profile
            : Role::PROFILE_CUSTOM;

        $role->update(['permission_profile' => $profile]);

        if ($profile !== Role::PROFILE_CUSTOM) {
            $permissionIds = AccessPermission::query()
                ->active()
                ->whereIn('key', $this->permissionKeysForProfile($profile))
                ->pluck('id')
                ->all();
        }

        $this->syncRolePermissions($role, $permissionIds);
    }

    /**
     * La FK en cascada elimina solo los pivotes rol-permiso. No existe
     * ninguna relación de borrado desde un permiso hacia usuarios.
     */
    public function deletePermission(AccessPermission $permission): void
    {
        DB::transaction(function () use ($permission): void {
            $permission->delete();
        });
    }
}
