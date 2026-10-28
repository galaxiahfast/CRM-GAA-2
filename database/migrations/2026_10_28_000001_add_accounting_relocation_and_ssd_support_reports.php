<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $target = ['Carlos Rafael De Jesús Diaz Ruiz'];

        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261028-0008'],
            [
                'issued_at' => '2026-10-28 18:14:40',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Contabilidad',
                'supports' => json_encode([
                    [
                        'key' => 'computer_relocation',
                        'title' => 'Reubicación y reconexión de equipo de cómputo',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'contpaqi_contabilidad_install',
                        'title' => 'Instalación de CONTPAQi Contabilidad',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'contpaqi_nominas_install',
                        'title' => 'Instalación de CONTPAQi Nóminas',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'windows_contpaqi_compatibility_update',
                        'title' => 'Actualización de Windows para compatibilidad con CONTPAQi',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-28 18:14:40',
                'updated_at' => '2026-10-28 18:14:40',
            ],
        );

        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261028-0009'],
            [
                'issued_at' => '2026-10-28 18:15:00',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Contabilidad',
                'supports' => json_encode([
                    [
                        'key' => 'ssd_full_backup',
                        'title' => 'Respaldo integral de información y configuraciones',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'ssd_corrected_system_migration',
                        'title' => 'Instalación de nueva unidad SSD y migración corregida del sistema',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                    [
                        'key' => 'ssd_diagnosis_repair',
                        'title' => 'Diagnóstico y reparación de unidad SSD anterior',
                        'quantity' => 1,
                        'targets' => $target,
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-28 18:15:00',
                'updated_at' => '2026-10-28 18:15:00',
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->whereIn('folio', ['ST-20261028-0008', 'ST-20261028-0009'])
            ->delete();
    }
};
