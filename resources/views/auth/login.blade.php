<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div>
            <p class="text-[15px] font-semibold uppercase tracking-[0.16em] text-[#2F80B7]">Bienvenido</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Inicia sesión</h1>
            <p class="mt-2 text-[15px] leading-6 text-gray-500">Ingresa tus datos para acceder al portal.</p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[15px] text-red-700" role="alert">
                Revisa el correo y la contraseña e intenta nuevamente.
            </div>
        @endif

        @session('status')
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[15px] text-emerald-700" role="status">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-[15px] font-medium text-gray-700">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15"
                    placeholder="nombre@empresa.com">
            </div>

            <div>
                <div class="flex items-center justify-between gap-4">
                    <label for="password" class="block text-[15px] font-medium text-gray-700">Contraseña</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[14px] font-medium text-[#1A3A6B] hover:text-[#2F80B7] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20">
                            Olvidé mi contraseña
                        </a>
                    @endif
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] text-gray-900 shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15"
                    placeholder="••••••••">
            </div>

            <label for="remember_me" class="flex cursor-pointer items-center gap-3 text-[15px] text-gray-600">
                <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#1A3A6B] focus:ring-[#1A3A6B]">
                <span>Recordar sesión</span>
            </label>

            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-sm transition-colors hover:bg-[#15305A] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B] focus:ring-offset-2">
                Iniciar sesión
            </button>
        </form>

        @if (Route::has('register'))
            <p class="mt-5 text-center text-[15px] text-gray-500">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-[#1A3A6B] hover:text-[#2F80B7]">Registrarse</a>
            </p>
        @endif

        <x-auth-social-buttons />
    </x-authentication-card>
</x-guest-layout>
