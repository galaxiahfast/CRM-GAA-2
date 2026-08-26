<div class="support-monochrome attendance-monochrome relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
     x-data="{
        viewScale: 100,
        isFullscreen: false,
        reportSearch: '',
        reportSearchOpened: false,
        voiceListening: false,
        voiceRecognition: null,

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
        .attendance-monochrome:fullscreen { overflow: auto; background: #fff; }
        .attendance-monochrome .admin-attendance-topbar { background: rgba(255,255,255,.75); }
        .attendance-monochrome :is(input, textarea, button):focus,
        .attendance-monochrome :is(input, textarea, button):focus-visible { outline: none !important; box-shadow: none !important; }
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
        .attendance-results-table thead th {
            position: relative;
            background: #f4f4f5;
            box-shadow: inset 0 -1px 0 #d4d4d8;
            text-align: center !important;
            vertical-align: middle;
        }
        .attendance-results-table thead th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 1px;
            height: 30px;
            background: #d4d4d8;
            transform: translateY(-50%);
        }
        .attendance-results-table tfoot td {
            position: relative;
            background: #f4f4f5;
            box-shadow: none;
            text-align: center !important;
            vertical-align: middle;
        }
        .attendance-results-table tfoot td:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 1px;
            height: 30px;
            background: #d4d4d8;
            transform: translateY(-50%);
        }
        .attendance-results-table {
            display: block;
            width: 1686px;
        }
        .attendance-results-table :is(thead, tfoot) {
            display: block;
            width: 1680px;
        }
        .attendance-results-table tbody {
            display: block;
            width: 1686px;
            max-height: 430px;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: #000 transparent;
        }
        .attendance-results-table tbody::-webkit-scrollbar { width: 6px; }
        .attendance-results-table tbody::-webkit-scrollbar-track { background: transparent; }
        .attendance-results-table tbody::-webkit-scrollbar-thumb { background: #000; border-radius: 9999px; }
        .attendance-results-table tr {
            display: grid;
            width: 1680px;
            grid-template-columns: 160px 280px 190px 200px 170px 140px 190px 190px 160px;
        }
        .attendance-results-table tbody td {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            text-align: center !important;
        }
        .attendance-results-table tbody td:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 1px;
            height: 30px;
            background: #e4e4e7;
            transform: translateY(-50%);
        }
        .attendance-results-table tbody td[colspan] { grid-column: 1 / -1; }
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

    <div class="no-print absolute left-1/2 top-[20px] z-30 flex -translate-x-1/2 items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño del Reloj checador administrativo">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none hover:bg-zinc-100 focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1000px] origin-top bg-white"
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
    <div class="admin-attendance-topbar flex flex-wrap items-center justify-between gap-12 whitespace-nowrap border-b border-zinc-200 p-[50px]">
        <div class="flex items-center gap-[15px] text-[15px] text-gray-500">
            <span class="font-medium">Actividades</span>
            <span class="font-light text-gray-300">&gt;</span>
            <span class="font-medium">Control de Horas</span>
            <span class="font-light text-gray-300">&gt;</span>
            <span class="font-semibold text-black">Reloj Checador</span>
        </div>
        <div class="flex flex-wrap items-center gap-[30px]">
            <button type="button" wire:click="export('pdf')" wire:loading.attr="disabled" @disabled(! $searched)
                    class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] font-medium text-black transition-colors hover:text-zinc-600 disabled:cursor-not-allowed disabled:opacity-40">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                Descargar PDF
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-[15px] font-medium text-black transition-colors hover:text-zinc-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10ZM9 7V3h6v4"/></svg>
                Imprimir
            </button>
        </div>
    </div>

    <div class="admin-attendance-content mx-[50px] mt-[50px] flex items-center gap-[20px]">
        <div class="flex min-w-0 items-center gap-[20px]">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-semibold tracking-tight text-black">Reloj Checador</h1>
                <p class="mt-[5px] truncate text-[15px] text-zinc-500">Administración de marcas biométricas, ajustes por día y exportación.</p>
            </div>
        </div>
    </div>

    {{-- Informes individual, grupal y general --}}
    <section class="admin-attendance-content relative z-20 mx-[50px] mb-[50px] mt-[20px] overflow-visible rounded-xl border border-zinc-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
        <header class="flex flex-wrap items-center justify-between gap-[20px] border-b border-zinc-200 bg-zinc-50 px-[20px] py-[15px]">
            <div class="min-w-0">
                <h2 class="text-[15px] font-semibold text-black">Preparar informe</h2>
                <p class="mt-[5px] truncate text-[15px] text-zinc-500">Configura y genera los resultados fácilmente.</p>
            </div>
            <div class="flex flex-wrap items-center gap-[30px] text-[15px] font-medium text-black">
                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>{{ count($selectedReportUserIds) }} seleccionados</span>
                <span class="inline-flex items-center gap-[10px] whitespace-nowrap"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg>Actualización automática</span>
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
                <button type="button" wire:click="selectAllReportUsers" class="inline-flex items-center gap-[8px] p-0 font-semibold text-black hover:text-zinc-600"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>Seleccionar todos</button>
                <button type="button" wire:click="clearReportSelection" class="inline-flex items-center gap-[8px] p-0 text-zinc-500 hover:text-black"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>Deseleccionar todos</button>
            </div>
        </div>

        <div class="grid min-h-[360px] grid-cols-[300px_minmax(0,1fr)]">
            <form wire:submit="generateSelectionReport" class="flex flex-col gap-[20px] border-r border-zinc-200 bg-white p-[20px]">
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

            <div class="flex min-h-0 min-w-0 flex-col bg-zinc-50">
                <div class="attendance-scrollbar min-h-0 flex-1 overflow-y-auto p-[20px]">
                    @forelse ($reportUsers->groupBy(fn ($user) => $user->activeOrganizationalProfile?->physicalArea?->name ?? 'Sin área asignada') as $areaName => $areaUsers)
                        <div class="mb-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white last:mb-0" x-show="reportSearch === '' || @js(strtolower($areaName.' '.$areaUsers->map(fn ($user) => trim($user->name.' '.$user->last_name).' '.$user->employee_id)->join(' '))).includes(reportSearch.toLowerCase())">
                            <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-100 px-[20px] py-[10px]"><span class="font-semibold text-black">{{ $areaName }}</span><span class="text-zinc-500">{{ $areaUsers->count() }} colaboradores</span></div>
                            <div class="grid gap-[15px] p-[20px] sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($areaUsers as $reportUser)
                                    @php
                                        $reportUserSearch = strtolower(trim($reportUser->name.' '.$reportUser->last_name).' '.$reportUser->employee_id.' '.$areaName);
                                    @endphp
                                    <div x-show="reportSearch === '' || @js($reportUserSearch).includes(reportSearch.toLowerCase())" class="flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-zinc-50 p-[10px] transition hover:bg-zinc-100">
                                        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-[15px] p-[5px]">
                                            <input type="checkbox" wire:model.live="selectedReportUserIds" value="{{ $reportUser->id }}" class="h-4 w-4 rounded border-zinc-300 text-black focus:ring-0">
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

        @php
            $attendanceReportActions = [
                'individual' => ['label' => 'Reporte individual', 'description' => 'Información de una persona', 'enabled' => count($reportedUserIds) === 1],
                'group' => ['label' => 'Reporte grupal', 'description' => 'Información de seleccionados', 'enabled' => count($reportedUserIds) > 1],
                'general' => ['label' => 'Reporte general', 'description' => 'Consolidado del periodo', 'enabled' => count($reportedUserIds) > 1],
            ];
        @endphp
        <footer class="grid grid-cols-3 border-t border-zinc-200 bg-zinc-50 px-[20px] py-[15px]">
            @foreach ($attendanceReportActions as $mode => $action)
                <button type="button" wire:click="exportSelectionReport('{{ $mode }}')"
                        @disabled(! $selectionReportIsCurrent || ! $action['enabled'])
                        class="attendance-report-download inline-flex w-full min-w-0 items-center justify-center gap-[10px] whitespace-nowrap border-0 bg-transparent px-[20px] py-0 text-left text-black transition-colors disabled:cursor-not-allowed">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold text-black">{{ $action['label'] }}</span>
                        <span class="mt-[3px] block truncate font-normal text-zinc-500">{{ $action['description'] }}</span>
                    </span>
                </button>
            @endforeach
        </footer>
    </section>

    @if ($selectionReportIsCurrent)
    <section class="admin-attendance-content mx-[50px] mb-[50px] mt-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
        <div>
            <div class="flex items-center justify-between gap-[20px] border-b border-zinc-200 bg-zinc-50 px-[20px] py-[15px]">
                <div>
                    <h2 class="font-semibold text-black">Informe de asistencia</h2>
                    <p class="mt-[3px] text-zinc-500">Selecciona un colaborador para consultar sus jornadas.</p>
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
            <nav class="attendance-scrollbar flex gap-[20px] overflow-x-auto p-[20px]" aria-label="Colaboradores incluidos en el informe">
                @foreach ($reportedUsers as $reportedUser)
                    <button type="button" wire:click="selectReportUser({{ $reportedUser->id }})"
                            class="flex min-w-[240px] flex-1 items-center gap-[12px] rounded-xl border px-[15px] py-[12px] text-left transition {{ $activeReportUserId === $reportedUser->id ? 'border-black bg-black text-white' : 'border-zinc-200 bg-white text-black hover:bg-zinc-100' }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $activeReportUserId === $reportedUser->id ? 'bg-white text-black' : 'bg-zinc-100 text-black' }} font-semibold">
                            {{ mb_strtoupper(mb_substr($reportedUser->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium">{{ trim($reportedUser->name.' '.$reportedUser->last_name) }}</span>
                            <span class="mt-[3px] block truncate text-[13px] {{ $activeReportUserId === $reportedUser->id ? 'text-zinc-300' : 'text-zinc-500' }}">ID {{ $reportedUser->employee_id }}</span>
                        </span>
                    </button>
                @endforeach
            </nav>
        </div>
    {{-- Tabla de resultados --}}
        <div class="border-t border-zinc-200">
            <div class="attendance-scrollbar overflow-x-auto px-[20px]">
                <table class="attendance-results-table border-separate border-spacing-0 text-left text-[15px]">
                    <thead>
                        <tr class="font-semibold text-zinc-700">
                            <th class="whitespace-nowrap px-[50px] py-[20px]">Fecha</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px]">Marcas / Chequeos</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-center">Tiempo neto</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-center">Hrs. decimales</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-center">Pago base</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-center">Bono</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-right">Total del día</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-center">Estado</th>
                            <th class="whitespace-nowrap px-[50px] py-[20px] text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 font-medium text-zinc-700">
                        @forelse ($payrollRows as $row)
                            @php
                                $rowClass = 'bg-white hover:bg-zinc-50';
                                if ($row['modified_individual'] ?? false) {
                                    $rowClass = 'bg-blue-50 hover:bg-blue-100/80 border-l-4 border-l-blue-400';
                                } elseif ($row['requiere_revision'] ?? false) {
                                    $rowClass = 'bg-red-50/70 hover:bg-red-100/80 border-l-4 border-l-red-300';
                                }
                            @endphp
                            <tr class="{{ $rowClass }} transition-colors">
                                <td class="whitespace-nowrap p-[15px] font-semibold tabular-nums text-black">{{ $row['fecha'] }}</td>
                                <td class="p-[15px] tabular-nums text-zinc-500" title="{{ $row['detalles_marcas'] }}">
                                    <div class="space-y-[3px]">
                                        @foreach (array_chunk(array_values(array_filter(array_map('trim', explode(',', (string) $row['detalles_marcas'])))), 2) as $attendanceMarkPair)
                                            <span class="block whitespace-nowrap">{{ implode(', ', $attendanceMarkPair) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-[15px] text-center tabular-nums text-zinc-600">{{ $row['neto'] }}</td>
                                <td class="p-[15px] text-center tabular-nums">{{ $row['horas_decimal'] }}</td>
                                <td class="p-[15px] text-center tabular-nums">{{ $row['pago_horas'] }}</td>
                                <td class="p-[15px] text-center tabular-nums text-black">{{ $row['bono'] }}</td>
                                <td class="p-[15px] text-right font-bold tabular-nums text-black">{{ $row['total'] }}</td>
                                <td class="p-[15px] text-center">
                                    @if (($row['estado'] ?? '') === 'Corregido')
                                        <span class="inline-flex items-center gap-[6px] rounded-full border border-zinc-300 bg-zinc-200 px-[10px] py-1 text-[13px] font-semibold text-black"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4Z"/></svg>Corregido</span>
                                    @elseif ($row['requiere_revision'])
                                        <span class="inline-flex items-center gap-[6px] rounded-full bg-black px-[10px] py-1 text-[13px] font-semibold text-white"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>Impar / Revisar</span>
                                    @else
                                        <span class="inline-flex items-center gap-[6px] rounded-full border border-zinc-200 bg-white px-[10px] py-1 text-[13px] font-semibold text-black"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>Correcto</span>
                                    @endif
                                </td>
                                <td class="p-[15px] text-center">
                                    <div class="flex items-center justify-center gap-[4px]">
                                        <button type="button" title="Eliminar (próximamente)" aria-label="Eliminar jornada" class="inline-flex h-7 w-7 items-center justify-center text-black transition hover:text-zinc-500 focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                        </button>
                                        <button type="button" title="Copiar (próximamente)" aria-label="Copiar jornada" class="inline-flex h-7 w-7 items-center justify-center text-black transition hover:text-zinc-500 focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3" /></svg>
                                        </button>
                                        <button type="button" wire:click="editRow('{{ $row['fecha'] }}')" title="Editar jornada" aria-label="Editar jornada" class="inline-flex h-7 w-7 items-center justify-center text-black transition hover:text-zinc-500 focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="bg-zinc-50 px-[20px] py-[40px] text-center text-zinc-500">
                                    Esta persona no tiene jornadas registradas en el periodo seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($searched && count($payrollRows) > 0)
                        <tfoot class="font-bold text-black">
                            <tr>
                                <td class="whitespace-nowrap px-[50px] py-[20px]">TOTAL</td>
                                <td class="px-[50px] py-[20px]"></td>
                                <td class="whitespace-nowrap px-[50px] py-[20px] tabular-nums">{{ $totalsFooter['tiempo'] ?? '00h 00m 00s' }}</td>
                                <td class="px-[50px] py-[20px] tabular-nums">{{ $totalsFooter['decimal'] ?? '0.00' }}</td>
                                <td class="px-[50px] py-[20px] tabular-nums">{{ $totalsFooter['pago_h'] ?? '$0.00' }}</td>
                                <td class="px-[50px] py-[20px] tabular-nums">{{ $totalsFooter['bonos'] ?? '$0.00' }}</td>
                                <td class="px-[50px] py-[20px] tabular-nums">{{ $totalsFooter['general'] ?? '$0.00' }}</td>
                                <td class="px-[50px] py-[20px]"></td>
                                <td class="px-[50px] py-[20px]"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($searched)
                <footer class="flex flex-wrap items-center justify-between gap-[20px] border-t border-zinc-200 bg-zinc-50 p-[20px] text-zinc-500">
                    <span class="font-semibold text-black">Referencia de estados</span>
                    <div class="flex flex-wrap items-center gap-[25px]">
                        <span class="inline-flex items-center gap-[10px]"><span class="h-3 w-3 rounded-full border border-zinc-400 bg-white"></span>Día correcto</span>
                        <span class="inline-flex items-center gap-[10px]"><span class="h-3 w-3 rounded-full bg-black"></span>Impar / Revisar</span>
                        <span class="inline-flex items-center gap-[10px]"><span class="h-3 w-3 rounded-full border border-zinc-400 bg-zinc-300"></span>Ajuste individual</span>
                    </div>
                </footer>
            @endif
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
                    <div class="flex min-w-0 items-center gap-[15px]">
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
                        <div class="relative" x-data="{ open: false, search: @js($editingEmployeeId) }" @click.outside="open = false">
                            <div class="relative">
                                <input id="attendance-employee-id" type="text" maxlength="50" autocomplete="off" wire:model="editingEmployeeId" x-model="search" @focus="open = true" @click="open = true" @input="open = true" @keydown.escape="open = false" class="attendance-employee-id-input h-[46px] w-full rounded-xl border border-zinc-300 bg-zinc-50 px-[15px] pr-12 text-black shadow-none focus:border-zinc-300 focus:outline-none focus:ring-0" placeholder="Busca un ID disponible" aria-autocomplete="list" aria-controls="attendance-employee-id-options" x-bind:aria-expanded="open">
                                <button type="button" @click="open = !open" aria-label="Mostrar IDs disponibles" class="absolute right-0 top-0 flex h-[46px] w-12 items-center justify-center text-zinc-500 hover:text-black focus:outline-none focus:ring-0">
                                    <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                                </button>
                            </div>
                            <div id="attendance-employee-id-options" x-cloak x-show="open" x-transition class="attendance-id-scrollbar absolute left-0 top-full z-[100] mt-[8px] max-h-[293px] w-full space-y-[5px] overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-50 p-[10px] shadow-[0_14px_35px_rgba(0,0,0,0.18)]" role="listbox">
                                @forelse ($employeeIdSuggestions as $employeeIdSuggestion)
                                    @php($suggestedPersonName = trim((string) ($employeeIdSuggestion->personName ?: 'Nombre no disponible')))
                                    <button type="button" data-employee-id="{{ $employeeIdSuggestion->employeeID }}" data-person-name="{{ $suggestedPersonName }}" x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())" @click="search = $el.dataset.employeeId; $wire.set('editingEmployeeId', $el.dataset.employeeId); open = false" class="flex h-[64px] w-full min-w-0 items-center gap-[12px] rounded-lg border border-zinc-200 bg-white px-[20px] py-[15px] text-left transition hover:bg-zinc-100 focus:border-zinc-200 focus:bg-zinc-100 focus:outline-none focus:ring-0" role="option">
                                        <span class="inline-flex w-[72px] shrink-0 items-center justify-center truncate rounded-md bg-black px-[8px] py-[5px] font-semibold text-white" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                        <span class="min-w-0 flex-1 truncate text-zinc-500">{{ $suggestedPersonName }}</span>
                                    </button>
                                @empty
                                    <p class="px-[12px] py-[10px] text-zinc-500">No hay IDs disponibles para asignar.</p>
                                @endforelse
                            </div>
                        </div>
                        <p class="mt-[8px] text-zinc-500">Solo aparecen IDs registrados que aún no están relacionados con otra persona.</p>
                    </div>

                    <div class="mt-[15px] flex justify-end gap-[10px]">
                        <button type="button" wire:click="closeEmployeeIdModal" class="inline-flex h-[42px] items-center justify-center rounded-lg border border-zinc-300 bg-white px-[18px] text-black transition hover:bg-zinc-200">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveEmployeeId" class="inline-flex h-[42px] items-center justify-center rounded-lg bg-black px-[18px] text-white transition hover:bg-zinc-800 disabled:cursor-wait">
                            <span wire:loading.remove wire:target="saveEmployeeId">Guardar ID</span>
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
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]" wire:keydown.escape.window="closeModal">
            <div x-data @click.away="$wire.closeModal()" class="flex max-h-[calc(100vh-3rem)] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-[#F3F3F3] shadow-2xl">
                <div class="flex items-center justify-between gap-4 border-b border-gray-300 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#1A3A6B] text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[15px] font-semibold text-gray-900">Editar jornada del día</h3>
                            <p class="mt-1 truncate text-[15px] text-gray-500" title="{{ $selectedEmployeeName }} — {{ $selectedDate }}">{{ $selectedEmployeeName }} — {{ $selectedDate }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Cerrar" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="saveDayAdjustment" class="flex min-h-0 flex-1 flex-col">
                    <div class="attendance-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto p-4 text-[15px]">
                        <section class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-[15px] font-semibold text-gray-900">Marcas / Chequeos</h4>
                                    <p class="mt-1 text-[15px] text-gray-500">Ordena la jornada agregando o eliminando marcas con precisión de segundos.</p>
                                </div>
                                <button type="button" wire:click="addAttendanceMark" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-[#1A3A6B] bg-white px-3 text-[15px] font-medium text-[#1A3A6B] hover:bg-blue-50 focus:outline-none focus:ring-0">
                                    <span class="text-lg leading-none">+</span> Agregar marca
                                </button>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($modalMarks as $index => $mark)
                                    <div class="rounded-lg border border-gray-200 bg-[#F3F3F3] p-2.5" wire:key="attendance-mark-{{ $selectedDate }}-{{ $index }}">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <label for="attendance-mark-{{ $index }}" class="text-[15px] font-medium text-gray-700">Chequeo {{ $index + 1 }}</label>
                                            <span class="rounded-full px-2 py-0.5 text-[12px] font-semibold {{ $index % 2 === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ $index % 2 === 0 ? 'Entrada' : 'Salida' }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <input id="attendance-mark-{{ $index }}" type="time" step="1" wire:model="modalMarks.{{ $index }}" class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0">
                                            <button type="button" wire:click="removeAttendanceMark({{ $index }})" aria-label="Eliminar chequeo {{ $index + 1 }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50 focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                            </button>
                                        </div>
                                        @error('modalMarks.'.$index) <p class="mt-2 text-[13px] text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                            @error('modalMarks') <p class="mt-3 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-3 rounded-lg border px-3 py-2 text-[15px] {{ count($modalMarks) % 2 === 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                {{ count($modalMarks) % 2 === 0 ? 'La jornada tiene marcas pares y quedará como corregida.' : 'La jornada conserva marcas impares y seguirá requiriendo revisión.' }}
                            </p>
                        </section>

                        <section class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                            <h4 class="text-[15px] font-semibold text-gray-900">Pago y bono del día</h4>
                            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-[15px] font-medium text-gray-700">Pago base del día ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model="modalDailyPay" class="h-10 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0">
                                    @error('modalDailyPay') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-[15px] font-medium text-gray-700">Bono del día ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model="modalBonusAmount" class="h-10 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0">
                                    @error('modalBonusAmount') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                            <label for="attendance-change-comment" class="mb-2 block text-[15px] font-medium text-gray-700">Comentario o motivo del cambio <span class="text-red-600">*</span></label>
                            <textarea id="attendance-change-comment" rows="3" maxlength="500" required wire:model="modalChangeComment" placeholder="Describe por qué se corrigió o modificó esta jornada..." class="w-full resize-y rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 py-2 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0"></textarea>
                            @error('modalChangeComment') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-2 text-[15px] text-gray-500">El comentario quedará registrado en el historial del día.</p>
                        </section>
                    </div>

                    <div class="flex shrink-0 justify-end gap-3 border-t border-gray-300 bg-[#F3F3F3] px-4 py-3">
                        <button type="button" wire:click="closeModal" class="inline-flex h-10 items-center justify-center rounded-lg border border-[#1A3A6B] bg-white px-5 text-[15px] font-medium text-[#1A3A6B] hover:bg-gray-100 focus:outline-none focus:ring-0">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveDayAdjustment" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-medium text-white hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveDayAdjustment">Guardar cambios</span>
                            <span wire:loading wire:target="saveDayAdjustment">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    </div>
</div>
