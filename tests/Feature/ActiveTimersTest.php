<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\Admin\ActiveTimers;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class ActiveTimersTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_supervision_replaces_activity_corrections(): void
    {
        $this->assertTrue(Route::has('time.admin.online'));
        $this->assertFalse(Route::has('time.admin.corrections'));
    }

    public function test_supervisor_sees_only_running_timers_with_operational_activity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 13:30:00', config('time-control.timezone')));

        $administratorRole = Role::create([
            'role' => 'Administrador',
            'permission_profile' => Role::PROFILE_ADMINISTRATOR,
        ]);
        $auxiliaryRole = Role::create([
            'role' => 'Auxiliar',
            'permission_profile' => Role::PROFILE_AUXILIARY,
        ]);
        $position = JobPosition::create(['name' => 'Auxiliar contable']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $administrator = $this->user('Administrador', 'admin-online@example.test', $administratorRole);
        $superior = $this->user('Jefa Directa', 'jefa-online@example.test', $auxiliaryRole);
        $activeUser = $this->user('Ana Activa', 'ana-online@example.test', $auxiliaryRole);
        $subordinate = $this->user('Luis Subordinado', 'luis-online@example.test', $auxiliaryRole);
        $pausedUser = $this->user('Usuario Pausado', 'pausado-online@example.test', $auxiliaryRole);

        $activeUser->superiors()->attach($superior->id, [
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
        ]);
        $subordinate->superiors()->attach($activeUser->id, [
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
        ]);

        $customer = Customer::create([
            'name' => 'Cliente en línea',
            'rfc' => 'XAXX010101000',
            'created_by' => $administrator->id,
        ]);
        $service = Service::create(['service' => 'Contabilidad']);
        $activity = SubService::create([
            'sub_service' => 'Conciliación bancaria',
            'service_id' => $service->id,
            'unique_key' => 'ACTIVE-TIMER-TEST',
        ]);

        $activeEntry = $this->entry(
            $activeUser,
            $customer,
            $activity,
            $administratorRole,
            $position,
            $area,
            TimeEntry::STATUS_IN_PROGRESS
        );
        $activeEntry->intervals()->create([
            'started_at' => Carbon::now(config('time-control.timezone'))->subMinutes(15),
        ]);

        $pausedEntry = $this->entry(
            $pausedUser,
            $customer,
            $activity,
            $administratorRole,
            $position,
            $area,
            TimeEntry::STATUS_PAUSED
        );
        $pausedEntry->intervals()->create([
            'started_at' => Carbon::now(config('time-control.timezone'))->subMinutes(30),
            'ended_at' => Carbon::now(config('time-control.timezone'))->subMinutes(10),
        ]);

        Livewire::actingAs($administrator)
            ->test(ActiveTimers::class)
            ->assertSet('activeTimers.0.name', 'Ana Activa')
            ->assertSet('activeTimers.0.activity', 'Conciliación bancaria')
            ->assertSet('activeTimers.0.customer', 'Cliente en línea')
            ->assertSet('activeTimers.0.position', 'Auxiliar contable')
            ->assertSet('activeTimers.0.area', 'Contabilidad')
            ->assertSet('activeTimers.0.elapsed_seconds', 900)
            ->assertSet('activeTimers.0.superiors.0.name', 'Jefa Directa')
            ->assertSet('activeTimers.0.subordinates.0.name', 'Luis Subordinado')
            ->assertCount('activeTimers', 1)
            ->assertSee('Personas con cronómetro activo')
            ->assertSee('wire:poll.3s.visible="refreshActiveTimers"', false)
            ->assertSee('wire:ignore', false)
            ->assertSee('Sincronización activa')
            ->assertSee('Jefe directo')
            ->assertSee('Subordinados')
            ->assertDontSee('Usuario Pausado');

        Carbon::setTestNow();
    }

    private function user(string $name, string $email, Role $role): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);
    }

    private function entry(
        User $user,
        Customer $customer,
        SubService $activity,
        Role $role,
        JobPosition $position,
        PhysicalArea $area,
        int $status
    ): TimeEntry {
        return TimeEntry::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sub_service_id' => $activity->id,
            'role_id_snapshot' => $role->id,
            'job_position_id_snapshot' => $position->id,
            'physical_area_id_snapshot' => $area->id,
            'entry_date' => now()->toDateString(),
            'status' => $status,
            'total_duration_seconds' => 0,
        ]);
    }
}
