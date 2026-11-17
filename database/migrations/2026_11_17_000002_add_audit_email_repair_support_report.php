<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261117-0030';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = now('America/Mexico_City')->utc()->format('Y-m-d H:i:s');
        $reportId = max(30, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = [[
            'key' => 'email_account_repair',
            'title' => 'Reparación de cuenta de correo electrónico',
            'quantity' => 2,
            'targets' => ['Noh Chan Claudia Alejandra'],
        ]];
        $details = [
            'versions_applied' => [],
            'omit_closing_paragraph' => true,
            'summary' => 'Se repararon dos cuentas de correo electrónico que presentaban fallas y se restableció su funcionamiento.',
        ];

        $report = [
            'id' => $reportId,
            'folio' => self::FOLIO,
            'issued_at' => $issuedAt,
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'service_recipient' => 'Auditoría',
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
