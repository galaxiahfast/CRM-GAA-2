<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInterns;
use App\Models\UserOrganizationalProfile;
use App\Notifications\VerifyAccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.allowed_domains' => ['datamid.com.mx'],
        ]);
    }

    public function test_google_button_only_appears_when_it_is_configured(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Continuar con Google');

        config(['services.google.allowed_domains' => []]);
        $this->get(route('login'))->assertOk()->assertDontSee('Continuar con Google');
        $this->get(route('auth.google.redirect'))->assertNotFound();
        $this->get(route('auth.google.callback'))->assertNotFound();
    }

    public function test_redirect_sends_the_user_to_google_with_the_basic_scopes(): void
    {
        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertEqualsCanonicalizing(['openid', 'email', 'profile'], explode(' ', $query['scope']));
        $this->assertNotEmpty($query['state']);
        $this->assertStringContainsString('hd=datamid.com.mx', $location);
    }

    public function test_new_accounts_start_as_interns_with_hourly_pay_and_food_allowance(): void
    {
        Notification::fake();
        $this->roles();
        $this->fakeGoogleUser('nueva.persona@datamid.com.mx', 'sub-1', ['given_name' => 'Nueva', 'family_name' => 'Persona']);

        $this->get(route('auth.google.callback'))->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'nueva.persona@datamid.com.mx')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Auxiliar', $user->role->role);
        $this->assertSame('Nueva', $user->name);
        $this->assertSame('Persona', $user->last_name);
        $this->assertSame('sub-1', $user->google_id);
        $this->assertTrue(Hash::check('cualquier-cosa', $user->password) === false);

        // El correo se confirma además con un enlace que llega al buzón; hasta entonces no se pasa de ahí
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyAccountEmail::class);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $link = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
        $this->get($link);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->assertDatabaseHas('user_interns', ['intern_id' => $user->id, 'created_by' => $user->id]);
        $profile = UserOrganizationalProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($profile->is_active);
        $this->assertSame('25.00', (string) $profile->hourly_rate);
        $this->assertSame('50.00', (string) $profile->food_allowance);
        $this->assertNull($profile->job_position_id);
    }

    public function test_existing_accounts_keep_their_role_and_are_not_turned_into_interns(): void
    {
        Notification::fake();
        $roles = $this->roles();
        $admin = User::query()->create([
            'name' => 'Admin', 'last_name' => 'Prueba', 'email' => 'admin@datamid.com.mx',
            'password' => Hash::make('secret'), 'role_id' => $roles['Administrador']->id,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->fakeGoogleUser('Admin@Datamid.com.mx', 'sub-admin');

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        Notification::assertNotSentTo($admin, VerifyAccountEmail::class);
        $this->assertTrue(Hash::check('secret', $admin->fresh()->password), 'La contraseña de una cuenta ya verificada no cambia.');

        $this->assertAuthenticatedAs($admin->fresh());
        $this->assertSame('Administrador', $admin->fresh()->role->role);
        $this->assertSame('sub-admin', $admin->fresh()->google_id);
        $this->assertSame(0, UserInterns::query()->count());
        $this->assertSame(0, UserOrganizationalProfile::query()->count());
        $this->assertSame(1, User::query()->count());
    }

    public function test_an_existing_account_without_a_confirmed_email_must_confirm_it_and_loses_its_old_password(): void
    {
        Notification::fake();
        $roles = $this->roles();
        $user = User::query()->create([
            'name' => 'Pendiente', 'last_name' => 'Confirmar', 'email' => 'pendiente@datamid.com.mx',
            'password' => Hash::make('contraseña-de-otra-persona'), 'role_id' => $roles['Auxiliar']->id,
        ]);
        $this->fakeGoogleUser('pendiente@datamid.com.mx', 'sub-5');

        $this->get(route('auth.google.callback'))->assertRedirect(route('verification.notice'));

        $user = $user->fresh();
        $this->assertSame('sub-5', $user->google_id);
        $this->assertFalse(Hash::check('contraseña-de-otra-persona', $user->password));
        Notification::assertSentTo($user, VerifyAccountEmail::class);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_accounts_without_google_are_not_asked_to_confirm_their_email(): void
    {
        $roles = $this->roles();
        $user = User::query()->create([
            'name' => 'Normal', 'last_name' => 'Prueba', 'email' => 'normal@datamid.com.mx',
            'password' => Hash::make('secret'), 'role_id' => $roles['Auxiliar']->id,
        ]);

        $this->assertNull($user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_accounts_from_other_domains_or_with_unverified_email_are_rejected(): void
    {
        $this->roles();

        $this->fakeGoogleUser('alguien@gmail.com', 'sub-2');
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->fakeGoogleUser('alguien@datamid.com.mx', 'sub-3', ['email_verified' => false]);
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_users_with_two_factor_enabled_still_have_to_enter_their_code(): void
    {
        $roles = $this->roles();
        $user = User::query()->create([
            'name' => 'Con', 'last_name' => 'Doble', 'email' => 'doble@datamid.com.mx',
            'password' => Hash::make('secret'), 'role_id' => $roles['Auxiliar']->id,
        ]);
        $user->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
        $this->fakeGoogleUser('doble@datamid.com.mx', 'sub-4');

        $this->get(route('auth.google.callback'))->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->assertSame($user->id, session('login.id'));
    }

    /** @return array<string, Role> */
    private function roles(): array
    {
        $roles = [];
        foreach (['Administrador', 'Coordinador', 'Contador', 'Auxiliar'] as $name) {
            $roles[$name] = Role::query()->firstOrCreate(['role' => $name]);
        }

        return $roles;
    }

    /** @param  array<string, mixed>  $raw */
    private function fakeGoogleUser(string $email, string $id, array $raw = []): void
    {
        $socialUser = (new SocialiteUser())
            ->setRaw($raw + ['email_verified' => true])
            ->map(['id' => $id, 'name' => 'Nombre Completo', 'email' => $email]);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }
}
