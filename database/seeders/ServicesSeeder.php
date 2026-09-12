<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Las instalaciones actuales reciben el catálogo mediante una migración
        // única. Evitamos reinsertarlo al ejecutar seeders para respetar las
        // categorías que un administrador haya eliminado deliberadamente.
        if (DB::table('services')->exists()) {
            return;
        }

        DB::table('services')->insert([
            ['service' => 'Impuestos Estatales', 'description'=> 'Impuestos estatales que la empresa tiene disponibles', 'created_at' => now()->format('Y-m-d H:i:s')],
            ['service' => 'Impuestos Federales', 'description'=> 'Impuestos federales que la empresa tiene disponibles','created_at' => now()->format('Y-m-d H:i:s')],
            ['service' => 'Impuestos Especiales', 'description'=> 'Impuestos especiales que la empresa tiene disponibles', 'created_at' => now()->format('Y-m-d H:i:s')],
        ]); 
    }
}
