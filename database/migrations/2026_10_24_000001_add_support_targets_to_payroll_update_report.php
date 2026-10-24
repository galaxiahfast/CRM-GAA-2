<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0002')
            ->update([
                'supports' => json_encode([
                    ['key' => 'contpaqi_nominas_server_update', 'title' => 'Actualización de servidor de CONTPAQi Nóminas', 'quantity' => 1, 'targets' => []],
                    ['key' => 'contpaqi_nominas_update', 'title' => 'Actualización de terminales de CONTPAQi Nóminas', 'quantity' => 3, 'targets' => [
                        'Martín Geovany Polanco Pineda',
                        'Axel Bladimir Dzul Pat',
                        'Carlos Jesús Campos Yah',
                    ]],
                    ['key' => 'contpaqi_contabilidad_update', 'title' => 'Actualización de terminales de CONTPAQi Contabilidad', 'quantity' => 4, 'targets' => [
                        'María Sinaí Campos Caamal',
                        'Pedro Pablo Tuyub Albornoz',
                        'Alan Alberto Chan Caballero',
                        'Carlos Rafael de Jesús Díaz Ruiz',
                    ]],
                    ['key' => 'linked_spreadsheets_correction', 'title' => 'Corrección de hojas de cálculo vinculadas', 'quantity' => 1, 'targets' => [
                        'María Sinaí Campos Caamal',
                    ]],
                    ['key' => 'office_license_activation', 'title' => 'Activación de licencia de Microsoft Office', 'quantity' => 1, 'targets' => [
                        'María Sinaí Campos Caamal',
                    ]],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0002')
            ->update([
                'supports' => json_encode([
                    ['key' => 'contpaqi_nominas_server_update', 'title' => 'Actualización de servidor de CONTPAQi Nóminas', 'quantity' => 1],
                    ['key' => 'contpaqi_nominas_update', 'title' => 'Actualización de terminales de CONTPAQi Nóminas', 'quantity' => 3],
                    ['key' => 'contpaqi_contabilidad_update', 'title' => 'Actualización de terminales de CONTPAQi Contabilidad', 'quantity' => 3],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
