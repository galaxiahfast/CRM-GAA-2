<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Security\RequestMemoizedHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Tests\TestCase;

/**
 * El inicio de sesión debe ser rápido sin perder validaciones: bcrypt (lento a propósito) se calcula una
 * sola vez por intento aunque Fortify necesite comprobar la contraseña en dos pasos de su flujo.
 */
class LoginSpeedTest extends TestCase
{
    use RefreshDatabase;

    private function hasher(): RequestMemoizedHasher
    {
        $hasher = app('hash');

        $this->assertInstanceOf(RequestMemoizedHasher::class, $hasher);

        return $hasher;
    }

    public function test_a_valid_login_computes_the_password_hash_only_once(): void
    {
        $user = User::factory()->create();
        $before = $this->hasher()->computedChecks();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('inicio', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $this->hasher()->computedChecks() - $before);
    }

    public function test_a_wrong_password_is_still_rejected_and_computed_only_once(): void
    {
        $user = User::factory()->create();
        $before = $this->hasher()->computedChecks();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, $this->hasher()->computedChecks() - $before);
    }

    public function test_a_user_with_two_factor_is_still_sent_to_the_challenge_after_one_check(): void
    {
        if (! in_array(TwoFactorAuthenticatable::class, class_uses_recursive(User::class), true)) {
            $this->markTestSkipped('El modelo de usuario no usa autenticación en dos pasos.');
        }

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => encrypt('SECRETOPRUEBA'),
            'two_factor_recovery_codes' => encrypt(json_encode(['codigo-1', 'codigo-2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $before = $this->hasher()->computedChecks();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->assertSame(1, $this->hasher()->computedChecks() - $before);
    }

    public function test_the_memoized_hasher_never_confuses_different_passwords_or_hashes(): void
    {
        $hasher = $this->hasher();
        $hash = Hash::make('clave-correcta');
        $otherHash = Hash::make('otra-clave');

        $this->assertTrue($hasher->check('clave-correcta', $hash));
        $this->assertTrue($hasher->check('clave-correcta', $hash));
        $this->assertFalse($hasher->check('clave-incorrecta', $hash));
        $this->assertFalse($hasher->check('clave-correcta', $otherHash));
        $this->assertTrue($hasher->check('otra-clave', $otherHash));
        $this->assertFalse($hasher->check('', $hash));
    }

    public function test_the_hashing_cost_and_algorithm_are_unchanged(): void
    {
        $hash = Hash::make('cualquier-clave');

        $this->assertSame('bcrypt', password_get_info($hash)['algoName']);
        $this->assertSame((int) config('hashing.bcrypt.rounds', 12), password_get_info($hash)['options']['cost']);
    }
}
