<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\Admin\InformeGeneralHoras;
use App\Livewire\TimeControl\MyProductivity;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\TimeReportService;
use App\Services\TimeControl\TimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TimeReportExportTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{aux: User, admin: User, customer: Customer, sub: SubService} */
    private function seedWithEntry(): array
    {
        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);
        $position = JobPosition::create(['name' => 'Auxiliar de Nómina']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $aux = User::create([
            'name' => 'Aux', 'last_name' => 'Uno', 'email' => 'aux@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id,
        ]);
        UserOrganizationalProfile::create([
            'user_id' => $aux->id, 'job_position_id' => $position->id,
            'physical_area_id' => $area->id, 'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin', 'last_name' => 'Root', 'email' => 'admin@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);

        $customer = Customer::create(['name' => 'Cliente A', 'rfc' => 'XAXX010101000', 'created_by' => $aux->id]);
        $service = Service::create(['service' => 'Fiscal']);
        $sub = new SubService(['sub_service' => 'Cálculo de impuestos', 'service_id' => $service->id]);
        $sub->unique_key = 'CALC-IMP';
        $sub->save();

        // Una actividad de 1h registrada hoy.
        $timer = app(TimerService::class);
        Carbon::setTestNow(now()->startOfDay()->addHours(9));
        $entry = $timer->start($aux, $customer->id, $sub->id);
        Carbon::setTestNow(now()->addHour());
        $timer->finish($entry);
        Carbon::setTestNow();

        return compact('aux', 'admin', 'customer', 'sub');
    }

    public function test_my_productivity_defaults_to_current_day(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();

        Livewire::actingAs($aux)->test(MyProductivity::class)
            ->assertSet('from', now()->toDateString())
            ->assertSet('to', now()->toDateString());
    }

    public function test_admin_dashboard_defaults_to_current_day(): void
    {
        ['admin' => $admin] = $this->seedWithEntry();

        Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->assertSet('from', now()->toDateString())
            ->assertSet('to', now()->toDateString());
    }

    public function test_user_report_matches_screen_total(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        $report = app(TimeReportService::class)->userReport($aux, $today, $today);

        $this->assertSame('01:00:00', $report->meta['Horas efectivas en el periodo']);
        $this->assertSame('Mi productividad', $report->title);
    }

    public function test_user_can_export_every_format(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        foreach (['csv', 'pdf', 'txt'] as $format) {
            Livewire::actingAs($aux)->test(MyProductivity::class)
                ->set('from', $today)
                ->set('to', $today)
                ->call('export', $format)
                ->assertFileDownloaded("mi-productividad_{$today}_{$today}.{$format}");
        }
    }

    public function test_admin_export_respects_selected_user(): void
    {
        ['admin' => $admin, 'aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->set('userId', $aux->id)
            ->set('from', $today)
            ->set('to', $today)
            ->call('export', 'csv')
            ->assertFileDownloaded("supervision-horas_usuario-{$aux->id}_{$today}_{$today}.csv");
    }

    public function test_admin_can_export_a_selected_group_report(): void
    {
        ['admin' => $admin, 'aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        $report = app(TimeReportService::class)->groupReport(collect([$aux]), $today, $today);
        $this->assertSame('Informe general de horas', $report->title);
        $this->assertSame('01:00:00', $report->meta['Horas efectivas totales']);
        $this->assertSame('Detalle de actividades por colaborador y día', collect($report->sections)->last()->title);

        Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->call('selectCollaboratorsByArea', (string) $aux->activeOrganizationalProfile->physical_area_id)
            ->assertSet('selectedCollaboratorIds', [$aux->id])
            ->call('selectCollaboratorsByPosition', (string) $aux->activeOrganizationalProfile->job_position_id)
            ->call('selectCollaboratorsBySuperior', '0');

        Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->set('selectedCollaboratorIds', [$aux->id])
            ->set('from', $today)
            ->set('to', $today)
            ->call('generateGroupReport')
            ->call('exportGroup', 'csv')
            ->assertFileDownloaded("informe-general-horas_{$today}_{$today}.csv");
    }

    public function test_admin_can_edit_activity_times_from_general_report_with_recalculation_and_audit(): void
    {
        ['admin' => $admin, 'aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();
        $entry = TimeEntry::with('intervals')->where('user_id', $aux->id)->firstOrFail();
        $editedStart = $entry->intervals->first()->started_at->copy()->subSeconds(30)->format('H:i:s');
        $editedEnd = $entry->intervals->first()->ended_at->copy()->addSeconds(45)->format('H:i:s');
        $correctionReason = 'Corrección validada con precisión de segundos.';

        Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->set('selectedCollaboratorIds', [$aux->id])
            ->set('from', $today)
            ->set('to', $today)
            ->call('generateGroupReport')
            ->call('openActivityEditModal', $entry->id)
            ->assertSet('showActivityEditModal', true)
            ->set('activityStartTime', $editedStart)
            ->set('activityEndTime', $editedEnd)
            ->call('saveActivityTimes')
            ->assertHasErrors(['activityCorrectionComment' => 'required'])
            ->set('activityCorrectionComment', $correctionReason)
            ->call('saveActivityTimes')
            ->assertHasNoErrors()
            ->assertSet('showActivityEditModal', false);

        $entry->refresh()->load('intervals', 'audits');

        $this->assertSame(3675, $entry->total_duration_seconds);
        $this->assertSame($editedStart, $entry->intervals->first()->started_at->format('H:i:s'));
        $this->assertSame($editedEnd, $entry->intervals->first()->ended_at->format('H:i:s'));
        $this->assertCount(1, $entry->audits);
        $this->assertSame($admin->id, $entry->audits->first()->admin_id);
        $this->assertSame($correctionReason, $entry->audits->first()->reason);
    }

    public function test_general_report_displays_all_activity_intervals_and_the_effective_daily_total(): void
    {
        ['admin' => $admin, 'aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();
        $entry = TimeEntry::with('intervals')->where('user_id', $aux->id)->firstOrFail();
        $firstInterval = $entry->intervals->first();
        $secondStart = $firstInterval->ended_at->copy()->addMinutes(15);
        $secondEnd = $secondStart->copy()->addMinutes(30);

        $entry->intervals()->create([
            'started_at' => $secondStart,
            'ended_at' => $secondEnd,
        ]);
        $entry->update(['total_duration_seconds' => 5400]);

        $data = app(TimeReportService::class)->adminSupervisionForUsers(
            [$aux->id],
            $today,
            $today,
            collect([$aux]),
        );
        $detail = app(TimeReportService::class)->activityDetailByDay($data['entries'], true, true);

        $this->assertSame(['Colaborador', 'Intervalos', 'Actividad', 'Cliente', 'Tiempo efectivo', 'Puesto profesional', 'Área física', 'Observaciones'], $detail['columns']);
        $this->assertSame('01h 30m 00s', $detail['groups'][0]['total_effective']);
        $this->assertStringContainsString(
            $secondStart->format('H:i:s').' – '.$secondEnd->format('H:i:s'),
            $detail['groups'][0]['rows'][0][1],
        );

        Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->set('selectedCollaboratorIds', [$aux->id])
            ->set('from', $today)
            ->set('to', $today)
            ->call('generateGroupReport')
            ->assertSee('Intervalos')
            ->assertSee('Total efectivo')
            ->assertSee('01h 30m 00s');
    }

    public function test_user_report_includes_activity_detail_by_day(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        $report = app(TimeReportService::class)->userReport($aux, $today, $today);
        $detailSection = collect($report->sections)->last();

        $this->assertSame('Detalle de actividades por día', $detailSection->title);
        $this->assertNotNull($detailSection->dayGroups);
        $this->assertCount(1, $detailSection->dayGroups);
        $this->assertSame(now()->format('d/m/Y'), $detailSection->dayGroups[0]['date']);
        $this->assertCount(1, $detailSection->dayGroups[0]['rows']);
        $this->assertSame('01:00:00', $detailSection->dayGroups[0]['rows'][0][4]);
    }

    public function test_admin_can_export_the_three_group_pdf_modes(): void
    {
        ['admin' => $admin, 'aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        $batch = app(TimeReportService::class)->individualReportsBatch(collect([$aux]), $today, $today);
        $this->assertSame('Reportes individuales agrupados', $batch->title);
        $this->assertStringContainsString('Reporte individual:', $batch->sections[0]->title);

        $component = Livewire::actingAs($admin)->test(InformeGeneralHoras::class)
            ->set('selectedCollaboratorIds', [$aux->id])
            ->set('from', $today)
            ->set('to', $today)
            ->call('generateGroupReport');

        $component->call('exportSelectedIndividualReport')
            ->assertFileDownloaded("supervision-horas_usuario-{$aux->id}_{$today}_{$today}.pdf");
        $component->call('exportSelectedIndividualBatch')
            ->assertFileDownloaded("reportes-individuales_{$today}_{$today}.pdf");
        $component->call('exportSelectedGeneralReport')
            ->assertFileDownloaded("informe-general-horas_{$today}_{$today}.pdf");
    }

    public function test_admin_report_includes_activity_detail_respecting_user_filter(): void
    {
        ['admin' => $admin, 'aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();

        $report = app(TimeReportService::class)->adminReport($aux, $today, $today);
        $detailSection = collect($report->sections)->last();

        $this->assertSame('Detalle de actividades por día', $detailSection->title);
        $this->assertNotContains('Colaborador', $detailSection->columns);

        $allReport = app(TimeReportService::class)->adminReport(null, $today, $today);
        $allDetail = collect($allReport->sections)->last();
        $this->assertContains('Colaborador', $allDetail->columns);
    }

    public function test_txt_export_includes_activity_lines(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();
        $report = app(TimeReportService::class)->userReport($aux, $today, $today);

        $content = app(\App\Services\Reports\Exporters\TxtExporter::class)->render($report);

        $this->assertStringContainsString('Detalle de actividades por día', $content);
        $this->assertStringContainsString('| Cliente: Cliente A | Tiempo: 01:00:00', $content);
    }

    public function test_pdf_export_generates_binary_content(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();
        $today = now()->toDateString();
        $report = app(TimeReportService::class)->userReport($aux, $today, $today);

        $pdf = app(\App\Services\Reports\Exporters\PdfExporter::class)->render($report);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_unsupported_format_is_rejected(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();

        $report = app(TimeReportService::class)->userReport($aux, now()->toDateString(), now()->toDateString());

        $this->assertFalse(app(ReportExportManager::class)->supports('xml'));
    }
}
