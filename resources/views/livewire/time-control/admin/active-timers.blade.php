<div class="min-h-[calc(100dvh-90px)] min-w-[1300px] bg-gray-100 text-[15px] text-gray-700">
    <x-time-admin-tabs active="online" />

    <header class="flex items-center justify-between gap-20 whitespace-nowrap border-b border-gray-200 px-[40px] py-[25px]">
        <div class="flex items-center gap-[15px] text-gray-500">
            <span class="font-medium">Actividades</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-medium">Control de Horas</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-medium">Supervisión de Horas</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-semibold text-[#1A3A6B]">Actividad en línea</span>
        </div>

        <span class="inline-flex items-center gap-[10px] text-[12px] font-medium text-gray-500">
            <span class="relative flex h-3 w-3 items-center justify-center">
                <span class="absolute h-3 w-3 animate-ping rounded-full bg-[#1A3A6B]/20"></span>
                <span class="relative h-1.5 w-1.5 rounded-full bg-[#1A3A6B]"></span>
            </span>
            Actualización automática
        </span>
    </header>

    <main class="mx-auto w-full max-w-[1500px] space-y-[25px] px-[40px] py-[25px]" wire:poll.3s.visible="refreshActiveTimers">
        <section class="flex items-end justify-between gap-[25px]">
            <div class="flex items-start gap-[15px]">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-[#cbd9ed] text-[#1A3A6B]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 100-16 8 8 0 000 16z" /></svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Actividad en línea</h1>
                    <p class="mt-1 text-gray-500">Consulta quién está trabajando, su actividad y contexto organizacional.</p>
                </div>
            </div>

            <div class="text-right">
                <p class="text-[24px] font-bold leading-none text-[#1A3A6B]">{{ count($activeTimers) }}</p>
                <p class="mt-[10px] text-[12px] font-medium text-gray-500">{{ count($activeTimers) === 1 ? 'cronómetro activo' : 'cronómetros activos' }}</p>
            </div>
        </section>

        <section class="grid h-[640px] min-h-0 grid-cols-[290px_minmax(0,1fr)] overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-[0_8px_24px_rgba(15,35,66,0.06)]">
            <aside class="border-r border-gray-200">
                <div class="flex h-[74px] items-center gap-[15px] border-b border-gray-200 px-[25px] py-[15px]">
                    <span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#1A3A6B] text-white">
                        <svg class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 13a4 4 0 100-8 4 4 0 000 8zm7 7a7 7 0 00-14 0" /></svg>
                        <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-gray-100"></span>
                    </span>
                    <div>
                        <p class="font-semibold text-gray-800">Supervisión en vivo</p>
                        <p class="mt-0.5 text-[12px] text-gray-500">Sincronización activa</p>
                    </div>
                </div>

                <div class="space-y-[25px] p-[25px]">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Estado mostrado</p>
                        <div class="mt-[15px] flex items-center gap-[10px]">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-gray-700">Cronómetro ejecutándose</span>
                        </div>
                    </div>

                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Actualización</p>
                        <p class="mt-[10px] leading-6 text-gray-500">La lista consulta el servidor cada 3 segundos y los contadores avanzan cada segundo.</p>
                    </div>

                    <div class="border-t border-gray-200 pt-[25px]">
                        <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Vista operativa</p>
                        <p class="mt-[10px] leading-6 text-gray-500">Identifica al colaborador, su actividad, tiempo activo, puesto, área y equipo relacionado.</p>
                    </div>
                </div>
            </aside>

            <div class="flex min-h-0 flex-col">
                <header class="flex h-[74px] items-center justify-between gap-[25px] border-b border-gray-200 px-[25px] py-[15px]">
                    <div>
                        <h2 class="font-semibold text-gray-800">Personas con cronómetro activo</h2>
                        <p class="mt-0.5 text-[12px] text-gray-500">Seguimiento operativo actualizado en tiempo real</p>
                    </div>
                    <span class="text-[12px] font-medium text-gray-500">{{ count($activeTimers) }} {{ count($activeTimers) === 1 ? 'persona activa' : 'personas activas' }}</span>
                </header>

                <div class="active-timers-scrollbar min-h-0 flex-1 overflow-y-auto p-[25px]">
                    @forelse ($activeTimers as $timer)
                        <article
                            wire:key="active-timer-{{ $timer['id'] }}"
                            data-active-timer
                            class="mb-[12px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_3px_12px_rgba(15,35,66,0.05)] last:mb-0"
                        >
                            <div class="grid min-h-[108px] grid-cols-[minmax(280px,0.95fr)_minmax(320px,1.25fr)_190px]">
                            <div class="flex min-w-0 items-center gap-[15px] px-[20px] py-[18px]">
                                <span class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-visible rounded-full bg-[#1A3A6B] text-[12px] font-semibold text-white">
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

                            <div class="flex min-w-0 items-center gap-[15px] border-l border-gray-200 px-[20px] py-[18px]">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#f3f6fa] text-[#1A3A6B]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0a3 3 0 006 0m-6 7h6m-6 4h4" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">Actividad actual</p>
                                    <p class="mt-[5px] truncate font-semibold text-gray-800" title="{{ $timer['activity'] }}">{{ $timer['activity'] }}</p>
                                    <p class="mt-[3px] truncate text-[12px] text-gray-500" title="{{ $timer['customer'] }}">{{ $timer['customer'] }}</p>
                                </div>
                            </div>

                            <div
                                wire:ignore
                                class="flex flex-col items-end justify-center border-l border-gray-200 bg-[#f7faff] px-[20px] py-[18px] text-right"
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
                                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">Tiempo activo</p>
                                <p class="mt-[7px] font-mono text-[16px] font-bold tabular-nums text-[#1A3A6B]" x-text="formatted()"></p>
                                <p class="mt-[5px] text-[12px] text-gray-500">Desde {{ $timer['started_at'] }}</p>
                            </div>
                            </div>

                            <div class="grid grid-cols-[0.85fr_0.85fr_1fr_1.3fr] border-t border-gray-200 bg-gray-50/70">
                                <div class="min-w-0 px-[20px] py-[14px]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Puesto</p>
                                    <p class="mt-[6px] truncate text-[12px] font-medium text-gray-700" title="{{ $timer['position'] }}">{{ $timer['position'] }}</p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 px-[20px] py-[14px]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Área</p>
                                    <p class="mt-[6px] truncate text-[12px] font-medium text-gray-700" title="{{ $timer['area'] }}">{{ $timer['area'] }}</p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 px-[20px] py-[14px]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Jefe directo</p>
                                    <p class="mt-[6px] truncate text-[12px] font-medium text-gray-700" title="{{ collect($timer['superiors'])->pluck('name')->join(', ') }}">
                                        {{ collect($timer['superiors'])->pluck('name')->join(', ') ?: 'Sin jefe asignado' }}
                                    </p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 px-[20px] py-[14px]">
                                    <div class="flex items-center justify-between gap-[10px]">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Subordinados</p>
                                        @if (count($timer['subordinates']) > 0)
                                            <span class="rounded-full bg-[#e8f0fb] px-2 py-0.5 text-[10px] font-semibold text-[#1A3A6B]">{{ count($timer['subordinates']) }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-[6px] truncate text-[12px] font-medium text-gray-700" title="{{ collect($timer['subordinates'])->pluck('name')->join(', ') }}">
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
            </div>
        </section>
    </main>

    <style>
        html, body, .active-timers-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #f8fafc;
        }
        html::-webkit-scrollbar, body::-webkit-scrollbar, .active-timers-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        html::-webkit-scrollbar-track, body::-webkit-scrollbar-track, .active-timers-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb, .active-timers-scrollbar::-webkit-scrollbar-thumb {
            background: #1A3A6B;
            border-radius: 9999px;
        }
    </style>
</div>
