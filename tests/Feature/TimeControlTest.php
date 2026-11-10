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
use Livewire\Livewire;
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

    public function test_each_activity_row_offers_plain_icons_and_is_dragged_by_the_row_itself(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);
        $entry = $timer->start($aux, $customer->id, $sub->id);
        $timer->pause($entry);

        Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->assertSeeHtml('data-activity-id="'.$entry->id.'"')
            ->assertDontSeeHtml('data-activity-drag-handle')
            ->assertDontSeeHtml('toggleHidden')
            ->assertSeeHtml('requestDeletion('.$entry->id.')')
            ->assertSeeHtml('class="clock-icon');
    }

    public function test_errors_are_shown_as_a_popup_notice_that_can_repeat(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $entry = app(TimerService::class)->start($aux, $customer->id, $sub->id);

        Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->call('requestDeletion', $entry->id)
            ->assertHasErrors('timer')
            ->assertSet('errorToastVersion', 1)
            ->assertSeeHtml('clock-error-toast-1')
            ->call('requestDeletion', $entry->id)
            ->assertSet('errorToastVersion', 2)
            ->assertSeeHtml('clock-error-toast-2');
    }

    public function test_the_clock_shows_an_example_row_until_a_real_activity_starts(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();

        $component = Livewire::actingAs($aux)->test(\App\Livewire\TimeControl\RegistroActividades::class);
        $component->assertSee('Cliente de ejemplo')->assertSee('Actividad de ejemplo');

        app(TimerService::class)->start($aux, $customer->id, $sub->id);

        Livewire::actingAs($aux)->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->assertDontSee('Cliente de ejemplo')
            ->assertDontSee('Actividad de ejemplo');
    }

    public function test_the_quick_tour_can_point_at_the_row_controls(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);
        $entry = $timer->start($aux, $customer->id, $sub->id);
        $timer->pause($entry);

        Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->assertDontSee('Guía de uso')
            ->assertSee('Uso rápido')
            ->assertSeeHtml('id="clock-tour-row"')
            ->assertSeeHtml('id="clock-tour-toggle"')
            ->assertSeeHtml('id="clock-tour-delete"');
    }

    public function test_the_clock_offers_its_pdf_guide(): void
    {
        ['aux' => $aux] = $this->seedCatalog();

        Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->assertSee('Guía PDF')
            ->assertSeeHtml(route('section.guide', 'cronometro'));

        $this->actingAs($aux)
            ->get(route('section.guide', 'cronometro'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_user_deletes_a_paused_activity_only_after_typing_its_exact_name(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $timer = app(TimerService::class);
        $entry = $timer->start($aux, $customer->id, $sub->id);
        $timer->pause($entry);
        $intervalId = $entry->intervals()->firstOrFail()->id;

        $component = Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->call('requestDeletion', $entry->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deleteActivityName', 'Cálculo de impuestos')
            ->set('deleteConfirmation', 'Calculo de impuestos')
            ->call('deleteEntry')
            ->assertHasErrors('deleteConfirmation');

        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);

        $component
            ->set('deleteConfirmation', 'Cálculo de impuestos')
            ->call('deleteEntry')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('time_intervals', ['id' => $intervalId]);
    }

    public function test_user_cannot_delete_an_active_or_someone_elses_activity(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $entry = app(TimerService::class)->start($aux, $customer->id, $sub->id);

        Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->call('requestDeletion', $entry->id)
            ->assertSet('showDeleteModal', false)
            ->assertHasErrors('timer');

        $otherRole = Role::where('role', 'Auxiliar')->firstOrFail();
        $other = User::create([
            'name' => 'Otro', 'email' => 'otro@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $otherRole->id,
        ]);

        Livewire::actingAs($other)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->call('requestDeletion', $entry->id)
            ->assertSet('showDeleteModal', false)
            ->assertHasErrors('timer');

        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_user_reorders_activities_without_changing_timer_data(): void
    {
        ['aux' => $aux, 'customer' => $customer, 'sub' => $sub] = $this->seedCatalog();
        $secondSub = SubService::create([
            'sub_service' => 'Revisión mensual',
            'service_id' => $sub->service_id,
            'unique_key' => 'REV-MENSUAL',
        ]);
        $timer = app(TimerService::class);

        $first = $timer->start($aux, $customer->id, $sub->id);
        $timer->pause($first);
        $second = $timer->start($aux, $customer->id, $secondSub->id);
        $timer->pause($second);

        $firstSnapshot = $first->fresh()->only(['status', 'total_duration_seconds']);
        $secondSnapshot = $second->fresh()->only(['status', 'total_duration_seconds']);
        $firstIntervalId = $first->intervals()->firstOrFail()->id;
        $secondIntervalId = $second->intervals()->firstOrFail()->id;

        Livewire::actingAs($aux)
            ->test(\App\Livewire\TimeControl\RegistroActividades::class)
            ->call('updateActivityOrder', [
                ['value' => $first->id],
                ['value' => $second->id],
            ]);

        $this->assertDatabaseHas('time_entries', ['id' => $first->id, 'sort_order' => 1] + $firstSnapshot);
        $this->assertDatabaseHas('time_entries', ['id' => $second->id, 'sort_order' => 2] + $secondSnapshot);
        $this->assertDatabaseHas('time_intervals', ['id' => $firstIntervalId, 'time_entry_id' => $first->id]);
        $this->assertDatabaseHas('time_intervals', ['id' => $secondIntervalId, 'time_entry_id' => $second->id]);
    }
}
