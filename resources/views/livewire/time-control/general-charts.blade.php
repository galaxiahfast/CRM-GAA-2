@php
    $fmt = fn (int $seconds): string => sprintf('%03dh %02dm %02ds', intdiv(max(0, $seconds), 3600), intdiv(max(0, $seconds) % 3600, 60), max(0, $seconds) % 60);
    // Cada cifra ocupa el mismo ancho (la letra Poppins no es tabular), igual que en Cronómetro y Manager Control
    $digits = fn (string $text) => new \Illuminate\Support\HtmlString(preg_replace('/\d/', '<span class="prod-dg">$0</span>', e($text)));
    $longDate = fn (string $date): string => \Illuminate\Support\Carbon::parse($date)->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $messages = [
        'no_user' => 'Elige un colaborador para ver sus gráficas.',
        'no_employee' => $isAdmin ? 'Este usuario del CRM no tiene un colaborador de ManagerControl asignado.' : 'Tu usuario no tiene un colaborador de ManagerControl asignado.',
        'error' => 'No se pudo consultar ManagerControl. Inténtalo de nuevo en unos minutos.',
    ];
    $userStep = $isAdmin ? [['target' => 'gc-user', 'title' => 'Elige el colaborador', 'description' => 'Las dos tarjetas muestran las gráficas del colaborador que elijas aquí.']] : [];
    $steps = fn (string $k, string $what) => [
        ['target' => "gc-{$k}-period", 'title' => 'Elige el periodo', 'description' => "Selecciona la fecha Desde y la fecha Hasta. Este periodo solo afecta a las gráficas de {$what}."],
        ['target' => "gc-{$k}-consult", 'title' => 'Consulta el periodo', 'description' => 'Presiona este botón para actualizar las gráficas con las fechas elegidas.'],
        ['target' => "gc-{$k}-tabs", 'title' => 'Cambia de gráfica', 'description' => 'Por día muestra el tiempo de cada día y su promedio (puedes filtrarlo por cliente o actividad). Por cliente y Por actividad muestran en qué se repartió el tiempo.'],
        ['target' => "gc-{$k}-chart", 'title' => 'Revisa la gráfica', 'description' => 'Pasa el cursor sobre una barra para ver el tiempo exacto con el formato 000h 00m 00s y, en las gráficas por cliente o actividad, su porcentaje.'],
        ['target' => "gc-{$k}-total", 'title' => 'Total del periodo', 'description' => "Es la suma de todo el tiempo de {$what} en el periodo elegido."],
    ];
    $numbered = fn (array $list) => array_map(fn ($step, $i) => ['target' => $step['target'], 'title' => ($i + 1).'. '.$step['title'], 'description' => $step['description']], $list, array_keys($list));
    $cronTour = $numbered(array_merge($userStep, $steps('cron', 'el Cronómetro')));
    $mcTour = $numbered(array_merge($userStep, $steps('mc', 'ManagerControl')));
@endphp

@assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endassets

{{-- Gráficos Generales: mismo diseño que Cronómetro y Manager Control (variables de panel, tema claro y oscuro, 15px y separaciones
     de 20px). Dos tarjetas: las gráficas del Cronómetro y las de ManagerControl; cada una con su periodo y una gráfica a la vez. --}}
<div
    data-panel
    data-productivity
    data-general-charts
    x-data="clockQuickTour(@js($cronTour), @js(['mc' => $mcTour]))"
    class="relative isolate flex min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 flex-col text-[15px]"
