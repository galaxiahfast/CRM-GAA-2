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

<div
    data-panel
    data-productivity
    class="relative isolate flex min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 flex-col text-[15px]"
>
    <x-panel-styles />

    <style>
        /* Mismo diseño que Cronómetro, Inicio y Panel general: texto negro (blanco en oscuro) y azul; enlaces y botones en verde. */
        [data-productivity] { --panel-muted: var(--panel-text); --prod-green: #15803d; }
        html.module-dark-theme [data-productivity] { --prod-green: #4ade80; }
        [data-productivity] .prod-link { color: var(--prod-green); transition: opacity 150ms ease; }
        [data-productivity] .prod-link:hover { opacity: .75; }
        [data-productivity] .prod-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--prod-green);
            border-radius: 12px;
            padding: 15px 20px;
            background-color: color-mix(in srgb, var(--prod-green) 12%, var(--panel-surface));
            color: var(--prod-green);
            cursor: pointer;
            transition: background-color 150ms ease;
        }
        [data-productivity] .prod-primary:hover { background-color: color-mix(in srgb, var(--prod-green) 20%, var(--panel-surface)); }
        [data-productivity] .prod-primary:disabled { cursor: not-allowed; opacity: .6; }
        [data-productivity] .prod-list-wrap { position: relative; min-height: 0; }
        [data-productivity] .prod-list { counter-reset: prodrow; }
        [data-productivity] .prod-item { counter-increment: prodrow; cursor: grab; }
        [data-productivity] .prod-counter::before { content: counter(prodrow); }
        /* Texto que puede partirse en varias líneas: 20px de espacio entre una línea y la siguiente */
        [data-productivity] .prod-lines { line-height: 35px; margin-bottom: -10px; }

        /* ===== Diseño adaptable: depende del ancho de cada tarjeta (el menú lateral y el zoom le quitan ancho) ===== */
        [data-productivity] .prod-wrap { container: prod / inline-size; }
        [data-productivity] .prod-layout { display: grid; grid-template-columns: minmax(0, 1fr); }
        [data-productivity] .prod-aside { min-width: 0; border-bottom-width: 1px; }
        [data-productivity] .prod-form { display: grid; gap: 20px; padding: 20px; }
        [data-productivity] .prod-dates { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
        [data-productivity] .prod-label { display: block; min-height: 24px; margin-bottom: 20px; line-height: 24px; }
        [data-productivity] .prod-main { display: flex; min-width: 0; flex-direction: column; }
        [data-productivity] .prod-dist { display: grid; grid-template-columns: minmax(0, 1fr); }
        [data-productivity] .prod-col { display: flex; min-width: 0; flex-direction: column; }
        [data-productivity] .prod-col + .prod-col { border-top-width: 1px; }
        [data-productivity] .prod-list { max-height: 360px; overflow-y: auto; }
        [data-productivity] .prod-grid { display: grid; grid-template-columns: 70px minmax(0, 1fr); align-items: stretch; }
        [data-productivity] .prod-index { display: flex; align-items: center; justify-content: center; border-right-width: 1px; }
        [data-productivity] .prod-cell { display: flex; min-width: 0; flex-direction: column; justify-content: center; padding: 20px; }
        [data-productivity] .prod-item { height: 70px; padding: 0; }
        [data-productivity] .prod-metrics { display: grid; grid-template-columns: minmax(0, 1fr) 76px minmax(40px, 80px) 40px; align-items: center; gap: 15px; height: 100%; box-sizing: border-box; padding-top: 14px; padding-bottom: 14px; }
        [data-productivity] .prod-item:nth-child(odd) { background-color: var(--panel-header); }
        [data-productivity] .prod-track { height: 6px; overflow: hidden; border-radius: 9999px; background-color: var(--panel-track); }
        [data-productivity] .prod-fill { height: 100%; border-radius: 9999px; background-color: var(--panel-accent); }
        /* El detalle muestra siempre 7 filas exactas (7 × 70px); el encabezado de cada día cuenta como una fila */
        [data-productivity] .prod-detail { position: relative; max-height: 490px; overflow-y: auto; }
        [data-productivity] .prod-day-head { height: 70px; box-sizing: border-box; align-content: center; }
        [data-productivity] .prod-row { cursor: grab; }
        [data-productivity] .prod-day + .prod-day { border-top-width: 1px; }
        [data-productivity] .prod-row:nth-child(even) > .prod-row-btn { background-color: var(--panel-header); }
        [data-productivity] .prod-row-btn { display: grid; width: 100%; grid-template-columns: minmax(0, 1fr) 24px; align-items: center; gap: 20px; height: 69px; box-sizing: border-box; padding: 0 20px; text-align: left; cursor: pointer; }
        [data-productivity] .prod-row-btn:hover { background-color: var(--panel-hover) !important; }
        [data-productivity] .prod-row-btn > :nth-child(5) { grid-column: 2; grid-row: 1; }
        [data-productivity] .prod-row-btn > :nth-child(-n+4) { grid-column: 1; }
        [data-productivity] .prod-info { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; padding: 20px; }

        @container prod (min-width: 480px) {
            [data-productivity] .prod-dates { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            [data-productivity] .prod-info { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @container prod (min-width: 760px) {
            [data-productivity] .prod-dist { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            [data-productivity] .prod-col + .prod-col { border-top-width: 0; border-left-width: 1px; }
            [data-productivity] .prod-row-btn { grid-template-columns: 150px minmax(0, 1fr) minmax(0, 1fr) 120px 24px; }
            [data-productivity] .prod-row-btn > :nth-child(5) { grid-column: auto; grid-row: auto; }
            [data-productivity] .prod-row-btn > :nth-child(-n+4) { grid-column: auto; }
            [data-productivity] .prod-info { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        /* Escritorio (1000 o más): periodo y exportación a la izquierda, resumen a la derecha, con alto fijo */
        @container prod (min-width: 1000px) {
            [data-productivity] .prod-layout { height: 600px; grid-template-columns: 320px minmax(0, 1fr); grid-template-rows: minmax(0, 1fr); }
            [data-productivity] .prod-aside { min-height: 0; overflow-y: auto; border-bottom-width: 0; border-right-width: 1px; }
            [data-productivity] .prod-dates { grid-template-columns: minmax(0, 1fr); }
            [data-productivity] .prod-main { min-height: 0; }
            [data-productivity] .prod-dist { min-height: 0; flex: 1; grid-template-rows: minmax(0, 1fr); }
            [data-productivity] .prod-col { min-height: 0; }
            [data-productivity] .prod-list-wrap { flex: 1; }
            /* La lista muestra siempre 7 filas exactas, igual que la tabla del Cronómetro */
            [data-productivity] .prod-list { position: absolute; inset: 0; max-height: none; container-type: size; }
            [data-productivity] .prod-item { height: max(59px, calc(100cqh / 7)); }
        }
    </style>

    <main class="relative z-10 flex w-full min-h-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]">
        <section class="flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="min-w-0 max-w-full">
                <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">Mi productividad</h1>
                <p class="prod-lines mt-[5px]">Consulta y analiza el tiempo dedicado a tus clientes y actividades.</p>
            </div>
            <span class="panel-accent inline-flex items-center gap-[10px] leading-none"><span aria-hidden="true">—</span>Sincronizado en tiempo real</span>
        </section>

        <div class="prod-wrap">
            <section class="panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Resumen de productividad">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Productividad del periodo</h2>
                        <p class="prod-lines mt-[5px]">Del {{ \Illuminate\Support\Carbon::parse($from)->locale('es')->translatedFormat('j \d\e F \d\e Y') }} al {{ \Illuminate\Support\Carbon::parse($to)->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</p>
                    </div>
                    <p class="leading-none">Tiempo total <span class="tabular-nums">{{ $fmt($totalSeconds) }}</span></p>
                </header>
                <div class="prod-layout">
                    <aside class="prod-aside panel-scroll panel-border">
                        <div class="panel-surface panel-border box-border h-[87px] shrink-0 border-b p-[20px]">
                            <h2 class="panel-accent leading-none">Periodo de consulta</h2>
                            <p class="prod-lines mt-[5px]">Seleccione periodo de fechas</p>
                        </div>
                        <div class="prod-form">
                            <div class="prod-dates">
                                <label class="block">
                                    <span class="prod-label">Desde</span>
                                    <input type="date" wire:model="from" class="panel-field w-full rounded-xl border px-[20px] py-[15px] focus:outline-none focus:ring-0">
                                </label>
                                <label class="block">
                                    <span class="prod-label">Hasta</span>
                                    <input type="date" wire:model="to" class="panel-field w-full rounded-xl border px-[20px] py-[15px] focus:outline-none focus:ring-0">
                                </label>
                            </div>
                            <button type="button" wire:click="applyFilters" class="prod-primary w-full focus:outline-none focus:ring-0">Consultar periodo</button>
                            <div class="panel-border border-t pt-[20px]">
                                <span class="prod-label">Exportar información</span>
                                <div class="flex gap-[10px]">
                                    @foreach ($exportFormats as $format)
                                        <button type="button" wire:click="export('{{ $format }}')" wire:loading.attr="disabled" class="prod-primary min-w-0 flex-1 px-[10px] focus:outline-none focus:ring-0">{{ strtoupper($format) }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="prod-main">
                        <div class="prod-dist">
                            @foreach ([['title' => 'Distribución por cliente', 'items' => $byCustomer], ['title' => 'Distribución por actividad', 'items' => $byActivity]] as $distribution)
                                <section class="prod-col panel-border">
                                    <div class="prod-grid panel-border box-border h-[87px] shrink-0 border-b">
                                        <h3 class="prod-index panel-border leading-none">N°</h3>
                                        <h3 class="prod-cell leading-none">{{ $distribution['title'] }}</h3>
                                    </div>
                                    <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="prod-list-wrap">
                                    <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const rows = $el.querySelectorAll('.prod-item'); if (! rows.length) return; const height = $el.scrollHeight / rows.length; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.max(0, Math.round($el.scrollTop / height) * height)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" data-activity-sortable data-sortable-local class="prod-list panel-scroll">
                                        @forelse ($distribution['items'] as $name => $seconds)
                                            <div class="prod-item prod-grid" data-activity-id="{{ $loop->index }}">
                                                <span class="prod-index prod-counter panel-border leading-none"></span>
                                                <div class="prod-cell prod-metrics">
                                                    <span class="truncate leading-none" title="{{ $name }}">{{ $name }}</span>
                                                    <span class="tabular-nums leading-none">{{ $fmt($seconds) }}</span>
                                                    <div class="prod-track"><div class="prod-fill" style="width: {{ $pct($seconds, $totalSeconds) }}%"></div></div>
                                                    <span class="text-right tabular-nums leading-none">{{ $pct($seconds, $totalSeconds) }}%</span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="p-[20px] text-center">Sin datos en este periodo.</p>
                                        @endforelse
                                    </div>
                                    <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="prod-wrap">
            <section class="panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Detalle de actividades por día" x-data="{ expandedRows: new Set(), toggleRow(id) { this.expandedRows.has(id) ? this.expandedRows.delete(id) : this.expandedRows.add(id) }, isExpanded(id) { return this.expandedRows.has(id) } }">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Detalle de actividades por día</h2>
                        <p class="prod-lines mt-[5px]">Consulta el registro completo del periodo seleccionado.</p>
                    </div>
                    <p class="leading-none">{{ count($entries) }} {{ count($entries) === 1 ? 'registro' : 'registros' }}</p>
                </header>
                <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="relative">
                <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const tops = [...$el.querySelectorAll('[data-snap]')].map((e) => e.offsetTop); if (! tops.length) return; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, tops.reduce((a, b) => Math.abs(b - $el.scrollTop) < Math.abs(a - $el.scrollTop) ? b : a)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" class="prod-detail panel-scroll">
                    @forelse ($activityDetail['groups'] as $group)
                        <section class="prod-day panel-border" data-activity-sortable data-sortable-local>
                            <header data-snap class="prod-day-head panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] overflow-hidden border-b p-[20px]">
                                <h3 class="panel-accent leading-none">{{ $group['date'] }}</h3>
                                <p class="leading-none">{{ count($group['rows']) }} actividad{{ count($group['rows']) === 1 ? '' : 'es' }} <span class="ml-[20px]">Tiempo total <span class="tabular-nums">{{ $group['total_effective'] }}</span></span></p>
                            </header>
                            @foreach ($group['rows'] as $row)
                                @php $rowId = $rowCounter++; @endphp
                                <article data-snap data-activity-id="{{ $rowId }}" class="prod-row panel-border border-b last:border-b-0">
                                    <button type="button" @click="toggleRow({{ $rowId }})" :aria-expanded="isExpanded({{ $rowId }})" data-sortable-handle class="prod-row-btn focus:outline-none focus:ring-0">
                                        <span class="leading-none">{{ $row[$idxInicio] ?? '-' }} – {{ $row[$idxFin] ?? '-' }}</span>
                                        <span class="truncate leading-none" title="{{ $row[$idxActividad] ?? '-' }}">{{ $row[$idxActividad] ?? '-' }}</span>
                                        <span class="truncate leading-none" title="{{ $row[$idxCliente] ?? '-' }}">{{ $row[$idxCliente] ?? '-' }}</span>
                                        <span class="tabular-nums leading-none">{{ $row[$idxTiempo] ?? '00:00:00' }}</span>
                                        <svg class="h-4 w-4 transition-transform" :class="isExpanded({{ $rowId }}) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                    </button>
                                    <dl x-show="isExpanded({{ $rowId }})" x-collapse style="display:none" class="prod-info panel-border panel-surface border-t">
                                        <div><dt class="panel-accent leading-none">Tiempo total realizado</dt><dd class="mt-[15px] tabular-nums leading-none">{{ $row[$idxTiempo] ?? '00:00:00' }}</dd></div>
                                        <div><dt class="panel-accent leading-none">Puesto profesional</dt><dd class="mt-[15px] leading-none">{{ $row[$idxPuesto] ?? '—' }}</dd></div>
                                        <div><dt class="panel-accent leading-none">Área física</dt><dd class="mt-[15px] leading-none">{{ $row[$idxArea] ?? '—' }}</dd></div>
                                        <div><dt class="panel-accent leading-none">Observaciones</dt><dd class="mt-[15px]">{{ $row[$idxObservaciones] ?? '—' }}</dd></div>
                                    </dl>
                                </article>
                            @endforeach
                        </section>
                    @empty
                        <div class="flex min-h-[260px] flex-col items-center justify-center px-[20px] text-center">
                            <p class="panel-accent leading-none">Sin actividades registradas</p>
                            <p class="prod-lines mt-[5px]">Ajusta el periodo o inicia una actividad desde el cronómetro.</p>
                        </div>
                    @endforelse
                </div>
                <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>
            </section>
        </div>
    </main>
</div>
