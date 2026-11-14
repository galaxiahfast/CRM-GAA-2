@php
    $inicioChecador = date('Y-m-d', strtotime('-14 days'));
    $finChecador = date('Y-m-d');
    $fechaLargaChecador = fn (string $date): string => \Illuminate\Support\Carbon::parse($date)->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $periodoChecador = 'Del '.$fechaLargaChecador($inicioChecador).' al '.$fechaLargaChecador($finChecador);
    // Cada cuadro tiene su propio tutorial (el de marcas es el predeterminado)
    $tourMarcas = [
        ['target' => 'ac-marks-period', 'title' => '1. Elige el periodo', 'description' => 'Selecciona la fecha Desde y la fecha Hasta de las marcas que quieres ver. Este periodo solo afecta a la tabla de marcas.'],
        ['target' => 'ac-marks-consult', 'title' => '2. Consulta el periodo', 'description' => 'Presiona este botón para traer del checador las marcas del periodo elegido.'],
        ['target' => 'ac-marks-table', 'title' => '3. Revisa tus marcas', 'description' => 'Cada fila es una jornada con sus marcas, tiempo neto, horas decimales, pago base y total. Las jornadas con marcas impares salen en rojo para que las revises.'],
        ['target' => 'ac-marks-export', 'title' => '4. Descarga el reporte', 'description' => 'Descarga en PDF las marcas del periodo consultado, con el formato de los reportes del CRM.'],
    ];
    $tourDatos = [
        ['target' => 'ac-general-period', 'title' => '1. Elige el periodo', 'description' => 'Selecciona la fecha Desde y la fecha Hasta. Este periodo solo afecta a los datos generales y sus gráficas.'],
        ['target' => 'ac-general-consult', 'title' => '2. Consulta el periodo', 'description' => 'Presiona este botón para actualizar los acumulados y las gráficas con las fechas elegidas.'],
        ['target' => 'ac-general-kpis', 'title' => '3. Acumulados del periodo', 'description' => 'Tiempo neto, horas decimales, pago base, bono y total de todas las jornadas del periodo.'],
        ['target' => 'ac-general-charts', 'title' => '4. Cambia de gráfica', 'description' => 'Tiempo neto muestra la duración de cada jornada y Ganancias el pago de cada día. Pasa el cursor sobre la gráfica para ver el valor exacto.'],
        ['target' => 'ac-general-total', 'title' => '5. Total del periodo', 'description' => 'Es la suma del tiempo neto de todas las jornadas del periodo elegido.'],
    ];
@endphp

{{-- Reloj Checador (usuario): mismo diseño que Cronómetro, Manager Control y Gráficos generales (título con una línea de
     subtítulo, tarjetas con cabecera gris, periodo a la izquierda, líneas de 1px, texto de 15px, separaciones de 20px,
     botones verdes y scroll que solo aparece con el puntero encima). Solo cambia la presentación: las consultas,
     gráficas y exportaciones son las mismas. --}}
<div
    wire:ignore
    data-panel
    data-productivity
    data-attendance-clock
    x-data="clockQuickTour(@js($tourMarcas), @js(['general' => $tourDatos]))"
    class="attendance-clock-modern relative isolate flex min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 flex-col text-[15px]"
