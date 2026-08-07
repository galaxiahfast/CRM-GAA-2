<x-guest-layout>
    <x-authentication-card compact>
        <div class="absolute inset-x-4 top-6 flex items-center justify-between text-[12px] font-semibold italic text-[#1A3A6B]/70 sm:inset-x-10">
            <span class="whitespace-nowrap">González Alonzo y Asociados S.C.P</span>
            <span class="whitespace-nowrap">DataMID del Sureste</span>
        </div>

        <div class="flex min-h-[560px] flex-col items-center justify-center">
            <div class="w-full max-w-[420px] text-center">
                <div class="mb-6">
                    <h1 class="text-[22px] font-semibold tracking-[0.01em] text-[#1A3A6B]">Crear cuenta</h1>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200/80 bg-red-50/80 px-4 py-3 text-left text-[15px] text-red-700" role="alert">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <div class="text-left">
                        <label for="name" class="block text-[15px] font-medium text-[#1A3A6B]">Nombre completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                            class="auth-login-input mt-[10px] block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 text-[12px] text-black shadow-none placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0"
                            placeholder="Nombre completo">
                    </div>

                    <div class="text-left">
                        <label for="email" class="block text-[15px] font-medium text-[#1A3A6B]">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                            class="auth-login-input mt-[10px] block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 text-[12px] text-black shadow-none placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0"
                            placeholder="nombre@empresa.com">
                    </div>

                    <div class="text-left" x-data="{ visible: false }">
                        <label for="password" class="block text-[15px] font-medium text-[#1A3A6B]">Contraseña</label>
                        <div class="relative mt-[10px]">
                            <input id="password" :type="visible ? 'text' : 'password'" name="password" required autocomplete="new-password"
                                class="auth-login-input block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 pr-11 text-[12px] text-black shadow-none placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0" placeholder="••••••••">
                            <button type="button" @click="visible = !visible" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#1A3A6B]/70 hover:text-[#1A3A6B] focus:outline-none">
                                <svg x-show="!visible" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.8"/></svg>
                                <svg x-show="visible" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M6.15 6.15C3.55 8.02 2.25 12 2.25 12S5.75 18 12 18c1.48 0 2.79-.34 3.93-.88"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="text-left" x-data="{ visible: false }">
                        <label for="password_confirmation" class="block text-[15px] font-medium text-[#1A3A6B]">Confirmar contraseña</label>
                        <div class="relative mt-[10px]">
                            <input id="password_confirmation" :type="visible ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                                class="auth-login-input block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 pr-11 text-[12px] text-black shadow-none placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0" placeholder="••••••••">
                            <button type="button" @click="visible = !visible" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#1A3A6B]/70 hover:text-[#1A3A6B] focus:outline-none">
                                <svg x-show="!visible" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.8"/></svg>
                                <svg x-show="visible" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M6.15 6.15C3.55 8.02 2.25 12 2.25 12S5.75 18 12 18c1.48 0 2.79-.34 3.93-.88"/></svg>
                            </button>
                        </div>
                    </div>

                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <label class="flex items-start gap-2 text-left text-[12px] text-black">
                            <input type="checkbox" name="terms" required class="mt-0.5 h-3.5 w-3.5 rounded border-[#1A3A6B]/50 bg-transparent text-[#1A3A6B] focus:ring-0">
                            <span>Acepto los términos de servicio y la política de privacidad.</span>
                        </label>
                    @endif

                    <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-[0_8px_24px_rgba(26,58,107,0.24)] transition-colors hover:bg-[#15305A] focus:outline-none focus:ring-0">Crear cuenta</button>
                </form>

                <p class="mt-5 text-center text-[12px] text-black">¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-[#1A3A6B] hover:text-[#15305A]">Iniciar sesión</a></p>
            </div>
        </div>

        <div class="absolute inset-x-4 bottom-6 flex items-center justify-between gap-4 text-[12px] font-semibold italic leading-4 text-[#1A3A6B]/70 sm:inset-x-10">
            <span class="whitespace-nowrap">Políticas de privacidad</span>
            <span class="whitespace-nowrap">&copy; {{ now()->year }} Todos los derechos reservados</span>
        </div>
    </x-authentication-card>
</x-guest-layout>
