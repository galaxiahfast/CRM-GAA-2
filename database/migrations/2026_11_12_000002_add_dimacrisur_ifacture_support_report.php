<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261112-0028';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = now('America/Mexico_City')->utc()->format('Y-m-d H:i:s');
        $reportId = max(28, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = [
            ['key' => 'sql_server_management_studio_install', 'title' => 'Instalación de SQL Server Management Studio', 'quantity' => 1, 'targets' => []],
            ['key' => 'powershell_2_fix', 'title' => 'Corrección de PowerShell 2.0', 'quantity' => 1, 'targets' => []],
            ['key' => 'database_catalog_products_export', 'title' => 'Exportación de catálogos y productos de la base de datos', 'quantity' => 1, 'targets' => []],
            ['key' => 'ifacture_services_photo_record', 'title' => 'Registro fotográfico de los servicios utilizados en iFacture', 'quantity' => 1, 'targets' => []],
        ];
        $details = [
            'versions_applied' => [],
            'summary' => 'Se instaló SQL Server Management Studio y se corrigió el problema relacionado con PowerShell 2.0. Se exportaron los catálogos y productos de la base de datos y se documentaron mediante fotografías los servicios utilizados en iFacture.',
        ];

        $report = [
            'id' => $reportId,
            'folio' => self::FOLIO,
            'issued_at' => $issuedAt,
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'service_recipient' => 'Distribuidora Mayorista de Cristales S.A. de C.V. / DIMACRISUR',
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
            'report_snapshot' => json_encode([...$report, 'supports' => $supports, 'details' => $details], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
