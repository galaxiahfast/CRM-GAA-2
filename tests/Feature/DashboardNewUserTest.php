<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertOk()
            ->assertSet('customers', fn ($customers): bool => collect($customers)->isEmpty());
    }
}
