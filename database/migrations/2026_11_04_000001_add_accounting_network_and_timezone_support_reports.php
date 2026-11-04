<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPORTS = [
        [
            'folio' => 'ST-20261104-0018',
            'issued_at' => '2026-11-04 17:29:00',
            'person' => 'Josue Ricardo Puc Pech',
            'email' => 'josucpuc292@gmail.com',
            'services' => [
                ['antivirus_web_access', 'Corrección de acceso lento a sitios web por configuración del antivirus'],
                ['contpaqi_timezone', 'Corrección de zona horaria para CONTPAQi Nóminas'],
                ['timezone_services', 'Corrección del cambio de zona horaria al iniciar o apagar el equipo'],
            ],
            'summary' => 'Las páginas tardaban en validar por una configuración del antivirus. Se ajustó la zona horaria requerida por CONTPAQi Nóminas y se revisó la configuración de los servicios de Windows que provocaba el cambio de hora al iniciar o apagar el equipo.',
        ],
        [
            'folio' => 'ST-20261104-0019',
            'issued_at' => '2026-11-04 17:30:00',
            'person' => 'Jesus Ricardo Pech Ku',
            'email' => 'bc_jesus_pech@gonzalezalonzo.net',
            'services' => [
                ['antivirus_web_access', 'Corrección de acceso lento a sitios web por configuración del antivirus'],
                ['contpaqi_timezone', 'Corrección de zona horaria para CONTPAQi Nóminas'],
                ['timezone_services', 'Corrección del cambio de zona horaria al iniciar o apagar el equipo'],
            ],
            'summary' => 'Se atendieron las mismas incidencias de acceso lento a sitios web y de zona horaria: ajuste del antivirus, corrección de la región para CONTPAQi Nóminas y revisión de los servicios de Windows que cambiaban la hora al iniciar o apagar el equipo.',
        ],
        [
            'folio' => 'ST-20261104-0020',
            'issued_at' => '2026-11-04 17:31:00',
            'person' => 'Alan Alberto Chan Caballero',
            'email' => 'alan.chan@gonzalezalonzo.net',
            'services' => [
                ['network_cable_diagnosis', 'Diagnóstico de falla en cable de red'],
                ['network_connector_retermination', 'Reponchado de conector de red'],
            ],
            'summary' => 'Se identificó el cable de red del equipo y se detectó falso contacto en una de sus puntas. Se reponchó el conector afectado para corregir la falla de conexión a Internet.',
        ],
        [
            'folio' => 'ST-20261104-0021',
            'issued_at' => '2026-11-04 17:32:00',
            'person' => 'Pedro Pablo Tuyub Albornoz',
            'email' => null,
            'services' => [
                ['network_cable_node_repair', 'Cambio de conexión de red, reponchado y pruebas del nodo'],
                ['network_cable_route_diagnosis', 'Diagnóstico de falla en el recorrido del cable de red'],
            ],
            'summary' => 'Se reponcharon y probaron los conectores y se cambió el nodo de red. Las pruebas indican una posible falla en algún tramo del recorrido del cable; queda pendiente localizar el punto exacto para una reparación definitiva.',
        ],
    ];

    public function up(): void
    {
        $nextId = max(18, ((int) DB::table('technical_support_reports')->max('id')) + 1);

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
                'service_recipient' => 'Contabilidad',
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
