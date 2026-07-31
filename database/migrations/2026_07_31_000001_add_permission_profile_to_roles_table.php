<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('permission_profile', 20)->default('custom')->after('description')->index();
        });

        DB::table('roles')->where('role', 'Administrador')->update(['permission_profile' => 'administrator']);
        DB::table('roles')->where('role', 'Auxiliar')->update(['permission_profile' => 'auxiliary']);

        foreach (config('access-permissions.catalog', []) as $definition) {
            $now = now();
            DB::table('access_permissions')->updateOrInsert(
                ['key' => $definition['key']],
                [
                    'name' => $definition['name'],
                    'module' => $definition['module'] ?? null,
                    'description' => $definition['description'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $permissionId = DB::table('access_permissions')->where('key', $definition['key'])->value('id');
            $roleIds = DB::table('roles')->whereIn('role', $definition['roles'] ?? [])->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_access_permission')->insertOrIgnore([
                    'role_id' => $roleId,
                    'access_permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropIndex(['permission_profile']);
            $table->dropColumn('permission_profile');
        });
    }
};
