<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo"><x-authentication-card-logo /></x-slot>

        <p class="text-[15px] font-semibold uppercase tracking-[0.16em] text-[#2F80B7]">Nueva cuenta</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Regístrate</h1>
        <p class="mt-2 text-[15px] leading-6 text-gray-500">Tu cuenta iniciará con el perfil operativo básico de Auxiliar.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[15px] text-red-700" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-[15px] font-medium text-gray-700">Nombre completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15">
            </div>
            <div>
                <label for="email" class="block text-[15px] font-medium text-gray-700">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-[15px] font-medium text-gray-700">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-[15px] font-medium text-gray-700">Confirmar contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15">
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <label class="flex items-start gap-3 text-[14px] text-gray-600">
                    <input type="checkbox" name="terms" required class="mt-0.5 rounded border-gray-300 text-[#1A3A6B] focus:ring-[#1A3A6B]">
                    <span>Acepto los términos de servicio y la política de privacidad.</span>
                </label>
            @endif

            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-sm hover:bg-[#15305A] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B] focus:ring-offset-2">
                Crear cuenta
            </button>
        </form>

        <p class="mt-5 text-center text-[15px] text-gray-500">¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-[#1A3A6B] hover:text-[#2F80B7]">Iniciar sesión</a></p>

        <x-auth-social-buttons />
    </x-authentication-card>
</x-guest-layout>
