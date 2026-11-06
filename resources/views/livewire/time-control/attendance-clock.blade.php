{{-- Reloj Checador (usuario): mismo diseño que Panel general, Administración y Reportes de soporte (tarjetas con
     cabecera gris, líneas de 1px, texto de 15px, separaciones de 20px, escala de vista al 90% ajustable y tema
     oscuro con fondo negro). Solo cambia la presentación; las consultas, gráficas y exportaciones son las mismas. --}}
<div
    wire:ignore
    data-panel
    data-attendance-clock
    class="attendance-clock-modern relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 overflow-hidden text-[15px]"
    x-data="{
        viewScale: 90,
        isFullscreen: false,
        isCompact: window.innerWidth / window.appAutoScale() < 1024,
        scaledHeight: null,
        observer: null,
        onFullscreen: null,
        init() {
            try {
                const saved = Number(null);
                if (saved >= 70 && saved <= 100) this.viewScale = saved;
            } catch (error) {}
            this.onFullscreen = () => { this.isFullscreen = document.fullscreenElement === this.$root; };
            document.addEventListener('fullscreenchange', this.onFullscreen);
            this.$nextTick(() => {
                this.observer = new ResizeObserver(() => this.syncHeight());
                this.observer.observe(this.$refs.scaled);
                this.syncHeight();
            });
        },
        destroy() {
            this.observer?.disconnect();
            document.removeEventListener('fullscreenchange', this.onFullscreen);
        },
        syncHeight() {
            if (this.$refs.scaled) this.scaledHeight = Math.ceil(this.$refs.scaled.offsetHeight * (this.viewScale / 100));
        },
        setScale(value) {
            this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
            try { localStorage.setItem('attendance-clock-view-scale', String(this.viewScale)); } catch (error) {}
            this.$nextTick(() => this.syncHeight());
        },
        async toggleFullscreen() {
            if (document.fullscreenElement === this.$root) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await this.$root.requestFullscreen();
        }
    }"
    @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024; syncHeight()"
