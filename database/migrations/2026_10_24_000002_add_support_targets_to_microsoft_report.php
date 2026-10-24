<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0003')
            ->update([
                'supports' => json_encode([
                    ['key' => 'microsoft_365_refresh', 'title' => 'Desinstalación e instalación actual de Microsoft 365 en Contabilidad', 'quantity' => 2, 'targets' => [
                        'Lizet Nayeli Pool Chin',
                        'Anastacio May Ku',
                    ]],
                    ['key' => 'claude_addin_setup', 'title' => 'Configuración del complemento de Claude en Microsoft 365', 'quantity' => 2, 'targets' => [
                        'Lizet Nayeli Pool Chin',
                        'Anastacio May Ku',
                    ]],
                    ['key' => 'contpaqi_contabilidad_reinstall', 'title' => 'Reinstalación de CONTPAQi Contabilidad', 'quantity' => 1, 'targets' => [
                        'Josué Ricardo Puc Pech',
                    ]],
                    ['key' => 'contpaqi_nominas_reinstall', 'title' => 'Reinstalación de CONTPAQi Nóminas', 'quantity' => 1, 'targets' => [
                        'Josué Ricardo Puc Pech',
                    ]],
                    ['key' => 'microsoft_store_compatibility_update', 'title' => 'Actualización de Windows para compatibilidad con Microsoft Store', 'quantity' => 1, 'targets' => [
                        'Anastacio May Ku',
                    ]],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0003')
            ->update([
                'supports' => json_encode([
                    ['key' => 'microsoft_365_refresh', 'title' => 'Desinstalación e instalación actual de Microsoft 365 en Contabilidad', 'quantity' => 2],
                    ['key' => 'claude_addin_setup', 'title' => 'Configuración del complemento de Claude en Microsoft 365', 'quantity' => 2],
                    ['key' => 'contpaqi_contabilidad_reinstall', 'title' => 'Reinstalación de CONTPAQi Contabilidad', 'quantity' => 1],
                    ['key' => 'contpaqi_nominas_reinstall', 'title' => 'Reinstalación de CONTPAQi Nóminas', 'quantity' => 1],
                    ['key' => 'microsoft_store_compatibility_update', 'title' => 'Actualización de Windows para compatibilidad con Microsoft Store', 'quantity' => 1],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
