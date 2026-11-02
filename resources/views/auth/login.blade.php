<x-guest-layout>
    <x-authentication-card compact>
        <h1 class="text-center text-[22px] font-semibold tracking-tight text-slate-900">Iniciar sesión</h1>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-[14px] leading-5 text-red-700" role="alert">
                Revisa el correo y la contraseña e intenta nuevamente.
            </div>
        @endif

        @session('status')
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-[14px] leading-5 text-emerald-700" role="status">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-[14px] font-medium text-slate-800">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="auth-login-input mt-[15px] block h-11 w-full rounded-lg border bg-white-full px-3.5 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none {{ $errors->any() ? 'border-red-300' : 'border-slate-300' }}">
            </div>

            <div x-data="{ passwordVisible: false }">
                <div class="flex items-center justify-between gap-3">
                    <label for="password" class="block text-[14px] font-medium text-slate-800">Contraseña</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[13px] text-[#1A3A6B] hover:underline focus:outline-none focus-visible:underline">¿Olvidaste tu contraseña?</a>
                    @endif
                </div>
                <div class="relative mt-[15px]">
                    <input id="password" :type="passwordVisible ? 'text' : 'password'" name="password" required autocomplete="current-password"
                        class="auth-login-input block h-11 w-full rounded-lg border bg-white-full pl-3.5 pr-11 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none {{ $errors->any() ? 'border-red-300' : 'border-slate-300' }}">
                    <button type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-[#1A3A6B] focus:outline-none focus-visible:text-[#1A3A6B]"
                        @click="passwordVisible = !passwordVisible"
                        :aria-label="passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        :title="passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                        <svg x-show="!passwordVisible" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12z" />
                            <circle cx="12" cy="12" r="2.75" stroke-width="1.7" />
                        </svg>
                        <svg x-show="passwordVisible" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 3l18 18M10.6 6.15A10.5 10.5 0 0112 6c6.25 0 9.75 6 9.75 6a15.5 15.5 0 01-2.1 2.72M6.15 6.15C3.55 8.02 2.25 12 2.25 12S5.75 18 12 18c1.48 0 2.79-.34 3.93-.88M9.88 9.88A3 3 0 0014.12 14.12" />
                        </svg>
                    </button>
                </div>
            </div>

            <label for="remember_me" class="flex cursor-pointer items-center gap-2.5 text-[14px] text-slate-700">
                <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#1A3A6B] focus:ring-2 focus:ring-[#1A3A6B]/20 focus:ring-offset-0">
                <span>Mantener sesión iniciada</span>
            </label>

            <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-medium text-white transition-colors hover:bg-[#15305A] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#1A3A6B]/25">
                Iniciar sesión
            </button>
        </form>

        @if (Route::has('register'))
            <x-slot name="footer">
                <p class="mt-4 rounded-xl border border-slate-200 bg-white-full px-4 py-4 text-center text-[14px] text-slate-700">
                    ¿No tienes cuenta?
                    <a href="{{ route('register') }}" class="text-[#1A3A6B] hover:underline">Regístrate</a>
                </p>
            </x-slot>
        @endif
    </x-authentication-card>
</x-guest-layout>