>
    <x-panel-styles />

    <style>
        /* Desplazamiento fino con el color de acento del panel */
        .attendance-clock-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: var(--panel-accent) transparent;
            overscroll-behavior: contain;
        }
        .attendance-clock-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .attendance-clock-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .attendance-clock-scrollbar::-webkit-scrollbar-thumb { background: var(--panel-accent); border-radius: 9999px; }

        /* Tabla de jornadas (las filas las dibuja revisarHorasChecador) */
        .attendance-clock-modern table thead,
        .attendance-clock-modern table tfoot { background-color: var(--panel-header); }
        .attendance-clock-modern table thead { box-shadow: 0 1px 0 var(--panel-border); }
        .attendance-clock-modern table tfoot { box-shadow: 0 -1px 0 var(--panel-border); }
        .attendance-clock-modern tbody tr:last-child td { border-bottom-width: 0; }
        .attendance-row:hover { background-color: var(--panel-subtle); }
        .attendance-row-review,
        .attendance-row-review:hover {
            background-color: var(--panel-danger-soft);
            box-shadow: inset 4px 0 0 var(--panel-danger);
        }
        .attendance-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid color-mix(in srgb, currentColor 45%, transparent);
            border-radius: 9999px;
            padding: 3px 12px;
            white-space: nowrap;
        }

        /* Gráficas: los colores salen del tema, así cambian solos al pasar de claro a oscuro */
        .attendance-chart-grid { stroke: var(--panel-border); }
        .attendance-chart-base { stroke: var(--panel-muted); }
        .attendance-chart-guide { stroke: var(--panel-border); opacity: .6; }
        .attendance-chart-text { fill: var(--panel-muted); font-weight: 400; font-variant-numeric: tabular-nums; }
        .attendance-chart-year { fill: var(--panel-muted); opacity: .75; }
        .attendance-chart-series { stroke: var(--panel-accent); }
        .attendance-chart-fill { fill: var(--panel-accent); }
        .attendance-chart-point { stroke: var(--panel-surface); }


        /* Gráficas interactivas: columna resaltada, línea guía y tooltip al pasar, enfocar o tocar */
        .attendance-chart-band { fill: transparent; }
        .attendance-chart-band-active { fill: var(--panel-accent); fill-opacity: .1; }
        .attendance-chart-cursor { stroke: var(--panel-muted); stroke-dasharray: 4 4; pointer-events: none; }
        .attendance-clock-modern [role="img"] svg * { pointer-events: none; -webkit-tap-highlight-color: transparent; }
        .attendance-clock-modern [role="img"] svg .attendance-chart-band { pointer-events: all; }

        /* Botón principal: en tema oscuro usa el azul de marca en lugar de blanco */
        html.module-dark-theme [data-panel] .panel-button { background-color: #1A3A6B; color: #ffffff; }
        html.module-dark-theme [data-panel] .panel-button:hover { background-color: #234b88; }
        [data-panel] .panel-button:disabled { cursor: not-allowed; opacity: .6; }

        /* Celular y tablet: cada jornada se muestra como una tarjeta, sin desplazamiento horizontal */
        @media (max-width: 1023px) {
            .attendance-clock-modern .attendance-table-wrap { max-height: none; overflow: hidden; }
            .attendance-clock-modern table { display: block; min-width: 0 !important; }
            .attendance-clock-modern colgroup,
            .attendance-clock-modern thead { display: none; }
            .attendance-clock-modern tbody,
            .attendance-clock-modern tfoot { display: block; position: static; }
            .attendance-clock-modern tbody tr,
            .attendance-clock-modern tfoot tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 15px 20px;
                padding: 20px;
            }
            .attendance-clock-modern tbody tr { border-bottom: 1px solid var(--panel-border); }
            .attendance-clock-modern tbody tr:last-child { border-bottom-width: 0; }
            .attendance-clock-modern tbody td,
            .attendance-clock-modern tfoot td { display: block; padding: 0 !important; border-width: 0 !important; }
            .attendance-clock-modern td[colspan] { grid-column: 1 / -1; }
            .attendance-clock-modern td:empty { display: none; }
            .attendance-clock-modern td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 5px; color: var(--panel-muted); }
            .attendance-clock-modern tbody tr > :nth-child(1) { order: 1; }
            .attendance-clock-modern tbody tr > :nth-child(8) { order: 2; }
            .attendance-clock-modern tbody tr > :nth-child(2) { order: 3; grid-column: 1 / -1; }
            .attendance-clock-modern tbody tr > :nth-child(3) { order: 4; }
            .attendance-clock-modern tbody tr > :nth-child(4) { order: 5; }
            .attendance-clock-modern tbody tr > :nth-child(5) { order: 6; }
            .attendance-clock-modern tbody tr > :nth-child(6) { order: 7; }
            .attendance-clock-modern tbody tr > :nth-child(7) { order: 8; }
        }

        @media print {
            .attendance-clock-modern .no-print { display: none !important; }
        }
    </style>

    <div class="w-full" :style="(!isCompact && scaledHeight) ? 'height:' + scaledHeight + 'px' : null">
        <div x-ref="scaled" class="origin-top-left" :style="isCompact ? 'width:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
            <div class="flex w-full min-w-0 flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">

                {{-- Migas de pan y botones antiguos (ocultos; se conservan por compatibilidad) --}}
                <div class="attendance-topbar hidden">
                    <button id="attendance-header-download" type="button" onclick="exportarChecador('pdf')">Descargar PDF</button>
                    <button type="button" onclick="window.print()">Imprimir</button>
                </div>

                <!-- Cabecera y accesos -->
                <section class="attendance-heading flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                    <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                        <span class="panel-surface panel-border panel-text grid h-14 w-14 shrink-0 place-items-center rounded-xl border">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Reloj Checador</h1>
                            <p class="mt-[10px] leading-tight">Consulta tus marcas biométricas y el resumen acumulado del periodo.</p>
                        </div>
                    </div>
                    <div class="no-print flex flex-wrap items-center gap-[10px]">
                        <a href="{{ route('time.index') }}" class="panel-button-secondary inline-flex h-11 items-center gap-[10px] rounded-xl px-[20px]">Cronómetro <span>›</span></a>
                        <a href="{{ route('time.reports') }}" class="panel-button-secondary inline-flex h-11 items-center gap-[10px] rounded-xl px-[20px]">Productividad <span>›</span></a>
                        <a href="{{ route('time.dashboard') }}" class="panel-button inline-flex h-11 items-center gap-[10px] rounded-xl px-[20px]">Panel de control <span>›</span></a>
                    </div>
                </section>

                <p class="max-w-2xl leading-tight">Visualiza las marcas registradas por día, con cálculo de tiempo y acumulados durante el periodo seleccionado.</p>

                <input type="hidden" id="employee_id" value="{{ auth()->user()->employee_id ?? '' }}">
                <input type="hidden" id="attendance_user_id" value="{{ auth()->id() }}">

                <!-- Periodo a consultar -->
                <section class="attendance-filters panel-surface panel-border flex flex-wrap items-end gap-[20px] rounded-xl border p-[20px]">
                    <div class="w-full shrink-0 sm:w-[180px]">
                        <label for="fecha_inicio" class="mb-[10px] block">Fecha Inicio</label>
                        <input type="date" id="fecha_inicio" value="{{ date('Y-m-d', strtotime('-14 days')) }}"
                            class="panel-field h-11 w-full rounded-xl border px-[15px] outline-none focus:ring-0">
                    </div>
                    <div class="w-full shrink-0 sm:w-[180px]">
                        <label for="fecha_fin" class="mb-[10px] block">Fecha Fin</label>
                        <input type="date" id="fecha_fin" value="{{ date('Y-m-d') }}"
                            class="panel-field h-11 w-full rounded-xl border px-[15px] outline-none focus:ring-0">
                    </div>
                    <div class="w-full shrink-0 sm:w-[180px]">
                        <button id="btn_revisar_horas" type="button" onclick="revisarHorasChecador()"
                            class="panel-button inline-flex h-11 w-full items-center justify-center gap-[10px] rounded-xl border-0 px-[20px] disabled:cursor-wait disabled:opacity-70">
                            <svg id="icono_buscar_checador" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>
                            <svg id="carga_checador" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span id="texto_boton_checador">Revisar Horas</span>
                        </button>
                    </div>
                </section>

                <div id="panel_resultados_checador" class="flex min-w-0 flex-col gap-[20px]">
                    <!-- Acumulados del periodo -->
                    <section aria-label="Acumulados del periodo" class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <header class="panel-header panel-border border-b p-[20px]">
                            <h2 class="panel-accent leading-none">Acumulados del periodo</h2>
                            <p class="mt-[10px] leading-tight">Totales de las jornadas consultadas</p>
                        </header>
                        <div class="panel-line grid grid-cols-1 gap-px sm:grid-cols-2 xl:grid-cols-5">
                            @foreach ([
                                ['id' => 'kpi_tiempo_neto', 'label' => 'Tiempo Neto', 'value' => '00h 00m 00s', 'icon' => 'M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                ['id' => 'kpi_horas_decimales', 'label' => 'Horas Decimales', 'value' => '0.00', 'icon' => 'M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm1 4h8M8 11h2m4 0h2m-8 4h2m4 0h2'],
                                ['id' => 'kpi_pago_base', 'label' => 'Pago Base', 'value' => '$0.00', 'icon' => 'M3 7.5A2.5 2.5 0 015.5 5H18a2 2 0 012 2v2H6a3 3 0 000 6h14v2a2 2 0 01-2 2H5.5A2.5 2.5 0 013 16.5v-9zM20 9v6h-4a3 3 0 010-6h4z'],
                                ['id' => 'kpi_bono', 'label' => 'Bono', 'value' => '$0.00', 'icon' => 'M20 12v8H4v-8m-1-5h18v5H3V7zm9 13V7m0 0H8.5A2.5 2.5 0 118 2c2 0 4 5 4 5zm0 0h3.5A2.5 2.5 0 1016 2c-2 0-4 5-4 5z'],
                                ['id' => 'kpi_total_dia', 'label' => 'Total Día', 'value' => '$0.00', 'icon' => 'M4 17l5-5 4 4 7-8m0 0h-5m5 0v5'],
                            ] as $kpi)
                                <article class="panel-surface flex min-w-0 items-center gap-[15px] p-[20px] {{ $loop->last ? 'sm:col-span-2 xl:col-span-1' : '' }}">
                                    <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $kpi['icon'] }}" /></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <span id="{{ $kpi['id'] }}" class="panel-accent block truncate leading-none tabular-nums" title="{{ $kpi['value'] }}" aria-live="polite">{{ $kpi['value'] }}</span>
                                        <span class="mt-[10px] block truncate leading-snug">{{ $kpi['label'] }}</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <!-- Gráficas -->
                    <section aria-label="Estadísticas del periodo" class="grid grid-cols-1 gap-[20px] xl:grid-cols-2">
                        <article class="panel-surface panel-border flex min-w-0 flex-col overflow-hidden rounded-xl border">
                            <header class="panel-header panel-border flex items-center gap-[15px] border-b p-[20px]">
                                <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <h2 class="panel-accent truncate leading-none">Tiempo neto por día</h2>
                                    <p class="mt-[10px] truncate leading-tight">Duración efectiva de cada jornada</p>
                                </div>
                            </header>
                            <div class="panel-line grid grid-cols-2 gap-px">
                                <div class="panel-surface min-w-0 p-[20px]">
                                    <span class="block truncate leading-none">Promedio diario</span>
                                    <strong id="promedio_tiempo_checador" class="panel-accent mt-[10px] block truncate leading-none">00h 00m 00s</strong>
                                </div>
                                <div class="panel-surface min-w-0 p-[20px]">
                                    <span class="block truncate leading-none">Jornada máxima</span>
                                    <strong id="maximo_tiempo_checador" class="panel-accent mt-[10px] block truncate leading-none">00h 00m 00s</strong>
                                </div>
                            </div>
                            <div class="panel-border relative h-[300px] border-t p-[20px]">
                                <div id="grafica_tiempo_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de tiempo neto por día"></div>
                                <div id="grafica_tiempo_vacia" class="panel-muted absolute inset-0 flex items-center justify-center px-[20px] text-center">Sin datos para mostrar en el periodo.</div>
                            </div>
                        </article>

                        <article class="panel-surface panel-border flex min-w-0 flex-col overflow-hidden rounded-xl border">
                            <header class="panel-header panel-border flex items-center gap-[15px] border-b p-[20px]">
                                <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17l6-6 4 4 7-8m0 0h-5m5 0v5" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <h2 class="panel-accent truncate leading-none">Ganancias por día</h2>
                                    <p class="mt-[10px] truncate leading-tight">Pago total generado por jornada</p>
                                </div>
                            </header>
                            <div class="panel-line grid grid-cols-2 gap-px">
                                <div class="panel-surface min-w-0 p-[20px]">
                                    <span class="block truncate leading-none">Promedio diario</span>
                                    <strong id="promedio_ganancias_checador" class="panel-accent mt-[10px] block truncate leading-none">$0.00</strong>
                                </div>
                                <div class="panel-surface min-w-0 p-[20px]">
                                    <span class="block truncate leading-none">Ingreso máximo</span>
                                    <strong id="maximo_ganancias_checador" class="panel-accent mt-[10px] block truncate leading-none">$0.00</strong>
                                </div>
                            </div>
                            <div class="panel-border relative h-[300px] border-t p-[20px]">
                                <div id="grafica_ganancias_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de ganancias por día"></div>
                                <div id="grafica_ganancias_vacia" class="panel-muted absolute inset-0 flex items-center justify-center px-[20px] text-center">Sin datos para mostrar en el periodo.</div>
                            </div>
                        </article>
                    </section>

                    <!-- Detalle de jornadas -->
                    <section aria-label="Detalle diario del checador" class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <header class="panel-header panel-border flex flex-col gap-[15px] border-b p-[20px] sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-[15px]">
                                <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm0 5h16M9 4v16m6-11v11" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <h2 class="panel-accent leading-none sm:truncate">Detalle de jornadas</h2>
                                    <p class="mt-[10px] leading-tight sm:truncate">Marcas, tiempos e importes del periodo consultado</p>
                                </div>
                            </div>
                            <div id="checador_export_bar" class="no-print hidden">
                                <div class="flex flex-wrap items-center gap-[10px]">
                                    @foreach ([['csv', 'CSV', 'Exportar CSV', 'Exportar reporte en CSV'], ['pdf', 'PDF', 'Exportar PDF', 'Exportar reporte en PDF'], ['txt', 'TXT', 'Exportar TXT', 'Exportar reporte en TXT']] as [$format, $formatLabel, $formatTitle, $formatAria])
                                        <button type="button" onclick="exportarChecador('{{ $format }}')" title="{{ $formatTitle }}" aria-label="{{ $formatAria }}"
                                            class="panel-button-secondary inline-flex h-10 items-center justify-center gap-[10px] rounded-xl px-[15px] focus:outline-none focus:ring-0">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 14h6m-6 3h4" />
                                            </svg>
                                            <span>{{ $formatLabel }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </header>

                        <div class="p-[20px]">
                            <div class="attendance-table-wrap attendance-clock-scrollbar panel-border max-h-[560px] overflow-auto rounded-xl border">
                                <table class="w-full table-fixed border-separate border-spacing-0" style="min-width: 1390px;">
                                    <colgroup>
                                        <col style="width: 150px;">
                                        <col style="width: 330px;">
                                        <col style="width: 170px;">
                                        <col style="width: 150px;">
                                        <col style="width: 140px;">
                                        <col style="width: 110px;">
                                        <col style="width: 140px;">
                                        <col style="width: 200px;">
                                    </colgroup>
                                    <thead class="sticky top-0 z-20">
                                        <tr>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Fecha Jornada</th>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Marcas / Chequeos</th>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Tiempo Neto</th>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Hrs Decimales</th>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Pago Base</th>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Bono</th>
                                            <th class="panel-accent px-[20px] py-[15px] text-left align-middle font-normal">Total del Día</th>
                                            <th class="panel-accent py-[15px] pl-[20px] pr-[30px] text-left align-middle font-normal">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_checador_body" class="panel-surface">
                                        <tr>
                                            <td colspan="8" class="panel-muted p-[30px] text-center">
                                                Consultando las marcas de los últimos 15 días...
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="sticky bottom-0 z-20">
                                        <tr>
                                            <td colspan="2" class="panel-accent whitespace-nowrap px-[20px] py-[15px]">Total acumulado</td>
                                            <td id="total_tiempo" data-label="Tiempo neto" class="whitespace-nowrap px-[20px] py-[15px]">00h 00m 00s</td>
                                            <td id="total_decimal" data-label="Hrs decimales" class="whitespace-nowrap px-[20px] py-[15px]">0.00</td>
                                            <td id="total_pago_h" data-label="Pago base" class="whitespace-nowrap px-[20px] py-[15px]">$0.00</td>
                                            <td id="total_bonos" data-label="Bono" class="whitespace-nowrap px-[20px] py-[15px]">$0.00</td>
                                            <td id="total_general" data-label="Total del día" class="panel-accent whitespace-nowrap px-[20px] py-[15px]">$0.00</td>
                                            <td class="py-[15px] pl-[20px] pr-[30px]"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <!-- Control de pantalla completa (igual que en Panel general, Administración y Reportes de soporte) -->
    <div class="no-print panel-surface panel-border fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] lg:flex">
        <button type="button" @click="toggleFullscreen()" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5" /></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5" /></svg>
        </button>
    </div>

<script>
function asignarValorChecador(id, valor) {
    const elemento = document.getElementById(id);

    if (!elemento) return;

    elemento.textContent = valor;
    elemento.title = valor;
}

function actualizarAcumuladosChecador(totales = {}) {
    const valores = {
        tiempo: totales.tiempo || '00h 00m 00s',
        decimal: totales.decimal || '0.00',
        pago: totales.pago_h || '$0.00',
        bonos: totales.bonos || '$0.00',
        general: totales.general || '$0.00',
    };

    asignarValorChecador('kpi_tiempo_neto', valores.tiempo);
    asignarValorChecador('kpi_horas_decimales', valores.decimal);
    asignarValorChecador('kpi_pago_base', valores.pago);
    asignarValorChecador('kpi_bono', valores.bonos);
    asignarValorChecador('kpi_total_dia', valores.general);

    asignarValorChecador('total_tiempo', valores.tiempo);
    asignarValorChecador('total_decimal', valores.decimal);
    asignarValorChecador('total_pago_h', valores.pago);
    asignarValorChecador('total_bonos', valores.bonos);
    asignarValorChecador('total_general', valores.general);
}

function alternarEstadoGraficaChecador(graficaId, vacioId, mostrarGrafica, mensaje = 'Sin datos para mostrar en el periodo.') {
    const grafica = document.getElementById(graficaId);
    const vacio = document.getElementById(vacioId);

    grafica?.classList.toggle('hidden', !mostrarGrafica);
    vacio?.classList.toggle('hidden', mostrarGrafica);

    if (vacio && !mostrarGrafica) {
        vacio.textContent = mensaje;
    }
}

function formatearSegundosChecador(segundos) {
    const total = Math.max(0, Math.round(Number(segundos) || 0));
    const horas = Math.floor(total / 3600);
    const minutos = Math.floor((total % 3600) / 60);
    const segundosRestantes = total % 60;

    return String(horas).padStart(2, '0')
        + 'h ' + String(minutos).padStart(2, '0')
        + 'm ' + String(segundosRestantes).padStart(2, '0') + 's';
}

const svgNamespaceChecador = 'http://www.w3.org/2000/svg';

function crearElementoSvgChecador(etiqueta, atributos = {}) {
    const elemento = document.createElementNS(svgNamespaceChecador, etiqueta);

    Object.entries(atributos).forEach(([nombre, valor]) => {
        elemento.setAttribute(nombre, String(valor));
    });

    return elemento;
}

function agregarTextoSvgChecador(svg, texto, atributos = {}) {
    const elemento = crearElementoSvgChecador('text', {
        class: 'attendance-chart-text',
        'font-size': 15,
        ...atributos,
    });
    elemento.textContent = texto;
    svg.appendChild(elemento);
}

function partesFechaChecador(fecha) {
    const partes = String(fecha).split('-');

    return partes.length === 3
        ? { corta: partes[2] + '/' + partes[1], anio: partes[0], completa: partes[2] + '/' + partes[1] + '/' + partes[0] }
        : { corta: String(fecha), anio: '', completa: String(fecha) };
}

function activarMarcaGraficaChecador(elemento, opacidadActiva) {
    elemento.style.cursor = 'pointer';
    elemento.style.transition = 'opacity 160ms ease';
    elemento.setAttribute('tabindex', '0');

    ['mouseenter', 'focus'].forEach(evento => {
        elemento.addEventListener(evento, () => elemento.setAttribute('opacity', opacidadActiva));
    });
    ['mouseleave', 'blur'].forEach(evento => {
        elemento.addEventListener(evento, () => elemento.setAttribute('opacity', '1'));
    });
}

function renderizarGraficaSvgChecador(contenedorId, etiquetas, valores, configuracion) {
    const contenedor = document.getElementById(contenedorId);
    if (!contenedor) return;

    contenedor.replaceChildren();

    const ancho = Math.max(320, Math.round(contenedor.clientWidth || 620));
    const alto = Math.max(220, Math.round(contenedor.clientHeight || 268));
    // Ambas gráficas comparten exactamente la misma retícula y canales de ejes.
    const margen = { superior: 10, derecho: 20, inferior: 60, izquierdo: ancho < 480 ? 92 : 100 };
    const anchoGrafica = Math.max(1, ancho - margen.izquierdo - margen.derecho);
    const altoGrafica = Math.max(1, alto - margen.superior - margen.inferior);
    const baseY = margen.superior + altoGrafica;
    const valoresNumericos = valores.map(valor => Math.max(0, Number(valor) || 0));
    const maximoDatos = Math.max(...valoresNumericos, 0);
    const promedio = valoresNumericos.reduce((total, valor) => total + valor, 0) / Math.max(1, valoresNumericos.length);
    const pasos = 4;
    const magnitud = maximoDatos > 0 ? 10 ** Math.floor(Math.log10(maximoDatos / pasos)) : 1;
    const pasoCrudo = maximoDatos > 0 ? maximoDatos / pasos / magnitud : 1;
    const factorPaso = pasoCrudo <= 1 ? 1 : (pasoCrudo <= 2 ? 2 : (pasoCrudo <= 5 ? 5 : 10));
    const pasoEje = factorPaso * magnitud;
    const maximoEje = maximoDatos > 0 ? Math.max(pasoEje, Math.ceil(maximoDatos / pasoEje) * pasoEje) : pasos;

    const svg = crearElementoSvgChecador('svg', {
        viewBox: '0 0 ' + ancho + ' ' + alto,
        width: '100%',
        height: '100%',
        role: 'img',
        'aria-label': configuracion.descripcion,
    });

    for (let paso = 0; paso <= pasos; paso++) {
        const proporcion = paso / pasos;
        const y = margen.superior + (altoGrafica * proporcion);
        const valorEje = maximoEje - (maximoEje * proporcion);

        svg.appendChild(crearElementoSvgChecador('line', {
            x1: margen.izquierdo,
            x2: ancho - margen.derecho,
            y1: y,
            y2: y,
            class: paso === pasos ? 'attendance-chart-base' : 'attendance-chart-grid',
            'stroke-width': 1,
            'stroke-dasharray': paso === pasos ? '0' : '4 4',
        }));

        agregarTextoSvgChecador(svg, configuracion.formatearEje(valorEje), {
            x: margen.izquierdo - 15,
            y: y + 5,
            'text-anchor': 'end',
        });
    }

    const cantidad = Math.max(1, valores.length);
    const rellenoHorizontal = configuracion.tipo === 'barras'
        ? Math.min(22, anchoGrafica / Math.max(2, cantidad * 2))
        : 4;
    const anchoDatos = Math.max(1, anchoGrafica - (rellenoHorizontal * 2));
    const obtenerX = indice => cantidad === 1
        ? margen.izquierdo + (anchoGrafica / 2)
        : margen.izquierdo + rellenoHorizontal + (anchoDatos * indice / (cantidad - 1));
    const obtenerY = valor => baseY - ((Math.max(0, Number(valor) || 0) / maximoEje) * altoGrafica);
    const maximoEtiquetas = Math.max(2, Math.floor(anchoGrafica / 92));
    const cantidadEtiquetas = Math.min(cantidad, maximoEtiquetas);
    const indicesEtiquetas = new Set();

    for (let indiceEtiqueta = 0; indiceEtiqueta < cantidadEtiquetas; indiceEtiqueta++) {
        indicesEtiquetas.add(Math.round(indiceEtiqueta * (cantidad - 1) / Math.max(1, cantidadEtiquetas - 1)));
    }

    etiquetas.forEach((etiqueta, indice) => {
        if (!indicesEtiquetas.has(indice)) return;

        svg.appendChild(crearElementoSvgChecador('line', {
            x1: obtenerX(indice),
            x2: obtenerX(indice),
            y1: margen.superior,
            y2: baseY,
            class: 'attendance-chart-guide',
            'stroke-width': 1,
            'stroke-dasharray': '3 4',
        }));

        const fecha = partesFechaChecador(etiqueta);
        const textoFecha = crearElementoSvgChecador('text', {
            x: obtenerX(indice),
            y: alto - 30,
            class: 'attendance-chart-text',
            'text-anchor': 'middle',
            'font-size': 15,
        });
        const lineaFecha = crearElementoSvgChecador('tspan', { x: obtenerX(indice), dy: 0 });
        lineaFecha.textContent = fecha.corta;
        textoFecha.appendChild(lineaFecha);

        if (fecha.anio) {
            const lineaAnio = crearElementoSvgChecador('tspan', {
                x: obtenerX(indice),
                dy: 20,
                class: 'attendance-chart-year',
                'font-size': 15,
            });
            lineaAnio.textContent = fecha.anio;
            textoFecha.appendChild(lineaAnio);
        }

        svg.appendChild(textoFecha);
    });

    const promedioY = obtenerY(promedio);
    svg.appendChild(crearElementoSvgChecador('line', {
        x1: margen.izquierdo,
        x2: ancho - margen.derecho,
        y1: promedioY,
        y2: promedioY,
        class: 'attendance-chart-series',
        'stroke-width': 1.5,
        'stroke-dasharray': '7 5',
        opacity: 0.5,
    }));

    // Tooltip visible al pasar el cursor, enfocar o tocar una jornada
    contenedor.style.position = 'relative';
    const tooltip = document.createElement('div');
    tooltip.className = 'panel-surface panel-border pointer-events-none absolute z-10 hidden whitespace-nowrap rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.18)]';
    const lineaGuia = crearElementoSvgChecador('line', {
        class: 'attendance-chart-cursor',
        y1: margen.superior,
        y2: baseY,
        'stroke-width': 1,
        visibility: 'hidden',
    });
    svg.appendChild(lineaGuia);
    const bandas = [];
    let temporizadorTooltip = null;

    const ocultarTooltip = () => {
        window.clearTimeout(temporizadorTooltip);
        tooltip.classList.add('hidden');
        lineaGuia.setAttribute('visibility', 'hidden');
        bandas.forEach(banda => banda.classList.remove('attendance-chart-band-active'));
    };

    const mostrarTooltip = indice => {
        window.clearTimeout(temporizadorTooltip);
        const x = obtenerX(indice);
        const y = obtenerY(valores[indice]);
        const textoFecha = document.createElement('span');
        textoFecha.className = 'panel-muted block leading-none';
        textoFecha.textContent = partesFechaChecador(etiquetas[indice]).completa;
        const textoValor = document.createElement('strong');
        textoValor.className = 'panel-accent mt-[10px] block leading-none';
        textoValor.textContent = configuracion.formatearDetalle(valores[indice], indice);
        tooltip.replaceChildren(textoFecha, textoValor);
        tooltip.classList.remove('hidden');
        const mitad = tooltip.offsetWidth / 2;
        const arriba = y - tooltip.offsetHeight - 15;
        tooltip.style.left = Math.min(Math.max(x, mitad + 5), ancho - mitad - 5) + 'px';
        tooltip.style.top = (arriba >= 0 ? arriba : y + 15) + 'px';
        tooltip.style.transform = 'translateX(-50%)';
        lineaGuia.setAttribute('x1', x);
        lineaGuia.setAttribute('x2', x);
        lineaGuia.setAttribute('visibility', 'visible');
        bandas.forEach((banda, posicion) => banda.classList.toggle('attendance-chart-band-active', posicion === indice));
    };

    const anchoBanda = Math.max(8, anchoGrafica / cantidad);
    valores.forEach((_valor, indice) => {
        const banda = crearElementoSvgChecador('rect', {
            x: obtenerX(indice) - (anchoBanda / 2),
            y: margen.superior,
            width: anchoBanda,
            height: altoGrafica,
            class: 'attendance-chart-band',
        });
        banda.addEventListener('pointerenter', () => mostrarTooltip(indice));
        banda.addEventListener('pointerdown', evento => {
            mostrarTooltip(indice);
            if (evento.pointerType !== 'mouse') temporizadorTooltip = window.setTimeout(ocultarTooltip, 3500);
        });
        banda.addEventListener('pointerleave', evento => {
            if (evento.pointerType === 'mouse') ocultarTooltip();
        });
        svg.appendChild(banda);
        bandas.push(banda);
    });

    if (configuracion.tipo === 'linea') {
        const puntos = valores.map((valor, indice) => obtenerX(indice) + ',' + obtenerY(valor));

        if (puntos.length > 1) {
            const area = crearElementoSvgChecador('polygon', {
                points: margen.izquierdo + ',' + baseY + ' ' + puntos.join(' ') + ' ' + obtenerX(valores.length - 1) + ',' + baseY,
                class: 'attendance-chart-fill',
                opacity: 0.12,
            });
            svg.appendChild(area);
        }

        svg.appendChild(crearElementoSvgChecador('polyline', {
            points: puntos.join(' '),
            fill: 'none',
            class: 'attendance-chart-series',
            'stroke-width': 3,
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
        }));

        valores.forEach((valor, indice) => {
            const punto = crearElementoSvgChecador('circle', {
                cx: obtenerX(indice),
                cy: obtenerY(valor),
                r: cantidad > 24 ? 3.5 : 4.5,
                class: 'attendance-chart-fill attendance-chart-point',
                'stroke-width': 2,
                'aria-label': etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice),
            });
            const titulo = crearElementoSvgChecador('title');
            titulo.textContent = etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice);
            punto.appendChild(titulo);
            activarMarcaGraficaChecador(punto, '0.62');
            punto.style.pointerEvents = 'none';
            punto.addEventListener('focus', () => mostrarTooltip(indice));
            punto.addEventListener('blur', ocultarTooltip);
            svg.appendChild(punto);
        });
    } else {
        const espacio = anchoGrafica / cantidad;
        const anchoBarra = Math.min(42, Math.max(8, espacio * 0.62));

        valores.forEach((valor, indice) => {
            const altoBarra = valor > 0 ? Math.max(2, baseY - obtenerY(valor)) : 0;
            const barra = crearElementoSvgChecador('rect', {
                x: obtenerX(indice) - (anchoBarra / 2),
                y: baseY - altoBarra,
                width: anchoBarra,
                height: altoBarra,
                rx: Math.min(5, anchoBarra / 3),
                class: 'attendance-chart-fill',
                opacity: 0.88,
                'aria-label': etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice),
            });
            const titulo = crearElementoSvgChecador('title');
            titulo.textContent = etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice);
            barra.appendChild(titulo);
            activarMarcaGraficaChecador(barra, '0.68');
            barra.style.pointerEvents = 'none';
            barra.addEventListener('focus', () => mostrarTooltip(indice));
            barra.addEventListener('blur', ocultarTooltip);
            svg.appendChild(barra);
        });
    }

    contenedor.appendChild(svg);
    contenedor.appendChild(tooltip);
}

