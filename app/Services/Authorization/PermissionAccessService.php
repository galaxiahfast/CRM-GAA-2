<?php

namespace App\Services\Authorization;

use App\Models\AccessPermission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionAccessService
{
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
