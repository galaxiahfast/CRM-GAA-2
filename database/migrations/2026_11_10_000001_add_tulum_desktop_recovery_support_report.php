<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261110-0026';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = now('America/Mexico_City')->utc()->format('Y-m-d H:i:s');
        $person = 'Manuel Agustin Kauil Canche';
        $reportId = max(26, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = [
            [
                'key' => 'desktop_display_diagnosis',
                'title' => 'Diagnóstico y corrección de fallas de visualización del escritorio',
                'quantity' => 1,
                'targets' => [$person],
            ],
            [
                'key' => 'local_system_recovery_copy',
                'title' => 'Configuración de copia local para recuperación del sistema',
                'quantity' => 1,
                'targets' => [$person],
            ],
        ];
        $details = [
            'versions_applied' => [],
            'summary' => 'Se revisó la falla que impedía mostrar correctamente el escritorio, se ajustó la configuración del sistema y se comprobó que volviera a mostrarse y operar. También se creó una copia local de recuperación en el mismo disco para intentar restaurar una versión anterior si la falla vuelve a presentarse.',
        ];

        $report = [
            'id' => $reportId,
            'folio' => self::FOLIO,
            'issued_at' => $issuedAt,
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'service_recipient' => 'Tulum Contabilidad',
            'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'created_by' => null,
            'created_at' => $issuedAt,
            'updated_at' => $issuedAt,
        ];

        DB::table('technical_support_reports')->insert($report);
        DB::table('technical_support_report_movements')->insert([
            'technical_support_report_id' => $reportId,
            'folio' => self::FOLIO,
            'action' => 'created',
            'user_id' => null,
            'user_name' => null,
            'user_email' => null,
            'report_snapshot' => json_encode([
                ...$report,
                'supports' => $supports,
                'details' => $details,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'occurred_at' => $issuedAt,
            'created_at' => $issuedAt,
            'updated_at' => $issuedAt,
        ]);
    }

    public function down(): void
    {
        DB::table('technical_support_report_movements')->where('folio', self::FOLIO)->delete();
        DB::table('technical_support_reports')->where('folio', self::FOLIO)->delete();
    }
};
