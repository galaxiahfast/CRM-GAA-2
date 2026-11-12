@php
    $fmt = fn (int $seconds): string => \App\Services\ManagerControl\HoursSummary::format($seconds);
    $pct = fn (int $part, int $whole): int => $whole > 0 ? (int) round($part * 100 / $whole) : 0;
    $digits = fn (string $text) => new \Illuminate\Support\HtmlString(preg_replace('/\d/', '<span class="prod-dg">$0</span>', e($text)));
    $longDate = fn (string $date) => \Illuminate\Support\Carbon::parse($date)->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $tourSteps = [
        ['target' => 'prod-tour-employee', 'title' => '1. Elige el colaborador', 'description' => 'La lista sale de ManagerControl (solo lectura).'],
        ['target' => 'prod-tour-period', 'title' => '2. Elige el periodo', 'description' => 'Selecciona la fecha Desde y la fecha Hasta. Al abrir son 15 días: hoy y los 14 anteriores.'],
        ['target' => 'prod-tour-consult', 'title' => '3. Consulta el periodo', 'description' => 'Lee la base de ManagerControl con una consulta SELECT; nada se escribe ni se modifica.'],
        ['target' => 'prod-tour-clients', 'title' => '4. Distribuciones', 'description' => 'Tiempo por cliente y por actividad, con barra y porcentaje. Siempre se ven siete filas exactas.'],
        ['target' => 'prod-tour-detail', 'title' => '5. Registro de horas', 'description' => 'Departamento o cliente, actividad, descripción y tiempo de cada registro, agrupado por día.'],
    ];
    $detailTourSteps = [
        ['target' => 'prod-d-period', 'title' => '1. Elige el periodo del detalle', 'description' => 'Este periodo es independiente del de arriba: solo afecta al Registro de horas por día.'],
        ['target' => 'prod-d-consult', 'title' => '2. Consulta el periodo', 'description' => 'Presiona este botón para actualizar el detalle con las fechas elegidas.'],
        ['target' => 'prod-d-export', 'title' => '3. Descarga el reporte', 'description' => 'Descarga en PDF el periodo del detalle. El recorrido no descarga nada; solo te muestra dónde hacerlo.'],
        ['target' => 'prod-d-list', 'title' => '4. Registro por día', 'description' => 'Cada día muestra su total y debajo cada registro con cliente, actividad, descripción y tiempo. Gira la rueda del ratón para moverte una fila a la vez.'],
    ];
    $linksTourSteps = [
        ['target' => 'mc-l-search', 'title' => '1. Busca un usuario', 'description' => 'Escribe parte del nombre o del correo del usuario del CRM.'],
        ['target' => 'mc-l-assigned', 'title' => '2. Revisa cuántos van', 'description' => 'Asignados muestra cuántos usuarios del CRM ya tienen su colaborador de ManagerControl.'],
        ['target' => 'mc-l-list', 'title' => '3. Asigna el colaborador', 'description' => 'Haz clic en la celda Colaborador de ManagerControl, escribe para buscar y elige a la persona: se guarda sola. La × quita la asignación.'],
    ];
    $padTotal = fn (int $seconds) => sprintf('%03dh %02dm %02ds', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    $detailRows = 0;
    $nextStripe = false; // rayado: la primera fila siempre va en blanco
@endphp

<div
    data-panel
    data-productivity
    x-data="clockQuickTour(@js($tourSteps), @js(['detail' => $detailTourSteps, 'links' => $linksTourSteps]))"
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
        [data-productivity] .prod-item:nth-child(even) { background-color: var(--panel-header); } /* la primera fila va con fondo blanco */
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
        [data-productivity] .prod-dgrid { display: grid; grid-template-columns: 130px 205px minmax(0, 1fr) minmax(0, 1fr); }
        [data-productivity] .prod-dcell { display: flex; min-width: 0; align-items: center; box-sizing: border-box; padding: 0 20px; border-right-width: 1px; }
        [data-productivity] .prod-dgrid > .prod-dcell:last-child { border-right-width: 0; }
        [data-productivity] .prod-dindex { justify-content: center; padding: 0; }
        [data-productivity] .prod-dg { display: inline-block; width: 0.64em; text-align: center; }
        [data-productivity] .prod-dcell[data-label="Horario"] { justify-content: center; }
        [data-productivity] .prod-dcell[data-label="Horario"] > span { width: auto; max-width: 100%; }
        [data-productivity] .prod-detail { counter-reset: prodd; }
        [data-productivity] .prod-drow { counter-increment: prodd; min-height: 70px; box-sizing: border-box; cursor: grab; }
        [data-productivity] .prod-dcounter::before { content: counter(prodd); }
        [data-productivity] .prod-drow:nth-child(even) { background-color: var(--panel-header); }
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
        [data-productivity] .prod-detail-card .prod-main > * { min-width: 760px; }
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
        /* Tabla del registro de ManagerControl: N°, Departamento o cliente, Actividad, Descripción y Tiempo */
        [data-productivity] .mc-grid { grid-template-columns: 90px minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.4fr) 130px 100px; }
        [data-productivity] .mc-action { display: inline-flex; height: 28px; width: 28px; align-items: center; justify-content: center; border-radius: 8px; }
        [data-productivity] button.mc-action:hover { background-color: var(--panel-hover); }
        @container prod (min-width: 1240px) { [data-productivity] #prod-tour-links { min-height: max(600px, calc(var(--app-dvh) - 348px)); } }
        [data-productivity] #prod-tour-links .prod-main { flex: 1; min-height: 0; }
        [data-productivity].prod-calendar .panel-option.is-active { background-color: var(--panel-subtle); }
        [data-productivity] .prod-detail .prod-drow:nth-child(n), [data-productivity] .prod-detail .prod-blank-row:nth-child(n) { background-color: var(--panel-surface); }
        [data-productivity] .prod-detail .mc-stripe.mc-stripe { background-color: var(--panel-header); }
        [data-productivity] .prod-detail .prod-drow.prod-drow:hover { background-color: var(--panel-hover); }
        [data-productivity] .mc-link-grid { grid-template-columns: 90px repeat(3, minmax(0, 1fr)) minmax(0, 1.3fr); }
        [data-productivity] .mc-alert { border: 1px solid var(--panel-border); border-radius: 12px; padding: 20px; background-color: var(--panel-header); }
        [data-productivity] .prod-detail-card .prod-main > * { min-width: 900px; }
    </style>

    <main class="relative z-10 flex w-full min-h-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]">
        <section class="flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="min-w-0 max-w-full">
                <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">{{ $ownMode ? 'Control Gerencial' : 'ManagerControl' }}</h1>
                <p class="prod-lines mt-[5px]">{{ $ownMode ? 'Tus actividades de ManagerControl.' : 'Horas laboradas por colaborador.' }}</p>
            </div>
            @if ($ownMode)
                <span class="panel-accent inline-flex items-center gap-[10px] leading-none"><span aria-hidden="true">—</span>Solo lectura</span>
            @else
                <div id="prod-tour-employee" class="flex flex-wrap items-center gap-[20px]">
                    <x-panel-select id="mc-employee" label="Colaborador" property="employeeId" searchable placeholder="Buscar colaborador…"
                        :options="collect($employees)->map(fn ($employee) => ['value' => (string) $employee['id'], 'label' => $employee['name']])->all()"
                        class="min-w-[280px]" />
                </div>
            @endif
        </section>

        @if ($error)
            <div class="mc-alert" role="alert" data-testid="managercontrol-error">
                <h2 class="panel-accent leading-none">No se pudo consultar ManagerControl</h2>
                <p class="prod-lines mt-[5px]">{{ $error }}</p>
            </div>
        @endif

        <div class="prod-wrap prod-summary-wrap">
            <section class="prod-summary-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Productividad del periodo">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Productividad del periodo</h2>
                        <p class="prod-lines mt-[5px]">Del {{ $longDate($appliedFrom) }} al {{ $longDate($appliedTo) }}</p>
                    </div>
                    <div @if ($ownMode) id="prod-tour-employee" @endif class="flex flex-wrap items-center gap-[20px]">
                        @if ($loaded)
                            <a href="{{ route($pdfRoute, $pdfExtra + ['from' => $appliedFrom, 'to' => $appliedTo]) }}" class="prod-link leading-none">Descargar reporte PDF</a>
                        @else
                            <span class="prod-link leading-none" style="opacity:.5;cursor:not-allowed" aria-disabled="true" title="Elige un colaborador para descargar el reporte">Descargar reporte PDF</span>
                        @endif
                    </div>
                </header>
                <div class="prod-layout">
                    <x-productivity-period-aside
                        from-prop="from" to-prop="to" :values="['from' => $from, 'to' => $to]"
                        consult-method="applyFilters" consult-id="prod-tour-consult" period-id="prod-tour-period"
                        guide="managercontrol" guide-title="Descargar la guía de ManagerControl en PDF" />

                    <div class="prod-main">
                        <div class="prod-dist">
                            @foreach ([['title' => 'Distribución por cliente', 'items' => $summary->byCustomer, 'nameWidth' => '10.3em', 'tourId' => 'prod-tour-clients'], ['title' => 'Distribución por actividad', 'items' => $summary->byActivity, 'nameWidth' => '11.4em', 'tourId' => 'prod-tour-activities']] as $distribution)
                                <section id="{{ $distribution['tourId'] }}" class="prod-col panel-border" style="--prod-name-w: {{ $distribution['nameWidth'] }}">
                                    <div class="prod-grid panel-border box-border h-[87px] shrink-0 border-b">
                                        <h3 class="prod-index panel-border leading-none">N°</h3>
                                        <h3 class="prod-cell leading-none">{{ $distribution['title'] }}</h3>
                                    </div>
                                    <div data-scroll-on-hover x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="prod-list-wrap">
                                    <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const rows = $el.querySelectorAll('.prod-item'); if (! rows.length) return; const height = $el.scrollHeight / rows.length; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.max(0, Math.round($el.scrollTop / height) * height)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" data-row-step=".prod-item" class="prod-list panel-scroll">
                                        @foreach ($distribution['items'] as $name => $seconds)
                                            <div class="prod-item prod-grid">
                                                <span class="prod-index prod-counter panel-border leading-none"></span>
                                                <div class="prod-cell prod-metrics">
                                                    <span class="truncate leading-none" title="{{ $name }}">{{ $name }}</span>
                                                    <span class="tabular-nums leading-none">{{ $fmt($seconds) }}</span>
                                                    <div class="prod-track"><div class="prod-fill" style="width: {{ $pct($seconds, $summary->totalSeconds) }}%"></div></div>
                                                    <span class="text-right tabular-nums leading-none">{{ $pct($seconds, $summary->totalSeconds) }}%</span>
                                                </div>
                                            </div>
                                        @endforeach
                                        @for ($slot = count($distribution['items']); $slot < 7; $slot++)
                                            <div class="prod-item prod-grid prod-blank" aria-hidden="true">
                                                <span class="prod-index panel-border"></span>
                                                <div class="prod-cell prod-metrics">
                                                    <span></span>
                                                    <span class="tabular-nums leading-none" style="visibility:hidden">00:00:00</span>
                                                    <div class="prod-track" style="visibility:hidden"></div>
                                                    <span class="text-right tabular-nums leading-none" style="visibility:hidden">0%</span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                    <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="prod-wrap">
            <section id="prod-tour-detail" class="prod-detail-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Registro de horas de ManagerControl">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Registro de horas por día</h2>
                        <p class="prod-lines mt-[5px]">Del {{ $longDate($appliedDetailFrom) }} al {{ $longDate($appliedDetailTo) }}</p>
                        @if ($writeNotice)<p class="prod-lines mt-[5px]" role="status" data-testid="write-notice">{{ $writeNotice }}</p>@endif
                    </div>
                    <div id="prod-d-export" class="flex flex-wrap items-center gap-[20px]">
                        @if ($canWrite)
                            <button type="button" wire:click="openCreate" class="prod-link cursor-pointer leading-none focus:outline-none focus:ring-0">Agregar actividad</button>
                        @else
                            <span class="prod-link leading-none" style="opacity:.5;cursor:not-allowed" aria-disabled="true" title="Solo lectura: agregar, editar y dar de baja se activará cuando se den los permisos de escritura en ManagerControl.">Agregar actividad</span>
                        @endif
                        @if ($detailLoaded)
                            <a href="{{ route($pdfRoute, $pdfExtra + ['from' => $appliedDetailFrom, 'to' => $appliedDetailTo, 'formato' => 'managercontrol']) }}" class="prod-link leading-none">Descargar reporte PDF</a>
                        @else
                            <span class="prod-link leading-none" style="opacity:.5;cursor:not-allowed" aria-disabled="true" title="Elige un colaborador para descargar el reporte">Descargar reporte PDF</span>
                        @endif
                    </div>
                </header>
                <div class="prod-layout">
                    <x-productivity-period-aside
                        from-prop="detailFrom" to-prop="detailTo" :values="['detailFrom' => $detailFrom, 'detailTo' => $detailTo]"
                        consult-method="applyDetailFilters" consult-id="prod-d-consult" period-id="prod-d-period"
                        guide="managercontrol-detalle" guide-title="Descargar la guía del Registro de horas por día en PDF" tour-set="detail" />

                    <div class="prod-main">
                    <div class="prod-detail-cols prod-dgrid mc-grid panel-surface panel-border box-border h-[87px] shrink-0 border-b" aria-hidden="true">
                        <span class="prod-dcell prod-dindex panel-border">N°</span>
                        <span class="prod-dcell panel-border">Departamento o cliente</span>
                        <span class="prod-dcell panel-border">Actividad</span>
                        <span class="prod-dcell panel-border">Descripción</span>
                        <span class="prod-dcell panel-border" style="justify-content:center">Tiempo</span>
                        <span class="prod-dcell panel-border" style="justify-content:center">Acción</span>
                    </div>
                    <div id="prod-d-list" data-scroll-on-hover x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="prod-detail-wrap">
                        <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const tops = [...$el.querySelectorAll('[data-snap]')].map((e) => e.offsetTop); if (! tops.length) return; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, tops.reduce((a, b) => Math.abs(b - $el.scrollTop) < Math.abs(a - $el.scrollTop) ? b : a)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" data-row-step="[data-snap]" class="prod-detail panel-scroll">
                            @foreach ($detail->byDay as $date => $dayRows)
                                @php $detailRows += 1 + count($dayRows); @endphp
                                <section class="prod-day panel-border">
                                    <header data-snap class="prod-day-head prod-dgrid mc-grid panel-border border-b">
                                        <span class="prod-dcell prod-dindex panel-border"><span class="whitespace-nowrap leading-none">{{ $digits(\Illuminate\Support\Carbon::parse($date)->format('d/m/y')) }}</span></span>
                                        <span class="prod-dcell panel-border"><span class="truncate leading-none">{{ count(array_unique(array_column($dayRows, 'customer'))) }} cliente(s)</span></span>
                                        <span class="prod-dcell panel-border"><span class="truncate leading-none">{{ count($dayRows) }} registro(s)</span></span>
                                        <span class="prod-dcell panel-border"><span class="truncate leading-none">{{ count(array_filter(array_column($dayRows, 'description'), fn ($text) => $text !== '')) }} descripción(es)</span></span>
                                        <span class="prod-dcell panel-border" style="justify-content:center"><span class="whitespace-nowrap leading-none tabular-nums">{{ $digits($padTotal(array_sum(array_column($dayRows, 'seconds')))) }}</span></span>
                                        <span class="prod-dcell panel-border"></span>
                                    </header>
                                    @foreach ($dayRows as $row)
                                        <article data-snap class="prod-drow prod-dgrid mc-grid panel-border border-b {{ $loop->iteration % 2 === 0 ? 'mc-stripe' : '' }}">
                                            <span class="prod-dcell prod-dindex prod-dcounter panel-border"></span>
                                            <span class="prod-dcell panel-border"><span class="truncate leading-none" title="{{ $row['customer'] }}">{{ $row['customer'] }}</span></span>
                                            <span class="prod-dcell panel-border"><span class="truncate leading-none" title="{{ $row['activity'] }}">{{ $row['activity'] }}</span></span>
                                            <span class="prod-dcell panel-border"><span class="truncate leading-none" title="{{ $row['description'] }}">{{ $row['description'] !== '' ? $row['description'] : '—' }}</span></span>
                                            <span class="prod-dcell panel-border" style="justify-content:center"><span class="whitespace-nowrap leading-none tabular-nums">{{ $digits($fmt($row['seconds'])) }}</span></span>
                                            <span class="prod-dcell panel-border" style="justify-content:center;gap:10px">
                                                @if ($canWrite)
                                                    <button type="button" wire:click="openEdit({{ $row['id'] }})" class="mc-action prod-link focus:outline-none focus:ring-0" title="Editar actividad" aria-label="Editar actividad"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.9 4.6 2.5 2.5M4 20l4.2-1 10.9-10.9a1.8 1.8 0 0 0-2.5-2.5L5.7 16.5 4 20Z"/></svg></button>
                                                    <button type="button" wire:click="deleteRecord({{ $row['id'] }})" wire:confirm="¿Dar de baja esta actividad en ManagerControl?" class="mc-action panel-accent focus:outline-none focus:ring-0" title="Dar de baja" aria-label="Dar de baja la actividad"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18"/></svg></button>
                                                @else
                                                    <span class="mc-action" style="opacity:.35" title="Solo lectura: agregar, editar y dar de baja se activará cuando se den los permisos de escritura en ManagerControl." aria-hidden="true"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.9 4.6 2.5 2.5M4 20l4.2-1 10.9-10.9a1.8 1.8 0 0 0-2.5-2.5L5.7 16.5 4 20Z"/></svg></span>
                                                    <span class="mc-action" style="opacity:.35" title="Solo lectura: agregar, editar y dar de baja se activará cuando se den los permisos de escritura en ManagerControl." aria-hidden="true"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18"/></svg></span>
                                                @endif
                                            </span>
                                        </article>
                                    @endforeach
                                    @php $nextStripe = count($dayRows) % 2 === 1; @endphp
                                </section>
                            @endforeach
                            @for ($slot = $detailRows; $slot < 7; $slot++)
                                <div class="prod-blank-row prod-dgrid mc-grid panel-border border-b {{ $nextStripe ? 'mc-stripe' : '' }}" aria-hidden="true">@php $nextStripe = ! $nextStripe; @endphp<span class="prod-dcell prod-dindex panel-border"></span><span class="prod-dcell panel-border"></span><span class="prod-dcell panel-border"></span><span class="prod-dcell panel-border"></span><span class="prod-dcell panel-border"></span></div>
                            @endfor
                        </div>
                        <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                </div>
                </div>
            </section>
        </div>

        @unless ($ownMode)
        <div class="prod-wrap">
            <section id="prod-tour-links" x-data="{
                legacyEmployees: @js($employees),
                linkQ: '',
                linkIndex: @js(collect($linkRows)->map(fn ($row) => ['id' => $row['id'], 'text' => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($row['name'].' '.$row['email'].' '.$row['role']))])->values()),
                norm(text) { return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
                get visibleIds() { const tokens = this.norm(this.linkQ).split(/\s+/).filter(Boolean); return this.linkIndex.filter(row => tokens.every(token => row.text.includes(token))).map(row => row.id); },
                stripe(id) { return this.visibleIds.indexOf(id) % 2 === 1; },
                get fillers() { return Math.max(0, 7 - this.visibleIds.length); }
            }" class="prod-detail-card panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Asignar usuarios del CRM a colaboradores de ManagerControl">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Asignar usuario del CRM</h2>
                        <p class="prod-lines mt-[5px]">Relaciona cada usuario con su colaborador.</p>

                    </div>
                    <p class="shrink-0 text-right leading-none" role="status">
                        @if ($linkError)
                            <span class="panel-accent" data-testid="link-error">{{ $linkError }}</span>
                        @elseif ($linkNotice)
                            <span data-testid="link-notice">{{ $linkNotice }}</span>
                        @endif
                    </p>
                </header>
                <div class="prod-layout">
                    <aside class="prod-aside panel-scroll panel-border">
                        <div class="panel-surface panel-border box-border h-[87px] shrink-0 border-b p-[20px]">
                            <h2 class="panel-accent leading-none">Filtro de usuarios</h2>
                            <p class="prod-lines mt-[5px]">Busca y elige qué usuarios ver</p>
                        </div>
                        <div class="prod-form">
                            <div style="display: contents">
                                <span class="prod-label">Buscar</span>
                                <input id="mc-l-search" type="search" x-model="linkQ" placeholder="Buscar usuario…" aria-label="Buscar usuario del CRM" class="panel-field min-h-[50px] w-full rounded-xl border px-[20px] focus:outline-none focus:ring-0">
                            </div>
                            <div style="display: contents">
                                <span class="prod-label">Asignados</span>
                                <div id="mc-l-assigned" class="prod-date-trigger panel-field w-full rounded-xl border px-[20px] py-[12px]" aria-live="polite"><span class="whitespace-nowrap">{{ $linkCounts['assigned'] }} de {{ $linkCounts['all'] }}</span></div>
                            </div>
                            <div style="display: contents">
                                <span class="prod-label">No asignados</span>
                                <div id="mc-l-unassigned" class="prod-date-trigger panel-field w-full rounded-xl border px-[20px] py-[12px]" aria-live="polite"><span class="whitespace-nowrap">{{ $linkCounts['unassigned'] }} de {{ $linkCounts['all'] }}</span></div>
                            </div>
                            <div class="panel-border border-t" aria-hidden="true"></div>
                            <div class="prod-guide">
                                <a href="{{ route('section.guide', 'managercontrol-asignar') }}" title="Descargar la guía de Asignar usuario del CRM en PDF" class="prod-guide-link prod-link leading-none">Descargar guía PDF</a>
                                <div class="panel-border border-t" aria-hidden="true"></div>
                                <button type="button" @click="startQuickTour('links')" class="prod-primary min-h-[48px] w-full focus:outline-none focus:ring-0">Tutorial rápido</button>
                            </div>
                        </div>
                    </aside>

                <div class="prod-main">
                    <div class="prod-detail-cols prod-dgrid mc-link-grid panel-surface panel-border box-border h-[87px] shrink-0 border-b" aria-hidden="true">
                        <span class="prod-dcell prod-dindex panel-border">N°</span>
                        <span class="prod-dcell panel-border">Usuario del CRM</span>
                        <span class="prod-dcell panel-border">Correo</span>
                        <span class="prod-dcell panel-border">Rol</span>
                        <span class="prod-dcell panel-border">Colaborador de ManagerControl</span>
                    </div>
                    <div id="mc-l-list" data-scroll-on-hover x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="prod-detail-wrap">
                        <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const tops = [...$el.querySelectorAll('[data-snap]')].map((e) => e.offsetTop); if (! tops.length) return; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, tops.reduce((a, b) => Math.abs(b - $el.scrollTop) < Math.abs(a - $el.scrollTop) ? b : a)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" data-row-step="[data-snap]" class="prod-detail panel-scroll">
                            @foreach ($linkRows as $row)
                                <article data-snap wire:key="link-{{ $row['id'] }}" x-data="legacyEmployeePicker($wire.entangle('links.{{ $row['id'] }}'), legacyEmployees, (value) => value ? $wire.saveLink({{ $row['id'] }}) : $wire.removeLink({{ $row['id'] }}))" @keydown.escape.window="open = false" @click.window="if (open && !$el.contains($event.target) && !$event.target.closest('[data-mc-picker]')) open = false" x-show="visibleIds.includes({{ $row['id'] }})" :class="{ 'mc-stripe': stripe({{ $row['id'] }}) }" class="prod-drow prod-dgrid mc-link-grid panel-border border-b">
                                    <span class="prod-dcell prod-dindex prod-dcounter panel-border"></span>
                                    <span class="prod-dcell panel-border" style="flex-direction:column;align-items:flex-start;justify-content:center;gap:15px">
                                        <span class="max-w-full truncate leading-none" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                    </span>
                                    <span class="prod-dcell panel-border"><span class="truncate leading-none" title="{{ $row['email'] }}">{{ $row['email'] ?: '—' }}</span></span>
                                    <span class="prod-dcell panel-border"><span class="truncate leading-none" title="{{ $row['role'] }}">{{ $row['role'] ?: '—' }}</span></span>
                                    <span class="prod-dcell panel-border">
                                        <div x-ref="trigger" @click="if (! open) $refs.field.focus()" class="flex h-full w-full cursor-text items-center gap-[10px]">
                                            <input type="text" x-ref="field" autocomplete="off" role="combobox" aria-haspopup="listbox" :aria-expanded="open" aria-label="Colaborador de ManagerControl para {{ $row['name'] }}"
                                                :placeholder="label || 'Sin asignar'" :value="open ? q : label"
                                                @focus="openList()" @click="openList()" @input="q = $event.target.value; active = 0; open = true"
                                                @keydown.arrow-down.prevent="open = true; move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="pick()"
                                                class="panel-text min-w-0 flex-1 truncate leading-none" style="border:0 !important; box-shadow:none !important; outline:none !important; padding:0 !important; margin:0; background:transparent !important; min-height:0; height:auto; color:inherit; --tw-ring-shadow:0 0 #0000;">
                                            <button x-show="value" x-cloak type="button" tabindex="-1" @click.stop="choose(null)" aria-label="Quitar asignación" title="Quitar asignación" class="shrink-0 leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18" /></svg></button>
                                            <button type="button" tabindex="-1" @click="open ? open = false : $refs.field.focus()" aria-label="Mostrar opciones" class="shrink-0 leading-none focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                                            </button>
                                        </div>
                                        <template x-teleport="body">
                                            <div x-cloak x-ref="panel" data-panel data-productivity data-prod-calendar data-mc-picker :style="`left:${left}px;top:${top}px;width:${width}px`" :class="open ? '' : 'hidden'" class="app-zoom-overlay prod-calendar panel-surface panel-border panel-text fixed overflow-hidden rounded-xl border" style="padding:0" role="listbox" aria-label="Elegir colaborador de ManagerControl">
                                                <div x-data="backupOverlayScroll()" x-effect="open; q; $nextTick(() => update())" class="relative">
                                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" @scroll.debounce.150ms="(() => { const rows = [...$el.querySelectorAll('.panel-option')]; if (! rows.length) return; const height = rows[0].offsetHeight; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.round($el.scrollTop / height) * height); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" data-row-step=".panel-option" class="panel-scroll relative overflow-y-auto overscroll-contain" style="height: 400px;">
                                                        <template x-for="(employee, index) in filtered" :key="employee.id">
                                                            <button type="button" @click="choose(employee)" @mouseenter="active = index" :data-active="active === index" :class="{ 'is-active': active === index }" :aria-selected="String(value) === String(employee.id)" role="option" class="panel-option flex h-[80px] w-full items-center gap-[15px] px-[20px] text-left leading-none focus:outline-none focus:ring-0">
                                                                <span class="min-w-0 flex-1">
                                                                    <span class="block truncate" x-text="employee.name"></span>
                                                                    <span class="mt-[15px] block truncate" x-html="idHtml(employee.id)"></span>
                                                                </span>
                                                            </button>
                                                        </template>
                                                        <p x-show="filtered.length === 0" class="p-[20px] leading-none">Sin resultados</p>
                                                    </div>
                                                    <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </span>
                                </article>
                            @endforeach
                            <template x-for="n in fillers" :key="'blank-' + n">
                                <div class="prod-blank-row prod-dgrid mc-link-grid panel-border border-b" :class="{ 'mc-stripe': (visibleIds.length + n - 1) % 2 === 1 }" aria-hidden="true"><span class="prod-dcell prod-dindex panel-border"></span><span class="prod-dcell panel-border"></span><span class="prod-dcell panel-border"></span><span class="prod-dcell panel-border"></span><span class="prod-dcell panel-border"></span></div>
                            </template>
                        </div>
                        <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                </div>
                </div>
            </section>
        </div>
        @endunless
    </main>

    @if ($formOpen)
        <div class="fixed inset-0 z-[150000] flex items-center justify-center p-[20px]" style="background: rgba(10, 18, 35, .45)" role="dialog" aria-modal="true" aria-labelledby="mc-form-title" wire:keydown.escape="closeForm">
            <section class="panel-surface panel-border w-full max-w-[560px] overflow-hidden rounded-xl border" style="max-height: calc(100dvh - 40px); overflow-y: auto">
                <header class="panel-header panel-border border-b p-[20px]">
                    <h2 id="mc-form-title" class="panel-accent leading-none">{{ $editingId ? 'Editar actividad' : 'Agregar actividad' }}</h2>
                    <p class="prod-lines mt-[5px]">Se guarda en ManagerControl con el precio por hora del colaborador.</p>
                </header>
                <div class="grid gap-[20px] p-[20px]">
                    <div x-data="productivityDatePicker($wire.entangle('formDate'))" @keydown.escape.window="open = false" @click.window="if (open && !$el.contains($event.target) && !$event.target.closest('[data-prod-calendar]')) open = false">
                        <span class="prod-label" id="mc-form-date-label">Fecha</span>
                        <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open" aria-labelledby="mc-form-date-label" class="prod-date-trigger panel-field w-full rounded-xl border px-[20px] py-[12px] focus:outline-none focus:ring-0">
                            <span x-html="formattedHtml" class="whitespace-nowrap"></span>
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></svg>
                        </button>
                        <template x-teleport="body">
                            <div x-cloak data-panel data-productivity data-prod-calendar :style="`left:${left}px;top:${top}px;z-index:200500`" :class="open ? '' : 'hidden'" class="app-zoom-overlay prod-calendar panel-surface panel-border panel-text panel-scroll fixed rounded-xl border p-[20px]" role="dialog" aria-label="Seleccionar fecha">
                                <div class="mb-[15px] flex items-center justify-between gap-[10px]">
                                    <strong class="panel-accent font-medium" x-text="monthLabel"></strong>
                                    <div class="flex gap-[5px]">
                                        <button type="button" @click="changeMonth(-1)" aria-label="Mes anterior" class="panel-border hover:bg-[color:var(--panel-hover)] h-9 w-9 rounded-lg border">‹</button>
                                        <button type="button" @click="changeMonth(1)" aria-label="Mes siguiente" class="panel-border hover:bg-[color:var(--panel-hover)] h-9 w-9 rounded-lg border">›</button>
                                    </div>
                                </div>
                                <div class="mb-[5px] grid grid-cols-7 text-center text-xs panel-muted"><span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span></div>
                                <div class="grid grid-cols-7 gap-[2px]">
                                    <template x-for="day in days" :key="day.key">
                                        <button type="button" @click="selectDate(day.date)" class="prod-calendar-day text-sm" :class="{ 'is-outside': day.outside, 'is-today': isToday(day.date), 'is-selected': value === day.key }" :aria-label="day.date.toLocaleDateString('es-MX', { dateStyle: 'full' })" x-text="day.date.getDate()"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div>
                        <span class="prod-label">Cliente</span>
                        <x-panel-select id="mc-form-customer" label="Cliente" property="formCustomer" searchable placeholder="Buscar cliente…"
                            :options="collect($formCustomers)->map(fn ($item) => ['value' => (string) $item['id'], 'label' => $item['name']])->all()" />
                    </div>
                    <div>
                        <span class="prod-label">Actividad</span>
                        <x-panel-select id="mc-form-activity" label="Actividad" property="formActivity" searchable placeholder="Buscar actividad…"
                            :options="collect($formActivities)->map(fn ($item) => ['value' => (string) $item['id'], 'label' => $item['name']])->all()" />
                    </div>
                    <div>
                        <label class="prod-label" for="mc-form-hours">Horas (en décimas: 0.5 = 30 minutos)</label>
                        <input id="mc-form-hours" type="text" inputmode="decimal" wire:model="formHours" placeholder="0.0" class="panel-field min-h-[50px] w-full rounded-xl border px-[20px] focus:outline-none focus:ring-0">
                    </div>
                    <div>
                        <label class="prod-label" for="mc-form-description">Descripción</label>
                        <textarea id="mc-form-description" wire:model="formDescription" rows="3" maxlength="2000" class="panel-field w-full rounded-xl border px-[20px] py-[15px] focus:outline-none focus:ring-0"></textarea>
                    </div>
                    @if ($formError)<p class="panel-accent prod-lines" role="alert" data-testid="form-error">{{ $formError }}</p>@endif
                </div>
                <footer class="panel-border flex items-center justify-between gap-[20px] border-t p-[20px]">
                    <button type="button" wire:click="closeForm" class="prod-link cursor-pointer leading-none focus:outline-none focus:ring-0">Cancelar</button>
                    <button type="button" wire:click="saveRecord" wire:loading.attr="disabled" wire:target="saveRecord" class="prod-primary focus:outline-none focus:ring-0">{{ $editingId ? 'Guardar cambios' : 'Agregar' }}</button>
                </footer>
            </section>
        </div>
    @endif

    <template x-teleport="body">
        <div data-panel data-productivity x-cloak x-show="quickTourStep > 0" x-transition.opacity class="pointer-events-none fixed inset-0 z-[299999]" style="background:transparent" @click.stop @wheel.prevent @touchmove.prevent>
            <div :style="maskTopStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskLeftStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskRightStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskBottomStyle" class="z-[300000]" aria-hidden="true"></div>

            <div :style="spotlightStyle" :class="quickTourSteps[quickTourStep - 1]?.target === 'prod-tour-period' ? 'pointer-events-none' : 'pointer-events-auto'" class="z-[300001]" aria-hidden="true"></div>

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
