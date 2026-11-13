<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FOLIO = 'ST-20261113-0029';

    public function up(): void
    {
        if (DB::table('technical_support_reports')->where('folio', self::FOLIO)->exists()) {
            return;
        }

        $issuedAt = now('America/Mexico_City')->utc()->format('Y-m-d H:i:s');
        $reportId = max(29, ((int) DB::table('technical_support_reports')->max('id')) + 1);
        $supports = [
            ['key' => 'network_startup_diagnosis', 'title' => 'Diagnóstico de lentitud de red al iniciar Windows', 'quantity' => 1, 'targets' => ['Jair Guadalupe Herrera Carrillo']],
            ['key' => 'network_adapter_power_settings', 'title' => 'Ajuste de energía del adaptador Ethernet', 'quantity' => 1, 'targets' => ['Jair Guadalupe Herrera Carrillo']],
            ['key' => 'windows_fast_startup_disable', 'title' => 'Desactivación del inicio rápido de Windows', 'quantity' => 1, 'targets' => ['Jair Guadalupe Herrera Carrillo']],
            ['key' => 'network_stack_reset', 'title' => 'Restablecimiento de red y limpieza de caché DNS', 'quantity' => 1, 'targets' => ['Jair Guadalupe Herrera Carrillo']],
        ];
        $details = [
            'versions_applied' => [],
            'omit_closing_paragraph' => true,
            'summary' => 'Se revisaron los eventos de rendimiento de arranque y las aplicaciones de inicio por la lentitud temporal de internet. Se desactivó el ahorro de energía del adaptador Realtek PCIe GbE y el inicio rápido de Windows. Se restablecieron la pila IP y Winsock y se limpió la caché DNS. Después de reiniciar el equipo, se comprobó que la conexión funciona correctamente.',
        ];

        $report = [
            'id' => $reportId,
            'folio' => self::FOLIO,
            'issued_at' => $issuedAt,
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'service_recipient' => 'Contabilidad',
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
