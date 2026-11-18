<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPORTS = [
        'ST-20261118-0031' => [
            'recipient' => 'Contabilidad Tulum',
            'supports' => [
                ['key' => 'contpaqi_contabilidad_reinstall', 'title' => 'Reinstalación de CONTPAQi Contabilidad', 'quantity' => 1, 'targets' => ['Manuel Agustín Kauil Canche']],
                ['key' => 'contpaqi_nominas_reinstall', 'title' => 'Reinstalación de CONTPAQi Nóminas', 'quantity' => 1, 'targets' => ['Manuel Agustín Kauil Canche']],
                ['key' => 'excel_addin_activation', 'title' => 'Activación de complemento de Excel', 'quantity' => 1, 'targets' => ['Manuel Agustín Kauil Canche']],
                ['key' => 'linked_spreadsheets_correction', 'title' => 'Corrección de hojas de cálculo vinculadas', 'quantity' => 1, 'targets' => ['Manuel Agustín Kauil Canche']],
            ],
            'summary' => 'Se realizó la reinstalación de CONTPAQi Contabilidad y de CONTPAQi Nóminas en el equipo de Manuel Agustín Kauil Canche, del área de Contabilidad Tulum. Además, se activó el complemento de Excel necesario para reparar las hojas vinculadas del libro de Costo Trabajo y se comprobó su funcionamiento.',
        ],
        'ST-20261118-0032' => [
            'recipient' => 'Contabilidad Mérida',
            'supports' => [
                ['key' => 'linked_spreadsheets_correction', 'title' => 'Corrección de hojas de cálculo vinculadas', 'quantity' => 1, 'targets' => ['Josué Ricardo Puc Pech']],
                ['key' => 'excel_addin_activation', 'title' => 'Activación de complemento de Excel', 'quantity' => 1, 'targets' => ['Josué Ricardo Puc Pech']],
            ],
            'summary' => 'Se repararon las hojas de cálculo vinculadas de Excel de Josué Ricardo Puc Pech, del área de Contabilidad Mérida, que impedían el funcionamiento de un complemento. Con la reparación se activó el complemento que presentaba fallas y se comprobó que quedara funcionando correctamente.',
        ],
    ];

    public function up(): void
    {
        foreach (self::REPORTS as $folio => $data) {
            if (DB::table('technical_support_reports')->where('folio', $folio)->exists()) {
                continue;
            }

            $issuedAt = now('America/Mexico_City')->utc()->format('Y-m-d H:i:s');
            $reportId = max(31, ((int) DB::table('technical_support_reports')->max('id')) + 1);
            $details = ['versions_applied' => [], 'omit_closing_paragraph' => true, 'summary' => $data['summary']];

            $report = [
                'id' => $reportId,
                'folio' => $folio,
                'issued_at' => $issuedAt,
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => $data['recipient'],
                'supports' => json_encode($data['supports'], JSON_UNESCAPED_UNICODE),
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ];

            DB::table('technical_support_reports')->insert($report);
            DB::table('technical_support_report_movements')->insert([
                'technical_support_report_id' => $reportId,
                'folio' => $folio,
                'action' => 'created',
                'user_id' => null,
                'user_name' => null,
                'user_email' => null,
                'report_snapshot' => json_encode([...$report, 'supports' => $data['supports'], 'details' => $details], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'occurred_at' => $issuedAt,
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ]);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::REPORTS) as $folio) {
            DB::table('technical_support_report_movements')->where('folio', $folio)->delete();
            DB::table('technical_support_reports')->where('folio', $folio)->delete();
        }
    }
};
