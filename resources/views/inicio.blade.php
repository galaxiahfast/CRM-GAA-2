<x-app-layout>
    <div class="min-h-[calc(100vh-90px)] bg-[#F3F4F6] px-5 py-8 sm:px-8 lg:px-10">
        <div class="mx-auto max-w-6xl">
            <section class="overflow-hidden rounded-2xl border border-[#1A3A6B]/10 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-7 sm:px-8">
                    <p class="text-sm font-medium text-[#7089A8]">Sesión iniciada correctamente</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-[#1A3A6B]">
                        Hola, {{ auth()->user()->name }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                        Bienvenido al portal interno. Selecciona una opción para comenzar.
                    </p>
                </div>

                <div class="grid gap-4 p-6 sm:grid-cols-3 sm:p-8">
                    <a href="{{ route('dashboard') }}" class="group rounded-xl border border-[#1A3A6B]/15 bg-[#F7F9FC] p-5 transition hover:-translate-y-0.5 hover:border-[#1A3A6B]/35 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1A3A6B]/10 text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-14h6V3h-6v4Z" />
                            </svg>
                        </span>
                        <span class="mt-4 block text-base font-semibold text-[#1A3A6B]">Dashboard</span>
                        <span class="mt-2 block text-xs leading-5 text-slate-500">Consultar clientes, archivos e indicadores.</span>
                    </a>

                    <a href="{{ route('soporte.ticket') }}" class="group rounded-xl border border-[#1A3A6B]/15 bg-[#F7F9FC] p-5 transition hover:-translate-y-0.5 hover:border-[#1A3A6B]/35 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1A3A6B]/10 text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.142-4.03 7.5-9 7.5a10.2 10.2 0 0 1-4.15-.86L3 20.25l1.54-3.86A6.9 6.9 0 0 1 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                            </svg>
                        </span>
                        <span class="mt-4 block text-base font-semibold text-[#1A3A6B]">Soporte</span>
                        <span class="mt-2 block text-xs leading-5 text-slate-500">Solicitar ayuda mediante un ticket.</span>
                    </a>

                    <a href="{{ route('soporte.preguntas') }}" class="group rounded-xl border border-[#1A3A6B]/15 bg-[#F7F9FC] p-5 transition hover:-translate-y-0.5 hover:border-[#1A3A6B]/35 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1A3A6B]/10 text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c.678-.75 1.642-1.219 2.711-1.219 2.034 0 3.68 1.69 3.68 3.775 0 1.768-1.189 3.252-2.79 3.662-.555.142-.98.645-.98 1.218v.545M12.5 18.25h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <span class="mt-4 block text-base font-semibold text-[#1A3A6B]">Preguntas</span>
                        <span class="mt-2 block text-xs leading-5 text-slate-500">Consultar respuestas y orientación rápida.</span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
