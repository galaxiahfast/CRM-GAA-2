<x-guest-layout>
    <x-authentication-card compact>
        <!-- Footer superior: Nombre empresa izquierda | DataMID del Sureste derecha -->
        <div class="absolute inset-x-4 top-6 flex items-center justify-between text-[12px] font-semibold italic text-[#1A3A6B]/70 sm:inset-x-10">
            <span class="whitespace-nowrap">González Alonzo y Asociados S.C.P</span>
            <span class="whitespace-nowrap">DataMID del Sureste</span>
        </div>

        <!-- Contenido centrado -->
        <div class="flex min-h-[400px] flex-col items-center justify-center">
            <div class="w-full max-w-[420px] text-center">
                <div class="mb-6">
                    <h1 class="text-[22px] font-semibold tracking-[0.01em] text-[#1A3A6B]">Inicio de sesión</h1>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200/80 bg-red-50/80 px-4 py-3 text-[15px] text-red-700" role="alert">
                        Revisa el correo y la contraseña e intenta nuevamente.
                    </div>
                @endif

                @session('status')
                    <div class="mb-6 rounded-lg border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-[15px] text-emerald-700" role="status">
                        {{ $value }}
                    </div>
                @endsession

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div class="text-left">
                        <label for="email" class="block text-[15px] font-medium text-[#1A3A6B]">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            class="auth-login-input mt-[10px] block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 text-[12px] text-[#000000] shadow-none placeholder:text-[12px] placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0"
                            placeholder="nombre@empresa.com">
                    </div>

                    <div class="mt-[20px] text-left" x-data="{ passwordVisible: false }">
                        <label for="password" class="block text-[15px] font-medium text-[#1A3A6B]">Contraseña</label>
                        <div class="relative mt-[10px]">
                            <input id="password" :type="passwordVisible ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                class="auth-login-input block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 pr-11 text-[12px] text-[#000000] shadow-none placeholder:text-[12px] placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0"
                                placeholder="••••••••">
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#1A3A6B]/70 hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                @click="passwordVisible = !passwordVisible"
                                :aria-label="passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                :title="passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                <svg x-show="!passwordVisible" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12z" />
                                    <circle cx="12" cy="12" r="2.75" stroke-width="1.8" />
                                </svg>
                                <svg x-show="passwordVisible" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.6 6.15A10.5 10.5 0 0112 6c6.25 0 9.75 6 9.75 6a15.5 15.5 0 01-2.1 2.72M6.15 6.15C3.55 8.02 2.25 12 2.25 12S5.75 18 12 18c1.48 0 2.79-.34 3.93-.88M9.88 9.88A3 3 0 0014.12 14.12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 mt-[20px]">
                        <label for="remember_me" class="flex cursor-pointer items-center gap-2 text-[12px] text-[#000000] whitespace-nowrap">
                            <input id="remember_me" name="remember" type="checkbox" class="h-3.5 w-3.5 rounded border-[#1A3A6B]/50 bg-transparent text-[#1A3A6B] focus:ring-0 focus:ring-offset-0">
                            <span>Mantener sesión iniciada</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="border-b border-transparent text-right text-[12px] font-semibold text-[#1A3A6B] whitespace-nowrap transition-colors hover:border-[#1A3A6B]/40 hover:text-[#15305A] focus:outline-none">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-[0_8px_24px_rgba(26,58,107,0.24)] transition-colors hover:bg-[#15305A] focus:outline-none focus:ring-0">
                        Iniciar sesión
                    </button>
                </form>

                @if (Route::has('register'))
                    <p class="mt-5 text-center text-[12px] text-[#000000]">
                        ¿No tienes cuenta?
                        <a href="{{ route('register') }}" class="font-semibold text-[#1A3A6B] hover:text-[#15305A]">Regístrate aquí</a>
                    </p>
                @endif
            </div>
        </div>

        <!-- Footer inferior en una sola línea con negritas y cursivas -->
        <div class="absolute inset-x-4 bottom-6 flex items-center justify-between gap-4 text-[12px] font-semibold italic leading-4 text-[#1A3A6B]/70 sm:inset-x-10">
            <span class="whitespace-nowrap">Políticas de privacidad</span>
            <span class="whitespace-nowrap">&copy; {{ now()->year }} Todos los derechos reservados</span>
        </div>
    </x-authentication-card>
</x-guest-layout>