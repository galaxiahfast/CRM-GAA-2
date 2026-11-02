@props(['compact' => false])

{{-- Marco compartido de las pantallas de acceso: logo, tarjeta centrada y pie discreto. --}}
<div class="relative isolate flex min-h-dvh w-full flex-col items-center overflow-hidden bg-[#F4F6F9] px-4 py-8 sm:py-10" data-auth-particle-network>
    {{-- Fondo suave en movimiento (red de partículas desenfocada). --}}
    <canvas class="pointer-events-none absolute inset-0 -z-10 h-full w-full opacity-70 blur-[2px]" data-auth-network-canvas aria-hidden="true"></canvas>
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_center,rgba(244,246,249,0.9),rgba(244,246,249,0)_65%)]"></div>

    <div class="my-auto w-full max-w-[400px]">
        <a href="/" class="mx-auto block w-fit rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1A3A6B]/40 focus-visible:ring-offset-4">
            <img src="/img/static/logo-principal_.png" alt="González Alonzo y Asociados" class="h-auto w-[84px]">
        </a>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white-full p-6 sm:p-8">
            {{ $slot }}
        </div>

        {{ $footer ?? '' }}
    </div>

    <footer class="mt-8 flex flex-wrap justify-center gap-x-5 gap-y-1 text-center text-[12px] text-slate-500">
        <span>Políticas de privacidad</span>
        <span>&copy; {{ now()->year }} Todos los derechos reservados</span>
    </footer>
</div>
