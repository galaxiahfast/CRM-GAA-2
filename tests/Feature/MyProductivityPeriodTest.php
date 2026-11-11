<?php

namespace Tests\Feature;

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
use App\Services\TimeControl\TimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lógica de los periodos de Mi productividad: el resumen de arriba y el Detalle de actividades por día
 * tienen cada uno su propio periodo (por defecto, los últimos 15 días).
 */
class MyProductivityPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $aux;

    private Customer $customer;

    private SubService $sub;

    protected function setUp(): void
    {
        parent::setUp();

        $auxRole = Role::create(['role' => 'Auxiliar']);
        $position = JobPosition::create(['name' => 'Auxiliar de Nómina']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $this->aux = User::create([
            'name' => 'Aux', 'last_name' => 'Uno', 'email' => 'aux@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id,
        ]);
        UserOrganizationalProfile::create([
            'user_id' => $this->aux->id, 'job_position_id' => $position->id,
            'physical_area_id' => $area->id, 'valid_from' => now()->subYear()->toDateString(),
            'is_active' => true,
        ]);

        $this->customer = Customer::create(['name' => 'Cliente A', 'rfc' => 'XAXX010101000', 'created_by' => $this->aux->id]);
        $service = Service::create(['service' => 'Fiscal']);
        $this->sub = new SubService(['sub_service' => 'Cálculo de impuestos', 'service_id' => $service->id]);
        $this->sub->unique_key = 'CALC-IMP';
        $this->sub->save();
    }

    /** Registra una actividad de $hours horas que empezó hace $daysAgo días a las 9:00. */
    private function entryDaysAgo(int $daysAgo, int $hours = 1, ?Customer $customer = null): TimeEntry
    {
        $timer = app(TimerService::class);
        Carbon::setTestNow(now()->subDays($daysAgo)->startOfDay()->addHours(9));
        $entry = $timer->start($this->aux, ($customer ?? $this->customer)->id, $this->sub->id);
        Carbon::setTestNow(now()->addHours($hours));
        $timer->finish($entry);
        Carbon::setTestNow();

        return $entry->fresh();
    }

    public function test_both_periods_start_with_fifteen_days_today_and_the_fourteen_before(): void
    {
        $component = Livewire::actingAs($this->aux)->test(MyProductivity::class);

        $component
            ->assertSet('to', now()->toDateString())
            ->assertSet('from', now()->subDays(14)->toDateString())
            ->assertSet('detailTo', now()->toDateString())
            ->assertSet('detailFrom', now()->subDays(14)->toDateString());

        // 15 días contando los dos extremos
        $days = Carbon::parse($component->get('from'))->diffInDays(Carbon::parse($component->get('to'))) + 1;
        $this->assertSame(15, (int) $days);
    }

    public function test_an_invalid_date_falls_back_to_the_default_period(): void
    {
        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->set('from', 'no-es-una-fecha')
            ->assertSet('from', now()->subDays(14)->toDateString())
            ->set('to', '2026-13-45')
            ->assertSet('to', now()->toDateString())
            ->set('detailFrom', '')
            ->assertSet('detailFrom', now()->subDays(14)->toDateString())
            ->set('detailTo', "'; DROP TABLE users; --")
            ->assertSet('detailTo', now()->toDateString())
            ->assertOk();
    }

    public function test_a_reversed_period_is_swapped_when_it_is_consulted(): void
    {
        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->set('from', now()->toDateString())
            ->set('to', now()->subDays(5)->toDateString())
            ->call('applyFilters')
            ->assertSet('from', now()->subDays(5)->toDateString())
            ->assertSet('to', now()->toDateString())
            ->set('detailFrom', now()->toDateString())
            ->set('detailTo', now()->subDays(3)->toDateString())
            ->call('applyDetailFilters')
            ->assertSet('detailFrom', now()->subDays(3)->toDateString())
            ->assertSet('detailTo', now()->toDateString());
    }

    public function test_the_detail_period_does_not_change_the_summary_period_or_its_data(): void
    {
        $this->entryDaysAgo(0);

        $component = Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->set('detailFrom', '2000-01-01')
            ->set('detailTo', '2000-01-02')
            ->call('applyDetailFilters');

        // El resumen sigue con sus fechas y con la actividad de hoy
        $component->assertSet('from', now()->subDays(14)->toDateString())
            ->assertSet('to', now()->toDateString());
        $this->assertSame(3600, $component->viewData('totalSeconds'));
        $this->assertCount(1, $component->viewData('entries'));

        // El detalle, en cambio, no tiene actividades en ese periodo y muestra el ejemplo
        $this->assertSame([], $component->viewData('activityDetail')['groups']);
        $this->assertCount(0, $component->viewData('detailEntries'));
        $component->assertSee('Actividad de ejemplo');
    }

    public function test_the_summary_period_does_not_change_the_detail(): void
    {
        $this->entryDaysAgo(0);

        $component = Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->set('from', '2000-01-01')
            ->set('to', '2000-01-02')
            ->call('applyFilters');

        $this->assertSame(0, $component->viewData('totalSeconds'));
        $this->assertCount(1, $component->viewData('detailEntries'));
        $this->assertCount(1, $component->viewData('activityDetail')['groups']);
    }

    public function test_the_detail_groups_each_day_in_chronological_order_with_its_own_total(): void
    {
        $this->entryDaysAgo(3, 2);
        $this->entryDaysAgo(3, 1, Customer::create(['name' => 'Cliente B', 'rfc' => 'XAXX010101001', 'created_by' => $this->aux->id]));
        $this->entryDaysAgo(0, 1);

        $groups = Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->viewData('activityDetail')['groups'];

        $this->assertCount(2, $groups);
        $this->assertSame(now()->subDays(3)->format('d/m/Y'), $groups[0]['date']);
        $this->assertSame(now()->format('d/m/Y'), $groups[1]['date']);
        $this->assertCount(2, $groups[0]['rows']);
        $this->assertCount(1, $groups[1]['rows']);
        $this->assertSame('3h 00m 00s', str_replace(['00h ', '03h '], ['', '3h '], $groups[0]['total_effective']));
    }

    public function test_the_day_total_shows_three_digit_hours_and_fixed_width_digits(): void
    {
        $entry = $this->entryDaysAgo(0);
        TimeEntry::query()->whereKey($entry->id)->update(['total_duration_seconds' => 111 * 3600]);

        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->assertSeeHtml('<span class="prod-dg">1</span><span class="prod-dg">1</span><span class="prod-dg">1</span>h')
            // la fecha del día usa año de dos cifras y cada cifra en su caja de ancho fijo
            ->assertSeeHtml('<span class="prod-dg">'.now()->format('y')[0].'</span><span class="prod-dg">'.now()->format('y')[1].'</span>');
    }

    public function test_the_day_row_counts_distinct_clients(): void
    {
        $other = Customer::create(['name' => 'Cliente B', 'rfc' => 'XAXX010101001', 'created_by' => $this->aux->id]);
        $this->entryDaysAgo(1, 1);
        $this->entryDaysAgo(1, 1, $other);

        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->assertSee('2 actividades')
            ->assertSee('2 clientes');
    }

    public function test_the_activity_detail_export_uses_the_detail_period_only(): void
    {
        $this->entryDaysAgo(0);

        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->set('from', '2001-01-01')
            ->set('to', '2001-01-31')
            ->set('detailFrom', '2002-02-01')
            ->set('detailTo', '2002-02-28')
            ->call('exportDetail', 'pdf')
            ->assertFileDownloaded('mi-productividad_2002-02-01_2002-02-28.pdf')
            ->call('export', 'pdf')
            ->assertFileDownloaded('mi-productividad_2001-01-01_2001-01-31.pdf');
    }

    public function test_only_the_pdf_format_can_be_exported(): void
    {
        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->call('export', 'csv')
            ->assertStatus(404);

        Livewire::actingAs($this->aux)->test(MyProductivity::class)
            ->call('exportDetail', 'txt')
            ->assertStatus(404);
    }

    public function test_a_user_cannot_see_the_activities_of_another_user(): void
    {
        $other = User::create([
            'name' => 'Otro', 'last_name' => 'Dos', 'email' => 'otro@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $this->aux->role_id,
        ]);
        UserOrganizationalProfile::create([
            'user_id' => $other->id, 'job_position_id' => JobPosition::first()->id,
            'physical_area_id' => PhysicalArea::first()->id, 'valid_from' => now()->subYear()->toDateString(),
            'is_active' => true,
        ]);
        $timer = app(TimerService::class);
        Carbon::setTestNow(now()->startOfDay()->addHours(9));
        $entry = $timer->start($other, $this->customer->id, $this->sub->id);
        Carbon::setTestNow(now()->addHour());
        $timer->finish($entry);
        Carbon::setTestNow();

        $component = Livewire::actingAs($this->aux)->test(MyProductivity::class);

        $this->assertSame(0, $component->viewData('totalSeconds'));
        $this->assertCount(0, $component->viewData('detailEntries'));
    }
}
