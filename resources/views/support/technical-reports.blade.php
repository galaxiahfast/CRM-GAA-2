<x-app-layout>
    {{-- Reportes de soporte: mismo diseño que Gestión de Respaldos, Panel general y Administración
         (tarjetas con cabecera gris, líneas de 1px, texto de 15px, separaciones de 20px, escala de vista al 90%
         ajustable, pantalla completa y tema oscuro con fondo negro). Solo cambia la presentación; la lógica vive en
         App\Http\Controllers\TechnicalSupportReportController. --}}
    <x-panel-styles />

    <style>
        [data-panel] { --support-hover: var(--panel-subtle); }
        html.module-dark-theme [data-panel] { --support-hover: var(--panel-hover); }

        /* Respuesta al pasar el cursor, al seleccionar y al deshabilitar */
        .support-report-page :is(button:not(:disabled), label, summary, [role="tab"]),
        [data-support-modal] :is(button:not(:disabled), label),
        [data-support-dropdown] :is(button:not(:disabled), label) { cursor: pointer; }
        .support-report-page :is(button, input):disabled,
        [data-support-modal] :is(button, input):disabled,
        [data-support-dropdown] :is(button, input):disabled { cursor: not-allowed; }
        .support-report-page :is(.panel-button, .panel-button-secondary):disabled,
        [data-support-modal] :is(.panel-button, .panel-button-secondary):disabled { opacity: .4; }

        /* Botón principal: en tema oscuro usa el azul de marca en lugar de blanco */
        html.module-dark-theme .support-report-page .panel-button,
        html.module-dark-theme [data-support-modal] .panel-button { background-color: #1A3A6B; color: #ffffff; }
        html.module-dark-theme .support-report-page .panel-button:hover:not(:disabled),
        html.module-dark-theme [data-support-modal] .panel-button:hover:not(:disabled) { background-color: #234b88; }

        /* Acción principal del reporte: el verde de Gestión de Respaldos. */
        .support-report-page .support-report-primary { background-color: #15803d; color: #ffffff; transition: background-color 150ms ease; }
        .support-report-page .support-report-primary:hover:not(:disabled) { background-color: #166534; }
        .support-report-page .support-report-primary:disabled { opacity: .4; }
        .support-report-page .support-report-header-action > button { color: #15803d; }
        html.module-dark-theme .support-report-page .support-report-header-action > button { color: #4ade80; }

        /* Pestañas (enlaces): la activa toma el color de acento */
        .support-report-page .panel-tab[aria-current="page"] { color: var(--panel-accent); }

        /* Opciones deshabilitadas de las listas: sin resaltado al pasar el cursor */
        html [data-support-dropdown] .panel-option:disabled:hover { background-color: var(--panel-surface); }

        /* Botones de icono de eliminar: toman el rojo del panel al pasar el cursor */
        .panel-icon-button.support-icon-danger:hover { color: var(--panel-danger); }

        /* Campos que abren una lista: respuesta al pasar el cursor */
        .support-trigger { transition: background-color 150ms ease; }
        .support-trigger:hover:not(:disabled) { background-color: var(--support-hover); }

        /* Casillas */
        [data-panel] input[type="checkbox"].support-checkbox {
            appearance: auto;
            -webkit-appearance: checkbox;
            height: 16px;
            width: 16px;
            flex-shrink: 0;
            accent-color: var(--panel-accent);
            background-image: none;
        }
        [data-panel] input[type="checkbox"].support-checkbox:focus,
        [data-panel] input[type="checkbox"].support-checkbox:focus-visible { outline: none; box-shadow: none; }

        /* Casilla dibujada de la lista de personas */
        .support-check {
            border-color: var(--panel-border);
            background-color: var(--panel-surface);
            color: transparent;
        }
        .support-check-on {
            border-color: var(--panel-accent);
            background-color: var(--panel-accent);
            color: #ffffff;
        }
        html.module-dark-theme [data-panel] .support-check-on { color: #09090b; }

        /* Filas de las tablas: resaltado al pasar el cursor y al estar seleccionadas */
        .support-row { transition: background-color 150ms ease; }
        .support-row:not(.is-selected):hover { background-color: var(--support-hover); }
        .support-row.is-selected { background-color: color-mix(in srgb, var(--panel-accent) 8%, transparent); }
        .support-row.is-selected:hover { background-color: color-mix(in srgb, var(--panel-accent) 14%, transparent); }

        /* Fechas de la columna izquierda, como en Gestión de Respaldos. */
        .support-date { transition: background-color 150ms ease; }
        .support-date:not(.is-selected):hover { background-color: var(--support-hover); }
        .support-date.is-selected { background-color: var(--panel-header); color: var(--panel-accent); }

        /* Indicador de scroll de la lista de fechas: gris, fino y separado del borde para no parecer una marca de selección */
        .support-date-scroll .panel-scroll-thumb {
            right: 3px;
            width: 2px;
            background: color-mix(in srgb, var(--panel-muted) 65%, transparent);
        }

        /* Las listas llenan la tarjeta: la última línea queda bajo el borde de la tarjeta (sin doble línea) */
        .support-flush { margin-bottom: -1px; }

        /* Cantidad sin flechas */
        .support-quantity-input { appearance: textfield; -moz-appearance: textfield; text-align: center; }
        .support-quantity-input::-webkit-inner-spin-button,
        .support-quantity-input::-webkit-outer-spin-button { margin: 0; appearance: none; -webkit-appearance: none; }

        /* Tabla de servicios.
           Celular: casilla a la izquierda y, a su derecha, servicio, personas y cantidad apilados.
           Tablet: casilla, servicio y cantidad en una línea y las personas debajo.
           Escritorio: cuatro columnas. */
        .support-service-grid {
            display: grid;
            grid-template-columns: 60px minmax(0, 1fr);
        }
        .support-service-grid > * { min-width: 0; }
        .support-service-grid > .support-check-cell { grid-column: 1; grid-row: 1 / span 3; border-right: 1px solid var(--panel-border); }
        .support-service-grid > .support-name-cell { grid-column: 2; grid-row: 1; }
        .support-service-grid > .support-people-cell { grid-column: 2; grid-row: 2; border-top: 1px solid var(--panel-border); }
        .support-service-grid > .support-qty-cell { grid-column: 2; grid-row: 3; border-top: 1px solid var(--panel-border); }
        .support-service-head { display: none; }

        @media (min-width: 640px) {
            .support-service-grid { grid-template-columns: 60px minmax(0, 1fr) 150px; }
            .support-service-row { min-height: 85px; }
            .support-service-grid > .support-check-cell { grid-column: auto; grid-row: auto; }
            .support-service-grid > .support-name-cell { grid-column: auto; grid-row: auto; border-right: 1px solid var(--panel-border); }
            .support-service-grid > .support-people-cell { grid-column: 1 / -1; grid-row: auto; order: 5; }
            .support-service-grid > .support-qty-cell { grid-column: auto; grid-row: auto; border-top: 0; justify-content: center; }
            .support-service-head { display: grid; }
            .support-service-head > .support-head-check,
            .support-service-head > .support-head-name { border-right: 1px solid var(--panel-border); }
            .support-service-head > .support-head-people { display: none; }
        }

        @media (min-width: 900px) {
            .support-service-grid { grid-template-columns: 60px minmax(0, 1fr) 340px 150px; }
            .support-service-grid > .support-people-cell { grid-column: auto; order: 0; border-top: 0; border-right: 1px solid var(--panel-border); }
            .support-service-head > .support-head-people { display: block; border-right: 1px solid var(--panel-border); }
        }

        @media (min-width: 1280px) {
            .support-service-grid { grid-template-columns: 60px minmax(0, 1fr) 480px 170px; }
        }

        .support-versions-grid { display: grid; gap: 20px; }
        @media (min-width: 900px) {
            .support-versions-grid { grid-template-columns: 300px minmax(0, 1fr); }
        }

        /* Paginación de Laravel (usa sus propias clases claras): mismos colores que el resto del panel */
        .support-pagination nav { gap: 15px; }
        .support-pagination p { color: var(--panel-muted); }
        .support-pagination :is(a, span)[class*="border"] {
            border-color: var(--panel-border);
            background-color: var(--panel-surface);
            color: var(--panel-text);
        }
        .support-pagination a[class*="border"]:hover { background-color: var(--support-hover); color: var(--panel-accent); }
        .support-pagination [aria-current="page"] > span { background-color: var(--panel-header); color: var(--panel-accent); }
        .support-pagination [aria-disabled="true"] > span { color: var(--panel-muted); opacity: .6; }
    </style>

    <div
        data-panel
        class="support-report-page relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 overflow-hidden text-[15px]"
        x-data="{
            viewScale: 90,
            isFullscreen: false,
            isCompact: window.innerWidth / window.appAutoScale() < 1024,
            scaledHeight: null,
            listFit: null,
            observer: null,
            get effScale() {
                return (this.isCompact ? 100 : this.viewScale) * (window.appAutoScale ? window.appAutoScale() : 1);
            },
            init() {
                try {
                    const saved = Number(null);
                    if (saved >= 70 && saved <= 100) this.viewScale = saved;
                } catch (error) {}
                this.$nextTick(() => {
                    this.observer = new ResizeObserver(() => this.refresh());
                    this.observer.observe(this.$refs.scaled);
                    this.refresh();
                });
            },
            destroy() {
                this.observer?.disconnect();
            },
            refresh() {
                this.fitList();
                this.syncHeight();
            },
            syncHeight() {
                if (this.$refs.scaled) this.scaledHeight = Math.ceil(this.$refs.scaled.offsetHeight * (this.viewScale / 100));
            },
            fitList() {
                // Generar reporte, Historial y Movimientos: la tarjeta llena la pantalla (con el mismo espacio abajo que arriba)
                // y sus listas se desplazan por dentro, así las tres tienen siempre la misma altura.
                const el = this.$root.querySelector('[data-fit-card]');
                if (!el || this.isCompact) { this.listFit = null; return; }
                const k = this.viewScale / 100;
                const rootTop = document.fullscreenElement === this.$root ? 0 : this.$root.getBoundingClientRect().top + window.scrollY;
                const offset = el.getBoundingClientRect().top - this.$root.getBoundingClientRect().top;
                const bottom = parseFloat(getComputedStyle(this.$refs.scaled.querySelector('main')).paddingBottom) || 0;
                const room = window.innerHeight - rootTop - offset - bottom * k;
                const fit = Math.max(Number(el.dataset.fitMin) || 520, Math.floor(room / k) - 1);
                if (fit !== this.listFit) this.listFit = fit;
            },
            setScale(value) {
                this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
                try { localStorage.setItem('support-reports-view-scale', String(this.viewScale)); } catch (error) {}
                this.$nextTick(() => this.refresh());
            },
            async toggleFullscreen(container) {
                if (document.fullscreenElement === container) return document.exitFullscreen();
                if (document.fullscreenElement) await document.exitFullscreen();
                await container.requestFullscreen();
            }
        }"
        @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root; $nextTick(() => refresh())"
        @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024; refresh()"
    >
        <div class="w-full" :style="(!isCompact && scaledHeight) ? 'height:' + scaledHeight + 'px' : null">
            <div x-ref="scaled" class="origin-top-left" :style="isCompact ? 'width:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
                <main class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
                    <!-- Cabecera -->
                    <section class="flex shrink-0 flex-wrap items-center justify-between gap-[20px]">
                        <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                            <span class="panel-surface panel-border panel-text grid h-14 w-14 shrink-0 place-items-center rounded-xl border">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Reportes de soporte</h1>
                                <p class="mt-[15px] leading-none">Genera y consulta los documentos de los servicios técnicos realizados.</p>
                            </div>
                        </div>
                        <x-support-report-trash :reports="$trashedReports" class="support-report-header-action w-full lg:ml-auto lg:w-auto" />
                    </section>

                    <!-- Secciones -->
                    <nav class="flex flex-wrap items-center gap-x-[20px] gap-y-[10px]" aria-label="Secciones de reportes de soporte">
                        <a href="{{ route('soporte.reportes-tecnicos') }}" @if ($activeTab === 'generate') aria-current="page" @endif class="panel-tab flex min-h-[44px] items-center gap-[10px] leading-none">
                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m-6 9 2 2 4-4"/></svg>
                            <span>Generar reporte</span>
                        </a>
                        <a href="{{ route('soporte.reportes-tecnicos.history') }}" @if ($activeTab === 'history') aria-current="page" @endif class="panel-tab flex min-h-[44px] items-center gap-[10px] leading-none">
                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <span>Historial</span>
                        </a>
                        <a href="{{ route('soporte.reportes-tecnicos.movements') }}" @if ($activeTab === 'movements') aria-current="page" @endif class="panel-tab flex min-h-[44px] items-center gap-[10px] leading-none">
                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                            <span>Historial de movimientos</span>
                        </a>
                    </nav>

                    @if (session('status'))
                        <div class="panel-surface panel-border rounded-xl border p-[20px] leading-tight" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($activeTab === 'generate')
                    <section
                        class="panel-surface panel-border flex flex-col overflow-hidden rounded-xl border"
                        data-fit-card data-fit-min="520" :style="listFit ? 'height:' + listFit + 'px' : null"
                        @keydown.escape.window="summaryOpen = false"
                x-data="{
                    query: '',
                    summaryOpen: false,
                    selected: @js(old('supports', [])),
                    quantities: @js(old('support_quantities', [])),
                    targets: @js(old('support_targets', [])),
                    catalog: @js($catalog),
                    directory: @js($peopleDirectory->values()->all()),
                    init() {
                        this.directory = this.directory.map(person => ({ ...person, norm: this.normalize(person.name) }));
                        this.quantities = Object.assign({}, this.quantities);
                        this.targets = Object.assign({}, this.targets);
                        this.catalog.forEach(item => {
                            if (! this.selected.includes(item.key)) this.quantities[item.key] = 0;
                        });
                    },
                    normalize(value) {
                        return value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
                    },
                    matches(item) {
                        const term = this.normalize(this.query.trim());
                        return !term || this.normalize(item.title + ' ' + item.description).includes(term);
                    },
                    syncRow(key) {
                        if (this.selected.includes(key)) {
                            if (! this.quantities[key]) this.quantities[key] = 1;
                        } else {
                            this.quantities[key] = 0;
                            this.targets[key] = '';
                        }
                    },
                    hasServerUpdate() {
                        return this.selected.some(key => key.endsWith('_server_update'));
                    },
                    itemFor(key) {
                        return this.catalog.find(item => item.key === key) || { key: key, title: key, description: '' };
                    },
                    people(key) {
                        return String(this.targets[key] ?? '').split(/\r\n|\r|\n/).map(value => value.trim()).filter(Boolean);
                    },
                    maxPeople(key) {
                        return Math.max(1, parseInt(this.quantities[key], 10) || 1);
                    },
                    overLimit(key) {
                        return this.people(key).length > this.maxPeople(key);
                    },
                    hasOverLimit() {
                        return this.selected.some(key => this.overLimit(key));
                    },
                    hasPeople() {
                        return this.selected.some(key => this.people(key).length > 0);
                    },
                    canSubmit() {
                        return this.selected.length > 0 && this.hasPeople() && ! this.hasOverLimit();
                    },
                    recipient() {
                        const byName = new Map(this.directory.map(person => [person.norm, person]));
                        const groups = new Map();
                        const clients = new Map();
                        this.selected.flatMap(key => this.people(key)).forEach(name => {
                            const person = byName.get(this.normalize(name));
                            if (person && person.type === 'user' && person.group) groups.set(this.normalize(person.group), person.group);
                            else clients.set(this.normalize(name), name);
                        });
                        const sorter = (a, b) => a.localeCompare(b, 'es', { sensitivity: 'base', numeric: true });
                        const parts = [...[...groups.values()].sort(sorter), ...[...clients.values()].sort(sorter)];
                        return parts.length > 1 ? parts.slice(0, -1).join(', ') + ' y ' + parts[parts.length - 1] : (parts[0] || '');
                    }
                }"
                    >
                        <form method="POST" action="{{ route('soporte.reportes-tecnicos.store') }}" class="flex min-h-0 flex-1 flex-col">
                            @csrf

                            <header class="panel-header panel-border flex shrink-0 flex-col gap-[15px] border-b p-[20px] sm:flex-row sm:items-center sm:justify-between sm:gap-[20px]">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-[10px]">
                                        <h2 class="panel-accent leading-none">Generar reporte</h2>
                                        <span class="panel-accent leading-none">(<span x-text="selected.length">0</span>)</span>
                                    </div>
                                    <p class="mt-[15px] leading-tight" :class="hasOverLimit() ? 'panel-tone-red' : ''" x-text="hasOverLimit() ? 'Hay servicios con más personas que su cantidad.' : (selected.length > 0 && ! hasPeople() ? 'Elige al menos una persona o cliente para generar el PDF.' : 'El folio y la fecha se asignarán automáticamente.')">El folio y la fecha se asignarán automáticamente.</p>
                                </div>
                                <div class="no-print flex flex-col gap-[10px] sm:flex-row sm:items-center">
                                    <button type="button" @click="summaryOpen = true" class="panel-button-secondary inline-flex h-11 w-full items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0 sm:w-auto">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                        <span>Ver resumen</span>
                                    </button>
                                    <button type="submit" :disabled="! canSubmit()" class="support-report-primary inline-flex h-11 w-full items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0 sm:w-auto">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg>
                                        <span>Generar PDF</span>
                                    </button>
                                </div>
                            </header>

                            <div class="panel-header panel-border flex shrink-0 flex-col gap-[15px] border-b p-[20px] sm:flex-row sm:items-center sm:gap-[20px] min-[900px]:min-h-[85px]">
                                <label class="relative block min-w-0 flex-1">
                                    <span class="sr-only">Buscar servicios de soporte</span>
                                    <input x-model="query" type="search" placeholder="Buscar instalación, CONTPAQi, correo, Office..." class="panel-field block h-11 w-full rounded-xl border px-[20px] focus:ring-0">
                                </label>
                                <span class="shrink-0 leading-none"><span x-text="selected.length">0</span> de 8 seleccionados</span>
                            </div>

                            <div x-show="hasServerUpdate()" x-cloak class="support-versions-grid panel-surface panel-border shrink-0 border-b p-[20px]">
                                <div>
                                    <h2 class="panel-accent leading-none">Versiones aplicadas</h2>
                                    <p class="mt-[10px] leading-tight">Solo aparece al seleccionar una actualización de servidor.</p>
                                </div>
                                <label class="block min-w-0">
                                    <span class="sr-only">Todas las versiones utilizadas</span>
                                    <textarea name="versions_applied" rows="3" placeholder="Una versión por línea:&#10;18.3.1&#10;19.0.1&#10;19.4.0" class="panel-field block w-full resize-y rounded-xl border px-[20px] py-[15px] focus:ring-0">{{ old('versions_applied') }}</textarea>
                                    @error('versions_applied')<span class="panel-tone-red mt-[10px] block leading-tight">{{ $message }}</span>@enderror
                                </label>
                            </div>

                            <div class="support-service-grid support-service-head panel-header panel-border panel-accent shrink-0 border-b leading-none">
                                <div class="support-head-check p-[20px]"><span class="sr-only">Seleccionar</span></div>
                                <div class="support-head-name p-[20px]">Servicio</div>
                                <div class="support-head-people p-[20px]">Personas o equipos atendidos</div>
                                <div class="support-head-qty p-[20px] text-center">Cantidad</div>
                            </div>

                            <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="query; selected.length; hasServerUpdate(); listFit; isCompact; $nextTick(() => update())" class="support-flush relative min-h-[260px] flex-1" :style="isCompact ? 'flex: none; height: min(640px, 70dvh)' : null">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                @error('supports')
                                    <p class="panel-tone-red panel-border border-b p-[20px] leading-tight">{{ $message }}</p>
                                @enderror
                                @error('supports.*')
                                    <p class="panel-tone-red panel-border border-b p-[20px] leading-tight">{{ $message }}</p>
                                @enderror
                                @foreach ($errors->get('support_targets') as $message)
                                    <p class="panel-tone-red panel-border border-b p-[20px] leading-tight">{{ $message }}</p>
                                @endforeach
                                @foreach ($errors->get('support_targets.*') as $messages)
                                    @foreach ($messages as $message)
                                        <p class="panel-tone-red panel-border border-b p-[20px] leading-tight">{{ $message }}</p>
                                    @endforeach
                                @endforeach

                                <template x-for="item in catalog" :key="item.key">
                                    <div x-show="matches(item)" class="support-service-grid support-service-row support-row panel-border border-b" :class="selected.includes(item.key) ? 'is-selected' : ''">
                                        <label class="support-check-cell flex cursor-pointer items-start justify-center p-[20px] sm:items-center">
                                            <input type="checkbox" name="supports[]" :value="item.key" x-model="selected" @change="$nextTick(() => syncRow(item.key))" :disabled="selected.length >= 8 && !selected.includes(item.key)" :aria-label="item.title" class="support-checkbox support-service-checkbox disabled:opacity-40">
                                        </label>

                                        <div class="support-name-cell flex min-w-0 flex-col justify-center p-[20px]">
                                            <span class="panel-accent block leading-none" x-text="item.title"></span>
                                            <span class="mt-[10px] block leading-tight" x-text="item.description"></span>
                                        </div>

                                        <div class="support-people-cell flex min-w-0 items-center p-[20px]">
                                            <textarea hidden :name="`support_targets[${item.key}]`" x-model="targets[item.key]" :disabled="!selected.includes(item.key)"></textarea>
                                            <x-support-people-picker class="w-full" />
                                        </div>

                                        <div class="support-qty-cell flex items-center p-[20px]">
                                            <div class="flex items-center gap-[10px] leading-none" :style="selected.includes(item.key) ? '' : 'opacity: 0.4;'">
                                                <span class="panel-muted shrink-0 sm:hidden">Cantidad</span>
                                                <span class="shrink-0" :class="overLimit(item.key) ? 'panel-tone-red' : 'panel-accent'" x-text="`${people(item.key).length} de`">0 de</span>
                                                <input type="number" min="1" max="999" :name="`support_quantities[${item.key}]`" x-model.number="quantities[item.key]" :disabled="!selected.includes(item.key)" :style="overLimit(item.key) ? 'border-color: var(--panel-danger) !important;' : ''" aria-label="Cantidad" title="Cantidad: máximo de personas atendidas" class="support-quantity-input panel-field h-11 w-[56px] shrink-0 rounded-xl border px-[10px] text-center focus:ring-0 focus:ring-offset-0">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                </div>
                                <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>
                        </form>

                        {{-- Resumen del reporte: destinatario calculado y servicios elegidos, antes de generar el PDF. --}}
                        <template x-teleport="body">
                            <div data-support-modal x-cloak x-show="summaryOpen" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/50 p-[20px]" role="dialog" aria-modal="true" aria-labelledby="support-summary-title">
                                <div data-panel :style="{ transform: `scale(${effScale / 100})` }" @click.outside="summaryOpen = false" class="panel-surface panel-border flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border shadow-2xl" style="height: min(620px, calc(100dvh - 40px));">
                                    <header class="panel-header panel-border flex shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                        <div class="flex min-w-0 items-center gap-[15px]">
                                            <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6M9 8h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                                            </span>
                                            <div class="min-w-0">
                                                <h3 id="support-summary-title" class="panel-accent truncate leading-none">Resumen del reporte</h3>
                                                <p class="mt-[10px] leading-tight sm:truncate">Revisa el destinatario y los servicios antes de generar el PDF.</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="summaryOpen = false" aria-label="Cerrar" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </header>

                                    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="summaryOpen; selected.length; $nextTick(() => update())" class="relative min-h-0 flex-1">
                                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                            <div class="panel-border border-b p-[20px]">
                                                <span class="block leading-none">Destinatario del reporte</span>
                                                <span x-show="recipient() !== ''" x-cloak class="panel-accent mt-[10px] block leading-tight" x-text="recipient()"></span>
                                                <span x-show="recipient() === ''" class="panel-muted mt-[10px] block leading-tight">Se llenará al elegir personas.</span>
                                                <span class="panel-muted mt-[15px] block leading-tight">Se toma del departamento de cada colaborador elegido o del nombre del cliente externo.</span>
                                            </div>

                                            <div class="panel-header panel-border panel-accent flex items-center gap-[10px] border-b p-[20px] leading-none">
                                                <span>Servicios seleccionados (<span x-text="selected.length">0</span>)</span>
                                            </div>

                                            <p x-show="selected.length === 0" class="panel-muted p-[20px] leading-tight">Aún no hay servicios seleccionados. Marca los de la tabla y aquí verás cuántos y para quién.</p>
                                            <template x-for="key in selected" :key="key">
                                                <div class="panel-border border-b p-[20px]">
                                                    <span class="panel-accent block truncate leading-none" x-text="itemFor(key).title"></span>
                                                    <span class="mt-[10px] block truncate leading-none" x-text="`Cantidad: ${quantities[key] || 1}`"></span>
                                                    <span class="mt-[10px] block leading-tight" :class="overLimit(key) ? 'panel-tone-red' : ''" x-text="people(key).length ? (overLimit(key) ? `Personas: ${people(key).length} de ${maxPeople(key)} permitidas` : `Personas: ${people(key).join(', ')}`) : 'Personas: sin elegir'"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                    </div>

                                    <footer class="panel-border flex shrink-0 justify-end gap-[10px] border-t p-[20px]">
                                        <button type="button" @click="summaryOpen = false" class="panel-button-secondary inline-flex h-11 items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">Cerrar</button>
                                    </footer>
                                </div>
                            </div>
                        </template>
                    </section>
                    @elseif ($activeTab === 'history')
                    <section
                x-data="{
                    confirmDelete: null,
                    deleteFolioText: '',
                    deleteWordText: '',
                    canDelete() {
                        return this.confirmDelete !== null
                            && this.deleteFolioText.trim().toUpperCase() === String(this.confirmDelete.folio).toUpperCase()
                            && this.deleteWordText.trim().toUpperCase() === 'ELIMINAR';
                    },
                    selectedReports: [],
                    pdfUrl: @js(route('soporte.reportes-tecnicos.pdf', ['report' => '__FOLIO__'])),
                    viewSelection() {
                        this.selectedReports.forEach(folio => window.open(this.pdfUrl.replace('__FOLIO__', encodeURIComponent(folio)), '_blank', 'noopener'));
                    },
                    pageFolios: @js($reports->pluck('folio')->values()),
                    allPageSelected() {
                        return this.pageFolios.length > 0 && this.pageFolios.every(folio => this.selectedReports.includes(folio));
                    },
                    togglePage() {
                        if (this.allPageSelected()) {
                            this.selectedReports = this.selectedReports.filter(folio => !this.pageFolios.includes(folio));
                            return;
                        }

                        this.selectedReports = [...new Set([...this.selectedReports, ...this.pageFolios])];
                    },
                    toggleReport(folio) {
                        this.selectedReports = this.selectedReports.includes(folio)
                            ? this.selectedReports.filter(selected => selected !== folio)
                            : [...this.selectedReports, folio];
                    }
                }"
                        class="panel-surface panel-border flex flex-col overflow-hidden rounded-xl border"
                        data-fit-card data-fit-min="520" :style="listFit ? 'height:' + listFit + 'px' : null"
                    >
                        <header class="panel-header panel-border flex shrink-0 flex-col gap-[15px] border-b p-[20px] sm:flex-row sm:items-center sm:justify-between sm:gap-[20px]">
                            <div class="min-w-0">
                                <div class="flex items-center gap-[10px]">
                                    <h2 class="panel-accent leading-none">Historial de reportes</h2>
                                    <span class="panel-accent leading-none">({{ $reports->total() }})</span>
                                </div>
                                <p class="mt-[15px] leading-tight">Consulta, filtra o descarga los documentos generados.</p>
                            </div>
                            <div class="no-print flex flex-col gap-[10px] sm:flex-row sm:items-center">
                                <button type="button" @click="viewSelection()" :disabled="selectedReports.length === 0" class="panel-button-secondary inline-flex h-11 w-full items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0 sm:w-auto">
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                    <span>Ver selección (<span x-text="selectedReports.length">0</span>)</span>
                                </button>
                                <form method="POST" action="{{ route('soporte.reportes-tecnicos.download-selection') }}" class="w-full sm:w-auto">
                                    @csrf
                                    <template x-for="folio in selectedReports" :key="folio">
                                        <input type="hidden" name="report_folios[]" :value="folio">
                                    </template>
                                    <button type="submit" :disabled="selectedReports.length === 0" class="support-report-primary inline-flex h-11 w-full items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0 sm:w-auto">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg>
                                        <span>Descargar selección (<span x-text="selectedReports.length">0</span>)</span>
                                    </button>
                                </form>
                            </div>
                        </header>

                        <div class="grid min-h-0 flex-1 min-[900px]:grid-cols-[220px_minmax(0,1fr)] min-[900px]:grid-rows-[minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)]">
                            <aside class="panel-surface panel-border flex h-[230px] flex-col overflow-hidden border-b min-[900px]:h-auto min-[900px]:border-b-0 min-[900px]:border-r" aria-label="Fechas de reportes de soporte">
                                <form method="GET" action="{{ route('soporte.reportes-tecnicos.history') }}" class="panel-header panel-border flex h-[85px] shrink-0 items-center border-b px-[20px]">
                                    @foreach (['q' => $filters['search'], 'recipient' => $filters['recipient'], 'target' => $filters['target']] as $field => $fieldValue)
                                        @if ($fieldValue !== '')
                                            <input type="hidden" name="{{ $field }}" value="{{ $fieldValue }}">
                                        @endif
                                    @endforeach
                                    <x-support-select name="period" label="Fecha de soportes" :value="$filters['period'] === 'day' ? '' : $filters['period']" :options="[['value' => 'week', 'label' => 'Por semana'], ['value' => 'year', 'label' => 'Por año']]" all-label="Por día" class="w-full" />
                                </form>
                                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="listFit; isCompact; $nextTick(() => update())" class="support-date-scroll support-flush relative min-h-0 flex-1">
                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                        @php
                                            $dateQuery = request()->except('page', 'date');
                                        @endphp
                                        @foreach ($dateOptions as $day)
                                            <a href="{{ route('soporte.reportes-tecnicos.history', array_merge($dateQuery, ['date' => $day['date']])) }}" @if ($filters['date'] === $day['date']) aria-current="true" @endif class="support-date panel-border flex min-h-[90px] w-full flex-col justify-center border-b px-[20px] py-[20px] {{ $filters['date'] === $day['date'] ? 'is-selected' : '' }}">
                                                <span class="support-date-label block w-full truncate leading-none" title="{{ $day['label'] }}">{{ $day['label'] }}</span>
                                                <span class="mt-[15px] leading-none">{{ $day['count'] }} {{ $day['count'] === 1 ? 'reporte' : 'reportes' }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                    <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>
                            </aside>

                            <div class="flex min-h-0 min-w-0 flex-col">
                                <div class="panel-header panel-border flex shrink-0 items-center border-b p-[20px] min-[900px]:min-h-[85px]">
                                    <form method="GET" action="{{ route('soporte.reportes-tecnicos.history') }}" class="flex w-full min-w-0 flex-col gap-[15px] sm:flex-row sm:flex-wrap sm:items-center sm:gap-[20px]">
                                        @if ($filters['date'] !== '')
                                            <input type="hidden" name="date" value="{{ $filters['date'] }}">
                                        @endif
                                        @if ($filters['period'] !== 'day')
                                            <input type="hidden" name="period" value="{{ $filters['period'] }}">
                                        @endif
                                        <x-support-folio-search name="q" :value="$filters['search']" :options="$folioOptions" placeholder="Buscar folio o servicio..." class="w-full sm:min-w-[220px] sm:flex-1" />
                                        <x-support-select name="recipient" :value="$filters['recipient']" :options="$recipientOptions" all-label="Todos los departamentos" class="w-full sm:w-[220px]" />
                                        <x-support-select name="target" :value="$filters['target']" :options="$targetOptions" all-label="Todas las personas" class="w-full sm:w-[220px]" />
                                        @if ($filters['search'] !== '' || $filters['recipient'] !== '' || $filters['target'] !== '')
                                            <a href="{{ route('soporte.reportes-tecnicos.history', array_filter(['date' => $filters['date'], 'period' => $filters['period'] !== 'day' ? $filters['period'] : ''], fn ($value) => $value !== '')) }}" class="panel-action inline-flex h-11 items-center gap-[10px] leading-none focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                                <span>Limpiar</span>
                                            </a>
                                        @endif
                                    </form>
                                </div>

                                <div class="panel-surface panel-border flex shrink-0 items-center border-b px-[20px] min-[900px]:hidden">
                                    <label class="inline-flex cursor-pointer items-center gap-[10px] py-[20px] leading-none">
                                        <input type="checkbox" :checked="allPageSelected()" @change="togglePage()" class="support-checkbox">
                                        <span>Seleccionar página</span>
                                    </label>
                                </div>

                                <div class="panel-header panel-border panel-accent hidden shrink-0 border-b leading-none min-[900px]:grid min-[900px]:grid-cols-[50px_185px_minmax(0,1fr)_150px] lg:grid-cols-[60px_210px_minmax(0,1fr)_200px]">
                                    <label class="panel-border flex cursor-pointer items-center justify-center border-r p-[20px]" title="Seleccionar página">
                                        <input type="checkbox" :checked="allPageSelected()" @change="togglePage()" class="support-checkbox" aria-label="Seleccionar página">
                                    </label>
                                    <div class="panel-border border-r p-[20px] text-center">Folio y fecha</div>
                                    <div class="panel-border border-r p-[20px]">Servicios y destinatario</div>
                                    <div class="p-[20px] text-center">Acciones</div>
                                </div>

                                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="listFit; isCompact; $nextTick(() => update())" class="relative min-h-[260px] flex-1 {{ $reports->hasPages() ? '' : 'support-flush' }}" :style="isCompact ? 'flex: none; height: min(560px, 70dvh)' : null">
                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                    @forelse ($reports as $report)
                                        @php
                                            $supportSummary = collect($report->supports)
                                                ->map(fn ($support) => $support['title'].' ('.((int) ($support['quantity'] ?? 1)).')')
                                                ->join(', ');
                                            $targetSummary = collect($report->supports)
                                                ->flatMap(fn ($support) => $support['targets'] ?? [])
                                                ->filter()
                                                ->unique()
                                                ->join(', ');
                                        @endphp
                                        <article @click="if (!$event.target.closest('a, button, input, label')) toggleReport('{{ $report->folio }}')" :class="selectedReports.includes(@js($report->folio)) ? 'is-selected' : ''" class="support-row panel-border grid min-h-[77px] cursor-pointer grid-cols-[60px_minmax(0,1fr)] border-b min-[900px]:grid-cols-[50px_185px_minmax(0,1fr)_150px] lg:grid-cols-[60px_210px_minmax(0,1fr)_200px]">
                                            <div class="panel-border row-span-3 flex items-start justify-center border-r p-[20px] min-[900px]:row-span-1 min-[900px]:items-center">
                                                <input type="checkbox" value="{{ $report->folio }}" x-model="selectedReports" @click.stop class="support-checkbox" aria-label="Seleccionar {{ $report->folio }}">
                                            </div>
                                            <div class="panel-border col-start-2 flex min-w-0 flex-col justify-center border-b p-[20px] min-[900px]:col-start-auto min-[900px]:border-b-0 min-[900px]:border-r min-[900px]:text-center">
                                                <p class="panel-accent leading-none">{{ $report->folio }}</p>
                                                <p class="mt-[10px] leading-none">{{ $report->issued_at->timezone(config('support.timezone', 'America/Mexico_City'))->translatedFormat('d M Y · H:i') }}</p>
                                            </div>
                                            <div class="panel-border col-start-2 flex min-w-0 flex-col justify-center border-b p-[20px] min-[900px]:col-start-auto min-[900px]:border-b-0 min-[900px]:border-r">
                                                <p class="panel-accent break-words leading-tight min-[900px]:truncate" title="{{ $supportSummary }}">{{ $supportSummary }}</p>
                                                <p class="mt-[10px] break-words leading-tight min-[900px]:truncate" title="Departamento o cliente: {{ $report->service_recipient }}">Departamento o cliente: {{ $report->service_recipient }}</p>
                                                @if ($targetSummary !== '')
                                                    <p class="mt-[10px] break-words leading-tight min-[900px]:truncate" title="Personas o equipos: {{ $targetSummary }}">Personas o equipos: {{ $targetSummary }}</p>
                                                @endif
                                            </div>
                                            <div class="col-start-2 flex items-center justify-start gap-[10px] px-[20px] py-[10px] min-[900px]:col-start-auto min-[900px]:justify-center min-[900px]:gap-[5px] min-[900px]:px-[10px] lg:gap-[10px] lg:p-[20px]">
                                                <a @click.stop href="{{ route('soporte.reportes-tecnicos.pdf', $report) }}" target="_blank" rel="noopener" class="panel-icon-button flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0" aria-label="Ver {{ $report->folio }}" title="Ver"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg></a>
                                                <a @click.stop href="{{ route('soporte.reportes-tecnicos.pdf', ['report' => $report, 'download' => 1]) }}" class="panel-icon-button flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0" aria-label="Descargar {{ $report->folio }}" title="Descargar"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg></a>
                                                @if (auth()->user()?->isAdmin() || (int) $report->created_by === (int) auth()->id())
                                                    <button type="button" @click.stop="deleteFolioText = ''; deleteWordText = ''; confirmDelete = { folio: @js($report->folio), url: @js(route('soporte.reportes-tecnicos.destroy', $report)) }" class="panel-icon-button support-icon-danger flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0" aria-label="Eliminar {{ $report->folio }}" title="Eliminar"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/></svg></button>
                                                @endif
                                            </div>
                                        </article>
                                    @empty
                                        <div class="panel-muted grid h-full min-h-[260px] place-items-center p-[20px] text-center leading-tight">No hay reportes que coincidan con los filtros.</div>
                                    @endforelse
                                    </div>
                                    <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>

                                @if ($reports->hasPages())
                                    <div class="support-pagination panel-surface panel-border relative shrink-0 border-t p-[20px]">{{ $reports->links() }}</div>
                                @endif
                            </div>
                        </div>

                        <template x-teleport="body">
                            <div data-support-modal x-cloak x-show="confirmDelete" @keydown.escape.window="confirmDelete = null" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/50 p-[20px]" role="dialog" aria-modal="true" aria-labelledby="support-delete-title">
                                <div data-panel :style="{ transform: `scale(${effScale / 100})` }" @click.outside="confirmDelete = null" class="panel-surface panel-border flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border shadow-2xl" style="max-height: calc(100dvh - 40px);">
                                    <header class="panel-header panel-border flex shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                        <div class="flex min-w-0 items-center gap-[15px]">
                                            <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/></svg>
                                            </span>
                                            <div class="min-w-0">
                                                <h3 id="support-delete-title" class="panel-accent truncate leading-none">Eliminar reporte</h3>
                                                <p class="mt-[10px] leading-tight sm:truncate">Se enviará a la papelera de reciclaje.</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="confirmDelete = null" aria-label="Cerrar" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </header>

                                    <form method="POST" :action="confirmDelete?.url" @submit="if (! canDelete()) $event.preventDefault()" class="flex min-h-0 flex-1 flex-col overflow-y-auto">
                                        @csrf
                                        @method('DELETE')

                                        <div class="p-[20px]">
                                            <section class="panel-border border-b pb-[20px]">
                                                <h2 class="leading-none">Reporte seleccionado</h2>
                                                <p class="panel-accent mt-[15px] leading-none" x-text="confirmDelete?.folio"></p>
                                            </section>

                                            <section class="pt-[20px]">
                                                <h2 class="leading-none">Verificación</h2>

                                                <label class="mt-[15px] block">
                                                    <span class="block leading-none">Escribe el folio exacto del reporte</span>
                                                    <input type="text" x-model="deleteFolioText" autocomplete="new-password" onpaste="return false" class="panel-field mt-[10px] block h-11 w-full rounded-xl border px-[20px] uppercase focus:ring-0" placeholder="Folio del reporte...">
                                                </label>

                                                <label class="mt-[20px] block">
                                                    <span class="block leading-none">Escribe ELIMINAR</span>
                                                    <input type="text" x-model="deleteWordText" autocomplete="new-password" onpaste="return false" class="panel-field mt-[10px] block h-11 w-full rounded-xl border px-[20px] uppercase focus:ring-0" placeholder="ELIMINAR">
                                                </label>
                                            </section>
                                        </div>

                                        <div class="panel-border mt-auto flex justify-end gap-[10px] border-t p-[20px]">
                                            <button type="button" @click="confirmDelete = null" class="panel-button-secondary inline-flex h-11 items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">Cancelar</button>
                                            <button type="submit" :disabled="! canDelete()" class="panel-button inline-flex h-11 items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">Eliminar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </section>
                    @else
                    <section class="panel-surface panel-border flex flex-col overflow-hidden rounded-xl border" data-fit-card data-fit-min="520" :style="listFit ? 'height:' + listFit + 'px' : null">
                        <header class="panel-header panel-border flex shrink-0 flex-col gap-[15px] border-b p-[20px] sm:flex-row sm:items-center sm:justify-between sm:gap-[20px]">
                            <div class="min-w-0">
                                <div class="flex items-center gap-[10px]">
                                    <h2 class="panel-accent leading-none">Historial de movimientos</h2>
                                    <span class="panel-accent leading-none">({{ $movements->total() }})</span>
                                </div>
                                <p class="mt-[15px] leading-tight">Registro permanente de reportes generados y eliminados.</p>
                            </div>
                        </header>

                        <div class="grid min-h-0 flex-1 min-[900px]:grid-cols-[220px_minmax(0,1fr)] min-[900px]:grid-rows-[minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)]">
                            <aside class="panel-surface panel-border flex h-[230px] flex-col overflow-hidden border-b min-[900px]:h-auto min-[900px]:border-b-0 min-[900px]:border-r" aria-label="Fechas de movimientos de reportes de soporte">
                                <form method="GET" action="{{ route('soporte.reportes-tecnicos.movements') }}" class="panel-header panel-border flex h-[85px] shrink-0 items-center border-b px-[20px]">
                                    @foreach (['q' => $filters['search'], 'action' => $filters['action'], 'user' => $filters['user']] as $field => $fieldValue)
                                        @if ($fieldValue !== '')
                                            <input type="hidden" name="{{ $field }}" value="{{ $fieldValue }}">
                                        @endif
                                    @endforeach
                                    <x-support-select name="period" label="Fecha de movimientos" :value="$filters['period'] === 'day' ? '' : $filters['period']" :options="[['value' => 'week', 'label' => 'Por semana'], ['value' => 'year', 'label' => 'Por año']]" all-label="Por día" class="w-full" />
                                </form>
                                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="listFit; isCompact; $nextTick(() => update())" class="support-date-scroll support-flush relative min-h-0 flex-1">
                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                        @php
                                            $dateQuery = request()->except('page', 'date');
                                        @endphp
                                        @foreach ($dateOptions as $day)
                                            <a href="{{ route('soporte.reportes-tecnicos.movements', array_merge($dateQuery, ['date' => $day['date']])) }}" @if ($filters['date'] === $day['date']) aria-current="true" @endif class="support-date panel-border flex min-h-[90px] w-full flex-col justify-center border-b px-[20px] py-[20px] {{ $filters['date'] === $day['date'] ? 'is-selected' : '' }}">
                                                <span class="support-date-label block w-full truncate leading-none" title="{{ $day['label'] }}">{{ $day['label'] }}</span>
                                                <span class="mt-[15px] leading-none">{{ $day['count'] }} {{ $day['count'] === 1 ? 'movimiento' : 'movimientos' }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                    <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>
                            </aside>

                            <div class="flex min-h-0 min-w-0 flex-col">
                                <div class="panel-header panel-border flex shrink-0 items-center border-b p-[20px] min-[900px]:min-h-[85px]">
                                    <form method="GET" action="{{ route('soporte.reportes-tecnicos.movements') }}" class="flex w-full min-w-0 flex-col gap-[15px] sm:flex-row sm:flex-wrap sm:items-center sm:gap-[20px]">
                                        @if ($filters['date'] !== '')
                                            <input type="hidden" name="date" value="{{ $filters['date'] }}">
                                        @endif
                                        @if ($filters['period'] !== 'day')
                                            <input type="hidden" name="period" value="{{ $filters['period'] }}">
                                        @endif
                                        <x-support-folio-search name="q" :value="$filters['search']" :options="$folioOptions" placeholder="Buscar folio o usuario..." class="w-full sm:min-w-[220px] sm:flex-1" />
                                        <x-support-select name="action" :value="$filters['action']" :options="$actionOptions" all-label="Todos los movimientos" class="w-full sm:w-[220px]" />
                                        <x-support-select name="user" :value="$filters['user']" :options="$userOptions" all-label="Todos los usuarios" class="w-full sm:w-[220px]" />
                                        @if ($filters['search'] !== '' || $filters['action'] !== '' || $filters['user'] !== '')
                                            <a href="{{ route('soporte.reportes-tecnicos.movements', array_filter(['date' => $filters['date'], 'period' => $filters['period'] !== 'day' ? $filters['period'] : ''], fn ($value) => $value !== '')) }}" class="panel-action inline-flex h-11 items-center gap-[10px] leading-none focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                                <span>Limpiar</span>
                                            </a>
                                        @endif
                                    </form>
                                </div>

                                <div class="panel-header panel-border panel-accent hidden shrink-0 border-b leading-none min-[900px]:grid min-[900px]:grid-cols-[190px_220px_minmax(0,1fr)_120px]">
                                    <div class="panel-border border-r p-[20px]">Movimiento</div>
                                    <div class="panel-border border-r p-[20px]">Folio</div>
                                    <div class="panel-border border-r p-[20px]">Usuario</div>
                                    <div class="p-[20px] text-center">Hora</div>
                                </div>

                                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="listFit; isCompact; $nextTick(() => update())" class="relative min-h-[260px] flex-1 {{ $movements->hasPages() ? '' : 'support-flush' }}" :style="isCompact ? 'flex: none; height: min(560px, 70dvh)' : null">
                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                    @forelse ($movements as $movement)
                                        @php
                                            $isDeleted = in_array($movement->action, [\App\Models\TechnicalSupportReportMovement::ACTION_DELETED, \App\Models\TechnicalSupportReportMovement::ACTION_PURGED], true);
                                            $movementLabel = match ($movement->action) {
                                                \App\Models\TechnicalSupportReportMovement::ACTION_DELETED => 'Reporte eliminado',
                                                \App\Models\TechnicalSupportReportMovement::ACTION_RESTORED => 'Reporte restaurado',
                                                \App\Models\TechnicalSupportReportMovement::ACTION_PURGED => 'Borrado definitivo',
                                                default => 'Reporte generado',
                                            };
                                            $actorName = $movement->user_name ?: $movement->user?->name ?: 'Usuario no disponible';
                                            $actorEmail = $movement->user_email ?: $movement->user?->email;
                                        @endphp
                                        <article class="panel-border grid min-h-[77px] grid-cols-1 border-b min-[640px]:grid-cols-2 min-[900px]:grid-cols-[190px_220px_minmax(0,1fr)_120px]">
                                            <div class="panel-border flex min-w-0 items-center border-b p-[20px] min-[640px]:border-r min-[900px]:border-b-0">
                                                <span class="block min-w-0 truncate whitespace-nowrap leading-none {{ $isDeleted ? 'panel-tone-red' : 'panel-accent' }}" title="{{ $movementLabel }}">{{ $movementLabel }}</span>
                                            </div>
                                            <div class="panel-border flex min-w-0 items-center border-b p-[20px] min-[900px]:border-b-0 min-[900px]:border-r">
                                                <span class="panel-accent block min-w-0 truncate whitespace-nowrap leading-none tabular-nums" title="{{ $movement->folio }}">{{ $movement->folio }}</span>
                                            </div>
                                            <div class="panel-border flex min-w-0 flex-col justify-center border-b p-[20px] min-[640px]:border-b-0 min-[640px]:border-r">
                                                <p class="panel-accent truncate leading-none" title="{{ $actorName }}">{{ $actorName }}</p>
                                                @if ($actorEmail)
                                                    <p class="panel-muted mt-[10px] truncate lowercase leading-none" title="{{ $actorEmail }}">{{ $actorEmail }}</p>
                                                @endif
                                            </div>
                                            <div class="flex items-center justify-start p-[20px] leading-none tabular-nums min-[900px]:justify-center min-[900px]:text-center">{{ $movement->occurred_at->timezone(config('support.timezone', 'America/Mexico_City'))->format('H:i:s') }}</div>
                                        </article>
                                    @empty
                                        <div class="panel-muted grid h-full min-h-[260px] place-items-center p-[20px] text-center leading-tight">No hay movimientos que coincidan con los filtros.</div>
                                    @endforelse
                                    </div>
                                    <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>

                                @if ($movements->hasPages())
                                    <div class="support-pagination panel-surface panel-border relative shrink-0 border-t p-[20px]">{{ $movements->links() }}</div>
                                @endif
                            </div>
                        </div>
                    </section>
                    @endif
                </main>
            </div>
        </div>

        <!-- Control de pantalla completa (igual que en Panel general, Administración, Reloj Checador y Reporte de respaldos) -->
        <div class="no-print panel-surface panel-border fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] lg:flex">
            <button type="button" @click="toggleFullscreen($root)" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver Reportes de soporte en pantalla completa'">
                <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
                <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
            </button>
        </div>
    </div>
</x-app-layout>
