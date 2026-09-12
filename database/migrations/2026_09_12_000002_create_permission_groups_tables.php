<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permission_group_access_permission', function (Blueprint $table): void {
            $table->foreignId('permission_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['permission_group_id', 'access_permission_id'], 'permission_group_access_primary');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('permission_group_id')->nullable()->after('permission_profile')->constrained()->nullOnDelete();
        });

        foreach ([
            'administrator' => ['name' => 'Administración completa', 'description' => 'Acceso integral a la administración y operación del sistema.'],
            'auxiliary' => ['name' => 'Operación auxiliar', 'description' => 'Acceso operativo a clientes, actividades, reloj y productividad personal.'],
        ] as $profile => $definition) {
            DB::table('permission_groups')->updateOrInsert(
                ['name' => $definition['name']],
                ['description' => $definition['description'], 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            );
            $groupId = DB::table('permission_groups')->where('name', $definition['name'])->value('id');
            $keys = collect(config('access-permissions.catalog', []))
                ->filter(fn (array $item): bool => in_array($profile, $item['profiles'] ?? [], true))
                ->pluck('key');
            $permissionIds = DB::table('access_permissions')->whereIn('key', $keys)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_group_access_permission')->insertOrIgnore([
                    'permission_group_id' => $groupId,
                    'access_permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('roles')->where('permission_profile', $profile)->update(['permission_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('permission_group_id');
        });
        Schema::dropIfExists('permission_group_access_permission');
        Schema::dropIfExists('permission_groups');
    }
};
