<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const JESUS_FOLIO = 'ST-20261104-0019';

    private const INVENTORY_FOLIO = 'ST-20261104-0022';

    public function up(): void
    {
        DB::transaction(function (): void {
            $jesus = DB::table('technical_support_reports')->where('folio', self::JESUS_FOLIO)->first();

            if ($jesus) {
                $supports = [[
                    'key' => 'antivirus_web_access',
                    'title' => 'Corrección de acceso lento a sitios web por configuración del antivirus',
                    'quantity' => 1,
                    'targets' => ['Jesus Ricardo Pech Ku'],
                ]];
                $details = json_decode($jesus->details, true) ?: [];
                $details['summary'] = 'Se corrigió la configuración del antivirus que provocaba retrasos al validar y cargar los sitios web.';

                DB::table('technical_support_reports')->where('id', $jesus->id)->update([
                    'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);

            }

            if (DB::table('technical_support_reports')->where('folio', self::INVENTORY_FOLIO)->exists()) {
                return;
            }

            $reportId = max(22, ((int) DB::table('technical_support_reports')->max('id')) + 1);
            $issuedAt = '2026-11-04 17:45:00';
            $supports = [[
                'key' => 'hardware_inventory_for_quote',
                'title' => 'Levantamiento de características del equipo para cotización',
                'quantity' => 1,
                'targets' => ['Ángel Moo'],
            ]];
            $details = [
                'report_kind' => 'hardware_inventory',
                'versions_applied' => [],
                'hardware_specs' => [
                    ['label' => 'Usuario del equipo', 'value' => 'Ángel Moo'],
                    ['label' => 'Fabricante y modelo', 'value' => 'BIOSTAR Group B450MH'],
                    ['label' => 'Placa base', 'value' => 'BIOSTAR Group; el modelo no apareció en la salida proporcionada'],
                    ['label' => 'Procesador', 'value' => 'AMD Ryzen 5 5500; 6 núcleos y 12 procesadores lógicos'],
                    ['label' => 'Memoria RAM', 'value' => '8 GB instalados; A-DATA Technology; velocidad reportada de 3200 MHz'],
                    ['label' => 'Tarjeta gráfica', 'value' => 'NVIDIA GeForce GT 710; 1 GB de memoria de video; controlador 30.0.14.7514'],
                    ['label' => 'Almacenamiento', 'value' => 'SSD ADATA SU630 de 480 GB (447.13 GiB reportados por Windows)'],
                    ['label' => 'Monitor', 'value' => 'Quaroni MQ19-02; 19.5 pulgadas; resolución 1366 × 768 píxeles (dato proporcionado)'],
                ],
                'summary' => 'La información corresponde a las características actuales facilitadas para elaborar una cotización. No se verificaron en sitio componentes adicionales ni se incluye precio o recomendación de compra.',
            ];

            $report = [
                'id' => $reportId,
                'folio' => self::INVENTORY_FOLIO,
                'issued_at' => $issuedAt,
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => 'Ángel Moo',
                'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => $issuedAt,
                'updated_at' => $issuedAt,
            ];

            DB::table('technical_support_reports')->insert($report);
            DB::table('technical_support_report_movements')->insert([
                'technical_support_report_id' => $reportId,
                'folio' => self::INVENTORY_FOLIO,
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
        });
    }

    public function down(): void
    {
        DB::table('technical_support_report_movements')->where('folio', self::INVENTORY_FOLIO)->delete();
        DB::table('technical_support_reports')->where('folio', self::INVENTORY_FOLIO)->delete();

        $jesus = DB::table('technical_support_reports')->where('folio', self::JESUS_FOLIO)->first();
        if ($jesus) {
            $supports = array_map(static fn (array $service): array => [
                'key' => $service[0],
                'title' => $service[1],
                'quantity' => 1,
                'targets' => ['Jesus Ricardo Pech Ku'],
            ], [
                ['antivirus_web_access', 'Corrección de acceso lento a sitios web por configuración del antivirus'],
                ['contpaqi_timezone', 'Corrección de zona horaria para CONTPAQi Nóminas'],
                ['timezone_services', 'Corrección del cambio de zona horaria al iniciar o apagar el equipo'],
            ]);
            $details = json_decode($jesus->details, true) ?: [];
            $details['summary'] = 'Se atendieron las mismas incidencias de acceso lento a sitios web y de zona horaria: ajuste del antivirus, corrección de la región para CONTPAQi Nóminas y revisión de los servicios de Windows que cambiaban la hora al iniciar o apagar el equipo.';

            DB::table('technical_support_reports')->where('id', $jesus->id)->update([
                'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
};
