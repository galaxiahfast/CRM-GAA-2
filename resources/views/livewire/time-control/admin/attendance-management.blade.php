{{-- Reloj Checador (administración): mismo diseño que Panel general, Administración y Reportes de soporte
     (tarjetas con cabecera gris, líneas de 1px, texto de 15px, separaciones de 20px, escala de vista al 90%
     ajustable y tema oscuro con fondo negro). Solo cambia la presentación; la lógica vive en
     App\Livewire\TimeControl\Admin\AttendanceManagement. --}}
<div
    data-panel
    data-attendance-admin
    class="relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 overflow-hidden text-[15px]"
    x-data="{
        viewScale: 90,
        isFullscreen: false,
        isCompact: window.innerWidth / window.appAutoScale() < 1024,
        scaledHeight: null,
        observer: null,
        onFullscreen: null,
        reportSearch: '',
        reportSearchOpened: false,
        resultUserSearch: '',
        voiceListening: false,
        voiceRecognition: null,
        selectedIds: @js(array_values(array_map('intval', $selectedReportUserIds))),
        allUserIds: @js($reportUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()),

        syncSelection() {
            $wire.set('selectedReportUserIds', [...this.selectedIds], false);
        },
        selectAllUsers() {
            this.selectedIds = [...this.allUserIds];
        },
        clearAllUsers() {
            this.selectedIds = [];
        },
        areAllAreaUsersSelected(areaUserIds) {
            return areaUserIds.length > 0 && areaUserIds.every(id => this.selectedIds.includes(Number(id)));
        },
        toggleAreaUsers(areaUserIds) {
            const ids = areaUserIds.map(Number);
            if (this.areAllAreaUsersSelected(ids)) {
                this.selectedIds = this.selectedIds.filter(id => !ids.includes(Number(id)));
                return;
            }
            this.selectedIds = [...new Set([...this.selectedIds.map(Number), ...ids])];
        },

        datePicker(model) {
            return {
                open: false,
                value: model,
                cursor: new Date(),
                init() { this.setCursorFromValue(); },
                setCursorFromValue() {
                    if (!this.value) return;
                    const [year, month] = String(this.value).split('-').map(Number);
                    if (year && month) this.cursor = new Date(year, month - 1, 1);
                },
                get formattedValue() {
                    if (!this.value) return 'Selecciona una fecha';
                    const [year, month, day] = String(this.value).split('-').map(Number);
                    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(year, month - 1, day));
                },
                get monthLabel() {
                    const label = new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' }).format(this.cursor);
                    return label.charAt(0).toUpperCase() + label.slice(1);
                },
                get days() {
                    const first = new Date(this.cursor.getFullYear(), this.cursor.getMonth(), 1);
                    const mondayOffset = (first.getDay() + 6) % 7;
                    const start = new Date(first);
                    start.setDate(first.getDate() - mondayOffset);
                    return Array.from({ length: 42 }, (_, index) => {
                        const date = new Date(start);
                        date.setDate(start.getDate() + index);
                        return { date, currentMonth: date.getMonth() === this.cursor.getMonth() };
                    });
                },
                previousMonth() { this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() - 1, 1); },
                nextMonth() { this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + 1, 1); },
                selectDate(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    this.value = `${year}-${month}-${day}`;
                    this.cursor = new Date(year, date.getMonth(), 1);
                    this.open = false;
                },
                isSelected(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return this.value === `${year}-${month}-${day}`;
                },
                isToday(date) { return date.toDateString() === new Date().toDateString(); }
            };
        },

        timePicker(model, pickerId) {
            return {
                open: false,
                pickerId,
                value: model,
                hour: '12',
                minute: '00',
                second: '00',
                period: 'a. m.',
                popoverStyle: '',
                init() { this.syncFromValue(); },
                syncFromValue() {
                    const parts = String(this.value || '00:00:00').split(':').map(Number);
                    const hour24 = Number.isFinite(parts[0]) ? parts[0] : 0;
                    this.hour = String((hour24 % 12) || 12).padStart(2, '0');
                    this.minute = String(Number.isFinite(parts[1]) ? parts[1] : 0).padStart(2, '0');
                    this.second = String(Number.isFinite(parts[2]) ? parts[2] : 0).padStart(2, '0');
                    this.period = hour24 >= 12 ? 'p. m.' : 'a. m.';
                },
                normalize(value, min, max) {
                    const number = Math.min(max, Math.max(min, Number.parseInt(String(value).replace(/\D/g, ''), 10) || min));
                    return String(number).padStart(2, '0');
                },
                apply() {
                    this.hour = this.normalize(this.hour, 1, 12);
                    this.minute = this.normalize(this.minute, 0, 59);
                    this.second = this.normalize(this.second, 0, 59);
                    let hour24 = Number(this.hour) % 12;
                    if (this.period === 'p. m.') hour24 += 12;
                    this.value = `${String(hour24).padStart(2, '0')}:${this.minute}:${this.second}`;
                    this.open = false;
                },
                toggle(element) {
                    this.syncFromValue();
                    if (this.open) {
                        this.open = false;
                        return;
                    }
                    const rect = element.getBoundingClientRect();
                    const width = Math.min(360, window.innerWidth - 32);
                    const left = Math.max(16, Math.min(rect.left, window.innerWidth - width - 16));
                    const bottom = Math.max(16, window.innerHeight - rect.top + 10);
                    this.popoverStyle = `left:${left}px;bottom:${bottom}px;width:${width}px`;
                    this.open = true;
                },
                get displayValue() {
                    return `${this.hour}:${this.minute}:${this.second} ${this.period}`;
                }
            };
        },


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
            try { localStorage.setItem('admin-attendance-view-scale', String(this.viewScale)); } catch (error) {}
            this.$nextTick(() => this.syncHeight());
        },
        async toggleFullscreen() {
            if (document.fullscreenElement === this.$root) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await this.$root.requestFullscreen();
        },
        startVoiceSearch() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return;
            if (this.voiceListening && this.voiceRecognition) {
                this.voiceRecognition.stop();
                return;
            }
            const recognition = new SpeechRecognition();
            this.voiceRecognition = recognition;
            recognition.lang = 'es-MX';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            recognition.onstart = () => this.voiceListening = true;
            recognition.onresult = event => this.reportSearch = event.results[0][0].transcript.trim();
            recognition.onerror = () => this.voiceListening = false;
            recognition.onend = () => {
                this.voiceListening = false;
                this.voiceRecognition = null;
            };
            recognition.start();
        }
     }"
    @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024; syncHeight()"
