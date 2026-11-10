<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261110-0027';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = now('America/Mexico_City')->utc()->format('Y-m-d H:i:s');
        $reportId = max(27, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = [[
            'key' => 'ifacture_server_name_resolution',
            'title' => 'Configuración de acceso a iFacture mediante el nombre del servidor',
            'quantity' => 1,
            'targets' => [],
        ]];
        $details = [
            'versions_applied' => [],
            'summary' => 'La terminal no reconocía el nombre del servidor de iFacture, aunque sí permitía acceder mediante su dirección IP. Se configuró y comprobó el acceso usando el nombre de red del servidor, de modo que la terminal pueda localizarlo sin depender de una dirección IP fija.',
        ];

        $report = [
            'id' => $reportId,
            'folio' => self::FOLIO,
            'issued_at' => $issuedAt,
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'service_recipient' => 'Frigo Insumos S.A. de C.V.',
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
