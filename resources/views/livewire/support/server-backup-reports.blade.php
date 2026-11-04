{{-- Reporte de respaldos: mismo diseño que Panel general, Administración, Reloj Checador y Reportes de soporte
     (tarjetas con cabecera gris, líneas de 1px, texto de 15px, separaciones de 20px, escala de vista al 90%
     ajustable y tema oscuro con fondo negro). Solo cambia la presentación; la lógica vive en
     App\Livewire\Support\ServerBackupReports y el envío de los lunes en App\Services\Support\WeeklyBackupReportService. --}}
<div
    data-panel
    class="support-backup-report-page relative isolate min-h-[calc(100dvh-100px)] w-full min-w-0 overflow-hidden text-[15px]"
    x-data="{
        viewScale: 90,
        isFullscreen: false,
        isCompact: window.innerWidth < 1024,
        scaledHeight: null,
        observer: null,
        selServer: @js($servers[0]['key'] ?? ''),
        selProfile: '',
        autoScanStarted: false,
        currentServer: '',
        currentProfile: '',
        processed: $wire.entangle('processedProfiles'),
        total: $wire.entangle('totalProfiles'),
        scanning: $wire.entangle('reading'),
        statusMessage: $wire.entangle('statusMessage'),
        recipientsOpen: false,
        selectedRecipients: $wire.entangle('emailRecipients'),
        mailSending: false,
        mailStatus: @js($emailStatus),
        mailSent: {{ $emailSent ? 'true' : 'false' }},
        init() {
            try {
                const saved = Number(localStorage.getItem('backup-reports-view-scale'));
                if (saved >= 70 && saved <= 100) this.viewScale = saved;
            } catch (error) {}
            this.$nextTick(() => {
                this.observer = new ResizeObserver(() => this.syncHeight());
                this.observer.observe(this.$refs.scaled);
                this.syncHeight();
            });
            if (!this.autoScanStarted) { this.autoScanStarted = true; setTimeout(() => $wire.startReading(), 800); }
        },
        destroy() {
            this.observer?.disconnect();
        },
        syncHeight() {
            if (this.$refs.scaled) this.scaledHeight = Math.ceil(this.$refs.scaled.offsetHeight * (this.viewScale / 100));
        },
        setScale(value) {
            this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
            try { localStorage.setItem('backup-reports-view-scale', String(this.viewScale)); } catch (error) {}
            this.$nextTick(() => this.syncHeight());
        },
        selectProfile(key) {
            this.selProfile = this.selProfile === key ? '' : key;
            if (this.selProfile && window.innerWidth < 1280) {
                this.$nextTick(() => {
                    const el = document.querySelector('[data-profile-detail=\'' + key + '\']');
                    if (el) { el.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
                });
            }
        },
        async sendReport() {
            if (this.mailSending) return;
            const scrollPosition = window.scrollY;
            this.mailSending = true;
            this.mailStatus = '';
            try {
                const result = await this.$wire.sendEmail();
                this.mailSent = result.sent;
                this.mailStatus = result.status;
                if (result.sent) this.recipientsOpen = false;
            } finally {
                this.mailSending = false;
                this.$nextTick(() => window.scrollTo(0, scrollPosition));
            }
        },
        async toggleFullscreen(container) {
            if (document.fullscreenElement === container) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await container.requestFullscreen();
        }
    }"
    x-on:backup-read-next.window="setTimeout(() => $wire.readNext(), 300)"
    x-on:backup-scan-progress.window="processed = $event.detail.processed; total = $event.detail.total; scanning = $event.detail.reading; statusMessage = $event.detail.message; currentServer = $event.detail.server || ''; currentProfile = $event.detail.profile || ''; if ($event.detail.reading) { mailStatus = ''; mailSent = false; }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
    @resize.window.debounce.150ms="isCompact = window.innerWidth < 1024; syncHeight()"
