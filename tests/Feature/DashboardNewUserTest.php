<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardNewUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_with_a_custom_role_can_open_the_dashboard(): void
    {
        $role = Role::create(['role' => 'Supervisor']);
        $user = User::create([
            'name' => 'Usuario nuevo',
            'email' => 'nuevo@test.mx',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('loadDashboard')
            ->assertOk()
            ->assertSet('dashboardReady', true)
            ->assertSet('customers', fn ($customers): bool => collect($customers)->isEmpty());
    }

    public function test_guest_is_redirected_to_login_instead_of_receiving_a_server_error(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_query_count_does_not_grow_with_the_number_of_customers(): void
    {
        $role = Role::create([
            'role' => 'Administrador',
            'permission_profile' => Role::PROFILE_ADMINISTRATOR,
        ]);
        $user = User::create([
            'name' => 'Administrador de prueba',
            'email' => 'admin-dashboard@test.mx',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
        ]);

        Customer::insert(collect(range(1, 30))->map(fn (int $number): array => [
            'name' => 'Cliente '.$number,
            'rfc' => 'TEST'.str_pad((string) $number, 9, '0', STR_PAD_LEFT),
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        $this->actingAs($user);
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $dashboard = new Dashboard();
        $dashboard->mount();
        $dashboard->loadDashboard();

        $this->assertTrue($dashboard->dashboardReady);
        $this->assertCount(30, collect($dashboard->customers));
        $this->assertLessThanOrEqual(18, $queryCount, 'El dashboard volvió a introducir consultas N+1.');
    }
}
