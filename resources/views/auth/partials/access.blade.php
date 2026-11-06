{{--
    Inicio de sesión y registro en una misma vista: las pestañas cambian al instante y sin recargar.
    $initial indica qué pestaña muestra el servidor ('login' o 'register'); los errores y valores previos
    solo se pintan en ese formulario.
--}}
@php
    $canRegister = Route::has('register');
    $initial = $canRegister ? $initial : 'login';
    $loginErrors = $initial === 'login' && $errors->any();
    $registerErrors = $initial === 'register' && $errors->any();
    $googleReady = \App\Http\Controllers\Auth\GoogleLoginController::enabled();
    $githubReady = Route::has('auth.github.redirect');
@endphp

<div x-data="{
        tab: @js($initial),
        urls: @js(['login' => route('login'), 'register' => $canRegister ? route('register') : null]),
        go(next) {
            if (next === this.tab || !this.urls[next]) return;
            this.tab = next;
            history.replaceState(null, '', this.urls[next]);
            document.title = next === 'login' ? 'Iniciar sesión' : 'Crear cuenta';
            this.$nextTick(() => this.$refs[next + 'First']?.focus({ preventScroll: true }));
        },
    }">

    <div class="auth-heading auth-stack">
        <div @class(['is-off to-left' => $initial !== 'login']) :class="{ 'is-off to-left': tab !== 'login' }">
            <h1 class="auth-title">¡Bienvenido de nuevo!</h1>
            <p>Ingresa tus datos para continuar.</p>
        </div>
        @if ($canRegister)
            <div @class(['is-off to-right' => $initial !== 'register']) :class="{ 'is-off to-right': tab !== 'register' }">
                <p class="auth-title" role="heading" aria-level="1">Crea tu cuenta</p>
                <p>Tu cuenta se creará con el rol de Becario.</p>
            </div>
        @endif
    </div>

    @if ($canRegister)
        <nav class="auth-tabs" aria-label="Acceso">
            <a href="{{ route('login') }}" @class(['auth-tab', 'is-active' => $initial === 'login']) :class="{ 'is-active': tab === 'login' }" @click.prevent="go('login')">Iniciar sesión</a>
            <a href="{{ route('register') }}" @class(['auth-tab', 'is-active' => $initial === 'register']) :class="{ 'is-active': tab === 'register' }" @click.prevent="go('register')">Registrarse</a>
            <span class="auth-tab-indicator" aria-hidden="true" :style="tab === 'register' && 'transform: translateX(100%)'"
                @if ($initial === 'register') style="transform: translateX(100%)" @endif></span>
        </nav>
    @endif

    {{-- Los dos paneles comparten el mismo espacio: la altura es la del más alto y no cambia al cambiar de pestaña. --}}
    <div class="auth-stack">
    {{-- Iniciar sesión --}}
    <div @class(['is-off to-left' => $initial !== 'login']) :class="{ 'is-off to-left': tab !== 'login' }">
        @if ($loginErrors)
            <div class="auth-alert auth-alert-error" role="alert">
                {{ $errors->has('google') ? $errors->first('google') : 'Revisa el correo y la contraseña e intenta nuevamente.' }}
            </div>
        @endif

        @if ($initial === 'login')
            @session('status')
                <div class="auth-alert auth-alert-ok" role="status">{{ $value }}</div>
            @endsession
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form" x-data="{ sending: false, visible: false }" @submit="sending = true">
            @csrf

            <div>
                <label for="email" class="auth-label">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ $initial === 'login' ? old('email') : '' }}" required autocomplete="username"
                    @if ($initial === 'login') autofocus @endif x-ref="loginFirst"
                    placeholder="tu@empresa.com" class="auth-input">
            </div>

            <div>
                <label for="password" class="auth-label">Contraseña</label>
                <div class="auth-field">
                    <input id="password" type="password" :type="visible ? 'text' : 'password'" name="password" required autocomplete="current-password"
                        placeholder="••••••••" class="auth-input" style="padding-right: 96px">
                    <button type="button" class="auth-input-action" @click="visible = !visible"
                        :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-text="visible ? 'Ocultar' : 'Mostrar'">Mostrar</button>
                </div>
            </div>

            <div class="auth-row">
                <label for="remember_me" class="auth-check">
                    <input id="remember_me" name="remember" type="checkbox">
                    <span>Recordarme</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>
                @endif
            </div>

            <button type="submit" class="auth-submit" :disabled="sending">
                <span x-show="!sending">Iniciar sesión</span>
                <span x-show="sending" x-cloak>Entrando…</span>
            </button>
        </form>

        <div class="auth-divider">o continúa con</div>

        <div class="auth-social-grid">
            @if ($googleReady)
                <a href="{{ route('auth.google.redirect') }}" class="auth-social">
            @else
                <span class="auth-social" aria-disabled="true" title="Próximamente">
            @endif
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M23.5 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.56-5.17 3.56-8.81Z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.91l-3.88-3c-1.08.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.71-4.95H1.28v3.1A12 12 0 0 0 12 24Z"/>
                    <path fill="#FBBC05" d="M5.29 14.29a7.2 7.2 0 0 1 0-4.58v-3.1H1.28a12 12 0 0 0 0 10.78l4.01-3.1Z"/>
                    <path fill="#EA4335" d="M12 4.76c1.76 0 3.34.61 4.58 1.8l3.43-3.43C17.96 1.19 15.24 0 12 0A12 12 0 0 0 1.28 6.61l4.01 3.1C6.23 6.87 8.88 4.76 12 4.76Z"/>
                </svg>
                <span>Google</span>
            @if ($googleReady)
                    <span class="sr-only">Continuar con Google</span>
                </a>
            @else
                </span>
            @endif

            @if ($githubReady)
                <a href="{{ route('auth.github.redirect') }}" class="auth-social">
            @else
                <span class="auth-social" aria-disabled="true" title="Próximamente">
            @endif
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 .5a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.37-3.88-1.37-.52-1.33-1.28-1.69-1.28-1.69-1.05-.71.08-.7.08-.7 1.15.08 1.76 1.19 1.76 1.19 1.03 1.76 2.7 1.25 3.36.96.1-.75.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.28 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.17 1.18a10.9 10.9 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.81 1.19 1.83 1.19 3.09 0 4.42-2.69 5.39-5.25 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5Z"/>
                </svg>
                <span>GitHub</span>
            @if ($githubReady) </a> @else </span> @endif
        </div>
    </div>

    {{-- Registro --}}
    @if ($canRegister)
        <div @class(['is-off to-right' => $initial !== 'register']) :class="{ 'is-off to-right': tab !== 'register' }">
            @if ($registerErrors)
                <div class="auth-alert auth-alert-error" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="auth-form" @submit="sending = true"
                x-data="{
                    sending: false,
                    visible: false,
                    password: '',
                    levels: ['', 'Débil', 'Moderada', 'Buena', 'Fuerte'],
                    get score() {
                        const p = this.password;
                        if (!p) return 0;
                        let points = 0;
                        if (p.length >= 8) points++;
                        if (p.length >= 12) points++;
                        if (/[a-z]/.test(p) && /[A-Z]/.test(p)) points++;
                        if (/\d/.test(p)) points++;
                        if (/[^A-Za-z0-9]/.test(p)) points++;
                        if (p.length < 6) return 1;
                        return Math.max(1, Math.min(points, 4));
                    },
                }">
                @csrf

                <div>
                    <label for="register_name" class="auth-label">Nombre completo</label>
                    <input id="register_name" type="text" name="name" value="{{ $initial === 'register' ? old('name') : '' }}" required autocomplete="name"
                        @if ($initial === 'register') autofocus @endif x-ref="registerFirst"
                        placeholder="Nombre y apellidos" class="auth-input">
                </div>

                <div>
                    <label for="register_email" class="auth-label">Correo electrónico</label>
                    <input id="register_email" type="email" name="email" value="{{ $initial === 'register' ? old('email') : '' }}" required autocomplete="email"
                        placeholder="tu@empresa.com" class="auth-input">
                </div>

                <div>
                    <div class="auth-label-row">
                        <label for="register_password" class="auth-label">Contraseña</label>
                        <span class="auth-pw-meter" x-show="password.length" x-cloak :data-level="score">
                            <span class="auth-pw-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                            <span class="auth-pw-text" x-text="levels[score]" aria-live="polite"></span>
                        </span>
                    </div>
                    <div class="auth-field">
                        <input id="register_password" type="password" :type="visible ? 'text' : 'password'" name="password" required autocomplete="new-password"
                            placeholder="Crea tu contraseña" class="auth-input" style="padding-right: 96px" x-model="password">
                        <button type="button" class="auth-input-action" @click="visible = !visible"
                            :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-text="visible ? 'Ocultar' : 'Mostrar'">Mostrar</button>
                    </div>
                </div>

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <label class="auth-check auth-row">
                        <input type="checkbox" name="terms" required>
                        <span>Acepto los términos de servicio y la política de privacidad.</span>
                    </label>
                @endif

                <button type="submit" class="auth-submit" :disabled="sending">
                    <span x-show="!sending">Crear cuenta</span>
                    <span x-show="sending" x-cloak>Creando cuenta…</span>
                </button>
            </form>
        </div>
    @endif
    </div>
</div>
