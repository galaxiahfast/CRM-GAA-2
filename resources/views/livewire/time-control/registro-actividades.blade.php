@php
    $moduleTimezone = (string) config('app.timezone', 'America/Mexico_City');
    $moduleTimezone = $moduleTimezone === 'UTC' ? 'America/Mexico_City' : $moduleTimezone;

    $fmt = function (int $s) {
        $s = max(0, $s);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    };

    $secondsFor = function ($entry) {
        return (int) $entry->intervals->reduce(function (int $carry, $interval) {
            if (! $interval->ended_at) {
                return $carry;
            }

            return $carry + max(0, $interval->ended_at->diffInSeconds($interval->started_at, true));
        }, 0);
    };

    $timestampFor = function ($date) use ($moduleTimezone) {
        if (! $date) {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($date->format('Y-m-d H:i:s'), $moduleTimezone)->timestamp;
    };

    $openStartFor = function ($entry) use ($timestampFor) {
        if ($entry->status !== \App\Models\TimeEntry::STATUS_IN_PROGRESS) {
            return '';
        }

        return $timestampFor($entry->intervals->firstWhere('ended_at', null)?->started_at);
    };

    $todayLiveBaseSeconds = (int) $todayEntries->sum(fn ($entry) => $secondsFor($entry));
    $todayOpenStart = $active ? $openStartFor($active) : '';
@endphp

{{-- Cronómetro: mismo diseño que Administración y Reloj Checador (variables de panel, tema claro/oscuro, 15px,
     separaciones de 20px y 15px). Solo cambia la presentación; la lógica vive en App\Livewire\TimeControl\RegistroActividades. --}}
<div
    data-panel
    data-time-clock
    x-data="clockQuickTour"
    class="relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 text-[15px] xl:flex xl:flex-col"
>
    <x-panel-styles />

    <style>
        /* Mismo diseño que Inicio y Panel general: texto negro (blanco en oscuro) y azul; enlaces y botones en verde. */
        [data-time-clock] { --panel-muted: var(--panel-text); --clock-green: #15803d; }
        html.module-dark-theme [data-time-clock] { --clock-green: #4ade80; }
        [data-time-clock] .clock-row:hover { background-color: var(--panel-hover); }
        [data-time-clock] .clock-link { color: var(--clock-green); transition: opacity 150ms ease; }
        [data-time-clock] .clock-link:hover { opacity: .75; }
        [data-time-clock] .clock-primary {
            border: 1px solid var(--clock-green);
            background-color: color-mix(in srgb, var(--clock-green) 12%, var(--panel-surface));
            color: var(--clock-green);
            transition: background-color 150ms ease;
        }
        [data-time-clock] .clock-primary:hover { background-color: color-mix(in srgb, var(--clock-green) 20%, var(--panel-surface)); }
        [data-time-clock] .clock-primary:disabled { cursor: not-allowed; opacity: .6; }
        [data-time-clock] .clock-pill { white-space: nowrap; }
        [data-time-clock] .clock-button {
            display: inline-flex;
            width: 120px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 12px;
            padding: 15px 20px;
            cursor: pointer;
        }
        [data-time-clock] .clock-button:disabled { cursor: not-allowed; opacity: .6; }
        [data-time-clock] .clock-danger {
            border: 1px solid var(--panel-danger-line);
            background-color: var(--panel-danger-soft);
            color: var(--panel-danger);
        }
        [data-time-clock] .clock-danger:hover { opacity: .8; }
        /* Listas de cliente y actividad: siempre 5 opciones exactas (5 × 55px) y al dejar de desplazar el scroll se alinea a la opción más cercana */
        [data-time-clock] .clock-options { max-height: 275px; }
        [data-time-clock] .clock-option {
            display: block;
            box-sizing: border-box;
            width: 100%;
            height: 55px;
            margin: 0;
            padding: 20px;
            overflow: hidden;
            text-align: left;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 15px;
        }
        [data-time-clock] .clock-table .clock-head-row th { height: 87px; }
        /* La regla global de tablas en tema oscuro las pinta de gris: aquí la tabla usa el mismo negro que el resto */
        html.module-dark-theme #main-content [data-time-clock] table tbody,
        html.module-dark-theme #main-content [data-time-clock] table tr {
            background: var(--panel-surface) !important;
        }
        /* Filas intercaladas: la primera con el color del encabezado y la siguiente con el fondo normal */
        [data-time-clock] .clock-body tbody tr:nth-child(odd) { background-color: var(--panel-header); }
        [data-time-clock] .clock-body tbody tr.clock-row:hover { background-color: var(--panel-hover); }
        /* La actividad en progreso se marca con la fila en verde */
        [data-time-clock] .clock-body tbody tr.clock-row.is-running { background-color: color-mix(in srgb, var(--clock-green) 12%, var(--panel-surface)); }
        html.module-dark-theme #main-content [data-time-clock] table tbody tr.clock-row.is-running { background: color-mix(in srgb, var(--clock-green) 12%, var(--panel-surface)) !important; }
        html.module-dark-theme #main-content [data-time-clock] table tbody tr:nth-child(odd) { background: var(--panel-header) !important; }
        html.module-dark-theme #main-content [data-time-clock] table tr.clock-row:hover { background: var(--panel-hover) !important; }
        /* Iconos de acción sin recuadro: mover y eliminar */
        [data-time-clock] .clock-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border: 0;
            background: transparent;
            color: var(--panel-text);
            cursor: pointer;
            transition: color 150ms ease, opacity 150ms ease;
        }
        [data-time-clock] .clock-icon:hover { color: var(--panel-accent); }
        [data-time-clock] .clock-icon-go { color: var(--clock-green); }
        [data-time-clock] .clock-icon-go:hover { color: var(--clock-green); opacity: .7; }
        [data-time-clock] .clock-icon-danger { color: var(--panel-danger); }
        [data-time-clock] .clock-icon-danger:hover { color: var(--panel-danger); opacity: .7; }
        [data-time-clock] .clock-scroll { scrollbar-width: thin; scrollbar-color: var(--panel-accent) transparent; overscroll-behavior: contain; }
        [data-time-clock] .clock-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
        [data-time-clock] .clock-scroll::-webkit-scrollbar-track { background: transparent; }
        [data-time-clock] .clock-scroll::-webkit-scrollbar-thumb { background: var(--panel-accent); border-radius: 9999px; }

        /* Índice de filas: lo numera el navegador, así se actualiza solo al reordenar */
        [data-time-clock] .clock-rows { counter-reset: clock-row; }
        [data-time-clock] .clock-rows > tr { counter-increment: clock-row; }
        /* Las filas se reordenan arrastrándolas con el clic apretado (los iconos conservan su clic normal) */
        [data-time-clock] .clock-rows > tr { cursor: grab; }
        [data-time-clock] .clock-rows > tr .clock-icon { cursor: pointer; }
        [data-time-clock] .clock-index::after { content: counter(clock-row); }

        /* Líneas verticales que dividen las columnas de la tabla (en celular y tablet cada fila es una tarjeta) */
        [data-time-clock] .clock-table :is(th, td) { font-weight: 400; }
        @media (min-width: 1440px) {
            [data-time-clock] .clock-table :is(th, td):not(:last-child) { border-right: 1px solid var(--panel-border); }
            [data-time-clock] .clock-table :is(th, td):nth-child(1), [data-time-clock] .clock-table :is(th, td):nth-child(n+4) { text-align: center; }
            /* La zona de filas muestra siempre 7 filas exactas (la altura de cada fila es 1/7 de la zona); al dejar de desplazar, el scroll se alinea a la fila más cercana */
            [data-time-clock] .clock-body { container-type: size; }
            [data-time-clock] .clock-body tbody td { height: max(59px, calc(100cqh / 7)); box-sizing: border-box; padding-top: 14px; padding-bottom: 14px; }
            /* Departamento o cliente y actividad miden lo mismo: la tabla usa columnas fijas y se reparten el ancho sobrante */
            [data-time-clock] .clock-table { table-layout: fixed; }
            [data-time-clock] .clock-table :is(th, td):nth-child(2), [data-time-clock] .clock-table :is(th, td):nth-child(3) { width: auto; overflow: hidden; text-overflow: ellipsis; }
            [data-time-clock] .clock-table :is(th, td):nth-child(1) { width: 80px; min-width: 80px; max-width: 80px; }
            /* Tiempo, estado y acción miden lo mismo (lo que ocupa "En progreso") para que la tabla no se mueva al cambiar de estado */
            [data-time-clock] .clock-table :is(th, td):nth-child(n+4) { width: 160px; min-width: 160px; max-width: 160px; }
            [data-time-clock] .clock-table .clock-actions { justify-content: center; }
        }

        /* Celular y tablet: cada actividad se muestra como tarjeta, sin desplazamiento horizontal */
        @media (max-width: 1439px) {
            [data-time-clock] .clock-table.clock-table-head { display: none; }
            [data-time-clock] .clock-table,
            [data-time-clock] .clock-table tbody { display: block; }
            [data-time-clock] .clock-table tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 15px 20px;
                padding: 20px;
            }
            [data-time-clock] .clock-table td { display: block; max-width: none; padding: 0; }
            [data-time-clock] .clock-table td[data-label]::before {
                content: attr(data-label);
                font-family: Poppins, ui-sans-serif, system-ui, sans-serif;
                display: block;
                margin-bottom: 5px;
                color: var(--panel-muted);
            }
            [data-time-clock] .clock-table td:nth-child(1),
            [data-time-clock] .clock-table td:nth-child(2),
            [data-time-clock] .clock-table td:nth-child(3),
            [data-time-clock] .clock-table td:nth-child(6) { grid-column: 1 / -1; }
            [data-time-clock] .clock-table .clock-button { width: 100%; }
        }
    </style>

    <main class="relative z-10 flex w-full flex-col gap-[50px] xl:min-h-0 xl:flex-1 p-[20px] sm:p-[30px] xl:p-[50px]">
    <section class="flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
        <div class="flex min-w-0 max-w-full items-center gap-[15px]">
            <span class="panel-surface panel-border panel-text grid h-[50px] w-[50px] shrink-0 place-items-center rounded-xl border">
                <svg class="h-[30px] w-[30px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div class="min-w-0">
                <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">Cronómetro</h1>
                <p class="mt-[15px] leading-none">Registra y administra el tiempo dedicado a tus actividades.</p>
            </div>
        </div>
        <div class="flex w-full min-w-0 flex-wrap items-start gap-[20px] lg:w-auto">
            <a href="{{ route('section.guide', 'cronometro') }}" title="Descargar la guía del Cronómetro en PDF" class="clock-link inline-flex shrink-0 self-end items-center gap-[10px] leading-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5" /></svg>
                Guía PDF
            </a>
            <div class="min-w-0 lg:text-right">
                <p class="panel-accent leading-none">Organización</p>
                <p class="mt-[15px] flex items-center gap-[10px] leading-none lg:justify-end"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>Sincronizado en tiempo real</p>
            </div>
        </div>
    </section>

    @if ($errors->has('timer') || $errors->has('customerId') || $errors->has('subServiceId'))
        @teleport('body')
            <div
                data-panel
                wire:key="clock-error-toast-{{ $errorToastVersion }}"
                x-data="{ visible: true }"
                x-init="setTimeout(() => { visible = false; $wire.dismissErrorToast(); }, 5000)"
                x-show="visible"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-3"
                class="panel-surface fixed left-1/2 top-[143px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-start gap-[15px] rounded-xl border px-[20px] py-[15px] shadow-[0_14px_40px_rgba(0,0,0,0.20)]"
                style="border-color: var(--panel-danger-line); color: var(--panel-danger)"
                role="alert"
            >
                <svg class="mt-[5px] h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                <div class="min-w-0 flex-1">
                    @foreach (['timer', 'customerId', 'subServiceId'] as $errorKey)
                        @error($errorKey)
                            <p class="leading-[30px]">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
            </div>
        @endteleport
    @endif

    <section
        class="panel-surface panel-border overflow-hidden rounded-xl border xl:flex xl:min-h-0 xl:flex-1 xl:flex-col"
        aria-label="Registro de actividades"
        x-data="{
            totalDisplay: '00:00:00',
            totalTimerId: null,
            formatSeconds(seconds) {
                seconds = Math.max(0, parseInt(seconds || 0, 10));
                let h = Math.floor(seconds / 3600);
                let m = Math.floor((seconds % 3600) / 60);
                let s = seconds % 60;

                return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            },
            currentEpochSecond() {
                return Math.floor(Date.now() / 1000);
            },
            elapsedFrom(openStart) {
                if (! openStart) {
                    return 0;
                }

                return Math.max(0, this.currentEpochSecond() - parseInt(openStart, 10));
            },
            tickTotal() {
                if (! this.$el.isConnected) {
                    clearInterval(this.totalTimerId);
                    return;
                }

                let total = parseInt(this.$el.dataset.totalAccumulated || '0', 10);
                total += this.elapsedFrom(this.$el.dataset.totalOpenStart);
                this.totalDisplay = this.formatSeconds(total);
            }
        }"
        x-init="tickTotal(); totalTimerId = setInterval(() => tickTotal(), 1000)"
        data-total-accumulated="{{ $todayLiveBaseSeconds }}"
        data-total-open-start="{{ $todayOpenStart }}"

    >
        <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
            <div class="min-w-0">
                <h2 class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Registro de actividades</span></h2>
                <p class="mt-[15px] leading-none">Selecciona dónde trabajarás y consulta el tiempo registrado hoy por cliente y actividad.</p>
            </div>
            <span class="leading-none">{{ $todayEntries->count() }} {{ $todayEntries->count() === 1 ? 'actividad' : 'actividades' }} hoy</span>
        </header>
        <div class="grid min-h-[460px] xl:min-h-0 xl:flex-1 xl:grid-rows-[minmax(0,1fr)] xl:grid-cols-[320px_minmax(0,1fr)]">
    <aside class="clock-scroll panel-border border-b xl:min-h-0 xl:overflow-y-auto xl:border-b-0 xl:border-r">
        <div class="panel-surface panel-border box-border h-[87px] border-b p-[20px]">
            <h2 class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" /></svg><span>Nuevo registro</span></h2>
            <p class="mt-[15px] leading-none">Selecciona dónde trabajarás.</p>
        </div>
        <form wire:submit="start" class="p-[20px]">
            <div class="grid gap-[20px]">
                <div
                    class="relative"
                    x-data="{
                        open: false,
                        search: '',
                        customers: {{ json_encode($customers) }},
                        selectedId: @entangle('customerId'),
                        selectedName: '',
                        init() {
                            if (this.selectedId) {
                                let match = this.customers.find(c => c.id == this.selectedId);
                                if (match) this.selectedName = match.search_name.toUpperCase();
                            }
                            $watch('selectedId', value => {
                                if (! value) { this.selectedName = ''; this.search = ''; }
                            });
                        },
                        select(id, name) {
                            this.selectedId = id;
                            this.selectedName = name;
                            this.search = '';
                            this.open = false;
                        },
                        get filteredCustomers() {
                            if (this.search === '') return this.customers;
                            return this.customers.filter(c => c.search_name.includes(this.search.toLowerCase()));
                        }
                    }"
                    @open-dropdown.window="if ($event.detail.id !== 'customer') open = false"
                    @click.away="open = false; search = ''"
                >
                    <label class="mb-[20px] flex min-h-6 items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg><span>Cliente</span></label>

                    <div class="relative">
                        <input
                            id="clock-tour-client"
                            type="text"
                            x-model="search"
                            @focus="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                            @click="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                            :placeholder="selectedName || '-- Escribe o busca un cliente --'"
                            class="panel-field w-full truncate rounded-xl border py-[15px] pl-[20px] pr-[56px] focus:outline-none focus:ring-0"
                            @keydown.escape="open = false"
                        >

                        <button type="button" @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'customer' }) }" class="absolute inset-y-0 right-[20px] flex cursor-pointer items-center" aria-label="Mostrar clientes">
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="panel-surface panel-border absolute left-0 top-full z-40 mt-[10px] w-full overflow-hidden rounded-xl border" role="listbox" aria-label="Cliente">
                        <div x-data="backupOverlayScroll()" x-effect="open; search; $nextTick(() => update())" class="relative">
                            <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.max(0, Math.round($el.scrollTop / 55) * 55)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" class="panel-scroll clock-options relative overflow-y-auto overscroll-contain">
                                <template x-for="c in filteredCustomers" :key="c.id">
                                    <button type="button" role="option" @click="select(c.id, c.search_name.toUpperCase())" :aria-selected="selectedId == c.id ? 'true' : 'false'" class="panel-option clock-option focus:outline-none focus:ring-0" x-text="c.search_name.toUpperCase()"></button>
                                </template>
                                <p x-show="filteredCustomers.length === 0" x-cloak class="clock-option">Sin resultados.</p>
                            </div>
                            <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>
                    </div>

                </div>

                <div
                    class="relative"
                    x-data="{
                        open: false,
                        search: '',
                        services: {{ json_encode($subServices) }},
                        selectedId: @entangle('subServiceId'),
                        selectedName: '',
                        init() {
                            if (this.selectedId) {
                                let match = this.services.find(s => s.id == this.selectedId);
                                if (match) this.selectedName = match.search_name.toUpperCase();
                            }
                            $watch('selectedId', value => {
                                if (! value) { this.selectedName = ''; this.search = ''; }
                            });
                        },
                        select(id, name) {
                            this.selectedId = id;
                            this.selectedName = name;
                            this.search = '';
                            this.open = false;
                        },
                        get filteredServices() {
                            if (this.search === '') return this.services;
                            return this.services.filter(s => s.search_name.includes(this.search.toLowerCase()));
                        }
                    }"
                    @open-dropdown.window="if ($event.detail.id !== 'service') open = false"
                    @click.away="open = false; search = ''"
                >
                    <label class="mb-[20px] flex min-h-6 items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg><span>Actividad</span></label>

                    <div class="relative">
                        <input
                            id="clock-tour-activity"
                            type="text"
                            x-model="search"
                            @focus="open = true; $dispatch('open-dropdown', { id: 'service' })"
                            @click="open = true; $dispatch('open-dropdown', { id: 'service' })"
                            :placeholder="selectedName || '-- Escribe o busca una actividad --'"
                            class="panel-field w-full truncate rounded-xl border py-[15px] pl-[20px] pr-[56px] focus:outline-none focus:ring-0"
                            @keydown.escape="open = false"
                        >

                        <button type="button" @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'service' }) }" class="absolute inset-y-0 right-[20px] flex cursor-pointer items-center" aria-label="Mostrar actividades">
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="panel-surface panel-border absolute left-0 top-full z-40 mt-[10px] w-full overflow-hidden rounded-xl border" role="listbox" aria-label="Actividad">
                        <div x-data="backupOverlayScroll()" x-effect="open; search; $nextTick(() => update())" class="relative">
                            <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.max(0, Math.round($el.scrollTop / 55) * 55)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" class="panel-scroll clock-options relative overflow-y-auto overscroll-contain">
                                <template x-for="s in filteredServices" :key="s.id">
                                    <button type="button" role="option" @click="select(s.id, s.search_name.toUpperCase())" :aria-selected="selectedId == s.id ? 'true' : 'false'" class="panel-option clock-option focus:outline-none focus:ring-0" x-text="s.search_name.toUpperCase()"></button>
                                </template>
                                <p x-show="filteredServices.length === 0" x-cloak class="clock-option">Sin resultados.</p>
                            </div>
                            <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>
                    </div>

                </div>

                <div>
                    <div class="mb-[20px] flex min-h-6 items-center gap-[10px] leading-none">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14l11-7L8 5z" /></svg>
                        <span>Acción</span>
                    </div>
                    <button id="clock-tour-start" type="submit" wire:loading.attr="disabled" wire:target="start" class="clock-primary inline-flex w-full cursor-pointer items-center justify-center rounded-xl px-[20px] py-[15px] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60">
                    <span class="flex w-full flex-row items-center justify-center gap-[10px]">
                        <svg wire:loading.remove wire:target="start" class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8 5.14v13.72L19 12 8 5.14z"/>
                        </svg>

                        <svg wire:loading wire:target="start" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="start">Iniciar actividad</span>
                        <span wire:loading wire:target="start" style="display: none;">Sincronizando...</span>
                    </span>
                    </button>
                </div>
            </div>
        </form>
        <div class="px-[20px] pb-[20px]">
            <button type="button" @click="startQuickTour()" class="clock-primary inline-flex min-h-[48px] w-full cursor-pointer items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] focus:outline-none focus:ring-0">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 9a2.5 2.5 0 014.8.9c0 1.7-2.3 2.1-2.3 3.6m0 2.5h.01"/></svg>
                Uso rápido
            </button>
        </div>
    </aside>

    <div
        class="flex min-h-0 min-w-0 flex-col"
    >
        @if ($todayEntries->isEmpty())
            <div class="flex min-h-[350px] flex-col items-center justify-center px-[20px] text-center">
                <span class="panel-border mb-[15px] flex h-12 w-12 items-center justify-center rounded-full border">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <p class="leading-[30px]">Tu jornada aún no tiene registros</p>
                <p class="leading-[30px]">Selecciona un cliente y una actividad para comenzar.</p>
            </div>
        @else
            {{-- Encabezado fijo: el scroll solo recorre las filas --}}
            <table class="clock-table clock-table-head w-full border-collapse text-left">
                <thead class="panel-surface panel-border border-b">
                    <tr class="clock-head-row">
                        <th class="whitespace-nowrap p-[20px] align-middle">N°</th>
                        <th class="p-[20px] align-middle">Departamento o cliente</th>
                        <th class="p-[20px] align-middle">Actividad</th>
                        <th class="whitespace-nowrap p-[20px] align-middle">Tiempo</th>
                        <th class="whitespace-nowrap p-[20px] align-middle">Estado</th>
                        <th class="whitespace-nowrap p-[20px] align-middle">Acción</th>
                    </tr>
                </thead>
            </table>
            <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="relative xl:min-h-[424px] xl:flex-1">
            <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const rows = $el.querySelectorAll('tbody tr'); if (! rows.length) return; const height = $el.scrollHeight / rows.length; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.max(0, Math.round($el.scrollTop / height) * height)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" class="panel-scroll clock-body relative max-h-[600px] overflow-auto overscroll-contain xl:absolute xl:inset-0 xl:max-h-none">
                <table class="clock-table w-full border-collapse text-left">
                    <tbody data-activity-sortable class="clock-rows">
                        @foreach ($todayEntries as $entry)
                            <tr
                                wire:key="time-entry-{{ $entry->id }}"
                                data-activity-id="{{ $entry->id }}"
                                class="clock-row panel-border border-b transition-[opacity,background-color] duration-150 {{ $entry->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS ? 'is-running' : '' }}"
                                x-data="{
                                    display: '00:00:00',
                                    timerId: null,
                                    formatSeconds(seconds) {
                                        seconds = Math.max(0, parseInt(seconds || 0, 10));
                                        let h = Math.floor(seconds / 3600);
                                        let m = Math.floor((seconds % 3600) / 60);
                                        let s = seconds % 60;

                                        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                    },
                                    currentEpochSecond() {
                                        return Math.floor(Date.now() / 1000);
                                    },
                                    elapsedFrom(openStart) {
                                        if (! openStart) {
                                            return 0;
                                        }

                                        return Math.max(0, this.currentEpochSecond() - parseInt(openStart, 10));
                                    },
                                    tick() {
                                        if (! this.$el.isConnected) {
                                            clearInterval(this.timerId);
                                            return;
                                        }

                                        let total = parseInt(this.$el.dataset.accumulated || '0', 10);
                                        total += this.elapsedFrom(this.$el.dataset.openStart);
                                        this.display = this.formatSeconds(total);
                                    }
                                }"
                                x-init="tick(); timerId = setInterval(() => tick(), 1000)"
                                data-accumulated="{{ $secondsFor($entry) }}"
                                data-open-start="{{ $openStartFor($entry) }}"
                            >
                                <td data-label="N°" class="clock-index whitespace-nowrap p-[20px] align-middle tabular-nums"></td>

                                <td data-label="Departamento o cliente" class="p-[20px] align-middle">
                                    <div class="truncate leading-none" title="{{ $entry->customer->name ?? '--' }}">{{ $entry->customer->name ?? '--' }}</div>
                                </td>

                                <td data-label="Actividad" class="p-[20px] align-middle">
                                    <div class="truncate leading-none" title="{{ $entry->subService->sub_service ?? '--' }}">{{ $entry->subService->sub_service ?? '--' }}</div>
                                </td>

                                <td data-label="Tiempo" class="whitespace-nowrap p-[20px] align-middle font-mono tabular-nums" x-text="display">
                                    {{ $fmt($entry->calculateEffectiveSeconds()) }}
                                </td>

                                <td data-label="Estado" class="whitespace-nowrap p-[20px] align-middle">
                                    <span class="clock-pill {{ $entry->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS ? 'panel-tone-green' : (in_array($entry->status, [\App\Models\TimeEntry::STATUS_PAUSED, \App\Models\TimeEntry::STATUS_FINISHED], true) ? 'panel-tone-red' : 'panel-tone-gray') }}">
                                        {{ in_array($entry->status, [\App\Models\TimeEntry::STATUS_PAUSED, \App\Models\TimeEntry::STATUS_FINISHED], true) ? 'Pausada' : ($entry->status === \App\Models\TimeEntry::STATUS_AUTO_CLOSED ? 'Cerrada' : $entry->status_label) }}
                                    </span>
                                </td>

                                <td data-label="Acción" class="whitespace-nowrap p-[20px] align-middle">
                                    <div class="clock-actions flex items-center justify-start gap-[10px]">
                                        @if ($entry->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS)
                                            <button type="button" wire:click="pause({{ $entry->id }})" wire:loading.attr="disabled" class="clock-icon clock-icon-go disabled:cursor-wait disabled:opacity-50" aria-label="Pausar {{ $entry->subService->sub_service ?? 'actividad' }}" title="Pausar">
                                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M7 5h4v14H7V5zm6 0h4v14h-4V5z"/>
                                                </svg>
                                            </button>
                                        @elseif ($entry->status !== \App\Models\TimeEntry::STATUS_AUTO_CLOSED)
                                            <button type="button" wire:click="resume({{ $entry->id }})" wire:loading.attr="disabled" class="clock-icon clock-icon-go disabled:cursor-wait disabled:opacity-50" aria-label="Reanudar {{ $entry->subService->sub_service ?? 'actividad' }}" title="Reanudar">
                                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M8 5.14v13.72L19 12 8 5.14z"/>
                                                </svg>
                                            </button>
                                        @else
                                            <button type="button" disabled class="clock-icon cursor-not-allowed opacity-50" aria-label="Cerrada automáticamente" title="Cerrada automáticamente">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            wire:click="requestDeletion({{ $entry->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="requestDeletion({{ $entry->id }})"
                                            class="clock-icon clock-icon-danger disabled:cursor-wait disabled:opacity-50"
                                            aria-label="Eliminar {{ $entry->subService->sub_service ?? 'actividad' }}"
                                            title="Eliminar actividad"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>

        @endif
    </div>
        </div>
    </section>
    </main>

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-entry-title">
            <div class="panel-surface panel-border w-full max-w-[520px] rounded-xl border shadow-[0_24px_80px_rgba(0,0,0,0.30)]">
                <div class="panel-surface panel-border flex items-start justify-between gap-[20px] rounded-t-xl border-b px-[20px] py-[15px]">
                    <div>
                        <h2 id="delete-entry-title" class="panel-accent text-xl font-normal leading-[30px]">Eliminar actividad</h2>
                        <p class="leading-[30px]">Esta acción eliminará el registro y todo el tiempo asociado.</p>
                    </div>
                    <button type="button" wire:click="cancelDeletion" class="panel-icon-button inline-flex shrink-0 items-center justify-center rounded-lg p-[10px] focus:outline-none focus:ring-0" aria-label="Cerrar confirmación">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>

                <div class="p-[20px]">
                    <p class="leading-[30px]">Para confirmar, escribe exactamente:</p>
                    <p class="panel-border mt-[15px] rounded-xl border px-[20px] py-[15px] leading-[30px]">{{ $deleteActivityName }}</p>

                    <label for="delete-activity-confirmation" class="mb-[15px] mt-[20px] block leading-[30px]">Nombre de la actividad</label>
                    <input
                        id="delete-activity-confirmation"
                        type="text"
                        wire:model="deleteConfirmation"
                        wire:keydown.enter="deleteEntry"
                        autocomplete="off"
                        class="panel-field w-full rounded-xl border px-[20px] py-[15px] focus:outline-none focus:ring-0"
                        placeholder="Escribe el nombre completo"
                        autofocus
                    >
                    @error('deleteConfirmation')
                        <p class="panel-tone-red mt-[15px]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="panel-border flex items-center justify-end gap-[20px] border-t p-[20px]">
                    <button type="button" wire:click="cancelDeletion" class="panel-button-secondary rounded-xl px-[20px] py-[15px] focus:outline-none focus:ring-0">Cancelar</button>
                    <button type="button" wire:click="deleteEntry" wire:loading.attr="disabled" wire:target="deleteEntry" class="clock-danger rounded-xl px-[20px] py-[15px] transition-opacity focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-50">
                        <span wire:loading.remove wire:target="deleteEntry">Eliminar actividad</span>
                        <span wire:loading wire:target="deleteEntry">Eliminando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <template x-teleport="body">
        <div data-panel data-time-clock x-cloak x-show="quickTourStep > 0" x-transition.opacity class="pointer-events-none fixed inset-0 z-[299999]" style="background:transparent" @click.stop @wheel.prevent @touchmove.prevent>
            <div :style="maskTopStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskLeftStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskRightStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskBottomStyle" class="z-[300000]" aria-hidden="true"></div>

            <div :style="spotlightStyle" class="pointer-events-auto z-[300001] rounded-xl border-2 border-emerald-500 shadow-[0_0_0_3px_rgba(74,222,128,0.28)]" aria-hidden="true">
                <span class="absolute -left-[13px] -top-[13px] grid h-7 w-7 place-items-center rounded-full bg-emerald-500 text-[13px] font-normal text-white shadow" x-text="quickTourStep"></span>
            </div>

            <section :style="popoverStyle" class="panel-surface panel-border pointer-events-auto fixed left-1/2 top-1/2 z-[300002] w-[min(440px,calc(100vw-40px))] -translate-x-1/2 -translate-y-1/2 rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)]" role="dialog" aria-modal="true" aria-labelledby="clock-tour-title">
                <p class="panel-accent leading-[30px]" x-text="quickTourStep ? 'Paso ' + quickTourStep + ' de ' + quickTourSteps.length : ''"></p>
                <h2 id="clock-tour-title" class="mt-[15px] leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].title : ''"></h2>
                <p class="mt-[15px] text-justify leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].description : ''"></p>
                <div class="mt-[20px] flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px]">
                    <button type="button" @click="closeQuickTour()" class="clock-link whitespace-nowrap leading-none">Saltar recorrido</button>
                    <div class="flex items-center gap-[20px] whitespace-nowrap">
                        <button x-show="quickTourStep > 1" type="button" @click="previousQuickTourStep()" class="panel-button-secondary rounded-xl px-[20px] py-[15px] leading-none">Anterior</button>
                        <button x-ref="quickTourNext" type="button" @click="nextQuickTourStep()" class="clock-primary rounded-xl px-[20px] py-[15px] leading-none" x-text="quickTourStep === quickTourSteps.length ? 'Terminar' : 'Siguiente'"></button>
                    </div>
                </div>
            </section>
        </div>
    </template>
</div>
