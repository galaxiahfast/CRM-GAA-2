<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['role' => 'Administrador', 'description' => 'Rol para administrar todas las funciones del sistema', 'permission_profile' => Role::PROFILE_ADMINISTRATOR],
            ['role' => 'Coordinador', 'description' => 'Rol para los coordinadores con casi todas las funciones del sistema', 'permission_profile' => Role::PROFILE_CUSTOM],
            ['role' => 'Contador', 'description' => 'Rol para los contadores con menos funcionalidades en el sistema', 'permission_profile' => Role::PROFILE_CUSTOM],
            ['role' => 'Auxiliar', 'description' => 'Rol para los auxiliares con funciones especificas', 'permission_profile' => Role::PROFILE_AUXILIARY],
        ] as $role) {
            DB::table('roles')->updateOrInsert(['role' => $role['role']], $role);
        }
    }
}
