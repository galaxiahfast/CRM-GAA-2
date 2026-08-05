<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo"><x-authentication-card-logo /></x-slot>

        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-[14px] font-medium text-[#1A3A6B] hover:text-[#2F80B7]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            Volver al inicio de sesión
        </a>

        <div class="mt-7 flex h-12 w-12 items-center justify-center rounded-xl bg-[#E8EEF7] text-[#1A3A6B]">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.74 5.74L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.59a1 1 0 01.29-.7l5.97-5.97A6 6 0 1121 9z" /></svg>
        </div>
        <h1 class="mt-5 text-3xl font-semibold tracking-tight text-gray-900">Recupera tu acceso</h1>
        <p class="mt-3 text-[15px] leading-6 text-gray-500">
            Escribe exactamente el correo registrado. Te enviaremos un enlace temporal para crear una nueva contraseña.
        </p>

        @session('status')
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[15px] text-emerald-700" role="status">{{ $value }}</div>
        @endsession

        @error('email')
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[15px] text-red-700" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-[15px] font-medium text-gray-700">Correo electrónico registrado</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15"
                    placeholder="nombre@empresa.com">
            </div>
            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-sm hover:bg-[#15305A] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B] focus:ring-offset-2">
                Enviar enlace seguro
            </button>
        </form>

        <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-[14px] leading-6 text-gray-600">
            Por seguridad, nunca enviamos contraseñas actuales por correo.
        </div>
    </x-authentication-card>
</x-guest-layout>
