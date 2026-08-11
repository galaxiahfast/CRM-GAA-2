<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSeeText('González Alonzo y Asociados S.C.P')
            ->assertSeeText('Políticas de privacidad')
            ->assertSeeText('Todos los derechos reservados')
            ->assertDontSee('data-social-provider="facebook"', false)
            ->assertDontSee('data-social-provider="google"', false)
            ->assertDontSee('data-social-provider="apple"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('inicio', absolute: false));
    }

    public function test_lightweight_start_page_does_not_query_dashboard_data(): void
    {
        $user = User::factory()->create();
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = mb_strtolower($query->sql);
        });

        $this->actingAs($user)
            ->get('/inicio')
            ->assertOk()
            ->assertSeeText('Sesión iniciada correctamente')
            ->assertSeeText($user->name)
            ->assertSeeText('Inicio')
            ->assertSeeText('Dashboard')
            ->assertSee('data-session-logout', false);

        $dashboardTables = ['customers', 'customer_files', 'services', 'sub_services'];

        foreach ($dashboardTables as $table) {
            $this->assertFalse(
                collect($queries)->contains(fn (string $sql): bool => str_contains($sql, $table)),
                "La pantalla ligera consultó la tabla pesada [{$table}]."
            );
        }
    }

    public function test_login_preserves_the_original_protected_destination(): void
    {
        $user = User::factory()->create();

        $this->get(route('soporte.ticket'))
            ->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('soporte.ticket', absolute: false));
    }

    public function test_users_can_keep_the_session_started(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->getRememberToken());
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_authenticated_users_are_sent_from_the_entry_page_to_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_logging_out_returns_to_the_same_canonical_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
        $this->get('/')->assertRedirect(route('login', absolute: false));
    }

    public function test_logout_uses_the_safe_session_validation_flow(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-session-logout', false)
            ->assertSee('$root.requestSubmit();', false);
    }
}
