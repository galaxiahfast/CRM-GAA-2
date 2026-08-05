<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo"><x-authentication-card-logo /></x-slot>

        <p class="text-[15px] font-semibold uppercase tracking-[0.16em] text-[#2F80B7]">Acceso seguro</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Crea una nueva contraseña</h1>
        <p class="mt-2 text-[15px] leading-6 text-gray-500">El enlace es temporal y solo puede utilizarse para la cuenta indicada.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[15px] text-red-700" role="alert">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="block text-[15px] font-medium text-gray-700">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" readonly
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-gray-100 px-4 text-[15px] text-gray-600 shadow-sm focus:border-[#1A3A6B] focus:outline-none">
            </div>
            <div>
                <label for="password" class="block text-[15px] font-medium text-gray-700">Nueva contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15">
            </div>
            <div>
                <label for="password_confirmation" class="block text-[15px] font-medium text-gray-700">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="mt-2 block h-12 w-full rounded-xl border border-gray-300 bg-[#F7F8FA] px-4 text-[15px] shadow-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/15">
            </div>
            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-sm hover:bg-[#15305A] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B] focus:ring-offset-2">
                Actualizar contraseña
            </button>
        </form>
    </x-authentication-card>
</x-guest-layout>
