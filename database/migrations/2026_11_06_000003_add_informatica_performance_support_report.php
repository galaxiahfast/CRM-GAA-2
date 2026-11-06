<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261106-0024';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = '2026-11-06 18:27:00';
        $person = 'Julian Emiliano Ortiz Rivero';
        $reportId = max(24, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = array_map(static fn (array $service): array => [
            'key' => $service[0],
            'title' => $service[1],
            'quantity' => 1,
            'targets' => [$person],
        ], [
            ['computer_performance_diagnosis', 'Diagnóstico de lentitud y consumo de recursos del equipo'],
            ['unneeded_software_removal', 'Desinstalación de aplicaciones y componentes no requeridos'],
            ['startup_residual_cleanup', 'Limpieza de componentes residuales y entradas de inicio automático'],
        ]);
        $details = [
            'versions_applied' => [],
            'omit_closing_paragraph' => true,
            'summary' => 'Antes de la intervención, el equipo tenía 8 GB de RAM, cerca de 300 MB libres y varias aplicaciones abiertas o configuradas para iniciar con Windows. Según el resumen de atención se retiraron Ollama, sus modelos y WPS Office; el registro confirma la desinstalación de Adobe y Autodesk y la limpieza de sus residuos e inicios automáticos. El espacio libre reportado pasó de 234.2 GB a 251.2 GB, unos 17 GB recuperados. Queda pendiente reiniciar y comprobar el rendimiento; se recomendó ampliar a 16 GB de RAM.',
        ];

        $report = [
            'id' => $reportId,
            'folio' => self::FOLIO,
            'issued_at' => $issuedAt,
            'responsible' => $person,
            'service_recipient' => 'Informática',
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