>
    <x-panel-styles />

    <style>
        /* Mismo diseño que Cronómetro, Manager Control y Gráficos generales: texto negro (blanco en oscuro) y azul; enlaces y botones en verde. */
        [data-productivity] { --panel-muted: var(--panel-text); --prod-green: #15803d; }
        html.module-dark-theme [data-productivity] { --prod-green: #4ade80; }
        [data-productivity] .prod-link { color: var(--prod-green); cursor: pointer; transition: opacity 150ms ease; }
        [data-productivity] .prod-link:hover { opacity: .75; }
        [data-productivity] .prod-primary {
            display: inline-flex;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid var(--prod-green);
            border-radius: 12px;
            padding: 0 20px;
            background-color: color-mix(in srgb, var(--prod-green) 12%, var(--panel-surface));
            color: var(--prod-green);
            line-height: 15px;
            cursor: pointer;
            transition: background-color 150ms ease;
        }
        [data-productivity] .prod-primary:hover { background-color: color-mix(in srgb, var(--prod-green) 20%, var(--panel-surface)); }
        [data-productivity] .prod-primary:disabled { cursor: wait; opacity: .6; }
        [data-productivity] .prod-lines { line-height: 35px; margin-bottom: -10px; }
        [data-productivity] .prod-dg { display: inline-block; width: 0.64em; text-align: center; }

        /* Calendario de Desde y Hasta (el mismo de Cronómetro, Manager Control y Gráficos generales) */
        [data-productivity].prod-calendar { z-index: 200000; width: min(320px, calc((100vw - 32px) / var(--app-zoom, 1))); max-height: calc((100dvh - 32px) / var(--app-zoom, 1)); overflow-y: auto; box-shadow: 0 14px 35px rgba(0, 0, 0, .18); }
        [data-productivity].prod-calendar.prod-calendar-over-tour { z-index: 300003; }
        [data-productivity].prod-calendar .prod-calendar-day { display: inline-flex; aspect-ratio: 1; align-items: center; justify-content: center; min-width: 0; border: 1px solid transparent; border-radius: 8px; color: var(--panel-text); }
        [data-productivity].prod-calendar .prod-calendar-day:hover { background-color: var(--panel-hover); }
        [data-productivity].prod-calendar .prod-calendar-day.is-outside { opacity: .5; }
        [data-productivity].prod-calendar .prod-calendar-day.is-today { border-color: var(--panel-border); }
        [data-productivity].prod-calendar .prod-calendar-day.is-selected { border-color: var(--panel-accent); background-color: color-mix(in srgb, var(--panel-accent) 14%, var(--panel-surface)); color: var(--panel-accent); opacity: 1; }

        /* ===== Tarjeta de datos generales: periodo a la izquierda y resultados a la derecha ===== */
        [data-attendance-clock] .ac-wrap { container: ac / inline-size; }
        [data-attendance-clock] .ac-layout { display: grid; grid-template-columns: minmax(0, 1fr); }
        [data-attendance-clock] .ac-aside { min-width: 0; border-bottom: 1px solid var(--panel-border); }
        [data-attendance-clock] .ac-form { display: grid; gap: 20px; padding: 20px; }
        [data-attendance-clock] .ac-dates { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
        [data-attendance-clock] .ac-label { display: block; min-height: 24px; margin-bottom: 20px; line-height: 24px; }
        [data-attendance-clock] .ac-consult { display: grid; gap: 20px; }
        [data-attendance-clock] .ac-consult .ac-label { margin-bottom: 0; }
        [data-attendance-clock] .ac-date-trigger { display: flex; min-height: 50px; align-items: center; justify-content: space-between; gap: 10px; text-align: left; cursor: pointer; }
        [data-attendance-clock] .ac-guide { display: flex; flex-direction: column; gap: 20px; }
        [data-attendance-clock] .ac-guide > a { display: inline-flex; width: fit-content; min-height: 24px; align-items: center; }
        [data-attendance-clock] .ac-main { display: flex; min-width: 0; flex-direction: column; }

        /* Acumulados: celdas de 87px (como el encabezado del periodo), dos por fila en pantallas angostas y cinco en anchas */
        [data-attendance-clock] .ac-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px; background-color: var(--panel-border); border-bottom: 1px solid var(--panel-border); }
        /* Mismas medidas que el encabezado "Periodo de consulta" de la izquierda (87px con su línea): título de 15px, 5px y
           el valor con la misma altura de línea, para que la línea inferior quede a la misma altura */
        [data-attendance-clock] .ac-kpi { display: flex; min-width: 0; height: 86px; box-sizing: border-box; flex-direction: column; justify-content: flex-start; padding: 20px; background-color: var(--panel-surface); }
        [data-attendance-clock] .ac-kpi:last-child { grid-column: 1 / -1; }
        [data-attendance-clock] .ac-kpi > span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        [data-attendance-clock] .ac-kpi > span:first-child { line-height: 15px; }
        [data-attendance-clock] .ac-kpi > span:last-child { margin-top: 5px; line-height: 35px; margin-bottom: -10px; }

        /* Gráficas: pestañas con el mismo recuadro que las de Gráficos generales */
        [data-attendance-clock] .ac-tabs { display: flex; min-height: 87px; box-sizing: border-box; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; padding: 15px 20px; border-bottom: 1px solid var(--panel-border); }
        [data-attendance-clock] .ac-tablist { display: flex; flex-wrap: wrap; gap: 20px; }
        [data-attendance-clock] .ac-tab { display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--panel-border); border-radius: 12px; padding: 15px 20px; background-color: var(--panel-surface); color: var(--panel-text); line-height: 1; white-space: nowrap; cursor: pointer; transition: background-color 150ms ease; }
        [data-attendance-clock] .ac-tab:hover { background-color: var(--panel-hover); }
        [data-attendance-clock] .ac-tab[aria-selected="true"] { border-color: var(--prod-green); background-color: color-mix(in srgb, var(--prod-green) 12%, var(--panel-surface)); color: var(--prod-green); }
        [data-attendance-clock] .ac-stats { display: flex; flex-wrap: wrap; gap: 10px 20px; line-height: 24px; }
        [data-attendance-clock] .ac-chart-area { position: relative; display: flex; min-height: 380px; flex: 1; flex-direction: column; padding: 20px; }
        [data-attendance-clock] .ac-chart { position: relative; flex: 1; min-height: 340px; }
        [data-attendance-clock] .ac-chart-empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 20px; text-align: center; }
        /* El JavaScript oculta el aviso con la clase hidden cuando hay datos; aquí debe ganarle al display: flex */
        [data-attendance-clock] .ac-chart-empty.hidden { display: none; }

        @container ac (min-width: 760px) {
            [data-attendance-clock] .ac-kpis { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            [data-attendance-clock] .ac-kpi:last-child { grid-column: auto; }
        }
        @container ac (min-width: 1040px) {
            /* Mismo alto que la tarjeta del Cronómetro, Manager Control y Gráficos generales: llena la pantalla */
            [data-attendance-clock] .ac-card { display: flex; flex-direction: column; min-height: max(600px, calc(var(--app-dvh) - 348px)); }
            [data-attendance-clock] .ac-layout { flex: 1; min-height: 0; grid-template-columns: 320px minmax(0, 1fr); grid-template-rows: minmax(0, 1fr); }
            [data-attendance-clock] .ac-aside { display: flex; flex-direction: column; min-height: 0; border-bottom: 0; border-right: 1px solid var(--panel-border); }
            /* Igual que el formulario del Cronómetro: textos de 15px y campos y botones de 50px repartidos en todo el alto */
            [data-attendance-clock] .ac-form { display: flex; flex: 1; flex-direction: column; justify-content: space-between; gap: 0; }
            [data-attendance-clock] .ac-dates, [data-attendance-clock] .ac-dates > div, [data-attendance-clock] .ac-consult, [data-attendance-clock] .ac-guide { display: contents; }
            [data-attendance-clock] .ac-label { min-height: 0; margin-bottom: 0; line-height: 15px; }
            [data-attendance-clock] .ac-guide > a { min-height: 0; line-height: 15px; }
        }

        /* ===== Tabla de marcas: encabezado de 87px, filas de 70px intercaladas, columnas separadas por líneas ===== */
        [data-attendance-clock] .ac-table-wrap { position: relative; min-width: 0; }
        /* 6 filas visibles: encabezado (87) + 6 filas + pie de totales; el pie ocupa el lugar de la séptima fila */
        [data-attendance-clock] .ac-table-scroll { max-height: 577px; overflow: auto; }
        /* En escritorio la tabla ocupa el mismo alto que el periodo de la izquierda: lo que queda debajo del encabezado
           se divide en 7 partes iguales (6 filas y el pie), así siempre se ven exactamente 6 filas completas */
        @container ac (min-width: 1040px) {
            [data-attendance-clock] .ac-table-wrap { min-height: 0; }
            [data-attendance-clock] .ac-table-scroll { position: absolute; inset: 0; max-height: none; container-type: size; }
            /* Va con .ac-table-scroll delante para ganarle a las reglas de 70px que están más abajo */
            [data-attendance-clock] .ac-table-scroll .ac-table :is(tbody, tfoot) td { height: calc((100cqh - 87px) / 7); padding-top: 0; padding-bottom: 0; }
        }
        [data-attendance-clock] .ac-table { width: 100%; min-width: 1000px; table-layout: fixed; border-collapse: separate; border-spacing: 0; }
        [data-attendance-clock] .ac-table :is(th, td) { box-sizing: border-box; padding: 0 20px; border-right: 1px solid var(--panel-border); font-weight: 400; text-align: left; vertical-align: middle; line-height: 24px; }
        [data-attendance-clock] .ac-table :is(th, td):last-child { border-right-width: 0; }
        [data-attendance-clock] .ac-table thead th { position: sticky; top: 0; z-index: 2; height: 87px; background-color: var(--panel-surface); color: var(--panel-text); box-shadow: inset 0 -1px 0 var(--panel-border); }
        [data-attendance-clock] .ac-table tbody td { height: 70px; padding-top: 10px; padding-bottom: 10px; font-variant-numeric: tabular-nums; }
        [data-attendance-clock] .ac-table tbody td[colspan] { text-align: center; }
        [data-attendance-clock] .ac-table tbody tr:nth-child(odd) { background-color: var(--panel-header); }
        [data-attendance-clock] .ac-table tbody tr:nth-child(even) { background-color: var(--panel-surface); }
        [data-attendance-clock] .ac-table tbody tr.attendance-row:hover { background-color: var(--panel-hover); }
        [data-attendance-clock] .ac-table tbody tr.attendance-row-review,
        [data-attendance-clock] .ac-table tbody tr.attendance-row-review:hover { background-color: var(--panel-danger-soft); }
        [data-attendance-clock] .ac-table tfoot td { position: sticky; bottom: 0; z-index: 2; height: 70px; background-color: var(--panel-header); box-shadow: inset 0 1px 0 var(--panel-border); }
        [data-attendance-clock] .ac-table :is(th, td):not(:nth-child(2)) { text-align: center; }
        [data-attendance-clock] .ac-table tfoot td:first-child { text-align: left; }
        [data-attendance-clock] .ac-fig { white-space: nowrap; }
        /* La regla global de tablas en tema oscuro las pinta de gris: aquí la tabla usa los mismos colores que el resto */
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table tbody,
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table tr { background: var(--panel-surface) !important; }
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table tbody tr:nth-child(odd) { background: var(--panel-header) !important; }
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table tbody tr.attendance-row:hover { background: var(--panel-hover) !important; }
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table tbody tr.attendance-row-review { background: var(--panel-danger-soft) !important; }
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table thead th { background: var(--panel-surface) !important; }
        html.module-dark-theme #main-content [data-attendance-clock] .ac-table tfoot td { background: var(--panel-header) !important; }
        /* Las descargas se ven siempre; sin marcas en el periodo quedan atenuadas y sin acción, como en Manager Control */
        [data-attendance-clock] #checador_export_bar.is-disabled { cursor: not-allowed; opacity: .5; }
        [data-attendance-clock] #checador_export_bar.is-disabled button { pointer-events: none; }
        [data-attendance-clock] .attendance-pill { display: inline-flex; align-items: center; gap: 10px; white-space: nowrap; }
        /* Indicador horizontal del scroll (el vertical es el mismo de las demás tablas) */
        [data-attendance-clock] .ac-hthumb { top: auto; right: auto; bottom: 5px; left: 0; width: 32px; height: 3px; min-height: 0; pointer-events: auto; cursor: grab; }

        /* Gráficas: los colores salen del tema, así cambian solos al pasar de claro a oscuro */
        .attendance-chart-grid { stroke: var(--panel-border); }
        .attendance-chart-base { stroke: var(--panel-muted); }
        .attendance-chart-guide { stroke: var(--panel-border); opacity: .6; }
        .attendance-chart-text { fill: var(--panel-muted); font-weight: 400; font-variant-numeric: tabular-nums; }
        .attendance-chart-year { fill: var(--panel-muted); opacity: .75; }
        .attendance-chart-series { stroke: var(--panel-accent); }
        .attendance-chart-fill { fill: var(--panel-accent); }
        .attendance-chart-point { stroke: var(--panel-surface); }
        .attendance-chart-band { fill: transparent; }
        .attendance-chart-band-active { fill: var(--panel-accent); fill-opacity: .1; }
        .attendance-chart-cursor { stroke: var(--panel-muted); stroke-dasharray: 4 4; pointer-events: none; }
        .attendance-clock-modern [role="img"] svg * { pointer-events: none; -webkit-tap-highlight-color: transparent; }
        .attendance-clock-modern [role="img"] svg .attendance-chart-band { pointer-events: all; }

        @media print {
            .attendance-clock-modern .no-print { display: none !important; }
        }
    </style>

    <main class="relative z-10 flex w-full min-h-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]">
        <section class="flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="min-w-0 max-w-full">
                <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">Reloj Checador</h1>
                <p class="prod-lines mt-[5px]">Consulta tus marcas biométricas y el resumen acumulado del periodo.</p>
            </div>
            <span class="panel-accent inline-flex items-center gap-[10px] leading-none"><span aria-hidden="true">—</span>Sincronizado con el checador</span>
        </section>

        <input type="hidden" id="employee_id" value="{{ auth()->user()->employee_id ?? '' }}">
        <input type="hidden" id="attendance_user_id" value="{{ auth()->id() }}">

        {{-- Marcas de tiempo: su propio periodo, guía y tutorial; la tabla a la derecha del periodo --}}
        <div class="ac-wrap">
            <section class="ac-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Marcas de tiempo">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Marcas de tiempo</h2>
                        <p id="periodo_marks" class="prod-lines mt-[5px]">{{ $periodoChecador }}</p>
                    </div>
                    <div id="ac-marks-export" class="no-print">
                        <div id="checador_export_bar" class="is-disabled flex flex-wrap items-center gap-[20px]" title="Consulta un periodo con marcas para descargar el reporte">
                            @foreach ([['pdf', 'Descargar reporte PDF']] as [$format, $formatLabel])
                                <button type="button" onclick="exportarChecador('{{ $format }}')" class="prod-link leading-none focus:outline-none focus:ring-0">{{ $formatLabel }}</button>
                            @endforeach
                        </div>
                    </div>
                </header>
                <div class="ac-layout">
                    <x-attendance-period-aside prefix="marks" :from="$inicioChecador" :to="$finChecador" consult="revisarMarcasChecador"
                        guide="reloj-checador" guide-title="Descargar la guía de Marcas de tiempo del Reloj Checador en PDF" />

                    <div id="ac-marks-table" data-scroll-on-hover x-data="backupOverlayScroll()" class="ac-table-wrap">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" data-scroll-top-offset="87" class="ac-table-scroll panel-scroll">
                            <table class="ac-table">
                                <colgroup>
                                    <col style="width: 160px;">
                                    <col>
                                    <col style="width: 160px;">
                                    <col style="width: 160px;">
                                    <col style="width: 160px;">
                                    <col style="width: 160px;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Marcas / Chequeos</th>
                                        <th>Tiempo neto</th>
                                        <th>Hrs decimales</th>
                                        <th>Pago base</th>
                                        <th>Total del día</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla_checador_body">
                                    <tr>
                                        <td colspan="6">Consultando las marcas de los últimos 15 días...</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total acumulado</td>
                                        <td><span id="total_tiempo" class="ac-fig">00h 00m 00s</span></td>
                                        <td><span id="total_decimal" class="ac-fig">0.00</span></td>
                                        <td><span id="total_pago_h" class="ac-fig">$0.00</span></td>
                                        <td><span id="total_general" class="ac-fig">$0.00</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        <span :class="horizontalVisible ? '' : 'hidden'" class="panel-scroll-thumb ac-hthumb" :style="`width:${width}px; transform:translateX(${left}px)`" @pointerdown="startHorizontalDrag($event)" aria-hidden="true"></span>
                    </div>
                </div>
            </section>
        </div>

        {{-- Datos generales: su propio periodo, guía y tutorial; acumulados y gráficas a la derecha --}}
        <div class="ac-wrap">
            <section class="ac-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Datos generales" x-data="{ tab: 'time' }">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Datos generales</h2>
                        <p id="periodo_general" class="prod-lines mt-[5px]">{{ $periodoChecador }}</p>
                    </div>
                    <p id="ac-general-total" class="leading-none">Tiempo neto del periodo: <span id="checador_total_header" class="whitespace-nowrap tabular-nums">00h 00m 00s</span></p>
                </header>
                <div class="ac-layout">
                    <x-attendance-period-aside prefix="general" :from="$inicioChecador" :to="$finChecador" consult="revisarDatosChecador"
                        guide="reloj-checador-datos" guide-title="Descargar la guía de Datos generales del Reloj Checador en PDF" tour-set="general" />

                    <div id="panel_resultados_checador" class="ac-main">
                        <div id="ac-general-kpis" class="ac-kpis">
                            @foreach ([
                                ['id' => 'kpi_tiempo_neto', 'label' => 'Tiempo neto', 'value' => '00h 00m 00s'],
                                ['id' => 'kpi_horas_decimales', 'label' => 'Horas decimales', 'value' => '0.00'],
                                ['id' => 'kpi_pago_base', 'label' => 'Pago base', 'value' => '$0.00'],
                                ['id' => 'kpi_bono', 'label' => 'Bono', 'value' => '$0.00'],
                                ['id' => 'kpi_total_dia', 'label' => 'Total', 'value' => '$0.00'],
                            ] as $kpi)
                                <article class="ac-kpi">
                                    <span>{{ $kpi['label'] }}</span>
                                    <span id="{{ $kpi['id'] }}" class="panel-accent tabular-nums" title="{{ $kpi['value'] }}" aria-live="polite">{{ $kpi['value'] }}</span>
                                </article>
                            @endforeach
                        </div>

                        <div id="ac-general-charts" class="ac-tabs">
                            <div class="ac-tablist" role="tablist" aria-label="Gráficas del checador">
                                @foreach (['time' => 'Tiempo neto', 'money' => 'Ganancias'] as $tabKey => $tabLabel)
                                    <button type="button" role="tab" @click="tab = '{{ $tabKey }}'; $nextTick(() => window.redibujarGraficasChecador?.())" :aria-selected="tab === '{{ $tabKey }}' ? 'true' : 'false'" class="ac-tab focus:outline-none focus:ring-0">{{ $tabLabel }}</button>
                                @endforeach
                            </div>
                            <div x-show="tab === 'time'" class="ac-stats">
                                <span>Promedio diario: <span id="promedio_tiempo_checador" class="panel-accent tabular-nums">00h 00m 00s</span></span>
                                <span>Jornada máxima: <span id="maximo_tiempo_checador" class="panel-accent tabular-nums">00h 00m 00s</span></span>
                            </div>
                            <div x-show="tab === 'money'" x-cloak class="ac-stats">
                                <span>Promedio diario: <span id="promedio_ganancias_checador" class="panel-accent tabular-nums">$0.00</span></span>
                                <span>Ingreso máximo: <span id="maximo_ganancias_checador" class="panel-accent tabular-nums">$0.00</span></span>
                            </div>
                        </div>
                        <div class="ac-chart-area">
                            <div x-show="tab === 'time'" class="ac-chart">
                                <div id="grafica_tiempo_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de tiempo neto por día"></div>
                                <div id="grafica_tiempo_vacia" class="ac-chart-empty">Sin datos para mostrar en el periodo.</div>
                            </div>
                            <div x-show="tab === 'money'" x-cloak class="ac-chart">
                                <div id="grafica_ganancias_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de ganancias por día"></div>
                                <div id="grafica_ganancias_vacia" class="ac-chart-empty">Sin datos para mostrar en el periodo.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <template x-teleport="body">
        <div data-panel data-productivity x-cloak x-show="quickTourStep > 0" x-transition.opacity class="pointer-events-none fixed inset-0 z-[299999]" style="background:transparent" @click.stop @wheel.prevent @touchmove.prevent>
            <div :style="maskTopStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskLeftStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskRightStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskBottomStyle" class="z-[300000]" aria-hidden="true"></div>

            <div :style="spotlightStyle" :class="(quickTourSteps[quickTourStep - 1]?.target || '').endsWith('-period') ? 'pointer-events-none' : 'pointer-events-auto'" class="z-[300001]" aria-hidden="true"></div>

            <section :style="popoverStyle" class="panel-surface panel-border pointer-events-auto fixed left-1/2 top-1/2 z-[300002] w-[min(440px,calc(100vw-40px))] -translate-x-1/2 -translate-y-1/2 rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)]" role="dialog" aria-modal="true" aria-labelledby="clock-tour-title">
                <p class="panel-accent leading-[30px]" x-text="quickTourStep ? 'Paso ' + quickTourStep + ' de ' + quickTourSteps.length : ''"></p>
                <h2 id="clock-tour-title" class="mt-[15px] leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].title : ''"></h2>
                <p class="mt-[15px] text-justify leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].description : ''"></p>
                <div class="mt-[20px] flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px]">
                    <button type="button" @click="closeQuickTour()" class="prod-link whitespace-nowrap leading-none">Saltar recorrido</button>
                    <div class="flex items-center gap-[20px] whitespace-nowrap">
                        <button x-show="quickTourStep > 1" type="button" @click="previousQuickTourStep()" class="panel-button-secondary rounded-xl px-[20px] py-[15px] leading-none">Anterior</button>
                        <button x-ref="quickTourNext" type="button" @click="nextQuickTourStep()" class="prod-primary leading-none" x-text="quickTourStep === quickTourSteps.length ? 'Terminar' : 'Siguiente'"></button>
                    </div>
                </div>
            </section>
        </div>
    </template>

<script>
// Cada cifra va en una caja de ancho fijo (la letra Poppins no es tabular: un "1" mide menos que un "0"),
// así fechas, horas e importes quedan alineados de una fila a otra, igual que en Cronómetro y Manager Control.
function cifrasChecador(texto) {
    const escapado = String(texto ?? '')
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    return escapado.replace(/\d/g, '<span class="prod-dg">$&</span>');
}

// 2026-11-12 → 12/11/26 (el mismo formato de las fechas del periodo)
function fechaCortaChecador(fecha) {
    const partes = String(fecha ?? '').split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0].slice(-2)}` : String(fecha ?? '');
}

function asignarValorChecador(id, valor) {
    const elemento = document.getElementById(id);

    if (!elemento) return;

    elemento.innerHTML = cifrasChecador(valor);
    elemento.title = valor;
}

// Formato de los totales que devuelve el checador
function totalesChecador(totales = {}) {
    return {
        tiempo: totales.tiempo || '00h 00m 00s',
        decimal: totales.decimal || '0.00',
        pago: totales.pago_h || '$0.00',
        bonos: totales.bonos || '$0.00',
        general: totales.general || '$0.00',
    };
}

// Datos generales: acumulados del periodo y tiempo neto del encabezado
function actualizarAcumuladosChecador(totales = {}) {
    const valores = totalesChecador(totales);
    asignarValorChecador('kpi_tiempo_neto', valores.tiempo);
    asignarValorChecador('checador_total_header', valores.tiempo);
    asignarValorChecador('kpi_horas_decimales', valores.decimal);
    asignarValorChecador('kpi_pago_base', valores.pago);
    asignarValorChecador('kpi_bono', valores.bonos);
    asignarValorChecador('kpi_total_dia', valores.general);
}

// Marcas de tiempo: fila de totales al pie de la tabla
function actualizarTotalesTablaChecador(totales = {}) {
    const valores = totalesChecador(totales);
    asignarValorChecador('total_tiempo', valores.tiempo);
    asignarValorChecador('total_decimal', valores.decimal);
    asignarValorChecador('total_pago_h', valores.pago);
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
    const margen = { superior: 10, derecho: 20, inferior: 40, izquierdo: ancho < 480 ? 92 : 100 };
    const anchoGrafica = Math.max(1, ancho - margen.izquierdo - margen.derecho);
    const altoGrafica = Math.max(1, alto - margen.superior - margen.inferior);
    const baseY = margen.superior + altoGrafica;
    const valoresNumericos = valores.map(valor => Math.max(0, Number(valor) || 0));
    const maximoDatos = Math.max(...valoresNumericos, 0);
    const valoresConDatos = valoresNumericos.filter(valor => valor > 0);
    const promedio = valoresConDatos.reduce((total, valor) => total + valor, 0) / Math.max(1, valoresConDatos.length);
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
    // Cada fecha "dd/mm" ocupa unos 52px: si caben todas se muestran todas; si no, una de cada 2, 3… con el mismo salto
    const maximoEtiquetas = Math.max(2, Math.floor(anchoGrafica / 52));
    const salto = Math.max(1, Math.ceil(cantidad / maximoEtiquetas));
    const indicesEtiquetas = new Set();

    for (let indiceEtiqueta = 0; indiceEtiqueta < cantidad; indiceEtiqueta += salto) {
        indicesEtiquetas.add(indiceEtiqueta);
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
            y: alto - 12,
            class: 'attendance-chart-text',
            'text-anchor': 'middle',
            'font-size': 15,
        });
        const lineaFecha = crearElementoSvgChecador('tspan', { x: obtenerX(indice), dy: 0 });
        lineaFecha.textContent = fecha.corta;
        textoFecha.appendChild(lineaFecha);


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

// Días del periodo (inicio y fin en Y-m-d), para que la gráfica muestre también los días sin marcas
function diasPeriodoChecador(inicio, fin) {
    const [ai, mi, di] = String(inicio ?? '').split('-').map(Number);
    const [af, mf, df] = String(fin ?? '').split('-').map(Number);
    if (!ai || !af) return [];
    const dias = [];
    const actual = new Date(ai, mi - 1, di);
    const ultimo = new Date(af, mf - 1, df);
    while (actual <= ultimo && dias.length < 400) {
        dias.push(`${actual.getFullYear()}-${String(actual.getMonth() + 1).padStart(2, '0')}-${String(actual.getDate()).padStart(2, '0')}`);
        actual.setDate(actual.getDate() + 1);
    }
    return dias;
}

let ultimoPeriodoGraficasChecador = null;

function actualizarGraficasChecador(resumen = [], periodo = ultimoPeriodoGraficasChecador) {
    const reales = Array.isArray(resumen)
        ? [...resumen].filter(item => !item.relleno).sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
        : [];
    ultimoPeriodoGraficasChecador = periodo;
    ultimoResumenGraficasChecador = reales;
    // Los días del periodo que no tienen marcas se agregan en 0 para que no falte ninguno en la gráfica
    const porFecha = new Map(reales.map(item => [String(item.fecha), item]));
    const dias = periodo ? diasPeriodoChecador(periodo.inicio, periodo.fin) : [];
    const filas = reales.length === 0
        ? []
        : (dias.length > 0
            ? dias.map(dia => porFecha.get(dia) ?? { fecha: dia, tiempo_segundos: 0, total_raw: 0, relleno: true })
            : reales);

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
    const tiemposReales = reales.map(item => Number(item.tiempo_segundos) || 0);
    const gananciasReales = reales.map(item => Number(item.total_raw) || 0);
    const promedioTiempo = tiemposReales.reduce((total, valor) => total + valor, 0) / Math.max(1, tiemposReales.length);
    const promedioGanancias = gananciasReales.reduce((total, valor) => total + valor, 0) / Math.max(1, gananciasReales.length);

    asignarValorChecador('promedio_tiempo_checador', formatearSegundosChecador(promedioTiempo));
    asignarValorChecador('maximo_tiempo_checador', formatearSegundosChecador(Math.max(...tiemposReales)));
    asignarValorChecador('promedio_ganancias_checador', formatoMoneda.format(promedioGanancias));
    asignarValorChecador('maximo_ganancias_checador', formatoMoneda.format(Math.max(...gananciasReales)));

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

function establecerCargaChecador(prefijo, cargando) {
    const boton = document.getElementById(`btn_${prefijo}`);
    const iconoCarga = document.getElementById(`carga_${prefijo}`);
    const texto = document.getElementById(`texto_${prefijo}`);

    if (!boton || !iconoCarga || !texto) return;

    boton.disabled = cargando;
    iconoCarga.classList.toggle('hidden', !cargando);
    texto.textContent = cargando ? 'Consultando...' : 'Consultar periodo';
}

function fechaLargaChecador(fecha) {
    const [anio, mes, dia] = String(fecha).split('-').map(Number);
    if (!anio || !mes || !dia) return fecha;
    return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(anio, mes - 1, dia));
}

function periodoChecador(prefijo) {
    return {
        inicio: document.getElementById(`${prefijo}_inicio`)?.value,
        fin: document.getElementById(`${prefijo}_fin`)?.value,
    };
}

// Consulta las jornadas del checador de un periodo; cada cuadro la usa con sus propias fechas
async function consultarChecador(prefijo) {
    const employeeId = document.getElementById('employee_id').value;
    const { inicio, fin } = periodoChecador(prefijo);

    if (!employeeId || !inicio || !fin) {
        throw new Error('Tu usuario no tiene ID de checador asignado o faltan fechas.');
    }

    const texto = document.getElementById(`periodo_${prefijo}`);
    if (texto) texto.textContent = `Del ${fechaLargaChecador(inicio)} al ${fechaLargaChecador(fin)}`;

    const response = await fetch('{{ route("control-horas.consultar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            employee_id: employeeId,
            user_id: document.getElementById('attendance_user_id')?.value,
            inicio,
            fin,
        }),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Error de servidor.');
    return data;
}

// La tabla siempre muestra al menos 6 filas (las vacías van intercaladas), como en Cronómetro y Manager Control
function rellenarFilasChecador(tbody) {
    for (let i = tbody.rows.length; i < 6; i++) {
        const fila = document.createElement('tr');
        fila.className = 'ac-blank';
        fila.innerHTML = '<td></td>'.repeat(6);
        tbody.appendChild(fila);
    }
}

function mensajeTablaChecador(mensaje) {
    const tbody = document.getElementById('tabla_checador_body');
    if (!tbody) return;
    tbody.innerHTML = '';
    const fila = document.createElement('tr');
    const celda = document.createElement('td');
    celda.colSpan = 6;
    celda.textContent = mensaje;
    fila.appendChild(celda);
    tbody.appendChild(fila);
    rellenarFilasChecador(tbody);
}

function pintarTablaChecador(data) {
    const tbody = document.getElementById('tabla_checador_body');
    actualizarTotalesTablaChecador(data.totales_pie || {});

    if (!data.resumen || data.resumen.length === 0) {
        mensajeTablaChecador('No hay marcas registradas en el dispositivo para este ID en las fechas seleccionadas.');
        document.getElementById('checador_export_bar').classList.add('is-disabled');
        return;
    }

    tbody.innerHTML = '';
    data.resumen.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = item.requiere_revision ? 'attendance-row attendance-row-review' : 'attendance-row';
        tr.innerHTML = `
            <td data-label="Fecha"><span class="ac-fig">${cifrasChecador(fechaCortaChecador(item.fecha))}</span></td>
            <td data-label="Marcas / Chequeos" class="whitespace-normal break-words" title="${cifrasChecador(item.detalles_marcas).replace(/<[^>]+>/g, '')}">${cifrasChecador(item.detalles_marcas)}</td>
            <td data-label="Tiempo neto"><span class="ac-fig">${cifrasChecador(item.neto)}</span></td>
            <td data-label="Hrs decimales"><span class="ac-fig">${cifrasChecador(item.horas_decimal)}</span></td>
            <td data-label="Pago base"><span class="ac-fig">${cifrasChecador(item.pago_horas)}</span></td>
            <td data-label="Total del día"><span class="ac-fig">${cifrasChecador(item.total)}</span></td>
        `;
        // Las jornadas con marcas impares se marcan en rojo (ya no hay columna Estado)
        if (item.requiere_revision) tr.title = 'Marcas impares: revisa esta jornada';
        tbody.appendChild(tr);
    });
    rellenarFilasChecador(tbody);
    document.getElementById('checador_export_bar').classList.remove('is-disabled');
}

// Botón Consultar periodo de Marcas de tiempo
async function revisarMarcasChecador() {
    establecerCargaChecador('marks', true);
    try {
        pintarTablaChecador(await consultarChecador('marks'));
    } catch (err) {
        console.error(err);
        alert(err.message || 'Hubo un error al intentar consultar las marcas del checador.');
    } finally {
        establecerCargaChecador('marks', false);
    }
}

// Botón Consultar periodo de Datos generales
async function revisarDatosChecador() {
    establecerCargaChecador('general', true);
    try {
        const data = await consultarChecador('general');
        actualizarAcumuladosChecador(data.totales_pie || {});
        actualizarGraficasChecador(data.resumen || [], periodoChecador('general'));
    } catch (err) {
        console.error(err);
        alert(err.message || 'Hubo un error al intentar consultar las marcas del checador.');
    } finally {
        establecerCargaChecador('general', false);
    }
}

// Se conserva el nombre anterior: consulta los dos cuadros
function revisarHorasChecador() {
    revisarMarcasChecador();
    revisarDatosChecador();
}

// Las descargas usan el periodo de Marcas de tiempo
function exportarChecador(format) {
    const employeeId = document.getElementById('employee_id').value;
    const { inicio, fin } = periodoChecador('marks');

    if (!employeeId || !inicio || !fin) {
        alert('No hay datos suficientes para exportar.');
        return;
    }

    const params = new URLSearchParams({
        employee_id: employeeId,
        user_id: document.getElementById('attendance_user_id')?.value,
        inicio,
        fin,
    });

    window.location.href = `{{ url('/control-horas/export') }}/${format}?${params.toString()}`;
}

function cargarPeriodoInicialChecador() {
    actualizarAcumuladosChecador();
    actualizarTotalesTablaChecador();
    actualizarGraficasChecador([]);

    if (document.getElementById('employee_id')?.value) {
        mensajeTablaChecador('Consultando las marcas de los últimos 15 días...');
        revisarHorasChecador();
        return;
    }

    mensajeTablaChecador('Tu usuario no tiene un ID de checador asignado.');
}

window.revisarHorasChecador = revisarHorasChecador;
window.revisarMarcasChecador = revisarMarcasChecador;
window.revisarDatosChecador = revisarDatosChecador;
// Al cambiar de pestaña la gráfica visible se vuelve a dibujar con el ancho real de su contenedor
window.redibujarGraficasChecador = () => {
    if (ultimoResumenGraficasChecador.length > 0) actualizarGraficasChecador(ultimoResumenGraficasChecador);
};
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
