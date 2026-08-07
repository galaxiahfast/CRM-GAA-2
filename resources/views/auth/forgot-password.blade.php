<x-guest-layout>
    <x-authentication-card compact>
        <div class="absolute inset-x-4 top-6 flex items-center justify-between text-[12px] font-semibold italic text-[#1A3A6B]/70 sm:inset-x-10">
            <span class="whitespace-nowrap">González Alonzo y Asociados S.C.P</span>
            <span class="whitespace-nowrap">DataMID del Sureste</span>
        </div>

        <div class="flex min-h-[400px] flex-col items-center justify-center">
            <div class="w-full max-w-[420px] text-center">
                <div class="mb-6">
                    <h1 class="text-[22px] font-semibold tracking-[0.01em] text-[#1A3A6B]">Recuperar contraseña</h1>
                </div>

                @session('status')
                    <div class="mb-6 rounded-lg border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-[15px] text-emerald-700" role="status">{{ $value }}</div>
                @endsession
                @error('email')
                    <div class="mb-6 rounded-lg border border-red-200/80 bg-red-50/80 px-4 py-3 text-[15px] text-red-700" role="alert">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf
                    <div class="text-left">
                        <label for="email" class="block text-[15px] font-medium text-[#1A3A6B]">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            class="auth-login-input mt-[10px] block h-11 w-full rounded-lg border border-[#1A3A6B]/40 bg-transparent px-4 text-[12px] text-black shadow-none placeholder:text-[#7089A8] focus:border-[#1A3A6B] focus:bg-transparent focus:outline-none focus:ring-0"
                            placeholder="nombre@empresa.com">
                    </div>
                    <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-[0_8px_24px_rgba(26,58,107,0.24)] transition-colors hover:bg-[#15305A] focus:outline-none focus:ring-0">Enviar enlace</button>
                </form>

                <p class="mt-5 text-center text-[12px] text-black">¿Recordaste tu contraseña? <a href="{{ route('login') }}" class="font-semibold text-[#1A3A6B] hover:text-[#15305A]">Iniciar sesión</a></p>
            </div>
        </div>

        <div class="absolute inset-x-4 bottom-6 flex items-center justify-between gap-4 text-[12px] font-semibold italic leading-4 text-[#1A3A6B]/70 sm:inset-x-10">
            <span class="whitespace-nowrap">Políticas de privacidad</span>
            <span class="whitespace-nowrap">&copy; {{ now()->year }} Todos los derechos reservados</span>
        </div>
    </x-authentication-card>
</x-guest-layout>
