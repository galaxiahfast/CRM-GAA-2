<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\Admin\AdminTimeDashboard;
use App\Livewire\TimeControl\MyProductivity;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
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

        Livewire::actingAs($admin)->test(AdminTimeDashboard::class)
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

        Livewire::actingAs($admin)->test(AdminTimeDashboard::class)
            ->set('userId', $aux->id)
            ->set('from', $today)
            ->set('to', $today)
            ->call('export', 'csv')
            ->assertFileDownloaded("supervision-horas_usuario-{$aux->id}_{$today}_{$today}.csv");
    }

    public function test_unsupported_format_is_rejected(): void
    {
        ['aux' => $aux] = $this->seedWithEntry();

        $report = app(TimeReportService::class)->userReport($aux, now()->toDateString(), now()->toDateString());

        $this->assertFalse(app(ReportExportManager::class)->supports('xml'));
    }
}
