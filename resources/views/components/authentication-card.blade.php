@props(['compact' => false])

<div
    class="auth-network relative w-full bg-[#EEF3F8] {{ $compact ? 'h-dvh overflow-auto px-4 py-4' : 'min-h-screen overflow-hidden px-4 py-8 sm:px-6 lg:px-8' }}"
    data-auth-particle-network
>
    <canvas class="pointer-events-none absolute inset-0 h-full w-full" data-auth-network-canvas aria-hidden="true"></canvas>
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),rgba(238,243,248,0.18)_78%)]"></div>

    @if ($compact)
        <main class="relative mx-auto flex min-h-[calc(100dvh-2rem)] w-[448px] pb-8">
            <section class="my-auto w-[448px] flex-none overflow-hidden rounded-[28px] border border-white/20 bg-[#102746]/25 px-10 py-8 text-white shadow-[0_28px_80px_rgba(15,35,66,0.20)] backdrop-blur-sm">
                {{ $slot }}
            </section>
        </main>
    @else
        <main class="relative mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-5xl items-center justify-center">
            <section class="grid w-full overflow-hidden rounded-2xl border border-white/70 bg-white/40 shadow-[0_24px_70px_rgba(15,35,66,0.18)] backdrop-blur-xl lg:grid-cols-[0.9fr_1.1fr]">
                <aside class="relative overflow-hidden bg-[#1A3A6B]/90 px-7 py-8 text-white backdrop-blur-md sm:px-10 lg:flex lg:min-h-[680px] lg:flex-col lg:justify-between lg:px-12 lg:py-12">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full border border-white/10"></div>
                    <div class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 rounded-full border border-white/10"></div>

                    <div class="relative">
                        {{ $logo }}

                        <div class="mt-8 hidden lg:block">
                            <p class="text-[15px] font-semibold uppercase tracking-[0.18em] text-blue-200">Portal corporativo</p>
                            <h2 class="mt-4 text-3xl font-semibold leading-tight">Tu operación, organizada en un solo lugar.</h2>
                            <p class="mt-4 max-w-sm text-[15px] leading-7 text-blue-100/90">
                                Accede de forma segura a clientes, actividades, productividad y herramientas administrativas.
                            </p>
                        </div>
                    </div>

                    <div class="relative mt-6 hidden border-t border-white/15 pt-6 text-[15px] text-blue-100 lg:block">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.25-4.5A11.95 11.95 0 0112 2.25 11.95 11.95 0 013.75 5.5 11.95 11.95 0 003 9.75c0 5.04 3.15 9.34 7.59 11.05a3.9 3.9 0 002.82 0C17.85 19.09 21 14.79 21 9.75c0-1.5-.27-2.93-.75-4.25z" />
                                </svg>
                            </span>
                            <span>Acceso protegido y gestión confiable</span>
                        </div>
                    </div>
                </aside>

                <div class="flex min-h-[620px] items-center bg-white/20 px-6 py-9 backdrop-blur-sm sm:px-10 lg:px-14 lg:py-12">
                    <div class="mx-auto w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </main>
    @endif

    <!-- Footer flotante: no ocupa espacio ni modifica el alto del login. -->
    <footer class="pointer-events-none inset-x-0 bottom-5 text-center text-[13px] {{ $compact ? 'fixed text-[#1A3A6B]/70' : 'absolute text-gray-500' }}">
        &copy; {{ now()->year }} DataMID. Todos los derechos reservados.
    </footer>
</div>
