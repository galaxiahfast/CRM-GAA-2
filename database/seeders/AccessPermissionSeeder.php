<?php

namespace Database\Seeders;

use App\Models\AccessPermission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $catalog = collect(config('access-permissions.catalog', []));
            $catalogKeys = $catalog->pluck('key')->filter()->all();

            foreach ($catalog as $definition) {
                $permission = AccessPermission::query()->updateOrCreate(
                    ['key' => $definition['key']],
                    Arr::only($definition, [
                        'name',
                        'module',
                        'description',
                        'sort_order',
                    ]) + ['is_active' => true]
                );

                $roleIds = Role::query()
                    ->whereIn('role', $definition['roles'] ?? [])
                    ->pluck('id')
                    ->all();

                $permission->roles()->syncWithoutDetaching($roleIds);
            }

            // Una clave retirada del catálogo se desactiva, no se elimina. Así
            // se conserva el historial y deja de conceder acceso inmediatamente.
            AccessPermission::query()
                ->when($catalogKeys !== [], fn ($query) => $query->whereNotIn('key', $catalogKeys))
                ->when($catalogKeys === [], fn ($query) => $query)
                ->update(['is_active' => false]);

            $access = app(PermissionAccessService::class);

            foreach ([
                Role::PROFILE_ADMINISTRATOR => ['name' => 'Administración completa', 'description' => 'Acceso integral a la administración y operación del sistema.'],
                Role::PROFILE_AUXILIARY => ['name' => 'Operación auxiliar', 'description' => 'Acceso operativo a clientes, actividades, reloj y productividad personal.'],
            ] as $profile => $groupData) {
                $group = PermissionGroup::query()->updateOrCreate(
                    ['name' => $groupData['name']],
                    $groupData + ['is_system' => true],
                );
                $group->permissions()->sync(
                    AccessPermission::query()->active()->whereIn('key', $access->permissionKeysForProfile($profile))->pluck('id')->all()
                );
                Role::query()
                    ->where('permission_profile', $profile)
                    ->each(fn (Role $role) => $access->syncRolePermissionGroup($role, $group->id));
            }
        });

        // Los permisos retirados se desactivan mediante una actualización
        // masiva (sin eventos Eloquent), por lo que la invalidación explícita
        // garantiza que el panel refleje inmediatamente el catálogo desplegado.
        app(ReferenceDataCache::class)->forgetPermissionCatalog();
    }
}
