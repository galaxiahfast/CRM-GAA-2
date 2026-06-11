<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("sub_services")->insert([
            ['sub_service' => 'Impuesto sobre nómina', 'service_id' => 1, 'description' => 'Impuesto para detelimitar las nóminas de los clientes', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_IDNC_1'],
            ['sub_service' => 'IMSS', 'service_id' => 1, 'description' => 'Control de IMSS de los clientes', 'created_at' => now()->format('Y-m-d H:i:s') , 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_IMSS_2'],
            ['sub_service' => 'FONACOT', 'service_id' => 1, 'description' => 'Impuesto para detelimitar las nóminas de los clientes', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_FONACOT_3'],
            ['sub_service' => 'SISUB', 'service_id' => 1, 'description' => 'Impuesto para detelimitar el SISUB de los clientes', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_SISUB_4'],
            ['sub_service' => 'ICSOE', 'service_id' => 1, 'description' => 'Impuesto para detelimitar el ICSOE de los clientes', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_ICSOE_5'],

            ['sub_service' => 'Declaraciones', 'service_id' => 2, 'description' => 'Impuesto para detelimitar las diferentes tipos de declaraciones de los clientes', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_DECL_6'],
            ['sub_service' => 'Informativas (DIOT)', 'service_id' => 2, 'description' => 'Impuesto de informativas (DIOT) de los clientes', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_IDIOT_7'],
            ['sub_service' => 'Contabiliada Electrónica', 'service_id' => 2, 'description' => 'Impuesto para la contabiliada eléctronica', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_CONTEL_8'],

            ['sub_service' => 'Frutos Civiles', 'service_id' => 3, 'description' => 'Impuesto para detelimitar los frutos civiles', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_FRUCIV_9'],
            ['sub_service' => 'Impuesto Cedular', 'service_id' => 3, 'description' => 'Impuesto para detelimitar impuesto cedular', 'created_at' => now()->format('Y-m-d H:i:s'), 'updated_at' => now()->format('Y-m-d H:i:s'), 'unique_key' => 'subservice_IMPCED_10'],
        ]);
    }
}