>
    <x-panel-styles />

    <style>
        /* Desplazamiento fino con el color de acento del panel */
        .attendance-scrollbar,
        .attendance-results-body-scroll {
            scrollbar-width: thin;
            scrollbar-color: var(--panel-accent) transparent;
            overscroll-behavior: contain;
        }
        .attendance-scrollbar::-webkit-scrollbar,
        .attendance-results-body-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .attendance-scrollbar::-webkit-scrollbar-track,
        .attendance-results-body-scroll::-webkit-scrollbar-track { background: transparent; }
        .attendance-scrollbar::-webkit-scrollbar-thumb,
        .attendance-results-body-scroll::-webkit-scrollbar-thumb { background: var(--panel-accent); border-radius: 9999px; }

        /* Tabla de jornadas: columnas fijas con desplazamiento horizontal sincronizado */
        .attendance-results-row {
            display: grid;
            width: 1740px;
            grid-template-columns: 130px 150px 340px 190px 160px 150px 130px 130px 160px 200px;
        }
        .attendance-results-cell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            padding: 0 15px;
            text-align: center;
        }
        .attendance-results-cell:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 1px;
            height: 20px;
            background: var(--panel-border);
            transform: translateY(-50%);
        }
        .attendance-row:hover { background-color: var(--panel-subtle); }
        .attendance-row-modified,
        .attendance-row-modified:hover {
            background-color: color-mix(in srgb, var(--panel-accent) 8%, transparent);
            box-shadow: inset 4px 0 0 var(--panel-accent);
        }
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
        .attendance-dot {
            display: inline-block;
            height: 10px;
            width: 10px;
            flex-shrink: 0;
            border-radius: 9999px;
            background-color: currentColor;
        }
        /* Casillas: la palomita la dibuja el icono de cada tarjeta; el plugin de formularios no debe rellenar el cuadro */
        [data-attendance-admin] input[type="checkbox"].panel-field:checked {
            background-color: var(--panel-surface);
            background-image: none;
            border-color: var(--panel-border);
        }
        #attendance-edit-modal input[type="number"] { appearance: textfield; -moz-appearance: textfield; }
        #attendance-edit-modal input[type="number"]::-webkit-inner-spin-button,
        #attendance-edit-modal input[type="number"]::-webkit-outer-spin-button { margin: 0; -webkit-appearance: none; }

        /* Respuesta al pasar el cursor y al seleccionar */
        [data-panel] button.panel-field { cursor: pointer; transition: background-color 150ms ease; }
        [data-panel] button.panel-field:hover { background-color: var(--panel-hover); }
        .attendance-pick { transition: background-color 150ms ease; }
        .attendance-pick:hover { background-color: var(--panel-hover); }
        .attendance-pick:has(input:checked) { background-color: color-mix(in srgb, var(--panel-accent) 8%, transparent); }
        .attendance-pick:has(input:checked):hover { background-color: color-mix(in srgb, var(--panel-accent) 14%, transparent); }

        /* Botón principal: en tema oscuro usa el azul de marca en lugar de blanco */
        html.module-dark-theme [data-panel] .panel-button { background-color: #1A3A6B; color: #ffffff; }
        html.module-dark-theme [data-panel] .panel-button:hover { background-color: #234b88; }
        [data-panel] .panel-button:disabled { cursor: not-allowed; opacity: .6; }

        /* Celular y tablet: cada jornada se muestra como una tarjeta, sin desplazamiento horizontal */
        @media (max-width: 1023px) {
            .attendance-table-head,
            .attendance-table-hscroll { display: none !important; }
            .attendance-table-body { height: auto !important; overflow: visible !important; }
            .attendance-table-inner { width: auto !important; min-height: 0 !important; }
            .attendance-table-empty { height: auto !important; padding-top: 30px; padding-bottom: 30px; }
            .attendance-results-row {
                width: auto;
                height: auto !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 15px 20px;
                padding: 20px;
            }
            .attendance-results-cell { flex-direction: column; align-items: flex-start; justify-content: flex-start; padding: 0; text-align: left; }
            .attendance-results-cell::after { display: none; }
            .attendance-results-cell:empty { display: none; }
            .attendance-results-cell[data-label]::before { content: attr(data-label); display: block; margin-bottom: 5px; color: var(--panel-muted); }
            .attendance-results-row > :nth-child(1) { order: 10; grid-column: 1 / -1; }
            .attendance-results-row > :nth-child(1)::before { display: none !important; }
            .attendance-results-row > :nth-child(2) { order: 1; }
            .attendance-results-row > :nth-child(10) { order: 2; }
            .attendance-results-row > :nth-child(3) { order: 3; grid-column: 1 / -1; }
            .attendance-results-row > :nth-child(4) { order: 4; }
            .attendance-results-row > :nth-child(5) { order: 5; }
            .attendance-results-row > :nth-child(6) { order: 6; }
            .attendance-results-row > :nth-child(7) { order: 7; }
            .attendance-results-row > :nth-child(8) { order: 8; }
            .attendance-results-row > :nth-child(9) { order: 9; }
            .attendance-cell-marks > div { flex-wrap: wrap !important; justify-content: flex-start !important; gap: 5px 10px !important; }
            .attendance-table-total .attendance-cell-total-title { grid-column: 1 / -1; }
            .attendance-results-cell .panel-icon-button { height: 40px; width: 40px; }
        }
        @media (max-width: 639px) {
            .attendance-export-bar > button { justify-content: flex-start; }
            .attendance-pick label { gap: 10px; padding-left: 15px; padding-right: 0; }
            .attendance-pick .truncate,
            [data-attendance-admin] .panel-header .truncate { overflow: visible; text-overflow: clip; white-space: normal; }
        }
    </style>

    <div class="w-full" :style="(!isCompact && scaledHeight) ? 'height:' + scaledHeight + 'px' : null">
        <div x-ref="scaled" class="origin-top-left" :style="isCompact ? 'width:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
            <div class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">

                @if (session()->has('message'))
                    <div class="panel-surface panel-tone-green rounded-xl border p-[20px]" style="border-color: currentColor">
                        {{ session('message') }}
                    </div>
                @endif

                @if (session()->has('error') || $errors->any())
                    @teleport('body')
                        <div
                            data-panel
                            wire:key="attendance-error-toast-{{ $errorToastVersion }}-{{ md5((string) (session('error') ?: $errors->first())) }}"
                            x-data="{ visible: true }"
                            x-init="setTimeout(() => visible = false, 4500)"
                            x-show="visible"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-3"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-3"
                            class="panel-surface fixed left-1/2 top-[20px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-center gap-[15px] rounded-xl border px-[20px] py-[15px] shadow-[0_14px_40px_rgba(0,0,0,0.20)]"
                            style="border-color: var(--panel-danger-line); color: var(--panel-danger)"
                            role="alert"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                            <span class="min-w-0 flex-1">{{ session('error') ?: $errors->first() }}</span>
                            <button type="button" @click="visible = false" aria-label="Cerrar notificación" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endteleport
                @endif

                {{-- Migas de pan y botones antiguos (ocultos; se conservan por compatibilidad) --}}
                <div class="admin-attendance-topbar hidden">
                    <button id="attendance-header-download" type="button" wire:click="export('pdf')" wire:loading.attr="disabled" @disabled(! $searched)>Descargar PDF</button>
                    <button type="button" disabled aria-disabled="true">Imprimir</button>
                </div>

                <!-- Cabecera y secciones -->
                <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                    <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                        <span class="panel-surface panel-border panel-text grid h-14 w-14 shrink-0 place-items-center rounded-xl border">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Reloj Checador</h1>
                            <p class="mt-[10px] leading-tight">Administración de marcas biométricas, ajustes por día y exportación.</p>
                        </div>
                    </div>

                    <nav class="flex flex-wrap items-center gap-[20px]" aria-label="Secciones del informe">
                        <button type="button" wire:click="showPreparationSection"
                                aria-selected="{{ $reportSection === 'prepare' ? 'true' : 'false' }}" @if ($reportSection === 'prepare') aria-current="page" @endif
                                class="panel-tab inline-flex items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg>
                            Generar informe
                        </button>
                        <button type="button" @click="syncSelection(); $wire.showResultsSection()"
                                aria-selected="{{ $reportSection === 'results' ? 'true' : 'false' }}" @if ($reportSection === 'results') aria-current="page" @endif
                                class="panel-tab inline-flex items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM4 10h16M9 10v9"/></svg>
                            Ver resultados
                        </button>
                        <button type="button" wire:click="showHistorySection"
                                aria-selected="{{ $reportSection === 'history' ? 'true' : 'false' }}" @if ($reportSection === 'history') aria-current="page" @endif
                                class="panel-tab inline-flex items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                            Historial de cambios
                        </button>
                    </nav>
                </section>

                {{-- Informes individual, grupal y general --}}
                @if ($reportSection === 'prepare')
                    <section class="panel-surface panel-border relative z-20 rounded-xl border">
                        <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-[20px] rounded-t-xl border-b p-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent leading-none">Preparar informe</h2>
                                <p class="mt-[10px] leading-tight">Configura y genera los resultados fácilmente.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span><span x-text="selectedIds.length">{{ count($selectedReportUserIds) }}</span> seleccionados</span></span>
                                <span class="inline-flex items-center gap-[10px] whitespace-nowrap">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                    {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
                                </span>
                                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M9 14h.01M9 18h.01M15 10h.01M15 14h.01M15 18h.01"/></svg>{{ $selectedAreaCount }} {{ $selectedAreaCount === 1 ? 'área participa' : 'áreas participan' }}</span>
                            </div>
                        </header>

                        <div class="panel-border flex flex-wrap items-center gap-[20px] border-b p-[20px]">
                            <div class="relative min-w-[260px] flex-1">
                                <svg class="panel-muted pointer-events-none absolute left-[15px] top-1/2 h-[18px] w-[18px] -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                                <input type="search" x-model="reportSearch" autocomplete="off"
                                       @focus="if (!reportSearchOpened) { reportSearch = ''; reportSearchOpened = true }"
                                       placeholder="Buscar colaborador, ID o área..."
                                       aria-label="Buscar colaborador, ID o área"
                                       class="panel-field h-11 w-full rounded-xl border pl-[44px] pr-[55px] focus:ring-0">
                                <button type="button" @click="startVoiceSearch()" :title="voiceListening ? 'Detener búsqueda por voz' : 'Buscar por voz'" aria-label="Buscar por voz"
                                        class="panel-icon-button absolute right-[5px] top-1/2 inline-flex h-[34px] w-[34px] -translate-y-1/2 items-center justify-center rounded-lg focus:outline-none focus:ring-0"
                                        :class="voiceListening ? 'panel-accent animate-pulse' : ''">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Zm-7 9v1a7 7 0 0 0 14 0v-1M12 19v3m-4 0h8"/></svg>
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-[20px] gap-y-[10px] whitespace-nowrap">
                                <button type="button" @click="selectAllUsers()" class="panel-action inline-flex items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>Seleccionar todos</button>
                                <button type="button" @click="clearAllUsers()" class="panel-action-danger inline-flex items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>Deseleccionar todos</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-[300px_minmax(0,1fr)]">
                            <form @submit.prevent="syncSelection(); $wire.generateSelectionReport()" class="panel-border flex flex-col gap-[20px] border-b p-[20px] lg:border-b-0 lg:border-r">
                                <div>
                                    <h3 class="panel-accent leading-none">Periodo del informe</h3>
                                    <p class="mt-[10px] leading-tight">Define las fechas que deseas.</p>
                                </div>
                                @foreach (['from' => 'Desde', 'to' => 'Hasta'] as $dateField => $dateLabel)
                                    <div class="relative grid gap-[10px]" x-data="datePicker($wire.entangle('{{ $dateField }}'))" @click.outside="open = false">
                                        <span>{{ $dateLabel }}</span>
                                        <button type="button" @click="open = !open; if (open) setCursorFromValue()" class="panel-field flex h-11 w-full items-center justify-between gap-[10px] rounded-xl border px-[15px] outline-none focus:ring-0" :aria-expanded="open">
                                            <span x-text="formattedValue"></span>
                                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                        </button>
                                        {{-- En pantallas grandes el calendario se abre al lado del formulario para que ninguna fila quede tapada --}}
                                        <div x-cloak x-show="open" x-transition class="panel-surface panel-border absolute left-0 top-full z-[80] mt-[10px] w-[300px] rounded-xl border p-[20px] shadow-[0_14px_35px_rgba(0,0,0,0.18)] lg:left-full lg:top-0 lg:ml-[20px] lg:mt-0">
                                            <div class="mb-[15px] flex items-center justify-between gap-[10px]">
                                                <button type="button" @click="previousMonth()" aria-label="Mes anterior" class="panel-icon-button inline-flex h-9 w-9 items-center justify-center rounded-lg focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg></button>
                                                <strong x-text="monthLabel"></strong>
                                                <button type="button" @click="nextMonth()" aria-label="Mes siguiente" class="panel-icon-button inline-flex h-9 w-9 items-center justify-center rounded-lg focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg></button>
                                            </div>
                                            <div class="panel-muted mb-[5px] grid grid-cols-7 text-center">
                                                @foreach (['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'] as $weekday)<span>{{ $weekday }}</span>@endforeach
                                            </div>
                                            <div class="grid grid-cols-7 gap-[3px]">
                                                <template x-for="day in days" :key="day.date.toISOString()">
                                                    <button type="button" @click="selectDate(day.date)" class="inline-flex aspect-square items-center justify-center rounded-lg border focus:outline-none focus:ring-0" :class="isSelected(day.date) ? 'panel-button border-transparent' : (isToday(day.date) ? 'panel-menu-item panel-accent panel-border' : (day.currentMonth ? 'panel-menu-item border-transparent' : 'panel-menu-item panel-muted border-transparent opacity-60'))" x-text="day.date.getDate()"></button>
                                                </template>
                                            </div>
                                            <button type="button" @click="selectDate(new Date())" class="panel-button-secondary mt-[15px] h-11 w-full rounded-xl px-[20px] focus:outline-none focus:ring-0">Seleccionar hoy</button>
                                        </div>
                                    </div>
                                @endforeach
                                <button type="submit" wire:loading.attr="disabled" wire:target="generateSelectionReport" class="panel-button inline-flex h-11 w-full shrink-0 items-center justify-center gap-[10px] rounded-xl px-[20px] focus:outline-none focus:ring-0 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="generateSelectionReport" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg>
                                    <svg wire:loading wire:target="generateSelectionReport" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                                    Generar informe
                                </button>
                                <p class="panel-muted inline-flex items-start gap-[10px] leading-tight">
                                    <svg class="mt-[1px] h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span>Último reporte realizado: {{ $lastReportGeneratedAt ?? 'Aún no generado' }}</span>
                                </p>
                            </form>

                            <div class="flex h-[520px] min-h-0 min-w-0 flex-col">
                                <div class="attendance-scrollbar min-h-0 flex-1 overflow-y-auto p-[20px]">
                                    @forelse ($reportUsers->groupBy(fn ($user) => $user->activeOrganizationalProfile?->physicalArea?->name ?? 'Sin área asignada') as $areaName => $areaUsers)
                                        <div class="panel-border mb-[20px] overflow-hidden rounded-xl border last:mb-0" x-show="reportSearch === '' || @js(strtolower($areaName.' '.$areaUsers->map(fn ($user) => trim($user->name.' '.$user->last_name).' '.$user->employee_id)->join(' '))).includes(reportSearch.toLowerCase())">
                                            <div class="panel-header panel-border flex items-center justify-between gap-[20px] border-b px-[20px] py-[15px]">
                                                <div class="flex min-w-0 items-center gap-[10px]">
                                                    <span class="panel-accent truncate">{{ $areaName }}</span>
                                                    <button type="button"
                                                            @click="toggleAreaUsers(@js($areaUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()))"
                                                            :aria-checked="areAllAreaUsersSelected(@js($areaUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()))"
                                                            role="checkbox" class="panel-field relative inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border focus:outline-none focus:ring-0"
                                                            aria-label="Seleccionar a todos los colaboradores de {{ $areaName }}">
                                                        <svg x-show="areAllAreaUsersSelected(@js($areaUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()))" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" /></svg>
                                                    </button>
                                                </div>
                                                <span class="panel-muted shrink-0">{{ $areaUsers->count() }} colaboradores</span>
                                            </div>
                                            <div class="panel-surface grid grid-cols-1 gap-[20px] p-[20px] sm:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($areaUsers as $reportUser)
                                                    @php
                                                        $reportUserSearch = strtolower(trim($reportUser->name.' '.$reportUser->last_name).' '.$reportUser->employee_id.' '.$areaName);
                                                    @endphp
                                                    <div x-show="reportSearch === '' || @js($reportUserSearch).includes(reportSearch.toLowerCase())" class="attendance-pick panel-border flex items-center gap-[10px] rounded-xl border pr-[15px]">
                                                        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-[15px] px-[20px] py-[15px]">
                                                            <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                                                <input type="checkbox" x-model.number="selectedIds" value="{{ $reportUser->id }}" class="panel-field peer h-5 w-5 cursor-pointer appearance-none rounded border focus:outline-none focus:ring-0">
                                                                <svg class="pointer-events-none absolute h-3.5 w-3.5 opacity-0 peer-checked:opacity-100" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                                                    <path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" />
                                                                </svg>
                                                            </span>
                                                            <span class="min-w-0 flex-1"><span class="block truncate">{{ trim($reportUser->name.' '.$reportUser->last_name) }}</span><span class="panel-muted mt-[5px] block truncate">ID Checador: {{ $reportUser->employee_id }}</span></span>
                                                        </label>
                                                        <button type="button" wire:click.stop="openEmployeeIdModal({{ $reportUser->id }})" title="Editar ID del checador" aria-label="Editar ID del checador de {{ trim($reportUser->name.' '.$reportUser->last_name) }}" class="panel-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <p class="panel-muted py-[30px] text-center">No hay colaboradores con ID de checador asignado.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($selectionReportIsCurrent && $reportSection === 'results')
                    @php
                        $attendanceReportActions = [
                            'individual' => ['label' => 'Reporte individual', 'description' => 'Información de una persona', 'enabled' => count($reportedUserIds) === 1],
                            'group' => ['label' => 'Reporte grupal', 'description' => 'Información de seleccionados', 'enabled' => count($reportedUserIds) > 1],
                            'general' => ['label' => 'Reporte general', 'description' => 'Consolidado del periodo', 'enabled' => count($reportedUserIds) > 1],
                        ];
                    @endphp
                    <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-[20px] border-b p-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent leading-none">Informe de asistencia</h2>
                                <p class="mt-[10px] leading-tight">Selecciona un colaborador para consultar sus jornadas.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                                <span class="inline-flex items-center gap-[10px] whitespace-nowrap">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    {{ $reportedUsers->count() }} {{ $reportedUsers->count() === 1 ? 'seleccionado' : 'seleccionados' }}
                                </span>
                                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>{{ $from }} — {{ $to }}</span>
                                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M8 15l3-3 3 2 5-6"/></svg>{{ count($payrollRows) }} jornadas</span>
                            </div>
                        </header>

                        {{-- Colaboradores y tabla de resultados --}}
                        <div class="grid lg:grid-cols-[300px_minmax(0,1fr)]">
                            <aside class="panel-border flex min-h-0 flex-col border-b lg:border-b-0 lg:border-r" aria-label="Colaboradores incluidos en el informe">
                                <div class="panel-border flex flex-col gap-[15px] border-b p-[20px]">
                                    <h3 class="panel-accent leading-none">Colaboradores</h3>
                                    <label class="relative block">
                                        <span class="sr-only">Buscar colaborador en el informe</span>
                                        <svg class="panel-muted pointer-events-none absolute left-[15px] top-1/2 h-[18px] w-[18px] -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                                        <input type="search" x-model="resultUserSearch" autocomplete="off" placeholder="Buscar por nombre o ID"
                                               class="panel-field h-11 w-full rounded-xl border pl-[44px] pr-[15px] focus:ring-0">
                                    </label>
                                </div>
                                <nav class="attendance-scrollbar h-[350px] overflow-y-auto lg:h-auto lg:min-h-0 lg:flex-1" aria-label="Cambiar colaborador">
                                    @foreach ($reportedUsers as $reportedUser)
                                        @php
                                            $reportedUserSearch = strtolower(trim($reportedUser->name.' '.$reportedUser->last_name).' '.$reportedUser->employee_id);
                                        @endphp
                                        <button type="button" wire:click="selectReportUser({{ $reportedUser->id }})"
                                                x-show="resultUserSearch === '' || @js($reportedUserSearch).includes(resultUserSearch.toLowerCase())"
                                                wire:key="report-result-user-{{ $reportedUser->id }}"
                                                @if ($activeReportUserId === $reportedUser->id) aria-current="true" @endif
                                                class="panel-menu-item flex w-full items-center gap-[15px] px-[20px] py-[15px] text-left focus:outline-none focus:ring-0 {{ $activeReportUserId === $reportedUser->id ? 'panel-header' : 'bg-transparent' }}">
                                            <span class="panel-header panel-border panel-accent grid h-10 w-10 shrink-0 place-items-center rounded-full border uppercase">
                                                {{ mb_strtoupper(mb_substr($reportedUser->name, 0, 1)) }}
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate">{{ trim($reportedUser->name.' '.$reportedUser->last_name) }}</span>
                                                <span class="panel-muted mt-[5px] block truncate">ID {{ $reportedUser->employee_id }}</span>
                                            </span>
                                            @if ($activeReportUserId === $reportedUser->id)
                                                <svg class="panel-accent h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 7"/></svg>
                                            @endif
                                        </button>
                                    @endforeach
                                </nav>
                            </aside>

                            <div class="min-h-0 min-w-0" x-data="{
                                syncAttendanceTableHorizontal(event) {
                                    const left = event.target.scrollLeft;
                                    this.$refs.attendanceTableHeader.scrollLeft = left;
                                    this.$refs.attendanceTableBody.scrollLeft = left;
                                    if (this.$refs.attendanceTableTotal) this.$refs.attendanceTableTotal.scrollLeft = left;
                                }
                            }">
                                <div x-ref="attendanceTableHeader" class="attendance-table-head panel-header panel-border overflow-hidden border-b">
                                    <div class="attendance-table-inner w-[1746px]">
                                        <div class="attendance-results-row h-[50px]" role="row">
                                            @foreach (['Acciones', 'Fecha', 'Marcas / Chequeos', 'Tiempo neto', 'Hrs. decimales', 'Pago base', 'Comida', 'Bono', 'Total del día', 'Estado'] as $heading)
                                                <div class="attendance-results-cell panel-accent whitespace-nowrap" role="columnheader">{{ $heading }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div x-ref="attendanceTableBody" class="attendance-results-body-scroll attendance-table-body panel-surface h-[480px] overflow-x-hidden overflow-y-scroll">
                                    <div class="attendance-table-inner min-h-[480px] w-[1740px]" role="rowgroup">
                                        @forelse ($payrollRows as $row)
                                            @php
                                                $rowClass = '';
                                                if ($row['modified_individual'] ?? false) {
                                                    $rowClass = 'attendance-row-modified';
                                                } elseif ($row['requiere_revision'] ?? false) {
                                                    $rowClass = 'attendance-row-review';
                                                }
                                                $attendanceMarks = array_values(array_filter(array_map('trim', explode(',', (string) $row['detalles_marcas']))));
                                                $visibleAttendanceMarks = array_slice($attendanceMarks, 0, 4);
                                            @endphp
                                            <div class="attendance-results-row attendance-row panel-border h-[60px] border-b transition-colors {{ $rowClass }}" role="row">
                                                <div class="attendance-results-cell" data-label="Acciones" role="cell">
                                                    <div class="flex items-center justify-center gap-[5px]">
                                                        <button type="button" title="Eliminar (próximamente)" aria-label="Eliminar jornada" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg></button>
                                                        <button type="button" title="Copiar (próximamente)" aria-label="Copiar jornada" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3" /></svg></button>
                                                        <button type="button" wire:click="editRow('{{ $row['fecha'] }}')" title="Editar jornada" aria-label="Editar jornada" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg></button>
                                                    </div>
                                                </div>
                                                <div class="attendance-results-cell whitespace-nowrap tabular-nums" data-label="Fecha" role="cell">{{ $row['fecha'] }}</div>
                                                <div class="attendance-results-cell attendance-cell-marks" title="{{ $row['detalles_marcas'] }}" data-label="Marcas / Chequeos" role="cell">
                                                    <div class="flex flex-nowrap items-center justify-center gap-[10px] whitespace-nowrap tabular-nums">
                                                        @foreach ($visibleAttendanceMarks as $attendanceMark)
                                                            <span class="whitespace-nowrap">{{ $attendanceMark }}</span>
                                                        @endforeach
                                                        @if (count($attendanceMarks) > 4)
                                                            <span class="whitespace-nowrap" title="Hay {{ count($attendanceMarks) - 4 }} marcas adicionales">...</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Tiempo neto" role="cell">{{ $row['neto'] }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Hrs. decimales" role="cell">{{ $row['horas_decimal'] }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Pago base" role="cell">{{ $row['pago_horas'] }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Comida" role="cell">{{ $row['comida'] }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Bono" role="cell">{{ $row['bono'] }}</div>
                                                <div class="attendance-results-cell panel-accent tabular-nums" data-label="Total del día" role="cell">{{ $row['total'] }}</div>
                                                <div class="attendance-results-cell" data-label="Estado" role="cell">
                                                    @if (($row['estado'] ?? '') === 'Corregido')
                                                        <span class="attendance-pill panel-accent"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4Z"/></svg>Corregido</span>
                                                    @elseif ($row['requiere_revision'])
                                                        <span class="attendance-pill panel-tone-red"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>Impar / Revisar</span>
                                                    @else
                                                        <span class="attendance-pill panel-tone-green"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>Correcto</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="attendance-table-empty panel-muted flex h-[480px] w-full items-center justify-center px-[20px] text-center">
                                                Esta persona no tiene jornadas registradas en el periodo seleccionado.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                @if ($searched && count($payrollRows) > 0)
                                    <div x-ref="attendanceTableTotal" class="attendance-table-total panel-header panel-border overflow-hidden border-t">
                                        <div class="attendance-table-inner w-[1746px]">
                                            <div class="attendance-results-row h-[50px]" role="row">
                                                <div class="attendance-results-cell" role="cell"></div>
                                                <div class="attendance-results-cell attendance-cell-total-title panel-accent whitespace-nowrap" role="cell">TOTAL</div>
                                                <div class="attendance-results-cell" role="cell"></div>
                                                <div class="attendance-results-cell whitespace-nowrap tabular-nums" data-label="Tiempo neto" role="cell">{{ $totalsFooter['tiempo'] ?? '00h 00m 00s' }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Hrs. decimales" role="cell">{{ $totalsFooter['decimal'] ?? '0.00' }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Pago base" role="cell">{{ $totalsFooter['pago_h'] ?? '$0.00' }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Comida" role="cell">{{ $totalsFooter['comida'] ?? '$0.00' }}</div>
                                                <div class="attendance-results-cell tabular-nums" data-label="Bono" role="cell">{{ $totalsFooter['bonos'] ?? '$0.00' }}</div>
                                                <div class="attendance-results-cell panel-accent tabular-nums" data-label="Total del día" role="cell">{{ $totalsFooter['general'] ?? '$0.00' }}</div>
                                                <div class="attendance-results-cell" role="cell"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="attendance-table-hscroll attendance-scrollbar panel-surface overflow-x-scroll overflow-y-hidden"
                                     @scroll="syncAttendanceTableHorizontal($event)" aria-label="Desplazar columnas del informe">
                                    <div class="h-px w-[1746px]"></div>
                                </div>
                                @if ($searched)
                                    <footer class="panel-border flex flex-wrap items-center justify-between gap-[20px] border-t p-[20px]">
                                        <span class="panel-accent">Referencia de estados</span>
                                        <div class="flex flex-wrap items-center gap-[20px]">
                                            <span class="panel-tone-green inline-flex items-center gap-[10px]"><span class="attendance-dot"></span><span class="panel-text">Día correcto</span></span>
                                            <span class="panel-tone-red inline-flex items-center gap-[10px]"><span class="attendance-dot"></span><span class="panel-text">Impar / Revisar</span></span>
                                            <span class="panel-accent inline-flex items-center gap-[10px]"><span class="attendance-dot"></span><span class="panel-text">Ajuste individual</span></span>
                                        </div>
                                    </footer>
                                @endif
                            </div>
                        </div>

                        <div class="attendance-export-bar panel-border grid grid-cols-1 border-t sm:grid-cols-3">
                            @foreach ($attendanceReportActions as $mode => $action)
                                <button type="button" wire:click="exportSelectionReport('{{ $mode }}')"
                                        @disabled(! $action['enabled'])
                                        class="panel-menu-item panel-border inline-flex w-full min-w-0 items-center justify-center gap-[15px] whitespace-nowrap border-b bg-transparent p-[20px] text-left last:border-b-0 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent sm:border-b-0 sm:border-l sm:first:border-l-0">
                                    <svg class="panel-accent h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                                    <span class="min-w-0">
                                        <span class="block truncate">{{ $action['label'] }}</span>
                                        <span class="panel-muted mt-[5px] block truncate">{{ $action['description'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($reportSection === 'history')
                    <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-[20px] border-b p-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent leading-none">Historial de cambios</h2>
                                <p class="mt-[10px] leading-tight">Consulta las correcciones realizadas en las jornadas.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                                <span class="inline-flex items-center gap-[10px]">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                                    {{ $changeHistoryIsExample ? 'Vista de ejemplo' : count($changeHistory).' '.(count($changeHistory) === 1 ? 'cambio registrado' : 'cambios registrados') }}
                                </span>
                                <button type="button" wire:click="exportChangeHistory" wire:loading.attr="disabled" wire:target="exportChangeHistory" @disabled(empty($changeHistory)) class="panel-action inline-flex items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="exportChangeHistory" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                                    <svg wire:loading wire:target="exportChangeHistory" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                                    Descargar PDF
                                </button>
                            </div>
                        </header>

                        <div class="attendance-scrollbar max-h-[560px] overflow-y-auto overscroll-contain" style="overscroll-behavior: contain;">
                            @forelse ($changeHistory as $change)
                                @php
                                    $historyMarksBefore = array_values(array_slice($change['marks_before'], 0, 4));
                                    $historyMarksAfter = array_values(array_slice($change['marks_after'], 0, 4));
                                @endphp
                                <article class="panel-border grid gap-[20px] border-b p-[20px] last:border-b-0 lg:grid-cols-[240px_minmax(0,1fr)_240px]">
                                    <div class="flex min-w-0 flex-col gap-[10px] text-left">
                                        <h3 class="panel-accent truncate">{{ $change['employee_name'] }}</h3>
                                        <p class="panel-muted truncate">ID {{ $change['employee_id'] }}</p>
                                        <p class="inline-flex items-center gap-[10px]">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                            Jornada del {{ $change['date'] }}
                                        </p>
                                    </div>

                                    <div class="panel-border flex min-w-0 flex-col gap-[10px] border-t pt-[20px] text-left lg:border-x lg:border-t-0 lg:px-[20px] lg:pt-0">
                                        <p>{{ $change['comment'] }}</p>
                                        <div class="grid gap-x-[20px] gap-y-[10px] sm:grid-cols-2">
                                            <p><span class="panel-muted">Marcas anteriores:</span> {{ $historyMarksBefore === [] ? 'Sin marcas' : implode(' · ', $historyMarksBefore) }}@if (count($change['marks_before']) > 4) · ...@endif</p>
                                            <p><span class="panel-muted">Marcas nuevas:</span> {{ $historyMarksAfter === [] ? 'Sin marcas' : implode(' · ', $historyMarksAfter) }}@if (count($change['marks_after']) > 4) · ...@endif</p>
                                            <p><span class="panel-muted">Pago por hora:</span> ${{ number_format($change['hourly_rate_before'], 2) }} → ${{ number_format($change['hourly_rate_after'], 2) }}</p>
                                            <p><span class="panel-muted">Comida:</span> {{ $change['bonus_before'] === null ? 'Sin valor' : '$'.number_format($change['bonus_before'], 2) }} → ${{ number_format($change['bonus_after'], 2) }}</p>
                                            <p><span class="panel-muted">Bono:</span> ${{ number_format($change['extra_bonus_before'], 2) }} → ${{ number_format($change['extra_bonus_after'], 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="flex min-w-0 flex-col gap-[10px] text-left">
                                        <p>{{ $change['admin_name'] }}</p>
                                        <p class="panel-muted">{{ $change['changed_at'] }}</p>
                                    </div>
                                </article>
                            @empty
                                <div class="flex min-h-[300px] flex-col items-center justify-center gap-[15px] p-[20px] text-center">
                                    <span class="panel-header panel-border panel-text grid h-12 w-12 place-items-center rounded-xl border">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                                    </span>
                                    <h3 class="panel-accent">Aún no hay cambios registrados</h3>
                                    <p class="panel-muted">Las correcciones de jornadas aparecerán aquí.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                {{-- Modal exclusivo para cambiar la vinculación con el checador --}}
                @if($showEmployeeIdModal)
                    @teleport('body')
                        <div class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 p-[20px]" wire:keydown.escape.window="closeEmployeeIdModal">
                            <div data-panel x-data @click.away="$wire.closeEmployeeIdModal()" class="panel-surface panel-border w-full max-w-lg overflow-visible rounded-xl border shadow-2xl">
                                <div class="panel-header panel-border flex items-center justify-between gap-[15px] rounded-t-xl border-b p-[20px]">
                                    <div class="flex min-w-0 items-center gap-[15px]">
                                        <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="panel-accent leading-none">Editar ID del checador</h3>
                                            <p class="mt-[10px] truncate leading-tight">{{ $editingEmployeeName }}</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="closeEmployeeIdModal" aria-label="Cerrar" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <form wire:submit="saveEmployeeId" class="flex flex-col gap-[20px] p-[20px]">
                                    <div class="panel-border rounded-xl border p-[20px]">
                                        <label for="attendance-employee-id" class="mb-[10px] block">ID relacionado</label>
                                        <div class="relative" x-data="{
                                            open: false,
                                            search: @js($editingEmployeeId),
                                            suggestionsRequested: @js($employeeIdSuggestionsRequested),
                                            loadSuggestions() {
                                                this.open = true;
                                                if (this.suggestionsRequested) return;
                                                this.suggestionsRequested = true;
                                                this.$wire.loadEmployeeIdSuggestions();
                                            }
                                        }" @click.outside="open = false">
                                            <div class="relative">
                                                <input id="attendance-employee-id" type="text" maxlength="50" autocomplete="off" wire:model="editingEmployeeId" x-model="search" @focus="loadSuggestions()" @click="loadSuggestions()" @input="loadSuggestions()" @keydown.escape="open = false" class="attendance-employee-id-input panel-field h-11 w-full rounded-xl border pl-[15px] pr-[50px] focus:ring-0" placeholder="Busca un ID disponible" aria-autocomplete="list" aria-controls="attendance-employee-id-options" x-bind:aria-expanded="open">
                                                <button type="button" @click="open ? open = false : loadSuggestions()" aria-label="Mostrar IDs disponibles" class="panel-icon-button absolute right-0 top-0 flex h-11 w-11 items-center justify-center rounded-r-xl focus:outline-none focus:ring-0">
                                                    <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                                </button>
                                            </div>
                                            <div id="attendance-employee-id-options" x-cloak x-show="open" x-transition class="attendance-scrollbar panel-surface panel-border absolute left-0 top-full z-[100] mt-[10px] flex max-h-[293px] w-full flex-col gap-[10px] overflow-y-auto rounded-xl border p-[10px] shadow-[0_14px_35px_rgba(0,0,0,0.18)]" role="listbox">
                                                <p wire:loading wire:target="loadEmployeeIdSuggestions" class="panel-muted px-[10px] py-[10px]">Cargando IDs disponibles...</p>
                                                <div wire:loading.remove wire:target="loadEmployeeIdSuggestions" class="flex flex-col gap-[10px]">
                                                    @forelse ($employeeIdSuggestions as $employeeIdSuggestion)
                                                        @php($suggestedPersonName = trim((string) ($employeeIdSuggestion->personName ?: 'Nombre no disponible')))
                                                        <button type="button" data-employee-id="{{ $employeeIdSuggestion->employeeID }}" data-person-name="{{ $suggestedPersonName }}" x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())" @click="search = $el.dataset.employeeId; $wire.set('editingEmployeeId', $el.dataset.employeeId, false); open = false" class="panel-option panel-border flex w-full min-w-0 items-center gap-[15px] rounded-xl border px-[15px] py-[10px] text-left focus:outline-none focus:ring-0" role="option">
                                                            <span class="panel-header panel-border panel-accent inline-flex w-[72px] shrink-0 items-center justify-center truncate rounded-lg border px-[10px] py-[5px]" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                                            <span class="panel-muted min-w-0 flex-1 truncate">{{ $suggestedPersonName }}</span>
                                                        </button>
                                                    @empty
                                                        <p class="panel-muted px-[10px] py-[10px]">No hay IDs disponibles para asignar.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                        <p class="panel-muted mt-[10px] leading-tight">Solo aparecen IDs registrados que aún no están relacionados con otra persona.</p>
                                    </div>

                                    <div class="panel-border rounded-xl border p-[20px]">
                                        <h4 class="panel-accent text-[15px] font-normal leading-none">Pago general</h4>
                                        <p class="panel-muted mt-[10px] leading-tight">Estos valores también se guardarán en el Centro de organización.</p>
                                        <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div>
                                                <label for="attendance-edit-hourly-rate" class="mb-[10px] block">Pago por hora ($)</label>
                                                <input id="attendance-edit-hourly-rate" type="number" min="0" step="0.01" wire:model="editingHourlyRate" class="attendance-employee-id-input panel-field h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                @error('editingHourlyRate') <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label for="attendance-edit-food-allowance" class="mb-[10px] block">Pago por comida ($)</label>
                                                <input id="attendance-edit-food-allowance" type="number" min="0" step="0.01" wire:model="editingFoodAllowance" class="attendance-employee-id-input panel-field h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                @error('editingFoodAllowance') <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-[10px]">
                                        <button type="button" wire:click="closeEmployeeIdModal" class="panel-button-secondary inline-flex h-11 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0">Cancelar</button>
                                        <button type="submit" wire:loading.attr="disabled" wire:target="saveEmployeeId" class="panel-button inline-flex h-11 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0 disabled:cursor-wait">
                                            <span wire:loading.remove wire:target="saveEmployeeId">Guardar y sincronizar</span>
                                            <span wire:loading wire:target="saveEmployeeId">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endteleport
                @endif

                {{-- Modal de ajuste por día --}}
                @if($showAttendanceModal)
                    @teleport('body')
                        <div x-data="{
                                previousHtmlOverflow: '',
                                previousBodyOverflow: '',
                                init() {
                                    this.previousHtmlOverflow = document.documentElement.style.overflow;
                                    this.previousBodyOverflow = document.body.style.overflow;
                                    document.documentElement.style.overflow = 'hidden';
                                    document.body.style.overflow = 'hidden';
                                },
                                destroy() {
                                    document.documentElement.style.overflow = this.previousHtmlOverflow;
                                    document.body.style.overflow = this.previousBodyOverflow;
                                }
                             }"
                             class="fixed inset-0 z-[100000] flex items-center justify-center overflow-hidden bg-black/50 p-[20px]"
                             wire:keydown.escape.window="closeModal">
                            <div id="attendance-edit-modal" data-panel @click.away="$wire.closeModal()" class="attendance-edit-modal panel-surface panel-border flex w-full max-w-[680px] flex-col overflow-hidden rounded-xl border shadow-2xl" style="max-height: min(720px, calc(100dvh - 40px));">
                                <div class="panel-header panel-border flex shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                    <div class="flex min-w-0 items-center gap-[15px]">
                                        <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="panel-accent leading-none">Editar jornada del día</h3>
                                            <p class="mt-[10px] truncate leading-tight" title="{{ $selectedEmployeeName }} — {{ $selectedDate }}">{{ $selectedEmployeeName }} — {{ $selectedDate }}</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="closeModal" aria-label="Cerrar" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <form wire:submit="saveDayAdjustment" class="flex min-h-0 flex-1 flex-col">
                                    <div class="attendance-scrollbar flex min-h-0 flex-1 flex-col gap-[20px] overflow-y-auto overscroll-contain p-[20px]" style="overscroll-behavior: contain;">
                                        <section class="panel-border shrink-0 overflow-hidden rounded-xl border">
                                            <header class="panel-header panel-border flex items-start justify-between gap-[20px] border-b p-[20px]">
                                                <div class="min-w-0">
                                                    <h4 class="panel-accent text-[15px] font-normal leading-none">Marcas / Chequeos</h4>
                                                    <p class="mt-[10px] leading-tight">Agrega, ordena o elimina marcas.</p>
                                                </div>
                                                <button type="button" wire:click="addAttendanceMark" class="panel-action inline-flex shrink-0 items-center gap-[10px] bg-transparent p-0 focus:outline-none focus:ring-0">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                                                    Agregar marca
                                                </button>
                                            </header>

                                            <div class="p-[20px]">
                                                <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                                    @foreach ($modalMarks as $index => $mark)
                                                        <div class="panel-border rounded-xl border p-[20px]" wire:key="attendance-mark-{{ $selectedDate }}-{{ $index }}">
                                                            <div class="mb-[10px] flex items-center gap-[10px]">
                                                                <label for="attendance-mark-{{ $index }}">Chequeo {{ $index + 1 }}</label>
                                                                <span class="panel-muted not-italic">({{ $index % 2 === 0 ? 'Entrada' : 'Salida' }})</span>
                                                            </div>
                                                            <div class="flex gap-[10px]">
                                                                <div class="relative min-w-0 flex-1" x-data="timePicker($wire.entangle('modalMarks.{{ $index }}').live, 'attendance-mark-{{ $index }}')" @attendance-time-picker-open.window="if ($event.detail !== pickerId) open = false">
                                                                    <button id="attendance-mark-{{ $index }}" type="button" @click.stop="$dispatch('attendance-time-picker-open', pickerId); toggle($el)" class="panel-field flex h-11 w-full items-center rounded-xl border px-[15px] text-left focus:ring-0" :aria-expanded="open">
                                                                        <span class="whitespace-nowrap tabular-nums" x-text="displayValue"></span>
                                                                    </button>
                                                                    <template x-teleport="body">
                                                                        <div data-panel x-cloak x-show="open" x-transition @click.outside="open = false" :style="popoverStyle" class="attendance-time-popover panel-surface panel-border fixed z-[100100] rounded-xl border p-[20px] shadow-[0_14px_35px_rgba(0,0,0,0.18)]">
                                                                            <div class="grid grid-cols-3 gap-[20px]">
                                                                                @foreach (['hour' => 'Hora', 'minute' => 'Min.', 'second' => 'Seg.'] as $timePart => $timeLabel)
                                                                                    <label class="panel-muted grid gap-[10px]">
                                                                                        <span>{{ $timeLabel }}</span>
                                                                                        <input type="text" inputmode="numeric" maxlength="2" x-model="{{ $timePart }}" class="panel-field h-11 w-full rounded-xl border px-[10px] text-center tabular-nums focus:ring-0">
                                                                                    </label>
                                                                                @endforeach
                                                                            </div>
                                                                            <div class="mt-[20px] flex items-center justify-between gap-[20px]">
                                                                                <div class="panel-border grid h-11 flex-1 grid-cols-2 overflow-hidden rounded-xl border">
                                                                                    @foreach (['a. m.', 'p. m.'] as $timePeriod)
                                                                                        <button type="button" @click="period = @js($timePeriod)" class="h-full px-[10px] focus:outline-none focus:ring-0" :class="period === @js($timePeriod) ? 'panel-button' : 'panel-menu-item'">{{ $timePeriod }}</button>
                                                                                    @endforeach
                                                                                </div>
                                                                                <button type="button" @click="apply()" class="panel-button inline-flex h-11 items-center justify-center gap-[10px] rounded-xl px-[20px] focus:outline-none focus:ring-0">
                                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6"/></svg>
                                                                                    Aplicar
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                                <button type="button" wire:click="removeAttendanceMark({{ $index }})" aria-label="Eliminar chequeo {{ $index + 1 }}" class="panel-button-secondary inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0">
                                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                                                </button>
                                                            </div>
                                                            @error('modalMarks.'.$index) <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @error('modalMarks') <p class="panel-tone-red mt-[20px]">{{ $message }}</p> @enderror
                                                @if (count($modalMarks) % 2 !== 0)
                                                    <p class="panel-danger-box mt-[20px] rounded-xl border px-[20px] py-[15px]">Las marcas impares requieren revisión.</p>
                                                @endif
                                            </div>
                                        </section>

                                        <section class="panel-border shrink-0 overflow-hidden rounded-xl border">
                                            <header class="panel-header panel-border border-b p-[20px]">
                                                <h4 class="panel-accent text-[15px] font-normal leading-none">Pago por hora y comida</h4>
                                                <p class="mt-[10px] leading-tight">El pago se calcula con el tiempo neto.</p>
                                            </header>
                                            <div class="p-[20px]">
                                                <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                                    <div>
                                                        <label class="mb-[10px] block">Pago por hora ($)</label>
                                                        <input type="number" min="0" step="0.01" wire:model="modalHourlyRate" class="panel-field h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        @error('modalHourlyRate') <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="mb-[10px] block">Comida ($)</label>
                                                        <input type="number" min="0" step="0.01" wire:model="modalBonusAmount" @disabled($selectedDateIsWeekend) class="panel-field h-11 w-full rounded-xl border px-[15px] focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60">
                                                        @error('modalBonusAmount') <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                                        @if ($selectedDateIsWeekend)
                                                            <p class="panel-muted mt-[10px] leading-tight">Los sábados y domingos no generan bono de comida.</p>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <label class="mb-[10px] block">Bono del día ($)</label>
                                                        <input type="number" min="0" step="0.01" wire:model.live.debounce.250ms="modalExtraBonusAmount" class="panel-field h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        @error('modalExtraBonusAmount') <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                                    </div>
                                                </div>
                                                <div class="panel-header panel-border mt-[20px] flex items-center justify-between gap-[20px] rounded-xl border px-[20px] py-[15px]">
                                                    <span class="panel-muted">Total calculado</span>
                                                    <strong class="panel-accent">${{ number_format($modalCalculatedTotal, 2) }}</strong>
                                                </div>
                                            </div>
                                        </section>

                                        <section class="panel-border shrink-0 overflow-hidden rounded-xl border">
                                            <header class="panel-header panel-border border-b p-[20px]">
                                                <label for="attendance-change-comment" class="panel-accent block leading-none">Comentario o motivo del cambio <span class="panel-tone-red">*</span></label>
                                            </header>
                                            <div class="p-[20px]">
                                                <textarea id="attendance-change-comment" rows="3" maxlength="500" required wire:model="modalChangeComment" placeholder="Describe por qué se corrigió o modificó esta jornada..." class="panel-field w-full resize-none rounded-xl border px-[15px] py-[10px] focus:ring-0"></textarea>
                                                @error('modalChangeComment') <p class="panel-tone-red mt-[10px]">{{ $message }}</p> @enderror
                                            </div>
                                        </section>
                                    </div>

                                    <div class="panel-header panel-border flex shrink-0 justify-end gap-[10px] border-t p-[20px]">
                                        <button type="button" wire:click="closeModal" class="panel-button-secondary inline-flex h-11 items-center justify-center gap-[10px] rounded-xl px-[20px] focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                                            Cancelar
                                        </button>
                                        <button type="submit" wire:loading.attr="disabled" wire:target="saveDayAdjustment" class="panel-button inline-flex h-11 items-center justify-center gap-[10px] rounded-xl px-[20px] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                                            <span wire:loading.remove wire:target="saveDayAdjustment" class="inline-flex items-center gap-[10px]"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6"/></svg>Guardar cambios</span>
                                            <span wire:loading wire:target="saveDayAdjustment">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endteleport
                @endif
            </div>
        </div>
    </div>

    <!-- Control de pantalla completa (igual que en Panel general, Administración y Reportes de soporte) -->
    <div class="panel-surface panel-border fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] lg:flex">
        <button type="button" @click="toggleFullscreen()" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5" /></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16v5h5" /></svg>
        </button>
    </div>
</div>
