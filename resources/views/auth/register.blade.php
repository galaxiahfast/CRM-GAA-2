<x-guest-layout>
    <x-authentication-card compact>
        <h1 class="text-center text-[22px] font-semibold tracking-tight text-slate-900">Crear cuenta</h1>
        <p class="mt-2 text-center text-[14px] text-slate-500">Tu cuenta se creará con el rol de Becario.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-[14px] leading-5 text-red-700" role="alert">
                <ul class="space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-[14px] font-medium text-slate-800">Nombre completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                    class="auth-login-input mt-[15px] block h-11 w-full rounded-lg border border-slate-300 bg-white-full px-3.5 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none">
            </div>

            <div>
                <label for="email" class="block text-[14px] font-medium text-slate-800">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    class="auth-login-input mt-[15px] block h-11 w-full rounded-lg border border-slate-300 bg-white-full px-3.5 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none">
            </div>

            <div x-data="{ visible: false }">
                <label for="password" class="block text-[14px] font-medium text-slate-800">Contraseña</label>
                <div class="relative mt-[15px]">
                    <input id="password" :type="visible ? 'text' : 'password'" name="password" required autocomplete="new-password"
                        class="auth-login-input block h-11 w-full rounded-lg border border-slate-300 bg-white-full pl-3.5 pr-11 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none">
                    <button type="button" @click="visible = !visible" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" :title="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-[#1A3A6B] focus:outline-none focus-visible:text-[#1A3A6B]">
                        <svg x-show="!visible" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.7"/></svg>
                        <svg x-show="visible" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 3l18 18M10.6 6.15A10.5 10.5 0 0112 6c6.25 0 9.75 6 9.75 6a15.5 15.5 0 01-2.1 2.72M6.15 6.15C3.55 8.02 2.25 12 2.25 12S5.75 18 12 18c1.48 0 2.79-.34 3.93-.88M9.88 9.88A3 3 0 0014.12 14.12"/></svg>
                    </button>
                </div>
            </div>

            <div x-data="{ visible: false }">
                <label for="password_confirmation" class="block text-[14px] font-medium text-slate-800">Confirmar contraseña</label>
                <div class="relative mt-[15px]">
                    <input id="password_confirmation" :type="visible ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                        class="auth-login-input block h-11 w-full rounded-lg border border-slate-300 bg-white-full pl-3.5 pr-11 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none">
                    <button type="button" @click="visible = !visible" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" :title="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-[#1A3A6B] focus:outline-none focus-visible:text-[#1A3A6B]">
                        <svg x-show="!visible" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.7"/></svg>
                        <svg x-show="visible" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 3l18 18M10.6 6.15A10.5 10.5 0 0112 6c6.25 0 9.75 6 9.75 6a15.5 15.5 0 01-2.1 2.72M6.15 6.15C3.55 8.02 2.25 12 2.25 12S5.75 18 12 18c1.48 0 2.79-.34 3.93-.88M9.88 9.88A3 3 0 0014.12 14.12"/></svg>
                    </button>
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <label class="flex items-start gap-2.5 text-[14px] leading-5 text-slate-700">
                    <input type="checkbox" name="terms" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1A3A6B] focus:ring-2 focus:ring-[#1A3A6B]/20 focus:ring-offset-0">
                    <span>Acepto los términos de servicio y la política de privacidad.</span>
                </label>
            @endif

            <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-medium text-white transition-colors hover:bg-[#15305A] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#1A3A6B]/25">Crear cuenta</button>
        </form>

        <x-slot name="footer">
            <p class="mt-4 rounded-xl border border-slate-200 bg-white-full px-4 py-4 text-center text-[14px] text-slate-700">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="text-[#1A3A6B] hover:underline">Iniciar sesión</a>
            </p>
        </x-slot>
    </x-authentication-card>
</x-guest-layout>
