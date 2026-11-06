<x-guest-layout>
    <x-authentication-card compact>
        <h1 class="text-center text-[22px] font-semibold tracking-tight text-slate-900">Confirma tu correo</h1>

        <p class="mt-5 text-[15px] leading-6 text-slate-700">
            Te enviamos un enlace de confirmación a <span class="text-slate-900">{{ Auth::user()->email }}</span>.
            Ábrelo desde tu correo para terminar de entrar. El enlace vence en una hora.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-[14px] leading-5 text-emerald-700" role="status">
                Te enviamos un nuevo enlace de confirmación.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="flex h-11 w-full items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-medium text-white transition-colors hover:bg-[#15305A] focus:outline-none focus:ring-0">
                Reenviar el correo de confirmación
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-[14px] text-[#1A3A6B] hover:underline focus:outline-none focus:ring-0">Cerrar sesión</button>
        </form>
    </x-authentication-card>
</x-guest-layout>
