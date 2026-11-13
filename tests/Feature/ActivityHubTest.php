<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\ActivityHub;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityHubTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        // El rol con id 1 cuenta como administrador: se reserva ese id antes de crear los demás
        Role::query()->firstOrCreate(['role' => 'Administrador']);
        $roleModel = Role::query()->firstOrCreate(['role' => $role]);
        $user = User::query()->create([
            'name' => 'Prueba', 'last_name' => 'Hub', 'email' => $email,
            'password' => Hash::make('secret'), 'role_id' => $roleModel->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public function test_the_stopwatch_comes_first_then_the_daily_detail_and_then_the_period_productivity(): void
    {
        $user = $this->user('Auxiliar', 'hub-aux@datamid.test');

        $html = Livewire::actingAs($user)->test(ActivityHub::class)->assertOk()->html();

        $clock = strpos($html, 'Registro de actividades');
        $productivity = strpos($html, 'Productividad del periodo');
        $detail = strpos($html, 'Detalle de actividades por día');
        $this->assertNotFalse($clock);
        $this->assertNotFalse($productivity);
        $this->assertNotFalse($detail);
        // Cronómetro, luego el detalle por día y al final la productividad del periodo
        $this->assertLessThan($detail, $clock);
        $this->assertLessThan($productivity, $detail);
        // Un solo título para toda la página
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('Cronómetro y productividad', $html);
        $this->assertStringNotContainsString('>Mi productividad<', $html);
    }

    public function test_the_old_productivity_address_sends_regular_users_to_the_shared_page(): void
    {
        $this->actingAs($this->user('Auxiliar', 'hub-old@datamid.test'))
            ->get(route('time.reports'))
            ->assertRedirect(route('time.index').'#productividad');
    }

    public function test_administrators_are_sent_to_their_own_panel(): void
    {
        Livewire::actingAs($this->user('Administrador', 'hub-admin@datamid.test'))
            ->test(ActivityHub::class)
            ->assertRedirect('/time/admin');
    }
}
