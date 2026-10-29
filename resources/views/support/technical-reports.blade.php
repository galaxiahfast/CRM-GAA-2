<x-app-layout>
    <style>
        .support-service-scroll {
            scrollbar-width: none;
            overscroll-behavior: contain;
        }

        .support-service-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .support-service-scroll-thumb {
            position: absolute;
            right: 0;
            top: 0;
            z-index: 20;
            width: 3px;
            min-height: 32px;
            border-radius: 9999px;
            background: #1A3A6B;
            pointer-events: none;
        }

        /* Todo el texto de la página sin negritas ni espaciado raro; solo azul fuerte resalta. */
        .support-report-page :is(h1, h2, h3, p, span, label, button, a, input, textarea, select) {
            font-weight: 400;
        }

        .support-report-page :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus,
        .support-report-page :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus-visible {
            border-color: #d4d4d8 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Un solo tamaño de letra en toda la página; solo el título principal es más grande. */
        .support-report-page :is(p, span, label, button, a, input, textarea, select, h2, h3) {
            font-size: 15px;
        }

        /* Los formularios emergentes salen fuera de la página: sin borde morado al enfocar. */
        [data-support-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus,
        [data-support-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus-visible {
            border-color: #d4d4d8 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .support-report-page input[type="checkbox"] {
            accent-color: #000000;
        }

        .support-report-page input[type="checkbox"]:focus,
        .support-report-page input[type="checkbox"]:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }

        /* Cada fila lleva su línea inferior, también la última. */
        .support-report-list > article {
            border-bottom: 1px solid #d4d4d8;
        }

        /* Tabla de servicios: casilla, servicio, personas y cantidad (en pantallas angostas las personas pasan a una segunda línea). */
        .support-service-grid {
            display: grid;
            grid-template-columns: 60px minmax(0, 1fr) 150px;
        }

        .support-service-grid > .support-people-cell {
            grid-column: 1 / -1;
            order: 5;
            border-top: 1px solid #d4d4d8;
        }

        .support-people-head {
            display: none;
        }

        .support-versions-grid {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 900px) {
            .support-service-grid {
                grid-template-columns: 60px minmax(0, 1fr) 340px 150px;
            }

            .support-service-grid > .support-people-cell {
                grid-column: auto;
                order: 0;
                border-top: 0;
                border-right: 1px solid #d4d4d8;
            }

            .support-people-head {
                display: block;
            }

            .support-versions-grid {
                grid-template-columns: 300px minmax(0, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .support-service-grid {
                grid-template-columns: 60px minmax(0, 1fr) 480px 170px;
            }
        }

        .support-quantity-input {
            appearance: textfield;
            -moz-appearance: textfield;
            text-align: center;
        }

        .support-quantity-input::-webkit-inner-spin-button,
        .support-quantity-input::-webkit-outer-spin-button {
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }

        /* Casilla de la lista de personas (claro). */
        .support-check {
            border-color: #d4d4d8;
            background-color: #ffffff;
            color: #ffffff;
        }

        .support-check-on {
            border-color: #000000;
            background-color: #000000;
        }

        /* ===== Tema oscuro del módulo de Soporte =====
           Paleta: superficie #18181b, encabezados #202023, líneas #3f3f46, texto #e4e4e7 y azul claro
           #6aa9ff para lo que en el tema claro va en azul fuerte. Cubre la página y también los
           desplegables y ventanas que se montan fuera de ella. */
        html.module-dark-theme {
            --support-accent: #6aa9ff;
            --support-check: #3b82f6;
            --support-border: #3f3f46;
            --support-surface: #18181b;
            --support-header: #202023;
        }

        html.module-dark-theme #main-content .support-report-page [class~="bg-white"],
        html.module-dark-theme [data-support-modal][class~="bg-white"],
        html.module-dark-theme [data-support-modal] [class~="bg-white"],
        html.module-dark-theme [data-support-dropdown][class~="bg-white"],
        html.module-dark-theme [data-support-dropdown] [class~="bg-white"] {
            background-color: var(--support-surface) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="bg-zinc-100"],
        html.module-dark-theme [data-support-modal][class~="bg-zinc-100"],
        html.module-dark-theme [data-support-modal] [class~="bg-zinc-100"],
        html.module-dark-theme [data-support-dropdown][class~="bg-zinc-100"],
        html.module-dark-theme [data-support-dropdown] [class~="bg-zinc-100"] {
            background-color: var(--support-header) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="bg-zinc-50"],
        html.module-dark-theme [data-support-modal][class~="bg-zinc-50"],
        html.module-dark-theme [data-support-modal] [class~="bg-zinc-50"],
        html.module-dark-theme [data-support-dropdown][class~="bg-zinc-50"],
        html.module-dark-theme [data-support-dropdown] [class~="bg-zinc-50"] {
            background-color: #1d1d21 !important;
        }

        html.module-dark-theme #main-content .support-report-page .support-row-selected,
        html.module-dark-theme [data-support-modal].support-row-selected,
        html.module-dark-theme [data-support-modal] .support-row-selected,
        html.module-dark-theme [data-support-dropdown].support-row-selected,
        html.module-dark-theme [data-support-dropdown] .support-row-selected {
            background-color: rgba(106, 169, 255, 0.1) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="hover:bg-zinc-50"]:hover,
        html.module-dark-theme [data-support-modal][class*="hover:bg-zinc-50"]:hover,
        html.module-dark-theme [data-support-modal] [class*="hover:bg-zinc-50"]:hover,
        html.module-dark-theme [data-support-dropdown][class*="hover:bg-zinc-50"]:hover,
        html.module-dark-theme [data-support-dropdown] [class*="hover:bg-zinc-50"]:hover {
            background-color: #26262b !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="hover:bg-zinc-100"]:hover,
        html.module-dark-theme [data-support-modal][class*="hover:bg-zinc-100"]:hover,
        html.module-dark-theme [data-support-modal] [class*="hover:bg-zinc-100"]:hover,
        html.module-dark-theme [data-support-dropdown][class*="hover:bg-zinc-100"]:hover,
        html.module-dark-theme [data-support-dropdown] [class*="hover:bg-zinc-100"]:hover {
            background-color: #26262b !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-modal][class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-modal] [class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-dropdown][class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-dropdown] [class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover) {
            background-color: transparent !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="hover:bg-zinc-100"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-modal][class*="hover:bg-zinc-100"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-modal] [class*="hover:bg-zinc-100"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-dropdown][class*="hover:bg-zinc-100"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-support-dropdown] [class*="hover:bg-zinc-100"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover) {
            background-color: transparent !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="bg-[#1A3A6B]"],
        html.module-dark-theme [data-support-modal][class~="bg-[#1A3A6B]"],
        html.module-dark-theme [data-support-modal] [class~="bg-[#1A3A6B]"],
        html.module-dark-theme [data-support-dropdown][class~="bg-[#1A3A6B]"],
        html.module-dark-theme [data-support-dropdown] [class~="bg-[#1A3A6B]"] {
            background-color: #1f4a8a !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="border-[#d4d4d8]"],
        html.module-dark-theme [data-support-modal][class*="border-[#d4d4d8]"],
        html.module-dark-theme [data-support-modal] [class*="border-[#d4d4d8]"],
        html.module-dark-theme [data-support-dropdown][class*="border-[#d4d4d8]"],
        html.module-dark-theme [data-support-dropdown] [class*="border-[#d4d4d8]"] {
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="text-black"],
        html.module-dark-theme [data-support-modal][class~="text-black"],
        html.module-dark-theme [data-support-modal] [class~="text-black"],
        html.module-dark-theme [data-support-dropdown][class~="text-black"],
        html.module-dark-theme [data-support-dropdown] [class~="text-black"] {
            color: #e4e4e7 !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="text-[#1A3A6B]"],
        html.module-dark-theme [data-support-modal][class~="text-[#1A3A6B]"],
        html.module-dark-theme [data-support-modal] [class~="text-[#1A3A6B]"],
        html.module-dark-theme [data-support-dropdown][class~="text-[#1A3A6B]"],
        html.module-dark-theme [data-support-dropdown] [class~="text-[#1A3A6B]"] {
            color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="hover:text-[#1A3A6B]"]:hover,
        html.module-dark-theme [data-support-modal][class*="hover:text-[#1A3A6B]"]:hover,
        html.module-dark-theme [data-support-modal] [class*="hover:text-[#1A3A6B]"]:hover,
        html.module-dark-theme [data-support-dropdown][class*="hover:text-[#1A3A6B]"]:hover,
        html.module-dark-theme [data-support-dropdown] [class*="hover:text-[#1A3A6B]"]:hover {
            color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover,
        html.module-dark-theme [data-support-modal][class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover,
        html.module-dark-theme [data-support-modal] [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover,
        html.module-dark-theme [data-support-dropdown][class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover,
        html.module-dark-theme [data-support-dropdown] [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover {
            color: #fafafa !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="text-red-600"],
        html.module-dark-theme [data-support-modal][class~="text-red-600"],
        html.module-dark-theme [data-support-modal] [class~="text-red-600"],
        html.module-dark-theme [data-support-dropdown][class~="text-red-600"],
        html.module-dark-theme [data-support-dropdown] [class~="text-red-600"] {
            color: #f87171 !important;
        }

        html.module-dark-theme #main-content .support-report-page [class*="hover:text-red-600"]:hover,
        html.module-dark-theme [data-support-modal][class*="hover:text-red-600"]:hover,
        html.module-dark-theme [data-support-modal] [class*="hover:text-red-600"]:hover,
        html.module-dark-theme [data-support-dropdown][class*="hover:text-red-600"]:hover,
        html.module-dark-theme [data-support-dropdown] [class*="hover:text-red-600"]:hover {
            color: #f87171 !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="text-zinc-500"],
        html.module-dark-theme [data-support-modal][class~="text-zinc-500"],
        html.module-dark-theme [data-support-modal] [class~="text-zinc-500"],
        html.module-dark-theme [data-support-dropdown][class~="text-zinc-500"],
        html.module-dark-theme [data-support-dropdown] [class~="text-zinc-500"] {
            color: #a1a1aa !important;
        }

        html.module-dark-theme #main-content .support-report-page :is(input:not([type="checkbox"]):not([type="radio"]), textarea),
        html.module-dark-theme [data-support-modal]:is(input:not([type="checkbox"]):not([type="radio"]), textarea),
        html.module-dark-theme [data-support-modal] :is(input:not([type="checkbox"]):not([type="radio"]), textarea),
        html.module-dark-theme [data-support-dropdown]:is(input:not([type="checkbox"]):not([type="radio"]), textarea),
        html.module-dark-theme [data-support-dropdown] :is(input:not([type="checkbox"]):not([type="radio"]), textarea) {
            border-color: var(--support-border) !important;
            background-color: var(--support-surface) !important;
            color: #fafafa !important;
        }

        html.module-dark-theme #main-content .support-report-page :is(input, textarea)::placeholder,
        html.module-dark-theme [data-support-modal]:is(input, textarea)::placeholder,
        html.module-dark-theme [data-support-modal] :is(input, textarea)::placeholder,
        html.module-dark-theme [data-support-dropdown]:is(input, textarea)::placeholder,
        html.module-dark-theme [data-support-dropdown] :is(input, textarea)::placeholder {
            color: #71717a !important;
        }

        html.module-dark-theme #main-content .support-report-page :is(input:not([type="checkbox"]):not([type="radio"]), textarea):focus,
        html.module-dark-theme [data-support-modal]:is(input:not([type="checkbox"]):not([type="radio"]), textarea):focus,
        html.module-dark-theme [data-support-modal] :is(input:not([type="checkbox"]):not([type="radio"]), textarea):focus,
        html.module-dark-theme [data-support-dropdown]:is(input:not([type="checkbox"]):not([type="radio"]), textarea):focus,
        html.module-dark-theme [data-support-dropdown] :is(input:not([type="checkbox"]):not([type="radio"]), textarea):focus {
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .support-report-page [class~="bg-black"],
        html.module-dark-theme [data-support-modal][class~="bg-black"],
        html.module-dark-theme [data-support-modal] [class~="bg-black"],
        html.module-dark-theme [data-support-dropdown][class~="bg-black"],
        html.module-dark-theme [data-support-dropdown] [class~="bg-black"] {
            border-color: #fafafa !important;
            background-color: #fafafa !important;
            color: #09090b !important;
        }

        html.module-dark-theme #main-content .support-report-page input[type="checkbox"]:checked {
            border-color: var(--support-check) !important;
            background-color: var(--support-check) !important;
        }

        html.module-dark-theme .support-service-grid > .support-people-cell {
            border-color: var(--support-border);
        }

        html.module-dark-theme .support-report-list > article {
            border-bottom-color: var(--support-border);
        }

        html.module-dark-theme .support-service-scroll-thumb {
            background: var(--support-accent);
        }

        html.module-dark-theme #main-content .support-report-page .support-row-selected:hover {
            background-color: rgba(106, 169, 255, 0.14) !important;
        }

        html.module-dark-theme .support-check {
            border-color: #52525b;
            background-color: var(--support-surface);
        }

        html.module-dark-theme .support-check-on {
            border-color: var(--support-check);
            background-color: var(--support-check);
            color: #ffffff;
        }

        .support-report-page:fullscreen {
            height: 100dvh;
        }
    </style>

    <div
        class="support-report-page h-[calc(100dvh-100px)] overflow-hidden bg-white text-[15px] text-black"
        x-data="{
            viewScale: Number(localStorage.getItem('support-reports-view-scale') || 90),
            isFullscreen: false,
            init() {
                if (this.viewScale < 70 || this.viewScale > 100) this.viewScale = 90;
            },
            saveScale() {
                localStorage.setItem('support-reports-view-scale', String(this.viewScale));
            },
            zoomOut() { this.viewScale = Math.max(70, this.viewScale - 5); this.saveScale() },
            zoomIn() { this.viewScale = Math.min(100, this.viewScale + 5); this.saveScale() },
            async toggleFullscreen() {
                if (document.fullscreenElement === this.$root) return document.exitFullscreen();
                if (document.fullscreenElement) await document.exitFullscreen();
                await this.$root.requestFullscreen();
            }
        }"
        @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
    >
        <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
            <button type="button" @click="zoomOut()" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">−</button>
            <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de Reportes de soporte">
            <button type="button" @click="zoomIn()" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">+</button>
            <span class="w-[42px] text-right tabular-nums text-black" x-text="viewScale + '%'">90%</span>
            <span class="h-5 w-px bg-zinc-200"></span>
            <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
                <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
                <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
            </button>
        </div>

        <div class="relative h-full w-full overflow-hidden">
        <main x-ref="scaledMain" class="flex w-full origin-top-left flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]" :style="`width:${10000 / viewScale}%;height:${10000 / viewScale}%;transform:scale(${viewScale / 100});`">
            <section class="flex shrink-0 flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-normal leading-none tracking-tight text-[#1A3A6B]">Reportes de soporte</h1>
                        <p class="mt-[10px] leading-none text-black">Genera y consulta los documentos de los servicios técnicos realizados.</p>
                    </div>
                </div>
            </section>

            <nav class="flex shrink-0 items-center gap-[20px]" aria-label="Secciones de reportes de soporte">
                <a href="{{ route('soporte.reportes-tecnicos') }}" class="flex items-center gap-[10px] py-[20px] leading-none transition focus:outline-none focus:ring-0 {{ $activeTab === 'generate' ? 'text-[#1A3A6B]' : 'text-black' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m-6 9 2 2 4-4"/></svg>
                    <span>Generar reporte</span>
                </a>
                <a href="{{ route('soporte.reportes-tecnicos.history') }}" class="flex items-center gap-[10px] py-[20px] leading-none transition focus:outline-none focus:ring-0 {{ $activeTab === 'history' ? 'text-[#1A3A6B]' : 'text-black' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2M4.9 4.9A10 10 0 1 1 2 12m0 0V6m0 6h6"/></svg>
                    <span>Historial</span>
                </a>
                <a href="{{ route('soporte.reportes-tecnicos.movements') }}" class="flex items-center gap-[10px] py-[20px] leading-none transition focus:outline-none focus:ring-0 {{ $activeTab === 'movements' ? 'text-[#1A3A6B]' : 'text-black' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10M18 16v5m0 0 2-2m-2 2-2-2"/></svg>
                    <span>Historial de movimientos</span>
                </a>
                <x-support-report-trash :reports="$trashedReports" class="ml-auto" />
            </nav>

            @if (session('status'))
                <div class="rounded-xl border border-[#d4d4d8] bg-white p-[20px] leading-none text-black" role="status">{{ session('status') }}</div>
            @endif

            @if ($activeTab === 'generate')
            <section
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white"
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

                    <div class="flex shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                        <div class="min-w-0">
                            <div class="flex items-center gap-[10px]">
                                <h2 class="leading-none text-[#1A3A6B]">Generar reporte</h2>
                                <span class="leading-none text-[#1A3A6B]">(<span x-text="selected.length">0</span>)</span>
                            </div>
                            <p class="mt-[10px] leading-none" :class="hasOverLimit() ? 'text-red-600' : 'text-black'" x-text="hasOverLimit() ? 'Hay servicios con más personas que su cantidad.' : (selected.length > 0 && ! hasPeople() ? 'Elige al menos una persona o cliente para generar el PDF.' : 'El folio y la fecha se asignarán automáticamente.')">El folio y la fecha se asignarán automáticamente.</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-0">
                            <button type="button" @click="summaryOpen = true" class="inline-flex items-center gap-[10px] bg-transparent py-[15px] pl-[20px] pr-[10px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                <span>Ver resumen</span>
                            </button>
                            <button type="submit" :disabled="! canSubmit()" class="inline-flex items-center gap-[10px] bg-transparent py-[15px] pl-[10px] pr-[20px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg>
                                <span>Generar PDF</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                        <div class="flex shrink-0 items-center gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px] min-[900px]:h-[85px]">
                            <label class="relative block min-w-0 flex-1">
                                <span class="sr-only">Buscar servicios de soporte</span>
                                <input x-model="query" type="search" placeholder="Buscar instalación, CONTPAQi, correo, Office..." class="h-[44px] w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] font-normal text-black placeholder:text-zinc-500 focus:outline-none focus:ring-0">
                            </label>
                            <span class="shrink-0 leading-none text-black"><span x-text="selected.length">0</span> de 8 seleccionados</span>
                        </div>

                        <div x-show="hasServerUpdate()" x-cloak class="support-versions-grid shrink-0 border-b border-[#d4d4d8] bg-white p-[20px]">
                            <div>
                                <h2 class="leading-none text-[#1A3A6B]">Versiones aplicadas</h2>
                                <p class="mt-[10px] leading-[20px] text-black">Solo aparece al seleccionar una actualización de servidor.</p>
                            </div>
                            <label class="block min-w-0">
                                <span class="sr-only">Todas las versiones utilizadas</span>
                                <textarea name="versions_applied" rows="3" placeholder="Una versión por línea:&#10;18.3.1&#10;19.0.1&#10;19.4.0" class="w-full resize-y rounded-xl border border-[#d4d4d8] bg-white px-[20px] py-[15px] text-black placeholder:text-zinc-500 focus:outline-none focus:ring-0">{{ old('versions_applied') }}</textarea>
                                @error('versions_applied')<span class="mt-[10px] block leading-none text-red-600">{{ $message }}</span>@enderror
                            </label>
                        </div>

                        <div class="support-service-grid shrink-0 border-b border-[#d4d4d8] bg-zinc-100 leading-none text-[#1A3A6B]">
                            <div class="border-r border-[#d4d4d8] p-[20px]"><span class="sr-only">Seleccionar</span></div>
                            <div class="border-r border-[#d4d4d8] p-[20px]">Servicio</div>
                            <div class="support-people-head border-r border-[#d4d4d8] p-[20px]">Personas o equipos atendidos</div>
                            <div class="p-[20px] text-center">Cantidad</div>
                        </div>

                        <div x-data="backupOverlayScroll()" x-effect="query; selected.length; hasServerUpdate(); $nextTick(() => update())" class="relative min-h-[260px] flex-1">
                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll h-full overflow-y-auto">
                                @error('supports')
                                    <p class="border-b border-[#d4d4d8] p-[20px] leading-none text-red-600">{{ $message }}</p>
                                @enderror
                                @error('supports.*')
                                    <p class="border-b border-[#d4d4d8] p-[20px] leading-none text-red-600">{{ $message }}</p>
                                @enderror
                                @foreach ($errors->get('support_targets') as $message)
                                    <p class="border-b border-[#d4d4d8] p-[20px] leading-none text-red-600">{{ $message }}</p>
                                @endforeach
                                @foreach ($errors->get('support_targets.*') as $messages)
                                    @foreach ($messages as $message)
                                        <p class="border-b border-[#d4d4d8] p-[20px] leading-none text-red-600">{{ $message }}</p>
                                    @endforeach
                                @endforeach

                                <template x-for="item in catalog" :key="item.key">
                                    <div x-show="matches(item)" style="min-height: 85px;" class="support-service-grid border-b border-[#d4d4d8] transition hover:bg-zinc-50" :class="selected.includes(item.key) ? 'bg-zinc-50 support-row-selected' : 'bg-white'">
                                        <label class="flex cursor-pointer items-center justify-center border-r border-[#d4d4d8] p-[20px]">
                                            <input type="checkbox" name="supports[]" :value="item.key" x-model="selected" @change="$nextTick(() => syncRow(item.key))" :disabled="selected.length >= 8 && !selected.includes(item.key)" :aria-label="item.title" class="support-service-checkbox h-4 w-4 shrink-0 rounded border-[#d4d4d8] text-black focus:outline-none focus:ring-0 focus:ring-offset-0 disabled:opacity-40">
                                        </label>

                                        <div class="flex min-w-0 flex-col justify-center border-r border-[#d4d4d8] p-[20px]">
                                            <span class="block leading-none text-[#1A3A6B]" x-text="item.title"></span>
                                            <span class="mt-[10px] block leading-[20px] text-black" x-text="item.description"></span>
                                        </div>

                                        <div class="support-people-cell flex min-w-0 items-center p-[20px]">
                                            <textarea hidden :name="`support_targets[${item.key}]`" x-model="targets[item.key]" :disabled="!selected.includes(item.key)"></textarea>
                                            <x-support-people-picker class="w-full" />
                                        </div>

                                        <div class="flex items-center justify-center p-[20px]">
                                            <div class="flex items-center justify-center gap-[10px] leading-none" :style="selected.includes(item.key) ? '' : 'opacity: 0.4;'">
                                                <span class="shrink-0" :class="overLimit(item.key) ? 'text-red-600' : 'text-[#1A3A6B]'" x-text="`${people(item.key).length} de`">0 de</span>
                                                <input type="number" min="1" max="999" :name="`support_quantities[${item.key}]`" x-model.number="quantities[item.key]" :disabled="!selected.includes(item.key)" :style="overLimit(item.key) ? 'border-color: #dc2626 !important;' : ''" aria-label="Cantidad" title="Cantidad: máximo de personas atendidas" class="support-quantity-input h-[44px] w-[56px] shrink-0 rounded-xl border border-[#d4d4d8] bg-white px-[10px] text-center text-black focus:outline-none focus:ring-0 focus:ring-offset-0 disabled:cursor-not-allowed">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>
                    </div>
                </form>

                {{-- Resumen del reporte: destinatario calculado y servicios elegidos, antes de generar el PDF. --}}
                <template x-teleport="body">
                    <div data-support-modal x-cloak x-show="summaryOpen" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="support-summary-title">
                        <div :style="{ transform: `scale(${(typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100})` }" @click.outside="summaryOpen = false" class="flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white text-[15px] text-black shadow-2xl" style="height: min(620px, calc(100dvh - 40px));">
                            <header class="flex shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-white p-[20px]">
                                <div class="flex min-w-0 items-center gap-[20px]">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6M9 8h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h1 id="support-summary-title" class="font-semibold leading-none text-black">Resumen del reporte</h1>
                                        <p class="mt-[10px] truncate leading-none text-black">Revisa el destinatario y los servicios antes de generar el PDF.</p>
                                    </div>
                                </div>
                                <button type="button" @click="summaryOpen = false" aria-label="Cerrar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 leading-none text-white focus:outline-none focus:ring-0">×</button>
                            </header>

                            <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="summaryOpen; selected.length; $nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll absolute inset-0 overflow-y-auto">
                                    <div class="border-b border-[#d4d4d8] p-[20px]">
                                        <span class="block leading-none text-black">Destinatario del reporte</span>
                                        <span x-show="recipient() !== ''" x-cloak class="mt-[10px] block leading-[20px] text-[#1A3A6B]" x-text="recipient()"></span>
                                        <span x-show="recipient() === ''" class="mt-[10px] block leading-[20px] text-black">Se llenará al elegir personas.</span>
                                        <span class="mt-[20px] block leading-[20px] text-black">Se toma del departamento de cada colaborador elegido o del nombre del cliente externo.</span>
                                    </div>

                                    <div class="flex items-center gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px] leading-none text-[#1A3A6B]">
                                        <span>Servicios seleccionados (<span x-text="selected.length">0</span>)</span>
                                    </div>

                                    <p x-show="selected.length === 0" class="p-[20px] leading-[20px] text-black">Aún no hay servicios seleccionados. Marca los de la tabla y aquí verás cuántos y para quién.</p>
                                    <template x-for="key in selected" :key="key">
                                        <div class="border-b border-[#d4d4d8] p-[20px]">
                                            <span class="block truncate leading-none text-[#1A3A6B]" x-text="itemFor(key).title"></span>
                                            <span class="mt-[10px] block truncate leading-none text-black" x-text="`Cantidad: ${quantities[key] || 1}`"></span>
                                            <span class="mt-[10px] block leading-[20px]" :class="overLimit(key) ? 'text-red-600' : 'text-black'" x-text="people(key).length ? (overLimit(key) ? `Personas: ${people(key).length} de ${maxPeople(key)} permitidas` : `Personas: ${people(key).join(', ')}`) : 'Personas: sin elegir'"></span>
                                        </div>
                                    </template>
                                </div>
                                <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>

                            <footer class="flex shrink-0 justify-end gap-[10px] border-t border-[#d4d4d8] bg-white p-[20px]">
                                <button type="button" @click="summaryOpen = false" class="h-[44px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] leading-none text-black focus:outline-none focus:ring-0">Cerrar</button>
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
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white"
            >
                <div class="flex shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                    <div>
                        <div class="flex items-center gap-[10px]">
                            <h2 class="leading-none text-[#1A3A6B]">Historial de reportes</h2>
                            <span class="leading-none text-[#1A3A6B]">({{ $reports->total() }})</span>
                        </div>
                        <p class="mt-[10px] leading-none text-black">Consulta, filtra o descarga los documentos generados.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-0">
                        <button type="button" @click="viewSelection()" :disabled="selectedReports.length === 0" class="inline-flex items-center gap-[10px] bg-transparent py-[15px] pl-[20px] pr-[10px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                            <span>Ver selección (<span x-text="selectedReports.length">0</span>)</span>
                        </button>
                        <form method="POST" action="{{ route('soporte.reportes-tecnicos.download-selection') }}">
                            @csrf
                            <template x-for="folio in selectedReports" :key="folio">
                                <input type="hidden" name="report_folios[]" :value="folio">
                            </template>
                            <button type="submit" :disabled="selectedReports.length === 0" class="inline-flex items-center gap-[10px] bg-transparent py-[15px] pl-[10px] pr-[20px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg>
                                <span>Descargar selección (<span x-text="selectedReports.length">0</span>)</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid min-h-0 flex-1 min-[900px]:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)]">
                    <aside class="flex h-[230px] min-h-0 flex-col overflow-hidden border-b border-[#d4d4d8] bg-white min-[900px]:h-auto min-[900px]:border-b-0 min-[900px]:border-r" aria-label="Fechas de reportes de soporte">
                        <div class="flex h-[85px] shrink-0 items-center justify-center border-b border-[#d4d4d8] bg-zinc-100 px-[20px] text-center text-[#1A3A6B]">
                            <span class="truncate">Fecha de soportes</span>
                        </div>
                        <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1">
                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll h-full overflow-y-auto">
                                @php
                                    $dateQuery = request()->except('page', 'date');
                                @endphp
                                @foreach ($dateOptions as $day)
                                    <a href="{{ route('soporte.reportes-tecnicos.history', array_merge($dateQuery, ['date' => $day['date']])) }}" class="flex min-h-[90px] w-full flex-col justify-center border-b border-[#d4d4d8] px-[20px] py-[12px] transition {{ $filters['date'] === $day['date'] ? 'bg-[#1A3A6B] text-white' : ($loop->odd ? 'bg-zinc-50 text-black hover:bg-zinc-100' : 'bg-white text-black hover:bg-zinc-50') }}">
                                        <span class="block w-full truncate leading-[20px]" title="{{ $day['label'] }}">{{ $day['label'] }}</span>
                                        <span class="mt-[6px] leading-none {{ $filters['date'] === $day['date'] ? 'text-white/70' : 'text-black' }}">{{ $day['count'] }} {{ $day['count'] === 1 ? 'reporte' : 'reportes' }}</span>
                                    </a>
                                @endforeach
                            </div>
                            <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>
                    </aside>

                    <div class="flex min-h-0 min-w-0 flex-col">
                        <div class="flex shrink-0 items-center border-b border-[#d4d4d8] bg-zinc-100 p-[20px] min-[900px]:h-[85px]">
                            <form method="GET" action="{{ route('soporte.reportes-tecnicos.history') }}" class="flex w-full min-w-0 flex-wrap items-center gap-[20px]">
                                @if ($filters['date'] !== '')
                                    <input type="hidden" name="date" value="{{ $filters['date'] }}">
                                @endif
                                <x-support-folio-search name="q" :value="$filters['search']" :options="$folioOptions" placeholder="Buscar folio o servicio..." class="min-w-[220px] flex-1" />
                                <x-support-select name="recipient" :value="$filters['recipient']" :options="$recipientOptions" all-label="Todos los departamentos" class="w-[220px]" />
                                <x-support-select name="target" :value="$filters['target']" :options="$targetOptions" all-label="Todas las personas" class="w-[220px]" />
                                @if ($filters['search'] !== '' || $filters['recipient'] !== '' || $filters['target'] !== '')
                                    <a href="{{ route('soporte.reportes-tecnicos.history', $filters['date'] !== '' ? ['date' => $filters['date']] : []) }}" class="inline-flex items-center gap-[10px] bg-transparent px-[20px] py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                        <span>Limpiar</span>
                                    </a>
                                @endif
                            </form>
                        </div>

                        <div class="flex shrink-0 items-center border-b border-[#d4d4d8] bg-white px-[20px] min-[900px]:hidden">
                            <label class="inline-flex cursor-pointer items-center gap-[10px] py-[20px] leading-none text-black">
                                <input type="checkbox" :checked="allPageSelected()" @change="togglePage()" class="h-4 w-4 rounded border-[#d4d4d8] text-black focus:outline-none focus:ring-0 focus:ring-offset-0">
                                <span>Seleccionar página</span>
                            </label>
                        </div>

                        <div class="hidden shrink-0 border-b border-[#d4d4d8] bg-zinc-100 leading-none text-[#1A3A6B] min-[900px]:grid min-[900px]:grid-cols-[50px_185px_minmax(0,1fr)_150px] lg:grid-cols-[60px_210px_minmax(0,1fr)_200px]">
                            <label class="flex cursor-pointer items-center justify-center border-r border-[#d4d4d8] p-[20px]" title="Seleccionar página">
                                <input type="checkbox" :checked="allPageSelected()" @change="togglePage()" class="h-4 w-4 shrink-0 rounded border-[#d4d4d8] text-black focus:outline-none focus:ring-0 focus:ring-offset-0" aria-label="Seleccionar página">
                            </label>
                            <div class="border-r border-[#d4d4d8] p-[20px] text-center">Folio y fecha</div>
                            <div class="border-r border-[#d4d4d8] p-[20px]">Servicios y destinatario</div>
                            <div class="p-[20px] text-center">Acciones</div>
                        </div>

                        <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-[260px] flex-1">
                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll support-report-list h-full overflow-y-auto">
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
                                    <article @click="if (!$event.target.closest('a, button, input, label')) toggleReport('{{ $report->folio }}')" class="grid min-h-[77px] cursor-pointer grid-cols-[60px_minmax(0,1fr)] bg-white transition hover:bg-zinc-50 min-[900px]:grid-cols-[50px_185px_minmax(0,1fr)_150px] lg:grid-cols-[60px_210px_minmax(0,1fr)_200px]">
                                        <div class="row-span-3 flex items-center justify-center border-r border-[#d4d4d8] p-[20px] min-[900px]:row-span-1">
                                            <input type="checkbox" value="{{ $report->folio }}" x-model="selectedReports" @click.stop class="h-4 w-4 shrink-0 rounded border-[#d4d4d8] text-black focus:outline-none focus:ring-0 focus:ring-offset-0" aria-label="Seleccionar {{ $report->folio }}">
                                        </div>
                                        <div class="col-start-2 flex min-w-0 flex-col justify-center border-b border-[#d4d4d8] p-[20px] text-center min-[900px]:col-start-auto min-[900px]:border-b-0 min-[900px]:border-r">
                                            <p class="leading-none text-[#1A3A6B]">{{ $report->folio }}</p>
                                            <p class="mt-[10px] leading-none text-black">{{ $report->issued_at->timezone(config('support.timezone', 'America/Mexico_City'))->translatedFormat('d M Y · H:i') }}</p>
                                        </div>
                                        <div class="col-start-2 flex min-w-0 flex-col justify-center border-b border-[#d4d4d8] p-[20px] min-[900px]:col-start-auto min-[900px]:border-b-0 min-[900px]:border-r">
                                            <p class="truncate leading-none text-[#1A3A6B]" title="{{ $supportSummary }}">{{ $supportSummary }}</p>
                                            <p class="mt-[10px] truncate leading-none text-black" title="Departamento o cliente: {{ $report->service_recipient }}">Departamento o cliente: {{ $report->service_recipient }}</p>
                                            @if ($targetSummary !== '')
                                                <p class="mt-[10px] truncate leading-none text-black" title="Personas o equipos: {{ $targetSummary }}">Personas o equipos: {{ $targetSummary }}</p>
                                            @endif
                                        </div>
                                        <div class="col-start-2 flex items-center justify-center gap-[10px] p-[20px] min-[900px]:col-start-auto min-[900px]:gap-[5px] min-[900px]:px-[10px] lg:gap-[10px] lg:p-[20px]">
                                            <a @click.stop href="{{ route('soporte.reportes-tecnicos.pdf', $report) }}" target="_blank" rel="noopener" class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Ver {{ $report->folio }}" title="Ver"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg></a>
                                            <a @click.stop href="{{ route('soporte.reportes-tecnicos.pdf', ['report' => $report, 'download' => 1]) }}" class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Descargar {{ $report->folio }}" title="Descargar"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg></a>
                                            @if (auth()->user()?->isAdmin() || (int) $report->created_by === (int) auth()->id())
                                                <button type="button" @click.stop="deleteFolioText = ''; deleteWordText = ''; confirmDelete = { folio: @js($report->folio), url: @js(route('soporte.reportes-tecnicos.destroy', $report)) }" class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-red-600 focus:outline-none focus:ring-0" aria-label="Eliminar {{ $report->folio }}" title="Eliminar"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/></svg></button>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="grid h-full min-h-[260px] place-items-center p-[20px] text-center leading-none text-black">No hay reportes que coincidan con los filtros.</div>
                                @endforelse
                            </div>
                            <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>

                        @if ($reports->hasPages())
                            <div class="shrink-0 border-t border-[#d4d4d8] p-[20px]">{{ $reports->links() }}</div>
                        @endif
                    </div>
                </div>

                <template x-teleport="body">
                    <div data-support-modal x-cloak x-show="confirmDelete" @keydown.escape.window="confirmDelete = null" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="support-delete-title">
                        <div :style="{ transform: `scale(${(typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100})` }" @click.outside="confirmDelete = null" class="flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white text-[15px] text-black shadow-2xl">
                            <header class="flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-white p-[20px]">
                                <div class="flex min-w-0 items-center gap-[20px]">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h1 id="support-delete-title" class="font-semibold leading-none text-black">Eliminar reporte</h1>
                                        <p class="mt-[10px] leading-none text-black">Se enviará a la papelera de reciclaje.</p>
                                    </div>
                                </div>
                                <button type="button" @click="confirmDelete = null" aria-label="Cerrar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 leading-none text-white focus:outline-none focus:ring-0">×</button>
                            </header>

                            <form method="POST" :action="confirmDelete?.url" @submit="if (! canDelete()) $event.preventDefault()">
                                @csrf
                                @method('DELETE')

                                <div class="p-[20px]">
                                    <section class="border-b border-[#d4d4d8] pb-[20px]">
                                        <h2 class="leading-none text-black">Reporte seleccionado</h2>
                                        <p class="mt-[20px] leading-none text-[#1A3A6B]" x-text="confirmDelete?.folio"></p>
                                    </section>

                                    <section class="pt-[20px]">
                                        <h2 class="leading-none text-black">Verificación</h2>

                                        <label class="mt-[20px] block">
                                            <span class="block leading-none text-black">Escribe el folio exacto del reporte</span>
                                            <input type="text" x-model="deleteFolioText" autocomplete="new-password" onpaste="return false" class="mt-[20px] h-[44px] w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] font-normal uppercase text-black placeholder:text-zinc-500 focus:outline-none focus:ring-0" placeholder="Folio del reporte...">
                                        </label>

                                        <label class="mt-[20px] block">
                                            <span class="block leading-none text-black">Escribe ELIMINAR</span>
                                            <input type="text" x-model="deleteWordText" autocomplete="new-password" onpaste="return false" class="mt-[20px] h-[44px] w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] font-normal uppercase text-black placeholder:text-zinc-500 focus:outline-none focus:ring-0" placeholder="ELIMINAR">
                                        </label>
                                    </section>
                                </div>

                                <div class="flex justify-end gap-[10px] border-t border-[#d4d4d8] bg-white p-[20px]">
                                    <button type="button" @click="confirmDelete = null" class="h-[44px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] leading-none text-black focus:outline-none focus:ring-0">Cancelar</button>
                                    <button type="submit" :disabled="! canDelete()" class="h-[44px] rounded-xl bg-black px-[20px] leading-none text-white focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">Eliminar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </section>
            @else
            <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                <div class="flex shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                    <div>
                        <div class="flex items-center gap-[10px]">
                            <h2 class="leading-none text-[#1A3A6B]">Historial de movimientos</h2>
                            <span class="leading-none text-[#1A3A6B]">({{ $movements->total() }})</span>
                        </div>
                        <p class="mt-[10px] leading-none text-black">Registro permanente de reportes generados y eliminados.</p>
                    </div>
                </div>

                <div class="grid min-h-0 flex-1 min-[900px]:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)]">
                    <aside class="flex h-[230px] min-h-0 flex-col overflow-hidden border-b border-[#d4d4d8] bg-white min-[900px]:h-auto min-[900px]:border-b-0 min-[900px]:border-r" aria-label="Fechas de movimientos de reportes de soporte">
                        <div class="flex h-[85px] shrink-0 items-center justify-center border-b border-[#d4d4d8] bg-zinc-100 px-[20px] text-center text-[#1A3A6B]">
                            <span class="truncate">Fecha de movimientos</span>
                        </div>
                        <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1">
                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll h-full overflow-y-auto">
                                @php
                                    $dateQuery = request()->except('page', 'date');
                                @endphp
                                @foreach ($dateOptions as $day)
                                    <a href="{{ route('soporte.reportes-tecnicos.movements', array_merge($dateQuery, ['date' => $day['date']])) }}" class="flex min-h-[90px] w-full flex-col justify-center border-b border-[#d4d4d8] px-[20px] py-[12px] transition {{ $filters['date'] === $day['date'] ? 'bg-[#1A3A6B] text-white' : ($loop->odd ? 'bg-zinc-50 text-black hover:bg-zinc-100' : 'bg-white text-black hover:bg-zinc-50') }}">
                                        <span class="block w-full truncate leading-[20px]" title="{{ $day['label'] }}">{{ $day['label'] }}</span>
                                        <span class="mt-[6px] leading-none {{ $filters['date'] === $day['date'] ? 'text-white/70' : 'text-black' }}">{{ $day['count'] }} {{ $day['count'] === 1 ? 'movimiento' : 'movimientos' }}</span>
                                    </a>
                                @endforeach
                            </div>
                            <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>
                    </aside>

                    <div class="flex min-h-0 min-w-0 flex-col">
                        <div class="flex shrink-0 items-center border-b border-[#d4d4d8] bg-zinc-100 p-[20px] min-[900px]:h-[85px]">
                            <form method="GET" action="{{ route('soporte.reportes-tecnicos.movements') }}" class="flex w-full min-w-0 flex-wrap items-center gap-[20px]">
                                @if ($filters['date'] !== '')
                                    <input type="hidden" name="date" value="{{ $filters['date'] }}">
                                @endif
                                <x-support-folio-search name="q" :value="$filters['search']" :options="$folioOptions" placeholder="Buscar folio o usuario..." class="min-w-[220px] flex-1" />
                                <x-support-select name="action" :value="$filters['action']" :options="$actionOptions" all-label="Todos los movimientos" class="w-[220px]" />
                                <x-support-select name="user" :value="$filters['user']" :options="$userOptions" all-label="Todos los usuarios" class="w-[220px]" />
                                @if ($filters['search'] !== '' || $filters['action'] !== '' || $filters['user'] !== '')
                                    <a href="{{ route('soporte.reportes-tecnicos.movements', $filters['date'] !== '' ? ['date' => $filters['date']] : []) }}" class="inline-flex items-center gap-[10px] bg-transparent px-[20px] py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                        <span>Limpiar</span>
                                    </a>
                                @endif
                            </form>
                        </div>

                        <div class="hidden shrink-0 border-b border-[#d4d4d8] bg-zinc-100 leading-none text-[#1A3A6B] min-[900px]:grid min-[900px]:grid-cols-[190px_190px_minmax(0,1fr)_120px]">
                            <div class="border-r border-[#d4d4d8] p-[20px]">Movimiento</div>
                            <div class="border-r border-[#d4d4d8] p-[20px]">Folio</div>
                            <div class="border-r border-[#d4d4d8] p-[20px]">Usuario</div>
                            <div class="p-[20px] text-center">Hora</div>
                        </div>

                        <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-[260px] flex-1">
                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll support-report-list h-full overflow-y-auto">
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
                                    <article class="grid min-h-[77px] grid-cols-1 bg-white min-[900px]:grid-cols-[190px_190px_minmax(0,1fr)_120px]">
                                        <div class="flex min-w-0 items-center border-b border-[#d4d4d8] p-[20px] min-[900px]:border-b-0 min-[900px]:border-r">
                                            <span class="block min-w-0 truncate whitespace-nowrap leading-none {{ $isDeleted ? 'text-red-600' : 'text-[#1A3A6B]' }}" title="{{ $movementLabel }}">{{ $movementLabel }}</span>
                                        </div>
                                        <div class="flex min-w-0 items-center border-b border-[#d4d4d8] p-[20px] min-[900px]:border-b-0 min-[900px]:border-r">
                                            <span class="block min-w-0 truncate whitespace-nowrap leading-none tabular-nums text-[#1A3A6B]" title="{{ $movement->folio }}">{{ $movement->folio }}</span>
                                        </div>
                                        <div class="flex min-w-0 flex-col justify-center border-b border-[#d4d4d8] p-[20px] min-[900px]:border-b-0 min-[900px]:border-r">
                                            <p class="truncate leading-none text-[#1A3A6B]" title="{{ $actorName }}">{{ $actorName }}</p>
                                            @if ($actorEmail)
                                                <p class="mt-[10px] truncate lowercase leading-none text-black" title="{{ $actorEmail }}">{{ $actorEmail }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center justify-center p-[20px] text-center leading-none tabular-nums text-black">{{ $movement->occurred_at->timezone(config('support.timezone', 'America/Mexico_City'))->format('H:i:s') }}</div>
                                    </article>
                                @empty
                                    <div class="grid h-full min-h-[260px] place-items-center p-[20px] text-center leading-none text-black">No hay movimientos que coincidan con los filtros.</div>
                                @endforelse
                            </div>
                            <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                        </div>

                        @if ($movements->hasPages())
                            <div class="shrink-0 border-t border-[#d4d4d8] p-[20px]">{{ $movements->links() }}</div>
                        @endif
                    </div>
                </div>
            </section>
            @endif
        </main>
        </div>
    </div>
</x-app-layout>
