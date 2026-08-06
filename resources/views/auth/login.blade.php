<x-guest-layout>
    <x-authentication-card compact>
        <a href="/" class="mx-auto flex w-fit flex-col items-center rounded-xl font-serif text-white focus:outline-none focus:ring-2 focus:ring-white/80" aria-label="González Alonzo y Asociados">
            <span class="text-[34px] font-bold leading-[0.88] tracking-[-0.03em] sm:text-[38px]">GONZÁLEZ</span>
            <span class="text-[34px] font-bold leading-[0.92] tracking-[-0.03em] sm:text-[38px]">ALONZO</span>
            <span class="mt-1 text-[15px] font-semibold tracking-[0.04em] sm:text-[16px]">Y ASOCIADOS S.C.P</span>
        </a>

        <div class="mt-6 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-white">Inicia sesión</h1>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-300/30 bg-red-400/10 px-4 py-3 text-[15px] text-red-100" role="alert">
                Revisa el correo y la contraseña e intenta nuevamente.
            </div>
        @endif

        @session('status')
            <div class="mt-6 rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-3 text-[15px] text-emerald-100" role="status">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-[14px] font-medium text-blue-50">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="mt-2 block h-12 w-full rounded-xl border border-white/25 bg-white/10 px-4 text-[15px] text-white shadow-sm placeholder:text-blue-100/45 focus:border-blue-200/70 focus:outline-none focus:ring-2 focus:ring-blue-200/15"
                    placeholder="nombre@empresa.com">
            </div>

            <div>
                <label for="password" class="block text-[14px] font-medium text-blue-50">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="mt-2 block h-12 w-full rounded-xl border border-white/25 bg-white/10 px-4 text-[15px] text-white shadow-sm placeholder:text-blue-100/45 focus:border-blue-200/70 focus:outline-none focus:ring-2 focus:ring-blue-200/15"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between gap-4">
                <label for="remember_me" class="flex cursor-pointer items-center gap-2 text-[13px] text-blue-100/85">
                    <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-white/30 bg-white/10 text-[#2F80B7] focus:ring-blue-200/40">
                    <span>Recordar sesión</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-right text-[13px] font-medium text-blue-100 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-100/30">
                        Olvidé mi contraseña
                    </a>
                @endif
            </div>

            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-white px-5 text-[15px] font-semibold text-[#102746] shadow-sm transition-colors hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#102746]">
                Iniciar sesión
            </button>
        </form>

        <x-auth-social-buttons dark />

        @if (Route::has('register'))
            <p class="mt-5 text-center text-[13px] text-blue-100/75">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-white hover:text-blue-100">Registrarse</a>
            </p>
        @endif
    </x-authentication-card>
</x-guest-layout>