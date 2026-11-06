<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPORTS = [
        [
            'folio' => 'ST-20261106-0023',
            'issued_at' => '2026-11-06 17:25:00',
            'person' => 'Manuel Agustin Kauil Canche',
            'email' => null,
            'recipient' => 'Tulum Contabilidad',
            'services' => [
                ['command_performance_correction', 'Corrección de comandos para mejorar el rendimiento del equipo'],
                ['excel_repair', 'Reparación de Microsoft Excel'],
            ],
            'summary' => 'Se aplicó una corrección de comandos en el equipo para eliminar la lentitud que presentaba y se realizó la reparación de Microsoft Excel.',
        ],
    ];

    public function up(): void
    {
        $nextId = max(23, ((int) DB::table('technical_support_reports')->max('id')) + 1);

        foreach (self::REPORTS as $item) {
            if (DB::table('technical_support_reports')->where('folio', $item['folio'])->exists()) {
                continue;
            }

            $supports = array_map(static fn (array $service): array => [
                'key' => $service[0],
                'title' => $service[1],
                'quantity' => 1,
                'targets' => [$item['person']],
            ], $item['services']);

            $details = [
                'versions_applied' => [],
                'contact_email' => $item['email'],
                'summary' => $item['summary'],
            ];

            $report = [
                'id' => $nextId++,
                'folio' => $item['folio'],
                'issued_at' => $item['issued_at'],
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => $item['recipient'],
                'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => $item['issued_at'],
                'updated_at' => $item['issued_at'],
            ];

            DB::table('technical_support_reports')->insert($report);
            $reportId = $report['id'];

            DB::table('technical_support_report_movements')->insert([
                'technical_support_report_id' => $reportId,
                'folio' => $item['folio'],
                'action' => 'created',
                'user_id' => null,
                'user_name' => null,
                'user_email' => null,
                'report_snapshot' => json_encode([
                    'id' => $reportId,
                    ...$report,
                    'supports' => $supports,
                    'details' => $details,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'occurred_at' => $item['issued_at'],
                'created_at' => $item['issued_at'],
                'updated_at' => $item['issued_at'],
            ]);
        }
    }

    public function down(): void
    {
        $folios = array_column(self::REPORTS, 'folio');
        DB::table('technical_support_report_movements')->whereIn('folio', $folios)->delete();
        DB::table('technical_support_reports')->whereIn('folio', $folios)->delete();
    }
};
