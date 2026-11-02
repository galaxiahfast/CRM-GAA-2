<x-guest-layout>
    <x-authentication-card compact>
        <h1 class="text-center text-[22px] font-semibold tracking-tight text-slate-900">Recuperar contraseña</h1>
        <p class="mt-2 text-center text-[14px] leading-5 text-slate-500">Te enviaremos un enlace para restablecerla.</p>

        @session('status')
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-[14px] leading-5 text-emerald-700" role="status">{{ $value }}</div>
        @endsession
        @error('email')
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-[14px] leading-5 text-red-700" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-[14px] font-medium text-slate-800">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="auth-login-input mt-[15px] block h-11 w-full rounded-lg border border-slate-300 bg-white-full px-3.5 text-[16px] text-slate-900 shadow-none placeholder:text-slate-400 focus:outline-none">
            </div>

            <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-medium text-white transition-colors hover:bg-[#15305A] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#1A3A6B]/25">Enviar enlace</button>
        </form>

        <x-slot name="footer">
            <p class="mt-4 rounded-xl border border-slate-200 bg-white-full px-4 py-4 text-center text-[14px] text-slate-700">
                ¿Recordaste tu contraseña?
                <a href="{{ route('login') }}" class="text-[#1A3A6B] hover:underline">Iniciar sesión</a>
            </p>
        </x-slot>
    </x-authentication-card>
</x-guest-layout>
