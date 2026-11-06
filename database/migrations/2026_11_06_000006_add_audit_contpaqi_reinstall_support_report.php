<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261106-0025';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = now()->format('Y-m-d H:i:00');
        $person = 'GALAZ CASTAÑEDA SANTOS GERARDO';
        $reportId = max(25, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = [[
            'key' => 'contpaqi_reinstall_components',
            'title' => 'Reinstalación de CONTPAQi y sus complementos',
            'quantity' => 1,
            'targets' => [$person],
        ]];
        $details = [
            'versions_applied' => [],
            'contact_email' => null,
            'summary' => 'Se realizó la reinstalación de CONTPAQi junto con sus complementos en el equipo de GALAZ CASTAÑEDA SANTOS GERARDO, del área de Auditoría.',
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
