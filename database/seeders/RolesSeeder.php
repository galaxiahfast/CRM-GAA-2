<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
USE Illuminate\Support\Facades\DB;


class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("roles")->insert([
            ['role' => 'Administrador', 'description' => 'Rol para administrar todas las funciones del sistema'],
            ['role' => 'Coordinador', 'description'=> 'Rol para los coordinadores con casi todas las funciones del sistema'],
            ['role' => 'Contador', 'description'=> 'Rol para los contadores con menos funcionalidades en el sistema'],
            ['role' => 'Auxiliar', 'description'=> 'Rol para los auxiliares con funciones especificas'],
        ]);
    }
}
