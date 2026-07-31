<?php

namespace Database\Seeders;

use App\Models\AccessPermission;
use App\Models\Role;
use App\Services\Authorization\PermissionAccessService;
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

            Role::query()
                ->whereIn('permission_profile', [Role::PROFILE_ADMINISTRATOR, Role::PROFILE_AUXILIARY])
                ->each(fn (Role $role) => $access->syncRoleAccess($role, $role->permission_profile));
        });
    }
}