>
    <x-panel-styles />

    <style>
        /* «Datos técnicos» (summary) no está en la lista base del panel: mismo tamaño y peso que el resto */
        [data-backup-modal] summary { font-size: 15px; font-weight: 400; }

        /* Respuesta al pasar el cursor, al seleccionar y al deshabilitar */
        .support-backup-report-page :is(button, label, summary, [role="tab"]) { cursor: pointer; }
        .support-backup-report-page button:disabled { cursor: not-allowed; }
        .support-backup-report-page .panel-button:disabled,
        .support-backup-report-page .panel-button-secondary:disabled { opacity: .4; }

        /* Botón principal: en tema oscuro usa el azul de marca en lugar de blanco */
        html.module-dark-theme .support-backup-report-page .panel-button,
        html.module-dark-theme [data-backup-modal] .panel-button { background-color: #1A3A6B; color: #ffffff; }
        html.module-dark-theme .support-backup-report-page .panel-button:hover,
        html.module-dark-theme [data-backup-modal] .panel-button:hover { background-color: #234b88; }

        /* Puntos de estado (rojo, ámbar, verde o gris según el color de texto de cada tono) */
        .backup-dot {
            display: inline-block;
            height: 10px;
            width: 10px;
            flex-shrink: 0;
            border-radius: 9999px;
            background-color: currentColor;
        }

        /* Lista de perfiles */
        .backup-profile-item { transition: background-color 150ms ease; }
        .backup-profile-item:hover { background-color: var(--panel-subtle); }
        html.module-dark-theme [data-panel] .backup-profile-item:hover { background-color: var(--panel-hover); }
        .backup-profile-item.is-selected,
        .backup-profile-item.is-selected:hover { background-color: var(--panel-header); }
        .backup-profile-item.is-selected .backup-profile-name { color: var(--panel-accent); }
        .backup-profile-item:last-child { border-bottom-width: 0; }

        /* Destinatarios del envío manual */
        .backup-recipient { transition: background-color 150ms ease; }
        .backup-recipient:hover { background-color: var(--panel-hover); }
        .backup-recipient:has(input:checked) { background-color: color-mix(in srgb, var(--panel-accent) 8%, transparent); }
        .backup-recipient:has(input:checked):hover { background-color: color-mix(in srgb, var(--panel-accent) 14%, transparent); }
        [data-panel] input[type="checkbox"].backup-check {
            appearance: auto;
            -webkit-appearance: checkbox;
            height: 16px;
            width: 16px;
            flex-shrink: 0;
            accent-color: var(--panel-accent);
            background-image: none;
        }
        [data-panel] input[type="checkbox"].backup-check:focus,
        [data-panel] input[type="checkbox"].backup-check:focus-visible { outline: none; box-shadow: none; }

        /* Barra de avance y aviso del envío por correo */
        .backup-bar-track { background-color: var(--panel-track); }
        .backup-bar-fill { background-color: var(--panel-accent); }
        .backup-bar-ok { background-color: #16a34a; }
        .backup-bar-warn { background-color: #f59e0b; }
        html.module-dark-theme [data-panel] .backup-bar-ok { background-color: #4ade80; }
        html.module-dark-theme [data-panel] .backup-bar-warn { background-color: #fbbf24; }

        /* Incidencias del perfil */
        .backup-box-head { border-bottom: 1px solid; border-color: inherit; }
        .backup-incident-list { margin: 0; padding: 0; list-style: none; }
        .backup-incident-list > li + li { border-top: 1px solid var(--panel-border); }
        .panel-danger-box .backup-incident-list > li + li { border-top-color: var(--panel-danger-line); }

        /* Etiquetas de destinatarios del envío automático */
        .backup-chip {
            display: inline-flex;
            min-width: 0;
            max-width: 100%;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--panel-border);
            border-radius: 9999px;
            padding: 5px 15px;
            background-color: var(--panel-surface);
        }

        /* Título del servidor: solo visible al imprimir (en pantalla lo muestra la pestaña activa) */
        .support-backup-report-page .backup-print-title { display: none; }

        /* Celular: botones del encabezado a todo el ancho y textos largos sin recortar */
        @media (max-width: 639px) {
            .backup-actions { width: 100%; }
            .backup-actions > *,
            .backup-actions > div > button:first-child { width: 100%; }
            .backup-profile-item .truncate,
            .backup-status-line { white-space: normal; overflow: visible; text-overflow: clip; }
            .backup-detail-grid { gap: 15px; }
        }

        @media print {
            .support-backup-report-page [data-server-panel] { display: block !important; break-inside: avoid; }
            .support-backup-report-page [data-server-panel] + [data-server-panel] { border-top: 1px solid #d4d4d8; }
            .support-backup-report-page [data-profile-detail] { display: block !important; }
            .support-backup-report-page [data-detail-empty] { display: none !important; }
            .support-backup-report-page .backup-print-title { display: block; padding: 20px; }
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .support-backup-report-page { overflow: visible !important; }
            .support-backup-report-page .backup-scale-wrap { height: auto !important; }
            .support-backup-report-page .backup-scale { width: 100% !important; transform: none !important; }
            html.module-dark-theme .support-backup-report-page {
                --panel-page: #ffffff; --panel-surface: #ffffff; --panel-header: #f4f4f5; --panel-subtle: #fafafa;
                --panel-border: #d4d4d8; --panel-text: #000000; --panel-muted: #71717a; --panel-accent: #1A3A6B;
                --panel-danger: #b91c1c; --panel-danger-soft: #fef2f2; --panel-danger-line: #fecaca;
            }
        }
    </style>

    <div class="backup-scale-wrap w-full" :style="(!isCompact && scaledHeight) ? 'height:' + scaledHeight + 'px' : null">
        <div x-ref="scaled" class="backup-scale origin-top-left" :style="isCompact ? 'width:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
            <main class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
                <span
                    class="hidden"
                    wire:key="backup-scan-state-{{ $processedProfiles }}-{{ $totalProfiles }}-{{ $reading ? 'reading' : 'idle' }}"
                    x-init="$nextTick(() => { processed = {{ $processedProfiles }}; total = {{ $totalProfiles }}; scanning = {{ $reading ? 'true' : 'false' }}; statusMessage = @js($statusMessage); })"
                    aria-hidden="true"
                ></span>

                <!-- Cabecera y acciones -->
                <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                    <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                        <span class="panel-surface panel-border panel-text grid h-14 w-14 shrink-0 place-items-center rounded-xl border">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 18h16M6 15V9m4 6V5m4 10v-3m4 3V7"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Informe Técnico de Respaldos</h1>
                            <p class="mt-[10px] leading-tight">Supervisa servidores, perfiles e incidencias.</p>
                        </div>
                    </div>

                    <div class="no-print backup-actions flex flex-wrap items-center gap-[10px]">
                        <button type="button" wire:click="startReading" wire:loading.attr="disabled" :disabled="scanning" class="panel-button inline-flex h-11 items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                            Actualizar registros
                        </button>
                        <div class="relative flex items-center select-none">
                            <button type="button" @click="recipientsOpen = !recipientsOpen" :disabled="mailSending || scanning || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="panel-button-secondary inline-flex h-11 w-full select-none items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6.75A1.75 1.75 0 0 1 4.75 5h14.5A1.75 1.75 0 0 1 21 6.75v10.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Zm.5-.5L12 13l8.5-6.75"/></svg>
                                <span>Enviar reporte por correo</span>
                            </button>
                            <div x-show="recipientsOpen" x-cloak x-transition.opacity.duration.150ms x-on:click.outside="recipientsOpen = false" class="panel-surface panel-border fixed inset-x-[20px] bottom-[20px] z-50 whitespace-normal rounded-xl border p-[20px] shadow-[0_18px_50px_rgba(0,0,0,0.18)] sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:top-full sm:mt-[10px] sm:w-[370px]">
                                <div class="mb-[20px]">
                                    <strong class="panel-accent block leading-none">Enviar reporte a</strong>
                                    <span class="panel-muted mt-[10px] block leading-tight">Selecciona uno o varios destinatarios.</span>
                                </div>
                                <div class="flex flex-col gap-[10px]">
                                    @foreach ($recipientOptions as $email => $label)
                                        <label wire:key="backup-recipient-{{ $email }}" class="backup-recipient panel-border flex cursor-pointer select-none items-center gap-[15px] rounded-xl border px-[20px] py-[10px]">
                                            <input x-model="selectedRecipients" type="checkbox" value="{{ $email }}" class="backup-check">
                                            <span class="min-w-0"><strong class="block">{{ $label }}</strong><span class="panel-muted block truncate">{{ $email }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                                <p x-show="selectedRecipients.length === 0" x-cloak class="panel-tone-amber mt-[10px]">Selecciona al menos un correo.</p>
                                <button type="button" @click.prevent.stop="sendReport()" :disabled="mailSending || scanning || total === 0 || processed < total || selectedRecipients.length === 0" class="panel-button ml-auto mt-[20px] flex h-11 select-none items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">
                                    <span x-show="!mailSending">Enviar reporte</span>
                                    <span x-show="mailSending" x-cloak>Enviando...</span>
                                </button>
                            </div>
                        </div>
                        <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" :disabled="scanning || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="panel-button-secondary inline-flex h-11 items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                            Guardar PDF
                        </button>
                    </div>
                </section>

                <!-- Resumen del informe -->
                <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                    <header class="panel-header panel-border flex shrink-0 flex-col justify-between gap-[10px] border-b p-[20px] lg:flex-row lg:items-center lg:gap-[20px]">
                        <div class="min-w-0">
                            <h2 class="panel-accent leading-none">Resumen del informe</h2>
                            <p class="mt-[10px] leading-tight">Estado de los perfiles de respaldo revisados</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-[10px] leading-tight"><span class="backup-dot panel-tone-green"></span>Acceso exclusivo para administradores</div>
                    </header>
                    @php
                        $reportStats = [
                            ['Servidores con datos', $summary['servers']],
                            ['Perfiles revisados', $summary['profiles']],
                            ['Perfiles correctos', $summary['correct']],
                            ['Perfiles con error', $summary['errors']],
                            ['Empresas afectadas', $summary['companies']],
                            ['Sin datos / pendientes', $summary['pending']],
                        ];
                    @endphp
                    <div class="panel-border border-b p-[20px]" aria-live="polite">
                        <div class="backup-status-line block truncate" :class="mailStatus ? (mailSent ? 'panel-tone-green' : 'panel-tone-amber') : ''" x-text="mailSending ? 'Generando PDF y enviando el correo...' : (mailStatus || statusMessage)">{{ $statusMessage }}</div>
                        <div class="mt-[15px] flex items-center gap-[15px]">
                            <div class="backup-bar-track h-2 min-w-0 flex-1 overflow-hidden rounded-full">
                                <div x-show="!mailSending && !mailStatus" class="backup-bar-fill h-full rounded-full transition-all duration-300" :style="`width: ${total > 0 ? Math.min(100, (processed / total) * 100) : 0}%`" style="width: {{ $totalProfiles > 0 ? min(100, ($processedProfiles / $totalProfiles) * 100) : 0 }}%"></div>
                                <div x-show="mailSending" x-cloak class="backup-bar-fill h-full w-1/2 animate-pulse rounded-full"></div>
                                <div x-show="!mailSending && mailStatus" x-cloak class="h-full rounded-full transition-all duration-300" :class="mailSent ? 'w-full backup-bar-ok' : 'w-1/3 backup-bar-warn'"></div>
                            </div>
                            <div class="flex shrink-0 items-center gap-[15px]">
                                <span x-show="!mailSending && !mailStatus" class="tabular-nums"><span x-text="processed">{{ $processedProfiles }}</span> / <span x-text="total">{{ $totalProfiles }}</span></span>
                                <span x-show="mailSending" x-cloak>Procesando</span>
                                <span x-show="!mailSending && mailStatus" x-cloak :class="mailSent ? 'panel-tone-green' : 'panel-tone-amber'" x-text="mailSent ? '100%' : 'Aviso'"></span>
                            </div>
                        </div>
                        <div class="panel-muted backup-status-line mt-[10px] min-w-0 sm:truncate" x-text="mailSending || mailStatus ? 'El reporte se envía a los destinatarios seleccionados.' : (scanning && currentServer ? `Lectura de solo acceso, perfil por perfil. Cargando ${currentServer} · ${currentProfile}…` : 'Lectura de solo acceso, perfil por perfil.')">Lectura de solo acceso, perfil por perfil.</div>
                    </div>
                    <div class="panel-line grid grid-cols-2 gap-px sm:grid-cols-3">
                        @foreach ($reportStats as $statIndex => [$label, $value])
                            <div class="panel-surface flex min-w-0 items-start gap-[15px] p-[15px] sm:items-center sm:p-[20px]">
                                <span class="panel-surface panel-border panel-text hidden h-10 w-10 shrink-0 place-items-center rounded-xl border sm:grid">
                                    @switch($statIndex)
                                        @case(0)
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M5 6.5h14v5H5v-5Zm0 6h14v5H5v-5ZM8 9h.01M8 15h.01"/></svg>
                                            @break
                                        @case(1)
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M8 4h8m-9 3h10a2 2 0 0 1 2 2v9H5V9a2 2 0 0 1 2-2Zm2 4h6m-6 3h4"/></svg>
                                            @break
                                        @case(2)
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m5 12 4 4L19 6"/></svg>
                                            @break
                                        @case(3)
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8v5m0 3h.01M10.3 4.7 3.8 16a2 2 0 0 0 1.7 3h13a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z"/></svg>
                                            @break
                                        @case(4)
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 19h16M6 19V8h5v11m2 0V4h5v15M8 11h1m6-4h1"/></svg>
                                            @break
                                        @default
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    @endswitch
                                </span>
                                <div class="min-w-0">
                                    <span class="block leading-none tabular-nums {{ $statIndex === 3 && $value > 0 ? 'panel-tone-red' : 'panel-accent' }}">{{ $value }}</span>
                                    <span class="mt-[10px] block leading-tight">{{ $label }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <!-- Envío automático semanal -->
                @php
                    $weekly = (array) config('backup-reports.weekly', []);
                    $weeklyEnabled = (bool) ($weekly['enabled'] ?? false);
                    $weeklyRecipients = array_values((array) ($weekly['recipients'] ?? []));
                    $weeklyDayNumber = (int) ($weekly['day'] ?? 1);
                    $weeklyTime = (string) ($weekly['time'] ?? '09:00');
                    $weekDays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
                    $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                    $weeklyDayLabel = $weekDays[$weeklyDayNumber] ?? 'lunes';
                    $hasTimeParts = (bool) preg_match('/^(\d{1,2}):(\d{2})$/', $weeklyTime, $timeParts);
                    $weeklyTimeLabel = $hasTimeParts ? ((int) $timeParts[1]).':'.$timeParts[2] : $weeklyTime;
                    $weeklyZone = null;
                    $nextRunLabel = null;
                    $lastWeeklyLabel = null;
                    try {
                        $weeklyZone = new DateTimeZone((string) config('support.timezone', 'America/Mexico_City'));
                        $nowInZone = new DateTimeImmutable('now', $weeklyZone);
                        $nextRun = $nowInZone->setTime($hasTimeParts ? (int) $timeParts[1] : 9, $hasTimeParts ? (int) $timeParts[2] : 0);
                        $nextRun = $nextRun->modify('+'.((($weeklyDayNumber - (int) $nowInZone->format('w')) + 7) % 7).' days');
                        if ($nextRun <= $nowInZone) {
                            $nextRun = $nextRun->modify('+7 days');
                        }
                        $nextRunLabel = $weekDays[(int) $nextRun->format('w')].' '.$nextRun->format('j').' de '.$monthNames[(int) $nextRun->format('n') - 1];
                    } catch (Throwable) {
                    }
                    try {
                        $lastWeekly = \Illuminate\Support\Facades\Cache::get('backup-reports.weekly-last-run');
                    } catch (Throwable) {
                        $lastWeekly = null;
                    }
                    $lastWeekly = is_array($lastWeekly) ? $lastWeekly : null;
                    if ($lastWeekly && $weeklyZone) {
                        try {
                            $lastWeeklyLabel = (new DateTimeImmutable((string) ($lastWeekly['at'] ?? 'now')))->setTimezone($weeklyZone)->format('d/m/Y H:i');
                        } catch (Throwable) {
                        }
                    }
                @endphp
                <section class="panel-surface panel-border overflow-hidden rounded-xl border" data-weekly-report>
                    <header class="panel-header panel-border flex shrink-0 flex-col justify-between gap-[10px] border-b p-[20px] lg:flex-row lg:items-center lg:gap-[20px]">
                        <div class="min-w-0">
                            <h2 class="panel-accent leading-none">Envío automático</h2>
                            <p class="mt-[10px] leading-tight">Cada {{ $weeklyDayLabel }} a las {{ $weeklyTimeLabel }} (hora de México) se lee y se envía solo, sin abrir esta pantalla.</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-[10px] leading-tight"><span class="backup-dot {{ $weeklyEnabled ? 'panel-tone-green' : 'panel-tone-gray' }}"></span>{{ $weeklyEnabled ? 'Activo' : 'Desactivado' }}</div>
                    </header>
                    <div class="panel-line grid grid-cols-1 gap-px md:grid-cols-3">
                        <div class="panel-surface flex min-w-0 items-center gap-[15px] p-[20px]">
                            <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="panel-accent block leading-tight">Cada {{ $weeklyDayLabel }}, {{ $weeklyTimeLabel }}</span>
                                <span class="mt-[10px] block leading-tight">Programación</span>
                            </div>
                        </div>
                        <div class="panel-surface flex min-w-0 items-center gap-[15px] p-[20px]">
                            <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="panel-accent block leading-tight">{{ $weeklyEnabled ? ($nextRunLabel ?? 'Pendiente') : 'Desactivado' }}</span>
                                <span class="mt-[10px] block leading-tight">Próximo envío</span>
                            </div>
                        </div>
                        <div class="panel-surface flex min-w-0 items-center gap-[15px] p-[20px]">
                            <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m5 12 4 4L19 6"/></svg>
                            </span>
                            <div class="min-w-0">
                                @if ($lastWeekly)
                                    <span class="block leading-tight {{ ($lastWeekly['sent'] ?? false) ? 'panel-tone-green' : 'panel-tone-red' }}">{{ ($lastWeekly['sent'] ?? false) ? 'Enviado' : 'No se envió' }}@if ($lastWeeklyLabel) · {{ $lastWeeklyLabel }}@endif</span>
                                    <span class="mt-[10px] block leading-tight">Último envío automático</span>
                                    @if (! ($lastWeekly['sent'] ?? false) && filled($lastWeekly['status'] ?? null))
                                        <span class="panel-muted mt-[10px] block leading-tight">{{ $lastWeekly['status'] }}</span>
                                    @endif
                                @else
                                    <span class="panel-accent block leading-tight">Sin ejecutarse</span>
                                    <span class="mt-[10px] block leading-tight">Último envío automático</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="panel-border flex flex-col gap-[15px] border-t p-[20px]">
                        <div class="flex flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                            <span class="panel-muted">Destinatarios del lunes</span>
                            <div class="flex min-w-0 flex-wrap items-center gap-[10px]">
                                @forelse ($weeklyRecipients as $weeklyEmail)
                                    <span class="backup-chip">
                                        <svg class="panel-muted h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6.75A1.75 1.75 0 0 1 4.75 5h14.5A1.75 1.75 0 0 1 21 6.75v10.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Zm.5-.5L12 13l8.5-6.75"/></svg>
                                        <span class="min-w-0 truncate">{{ $weeklyEmail }}</span>
                                    </span>
                                @empty
                                    <span class="panel-tone-amber">Sin destinatarios configurados</span>
                                @endforelse
                            </div>
                        </div>
                        <p class="panel-muted leading-tight">El envío manual sigue disponible cuando lo necesites: usa «Enviar reporte por correo» y elige los destinatarios.</p>
                    </div>
                </section>

                @if (count($servers) > 0)
                    @php
                        $serverMeta = [];
                        $selectableProfiles = [];
                        foreach ($servers as $serverIndex => $server) {
                            $serverMeta[$serverIndex] = [
                                'available' => collect($server['profiles'])->where('available', true)->count(),
                                'errors' => collect($server['profiles'])->filter(fn ($profile) => $profile['available'] && ($profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []))->count(),
                                'pending' => collect($server['profiles'])->where('done', false)->count(),
                            ];
                            foreach (array_keys($server['profiles']) as $profileIndex) {
                                $selectableProfiles[] = $server['key'].':'.$profileIndex;
                            }
                        }
                        $selectableServers = collect($servers)->pluck('key')->values()->all();
                    @endphp
                    <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <span
                            class="hidden"
                            wire:key="backup-selection-{{ md5(json_encode([$selectableServers, $selectableProfiles])) }}"
                            x-init="$nextTick(() => { const servers = @js($selectableServers); const profiles = @js($selectableProfiles); if (!servers.includes(selServer)) { selServer = servers[0] ?? ''; selProfile = ''; } if (selProfile && !profiles.includes(selProfile)) { selProfile = ''; } })"
                            aria-hidden="true"
                        ></span>

                        <div class="no-print panel-header panel-border flex flex-wrap items-center gap-x-[20px] gap-y-[15px] border-b p-[20px]" role="tablist" aria-label="Servidores supervisados">
                            @foreach ($servers as $serverIndex => $server)
                                @php $meta = $serverMeta[$serverIndex]; @endphp
                                <button
                                    type="button"
                                    role="tab"
                                    wire:key="backup-server-tab-{{ $server['key'] }}"
                                    @click="selServer = @js($server['key']); selProfile = ''"
                                    :aria-selected="selServer === @js($server['key']) ? 'true' : 'false'"
                                    class="panel-tab inline-flex max-w-full items-center gap-[10px] bg-transparent p-0 leading-none focus:outline-none focus:ring-0"
                                >
                                    <span class="backup-dot {{ $meta['errors'] ? 'panel-tone-red' : ($meta['pending'] ? 'panel-tone-amber' : ($meta['available'] === 0 ? 'panel-tone-gray' : 'panel-tone-green')) }}"></span>
                                    <span class="min-w-0 truncate">{{ $server['name'] }}</span>
                                    @if ($meta['errors'] > 0)
                                        <span class="panel-surface panel-tone-red shrink-0 rounded-full border px-[10px] py-[3px] leading-none tabular-nums" style="border-color: currentColor" title="{{ $meta['errors'] }} {{ $meta['errors'] === 1 ? 'perfil con error' : 'perfiles con error' }}">{{ $meta['errors'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        @foreach ($servers as $serverIndex => $server)
                            @php
                                $meta = $serverMeta[$serverIndex];
                                $profileCount = count($server['profiles']);
                                $serverStatus = $meta['pending'] ? 'Leyendo' : ($meta['available'] === 0 ? 'Sin datos' : ($meta['errors'] ? 'Con incidencias' : 'Todo realizado'));
                                $serverTone = $meta['pending'] ? 'panel-tone-amber' : ($meta['available'] === 0 ? 'panel-tone-gray' : ($meta['errors'] ? 'panel-tone-red' : 'panel-tone-green'));
                                $rows = [];
                                foreach ($server['profiles'] as $profileIndex => $profile) {
                                    $isBad = $profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== [];
                                    $hasError = $profile['available'] && $isBad;
                                    $hasIncidents = ($profile['failedFiles'] ?? []) !== [] || $profile['errors'] !== [] || $profile['issues'] !== [] || ($profile['message'] && $profile['done'] && ! $profile['available']);
                                    $rows[$profileIndex] = [
                                        'key' => $server['key'].':'.$profileIndex,
                                        'bad' => $isBad,
                                        'error' => $hasError,
                                        'dot' => $hasError ? 'panel-tone-red' : ($profile['available'] ? 'panel-tone-green' : ($profile['done'] ? 'panel-tone-gray' : 'panel-tone-amber')),
                                        'result' => $profile['available'] ? ($isBad ? 'Error' : ($profile['processed'] === '0/0' ? 'Sin cambios' : 'Todo realizado')) : ($profile['done'] ? 'No disponible' : 'Pendiente'),
                                        'date' => $profile['available'] ? ($profile['date'] ?: 'Sin fecha') : ($profile['done'] ? 'Sin datos' : 'Leyendo…'),
                                        'processed' => $profile['available'] ? ($profile['processed'] ?: '—') : '—',
                                        'hasIncidents' => $hasIncidents,
                                        'incidentCount' => (($profile['failedFiles'] ?? []) !== [] ? count($profile['failedFiles']) : count($profile['errors'])) + count($profile['issues']) + (($profile['message'] && ! $profile['available']) ? 1 : 0),
                                    ];
                                }
                            @endphp
                            <div wire:key="backup-server-panel-{{ $server['key'] }}" x-show="selServer === @js($server['key'])" x-cloak role="tabpanel" data-server-panel>
                                <h2 class="backup-print-title panel-accent leading-none">{{ $server['name'] }}</h2>

                                <div class="panel-border flex flex-col items-start justify-between gap-[10px] border-b px-[20px] py-[15px] sm:flex-row sm:items-center sm:gap-[20px]">
                                    <div class="flex min-w-0 flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                                        <span class="flex min-w-0 items-center gap-[10px] leading-none">
                                            <span class="shrink-0">{{ $server['ip'] }}</span>
                                            <span class="panel-muted" aria-hidden="true">·</span>
                                            <span class="panel-muted truncate font-mono">{{ $server['share'] }}</span>
                                        </span>
                                        <span class="inline-flex items-center gap-[10px] leading-none"><span class="backup-dot {{ $serverTone }}"></span>{{ $serverStatus }}</span>
                                    </div>
                                    <div class="no-print flex shrink-0 flex-wrap items-center gap-x-[20px] gap-y-[5px]">
                                        <button type="button" wire:click="openServer({{ $serverIndex }})" class="panel-action inline-flex items-center gap-[10px] py-[10px] leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M9 11l7.6-7.6a2 2 0 0 1 2.8 0l1.2 1.2a2 2 0 0 1 0 2.8L13 15l-4 1 1-4ZM5 19h14"/></svg>Editar servidor</button>
                                        <button type="button" wire:click="addProfile({{ $serverIndex }})" class="panel-action inline-flex items-center gap-[10px] py-[10px] leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14"/></svg>Agregar perfil</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 xl:grid-cols-5">
                                    <div class="min-w-0 xl:col-span-2">
                                        <header class="panel-header panel-border border-b p-[20px]">
                                            <h2 class="panel-accent leading-none">Perfiles</h2>
                                            <p class="mt-[10px] flex flex-wrap items-center gap-x-[10px] leading-tight">
                                                <span>{{ $profileCount }} {{ $profileCount === 1 ? 'perfil configurado' : 'perfiles configurados' }}</span>
                                                @if ($meta['errors'] > 0)
                                                    <span class="panel-muted" aria-hidden="true">·</span>
                                                    <span class="panel-tone-red">{{ $meta['errors'] }} con error</span>
                                                @endif
                                            </p>
                                        </header>
                                        @if ($profileCount === 0)
                                            <p class="p-[20px] leading-[25px]">Este servidor aún no tiene perfiles. Usa «Agregar perfil» para crear el primero.</p>
                                        @else
                                            <ul class="m-0 list-none p-0">
                                                @foreach ($server['profiles'] as $profileIndex => $profile)
                                                    @php $row = $rows[$profileIndex]; @endphp
                                                    <li
                                                        wire:key="backup-profile-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}"
                                                        :class="selProfile === @js($row['key']) ? 'is-selected' : ''"
                                                        class="backup-profile-item panel-border relative flex items-stretch border-b"
                                                    >
                                                        <span x-show="selProfile === @js($row['key'])" x-cloak class="absolute inset-y-0 left-0 w-[2px]" style="background-color: var(--panel-accent)" aria-hidden="true"></span>
                                                        <button
                                                            type="button"
                                                            @click="selectProfile(@js($row['key']))"
                                                            :aria-pressed="selProfile === @js($row['key']) ? 'true' : 'false'"
                                                            class="min-w-0 flex-1 px-[20px] py-[15px] text-left focus:outline-none focus:ring-0"
                                                        >
                                                            <span class="flex items-center justify-between gap-[15px]">
                                                                <span class="flex min-w-0 items-center gap-[10px] leading-none"><span class="backup-dot {{ $row['dot'] }}"></span><span class="backup-profile-name truncate">{{ $profile['name'] }}</span></span>
                                                                <span class="shrink-0 leading-none {{ $row['error'] ? 'panel-tone-red' : '' }}">{{ $row['result'] }}</span>
                                                            </span>
                                                            <span class="panel-muted mt-[10px] flex min-w-0 items-center gap-[10px] pl-[20px] leading-none"><span class="truncate">{{ $row['date'] }}</span><span aria-hidden="true">·</span><span class="shrink-0 font-mono">{{ $row['processed'] }}</span></span>
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    <div class="panel-border min-w-0 border-t xl:col-span-3 xl:border-l xl:border-t-0">
                                        <div wire:key="backup-detail-empty-{{ $server['key'] }}" x-show="!selProfile" x-cloak data-detail-empty>
                                            <header class="panel-header panel-border border-b p-[20px]">
                                                <h2 class="panel-accent leading-none">Detalle del perfil</h2>
                                                <p class="mt-[10px] leading-tight">Ningún perfil seleccionado</p>
                                            </header>
                                            <div class="grid min-h-[260px] place-items-center p-[20px] text-center">
                                                <div class="max-w-[380px]">
                                                    <span class="panel-surface panel-border panel-text mx-auto grid h-14 w-14 place-items-center rounded-xl border">
                                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7m6-4 3 3m0 0-3 3m3-3h-6"/></svg>
                                                    </span>
                                                    <p class="panel-accent mt-[20px] leading-none">Selecciona un perfil de la lista</p>
                                                    <p class="mt-[10px] leading-[25px]">Aquí verás sus incidencias y el detalle de su última ejecución.</p>
                                                    @if ($meta['errors'] > 0)
                                                        <p class="panel-tone-red mt-[20px] leading-[25px]">{{ $meta['errors'] }} {{ $meta['errors'] === 1 ? 'perfil con error' : 'perfiles con error' }} en este servidor, marcados en rojo.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @foreach ($server['profiles'] as $profileIndex => $profile)
                                            @php $row = $rows[$profileIndex]; @endphp
                                            <div wire:key="backup-profile-detail-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}" x-show="selProfile === @js($row['key'])" x-cloak data-profile-detail="{{ $row['key'] }}">
                                                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                                                    <div class="min-w-0">
                                                        <h2 class="panel-accent truncate leading-none">{{ $profile['name'] }}</h2>
                                                        <p class="mt-[10px] truncate leading-tight">{{ $server['name'] }}</p>
                                                    </div>
                                                    <div class="flex shrink-0 flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                                                        <span class="inline-flex items-center gap-[10px] leading-none {{ $row['error'] ? 'panel-tone-red' : '' }}"><span class="backup-dot {{ $row['dot'] }}"></span>{{ $row['result'] }}</span>
                                                        <button type="button" wire:click="openProfile({{ $serverIndex }}, {{ $profileIndex }})" class="no-print panel-action inline-flex items-center gap-[10px] leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M9 11l7.6-7.6a2 2 0 0 1 2.8 0l1.2 1.2a2 2 0 0 1 0 2.8L13 15l-4 1 1-4ZM5 19h14"/></svg>Editar perfil</button>
                                                    </div>
                                                </header>
                                                <div class="p-[20px]">
                                                    <div class="backup-detail-grid grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                                        <div class="min-w-0"><span class="panel-muted block leading-none">Última ejecución</span><span class="mt-[10px] block leading-tight">{{ $row['date'] }}</span></div>
                                                        <div class="min-w-0"><span class="panel-muted block leading-none">Hecho</span><span class="mt-[10px] block truncate font-mono leading-tight">{{ $row['processed'] }}</span></div>
                                                        <div class="min-w-0"><span class="panel-muted block leading-none">Incidencias</span><span class="mt-[10px] block leading-tight tabular-nums">{{ $row['hasIncidents'] ? $row['incidentCount'] : 0 }}</span></div>
                                                    </div>

                                                    @if ($row['hasIncidents'])
                                                        <div class="{{ $row['bad'] ? 'panel-danger-box' : 'panel-subtle panel-border' }} mt-[20px] overflow-hidden rounded-xl border">
                                                            <div class="backup-box-head flex items-center justify-between gap-[15px] px-[20px] py-[15px]">
                                                                <span class="inline-flex items-center gap-[10px] leading-none">
                                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8v5m0 3h.01M10.3 4.7 3.8 16a2 2 0 0 0 1.7 3h13a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z"/></svg>
                                                                    Incidencias
                                                                </span>
                                                                <span class="panel-surface shrink-0 rounded-full border px-[10px] py-[3px] leading-none tabular-nums" style="border-color: currentColor">{{ $row['incidentCount'] }}</span>
                                                            </div>
                                                            <ul class="backup-incident-list">
                                                                @if (($profile['failedFiles'] ?? []) !== [])
                                                                    @foreach ($profile['failedFiles'] as $file)
                                                                        <li class="break-all px-[20px] py-[10px] font-mono">{{ is_array($file) ? ($file['path'] ?? '') : $file }}</li>
                                                                    @endforeach
                                                                @else
                                                                    @foreach ($profile['errors'] as $error)
                                                                        <li class="break-all px-[20px] py-[10px]">{{ $error }}</li>
                                                                    @endforeach
                                                                @endif
                                                                @foreach ($profile['issues'] as $issue)
                                                                    <li class="break-words px-[20px] py-[10px]">{{ $issue }}</li>
                                                                @endforeach
                                                                @if ($profile['message'] && ! $profile['available'])
                                                                    <li class="break-words px-[20px] py-[10px]">{{ $profile['message'] }}</li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    @else
                                                        <div class="panel-subtle panel-border mt-[20px] flex items-start gap-[10px] rounded-xl border p-[20px] leading-[25px]">
                                                            <span class="backup-dot {{ $row['dot'] }} mt-[8px]"></span>
                                                            <span class="min-w-0">{{ $profile['available'] ? ($row['bad'] ? 'El perfil terminó con error, pero no se registró el detalle.' : 'Este perfil no tiene incidencias.') : ($profile['done'] ? 'No hay datos disponibles de este perfil.' : 'Leyendo este perfil…') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif
            </main>
        </div>
    </div>

    <!-- Control de zoom y pantalla completa (igual que en Panel general, Administración y Reloj Checador) -->
    <div class="no-print panel-surface panel-border fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] lg:flex">
        <button type="button" @click="setScale(viewScale - 5)" aria-label="Alejar vista" title="Alejar vista" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" :value="viewScale" @input="setScale($event.target.value)" class="h-1.5 w-[130px] cursor-pointer" style="accent-color: var(--panel-accent)" aria-label="Ajustar tamaño de la vista">
        <button type="button" @click="setScale(viewScale + 5)" aria-label="Acercar vista" title="Acercar vista" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right tabular-nums" x-text="viewScale + '%'">90%</span>
        <span class="panel-line h-5 w-px"></span>
        <button type="button" @click="toggleFullscreen($root)" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver el informe en pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    {{-- Formulario para agregar o editar un perfil --}}
    @if ($showProfileEditor)
        @teleport('body')
            <div wire:key="profile-editor">
                <div
                    x-data="{ visible: true, pressedOnBackdrop: false }"
                    x-show="visible"
                    @pointerdown="pressedOnBackdrop = ($event.target === $el)"
                    @click.self="if (pressedOnBackdrop) { $wire.$set('showProfileEditor', false) } pressedOnBackdrop = false"
                    @keydown.escape.window="$wire.$set('showProfileEditor', false)"
                    @wheel.self.prevent
                    @touchmove.self.prevent
                    class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/50 p-[20px]"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="backup-profile-editor-title"
                    data-backup-modal="profile-editor"
                >
                    <div data-panel class="panel-surface panel-border relative flex w-full flex-col overflow-hidden rounded-xl border shadow-2xl" style="max-width: 620px; height: min(620px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); overscroll-behavior: contain;">
                        <form wire:submit="saveProfile" class="flex min-h-0 flex-1 flex-col">
                            <header class="panel-header panel-border flex flex-shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                <div class="flex min-w-0 items-center gap-[15px]">
                                    <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 id="backup-profile-editor-title" class="panel-accent truncate leading-none">{{ $editingProfile === null ? 'Agregar perfil' : 'Editar perfil' }}</h3>
                                        <p class="mt-[10px] leading-tight sm:truncate">Los ajustes solo afectan el informe actual.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showProfileEditor', false)" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0" aria-label="Cerrar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </header>

                            <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                    <div class="flex flex-col gap-[20px] p-[20px]">
                                        <div class="panel-border rounded-xl border p-[20px]">
                                            <h4 class="panel-accent text-[15px] font-normal leading-none">Datos del perfil</h4>
                                            <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                                <div class="min-w-0">
                                                    <label for="backup-profile-name" class="mb-[10px] block">Perfil *</label>
                                                    <input id="backup-profile-name" type="text" wire:model="profileForm.name" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                    @error('profileForm.name')<p class="panel-tone-red mt-[10px]">{{ $message }}</p>@enderror
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-log" class="mb-[10px] block">Nombre del log</label>
                                                    <input id="backup-profile-log" type="text" wire:model="profileForm.log" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-date" class="mb-[10px] block">Fecha y hora</label>
                                                    <input id="backup-profile-date" type="text" wire:model="profileForm.date" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-processed" class="mb-[10px] block">Hecho</label>
                                                    <input id="backup-profile-processed" type="text" wire:model="profileForm.processed" placeholder="0/0" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="panel-border rounded-xl border p-[20px]">
                                            <h4 class="panel-accent text-[15px] font-normal leading-none">Incidencias del perfil</h4>
                                            <div class="mt-[20px] grid grid-cols-1 gap-[20px]">
                                                <div class="min-w-0">
                                                    <label for="backup-profile-errors" class="mb-[10px] block">Empresas con error</label>
                                                    <textarea id="backup-profile-errors" wire:model="profileForm.errors" rows="4" placeholder="Una empresa por línea" class="panel-field block w-full rounded-xl border px-[15px] py-[12px] focus:ring-0"></textarea>
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-issues" class="mb-[10px] block">Incidencias generales</label>
                                                    <textarea id="backup-profile-issues" wire:model="profileForm.issues" rows="3" placeholder="Ej. Directorio destino inválido" class="panel-field block w-full rounded-xl border px-[15px] py-[12px] focus:ring-0"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="panel-border rounded-xl border p-[20px]">
                                            <h4 class="panel-accent text-[15px] font-normal leading-none">Informe</h4>
                                            <div class="mt-[20px] grid grid-cols-1 gap-[20px]">
                                                <label class="flex cursor-pointer items-center gap-[10px]">
                                                    <input wire:model="profileForm.available" type="checkbox" class="backup-check">
                                                    <span>Incluir este perfil en el PDF</span>
                                                </label>
                                                <details class="panel-border rounded-xl border px-[20px] py-[15px]">
                                                    <summary class="panel-accent cursor-pointer">Datos técnicos</summary>
                                                    <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-source" class="mb-[10px] block">Origen</label>
                                                            <input id="backup-profile-source" type="text" wire:model="profileForm.source" placeholder="Origen" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-destination" class="mb-[10px] block">Destino</label>
                                                            <input id="backup-profile-destination" type="text" wire:model="profileForm.destination" placeholder="Destino" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-duration" class="mb-[10px] block">Duración</label>
                                                            <input id="backup-profile-duration" type="text" wire:model="profileForm.duration" placeholder="Duración" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>

                            <footer class="panel-border flex flex-shrink-0 flex-wrap items-center justify-end gap-[10px] border-t p-[20px]">
                                @if ($editingProfile !== null)<button type="button" wire:click="deleteProfile" wire:confirm="¿Eliminar este perfil del informe?" class="panel-action-danger mr-auto inline-flex h-11 items-center gap-[10px] leading-none focus:outline-none focus:ring-0">Eliminar</button>@endif
                                <button type="button" wire:click="$set('showProfileEditor', false)" class="panel-button-secondary inline-flex h-11 min-w-28 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0">Cancelar</button>
                                <button type="submit" class="panel-button inline-flex h-11 min-w-28 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0">Guardar</button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    {{-- Formulario para editar un servidor --}}
    @if ($showServerEditor)
        @teleport('body')
            <div wire:key="server-editor">
                <div
                    x-data="{ visible: true, pressedOnBackdrop: false }"
                    x-show="visible"
                    @pointerdown="pressedOnBackdrop = ($event.target === $el)"
                    @click.self="if (pressedOnBackdrop) { $wire.$set('showServerEditor', false) } pressedOnBackdrop = false"
                    @keydown.escape.window="$wire.$set('showServerEditor', false)"
                    @wheel.self.prevent
                    @touchmove.self.prevent
                    class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/50 p-[20px]"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="backup-server-editor-title"
                    data-backup-modal="server-editor"
                >
                    <div data-panel class="panel-surface panel-border relative flex w-full flex-col overflow-hidden rounded-xl border shadow-2xl" style="max-width: 620px; max-height: calc(100dvh - 40px); overscroll-behavior: contain;">
                        <form wire:submit="saveServer" class="flex min-h-0 flex-1 flex-col">
                            <header class="panel-header panel-border flex flex-shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                <div class="flex min-w-0 items-center gap-[15px]">
                                    <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 6.5h14v5H5v-5Zm0 6h14v5H5v-5ZM8 9h.01M8 15h.01"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 id="backup-server-editor-title" class="panel-accent truncate leading-none">Editar servidor</h3>
                                        <p class="mt-[10px] leading-tight sm:truncate">Define el nombre y el recurso compartido donde están los logs.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showServerEditor', false)" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0" aria-label="Cerrar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </header>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <div class="p-[20px]">
                                    <div class="panel-border rounded-xl border p-[20px]">
                                        <h4 class="panel-accent text-[15px] font-normal leading-none">Datos del servidor</h4>
                                        <div class="mt-[20px] grid grid-cols-1 gap-[20px]">
                                            <div class="min-w-0">
                                                <label for="backup-server-name" class="mb-[10px] block">Servidor *</label>
                                                <input id="backup-server-name" type="text" wire:model="serverForm.name" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                @error('serverForm.name')<p class="panel-tone-red mt-[10px]">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="min-w-0">
                                                <label for="backup-server-ip" class="mb-[10px] block">Dirección IP</label>
                                                <input id="backup-server-ip" type="text" wire:model="serverForm.ip" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                            </div>
                                            <div class="min-w-0">
                                                <label for="backup-server-share" class="mb-[10px] block">Carpeta compartida</label>
                                                <input id="backup-server-share" type="text" wire:model="serverForm.share" placeholder="\\SERVIDOR\log" class="panel-field block h-11 w-full rounded-xl border px-[15px] font-mono focus:ring-0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <footer class="panel-border flex flex-shrink-0 flex-wrap items-center justify-end gap-[10px] border-t p-[20px]">
                                <button type="button" wire:click="deleteServer" wire:confirm="¿Eliminar este servidor y sus perfiles del informe?" class="panel-action-danger mr-auto inline-flex h-11 items-center gap-[10px] leading-none focus:outline-none focus:ring-0">Eliminar</button>
                                <button type="button" wire:click="$set('showServerEditor', false)" class="panel-button-secondary inline-flex h-11 min-w-28 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0">Cancelar</button>
                                <button type="submit" class="panel-button inline-flex h-11 min-w-28 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0">Guardar</button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
