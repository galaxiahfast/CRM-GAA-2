<div class="support-monochrome attendance-monochrome relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700" style="background-color: #ffffff !important;"
     x-data="{
        viewScale: 100,
        isFullscreen: false,
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

        timePicker(model) {
            return {
                open: false,
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
            const savedScale = Number(localStorage.getItem('admin-attendance-view-scale'));
            if (savedScale >= 70 && savedScale <= 100) this.viewScale = savedScale;
        },
        saveScale() { localStorage.setItem('admin-attendance-view-scale', String(this.viewScale)); },
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
     }" @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root">

    <style>
        .attendance-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .attendance-scrollbar::-webkit-scrollbar-track { background: #fff; }
        .attendance-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 9999px; }
        .attendance-scrollbar { scrollbar-width: thin; scrollbar-color: #000 #fff; }
        html:has(.attendance-monochrome),
        body:has(.attendance-monochrome),
        body:has(.attendance-monochrome) > .min-h-screen,
        body:has(.attendance-monochrome) #main-content,
        body:has(.attendance-monochrome) #main-content > div,
        .attendance-monochrome { background: #fff !important; }
        .attendance-monochrome [class~="bg-white"] { background-color: #fff !important; }
        .attendance-monochrome .attendance-page-body,
        .attendance-monochrome .attendance-page-heading,
        .attendance-monochrome .attendance-page-icon,
        .attendance-monochrome .attendance-report-container { background-color: #fff !important; }
        .attendance-monochrome .attendance-page-icon,
        .attendance-monochrome .attendance-report-container { box-shadow: none !important; }
        .attendance-monochrome .attendance-report-container {
            border-radius: .75rem !important;
            background-clip: padding-box;
            isolation: isolate;
        }
        .attendance-monochrome .attendance-report-header { border-radius: .6875rem .6875rem 0 0; }
        .attendance-monochrome .attendance-report-footer { border-radius: 0 0 .6875rem .6875rem; }
        .attendance-monochrome .attendance-page-icon {
            background: #fff !important;
            border: 1px solid #e4e4e7 !important;
            box-shadow: none !important;
        }
        .attendance-monochrome:fullscreen { overflow: auto; background: #fff; }
        .attendance-monochrome .admin-attendance-topbar {
            background: #fff;
            color: #000;
            font-family: inherit;
            font-weight: 400;
        }
        .attendance-monochrome .admin-attendance-topbar * { font-family: inherit; font-weight: 400 !important; }
        .attendance-monochrome .admin-attendance-topbar .attendance-header-emphasis { font-weight: 600 !important; }
        .attendance-monochrome .admin-attendance-topbar .attendance-header-emphasis:disabled { font-weight: 400 !important; }
        .attendance-monochrome table thead tr { background: #fff !important; }
        .attendance-monochrome :is(input, textarea, button):focus,
        .attendance-monochrome :is(input, textarea, button):focus-visible { outline: none !important; box-shadow: none !important; }
        :is(#attendance-edit-modal, .attendance-time-popover) :is(input, textarea, select, button):focus,
        :is(#attendance-edit-modal, .attendance-time-popover) :is(input, textarea, select, button):focus-visible {
            outline: none !important;
            border-color: #d4d4d8 !important;
            box-shadow: none !important;
            --tw-ring-color: transparent !important;
            --tw-ring-offset-width: 0px !important;
        }
        #attendance-edit-modal input[type="number"] { appearance: textfield; -moz-appearance: textfield; }
        #attendance-edit-modal input[type="number"]::-webkit-inner-spin-button,
        #attendance-edit-modal input[type="number"]::-webkit-outer-spin-button { margin: 0; -webkit-appearance: none; }
        .attendance-monochrome .attendance-report-search:focus,
        .attendance-monochrome .attendance-report-search:focus-visible {
            border-color: #e4e4e7 !important;
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-color: transparent !important;
        }
        .attendance-monochrome .attendance-report-download,
        .attendance-monochrome .attendance-report-download:disabled {
            background: transparent !important;
            color: #000 !important;
            opacity: 1 !important;
        }
        .attendance-monochrome .attendance-report-download svg { color: #000 !important; }
        .attendance-monochrome .attendance-report-download:not(:disabled):hover { color: #52525b !important; }
        .attendance-results-row {
            display: grid;
            width: 1820px;
            grid-template-columns: 140px 160px 280px 190px 200px 170px 140px 140px 190px 210px;
        }
        .attendance-results-cell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            text-align: center !important;
        }
        .attendance-results-cell:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 1px;
            height: 30px;
            background: #e4e4e7;
            transform: translateY(-50%);
        }
        .attendance-results-header .attendance-results-cell::after,
        .attendance-results-total .attendance-results-cell::after { background: #d4d4d8; }
        .attendance-results-body-scroll {
            scrollbar-width: thin;
            scrollbar-color: #000 #fff !important;
            overscroll-behavior: contain;
        }
        .attendance-results-body-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .attendance-results-body-scroll::-webkit-scrollbar-track { background: #fff !important; }
        .attendance-results-body-scroll::-webkit-scrollbar-thumb { background: #000 !important; border-radius: 9999px; }
        html.module-dark-theme .attendance-monochrome:fullscreen { background: #09090b; }
        html.module-dark-theme .attendance-monochrome .attendance-scrollbar { scrollbar-color: #fff transparent; }
        html.module-dark-theme .attendance-monochrome .attendance-scrollbar::-webkit-scrollbar-track { background: transparent; }
        html.module-dark-theme .attendance-monochrome .attendance-scrollbar::-webkit-scrollbar-thumb { background: #fff; }
        html.module-dark-theme .attendance-monochrome .attendance-report-search:focus,
        html.module-dark-theme .attendance-monochrome .attendance-report-search:focus-visible { border-color: #52525b !important; }
        @media (max-width: 1100px) {
            .attendance-monochrome .admin-attendance-topbar { padding: 30px !important; }
            .attendance-monochrome .admin-attendance-content { margin-left: 30px !important; margin-right: 30px !important; }
        }
    </style>

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño del Reloj checador administrativo">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none hover:bg-zinc-100 focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="attendance-page-body relative z-10 min-h-[calc(100dvh-90px)] min-w-[1000px] origin-top bg-[#FFFFFF]" style="background: #ffffff !important;"
         :style="`width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">

    @if (session()->has('message'))
        <div class="mx-10 mt-6 rounded-xl border border-green-200 border-l-4 border-l-green-500 bg-green-50 p-4 text-[15px] text-green-700 shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error') || $errors->any())
        @teleport('body')
            <div
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
                class="fixed left-1/2 top-[20px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-center gap-[10px] rounded-xl border border-red-200 bg-white px-[20px] py-[15px] text-[15px] text-red-700 shadow-[0_14px_40px_rgba(0,0,0,0.20)]"
                role="alert"
            >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                </span>
                <span class="min-w-0 flex-1">{{ session('error') ?: $errors->first() }}</span>
                <button type="button" @click="visible = false" aria-label="Cerrar notificación" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endteleport
    @endif

    {{-- Encabezado y migas de pan --}}
    <div class="admin-attendance-topbar flex flex-wrap items-center justify-between gap-12 whitespace-nowrap border-b border-zinc-200 bg-white p-[50px]">
        <div class="flex items-center gap-[15px] text-[15px] font-normal text-gray-500">
            <span>Actividades</span>
            <span class="font-light text-gray-300">&gt;</span>
            <span>Control de Horas</span>
            <span class="font-light text-gray-300">&gt;</span>
            <span class="attendance-header-emphasis text-black">Reloj Checador</span>
        </div>
        <div class="flex flex-wrap items-center gap-[30px]">
            <button type="button" wire:click="export('pdf')" wire:loading.attr="disabled" @disabled(! $searched)
                    class="attendance-header-emphasis inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] text-black transition-colors hover:text-gray-500 disabled:cursor-not-allowed disabled:text-gray-500 disabled:opacity-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                Descargar PDF
            </button>
            <button type="button" disabled aria-disabled="true" class="inline-flex cursor-not-allowed items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] font-normal text-gray-500 opacity-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10ZM9 7V3h6v4"/></svg>
                Imprimir
            </button>
        </div>
    </div>

    <div class="admin-attendance-content attendance-page-heading mx-[50px] mt-[50px] flex flex-wrap items-center justify-between gap-[20px] bg-[#FFFFFF]" style="background: #ffffff !important;">
        <div class="flex min-w-0 items-center gap-[20px]">
            <div class="attendance-page-icon flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black shadow-none" style="background: #ffffff !important; box-shadow: none !important;">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-semibold tracking-tight text-black">Reloj Checador</h1>
                <p class="mt-[5px] truncate text-[15px] text-zinc-500">Administración de marcas biométricas, ajustes por día y exportación.</p>
            </div>
        </div>
        <nav class="flex flex-wrap items-center gap-[30px]" aria-label="Secciones del informe">
            <button type="button" wire:click="showPreparationSection"
                    @if ($reportSection === 'prepare') aria-current="page" @endif
                    class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] transition-colors {{ $reportSection === 'prepare' ? 'font-semibold text-black' : 'font-normal text-zinc-500 hover:text-black' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg>
                Generar informe
            </button>
            <button type="button" @click="syncSelection(); $wire.showResultsSection()"
                    @if ($reportSection === 'results') aria-current="page" @endif
                    class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] transition-colors {{ $reportSection === 'results' ? 'font-semibold text-black' : 'font-normal text-zinc-500 hover:text-black' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM4 10h16M9 10v9"/></svg>
                Ver resultados
            </button>
            <button type="button" wire:click="showHistorySection"
                    @if ($reportSection === 'history') aria-current="page" @endif
                    class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] transition-colors {{ $reportSection === 'history' ? 'font-semibold text-black' : 'font-normal text-zinc-500 hover:text-black' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                Historial de cambios
            </button>
        </nav>
    </div>

    {{-- Informes individual, grupal y general --}}
    @if ($reportSection === 'prepare')
    <section
        class="admin-attendance-content attendance-report-container relative z-20 mx-[50px] mb-[50px] mt-[20px] overflow-visible rounded-xl border border-zinc-200 bg-[#FFFFFF] shadow-none"
        style="background: #ffffff !important; box-shadow: none !important;"
    >
        <header class="attendance-report-header flex min-h-[76px] flex-wrap items-center justify-between gap-[20px] border-b border-zinc-200 bg-white px-[20px] py-[15px]">
            <div class="min-w-0">
                <h2 class="text-[15px] font-semibold text-black">Preparar informe</h2>
                <p class="mt-[5px] truncate text-[15px] text-zinc-500">Configura y genera los resultados fácilmente.</p>
            </div>
            <div class="flex flex-wrap items-center gap-[30px] text-[15px] font-medium text-black">
                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span x-text="selectedIds.length">{{ count($selectedReportUserIds) }}</span> seleccionados</span>
                <span class="inline-flex items-center gap-[10px] whitespace-nowrap">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                    {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
                </span>
                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M9 14h.01M9 18h.01M15 10h.01M15 14h.01M15 18h.01"/></svg>{{ $selectedAreaCount }} {{ $selectedAreaCount === 1 ? 'área participa' : 'áreas participan' }}</span>
            </div>
        </header>

        <div class="flex flex-wrap items-center gap-[20px] border-b border-zinc-200 bg-white p-[20px]">
            <div class="relative min-w-[320px] flex-1">
                <svg class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                <input type="search" x-model="reportSearch" autocomplete="off"
                       @focus="if (!reportSearchOpened) { reportSearch = ''; reportSearchOpened = true }"
                       placeholder="Buscar colaborador, ID o área..."
                       class="attendance-report-search h-[50px] w-full rounded-xl border border-zinc-200 bg-white pl-[45px] pr-[55px] text-[15px] text-black outline-none focus:ring-0">
                <button type="button" @click="startVoiceSearch()" :title="voiceListening ? 'Detener búsqueda por voz' : 'Buscar por voz'" aria-label="Buscar por voz"
                        class="absolute right-[10px] top-1/2 inline-flex h-[34px] w-[34px] -translate-y-1/2 items-center justify-center rounded-lg text-black hover:bg-zinc-100"
                        :class="voiceListening ? 'bg-black text-white animate-pulse' : ''">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Zm-7 9v1a7 7 0 0 0 14 0v-1M12 19v3m-4 0h8"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-[20px] whitespace-nowrap">
                <button type="button" @click="selectAllUsers()" class="inline-flex items-center gap-[8px] p-0 font-semibold text-black hover:text-zinc-600"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>Seleccionar todos</button>
                <button type="button" @click="clearAllUsers()" class="inline-flex items-center gap-[8px] p-0 text-zinc-500 hover:text-black"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>Deseleccionar todos</button>
            </div>
        </div>

        <div class="grid h-[520px] min-h-0 grid-cols-[300px_minmax(0,1fr)] overflow-hidden">
            <form @submit.prevent="syncSelection(); $wire.generateSelectionReport()" class="flex flex-col gap-[20px] border-r border-zinc-200 bg-white p-[20px]">
                <div>
                    <h3 class="font-semibold text-black">Periodo del informe</h3>
                    <p class="mt-[3px] text-[13px] text-zinc-500">Define las fechas que deseas.</p>
                </div>
                @foreach (['from' => 'Desde', 'to' => 'Hasta'] as $dateField => $dateLabel)
                    <div class="relative grid gap-[10px] font-semibold text-black" x-data="datePicker($wire.entangle('{{ $dateField }}'))" @click.outside="open = false">
                        <span>{{ $dateLabel }}</span>
                        <button type="button" @click="open = !open; if (open) setCursorFromValue()" class="flex h-[50px] w-full items-center justify-between rounded-xl border border-zinc-200 bg-white px-[20px] font-normal text-black outline-none transition hover:bg-zinc-50 focus:border-zinc-200 focus:outline-none focus:ring-0" :aria-expanded="open">
                            <span x-text="formattedValue"></span>
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                        </button>
                        <div x-cloak x-show="open" x-transition class="absolute left-0 top-full z-[80] mt-[8px] w-[300px] rounded-xl border border-zinc-200 bg-white p-[15px] font-normal text-black shadow-[0_14px_35px_rgba(0,0,0,0.18)]">
                            <div class="mb-[15px] flex items-center justify-between gap-[10px]">
                                <button type="button" @click="previousMonth()" aria-label="Mes anterior" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg></button>
                                <strong class="text-[15px] font-semibold" x-text="monthLabel"></strong>
                                <button type="button" @click="nextMonth()" aria-label="Mes siguiente" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg></button>
                            </div>
                            <div class="mb-[5px] grid grid-cols-7 text-center text-[12px] font-medium text-zinc-400">
                                @foreach (['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'] as $weekday)<span>{{ $weekday }}</span>@endforeach
                            </div>
                            <div class="grid grid-cols-7 gap-[3px]">
                                <template x-for="day in days" :key="day.date.toISOString()">
                                    <button type="button" @click="selectDate(day.date)" class="inline-flex aspect-square items-center justify-center rounded-lg text-[13px] transition focus:outline-none focus:ring-0" :class="isSelected(day.date) ? 'bg-black font-semibold text-white' : (isToday(day.date) ? 'border border-black bg-white font-semibold text-black' : (day.currentMonth ? 'text-black hover:bg-zinc-100' : 'text-zinc-300 hover:bg-zinc-50'))" x-text="day.date.getDate()"></button>
                                </template>
                            </div>
                            <button type="button" @click="selectDate(new Date())" class="mt-[10px] w-full rounded-lg bg-zinc-100 px-[12px] py-[8px] text-[13px] font-medium text-black transition hover:bg-zinc-200 focus:outline-none focus:ring-0">Seleccionar hoy</button>
                        </div>
                    </div>
                @endforeach
                <button type="submit" wire:loading.attr="disabled" wire:target="generateSelectionReport" class="inline-flex h-[50px] w-full shrink-0 items-center justify-center gap-[10px] rounded-xl bg-black px-[20px] font-normal text-white hover:bg-zinc-800 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="generateSelectionReport" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg>
                    <svg wire:loading wire:target="generateSelectionReport" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                    Generar informe
                </button>
                <p class="inline-flex items-center gap-[10px] text-[13px] text-zinc-500">
                    <svg class="h-4 w-4 shrink-0 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Último reporte realizado: {{ $lastReportGeneratedAt ?? 'Aún no generado' }}
                </p>

            </form>

            <div class="flex min-h-0 min-w-0 flex-col bg-white">
                <div class="attendance-scrollbar min-h-0 flex-1 overflow-y-auto p-[20px]">
                    @forelse ($reportUsers->groupBy(fn ($user) => $user->activeOrganizationalProfile?->physicalArea?->name ?? 'Sin área asignada') as $areaName => $areaUsers)
                        <div class="mb-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white last:mb-0" x-show="reportSearch === '' || @js(strtolower($areaName.' '.$areaUsers->map(fn ($user) => trim($user->name.' '.$user->last_name).' '.$user->employee_id)->join(' '))).includes(reportSearch.toLowerCase())">
                            <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-100 px-[20px] py-[10px]">
                                <div class="flex min-w-0 items-center gap-[10px]">
                                    <span class="truncate font-semibold text-black">{{ $areaName }}</span>
                                    <button type="button"
                                            @click="toggleAreaUsers(@js($areaUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()))"
                                            :aria-checked="areAllAreaUsersSelected(@js($areaUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()))"
                                            role="checkbox" class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border border-zinc-300 bg-white focus:outline-none focus:ring-0"
                                            aria-label="Seleccionar a todos los colaboradores de {{ $areaName }}">
                                        <svg x-show="areAllAreaUsersSelected(@js($areaUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all()))" class="h-3.5 w-3.5 text-black" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" /></svg>
                                    </button>
                                </div>
                                <span class="text-zinc-500">{{ $areaUsers->count() }} colaboradores</span>
                            </div>
                            <div class="grid gap-[15px] p-[20px] sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($areaUsers as $reportUser)
                                    @php
                                        $reportUserSearch = strtolower(trim($reportUser->name.' '.$reportUser->last_name).' '.$reportUser->employee_id.' '.$areaName);
                                    @endphp
                                    <div x-show="reportSearch === '' || @js($reportUserSearch).includes(reportSearch.toLowerCase())" class="flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white p-[10px] transition hover:bg-zinc-100">
                                        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-[15px] p-[5px]">
                                            <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                                <input type="checkbox" x-model.number="selectedIds" value="{{ $reportUser->id }}" class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-zinc-300 bg-white checked:border-zinc-300 checked:bg-white focus:outline-none focus:ring-0">
                                                <svg class="pointer-events-none absolute h-3.5 w-3.5 text-black opacity-0 peer-checked:opacity-100" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                                    <path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" />
                                                </svg>
                                            </span>
                                            <span class="min-w-0 flex-1"><span class="block truncate font-medium text-black">{{ trim($reportUser->name.' '.$reportUser->last_name) }}</span><span class="mt-[3px] block truncate text-zinc-500">ID Checador: {{ $reportUser->employee_id }}</span></span>
                                        </label>
                                        <button type="button" wire:click.stop="openEmployeeIdModal({{ $reportUser->id }})" title="Editar ID del checador" aria-label="Editar ID del checador de {{ trim($reportUser->name.' '.$reportUser->last_name) }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="py-[30px] text-center text-zinc-500">No hay colaboradores con ID de checador asignado.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </section>
    @endif

    @if ($selectionReportIsCurrent && $reportSection === 'results')
    <section class="admin-attendance-content mx-[50px] mb-[50px] mt-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
        <div>
            <div class="flex min-h-[76px] items-center justify-between gap-[20px] border-b border-zinc-200 bg-white px-[20px] py-[15px]">
                <div>
                    <h2 class="text-[15px] font-semibold text-black">Informe de asistencia</h2>
                    <p class="mt-[5px] text-[15px] text-zinc-500">Selecciona un colaborador para consultar sus jornadas.</p>
                </div>
                <div class="flex flex-wrap items-center gap-[25px] font-medium text-black">
                    <span class="inline-flex items-center gap-[10px] whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        {{ $reportedUsers->count() }} {{ $reportedUsers->count() === 1 ? 'seleccionado' : 'seleccionados' }}
                    </span>
                    <span class="inline-flex items-center gap-[8px] whitespace-nowrap"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>{{ $from }} — {{ $to }}</span>
                    <span class="inline-flex items-center gap-[8px] whitespace-nowrap"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M8 15l3-3 3 2 5-6"/></svg>{{ count($payrollRows) }} jornadas</span>
                </div>
            </div>
            @php
                $attendanceReportActions = [
                    'individual' => ['label' => 'Reporte individual', 'description' => 'Información de una persona', 'enabled' => count($reportedUserIds) === 1],
                    'group' => ['label' => 'Reporte grupal', 'description' => 'Información de seleccionados', 'enabled' => count($reportedUserIds) > 1],
                    'general' => ['label' => 'Reporte general', 'description' => 'Consolidado del periodo', 'enabled' => count($reportedUserIds) > 1],
                ];
            @endphp
        </div>
    {{-- Tabla de resultados --}}
        <div class="grid border-t border-zinc-200 bg-white lg:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="flex min-h-0 flex-col border-b border-zinc-200 bg-white lg:border-b-0 lg:border-r" aria-label="Colaboradores incluidos en el informe">
                <div class="border-b border-zinc-200 p-[15px]">
                    <div class="mb-[10px]">
                        <h3 class="font-semibold text-black">Colaboradores</h3>
                    </div>
                    <label class="relative block">
                        <span class="sr-only">Buscar colaborador en el informe</span>
                        <svg class="pointer-events-none absolute left-[12px] top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                        <input type="search" x-model="resultUserSearch" autocomplete="off" placeholder="Buscar por nombre o ID"
                               class="h-[40px] w-full rounded-md border border-zinc-200 bg-white pl-[36px] pr-[12px] text-[13px] text-black outline-none focus:border-zinc-300 focus:ring-0">
                    </label>
                </div>
                <nav class="attendance-scrollbar h-[350px] overflow-y-auto p-0 lg:h-auto lg:min-h-0 lg:flex-1" aria-label="Cambiar colaborador">
                    @foreach ($reportedUsers as $reportedUser)
                        @php
                            $reportedUserSearch = strtolower(trim($reportedUser->name.' '.$reportedUser->last_name).' '.$reportedUser->employee_id);
                        @endphp
                        <button type="button" wire:click="selectReportUser({{ $reportedUser->id }})"
                                x-show="resultUserSearch === '' || @js($reportedUserSearch).includes(resultUserSearch.toLowerCase())"
                                wire:key="report-result-user-{{ $reportedUser->id }}"
                                class="flex min-h-[70px] w-full items-center gap-[10px] rounded-md border border-transparent px-[20px] py-[12px] text-left {{ $activeReportUserId === $reportedUser->id ? 'bg-zinc-100 text-black' : 'bg-white text-black' }}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border {{ $activeReportUserId === $reportedUser->id ? 'border-zinc-200 bg-black text-white' : 'border-zinc-200 bg-white text-black' }} text-[13px] font-semibold">
                                {{ mb_strtoupper(mb_substr($reportedUser->name, 0, 1)) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-medium">{{ trim($reportedUser->name.' '.$reportedUser->last_name) }}</span>
                                <span class="mt-[2px] block truncate text-[12px] text-zinc-500">ID {{ $reportedUser->employee_id }}</span>
                            </span>
                            @if ($activeReportUserId === $reportedUser->id)
                                <svg class="h-4 w-4 shrink-0 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 7"/></svg>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </aside>

            <div class="min-h-0 min-w-0 bg-white" x-data="{
                syncAttendanceTableHorizontal(event) {
                    const left = event.target.scrollLeft;
                    this.$refs.attendanceTableHeader.scrollLeft = left;
                    this.$refs.attendanceTableBody.scrollLeft = left;
                    if (this.$refs.attendanceTableTotal) this.$refs.attendanceTableTotal.scrollLeft = left;
                }
            }">
                <div x-ref="attendanceTableHeader" class="overflow-hidden bg-zinc-100">
                    <div class="w-[1826px]">
                    <div class="attendance-results-row attendance-results-header h-[64px] font-semibold text-zinc-700" role="row">
                        @foreach (['Acciones', 'Fecha', 'Marcas / Chequeos', 'Tiempo neto', 'Hrs. decimales', 'Pago base', 'Comida', 'Bono', 'Total del día', 'Estado'] as $heading)
                            <div class="attendance-results-cell whitespace-nowrap px-[20px]" role="columnheader">{{ $heading }}</div>
                        @endforeach
                    </div>
                    </div>
                </div>

                <div x-ref="attendanceTableBody"
                     class="attendance-results-body-scroll h-[480px] overflow-x-hidden overflow-y-scroll bg-white text-[15px] font-medium text-zinc-700">
                    <div class="min-h-[480px] w-[1820px]" role="rowgroup">
                        @forelse ($payrollRows as $row)
                            @php
                                $rowClass = 'bg-white hover:bg-zinc-50';
                                if ($row['modified_individual'] ?? false) {
                                    $rowClass = 'bg-blue-50 hover:bg-blue-100/80 shadow-[inset_4px_0_0_#60a5fa]';
                                } elseif ($row['requiere_revision'] ?? false) {
                                    $rowClass = 'bg-red-50/70 hover:bg-red-100/80 shadow-[inset_4px_0_0_#fca5a5]';
                                }
                                $attendanceMarks = array_values(array_filter(array_map('trim', explode(',', (string) $row['detalles_marcas']))));
                                $visibleAttendanceMarks = array_slice($attendanceMarks, 0, 4);
                            @endphp
                            <div class="attendance-results-row h-[80px] border-b border-zinc-200 {{ $rowClass }} transition-colors" role="row">
                                <div class="attendance-results-cell px-[15px]" role="cell">
                                    <div class="flex items-center justify-center gap-[4px]">
                                        <button type="button" title="Eliminar (próximamente)" aria-label="Eliminar jornada" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg></button>
                                        <button type="button" title="Copiar (próximamente)" aria-label="Copiar jornada" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3" /></svg></button>
                                        <button type="button" wire:click="editRow('{{ $row['fecha'] }}')" title="Editar jornada" aria-label="Editar jornada" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg></button>
                                    </div>
                                </div>
                                <div class="attendance-results-cell whitespace-nowrap px-[15px] font-semibold tabular-nums text-black" role="cell">{{ $row['fecha'] }}</div>
                                <div class="attendance-results-cell px-[15px] text-zinc-500" title="{{ $row['detalles_marcas'] }}" role="cell">
                                    <div class="flex flex-nowrap items-center justify-center gap-[10px] whitespace-nowrap text-[13px] tabular-nums">
                                        @foreach ($visibleAttendanceMarks as $attendanceMark)
                                            <span class="whitespace-nowrap">{{ $attendanceMark }}</span>
                                        @endforeach
                                        @if (count($attendanceMarks) > 4)
                                            <span class="whitespace-nowrap" title="Hay {{ count($attendanceMarks) - 4 }} marcas adicionales">...</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="attendance-results-cell px-[15px] tabular-nums text-zinc-600" role="cell">{{ $row['neto'] }}</div>
                                <div class="attendance-results-cell px-[15px] tabular-nums" role="cell">{{ $row['horas_decimal'] }}</div>
                                <div class="attendance-results-cell px-[15px] tabular-nums" role="cell">{{ $row['pago_horas'] }}</div>
                                <div class="attendance-results-cell px-[15px] tabular-nums" role="cell">{{ $row['comida'] }}</div>
                                <div class="attendance-results-cell px-[15px] tabular-nums text-black" role="cell">{{ $row['bono'] }}</div>
                                <div class="attendance-results-cell px-[15px] font-bold tabular-nums text-black" role="cell">{{ $row['total'] }}</div>
                                <div class="attendance-results-cell px-[15px]" role="cell">
                                    @if (($row['estado'] ?? '') === 'Corregido')
                                        <span class="inline-flex items-center gap-[6px] rounded-full border border-zinc-300 bg-zinc-200 px-[10px] py-1 text-[13px] font-semibold text-black"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4Z"/></svg>Corregido</span>
                                    @elseif ($row['requiere_revision'])
                                        <span class="inline-flex items-center gap-[6px] rounded-full bg-black px-[10px] py-1 text-[13px] font-semibold text-white"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>Impar / Revisar</span>
                                    @else
                                        <span class="inline-flex items-center gap-[6px] rounded-full border border-zinc-200 bg-white px-[10px] py-1 text-[13px] font-semibold text-black"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>Correcto</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="flex h-[480px] w-full items-center justify-center px-[20px] text-center text-zinc-500">
                                Esta persona no tiene jornadas registradas en el periodo seleccionado.
                            </div>
                        @endforelse
                    </div>
                </div>

                @if ($searched && count($payrollRows) > 0)
                    <div x-ref="attendanceTableTotal" class="overflow-hidden bg-zinc-100">
                        <div class="w-[1826px]">
                        <div class="attendance-results-row attendance-results-total h-[64px] font-bold text-black" role="row">
                            <div class="attendance-results-cell" role="cell"></div>
                            <div class="attendance-results-cell whitespace-nowrap px-[20px]" role="cell">TOTAL</div>
                            <div class="attendance-results-cell" role="cell"></div>
                            <div class="attendance-results-cell whitespace-nowrap px-[20px] tabular-nums" role="cell">{{ $totalsFooter['tiempo'] ?? '00h 00m 00s' }}</div>
                            <div class="attendance-results-cell px-[20px] tabular-nums" role="cell">{{ $totalsFooter['decimal'] ?? '0.00' }}</div>
                            <div class="attendance-results-cell px-[20px] tabular-nums" role="cell">{{ $totalsFooter['pago_h'] ?? '$0.00' }}</div>
                            <div class="attendance-results-cell px-[20px] tabular-nums" role="cell">{{ $totalsFooter['comida'] ?? '$0.00' }}</div>
                            <div class="attendance-results-cell px-[20px] tabular-nums" role="cell">{{ $totalsFooter['bonos'] ?? '$0.00' }}</div>
                            <div class="attendance-results-cell px-[20px] tabular-nums" role="cell">{{ $totalsFooter['general'] ?? '$0.00' }}</div>
                            <div class="attendance-results-cell" role="cell"></div>
                        </div>
                        </div>
                    </div>
                @endif
                <div class="attendance-scrollbar overflow-x-scroll overflow-y-hidden bg-white"
                     @scroll="syncAttendanceTableHorizontal($event)" aria-label="Desplazar columnas del informe">
                    <div class="h-px w-[1826px]"></div>
                </div>
            @if ($searched)
                <footer class="flex flex-wrap items-center justify-between gap-[20px] border-t border-zinc-200 bg-white p-[20px] text-zinc-500">
                    <span class="font-semibold text-black">Referencia de estados</span>
                    <div class="flex flex-wrap items-center gap-[25px]">
                        <span class="inline-flex items-center gap-[10px]"><span class="h-3 w-3 rounded-full border border-zinc-400 bg-white"></span>Día correcto</span>
                        <span class="inline-flex items-center gap-[10px]"><span class="h-3 w-3 rounded-full bg-black"></span>Impar / Revisar</span>
                        <span class="inline-flex items-center gap-[10px]"><span class="h-3 w-3 rounded-full border border-zinc-400 bg-zinc-300"></span>Ajuste individual</span>
                    </div>
                </footer>
            @endif
            </div>
        </div>
        <div class="grid grid-cols-3 border-t border-zinc-200 bg-white px-[20px] py-[15px]">
            @foreach ($attendanceReportActions as $mode => $action)
                <button type="button" wire:click="exportSelectionReport('{{ $mode }}')"
                        @disabled(! $action['enabled'])
                        class="attendance-report-download inline-flex w-full min-w-0 items-center justify-center gap-[10px] whitespace-nowrap border-0 bg-transparent px-[20px] py-0 text-left text-black transition-colors disabled:cursor-not-allowed">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold text-black">{{ $action['label'] }}</span>
                        <span class="mt-[3px] block truncate font-normal text-zinc-500">{{ $action['description'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </section>
    @endif

    @if ($reportSection === 'history')
        <section class="admin-attendance-content mx-[50px] mb-[50px] mt-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none">
            <header class="flex min-h-[76px] flex-wrap items-center justify-between gap-[20px] border-b border-zinc-200 bg-white px-[20px] py-[15px]">
                <div>
                    <h2 class="text-[15px] font-semibold text-black">Historial de cambios</h2>
                    <p class="mt-[5px] text-[15px] text-zinc-500">Consulta las correcciones realizadas en las jornadas.</p>
                </div>
                <span class="inline-flex items-center gap-[10px] text-[15px] font-medium text-black">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                    {{ $changeHistoryIsExample ? 'Vista de ejemplo' : count($changeHistory).' '.(count($changeHistory) === 1 ? 'cambio registrado' : 'cambios registrados') }}
                </span>
            </header>

            <div class="attendance-scrollbar max-h-[560px] overflow-y-auto overscroll-contain bg-white" style="overscroll-behavior: contain;">
                @forelse ($changeHistory as $change)
                    @php
                        $historyMarksBefore = array_values(array_slice($change['marks_before'], 0, 4));
                        $historyMarksAfter = array_values(array_slice($change['marks_after'], 0, 4));
                    @endphp
                    <article class="grid items-stretch gap-[20px] border-b border-zinc-200 bg-white p-[20px] last:border-b-0 lg:grid-cols-[220px_minmax(0,1fr)_220px]">
                        <div class="flex min-w-0 flex-col justify-center text-left">
                            <h3 class="truncate font-semibold text-black">{{ $change['employee_name'] }}</h3>
                            <p class="mt-[3px] truncate text-zinc-500">ID {{ $change['employee_id'] }}</p>
                            <p class="mt-[8px] inline-flex items-center gap-[8px] text-black">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                                Jornada del {{ $change['date'] }}
                            </p>
                        </div>

                        <div class="flex min-w-0 flex-col justify-center border-zinc-200 text-left lg:border-x lg:px-[20px]">
                            <div class="grid items-stretch gap-[20px] text-[13px] text-zinc-500 sm:grid-cols-2">
                                <div class="grid h-full min-w-0 grid-rows-3 items-center gap-[10px] text-left">
                                    <p class="font-medium text-black">{{ $change['comment'] }}</p>
                                    <p class="whitespace-nowrap"><span class="font-medium text-black">Marcas anteriores:</span> {{ $historyMarksBefore === [] ? 'Sin marcas' : implode(' · ', $historyMarksBefore) }}@if (count($change['marks_before']) > 4) · ...@endif</p>
                                    <p><span class="font-medium text-black">Pago por hora:</span> ${{ number_format($change['hourly_rate_before'], 2) }} → ${{ number_format($change['hourly_rate_after'], 2) }}</p>
                                </div>
                                <div class="grid h-full min-w-0 grid-rows-3 items-center gap-[10px] text-left">
                                    <p class="whitespace-nowrap"><span class="font-medium text-black">Marcas nuevas:</span> {{ $historyMarksAfter === [] ? 'Sin marcas' : implode(' · ', $historyMarksAfter) }}@if (count($change['marks_after']) > 4) · ...@endif</p>
                                    <p><span class="font-medium text-black">Comida:</span> {{ $change['bonus_before'] === null ? 'Sin valor' : '$'.number_format($change['bonus_before'], 2) }} → ${{ number_format($change['bonus_after'], 2) }}</p>
                                    <p><span class="font-medium text-black">Bono:</span> ${{ number_format($change['extra_bonus_before'], 2) }} → ${{ number_format($change['extra_bonus_after'], 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex min-w-0 flex-col justify-center text-left text-zinc-500">
                            <p class="font-medium text-black">{{ $change['admin_name'] }}</p>
                            <p class="mt-[3px]">{{ $change['changed_at'] }}</p>
                        </div>
                    </article>
                @empty
                    <div class="flex min-h-[300px] flex-col items-center justify-center gap-[10px] px-[20px] text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-lg border border-zinc-200 bg-white text-black">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                        </span>
                        <h3 class="font-semibold text-black">Aún no hay cambios registrados</h3>
                        <p class="text-zinc-500">Las correcciones de jornadas aparecerán aquí.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    {{-- Modal exclusivo para cambiar la vinculación con el checador --}}
    @if($showEmployeeIdModal)
        @teleport('body')
        <div class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-[2px]" wire:keydown.escape.window="closeEmployeeIdModal">
            <style>
                .attendance-employee-id-input:focus,
                .attendance-employee-id-input:focus-visible {
                    border-color: #d4d4d8 !important;
                    outline: none !important;
                    box-shadow: none !important;
                    --tw-ring-color: transparent !important;
                }
                .attendance-id-scrollbar {
                    scrollbar-width: thin;
                    scrollbar-color: #000 transparent;
                    overscroll-behavior: contain;
                }
                .attendance-id-scrollbar::-webkit-scrollbar { width: 6px; }
                .attendance-id-scrollbar::-webkit-scrollbar-track { background: transparent; }
                .attendance-id-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 9999px; }
            </style>
            <div x-data @click.away="$wire.closeEmployeeIdModal()" class="w-full max-w-lg overflow-visible rounded-xl border border-zinc-200 bg-zinc-100 shadow-2xl">
                <div class="flex items-center justify-between gap-[15px] border-b border-zinc-300 px-[20px] py-[15px]">
                    <div class="flex min-w-0 items-center gap-[10px]">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-black text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-black">Editar ID del checador</h3>
                            <p class="mt-[3px] truncate text-zinc-500">{{ $editingEmployeeName }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeEmployeeIdModal" aria-label="Cerrar" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-300 bg-white text-black transition hover:bg-zinc-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="saveEmployeeId" class="p-[20px]">
                    <div class="rounded-xl border border-zinc-200 bg-white p-[20px]">
                        <label for="attendance-employee-id" class="mb-[8px] block font-medium text-black">ID relacionado</label>
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
                                <input id="attendance-employee-id" type="text" maxlength="50" autocomplete="off" wire:model="editingEmployeeId" x-model="search" @focus="loadSuggestions()" @click="loadSuggestions()" @input="loadSuggestions()" @keydown.escape="open = false" class="attendance-employee-id-input h-[46px] w-full rounded-xl border border-zinc-300 bg-white px-[15px] pr-12 text-black shadow-none focus:border-zinc-300 focus:outline-none focus:ring-0" placeholder="Busca un ID disponible" aria-autocomplete="list" aria-controls="attendance-employee-id-options" x-bind:aria-expanded="open">
                                <button type="button" @click="open ? open = false : loadSuggestions()" aria-label="Mostrar IDs disponibles" class="absolute right-0 top-0 flex h-[46px] w-12 items-center justify-center text-zinc-500 hover:text-black focus:outline-none focus:ring-0">
                                    <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                </button>
                            </div>
                            <div id="attendance-employee-id-options" x-cloak x-show="open" x-transition class="attendance-id-scrollbar absolute left-0 top-full z-[100] mt-[8px] max-h-[293px] w-full space-y-[5px] overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[10px] shadow-[0_14px_35px_rgba(0,0,0,0.18)]" role="listbox">
                                <p wire:loading wire:target="loadEmployeeIdSuggestions" class="px-[12px] py-[10px] text-zinc-500">Cargando IDs disponibles...</p>
                                <div wire:loading.remove wire:target="loadEmployeeIdSuggestions" class="space-y-[5px]">
                                    @forelse ($employeeIdSuggestions as $employeeIdSuggestion)
                                        @php($suggestedPersonName = trim((string) ($employeeIdSuggestion->personName ?: 'Nombre no disponible')))
                                        <button type="button" data-employee-id="{{ $employeeIdSuggestion->employeeID }}" data-person-name="{{ $suggestedPersonName }}" x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())" @click="search = $el.dataset.employeeId; $wire.set('editingEmployeeId', $el.dataset.employeeId, false); open = false" class="flex h-[64px] w-full min-w-0 items-center gap-[12px] rounded-lg border border-zinc-200 bg-white px-[20px] py-[15px] text-left transition hover:bg-zinc-100 focus:border-zinc-200 focus:bg-zinc-100 focus:outline-none focus:ring-0" role="option">
                                            <span class="inline-flex w-[72px] shrink-0 items-center justify-center truncate rounded-md bg-black px-[8px] py-[5px] font-semibold text-white" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                            <span class="min-w-0 flex-1 truncate text-zinc-500">{{ $suggestedPersonName }}</span>
                                        </button>
                                    @empty
                                        <p class="px-[12px] py-[10px] text-zinc-500">No hay IDs disponibles para asignar.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <p class="mt-[8px] text-zinc-500">Solo aparecen IDs registrados que aún no están relacionados con otra persona.</p>
                    </div>

                    <div class="mt-[15px] rounded-xl border border-zinc-200 bg-white p-[20px]">
                        <h4 class="font-medium text-black">Pago general</h4>
                        <p class="mt-[3px] text-zinc-500">Estos valores también se guardarán en el Centro de organización.</p>
                        <div class="mt-[15px] grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                            <div>
                                <label for="attendance-edit-hourly-rate" class="mb-[8px] block font-medium text-black">Pago por hora ($)</label>
                                <input id="attendance-edit-hourly-rate" type="number" min="0" step="0.01" wire:model="editingHourlyRate" class="attendance-employee-id-input h-[46px] w-full rounded-xl border border-zinc-300 bg-white px-[15px] text-black shadow-none focus:border-zinc-300 focus:outline-none focus:ring-0">
                                @error('editingHourlyRate') <p class="mt-[6px] text-[13px] text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="attendance-edit-food-allowance" class="mb-[8px] block font-medium text-black">Pago por comida ($)</label>
                                <input id="attendance-edit-food-allowance" type="number" min="0" step="0.01" wire:model="editingFoodAllowance" class="attendance-employee-id-input h-[46px] w-full rounded-xl border border-zinc-300 bg-white px-[15px] text-black shadow-none focus:border-zinc-300 focus:outline-none focus:ring-0">
                                @error('editingFoodAllowance') <p class="mt-[6px] text-[13px] text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-[15px] flex justify-end gap-[10px]">
                        <button type="button" wire:click="closeEmployeeIdModal" class="inline-flex h-[42px] items-center justify-center rounded-lg border border-zinc-300 bg-white px-[18px] text-black transition hover:bg-zinc-200">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveEmployeeId" class="inline-flex h-[42px] items-center justify-center rounded-lg bg-black px-[18px] text-white transition hover:bg-zinc-800 disabled:cursor-wait">
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
             class="fixed inset-0 z-[100000] flex items-center justify-center overflow-hidden bg-black/45 p-[16px]"
             wire:keydown.escape.window="closeModal">
            <div id="attendance-edit-modal" @click.away="$wire.closeModal()" class="attendance-edit-modal flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none" style="max-height: min(680px, calc(100dvh - 48px));">
                <div class="flex shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white px-[20px] py-[15px]">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-black">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[15px] font-semibold text-black">Editar jornada del día</h3>
                            <p class="mt-[3px] truncate text-[15px] text-zinc-500" title="{{ $selectedEmployeeName }} — {{ $selectedDate }}">{{ $selectedEmployeeName }} — {{ $selectedDate }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Cerrar" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-300 bg-white text-black focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="saveDayAdjustment" class="flex min-h-0 flex-1 flex-col">
                    <div class="attendance-scrollbar min-h-0 flex-1 space-y-[20px] overflow-y-auto overscroll-contain bg-white p-[20px] text-[15px]" style="overscroll-behavior: contain;">
                        <section class="rounded-xl border border-zinc-200 bg-white p-[20px] shadow-none">
                            <div class="flex items-start justify-between gap-[20px]">
                                <div>
                                    <h4 class="text-[15px] font-semibold text-black">Marcas / Chequeos</h4>
                                    <p class="mt-[3px] text-[15px] text-zinc-500">Agrega, ordena o elimina marcas.</p>
                                </div>
                                <button type="button" wire:click="addAttendanceMark" class="inline-flex shrink-0 items-center justify-center gap-[10px] rounded-lg border border-zinc-300 bg-white px-[20px] py-[15px] text-[15px] font-medium text-black focus:outline-none focus:ring-0">
                                    <span class="text-lg leading-none">+</span> Agregar marca
                                </button>
                            </div>

                            <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                @foreach ($modalMarks as $index => $mark)
                                    <div class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px]" wire:key="attendance-mark-{{ $selectedDate }}-{{ $index }}">
                                        <div class="mb-[10px] flex items-center gap-[10px]">
                                            <label for="attendance-mark-{{ $index }}" class="text-[15px] font-medium text-black">Chequeo {{ $index + 1 }}</label>
                                            <span class="text-[13px] not-italic text-zinc-500">({{ $index % 2 === 0 ? 'Entrada' : 'Salida' }})</span>
                                        </div>
                                        <div class="flex gap-[20px]">
                                            <div class="relative min-w-0 flex-1" x-data="timePicker($wire.entangle('modalMarks.{{ $index }}').live)">
                                                <button id="attendance-mark-{{ $index }}" type="button" @click.stop="toggle($el)" class="flex w-full items-center rounded-xl border border-zinc-300 bg-white px-[20px] py-[15px] text-left text-[15px] text-black shadow-none focus:border-zinc-300 focus:outline-none focus:ring-0" :aria-expanded="open">
                                                    <span class="whitespace-nowrap tabular-nums" x-text="displayValue"></span>
                                                </button>
                                                <template x-teleport="body">
                                                <div x-cloak x-show="open" x-transition @click.outside="open = false" :style="popoverStyle" class="attendance-time-popover fixed z-[100100] rounded-xl border border-zinc-200 bg-white p-[20px] shadow-[0_14px_35px_rgba(0,0,0,0.18)]">
                                                    <div class="grid grid-cols-3 gap-[20px]">
                                                        @foreach (['hour' => 'Hora', 'minute' => 'Min.', 'second' => 'Seg.'] as $timePart => $timeLabel)
                                                            <label class="grid gap-[10px] text-[13px] text-zinc-500">
                                                                <span>{{ $timeLabel }}</span>
                                                                <input type="text" inputmode="numeric" maxlength="2" x-model="{{ $timePart }}" class="w-full rounded-xl border border-zinc-300 bg-white px-[10px] py-[10px] text-center text-[15px] tabular-nums text-black shadow-none focus:border-zinc-300 focus:outline-none focus:ring-0">
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <div class="mt-[20px] flex items-center justify-between gap-[20px]">
                                                        <div class="grid flex-1 grid-cols-2 overflow-hidden rounded-xl border border-zinc-300 bg-white">
                                                            @foreach (['a. m.', 'p. m.'] as $timePeriod)
                                                                <button type="button" @click="period = @js($timePeriod)" class="px-[10px] py-[10px] text-[13px] focus:outline-none focus:ring-0" :class="period === @js($timePeriod) ? 'bg-black text-white' : 'bg-white text-black'">{{ $timePeriod }}</button>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" @click="apply()" class="inline-flex items-center justify-center gap-[10px] rounded-xl bg-black px-[20px] py-[15px] text-[15px] text-white focus:outline-none focus:ring-0">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6"/></svg>
                                                            Aplicar
                                                        </button>
                                                    </div>
                                                </div>
                                                </template>
                                            </div>
                                            <button type="button" wire:click="removeAttendanceMark({{ $index }})" aria-label="Eliminar chequeo {{ $index + 1 }}" class="inline-flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-xl border border-zinc-300 bg-white text-black focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                            </button>
                                        </div>
                                        @error('modalMarks.'.$index) <p class="mt-2 text-[13px] text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                            @error('modalMarks') <p class="mt-3 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            @if (count($modalMarks) % 2 !== 0)
                                <p class="mt-[20px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] text-zinc-600">Las marcas impares requieren revisión.</p>
                            @endif
                        </section>

                        <section class="rounded-xl border border-zinc-200 bg-white p-[20px] shadow-none">
                            <h4 class="text-[15px] font-semibold text-black">Pago por hora y comida</h4>
                            <p class="mt-[3px] text-[15px] text-zinc-500">El pago se calcula con el tiempo neto.</p>
                            <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                <div>
                                    <label class="mb-[10px] block text-[15px] font-medium text-black">Pago por hora ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model="modalHourlyRate" class="w-full rounded-xl border border-zinc-300 bg-white px-[20px] py-[15px] text-[15px] shadow-none focus:border-zinc-300 focus:ring-0">
                                    @error('modalHourlyRate') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-[10px] block text-[15px] font-medium text-black">Comida ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model="modalBonusAmount" @disabled($selectedDateIsWeekend) class="w-full rounded-xl border border-zinc-300 bg-white px-[20px] py-[15px] text-[15px] shadow-none focus:border-zinc-300 focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60">
                                    @error('modalBonusAmount') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                    @if ($selectedDateIsWeekend)
                                        <p class="mt-2 text-[13px] text-gray-500">Los sábados y domingos no generan bono de comida.</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="mb-[10px] block text-[15px] font-medium text-black">Bono del día ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model.live.debounce.250ms="modalExtraBonusAmount" class="w-full rounded-xl border border-zinc-300 bg-white px-[20px] py-[15px] text-[15px] shadow-none focus:border-zinc-300 focus:ring-0">
                                    @error('modalExtraBonusAmount') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="mt-[20px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px]">
                                <span class="block text-[13px] text-zinc-500">Total calculado</span>
                                <strong class="mt-1 block text-[15px] text-black">${{ number_format($modalCalculatedTotal, 2) }}</strong>
                            </div>
                        </section>

                        <section class="rounded-xl border border-zinc-200 bg-white p-[20px] shadow-none">
                            <label for="attendance-change-comment" class="mb-[10px] block text-[15px] font-medium text-black">Comentario o motivo del cambio <span class="text-red-600">*</span></label>
                            <textarea id="attendance-change-comment" rows="3" maxlength="500" required wire:model="modalChangeComment" placeholder="Describe por qué se corrigió o modificó esta jornada..." class="w-full resize-none rounded-xl border border-zinc-300 bg-white px-[20px] py-[15px] text-[15px] shadow-none focus:border-zinc-300 focus:ring-0"></textarea>
                            @error('modalChangeComment') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                        </section>
                    </div>

                    <div class="flex shrink-0 justify-end gap-[20px] border-t border-zinc-200 bg-white px-[20px] py-[15px]">
                        <button type="button" wire:click="closeModal" class="inline-flex items-center justify-center gap-[10px] rounded-lg border border-zinc-300 bg-white px-[20px] py-[15px] text-[15px] font-medium text-black focus:outline-none focus:ring-0">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12"/></svg>
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveDayAdjustment" class="inline-flex items-center justify-center gap-[10px] rounded-lg bg-black px-[20px] py-[15px] text-[15px] font-medium text-white focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
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
