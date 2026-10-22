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
            ->assertSee('Generar reporte de soporte')
            ->assertDontSee('Historial de reportes');

        $this->actingAs($user)
            ->get(route('soporte.reportes-tecnicos.history'))
            ->assertOk()
            ->assertSee('Historial de reportes')
            ->assertSee('ST-20261022-0001');

        $this->actingAs($user)
            ->get(route('soporte.reportes-tecnicos.pdf', $report))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_generate_a_report_from_selected_supports(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'supports' => ['contpaqi_contabilidad_server_update', 'office_install'],
            'support_quantities' => [
                'contpaqi_contabilidad_server_update' => 4,
                'office_install' => 2,
            ],
            'versions_applied' => "18.3.1 - Solución fuera de versión\n19.4.0 - Versión final",
        ]);

        $report = TechnicalSupportReport::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('soporte.reportes-tecnicos.history', ['created' => $report->folio]));
        $this->assertSame($user->id, $report->created_by);
        $this->assertSame('Actualización de servidor de CONTPAQi Contabilidad', $report->supports[0]['title']);
        $this->assertSame(4, $report->supports[0]['quantity']);
        $this->assertSame('Instalación de Microsoft Office', $report->supports[1]['title']);
        $this->assertSame(2, $report->supports[1]['quantity']);
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
            'supports' => ['contpaqi_nominas_update'],
            'support_quantities' => ['contpaqi_nominas_update' => 2],
            'versions_applied' => "19.0.1\n19.4.0",
        ])->assertRedirect();

        $normalUpdate = TechnicalSupportReport::query()->latest('id')->firstOrFail();
        $this->assertSame([], $normalUpdate->details['versions_applied']);

        $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
            'supports' => ['contpaqi_nominas_server_update'],
            'support_quantities' => ['contpaqi_nominas_server_update' => 1],
        ])->assertSessionHasErrors('versions_applied');
    }

    public function test_microsoft_365_and_claude_services_can_be_reported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('soporte.reportes-tecnicos.store'), [
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
}