>
    <x-panel-styles />

    <style>
        /* Mismo diseño que Cronómetro, Inicio y Panel general: texto negro (blanco en oscuro) y azul; enlaces y botones en verde. */
        [data-productivity] { --panel-muted: var(--panel-text); --prod-green: #15803d; }
        html.module-dark-theme [data-productivity] { --prod-green: #4ade80; }
        [data-productivity] .prod-link { color: var(--prod-green); transition: opacity 150ms ease; }
        [data-productivity] .prod-link:hover { opacity: .75; }
        [data-productivity].prod-calendar { z-index: 200000; }
        [data-productivity].prod-calendar.prod-calendar-over-tour { z-index: 300003; }
        [data-productivity].prod-calendar { width: min(320px, calc((100vw - 32px) / var(--app-zoom, 1))); max-height: calc((100dvh - 32px) / var(--app-zoom, 1)); overflow-y: auto; box-shadow: 0 14px 35px rgba(0, 0, 0, .18); }
        [data-productivity].prod-calendar .prod-calendar-day { display: inline-flex; aspect-ratio: 1; align-items: center; justify-content: center; min-width: 0; border: 1px solid transparent; border-radius: 8px; color: var(--panel-text); }
        [data-productivity].prod-calendar .prod-calendar-day:hover { background-color: var(--panel-hover); }
        [data-productivity].prod-calendar .prod-calendar-day.is-outside { opacity: .5; }
        [data-productivity].prod-calendar .prod-calendar-day.is-today { border-color: var(--panel-border); }
        [data-productivity].prod-calendar .prod-calendar-day.is-selected { border-color: var(--panel-accent); background-color: color-mix(in srgb, var(--panel-accent) 14%, var(--panel-surface)); color: var(--panel-accent); opacity: 1; }
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
        [data-productivity] .prod-blank { counter-increment: none; cursor: default; }
        [data-productivity] .prod-counter::before { content: counter(prodrow); }
        /* Texto que puede partirse en varias líneas: 20px de espacio entre una línea y la siguiente */
        [data-productivity] .prod-lines { line-height: 35px; margin-bottom: -10px; }

        /* ===== Diseño adaptable: depende del ancho de cada tarjeta (el menú lateral y el zoom le quitan ancho) ===== */
        [data-productivity] .prod-wrap { container: prod / inline-size; }
        [data-productivity] .prod-summary-wrap { display: flex; flex-direction: column; }
        [data-productivity] .prod-summary-card { display: flex; flex-direction: column; flex: 1; min-height: 0; }
        [data-productivity] .prod-layout { display: grid; grid-template-columns: minmax(0, 1fr); }
        [data-productivity] .prod-aside { min-width: 0; border-bottom-width: 1px; }
        [data-productivity] .prod-form { display: grid; gap: 20px; padding: 20px; }
        [data-productivity] .prod-dates { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; }
        [data-productivity] .prod-label { display: block; min-height: 24px; margin-bottom: 20px; line-height: 24px; }
        [data-productivity] .prod-date-trigger { display: flex; min-height: 50px; align-items: center; justify-content: space-between; gap: 10px; text-align: left; cursor: pointer; }
        [data-productivity] .prod-guide-link { display: inline-flex; min-height: 24px; align-items: center; }
        [data-productivity] .prod-consult { display: grid; gap: 20px; }
        [data-productivity] .prod-consult .prod-label { margin-bottom: 0; }
        [data-productivity] .prod-main { display: flex; min-width: 0; flex-direction: column; }
        [data-productivity] .prod-dist { display: grid; grid-template-columns: minmax(0, 1fr); }
        [data-productivity] .prod-col { display: flex; min-width: 0; flex-direction: column; }
        [data-productivity] .prod-col + .prod-col { border-top-width: 1px; }
        [data-productivity] .prod-list { max-height: 360px; overflow-y: auto; }
        [data-productivity] .prod-grid { display: grid; grid-template-columns: 70px minmax(0, 1fr); align-items: stretch; }
        [data-productivity] .prod-index { display: flex; align-items: center; justify-content: center; border-right-width: 1px; }
        [data-productivity] .prod-cell { display: flex; min-width: 0; flex-direction: column; justify-content: center; padding: 20px; }
        [data-productivity] .prod-item { height: 70px; padding: 0; }
        /* Tiempo, barra y porcentaje van pegados a la derecha; el nombre queda a la izquierda */
        /* El nombre ocupa todo el espacio libre y termina 20px antes del tiempo; tiempo, barra y porcentaje van pegados a la derecha, 20px entre cada uno */
        [data-productivity] .prod-metrics > :nth-child(n+2) { margin-left: 20px; }
        /* El nombre mide lo mismo que su texto (no todo el ancho de la columna); si es largo se corta con "…" */
        [data-productivity] .prod-metrics > :nth-child(1) { justify-self: start; max-width: 100%; }
        /* Línea que separa el nombre de los datos (tiempo, barra y porcentaje) */
        /* Tiempo: ancho fijo (las cifras de la fuente no miden lo mismo) para que la línea y la barra queden alineadas en todas las filas; 20px a cada lado de la línea */
        [data-productivity] .prod-metrics > :nth-child(2) { position: relative; margin-left: 40px; width: 4.3em; text-align: left; }
        [data-productivity] .prod-metrics > :nth-child(2)::before { content: ''; position: absolute; top: -200px; bottom: -200px; left: -20px; border-left: 1px solid var(--panel-border); pointer-events: none; visibility: visible; }
        [data-productivity] .prod-metrics { overflow: hidden; }
        [data-productivity] .prod-metrics > :nth-child(4) { width: 2.4em; text-align: right; }
        /* Los tiempos quedan alineados a la derecha, en la misma columna en todas las filas */
        [data-productivity] .prod-guide { display: flex; flex-direction: column; gap: 20px; }
        [data-productivity] .prod-guide > a { width: fit-content; }
        [data-productivity] .prod-metrics { display: grid; grid-template-columns: minmax(0, 1fr) max-content minmax(50px, min(230px, 34%)) max-content; align-items: center; gap: 0; height: 100%; box-sizing: border-box; padding-top: 14px; padding-bottom: 14px; }
        [data-productivity] .prod-item:nth-child(odd) { background-color: var(--panel-header); }
        [data-productivity] .prod-track { height: 6px; overflow: hidden; border-radius: 9999px; background-color: var(--panel-track); }
        [data-productivity] .prod-fill { height: 100%; border-radius: 9999px; background-color: var(--panel-accent); }
        /* El detalle muestra siempre 7 filas exactas (7 × 70px); el encabezado de cada día cuenta como una fila */
        [data-productivity] .prod-detail { position: relative; max-height: 490px; overflow-y: auto; }
        [data-productivity] .prod-blank-row { height: 70px; box-sizing: border-box; }
        [data-productivity] .prod-blank-row:nth-child(odd) { background-color: var(--panel-header); }
        [data-productivity] .prod-detail-card { display: flex; flex-direction: column; }
        [data-productivity] .prod-detail-wrap { position: relative; min-height: 0; }
        [data-productivity] .prod-detail-cols { display: grid; }
        /* Tabla del detalle: columnas separadas por líneas, 20px de espacio a cada lado y 15px entre las líneas de texto de una celda */
        [data-productivity] .prod-dgrid { display: grid; grid-template-columns: 60px repeat(3, minmax(240px, 1fr)) repeat(2, 140px); }
        [data-productivity] .prod-dcell { display: flex; min-width: 0; align-items: center; box-sizing: border-box; padding: 0 20px; border-right-width: 1px; }
        [data-productivity] .prod-dgrid > .prod-dcell:last-child { border-right-width: 0; }
        [data-productivity] .prod-dindex { justify-content: center; padding: 0; }
        [data-productivity] .prod-dg { display: inline-block; width: 0.64em; text-align: center; }
        [data-productivity] .prod-dcell[data-label="Horario"], [data-productivity] .prod-dcell[data-label="Duración"] { justify-content: center; }
        [data-productivity] .prod-dcell[data-label="Horario"] > span { width: auto; max-width: 100%; }
        [data-productivity] .prod-detail { counter-reset: prodd; }
        [data-productivity] .prod-drow { counter-increment: prodd; min-height: 70px; box-sizing: border-box; cursor: grab; }
        [data-productivity] .prod-dcounter::before { content: counter(prodd); }
        [data-productivity] .prod-drow:nth-child(even) { background-color: var(--panel-header); }
        [data-productivity] .prod-hide:hover { background-color: var(--panel-hover); }
        [data-productivity] .prod-blank-row:nth-child(n) { background-color: var(--panel-surface); }
        [data-productivity] .prod-blank-row.mc-stripe:nth-child(n) { background-color: var(--panel-header); }
        [data-productivity] .prod-drow:hover { background-color: var(--panel-hover); }
        /* Fila del día: usa las mismas columnas y líneas de la tabla, con un tono azul muy suave para distinguirla de las actividades */
        [data-productivity] .prod-day-head { height: 70px; box-sizing: border-box; background-color: color-mix(in srgb, var(--panel-accent) 9%, var(--panel-surface)); }
        [data-productivity] .prod-row { cursor: grab; }
        /* La última fila de cada día ya trae su línea inferior: no se agrega otra arriba del día siguiente (evita la línea doble) */
        /* La numeración (N°) empieza en 1 en cada día */
        [data-productivity] .prod-day { counter-reset: prodd; }
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
        /* Pantallas angostas: la tabla conserva su forma y se desplaza en horizontal con el scroll delgado */
        [data-productivity] .prod-detail-card .prod-main { overflow-x: auto; scrollbar-width: thin; scrollbar-color: var(--panel-accent) transparent; }
        [data-productivity] .prod-detail-card .prod-main::-webkit-scrollbar { height: 4px; }
        [data-productivity] .prod-detail-card .prod-main::-webkit-scrollbar-track { background: transparent; }
        [data-productivity] .prod-detail-card .prod-main::-webkit-scrollbar-thumb { background: var(--panel-accent); border-radius: 9999px; }
        [data-productivity] .prod-detail-card .prod-main > *, [data-productivity] .prod-detail-card .prod-main > .contents > * { min-width: 1060px; }
        /* Escritorio: la tarjeta principal ocupa el alto disponible, como en Cronómetro. */
        @container prod (min-width: 1240px) {
            [data-productivity] .prod-summary-card { min-height: max(600px, calc(var(--app-dvh) - 348px)); }
            [data-productivity] .prod-layout { flex: 1; min-height: 0; grid-template-columns: 320px minmax(0, 1fr); grid-template-rows: minmax(0, 1fr); }
            [data-productivity] .prod-aside { display: flex; min-height: 0; flex-direction: column; overflow-y: auto; border-bottom-width: 0; border-right-width: 1px; }
            [data-productivity] .prod-form { display: flex; flex: 1; flex-direction: column; gap: 0; justify-content: space-between; }
            [data-productivity] .prod-dates, [data-productivity] .prod-date-field, [data-productivity] .prod-consult, [data-productivity] .prod-guide { display: contents; }
            [data-productivity] .prod-label, [data-productivity] .prod-guide-link { min-height: 0; margin-bottom: 0; line-height: 15px; }
            [data-productivity] .prod-primary { min-height: 50px; padding-top: 0; padding-bottom: 0; line-height: 15px; }
            [data-productivity] .prod-main { min-height: 0; }
            [data-productivity] .prod-dist { min-height: 0; flex: 1; grid-template-rows: minmax(0, 1fr); }
            [data-productivity] .prod-col { min-height: 0; }
            [data-productivity] .prod-list-wrap { flex: 1; }
            /* La lista muestra siempre 7 filas exactas, igual que la tabla del Cronómetro */
            [data-productivity] .prod-list { position: absolute; inset: 0; max-height: none; container-type: size; }
            [data-productivity] .prod-item { height: max(59px, calc(100cqh / 7)); }
            /* Detalle por día: tarjeta de alto fijo con 7 filas exactas */
            /* Mismo alto que la tarjeta de arriba, para que las filas de ambas midan lo mismo */
            [data-productivity] .prod-detail-card { min-height: max(600px, calc(var(--app-dvh) - 348px)); }
            [data-productivity] .prod-detail-wrap { flex: 1; }
            [data-productivity] .prod-detail { position: absolute; inset: 0; max-height: none; container-type: size; }
            [data-productivity] .prod-detail .prod-day-head, [data-productivity] .prod-detail .prod-blank-row, [data-productivity] .prod-detail .prod-drow { height: max(59px, calc(100cqh / 7)); }
        }

        /* ===== Gráficos Generales ===== */
        /* Fila de pestañas: mide lo mismo que el encabezado «Periodo de consulta» (87px) para que las líneas queden a la misma altura */
        /* Misma altura (87px) que el encabezado «Periodo de consulta» del formulario, para que las líneas del panel queden alineadas */
        [data-general-charts] .gc-tabs { display: flex; height: 87px; box-sizing: border-box; flex-wrap: nowrap; align-items: center; justify-content: space-between; gap: 20px; padding: 20px; overflow-x: auto; }
        [data-general-charts] .gc-tablist { display: flex; flex-wrap: nowrap; gap: 20px; flex-shrink: 0; }
        /* Mismos 20px a los lados y 15px arriba/abajo que el resto de los botones del módulo (p. ej. .prod-primary) */
        [data-general-charts] .gc-tab { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--panel-border); border-radius: 12px; padding: 15px 20px; background-color: var(--panel-surface); color: var(--panel-text); line-height: 1; white-space: nowrap; cursor: pointer; transition: background-color 150ms ease; }
        [data-general-charts] .gc-tab:hover { background-color: var(--panel-hover); }
        [data-general-charts] .gc-tab[aria-selected="true"] { border-color: var(--prod-green); background-color: color-mix(in srgb, var(--prod-green) 12%, var(--panel-surface)); color: var(--prod-green); }
        [data-general-charts] .gc-filters { display: flex; flex-wrap: nowrap; flex-shrink: 0; gap: 20px; }
        [data-general-charts] .gc-filters > * { width: 240px; max-width: 100%; flex-shrink: 0; }
        [data-general-charts] .gc-chart-area { position: relative; display: flex; min-height: 420px; flex: 1; flex-direction: column; }
        [data-general-charts] .gc-pane { display: flex; min-height: 0; flex: 1; flex-direction: column; padding: 20px; }
        [data-general-charts] .gc-canvas-box { position: relative; min-height: 380px; flex: 1; }
        [data-general-charts] .gc-canvas-box canvas { position: absolute; inset: 0; }
        /* El tema oscuro general invierte los canvas; estas gráficas ya toman sus colores del tema, así que no se invierten */
        html.module-dark-theme [data-general-charts] .gc-canvas-box canvas { filter: none !important; }
        [data-general-charts] .gc-empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
        [data-general-charts] .gc-message { display: flex; flex: 1; align-items: center; justify-content: center; padding: 20px; text-align: center; }
        /* Las listas de los filtros flotan por encima de todo, como en el resto de los formularios */
        [data-general-charts] .panel-scroll { scrollbar-width: none; }
        @container prod (min-width: 1240px) {
            [data-general-charts] .gc-chart-area { min-height: 0; }
            [data-general-charts] .gc-canvas-box { min-height: 0; }
        }
    </style>

    <main class="relative z-10 flex w-full min-h-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]">
        <section class="flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="min-w-0 max-w-full">
                <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">Gráficos Generales</h1>
                <p class="prod-lines mt-[5px]">Consulta en gráficas el tiempo registrado en el Cronómetro y en ManagerControl.</p>
            </div>
            @if ($isAdmin)
                <div id="gc-user" class="flex flex-wrap items-center gap-[20px]">
                    <x-panel-select id="gc-user-select" label="Colaborador" property="userId" searchable hover-scroll placeholder="Buscar colaborador…"
                        :options="$users" class="min-w-[280px]" />
                </div>
            @else
                <span class="panel-accent inline-flex items-center gap-[10px] leading-none"><span aria-hidden="true">—</span>Sincronizado en tiempo real</span>
            @endif
        </section>

        @if (true)
        <div class="prod-wrap prod-summary-wrap">
            <section id="gc-cron" class="prod-summary-card gc-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Cronómetro" x-data="{ tab: 'day' }">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Cronómetro</h2>
                        <p class="prod-lines mt-[5px]">Del {{ $longDate($cronFrom) }} al {{ $longDate($cronTo) }}</p>
                    </div>
                    <p id="gc-cron-total" class="leading-none">Total del periodo: <span class="whitespace-nowrap">{{ $digits($fmt($cron['total'])) }}</span></p>
                </header>
                <div class="prod-layout">
                    <x-productivity-period-aside
                        from-prop="cronFrom" to-prop="cronTo" :values="['cronFrom' => $cronFrom, 'cronTo' => $cronTo]"
                        consult-method="applyCron" consult-id="gc-cron-consult" period-id="gc-cron-period"
                        guide="panel-control" guide-title="Descargar la guía de Gráficos Generales en PDF"  />

                    <div class="prod-main">
                        <div id="gc-cron-tabs" class="gc-tabs panel-surface panel-border border-b">
                            <div class="gc-tablist" role="tablist" aria-label="Gráficas de Cronómetro">
                                @foreach (['day' => 'Por día', 'clients' => 'Por cliente', 'activities' => 'Por actividad'] as $tabKey => $tabLabel)
                                    <button type="button" role="tab" @click="tab = '{{ $tabKey }}'" :aria-selected="tab === '{{ $tabKey }}' ? 'true' : 'false'" class="gc-tab focus:outline-none focus:ring-0">{{ $tabLabel }}</button>
                                @endforeach
                            </div>
                            @if ($cron['state'] === 'ok')
                                <div x-show="tab === 'day'" class="gc-filters">
                                    <x-panel-select id="gc-cron-client" label="Cliente" property="cronClient" searchable floating hover-scroll placeholder="Buscar cliente…"
                                        :options="array_merge([['value' => '', 'label' => 'Todos los clientes']], $cron['clientOptions'])" />
                                    <x-panel-select id="gc-cron-activity" label="Actividad" property="cronActivity" searchable floating hover-scroll placeholder="Buscar actividad…"
                                        :options="array_merge([['value' => '', 'label' => 'Todas las actividades']], $cron['activityOptions'])" />
                                </div>
                            @endif
                        </div>
                        <div id="gc-cron-chart" class="gc-chart-area">
                            @if ($cron['state'] === 'ok')
                                @foreach (['day' => $cron['day'], 'clients' => $cron['clients'], 'activities' => $cron['activities']] as $paneKey => $paneConfig)
                                    <div x-show="tab === '{{ $paneKey }}'" class="gc-pane">
                                        <div wire:key="gc-cron-{{ $paneKey }}-{{ md5(json_encode($paneConfig)) }}" wire:ignore x-data="generalChart(@js($paneConfig))" class="gc-canvas-box">
                                            <canvas x-ref="canvas" role="img" aria-label="Gráfica {{ $paneKey === 'day' ? 'de tiempo por día' : ($paneKey === 'clients' ? 'de tiempo por cliente' : 'de tiempo por actividad') }}"></canvas>
                                            <p x-show="empty" class="gc-empty">Sin tiempo registrado en el periodo.</p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="gc-message" role="status" data-testid="gc-cron-message">
                                    <p class="leading-[30px]">{{ $messages[$cron['state']] ?? $messages['error'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </div>
        @endif

        @if ($mc['state'] !== 'no_access')
        <div class="prod-wrap prod-summary-wrap">
            <section id="gc-mc" class="prod-summary-card gc-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Manager Control" x-data="{ tab: 'day' }">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Manager Control</h2>
                        <p class="prod-lines mt-[5px]">Del {{ $longDate($mcFrom) }} al {{ $longDate($mcTo) }}@if ($mc['employee']) · {{ $mc['employee'] }}@endif</p>
                    </div>
                    <p id="gc-mc-total" class="leading-none">Total del periodo: <span class="whitespace-nowrap">{{ $digits($fmt($mc['total'])) }}</span></p>
                </header>
                <div class="prod-layout">
                    <x-productivity-period-aside
                        from-prop="mcFrom" to-prop="mcTo" :values="['mcFrom' => $mcFrom, 'mcTo' => $mcTo]"
                        consult-method="applyMc" consult-id="gc-mc-consult" period-id="gc-mc-period"
                        guide="panel-control" guide-title="Descargar la guía de Gráficos Generales en PDF" tour-set="mc" />

                    <div class="prod-main">
                        <div id="gc-mc-tabs" class="gc-tabs panel-surface panel-border border-b">
                            <div class="gc-tablist" role="tablist" aria-label="Gráficas de Manager Control">
                                @foreach (['day' => 'Por día', 'clients' => 'Por cliente', 'activities' => 'Por actividad'] as $tabKey => $tabLabel)
                                    <button type="button" role="tab" @click="tab = '{{ $tabKey }}'" :aria-selected="tab === '{{ $tabKey }}' ? 'true' : 'false'" class="gc-tab focus:outline-none focus:ring-0">{{ $tabLabel }}</button>
                                @endforeach
                            </div>
                            @if ($mc['state'] === 'ok')
                                <div x-show="tab === 'day'" class="gc-filters">
                                    <x-panel-select id="gc-mc-client" label="Cliente" property="mcClient" searchable floating hover-scroll placeholder="Buscar cliente…"
                                        :options="array_merge([['value' => '', 'label' => 'Todos los clientes']], $mc['clientOptions'])" />
                                    <x-panel-select id="gc-mc-activity" label="Actividad" property="mcActivity" searchable floating hover-scroll placeholder="Buscar actividad…"
                                        :options="array_merge([['value' => '', 'label' => 'Todas las actividades']], $mc['activityOptions'])" />
                                </div>
                            @endif
                        </div>
                        <div id="gc-mc-chart" class="gc-chart-area">
                            @if ($mc['state'] === 'ok')
                                @foreach (['day' => $mc['day'], 'clients' => $mc['clients'], 'activities' => $mc['activities']] as $paneKey => $paneConfig)
                                    <div x-show="tab === '{{ $paneKey }}'" class="gc-pane">
                                        <div wire:key="gc-mc-{{ $paneKey }}-{{ md5(json_encode($paneConfig)) }}" wire:ignore x-data="generalChart(@js($paneConfig))" class="gc-canvas-box">
                                            <canvas x-ref="canvas" role="img" aria-label="Gráfica {{ $paneKey === 'day' ? 'de tiempo por día' : ($paneKey === 'clients' ? 'de tiempo por cliente' : 'de tiempo por actividad') }}"></canvas>
                                            <p x-show="empty" class="gc-empty">Sin tiempo registrado en el periodo.</p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="gc-message" role="status" data-testid="gc-mc-message">
                                    <p class="leading-[30px]">{{ $messages[$mc['state']] ?? $messages['error'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </div>
        @endif

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
</div>
