<?php

namespace Database\Seeders;

use App\Models\AccessPermission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('access-permissions.catalog', []) as $definition) {
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
        });
    }
}