let ultimoResumenGraficasChecador = [];
let temporizadorResizeGraficasChecador = null;

function actualizarGraficasChecador(resumen = []) {
    const filas = Array.isArray(resumen)
        ? [...resumen].sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
        : [];
    ultimoResumenGraficasChecador = filas;

    if (filas.length === 0) {
        document.getElementById('grafica_tiempo_checador')?.replaceChildren();
        document.getElementById('grafica_ganancias_checador')?.replaceChildren();
        alternarEstadoGraficaChecador('grafica_tiempo_checador', 'grafica_tiempo_vacia', false);
        alternarEstadoGraficaChecador('grafica_ganancias_checador', 'grafica_ganancias_vacia', false);
        asignarValorChecador('promedio_tiempo_checador', '00h 00m 00s');
        asignarValorChecador('maximo_tiempo_checador', '00h 00m 00s');
        asignarValorChecador('promedio_ganancias_checador', '$0.00');
        asignarValorChecador('maximo_ganancias_checador', '$0.00');
        return;
    }

    const etiquetas = filas.map(item => item.fecha);
    const tiemposSegundos = filas.map(item => Number(item.tiempo_segundos) || 0);
    const ganancias = filas.map(item => Number(item.total_raw) || 0);
    const formatoMoneda = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
    });
    const promedioTiempo = tiemposSegundos.reduce((total, valor) => total + valor, 0) / tiemposSegundos.length;
    const promedioGanancias = ganancias.reduce((total, valor) => total + valor, 0) / ganancias.length;

    asignarValorChecador('promedio_tiempo_checador', formatearSegundosChecador(promedioTiempo));
    asignarValorChecador('maximo_tiempo_checador', formatearSegundosChecador(Math.max(...tiemposSegundos)));
    asignarValorChecador('promedio_ganancias_checador', formatoMoneda.format(promedioGanancias));
    asignarValorChecador('maximo_ganancias_checador', formatoMoneda.format(Math.max(...ganancias)));

    alternarEstadoGraficaChecador('grafica_tiempo_checador', 'grafica_tiempo_vacia', true);
    alternarEstadoGraficaChecador('grafica_ganancias_checador', 'grafica_ganancias_vacia', true);

    renderizarGraficaSvgChecador('grafica_tiempo_checador', etiquetas, tiemposSegundos, {
        tipo: 'linea',
        descripcion: 'Evolución del tiempo neto por día',
        formatearEje: valor => formatearSegundosChecador(valor).replaceAll('h ', ':').replaceAll('m ', ':').replace('s', ''),
        formatearDetalle: (_valor, indice) => formatearSegundosChecador(tiemposSegundos[indice]),
    });

    renderizarGraficaSvgChecador('grafica_ganancias_checador', etiquetas, ganancias, {
        tipo: 'barras',
        descripcion: 'Evolución de las ganancias por día',
        formatearEje: valor => formatoMoneda.format(valor),
        formatearDetalle: valor => formatoMoneda.format(valor),
    });
}

