<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261030-0013';

    public function up(): void
    {
        // Fecha de emisión en UTC (30 de octubre de 2026, 14:41 hora de Ciudad de México).
        $issuedAt = '2026-10-30 20:41:08';
        $target = ['Carlos Rafael De Jesús Diaz Ruiz'];

        $supports = [
            ['key' => 'linked_spreadsheets_correction', 'title' => 'Corrección de hojas de cálculo vinculadas'],
            ['key' => 'contpaqi_nominas_reinstall', 'title' => 'Reinstalación de CONTPAQi Nóminas'],
            ['key' => 'office_2013_uninstall', 'title' => 'Desinstalación de Microsoft Office 2013'],
            ['key' => 'office_2019_install', 'title' => 'Instalación de Microsoft Office 2019'],
            ['key' => 'office_2019_activation', 'title' => 'Activación de Microsoft Office 2019 y corrección de errores de activación'],
            ['key' => 'excel_addin_persistence', 'title' => 'Configuración del complemento de hoja de cálculo de Excel para mantenerlo activo'],
            ['key' => 'report_linked_files_bug_fix', 'title' => 'Corrección de error en los archivos enlazados del sistema de reportes'],
        ];

        $supports = array_map(
            fn (array $support): array => $support + ['quantity' => 1, 'targets' => $target],
            $supports,
        );

        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => self::FOLIO],
            [
                'issued_at' => $issuedAt,
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Contabilidad',
                'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ],
        );

        $report = DB::table('technical_support_reports')->where('folio', self::FOLIO)->first();

        // Igual que los reportes generados desde la página: queda el movimiento "Reporte generado".
        $snapshot = (array) $report;
        $snapshot['supports'] = $supports;
        $snapshot['details'] = ['versions_applied' => []];

        DB::table('technical_support_report_movements')->updateOrInsert(
            ['folio' => self::FOLIO, 'action' => 'created'],
            [
                'technical_support_report_id' => $report->id,
                'user_id' => null,
                'user_name' => null,
                'user_email' => null,
                'report_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'occurred_at' => $issuedAt,
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ],
        );
    }

    public function down(): void
    {
        DB::table('technical_support_report_movements')
            ->where('folio', self::FOLIO)
            ->delete();

        DB::table('technical_support_reports')
            ->where('folio', self::FOLIO)
            ->delete();
    }
};
