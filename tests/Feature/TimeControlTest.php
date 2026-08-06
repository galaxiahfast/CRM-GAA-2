<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Services\TimeControl\Exceptions\ActiveEntryException;
use App\Services\TimeControl\TimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimeControlTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): array
    {
        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);

        $position = JobPosition::create(['name' => 'Auxiliar de Nómina']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $aux = User::create([
            'name' => 'Aux', 'email' => 'aux@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id,
        ]);
        UserOrganizationalProfile::create([
            'user_id' => $aux->id, 'job_position_id' => $position->id,
            'physical_area_id' => $area->id, 'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);

        $customer = Customer::create(['name' => 'Cliente A', 'rfc' => 'XAXX010101000', 'created_by' => $aux->id]);
        $service = Service::create(['service' => 'Fiscal']);
        $sub = new SubService(['sub_service' => 'Cálculo de impuestos', 'service_id' => $service->id]);
        $sub->unique_key = 'CALC-IMP';
        $sub->save();

        return compact('aux', 'admin', 'customer', 'sub');
    }

    public function test_auxiliary_runs_timer_cycle_ignoring_dead_time(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);

        Carbon::setTestNow('2026-06-10 13:00:00');
        $entry = $timer->start($aux, $customer->id, $sub->id);

        Carbon::setTestNow('2026-06-10 14:00:00'); // 1h trabajada
        $timer->pause($entry);

        Carbon::setTestNow('2026-06-10 15:00:00'); // 1h muerta (ignorada)
        $timer->resume($entry);

        Carbon::setTestNow('2026-06-10 18:00:00'); // 3h trabajadas
        $timer->finish($entry);

        $entry->refresh();
        $this->assertSame(TimeEntry::STATUS_PAUSED, $entry->status);
        $this->assertSame(4 * 3600, $entry->total_duration_seconds); // 4h efectivas
        $this->assertCount(2, $entry->intervals);

        Carbon::setTestNow();
    }

    public function test_concurrency_blocks_second_active_entry(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);

        $timer->start($aux, $customer->id, $sub->id);

        $this->expectException(ActiveEntryException::class);
        $timer->start($aux, $customer->id, $sub->id);
    }

    public function test_admin_is_forbidden_from_starting_via_api(): void
    {
        ['admin' => $admin, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();

        $this->actingAs($admin)
            ->postJson('/api/time-entries/start', ['customer_id' => $customer->id, 'sub_service_id' => $sub->id])
            ->assertStatus(403);
    }

    public function test_api_reuses_existing_daily_entry_when_already_active(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $entry = app(TimerService::class)->start($aux, $customer->id, $sub->id);

        $this->actingAs($aux)
            ->postJson('/api/time-entries/start', ['customer_id' => $customer->id, 'sub_service_id' => $sub->id])
            ->assertCreated()
            ->assertJsonPath('time_entry_id', $entry->id);
    }

    public function test_scheduler_auto_closes_in_progress_entry(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);

        Carbon::setTestNow('2026-06-10 10:00:00');
        $entry = $timer->start($aux, $customer->id, $sub->id);

        Carbon::setTestNow('2026-06-10 23:59:59');
        $closed = $timer->autoCloseOpenEntries();

        $this->assertSame(1, $closed);
        $entry->refresh();
        $this->assertSame(TimeEntry::STATUS_AUTO_CLOSED, $entry->status);
        $this->assertNotNull($entry->intervals->first()->ended_at);

        Carbon::setTestNow();
    }

    public function test_full_shift_from_three_to_nine_is_not_truncated(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);

        Carbon::setTestNow(Carbon::create(2026, 6, 10, 3, 0, 0, 'America/Mexico_City'));
        $entry = $timer->start($aux, $customer->id, $sub->id);

        Carbon::setTestNow(Carbon::create(2026, 6, 10, 21, 0, 0, 'America/Mexico_City'));
        $timer->autoCloseOpenEntries();

        $entry->refresh();
        $this->assertSame(TimeEntry::STATUS_AUTO_CLOSED, $entry->status);
        $this->assertSame(18 * 3600, $entry->total_duration_seconds);

        Carbon::setTestNow();
    }
}
