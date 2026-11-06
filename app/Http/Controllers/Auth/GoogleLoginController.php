<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInterns;
use App\Models\UserOrganizationalProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Inicio de sesión con Google.
 *
 * Confidencialidad: solo entran cuentas de Google con el correo verificado y de un dominio permitido
 * (GOOGLE_ALLOWED_DOMAINS); no se guarda ningún token ni foto de Google, solo el identificador de la cuenta;
 * y si el usuario tiene verificación en dos pasos activa, se le sigue pidiendo el código.
 *
 * Las cuentas nuevas empiezan siempre como becario (rol «Auxiliar») hasta que un administrador las modifique.
 * Las cuentas que ya existen (por ejemplo, las de administradores) conservan su rol y sus datos tal como están.
 *
 * Además de la verificación de Google, el correo se confirma con un enlace que llega al buzón (correo SMTP de la
 * empresa): hasta entonces la cuenta vinculada con Google no pasa de la pantalla de verificación.
 */
class GoogleLoginController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && config('services.google.allowed_domains', []) !== [];
    }

    public function redirect(): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $domains = config('services.google.allowed_domains', []);
        $hints = ['prompt' => 'select_account'];
        if (count($domains) === 1) {
            $hints['hd'] = $domains[0];
        }

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->with($hints)->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        if ($request->filled('error')) {
            return $this->fail('Se canceló el inicio de sesión con Google.');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::warning('Falló el inicio de sesión con Google.', ['message' => $exception->getMessage()]);

            return $this->fail('No se pudo iniciar sesión con Google. Intenta nuevamente.');
        }

        $raw = (array) $google->user;
        $email = Str::lower(trim((string) $google->getEmail()));
        $verified = filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOL);
        $domain = Str::after($email, '@');

        if ($email === '' || ! str_contains($email, '@') || ! $verified || ! in_array($domain, config('services.google.allowed_domains', []), true)) {
            Log::notice('Inicio de sesión con Google rechazado: correo no verificado o de un dominio no permitido.');

            return $this->fail('Esta cuenta de Google no tiene permiso para entrar. Usa tu correo de la empresa.');
        }

        $googleId = (string) $google->getId();

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $created = $user === null;

        try {
            $user = $created ? $this->createIntern($raw, $google->getName(), $email, $googleId) : $this->linkGoogle($user, $googleId);
            $this->askToVerifyEmail($user);
        } catch (Throwable $exception) {
            Log::error('No se pudo guardar la cuenta de Google.', ['message' => $exception->getMessage()]);

            return $this->fail('No se pudo crear tu cuenta. Contacta al administrador.');
        }

        Log::info('Inicio de sesión con Google.', ['user_id' => $user->id, 'created' => $created]);

        if (filled($user->two_factor_secret) && filled($user->two_factor_confirmed_at)) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    /** Manda el enlace de confirmación si el correo todavía no se ha confirmado; un fallo del correo no tumba el inicio de sesión. */
    private function askToVerifyEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el correo de confirmación.', ['user_id' => $user->id, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Cuenta nueva: becario, con su registro de becario y su perfil organizacional con el pago por hora y el apoyo
     * de comida iniciales (el administrador los ajusta después junto con el puesto y el área).
     *
     * @param  array<string, mixed>  $raw
     */
    private function createIntern(array $raw, ?string $fullName, string $email, string $googleId): User
    {
        $role = Role::query()->where('role', 'Auxiliar')->firstOrFail();

        return DB::transaction(function () use ($raw, $fullName, $email, $googleId, $role): User {
            $user = new User();
            $user->forceFill([
                'name' => trim((string) ($raw['given_name'] ?? '')) ?: trim((string) $fullName) ?: Str::before($email, '@'),
                'last_name' => trim((string) ($raw['family_name'] ?? '')) ?: null,
                'email' => $email,
                'google_id' => $googleId,
                'password' => Hash::make(Str::random(64)),
                'role_id' => $role->id,
            ])->save();

            // Quien se registra solo queda como su propio creador; un administrador puede cambiarlo después.
            UserInterns::create(['intern_id' => $user->id, 'created_by' => $user->id]);

            UserOrganizationalProfile::create([
                'user_id' => $user->id,
                'job_position_id' => null,
                'physical_area_id' => null,
                'hourly_rate' => (float) config('services.google.new_user_hourly_rate', 25),
                'food_allowance' => (float) config('services.google.new_user_food_allowance', 50),
                'valid_from' => now()->toDateString(),
                'is_active' => true,
            ]);

            return $user;
        });
    }

    /** Cuenta existente: se vincula con Google; su rol, permisos y demás datos no cambian. */
    private function linkGoogle(User $user, string $googleId): User
    {
        $user->forceFill(['google_id' => $googleId]);

        // Una cuenta sin correo confirmado pudo haberla creado otra persona con ese correo y una contraseña suya:
        // se invalida esa contraseña para que solo quien confirme el correo desde su buzón pueda entrar.
        if ($user->email_verified_at === null) {
            $user->forceFill(['password' => Hash::make(Str::random(64))]);
        }

        $user->save();

        return $user;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}