window.addEventListener('resize', () => {
    window.clearTimeout(temporizadorResizeGraficasChecador);
    temporizadorResizeGraficasChecador = window.setTimeout(() => {
        if (ultimoResumenGraficasChecador.length > 0) {
            actualizarGraficasChecador(ultimoResumenGraficasChecador);
        }
    }, 160);
});

function establecerCargaChecador(cargando) {
    const boton = document.getElementById('btn_revisar_horas');
    const iconoBuscar = document.getElementById('icono_buscar_checador');
    const iconoCarga = document.getElementById('carga_checador');
    const texto = document.getElementById('texto_boton_checador');

    if (!boton || !iconoBuscar || !iconoCarga || !texto) return;

    boton.disabled = cargando;
    iconoBuscar.classList.toggle('hidden', cargando);
    iconoCarga.classList.toggle('hidden', !cargando);
    texto.textContent = cargando ? 'Consultando...' : 'Revisar Horas';
}

function revisarHorasChecador() {
    const employeeId = document.getElementById('employee_id').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    if (!employeeId || !fechaInicio || !fechaFin) {
        alert('Tu usuario no tiene ID de checador asignado o faltan fechas.');
        return;
    }

    establecerCargaChecador(true);

    const payload = {
        employee_id: employeeId,
        user_id: document.getElementById('attendance_user_id')?.value,
        inicio: fechaInicio,
        fin: fechaFin,
    };

    fetch('{{ route("control-horas.consultar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Error de servidor.');
        }
        return data;
    })
    .then(data => {
        const tbody = document.getElementById('tabla_checador_body');
        tbody.innerHTML = '';
        actualizarAcumuladosChecador(data.totales_pie || {});
        actualizarGraficasChecador(data.resumen || []);

        if (!data.resumen || data.resumen.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="panel-muted p-[30px] text-center">
                        No hay marcas registradas en el dispositivo para este ID en las fechas seleccionadas.
                    </td>
                </tr>`;
            document.getElementById('checador_export_bar').classList.add('hidden');
            document.getElementById('panel_resultados_checador').classList.remove('hidden');
            return;
        }

        data.resumen.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = item.requiere_revision ? 'attendance-row attendance-row-review' : 'attendance-row';

            const iconoCorrecto = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>';
            const iconoRevisar = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>';

            tr.innerHTML = `
                <td data-label="Fecha Jornada" class="panel-border whitespace-nowrap border-b px-[20px] py-[15px] align-top tabular-nums">${item.fecha}</td>
                <td data-label="Marcas / Chequeos" class="panel-border whitespace-normal break-words border-b px-[20px] py-[15px] align-top tabular-nums" title="${item.detalles_marcas}">${item.detalles_marcas}</td>
                <td data-label="Tiempo neto" class="panel-border whitespace-nowrap border-b px-[20px] py-[15px] align-top tabular-nums">${item.neto}</td>
                <td data-label="Hrs decimales" class="panel-border whitespace-nowrap border-b px-[20px] py-[15px] align-top tabular-nums">${item.horas_decimal}</td>
                <td data-label="Pago base" class="panel-border whitespace-nowrap border-b px-[20px] py-[15px] align-top tabular-nums">${item.pago_horas}</td>
                <td data-label="Bono" class="panel-border whitespace-nowrap border-b px-[20px] py-[15px] align-top tabular-nums">${item.bono}</td>
                <td data-label="Total del día" class="panel-border panel-accent whitespace-nowrap border-b px-[20px] py-[15px] align-top tabular-nums">${item.total}</td>
                <td data-label="Estado" class="panel-border border-b py-[15px] pl-[20px] pr-[30px] align-top">
                    ${item.requiere_revision
                        ? '<span class="attendance-pill panel-tone-red">' + iconoRevisar + 'Impar / Revisar</span>'
                        : '<span class="attendance-pill panel-tone-green">' + iconoCorrecto + 'Correcto</span>'
                    }
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('checador_export_bar').classList.remove('hidden');
        document.getElementById('panel_resultados_checador').classList.remove('hidden');
    })
    .catch(err => {
        console.error(err);
        alert(err.message || 'Hubo un error al intentar consultar las marcas del checador.');
    })
    .finally(() => {
        establecerCargaChecador(false);
    });
}

