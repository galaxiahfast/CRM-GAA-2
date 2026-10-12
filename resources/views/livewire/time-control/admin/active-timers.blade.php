<div class="supervision-online-uniform min-h-[calc(100dvh-90px)] min-w-[1300px] bg-white text-[15px] text-zinc-700">
    <style>
        .supervision-online-uniform :where(p, span, small, label, input, textarea, button, a, strong, h2, h3, th, td) {
            font-size: 15px !important;
        }
    </style>
    <main class="mx-auto w-full max-w-[1500px] space-y-[20px] px-[50px] py-[50px]" wire:poll.3s.visible="refreshActiveTimers">
        <section class="flex items-center justify-between gap-[20px]">
            <div class="flex items-center gap-[20px]">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 100-16 8 8 0 000 16z" /></svg>
                </span>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-black">Supervisión de Horas</h1>
                    <p class="mt-[5px] text-zinc-500">Consulta, compara y exporta las horas registradas por los colaboradores.</p>
                </div>
            </div>
            <nav class="flex items-center gap-[20px]" aria-label="Secciones de Supervisión de Horas">
                <a href="{{ route('time.admin.dashboard') }}" class="inline-flex items-center gap-[10px] text-[15px] font-normal text-zinc-500"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM4 10h16M9 10v9"/></svg>Informe general</a>
                <span class="inline-flex items-center gap-[10px] text-[15px] font-semibold text-black"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></svg>Actividad en línea</span>
                <a href="{{ route('time.admin.dashboard', ['section' => 'results']) }}" class="inline-flex items-center gap-[10px] text-[15px] font-normal text-zinc-500"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M6 16l4-4 3 2 5-7"/></svg>Resultado del reporte</a>
            </nav>
        </section>

        <section class="flex h-[700px] min-h-0 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none">
            <header class="flex min-h-[80px] items-center justify-between gap-[30px] border-b border-zinc-200 px-[25px] py-[16px]">
                <div>
                    <h2 class="font-semibold text-black">Actividad en línea</h2>
                    <p class="mt-[5px] text-zinc-500">Seguimiento en tiempo real de los cronómetros activos.</p>
                </div>
                <div class="flex items-center gap-[28px] text-black">
                    <span class="inline-flex items-center gap-[10px] whitespace-nowrap">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <strong class="font-semibold">{{ $showingDemoTimers ? 'Ejemplo local' : 'Sincronización activa' }}</strong>
                    </span>
                    <span class="inline-flex items-center gap-[10px] whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87m-1-12a4 4 0 010 7.75" /></svg>
                        <strong class="font-semibold">{{ count($activeTimers) }} {{ count($activeTimers) === 1 ? 'persona activa' : 'personas activas' }}</strong>
                    </span>
                    <span class="inline-flex items-center gap-[10px] whitespace-nowrap text-zinc-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 100-16 8 8 0 000 16z" /></svg>
                        Actualización cada 3 segundos
                    </span>
                </div>
            </header>

            <div class="grid min-h-0 flex-1 grid-cols-[320px_minmax(0,1fr)]">
                <aside class="border-r border-zinc-200 bg-zinc-50/40">
                <div class="flex h-[74px] items-center gap-[12px] border-b border-gray-200 px-[20px] py-[12px]">
                    <span class="relative grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-zinc-900 text-white">
                        <svg class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 13a4 4 0 100-8 4 4 0 000 8zm7 7a7 7 0 00-14 0" /></svg>
                        <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-gray-100"></span>
                    </span>
                    <div class="min-w-0">
                        <p class="whitespace-nowrap font-semibold text-gray-800">Referencia de actividad</p>
                        <p class="mt-[3px] whitespace-nowrap text-gray-500">Lectura en tiempo real</p>
                    </div>
                </div>

                <div class="space-y-[25px] p-[25px]">
                    <div>
                        <p class="font-semibold text-gray-800">Estado mostrado</p>
                        <div class="mt-[15px] flex items-center gap-[10px]">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-gray-700">Cronómetro ejecutándose</span>
                        </div>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800">Actualización</p>
                        <p class="mt-[10px] leading-6 text-gray-500">La lista consulta el servidor cada 3 segundos y los contadores avanzan cada segundo.</p>
                    </div>

                    <div class="border-t border-gray-200 pt-[25px]">
                        <p class="font-semibold text-gray-800">Vista operativa</p>
                        <p class="mt-[10px] leading-6 text-gray-500">Identifica al colaborador, su actividad, tiempo activo, puesto, área y equipo relacionado.</p>
                    </div>
                </div>
                </aside>

            <div class="flex min-h-0 flex-col">
                <header class="flex h-[74px] items-center justify-between gap-[25px] border-b border-zinc-200 px-[25px] py-[15px]">
                    <div>
                        <h3 class="font-semibold text-black">Personas con cronómetro activo</h3>
                        <p class="mt-0.5 text-gray-500">Consulta la actividad actual y el tiempo transcurrido.</p>
                    </div>
                    <span class="font-medium text-gray-500">Ordenados alfabéticamente</span>
                </header>

                <div class="grid h-[54px] shrink-0 grid-cols-[minmax(280px,0.95fr)_minmax(320px,1.25fr)_190px] border-b border-zinc-200 bg-zinc-100 font-semibold text-zinc-700">
                    <div class="flex items-center px-[25px]">Colaborador</div>
                    <div class="flex items-center border-l border-zinc-200 px-[20px]">Actividad actual</div>
                    <div class="flex items-center justify-end border-l border-zinc-200 px-[20px]">Tiempo activo</div>
                </div>

                <div class="active-timers-scrollbar min-h-0 flex-1 overflow-y-auto">
                    @forelse ($activeTimers as $timer)
                        <article
                            wire:key="active-timer-{{ $timer['id'] }}"
                            data-active-timer
                            class="overflow-hidden border-b border-zinc-200 bg-white last:border-b-0"
                        >
                            <div class="grid min-h-[92px] grid-cols-[minmax(280px,0.95fr)_minmax(320px,1.25fr)_190px]">
                            <div class="flex min-w-0 items-center gap-[15px] px-[25px] py-[15px]">
                                <span class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-visible rounded-full bg-zinc-900 text-[12px] font-semibold text-white">
                                    @if ($timer['photo_url'])
                                        <img src="{{ $timer['photo_url'] }}" alt="Foto de {{ $timer['name'] }}" class="h-full w-full rounded-full object-cover">
                                    @else
                                        {{ $timer['initials'] }}
                                    @endif
                                    <span class="absolute -right-0.5 top-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500" title="En línea"></span>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-800" title="{{ $timer['name'] }}">{{ $timer['name'] }}</p>
                                    <p class="mt-[6px] truncate text-[12px] text-gray-500" title="{{ $timer['email'] }}">{{ $timer['email'] }}</p>
                                </div>
                            </div>

                            <div class="flex min-w-0 items-center border-l border-gray-200 px-[20px] py-[15px]">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-800" title="{{ $timer['activity'] }}">{{ $timer['activity'] }}</p>
                                    <p class="mt-[6px] truncate text-gray-500" title="{{ $timer['customer'] }}">{{ $timer['customer'] }}</p>
                                </div>
                            </div>

                            <div
                                wire:ignore
                                class="flex flex-col items-end justify-center border-l border-gray-200 bg-zinc-50 px-[20px] py-[15px] text-right"
                                x-data="{
                                    seconds: {{ (int) $timer['elapsed_seconds'] }},
                                    interval: null,
                                    init() { this.interval = setInterval(() => this.seconds++, 1000) },
                                    destroy() { clearInterval(this.interval) },
                                    formatted() {
                                        const hours = Math.floor(this.seconds / 3600).toString().padStart(2, '0');
                                        const minutes = Math.floor((this.seconds % 3600) / 60).toString().padStart(2, '0');
                                        const seconds = (this.seconds % 60).toString().padStart(2, '0');
                                        return `${hours}:${minutes}:${seconds}`;
                                    }
                                }"
                            >
                                <p class="font-mono font-semibold tabular-nums text-black" x-text="formatted()"></p>
                                <p class="mt-[6px] text-gray-500">Desde {{ $timer['started_at'] }}</p>
                            </div>
                            </div>

                            <div class="grid min-h-[70px] grid-cols-[0.85fr_0.85fr_1fr_1.3fr] border-t border-gray-200 bg-zinc-50/70">
                                <div class="min-w-0 px-[20px] py-[12px]">
                                    <p class="font-semibold text-gray-500">Puesto</p>
                                    <p class="mt-[5px] truncate font-medium text-gray-700" title="{{ $timer['position'] }}">{{ $timer['position'] }}</p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 px-[20px] py-[12px]">
                                    <p class="font-semibold text-gray-500">Área</p>
                                    <p class="mt-[5px] truncate font-medium text-gray-700" title="{{ $timer['area'] }}">{{ $timer['area'] }}</p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 px-[20px] py-[12px]">
                                    <p class="font-semibold text-gray-500">Jefe directo</p>
                                    <p class="mt-[5px] truncate font-medium text-gray-700" title="{{ collect($timer['superiors'])->pluck('name')->join(', ') }}">
                                        {{ collect($timer['superiors'])->pluck('name')->join(', ') ?: 'Sin jefe asignado' }}
                                    </p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 px-[20px] py-[12px]">
                                    <div class="flex items-center justify-between gap-[10px]">
                                        <p class="font-semibold text-gray-500">Subordinados</p>
                                        @if (count($timer['subordinates']) > 0)
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-black">{{ count($timer['subordinates']) }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-[5px] truncate font-medium text-gray-700" title="{{ collect($timer['subordinates'])->pluck('name')->join(', ') }}">
                                        {{ collect($timer['subordinates'])->pluck('name')->join(', ') ?: 'Sin subordinados directos' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="flex h-full min-h-[420px] flex-col items-center justify-center px-[40px] text-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full border border-gray-200 text-gray-300">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 100-16 8 8 0 000 16z" /></svg>
                            </span>
                            <p class="mt-[15px] font-semibold text-gray-600">No hay cronómetros activos</p>
                            <p class="mt-[10px] max-w-md text-gray-400">Cuando un colaborador inicie una actividad aparecerá automáticamente en esta lista.</p>
                        </div>
                    @endforelse
                </div>
                <footer class="flex min-h-[58px] shrink-0 items-center justify-between gap-[25px] border-t border-zinc-200 bg-zinc-50 px-[25px]">
                    <div class="flex items-center gap-[20px]">
                        <strong class="font-semibold text-black">Referencia de estados</strong>
                        <span class="inline-flex items-center gap-[8px] text-zinc-600"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Cronómetro ejecutándose</span>
                    </div>
                    <span class="text-zinc-500">Los datos se sincronizan automáticamente.</span>
                </footer>
            </div>
            </div>
        </section>
    </main>

    <style>
        html, body, .active-timers-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #000 #fff;
        }
        html::-webkit-scrollbar, body::-webkit-scrollbar, .active-timers-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        html::-webkit-scrollbar-track, body::-webkit-scrollbar-track, .active-timers-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb, .active-timers-scrollbar::-webkit-scrollbar-thumb {
            background: #000;
            border-radius: 9999px;
        }
    </style>
</div>
