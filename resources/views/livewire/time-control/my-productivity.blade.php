@php
    $fmt = fn (int $seconds): string => sprintf('%02d:%02d:%02d', intdiv(max(0, $seconds), 3600), intdiv(max(0, $seconds) % 3600, 60), max(0, $seconds) % 60);
    $pct = fn (int $part, int $whole): int => $whole > 0 ? (int) round($part * 100 / $whole) : 0;
    $colIndex = array_flip($activityDetail['columns']);
    $idxInicio = $colIndex['Inicio'] ?? 0; $idxFin = $colIndex['Fin'] ?? 1;
    $idxActividad = $colIndex['Actividad'] ?? 2; $idxCliente = $colIndex['Cliente'] ?? 3;
    $idxTiempo = $colIndex['Tiempo efectivo'] ?? 4; $idxPuesto = $colIndex['Puesto profesional'] ?? 5;
    $idxArea = $colIndex['Área física'] ?? 6; $idxObservaciones = $colIndex['Observaciones'] ?? 7;
    $rowCounter = 0;
@endphp

<div data-clock-particle-network class="support-monochrome relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
    x-data="{ viewScale: 100, isFullscreen: false,
        init() { const saved = Number(localStorage.getItem('time-productivity-view-scale')); if (saved >= 70 && saved <= 100) this.viewScale = saved },
        saveScale() { localStorage.setItem('time-productivity-view-scale', String(this.viewScale)) },
        async toggleFullscreen(container) { if (document.fullscreenElement === container) return document.exitFullscreen(); if (document.fullscreenElement) await document.exitFullscreen(); await container.requestFullscreen() }
    }" @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root">
    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.55]" aria-hidden="true"></canvas>

    <div class="no-print absolute left-1/2 top-[20px] z-30 flex -translate-x-1/2 items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de Productividad">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span><span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen($root)" class="inline-flex p-[5px] text-black outline-none hover:bg-zinc-100 focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1200px] origin-top" :style="`width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">
        <header class="flex items-center justify-between gap-[20px] whitespace-nowrap border-b border-zinc-200 bg-white/75 p-[50px]">
            <div class="flex items-center gap-[15px] text-zinc-500"><span>Actividades</span><span class="text-zinc-300">&gt;</span><span>Control de horas</span><span class="text-zinc-300">&gt;</span><a href="{{ route('time.reports') }}" class="font-semibold text-black hover:text-zinc-600">Productividad</a></div>
            <div class="no-print flex items-center gap-[20px]">
                <button type="button" wire:click="export('pdf')" wire:loading.attr="disabled" class="inline-flex items-center gap-[10px] p-0 font-medium text-black hover:text-zinc-600 disabled:opacity-50"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>Descargar PDF</button>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-[10px] p-0 font-medium text-black hover:text-zinc-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10ZM9 7V3h6v4"/></svg>Imprimir</button>
            </div>
        </header>

        <main class="mx-auto w-full space-y-[20px] p-[50px]">
            <section class="flex items-center justify-between gap-[20px]">
                <div class="flex items-center gap-[20px]"><span class="flex h-14 w-14 items-center justify-center rounded-xl border border-zinc-200 bg-white/75 text-black"><svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg></span><div><h1 class="text-xl font-semibold text-black">Mi productividad</h1><p class="mt-[5px] text-zinc-500">Consulta y analiza el tiempo dedicado a tus clientes y actividades.</p></div></div>
                <div class="no-print flex gap-[10px]"><a href="{{ route('time.index') }}" class="inline-flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-normal text-black hover:bg-zinc-100">Cronómetro <span>›</span></a><a href="{{ route('time.dashboard') }}" class="inline-flex items-center gap-[10px] rounded-xl bg-black px-[20px] py-[15px] font-normal text-white hover:bg-zinc-800">Panel de control <span>›</span></a></div>
            </section>

            <section class="grid h-[520px] min-h-0 overflow-hidden rounded-xl border border-zinc-200 bg-white/65 shadow-[0_8px_24px_rgba(0,0,0,0.05)] xl:grid-cols-[380px_minmax(0,1fr)]">
                <aside class="box-border flex min-h-0 flex-col overflow-hidden border-b border-zinc-200 bg-white/55 p-[20px] xl:border-b-0 xl:border-r">
                    <div class="border-b border-zinc-200 pb-[20px]"><h2 class="font-semibold text-black">Periodo de consulta</h2><p class="mt-[5px] text-justify leading-6 text-zinc-500">Selecciona las fechas para ver tareas.</p></div>
                    <div class="mt-[20px] grid gap-[20px]">
                        <label class="grid gap-[10px] font-semibold text-black">Desde<input type="date" wire:model="from" class="w-full rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-normal text-black outline-none focus:border-zinc-400 focus:ring-0"></label>
                        <label class="grid gap-[10px] font-semibold text-black">Hasta<input type="date" wire:model="to" class="w-full rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-normal text-black outline-none focus:border-zinc-400 focus:ring-0"></label>
                        <button type="button" wire:click="applyFilters" class="inline-flex w-full items-center justify-center gap-[10px] rounded-xl bg-black px-[20px] py-[15px] font-normal text-white outline-none hover:bg-zinc-800 focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>Consultar periodo</button>
                    </div>
                    <div class="mt-auto border-t border-zinc-200 pt-[20px]"><p class="font-semibold text-black">Exportar información</p><div class="mt-[15px] flex gap-[10px]">@foreach ($exportFormats as $format)<button type="button" wire:click="export('{{ $format }}')" wire:loading.attr="disabled" class="inline-flex flex-1 justify-center rounded-xl border border-zinc-200 bg-white px-[15px] py-[10px] font-normal text-black hover:bg-zinc-100 disabled:opacity-50">{{ strtoupper($format) }}</button>@endforeach</div></div>
                </aside>

                <div class="box-border flex min-h-0 min-w-0 flex-col overflow-hidden bg-white/35 p-[20px]">
                    <div class="flex items-center justify-between gap-[20px] border-b border-zinc-200 pb-[20px]"><div><h2 class="font-semibold text-black">Resumen del periodo</h2><p class="mt-[5px] text-zinc-500">Distribución del tiempo registrado.</p></div><div class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px]"><span class="text-zinc-500">Horas efectivas</span><strong class="ml-[10px] font-semibold tabular-nums text-black">{{ $fmt($totalSeconds) }}</strong></div></div>
                    <div class="grid min-h-0 flex-1 overflow-hidden gap-[20px] pt-[20px] xl:grid-cols-2">
                        @foreach ([['title' => 'Distribución por cliente', 'items' => $byCustomer], ['title' => 'Distribución por actividad', 'items' => $byActivity]] as $distribution)
                            <section class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white/80 p-[20px]"><h3 class="shrink-0 font-semibold text-black">{{ $distribution['title'] }}</h3><div class="support-productivity-scrollbar -mr-[20px] mt-[20px] h-0 min-h-0 flex-1 space-y-[15px] overflow-y-auto pr-[20px]">
                                @forelse ($distribution['items'] as $name => $seconds)<div><div class="flex justify-between gap-[20px]"><span class="truncate text-black" title="{{ $name }}">{{ $name }}</span><strong class="shrink-0 font-semibold tabular-nums text-black">{{ $fmt($seconds) }}</strong></div><div class="mt-[10px] h-2 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-black" style="width: {{ $pct($seconds, $totalSeconds) }}%"></div></div><p class="mt-[5px] text-right text-zinc-500">{{ $pct($seconds, $totalSeconds) }}%</p></div>@empty<p class="rounded-xl border border-dashed border-zinc-200 p-[20px] text-center text-zinc-500">Sin datos en este periodo.</p>@endforelse
                            </div></section>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white/65 shadow-[0_8px_24px_rgba(0,0,0,0.05)]" x-data="{ expandedRows: new Set(), toggleRow(id) { this.expandedRows.has(id) ? this.expandedRows.delete(id) : this.expandedRows.add(id) }, isExpanded(id) { return this.expandedRows.has(id) } }">
                <header class="flex items-center justify-between gap-[20px] border-b border-zinc-200 bg-white/55 p-[20px]"><div><h2 class="font-semibold text-black">Detalle de actividades por día</h2><p class="mt-[5px] text-zinc-500">Consulta el registro completo del periodo seleccionado.</p></div><span class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-black">{{ count($entries) }} registros</span></header>
                <div class="support-productivity-scrollbar max-h-[520px] overflow-y-auto p-[20px]"><div class="space-y-[20px]">
                    @forelse ($activityDetail['groups'] as $group)
                        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white/80"><header class="flex justify-between gap-[20px] border-b border-zinc-200 bg-zinc-50 p-[20px]"><strong class="text-black">{{ $group['date'] }}</strong><div class="flex items-center gap-[20px]"><strong class="font-semibold text-black">{{ count($group['rows']) }} actividad{{ count($group['rows']) === 1 ? '' : 'es' }}</strong><span class="font-normal text-zinc-500">Tiempo total <strong class="ml-[10px] font-semibold tabular-nums text-black">{{ $group['total_effective'] }}</strong></span></div></header>
                            @foreach ($group['rows'] as $row) @php $rowId = $rowCounter++; @endphp
                                <article class="border-b border-zinc-200 last:border-b-0"><button type="button" @click="toggleRow({{ $rowId }})" class="grid w-full grid-cols-[150px_minmax(180px,1fr)_minmax(180px,1fr)_140px_24px] items-center gap-[20px] p-[20px] text-left outline-none hover:bg-zinc-50 focus:ring-0"><span class="text-zinc-500">{{ $row[$idxInicio] ?? '-' }} – {{ $row[$idxFin] ?? '-' }}</span><span class="truncate font-semibold text-black" title="{{ $row[$idxActividad] ?? '-' }}">{{ $row[$idxActividad] ?? '-' }}</span><span class="truncate text-black" title="{{ $row[$idxCliente] ?? '-' }}">{{ $row[$idxCliente] ?? '-' }}</span><span class="font-semibold tabular-nums text-black">{{ $row[$idxTiempo] ?? '00:00:00' }}</span><svg class="h-4 w-4 transition-transform" :class="isExpanded({{ $rowId }}) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19 9-7 7-7-7"/></svg></button>
                                    <div x-show="isExpanded({{ $rowId }})" x-collapse class="border-t border-zinc-200 bg-zinc-50 p-[20px]" style="display:none"><dl class="grid gap-[20px] xl:grid-cols-4"><div><dt class="font-semibold text-zinc-500">Tiempo total realizado</dt><dd class="mt-[5px] font-semibold tabular-nums text-black">{{ $row[$idxTiempo] ?? '00:00:00' }}</dd></div><div><dt class="font-semibold text-zinc-500">Puesto profesional</dt><dd class="mt-[5px] text-black">{{ $row[$idxPuesto] ?? '—' }}</dd></div><div><dt class="font-semibold text-zinc-500">Área física</dt><dd class="mt-[5px] text-black">{{ $row[$idxArea] ?? '—' }}</dd></div><div><dt class="font-semibold text-zinc-500">Observaciones</dt><dd class="mt-[5px] text-black">{{ $row[$idxObservaciones] ?? '—' }}</dd></div></dl></div>
                                </article>
                            @endforeach
                        </section>
                    @empty
                        <div class="flex min-h-[260px] flex-col items-center justify-center text-center text-zinc-500"><svg class="h-10 w-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg><p class="mt-[15px] font-semibold text-black">Sin actividades registradas</p><p class="mt-[5px]">Ajusta el periodo o inicia una actividad desde el cronómetro.</p></div>
                    @endforelse
                </div></div>
            </section>
        </main>
    </div>
    <style>
        .support-productivity-scrollbar { scrollbar-width: thin; scrollbar-color: #000 transparent; }
        .support-productivity-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .support-productivity-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .support-productivity-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 9999px; }
        @media print { #main-nav, #logo-sidebar, .no-print { display: none !important; } #main-content { margin-left: 0 !important; padding-top: 0 !important; } }
    </style>
</div>