function exportarChecador(format) {
    const employeeId = document.getElementById('employee_id').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    if (!employeeId || !fechaInicio || !fechaFin) {
        alert('No hay datos suficientes para exportar.');
        return;
    }

    const params = new URLSearchParams({
        employee_id: employeeId,
        user_id: document.getElementById('attendance_user_id')?.value,
        inicio: fechaInicio,
        fin: fechaFin,
    });

    window.location.href = `{{ url('/control-horas/export') }}/${format}?${params.toString()}`;
}

function cargarPeriodoInicialChecador() {
    const employeeId = document.getElementById('employee_id')?.value;
    actualizarAcumuladosChecador();
    actualizarGraficasChecador([]);

    if (employeeId) {
        revisarHorasChecador();
        return;
    }

    const tbody = document.getElementById('tabla_checador_body');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="panel-muted p-[30px] text-center">
                    Tu usuario no tiene un ID de checador asignado.
                </td>
            </tr>`;
    }
}

window.revisarHorasChecador = revisarHorasChecador;
window.exportarChecador = exportarChecador;
window.actualizarAcumuladosChecador = actualizarAcumuladosChecador;
window.actualizarGraficasChecador = actualizarGraficasChecador;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarPeriodoInicialChecador, { once: true });
} else {
    cargarPeriodoInicialChecador();
}
</script>
</div>
