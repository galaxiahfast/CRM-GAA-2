<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('technical_support_reports')
            ->where('folio', 'ST-20261022-0001')
            ->delete();

        foreach ($this->reports() as $report) {
            DB::table('technical_support_reports')->updateOrInsert(
                ['folio' => $report['folio']],
                $report,
            );
        }
    }

    public function down(): void
    {
        DB::table('technical_support_reports')
            ->whereIn('folio', array_column($this->reports(), 'folio'))
            ->delete();

        DB::table('technical_support_reports')->updateOrInsert(
            ['folio' => 'ST-20261022-0001'],
            [
                'issued_at' => '2026-10-22 17:51:46',
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'supports' => json_encode([
                    ['key' => 'initial_support', 'title' => 'Soporte técnico general', 'quantity' => 1],
                ], JSON_UNESCAPED_UNICODE),
                'details' => json_encode(['versions_applied' => []], JSON_UNESCAPED_UNICODE),
                'created_by' => null,
                'created_at' => '2026-10-22 17:51:46',
                'updated_at' => '2026-10-22 17:51:46',
            ],
        );
    }

    private function reports(): array
    {
        return [
            $this->report(
                'ST-20261022-0002',
                '2026-10-22 23:07:58',
                [
                    ['key' => 'contpaqi_nominas_server_update', 'title' => 'Actualización de servidor de CONTPAQi Nóminas', 'quantity' => 1],
                    ['key' => 'contpaqi_nominas_update', 'title' => 'Actualización de terminales de CONTPAQi Nóminas', 'quantity' => 5],
                ],
                [
                    '18.2.2 - Versión base',
                    '18.3.1 - Solución fuera de versión',
                    '18.3.2 - Solución fuera de versión',
                    '19.0.1 - Actualización principal',
                    '19.0.2 - Solución fuera de versión',
                    '19.1.1 - Actualización principal',
                    '19.2.1 - Actualización principal',
                    '19.2.2 - Solución fuera de versión',
                    '19.3.5 - Actualización principal',
                    '19.4.0 - Solución fuera de versión y versión homologada',
                ],
            ),
            $this->report(
                'ST-20261022-0003',
                '2026-10-22 23:20:20',
                [
                    ['key' => 'microsoft_365_refresh', 'title' => 'Desinstalación e instalación actual de Microsoft 365 en Contabilidad', 'quantity' => 2],
                    ['key' => 'claude_addin_setup', 'title' => 'Configuración del complemento de Claude en Microsoft 365', 'quantity' => 2],
                    ['key' => 'contpaqi_contabilidad_reinstall', 'title' => 'Reinstalación de CONTPAQi Contabilidad', 'quantity' => 1],
                    ['key' => 'contpaqi_nominas_reinstall', 'title' => 'Reinstalación de CONTPAQi Nóminas', 'quantity' => 1],
                    ['key' => 'microsoft_store_compatibility_update', 'title' => 'Actualización de Windows para compatibilidad con Microsoft Store', 'quantity' => 1],
                ],
            ),
            $this->report(
                'ST-20261022-0004',
                '2026-10-22 23:26:30',
                [
                    ['key' => 'sql_server_2012_restore', 'title' => 'Restauración de base de datos empresarial en SQL Server 2012', 'quantity' => 1],
                    ['key' => 'sql_terminal_access_setup', 'title' => 'Configuración de acceso SQL desde terminales', 'quantity' => 1],
                ],
            ),
            $this->report(
                'ST-20261022-0005',
                '2026-10-22 23:27:30',
                [
                    ['key' => 'intern_email_creation', 'title' => 'Creación de cuentas de correo para becarios', 'quantity' => 2],
                    ['key' => 'email_setup', 'title' => 'Configuración de correo electrónico en equipos', 'quantity' => 2],
                    ['key' => 'email_web_access_limits', 'title' => 'Configuración de accesos web y límites de correo', 'quantity' => 2],
                ],
            ),
        ];
    }

    private function report(string $folio, string $issuedAt, array $supports, array $versions = []): array
    {
        return [
            'folio' => $folio,
            'issued_at' => $issuedAt,
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'supports' => json_encode($supports, JSON_UNESCAPED_UNICODE),
            'details' => json_encode(['versions_applied' => $versions], JSON_UNESCAPED_UNICODE),
            'created_by' => null,
            'created_at' => $issuedAt,
            'updated_at' => $issuedAt,
        ];
    }
};
