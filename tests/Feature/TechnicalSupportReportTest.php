<?php

namespace Tests\Feature;

use App\Models\TechnicalSupportReport;
use App\Models\User;
use App\Services\Support\TechnicalSupportReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalSupportReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_section_requires_authentication(): void
    {
        $this->get(route('soporte.reportes-tecnicos'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_report_section_and_pdf(): void
    {
        $user = User::factory()->create();
        $report = TechnicalSupportReport::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('soporte.reportes-tecnicos'))
            ->assertOk()
            ->assertSee('Reportes de soporte')
            ->assertSee('Servicios incluidos')
            ->assertDontSee('Generar reporte de soporte')
            ->assertDontSee('Selecciona servicios e indica cuántas veces se realizó cada uno.')
            ->assertDontSee('Historial de reportes');

        $this->actingAs($user)
            ->get(route('soporte.reportes-tecnicos.history'))
            ->assertOk()
            ->assertSee('Historial de reportes')
            ->assertSee('ST-20261023-0007')
            ->assertSee('Respaldo local de correo, configuración, contactos y calendario')
            ->assertSee('Departamento o cliente: Auditoría')
            ->assertSee('ST-20261022-0005')
            ->assertSee('Departamento o cliente: Contabilidad')
            ->assertDontSee('Soporte técnico general');

        $this->actingAs($user)
            ->get(route('soporte.reportes-tecnicos.pdf', $report))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_generate_a_report_from_selected_supports(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'service_recipient' => 'Cliente Externo Ejemplo',
            'supports' => ['contpaqi_contabilidad_server_update', 'office_install'],
            'support_quantities' => [
                'contpaqi_contabilidad_server_update' => 4,
                'office_install' => 2,
            ],
            'support_targets' => [
                'contpaqi_contabilidad_server_update' => "Equipo servidor\nTerminal de pruebas",
                'office_install' => 'María Sinaí Campos Caamal',
            ],
            'versions_applied' => "18.3.1 - Solución fuera de versión\n19.4.0 - Versión final",
        ]);

        $report = TechnicalSupportReport::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('soporte.reportes-tecnicos.history', ['created' => $report->folio]));
        $this->assertSame($user->id, $report->created_by);
        $this->assertSame('Cliente Externo Ejemplo', $report->service_recipient);
        $this->assertSame('Actualización de servidor de CONTPAQi Contabilidad', $report->supports[0]['title']);
        $this->assertSame(4, $report->supports[0]['quantity']);
        $this->assertSame(['Equipo servidor', 'Terminal de pruebas'], $report->supports[0]['targets']);
        $this->assertSame('Instalación de Microsoft Office', $report->supports[1]['title']);
        $this->assertSame(2, $report->supports[1]['quantity']);
        $this->assertSame(['María Sinaí Campos Caamal'], $report->supports[1]['targets']);
        $this->assertSame(['18.3.1 - Solución fuera de versión', '19.4.0 - Versión final'], $report->details['versions_applied']);
        $this->assertStringStartsWith('ST-', $report->folio);
        $this->assertSame(
            $report->folio.'-act-srv-cont-inst-office.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );

        $this->actingAs($user)
            ->get(route('soporte.reportes-tecnicos.pdf', $report))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_versions_are_only_saved_for_server_updates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'service_recipient' => 'Nóminas',
            'supports' => ['contpaqi_nominas_update'],
            'support_quantities' => ['contpaqi_nominas_update' => 2],
            'versions_applied' => "19.0.1\n19.4.0",
        ])->assertRedirect();

        $normalUpdate = TechnicalSupportReport::query()->latest('id')->firstOrFail();
        $this->assertSame([], $normalUpdate->details['versions_applied']);

        $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'service_recipient' => 'Nóminas',
            'supports' => ['contpaqi_nominas_server_update'],
            'support_quantities' => ['contpaqi_nominas_server_update' => 1],
        ])->assertSessionHasErrors('versions_applied');
    }

    public function test_microsoft_365_and_claude_services_can_be_reported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'service_recipient' => 'Contabilidad',
            'supports' => [
                'microsoft_365_refresh',
                'claude_addin_setup',
                'microsoft_store_compatibility_update',
            ],
            'support_quantities' => [
                'microsoft_365_refresh' => 2,
                'claude_addin_setup' => 2,
                'microsoft_store_compatibility_update' => 1,
            ],
        ])->assertRedirect();

        $report = TechnicalSupportReport::query()->latest('id')->firstOrFail();

        $this->assertSame(2, $report->supports[0]['quantity']);
        $this->assertSame('Configuración del complemento de Claude en Microsoft 365', $report->supports[1]['title']);
        $this->assertSame(
            $report->folio.'-m365-claude-act-store.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }

    public function test_sql_and_intern_email_services_can_be_reported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'service_recipient' => 'Auditoría',
            'supports' => [
                'sql_server_2012_restore',
                'sql_terminal_access_setup',
                'intern_email_creation',
                'email_setup',
                'email_web_access_limits',
            ],
            'support_quantities' => [
                'sql_server_2012_restore' => 1,
                'sql_terminal_access_setup' => 1,
                'intern_email_creation' => 2,
                'email_setup' => 2,
                'email_web_access_limits' => 2,
            ],
        ])->assertRedirect();

        $report = TechnicalSupportReport::query()->latest('id')->firstOrFail();

        $this->assertSame('Restauración de base de datos empresarial en SQL Server 2012', $report->supports[0]['title']);
        $this->assertSame(2, $report->supports[2]['quantity']);
        $this->assertSame(
            $report->folio.'-restaurar-sql2012-acceso-sql-correos-becarios-config-correo-limites-correo.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }

    public function test_computer_printer_connection_can_be_reported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('soporte.reportes-tecnicos.store'), [
                'service_recipient' => 'Jurídico',
                'supports' => ['computer_printer_connection'],
                'support_quantities' => ['computer_printer_connection' => 1],
            ])
            ->assertRedirect();

        $report = TechnicalSupportReport::query()->latest('id')->firstOrFail();

        $this->assertSame('computer_printer_connection', $report->supports[0]['key']);
        $this->assertSame('Jurídico', $report->service_recipient);
        $this->assertSame('Conexión de computadora a impresora', $report->supports[0]['title']);
        $this->assertStringContainsString(
            'conexion-impresora',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }

    public function test_audit_email_backup_and_migration_services_can_be_reported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('soporte.reportes-tecnicos.store'), [
                'service_recipient' => 'Auditoría',
                'supports' => [
                    'email_local_backup',
                    'email_data_migration',
                    'email_pop_to_imap',
                    'windows_startup_ini_removal',
                ],
                'support_quantities' => [
                    'email_local_backup' => 1,
                    'email_data_migration' => 1,
                    'email_pop_to_imap' => 1,
                    'windows_startup_ini_removal' => 1,
                ],
            ])
            ->assertRedirect();

        $report = TechnicalSupportReport::query()->latest('id')->firstOrFail();

        $this->assertSame('Auditoría', $report->service_recipient);
        $this->assertSame('email_local_backup', $report->supports[0]['key']);
        $this->assertSame('Cambio de cuenta de correo de POP a IMAP y sincronización inicial', $report->supports[2]['title']);
        $this->assertSame(
            $report->folio.'-respaldo-correo-migracion-correo-pop-imap-quitar-ini.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }

    public function test_payroll_update_report_lists_people_by_service(): void
    {
        $report = TechnicalSupportReport::query()
            ->where('folio', 'ST-20261022-0002')
            ->firstOrFail();

        $this->assertSame(3, $report->supports[1]['quantity']);
        $this->assertSame([
            'Martín Geovany Polanco Pineda',
            'Axel Bladimir Dzul Pat',
            'Carlos Jesús Campos Yah',
        ], $report->supports[1]['targets']);
        $this->assertSame(4, $report->supports[2]['quantity']);
        $this->assertSame('María Sinaí Campos Caamal', $report->supports[2]['targets'][0]);
        $this->assertSame('linked_spreadsheets_correction', $report->supports[3]['key']);
        $this->assertSame('office_license_activation', $report->supports[4]['key']);
        $this->assertSame(
            $report->folio.'-act-srv-nom-act-nom-act-cont-hojas-vinculadas-act-office.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }

    public function test_microsoft_report_lists_people_by_service(): void
    {
        $report = TechnicalSupportReport::query()
            ->where('folio', 'ST-20261022-0003')
            ->firstOrFail();

        $this->assertSame([
            'Lizet Nayeli Pool Chin',
            'Anastacio May Ku',
        ], $report->supports[0]['targets']);
        $this->assertSame([
            'Lizet Nayeli Pool Chin',
            'Anastacio May Ku',
        ], $report->supports[1]['targets']);
        $this->assertSame(['Josué Ricardo Puc Pech'], $report->supports[2]['targets']);
        $this->assertSame(['Josué Ricardo Puc Pech'], $report->supports[3]['targets']);
        $this->assertSame(['Anastacio May Ku'], $report->supports[4]['targets']);
    }

    public function test_existing_reports_list_the_people_who_received_each_service(): void
    {
        $expectedTargets = [
            'ST-20261022-0004' => ['Noh Chan Claudia Alejandra'],
            'ST-20261022-0005' => ['Perla Naomy Rivero Couoh', 'Josue Ricardo Puc Pech'],
            'ST-20261023-0006' => ['Victor Gabriel Tapia Hernandez'],
            'ST-20261023-0007' => ['Sanchez Coba Caridad Iliana'],
        ];

        foreach ($expectedTargets as $folio => $targets) {
            $report = TechnicalSupportReport::query()->where('folio', $folio)->firstOrFail();

            foreach ($report->supports as $support) {
                $this->assertSame($targets, $support['targets']);
            }
        }
    }

    public function test_accounting_relocation_and_ssd_reports_are_available(): void
    {
        $relocation = TechnicalSupportReport::query()
            ->where('folio', 'ST-20261028-0008')
            ->firstOrFail();
        $ssdMigration = TechnicalSupportReport::query()
            ->where('folio', 'ST-20261028-0009')
            ->firstOrFail();

        $this->assertSame('Contabilidad', $relocation->service_recipient);
        $this->assertSame('computer_relocation', $relocation->supports[0]['key']);
        $this->assertSame('contpaqi_contabilidad_install', $relocation->supports[1]['key']);
        $this->assertSame('contpaqi_nominas_install', $relocation->supports[2]['key']);
        $this->assertSame('windows_contpaqi_compatibility_update', $relocation->supports[3]['key']);
        $this->assertSame(['Carlos Rafael De Jesús Diaz Ruiz'], $relocation->supports[0]['targets']);
        $this->assertSame(
            'ST-20261028-0008-reubicar-equipo-inst-cont-inst-nom-act-windows-contpaqi.pdf',
            app(TechnicalSupportReportPdfService::class)->data($relocation)['filename'],
        );

        $this->assertSame('Contabilidad', $ssdMigration->service_recipient);
        $this->assertSame('ssd_full_backup', $ssdMigration->supports[0]['key']);
        $this->assertSame('ssd_corrected_system_migration', $ssdMigration->supports[1]['key']);
        $this->assertSame('ssd_diagnosis_repair', $ssdMigration->supports[2]['key']);
        $this->assertSame(['Carlos Rafael De Jesús Diaz Ruiz'], $ssdMigration->supports[2]['targets']);
        $this->assertSame(
            'ST-20261028-0009-respaldo-ssd-migracion-ssd-reparar-ssd.pdf',
            app(TechnicalSupportReportPdfService::class)->data($ssdMigration)['filename'],
        );
    }

    public function test_accounting_email_setup_report_is_available(): void
    {
        $report = TechnicalSupportReport::query()
            ->where('folio', 'ST-20261028-0010')
            ->firstOrFail();

        $this->assertSame('Contabilidad', $report->service_recipient);
        $this->assertSame('email_installation', $report->supports[0]['key']);
        $this->assertSame('Instalación de correo electrónico', $report->supports[0]['title']);
        $this->assertSame(1, $report->supports[0]['quantity']);
        $this->assertSame(['Carlos Rafael De Jesús Diaz Ruiz'], $report->supports[0]['targets']);
        $this->assertSame(
            'ST-20261028-0010-inst-correo.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }

    public function test_audit_microsoft_365_report_is_available(): void
    {
        $report = TechnicalSupportReport::query()
            ->where('folio', 'ST-20261028-0011')
            ->firstOrFail();

        $this->assertSame('Auditoría', $report->service_recipient);
        $this->assertSame('microsoft_365_install', $report->supports[0]['key']);
        $this->assertSame('office_license_activation', $report->supports[1]['key']);
        $this->assertSame('claude_addin_activation', $report->supports[2]['key']);

        foreach ($report->supports as $support) {
            $this->assertSame(['Noh Chan Claudia Alejandra'], $support['targets']);
        }

        $this->assertSame(
            'ST-20261028-0011-inst-m365-act-office-act-claude.pdf',
            app(TechnicalSupportReportPdfService::class)->data($report)['filename'],
        );
    }
}
