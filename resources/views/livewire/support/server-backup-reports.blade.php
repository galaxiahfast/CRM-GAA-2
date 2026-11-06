{{-- Reporte de respaldos: mismo diseño que Panel general, Administración, Reloj Checador y Reportes de soporte
     (tarjetas con cabecera gris, líneas de 1px, texto de 15px, separaciones de 20px, escala de vista al 90%
     ajustable y tema oscuro con fondo negro). Solo cambia la presentación; la lógica vive en
     App\Livewire\Support\ServerBackupReports y el envío de los lunes en App\Services\Support\WeeklyBackupReportService. --}}
@php
    // Perfil que se abre al elegir cada servidor: el primero con error y, si no hay, el primero de la lista
    $defaultProfileKeys = [];
    foreach ($servers as $defaultServer) {
        $firstKey = null;
        $firstErrorKey = null;
        foreach ($defaultServer['profiles'] as $defaultIndex => $defaultProfile) {
            $firstKey ??= $defaultServer['key'].':'.$defaultIndex;
            if ($firstErrorKey === null && $defaultProfile['available'] && ($defaultProfile['failed'] || $defaultProfile['errors'] !== [] || $defaultProfile['issues'] !== [])) {
                $firstErrorKey = $defaultServer['key'].':'.$defaultIndex;
            }
        }
        $defaultProfileKeys[$defaultServer['key']] = $firstErrorKey ?? $firstKey ?? '';
    }
    $initialProfileKey = $defaultProfileKeys[$servers[0]['key'] ?? ''] ?? '';
    $serverIndexByKey = [];
    foreach ($servers as $serverPosition => $serverItem) {
        $serverIndexByKey[$serverItem['key']] = $serverPosition;
    }
@endphp
<div
    data-panel
    class="support-backup-report-page relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 overflow-hidden text-[15px]"
    x-data="{
        viewScale: 90,
        isFullscreen: false,
        isCompact: window.innerWidth / window.appAutoScale() < 1024,
        scaledHeight: null,
        observer: null,
        selServer: @js($servers[0]['key'] ?? ''),
        selProfile: @js($initialProfileKey),
        serverIndexes: @js($serverIndexByKey),
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
        scanStartedAt: 0,
        lastProgressAt: 0,
        nowTick: Date.now(),
        tickTimer: null,
        lastDuration: 0,
        segmentMs: 5000,
        live: false,
        liveRun: false,
        liveStartedAt: 0,
        cums: [],
        segIndex: 0,
        segStart: 0,
        segFrac: 0,
        barW: 0,
        shownPct: 0,
        shownCount: 0,
        realDone: 0,
        readAvg: 0,
        readSamples: 0,
        readStartedAt: 0,
        pendingRead: false,
        readingMessage: '',
        etaText: '',
        etaAt: 0,
        frameAt: 0,
        raf: null,
        watchdog: null,
        segTimes: [],
        segDone: 0,
        segElapsed: 0,
        readTimer: null,
        lastSeen: 0,
        get busy() { return this.scanning || this.live; },
        init() {
            this.lastSeen = Number(this.processed) || 0;
            this.$watch('scanning', (value) => {
                clearInterval(this.tickTimer);
                this.tickTimer = null;
                if (value) {
                    this.scanStartedAt = this.lastProgressAt = this.nowTick = Date.now();
                    this.lastDuration = 0;
                    this.tickTimer = setInterval(() => { this.nowTick = Date.now(); }, 1000);
                } else if (this.scanStartedAt && !this.live) {
                    this.lastDuration = Date.now() - this.scanStartedAt;
                }
            });
            this.$watch('processed', () => { this.lastProgressAt = this.nowTick = Date.now(); });
            try {
                const saved = Number(null);
                if (saved >= 70 && saved <= 100) this.viewScale = saved;
            } catch (error) {}
            this.$nextTick(() => {
                this.observer = new ResizeObserver(() => this.syncHeight());
                this.observer.observe(this.$refs.scaled);
                this.syncHeight();
            });
        },
        destroy() {
            this.observer?.disconnect();
            clearInterval(this.tickTimer);
            this.stopLive();
        },
        // Puntos de la barra: uno por servidor, ubicado donde termina la lectura de sus perfiles
        dotEls() {
            return [...this.$root.querySelectorAll('[data-backup-milestone]')];
        },
        dotCums() {
            return this.dotEls().map((dot) => Number(dot.dataset.cum));
        },
        dotState(cum, index) {
            if (this.live) return index < this.segIndex ? 'passed' : (index === this.segIndex ? 'current' : 'pending');
            return this.processed >= cum ? 'passed' : 'pending';
        },
        // Barra fluida: se llena de forma continua, un tramo por servidor, y tarda al menos segmentMs en cada uno aunque la lectura real vaya a ráfagas.
        // La lectura real solo la frena (nunca pasa del 97 % de un tramo hasta que ese servidor termina) y no empieza el siguiente servidor hasta que la barra llega al punto.
        trackDots(next, total, reading, message) {
            const prev = this.lastSeen;
            this.lastSeen = next;
            if (reading) this.readingMessage = message || '';
            if (reading && (next === 0 || next < prev)) {
                this.startLive();
            } else if (!reading && next < total) {
                this.stopLive();
                return;
            }
            if (!this.live) return;
            this.realDone = this.cums.filter((cum) => cum <= next).length;
            if (this.readStartedAt && next > prev) {
                const done = next - prev;
                const each = (Date.now() - this.readStartedAt) / done;
                this.readAvg = (this.readAvg * this.readSamples + each * done) / (this.readSamples + done);
                this.readSamples += done;
                this.readStartedAt = 0;
            }
            this.releaseRead();
        },
        startLive() {
            const cums = this.dotCums();
            this.stopLive();
            if (cums.length === 0 || !(cums[cums.length - 1] > 0)) return;
            this.cums = cums;
            this.live = true;
            this.liveRun = true;
            this.liveStartedAt = Date.now();
            this.segIndex = 0;
            this.segFrac = 0;
            this.realDone = 0;
            this.readAvg = 0;
            this.readSamples = 0;
            this.readStartedAt = 0;
            this.pendingRead = false;
            this.segTimes = [];
            this.segDone = 0;
            this.segElapsed = 0;
            this.segStart = this.frameAt = performance.now();
            this.syncShown();
            this.updateEta();
            this.raf = requestAnimationFrame(() => this.tick());
            this.watchdog = setInterval(() => { if (performance.now() - this.frameAt > 500) this.tick(); }, 500);
        },
        tick() {
            if (this.raf !== null) cancelAnimationFrame(this.raf);
            this.raf = null;
            if (!this.live) return;
            const now = performance.now();
            const dt = Math.min(Math.max(now - this.frameAt, 0), 1000);
            this.frameAt = now;
            const realOk = this.realDone > this.segIndex;
            const want = Math.min(1, (now - this.segStart) / this.segmentMs, realOk ? 1 : 0.97);
            this.segFrac = Math.max(this.segFrac, Math.min(want, this.segFrac + dt / 600));
            this.syncShown();
            const elapsed = Math.floor((now - this.segStart) / 100) * 100;
            if (elapsed !== this.segElapsed) this.segElapsed = elapsed;
            if (this.segFrac >= 1) {
                this.arrive(now);
            } else if (now - this.etaAt >= 250) {
                this.updateEta();
            }
            if (this.live && this.raf === null) this.raf = requestAnimationFrame(() => this.tick());
        },
        // La barra llega al punto del servidor: se marca con palomita, se anota lo que tardó y empieza el tramo siguiente
        arrive(now) {
            const index = this.segIndex;
            const times = this.segTimes.slice();
            times[index] = now - this.segStart;
            this.segTimes = times;
            this.segIndex = index + 1;
            this.segFrac = 0;
            this.segDone = 0;
            this.segElapsed = 0;
            this.segStart = now;
            if (this.segIndex >= this.cums.length) {
                this.finishLive();
                return;
            }
            this.syncShown();
            this.updateEta();
            this.releaseRead();
        },
        finishLive() {
            this.stopTicks();
            this.live = false;
            this.pendingRead = false;
            this.lastDuration = Date.now() - this.liveStartedAt;
            this.etaText = '';
            this.syncShown();
        },
        stopLive() {
            this.stopTicks();
            clearTimeout(this.readTimer);
            this.readTimer = null;
            this.live = false;
            this.pendingRead = false;
            this.etaText = '';
        },
        stopTicks() {
            if (this.raf !== null) cancelAnimationFrame(this.raf);
            this.raf = null;
            clearInterval(this.watchdog);
            this.watchdog = null;
        },
        // Valor mostrado: el tramo en curso va de un punto al siguiente según segFrac
        syncShown() {
            const count = this.cums.length;
            const last = count > 0 ? this.cums[count - 1] : 0;
            const base = this.segIndex > 0 ? this.cums[Math.min(this.segIndex, count) - 1] : 0;
            const value = this.segIndex >= count ? last : base + (this.cums[this.segIndex] - base) * this.segFrac;
            this.barW = last > 0 ? Math.min(100, (value / last) * 100) : 0;
            this.shownPct = Math.floor(this.barW + 1e-9);
            this.shownCount = Math.floor(value + 1e-9);
            const inSegment = this.segIndex < count ? Math.floor((this.cums[this.segIndex] - base) * this.segFrac + 1e-9) : 0;
            if (inSegment !== this.segDone) this.segDone = inSegment;
            // El avance del tramo (aro de la bolita y línea de la etiqueta) se escribe directo, sin pasar por la reactividad, para no gastar cuadros
            if (this.$refs.segHost) this.$refs.segHost.style.setProperty('--seg', this.segFrac.toFixed(4));
        },
        // Etiquetas bajo la barra: estado, perfiles leídos y tiempo de cada servidor
        stageToken(index, cum) {
            const state = this.dotState(cum, index);
            return state === 'passed' ? 'leído' : (state === 'current' ? 'leyendo' : 'pendiente');
        },
        stageCount(index, cum, count) {
            const state = this.dotState(cum, index);
            const done = state === 'passed' ? count : (state === 'current' ? this.segDone : Math.max(0, Math.min(count, this.processed - (cum - count))));
            return Math.min(count, done) + '/' + count;
        },
        stageTime(index, cum) {
            const state = this.dotState(cum, index);
            if (state === 'current') return this.live ? this.formatSeconds(this.segElapsed) : '';
            const spent = this.segTimes[index];
            return state === 'passed' && spent !== undefined ? this.formatSeconds(spent) : '';
        },
        formatSeconds(ms) {
            return (ms / 1000).toFixed(1) + ' s';
        },
        pctLabel() {
            const value = this.live ? this.shownPct : (this.total > 0 ? Math.min(100, Math.round((this.processed / this.total) * 100)) : 0);
            return value + ' %';
        },
        barWidth() {
            if (this.live) return this.barW;
            return this.total > 0 ? Math.min(100, (this.processed / this.total) * 100) : 0;
        },
        // La lectura real del siguiente servidor espera a que la barra llegue al punto del anterior
        scheduleRead() {
            if (!this.live) {
                this.queueRead();
                return;
            }
            this.pendingRead = true;
            this.releaseRead();
        },
        releaseRead() {
            if (!this.pendingRead || !this.live || this.realDone > this.segIndex) return;
            this.pendingRead = false;
            this.queueRead();
        },
        queueRead() {
            clearTimeout(this.readTimer);
            this.readTimer = setTimeout(() => {
                this.readTimer = null;
                this.readStartedAt = Date.now();
                this.$wire.readNext();
            }, 300);
        },
        loadingLine() {
            const base = 'Lectura de solo acceso, perfil por perfil.';
            if (this.live) {
                const dot = this.dotEls()[this.segIndex];
                if (!dot) return base;
                const profile = this.currentServer === dot.dataset.name && this.currentProfile ? ' · ' + this.currentProfile : '';
                return base + ' Cargando ' + dot.dataset.name + profile + '…';
            }
            return this.scanning && this.currentServer ? base + ' Cargando ' + this.currentServer + ' · ' + this.currentProfile + '…' : base;
        },
        statusText() {
            return this.live && !this.scanning && this.readingMessage ? this.readingMessage : this.statusMessage;
        },
        formatDuration(ms) {
            const seconds = Math.max(1, Math.ceil(ms / 1000));
            if (seconds < 60) return seconds + ' s';
            const minutes = Math.floor(seconds / 60);
            const rest = seconds % 60;
            return minutes + ' min' + (rest ? ' ' + rest + ' s' : '');
        },
        // Tiempo restante: lo que falta del tramo actual más los tramos siguientes (cada uno, al menos segmentMs)
        updateEta() {
            this.etaAt = performance.now();
            const cums = this.cums;
            const duration = (index) => {
                const count = cums[index] - (index > 0 ? cums[index - 1] : 0);
                return Math.max(this.segmentMs, this.readAvg > 0 ? (this.readAvg + 300) * count : 0);
            };
            let remaining = (1 - this.segFrac) * duration(this.segIndex);
            for (let index = this.segIndex + 1; index < cums.length; index++) remaining += duration(index);
            this.etaText = remaining < 1000 ? 'Finalizando…' : 'Tiempo estimado: ' + this.formatDuration(remaining);
        },
        etaLabel() {
            if (this.live) return this.etaText;
            if (this.scanning) {
                if (this.total <= 0) return '';
                if (this.processed <= 0 || !this.scanStartedAt) return 'Calculando tiempo estimado…';
                const perProfile = Math.max(0, (this.lastProgressAt - this.scanStartedAt) / this.processed);
                const remaining = Math.max(0, perProfile * (this.total - this.processed) - Math.max(0, this.nowTick - this.lastProgressAt));
                return remaining < 1000 ? 'Finalizando…' : 'Tiempo estimado: ' + this.formatDuration(remaining);
            }
            return this.lastDuration > 0 ? 'Completado en ' + this.formatDuration(this.lastDuration) : '';
        },
        syncHeight() {
            if (this.$refs.scaled) this.scaledHeight = Math.ceil(this.$refs.scaled.offsetHeight * (this.viewScale / 100));
        },
        setScale(value) {
            this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
            try { localStorage.setItem('backup-reports-view-scale', String(this.viewScale)); } catch (error) {}
            this.$nextTick(() => this.syncHeight());
        },
        selectServer(serverKey, profileKey) {
            this.selServer = serverKey;
            this.selProfile = profileKey || '';
        },
        selectProfile(key) {
            this.selProfile = key;
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
    x-on:backup-read-next.window="scheduleRead()"
    x-on:backup-scan-progress.window="trackDots($event.detail.processed, $event.detail.total, $event.detail.reading, $event.detail.message); processed = $event.detail.processed; total = $event.detail.total; scanning = $event.detail.reading; statusMessage = $event.detail.message; currentServer = $event.detail.server || ''; currentProfile = $event.detail.profile || ''; if ($event.detail.reading) { mailStatus = ''; mailSent = false; }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
    @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024; syncHeight()"
>
    <x-panel-styles />

    <style>
        /* «Datos técnicos» (summary) no está en la lista base del panel: mismo tamaño y peso que el resto */
        [data-backup-modal] summary { font-size: 15px; font-weight: 400; }

        /* Acciones de texto de la cabecera: solo icono y nombre, sin caja de botón */
        [data-panel] .backup-action:disabled,
        [data-panel] .backup-action:disabled:hover { opacity: 0.4; }

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

        /* Menú de servidores y lista de perfiles: la fila elegida se marca solo con un gris neutro (sin color ni línea) */
        .backup-server-item,
        .backup-profile-item { transition: background-color 150ms ease; }
        .backup-server-item:hover,
        .backup-profile-item:hover { background-color: var(--panel-subtle); }
        html.module-dark-theme [data-panel] .backup-server-item:hover,
        html.module-dark-theme [data-panel] .backup-profile-item:hover { background-color: var(--panel-hover); }
        .backup-server-item[aria-selected="true"],
        .backup-server-item[aria-selected="true"]:hover,
        .backup-profile-item.is-selected,
        .backup-profile-item.is-selected:hover { background-color: color-mix(in srgb, var(--panel-accent) 8%, var(--panel-surface)); }
        html.module-dark-theme [data-panel] .backup-server-item[aria-selected="true"],
        html.module-dark-theme [data-panel] .backup-server-item[aria-selected="true"]:hover,
        html.module-dark-theme [data-panel] .backup-profile-item.is-selected,
        html.module-dark-theme [data-panel] .backup-profile-item.is-selected:hover { background-color: color-mix(in srgb, var(--panel-accent) 18%, var(--panel-surface)); }

        /* Menú de servidores en celular: fila que se desplaza de lado */
        .backup-server-menu { scrollbar-width: none; }
        .backup-server-menu::-webkit-scrollbar { display: none; }

        /* Barra de avance: un solo azul, sólido */
        .backup-progress { --bar-blue: #1d4ed8; }
        html.module-dark-theme .backup-progress { --bar-blue: var(--panel-accent); }
        .backup-bar-track { position: relative; background-color: var(--panel-track); }
        .backup-bar-fill,
        .backup-bar-ok { background-color: var(--bar-blue); }
        /* Durante la lectura el ancho se actualiza en cada cuadro (llenado continuo); la transición solo suaviza saltos fuera de ella */
        .backup-bar-fill.is-eased { transition: width 700ms cubic-bezier(.22, 1, .36, 1); }
        .backup-bar-warn { background-color: var(--panel-danger); }

        /* Estado de la lectura junto al texto: rueda mientras lee y palomita al terminar */
        .backup-spinner { display: inline-block; height: 16px; width: 16px; flex-shrink: 0; border-radius: 9999px; border: 2px solid var(--panel-track); border-top-color: var(--bar-blue); animation: backup-spin 800ms linear infinite; }
        .backup-done-icon { color: var(--bar-blue); }

        /* Puntos de la barra (uno por servidor): del color de la línea, el aro se rellena mientras la barra llega y se llenan de azul con palomita al pasarlos */
        /* Línea de tiempo: contenedor de consulta (los globos se miden en % de la barra), el último punto cabe en el margen derecho */
        .backup-timeline-wrap { --ms: 34px; container-type: inline-size; padding-right: calc(var(--ms) / 2); }
        @media (max-width: 639px) { .backup-timeline-wrap { --ms: 28px; } }
        .backup-timeline { position: relative; }
        .backup-milestones-row { min-height: var(--ms); }
        .backup-milestone {
            pointer-events: auto;
            cursor: default;
            outline: none;
            position: absolute;
            top: 50%;
            z-index: 1;
            display: flex;
            height: var(--ms);
            width: var(--ms);
            align-items: center;
            justify-content: center;
            transform: translate(-50%, -50%);
            border-radius: 9999px;
            background-color: var(--panel-track);
            box-shadow: 0 0 0 4px var(--panel-surface);
            color: var(--panel-muted);
            line-height: 1;
            transition: background-color 300ms ease, color 300ms ease;
        }
        .backup-milestone-n { transition: opacity 200ms ease; }
        .backup-milestone-ring,
        .backup-milestone-check { position: absolute; inset: 0; height: 100%; width: 100%; }
        .backup-milestone-ring { opacity: 0; transform: rotate(-90deg); fill: none; stroke-width: 3; }
        .backup-milestone-ring .bg { stroke: var(--panel-track); }
        .backup-milestone-ring .fg { stroke: var(--bar-blue); stroke-linecap: butt; stroke-dasharray: 100; stroke-dashoffset: 100; }
        .backup-milestone-check { padding: 8px; fill: none; stroke: #ffffff; stroke-width: 3.2; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 24; stroke-dashoffset: 24; }
        html.module-dark-theme .backup-milestone-check { stroke: #09090b; }
        .backup-milestone[data-state="current"] { background-color: var(--panel-surface); color: var(--bar-blue); }
        .backup-milestone[data-state="current"] .backup-milestone-ring { opacity: 1; }
        .backup-milestone[data-state="current"] .backup-milestone-ring .fg { stroke-dashoffset: calc(100px * (1 - var(--seg, 0))); }
        .backup-milestone[data-state="passed"] { background-color: var(--bar-blue); color: #ffffff; }
        html.module-dark-theme .backup-milestone[data-state="passed"] { color: #09090b; }
        .backup-milestone[data-state="passed"] .backup-milestone-n { opacity: 0; }
        .backup-milestone[data-state="passed"] .backup-milestone-check { stroke-dashoffset: 0; }
        .is-live .backup-milestone[data-state="passed"] { animation: backup-pop 420ms cubic-bezier(.22, 1, .36, 1); }
        .is-live .backup-milestone[data-state="passed"] .backup-milestone-check { animation: backup-draw 380ms ease-out 120ms backwards; }

        /* Globos emergentes (uno por servidor): flotan bajo su punto sin ocupar espacio. Se muestran solo mientras ese servidor
           se está leyendo o al pasar el cursor / enfocar su punto. Texto técnico, líneas de 1px. */
        .backup-balloons { position: absolute; inset: 0; margin: 0; padding: 0; pointer-events: none; }
        .backup-balloon {
            --bw: min(270px, 100cqw);
            --bl: clamp(0px, calc(var(--x, 0) * 1cqw - var(--bw) / 2), calc(100cqw + var(--ms) / 2 - var(--bw)));
            --ax: clamp(17px, calc(var(--x, 0) * 1cqw - var(--bl)), calc(100% - 17px));
            --balloon-bg: var(--panel-surface);
            --balloon-border: var(--panel-border);
            position: absolute;
            top: 100%;
            left: var(--bl);
            z-index: 30;
            box-sizing: border-box;
            width: var(--bw);
            margin-top: 12px;
            list-style: none;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            transition: opacity 160ms ease, transform 160ms ease, visibility 0s linear 160ms;
        }
        .backup-balloon.is-open,
        .backup-balloon[data-state="current"]:not(.is-muted) { opacity: 1; visibility: visible; transform: none; transition: opacity 160ms ease, transform 160ms ease, visibility 0s; }
        .backup-balloon[data-state="current"]:not(.is-muted) { pointer-events: auto; }
        .backup-balloon-card { position: relative; overflow: hidden; padding: 20px; border: 1px solid var(--balloon-border); border-radius: 8px; background-color: var(--balloon-bg); box-shadow: 0 8px 24px rgb(15 23 42 / .12); transition: background-color 300ms ease, border-color 300ms ease; }
        /* Línea fina de avance en el borde inferior */
        .backup-balloon-card::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background-color: var(--tone); transform: scaleX(0); transform-origin: left; }
        .backup-balloon[data-state="passed"] .backup-balloon-card::after { transform: scaleX(1); }
        .backup-balloon[data-state="current"] .backup-balloon-card::after { transform: scaleX(var(--seg, 0)); }
        /* Flecha del globo apuntando a su punto */
        .backup-balloon-arrow { position: absolute; top: -6px; z-index: 1; left: var(--ax); box-sizing: border-box; height: 12px; width: 12px; margin-left: -6px; border: 0 solid var(--balloon-border); border-width: 1px 0 0 1px; background-color: var(--balloon-bg); transform: rotate(45deg); transition: background-color 300ms ease, border-color 300ms ease; }
        /* Diseño del globo: icono en chip, nombre con IP, número del servidor y pie con estado, perfiles leídos y tiempo */
        /* Solo dos colores: azul fuerte (leyendo y leído) y rojo vino (errores y cancelar); gris para lo pendiente */
        .backup-tone-blue { color: var(--panel-accent); }
        .backup-tone-red { color: var(--panel-danger); }
        .backup-balloon { --tone: var(--panel-muted); }
        .backup-balloon[data-state="current"],
        .backup-balloon[data-state="passed"] { --tone: var(--bar-blue); }
        .backup-balloon-chip { display: inline-flex; height: 40px; width: 40px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 8px; background-color: color-mix(in srgb, var(--tone) 14%, transparent); color: var(--tone); transition: background-color 300ms ease, color 300ms ease; }
        .backup-balloon-cancel { border: 1px solid var(--panel-danger-line); border-radius: 8px; background-color: var(--panel-danger-soft); pointer-events: auto; }
        .backup-balloon-num { align-self: center; color: var(--panel-muted); }
        .backup-balloon-state { border-top: 1px solid var(--balloon-border); color: var(--panel-muted); }
        .backup-balloon-state .backup-balloon-sep { color: var(--panel-muted); }
        .backup-balloon-dot { display: inline-flex; height: 16px; width: 16px; }
        .backup-balloon-dot svg { display: none; height: 100%; width: 100%; }
        .backup-balloon[data-state="pending"] .backup-balloon-dot [data-icon="pending"],
        .backup-balloon[data-state="passed"] .backup-balloon-dot [data-icon="passed"],
        .backup-balloon[data-state="current"] .backup-balloon-dot [data-icon="current"] { display: block; }
        .backup-balloon[data-state="passed"] .backup-balloon-state,
        .backup-balloon[data-state="current"] .backup-balloon-state { color: var(--tone); }
        .backup-balloon[data-state="current"] .backup-balloon-dot svg { animation: backup-spin 800ms linear infinite; }

        @keyframes backup-pop { 0% { transform: translate(-50%, -50%) scale(.7); } 55% { transform: translate(-50%, -50%) scale(1.18); } 100% { transform: translate(-50%, -50%) scale(1); } }
        @keyframes backup-draw { from { stroke-dashoffset: 24; } to { stroke-dashoffset: 0; } }
        @keyframes backup-spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) {
            .backup-milestone,
            .backup-milestone-check,
            .backup-balloon-dot svg,
            .backup-spinner { animation: none !important; }
            .backup-bar-fill.is-eased,
            .backup-balloon,
            .backup-balloon-card,
            .backup-balloon-arrow { transition: none !important; }
        }

        /* Cifras del resumen: mismo tamaño y tipo de letra que el resto del texto */
        [data-panel] .backup-figure { font-size: inherit; line-height: inherit; letter-spacing: normal; }

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

        /* Incidencias del perfil */
        .backup-box-head { border-bottom: 1px solid; border-color: inherit; }
        .backup-incident-list { margin: 0; list-style: none; }

        /* Etiquetas de destinatarios del envío automático */
        .backup-chip {
            display: inline-flex;
            min-width: 0;
            max-width: 100%;
            align-items: center;
            border: 1px solid var(--panel-border);
            border-radius: 9999px;
            padding: 5px 15px;
            background-color: var(--panel-surface);
        }

        /* Título del servidor: solo visible al imprimir (en pantalla lo muestra la pestaña activa) */
        .support-backup-report-page .backup-print-title { display: none; }
        .support-backup-report-page .backup-print-meta { display: none; }

        /* Solo negro o azul fuerte en los textos (más rojo y verde para estados): lo que era gris pasa a negro, las etiquetas van en azul y nada en negritas */
        .support-backup-report-page[data-panel],
        html.module-dark-theme .support-backup-report-page[data-panel] { --panel-muted: var(--panel-text); }
        .support-backup-report-page :is(strong, b, th) { font-weight: 400; }

        /* Botones de acción (enviar, guardar, guía, actualizar, agregar, editar y eliminar de cada fila) en verde; solo «Cancelar lectura» sigue en rojo */
        .support-backup-report-page[data-panel] { --backup-green: #15803d; }
        html.module-dark-theme .support-backup-report-page[data-panel] { --backup-green: #4ade80; }
        .support-backup-report-page .panel-action:not(.panel-action-danger) { color: var(--backup-green); }

        /* Formularios del informe (mismo estilo que «Agregar usuario»): encabezado y pie blancos, secciones separadas solo por líneas,
           botón principal en azul fuerte, cancelar con contorno azul y cierre / eliminar en rojo */
        [data-backup-modal] { --backup-blue: #1A3A6B; --backup-blue-hover: #142d54; }
        html.module-dark-theme [data-backup-modal] { --backup-blue: var(--panel-accent); --backup-blue-hover: var(--panel-text); }
        [data-backup-modal] .backup-modal-section + .backup-modal-section { border-top: 1px solid var(--panel-border); padding-top: 20px; }
        [data-backup-modal] .backup-modal-primary { background-color: var(--backup-blue); color: #ffffff; transition: background-color 150ms ease; }
        [data-backup-modal] .backup-modal-primary:hover { background-color: var(--backup-blue-hover); }
        html.module-dark-theme [data-backup-modal] .backup-modal-primary { color: var(--panel-page); }
        [data-backup-modal] .backup-modal-outline { border: 1px solid var(--backup-blue); color: var(--backup-blue); background-color: var(--panel-surface); transition: background-color 150ms ease; }
        [data-backup-modal] .backup-modal-outline:hover { background-color: color-mix(in srgb, var(--backup-blue) 8%, var(--panel-surface)); }
        [data-backup-modal] .backup-modal-close { border: 1px solid var(--panel-danger-line); color: var(--panel-danger); background-color: var(--panel-surface); transition: background-color 150ms ease; }
        [data-backup-modal] .backup-modal-close:hover { background-color: var(--panel-danger-soft); }
        [data-backup-modal] .backup-modal-delete { border: 1px solid var(--panel-danger-line); color: var(--panel-danger); background-color: var(--panel-surface); transition: background-color 150ms ease; }
        [data-backup-modal] .backup-modal-delete:hover { background-color: var(--panel-danger-soft); }

        /* Cajas de texto exactas: el cuadro de cada texto va de la altura de la mayúscula a la línea base (sin el aire que trae
           la fuente por defecto), así la separación entre una línea de texto y la siguiente es la distancia real (15 px).
           Con varias líneas, 25 px de interlineado equivalen a esos 15 px entre una y otra. Los iconos de 16 px se recortan
           al mismo alto para que no abran más espacio. Requiere text-box (Chrome 133+); sin él se ve con el interlineado normal. */
        @supports (text-box: trim-both cap alphabetic) {
            .support-backup-report-page :is(h1, h2, p, li, summary, label, td, th, span.block, :is(.flex, .inline-flex, .grid) > span) {
                text-box: trim-both cap alphabetic;
                line-height: 25px;
            }
            .support-backup-report-page :is(svg.h-4, .backup-balloon-dot) { margin-block: -2.75px; }
            /* Con recorte (truncate) las letras con cola (g, p, y) no deben cortarse */
            .support-backup-report-page :is([class*="truncate"], .backup-status-line) { overflow: clip; overflow-clip-margin: 6px; }
        }

        /* Celular: acciones del encabezado en varias líneas y textos largos sin recortar */
        @media (max-width: 639px) {
            .backup-actions { width: 100%; column-gap: 20px; }
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
            .support-backup-report-page .backup-print-meta { display: block; padding: 0 20px 20px; margin-top: -10px; }
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

                <!-- Cabecera: título y subtítulo a la izquierda; servicio y estado a la derecha -->
                <section class="mb-[30px] flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                    <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                        {{-- El recuadro mide lo mismo que el bloque de texto (título 20 px + 15 px + subtítulo), así la distancia al menú es la misma que la de abajo --}}
                        <span class="panel-surface panel-border panel-text grid h-[40px] w-[40px] shrink-0 place-items-center rounded-xl border">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 18h16M6 15V9m4 6V5m4 10v-3m4 3V7"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Informe Técnico de Respaldos</h1>
                            <p class="mt-[15px] leading-tight">Supervisa servidores, perfiles e incidencias.</p>
                        </div>
                    </div>

                    <!-- Servicio y estado de la conexión, a la altura del título y del subtítulo -->
                    @php
                        $backupProfiles = collect($servers)->flatMap(fn ($server) => $server['profiles']);
                        $backupRead = $backupProfiles->where('done', true);
                        // Un perfil «Excluido por el responsable» no cuenta; «no disponible» también ocurre con un log sin formato o incompleto,
                        // así que solo se marca «Desconectado» cuando NINGÚN perfil se pudo leer; si fallan algunos sigue «Conectado» y el detalle va en el título.
                        $backupRead = $backupRead->reject(fn ($profile) => ! $profile['available'] && ($profile['message'] ?? '') === 'Excluido por el responsable.');
                        $backupOffline = $backupRead->where('available', false)->count();
                        $connection = $backupRead->isEmpty()
                            ? ['Sin lectura', 'panel-muted', 'Aún no hay ninguna lectura guardada.']
                            : ($backupOffline === $backupRead->count()
                                ? ['Desconectado', 'backup-tone-red', 'Ningún perfil se pudo leer en la última lectura.']
                                : ($backupOffline > 0
                                    ? ['Conectado', 'backup-tone-blue', $backupOffline.' de '.$backupRead->count().' perfiles no se pudieron leer en la última lectura.']
                                    : ['Conectado', 'backup-tone-blue', 'Todos los perfiles respondieron en la última lectura.']));
                    @endphp
                    <div class="w-full min-w-0 lg:w-auto lg:text-right" data-backup-connection>
                        {{-- La guía va al mismo nivel que «Servicio de respaldos»: es lo primero que se necesita para saber cómo funciona --}}
                        <div class="flex flex-wrap items-center gap-x-[10px] gap-y-[15px] lg:justify-end">
                            <span class="no-print flex items-center">
                                <button type="button" wire:click="downloadGuide" wire:loading.attr="disabled" wire:target="downloadGuide" title="Descargar la guía del Reporte de Respaldos en PDF" class="panel-action backup-action inline-flex items-center gap-[10px] whitespace-nowrap leading-none focus:outline-none focus:ring-0">
                                <svg wire:loading.remove wire:target="downloadGuide" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5"/></svg>
                                <svg wire:loading wire:target="downloadGuide" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span>Guía PDF</span>
                                </button>
                            </span>
                            <span class="no-print panel-muted" aria-hidden="true">·</span>
                            <p class="panel-accent flex items-center leading-none"><span>Servicio de respaldos</span></p>
                        </div>
                        <p class="mt-[15px] flex items-center gap-[10px] leading-tight lg:justify-end" aria-live="polite">
                            <span x-show="busy" x-cloak class="panel-muted inline-flex items-center gap-[10px]">
                                <span class="h-2 w-2 shrink-0 animate-pulse rounded-full bg-current" aria-hidden="true"></span>
                                <span>Leyendo…</span>
                            </span>
                            <span x-show="!busy" x-cloak class="inline-flex items-center gap-[10px] {{ $connection[1] }}" title="{{ $connection[2] }}">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
                                <span>{{ $connection[0] }}</span>
                            </span>
                        </p>
                    </div>
                </section>

                <!-- Caja general: barra de avance, envío automático, cifras y tabla de servidores -->
                <!-- Sin overflow-hidden: el globo emergente de cada servidor sale de la caja y no debe cortarse; por eso la cabecera redondea sus propias esquinas -->
                <section class="panel-surface panel-border rounded-xl border" aria-label="Resumen del informe">
                    <div class="backup-progress" aria-live="polite">
                        <!-- Cabecera gris como las demás: título y, debajo a 10 px, estado de la lectura y cifras -->
                        <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] rounded-t-[11px] border-b p-[20px]">
                        <div class="min-w-0">
                        <h2 class="panel-accent flex items-center leading-none"><span>Lectura de respaldos</span></h2>
                            <span class="mt-[15px] flex min-w-0 items-start gap-[10px] sm:items-center">
                                <span x-show="busy && !mailSending && !mailStatus" x-cloak class="backup-spinner mt-[4px] sm:mt-0" aria-hidden="true"></span>
                                <span class="backup-status-line block min-w-0 truncate" :class="mailStatus && !mailSent ? 'backup-tone-red' : ''" x-text="mailSending ? 'Generando PDF y enviando el correo...' : (mailStatus || statusText())">{{ $statusMessage }}</span>
                            </span>
                        </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-x-[10px] gap-y-[5px]" data-backup-meta>
                                <span x-show="total > 0 && !mailSending && !mailStatus" x-cloak class="backup-figure tabular-nums" x-text="pctLabel()">{{ $totalProfiles > 0 ? min(100, (int) round(($processedProfiles / $totalProfiles) * 100)) : 0 }} %</span>
                                <span x-show="total > 0 && !mailSending && !mailStatus" x-cloak class="panel-muted" aria-hidden="true">·</span>
                                <span x-show="total > 0 && !mailSending && !mailStatus" x-cloak class="panel-muted tabular-nums"><span x-text="live ? shownCount : processed">{{ $processedProfiles }}</span> / <span x-text="total">{{ $totalProfiles }}</span> perfiles</span>
                                <span x-show="total > 0 && !mailSending && !mailStatus && etaLabel()" x-cloak class="panel-muted hidden sm:inline" aria-hidden="true">·</span>
                                <span x-show="!mailSending && !mailStatus && etaLabel()" x-cloak class="panel-muted tabular-nums basis-full sm:basis-auto" x-text="etaLabel()" data-eta></span>
                                <span x-show="mailSending" x-cloak class="panel-muted">Procesando</span>
                            </div>
</header>
                        <div class="p-[20px]">
                        <div x-ref="segHost" x-show="total > 0 || busy || mailSending || mailStatus" x-cloak>
                            @php
                                // Un punto por servidor, donde termina la lectura de sus perfiles
                                $milestones = [];
                                $milestoneCum = 0;
                                foreach ($servers as $milestoneServer) {
                                    $milestoneCount = count($milestoneServer['profiles']);
                                    if ($milestoneCount === 0) {
                                        continue;
                                    }
                                    $milestoneCum += $milestoneCount;
                                    $milestones[] = ['name' => $milestoneServer['name'], 'ip' => $milestoneServer['ip'], 'count' => $milestoneCount, 'cum' => $milestoneCum];
                                }
                            @endphp
                            <!-- peek: índice del punto bajo el cursor o con foco; su globo emerge y oculta el del servidor en lectura -->
                            <div class="backup-timeline-wrap" x-data="{ peek: null }">
                                <div class="backup-timeline">
                                    <div class="backup-milestones-row relative flex items-center">
                                        <div
                                            class="backup-bar-track h-1 min-w-0 flex-1 overflow-hidden rounded-full"
                                            :class="busy && !mailSending && !mailStatus ? 'is-reading' : ''"
                                            role="progressbar"
                                            aria-label="Avance de la lectura de respaldos"
                                            aria-valuemin="0"
                                            :aria-valuemax="total"
                                            :aria-valuenow="live ? shownCount : processed"
                                        >
                                            <div x-show="!mailSending && !mailStatus" class="backup-bar-fill h-full rounded-full" :class="live ? '' : 'is-eased'" :style="{ width: barWidth() + '%' }" style="width: {{ $totalProfiles > 0 ? min(100, ($processedProfiles / $totalProfiles) * 100) : 0 }}%"></div>
                                            <div x-show="mailSending" x-cloak class="backup-bar-fill h-full w-1/2 animate-pulse rounded-full"></div>
                                            <div x-show="!mailSending && mailStatus" x-cloak class="h-full rounded-full transition-all duration-300" :class="mailSent ? 'w-full backup-bar-ok' : 'w-1/3 backup-bar-warn'"></div>
                                        </div>
                                        @if (count($milestones) > 0)
                                            <!-- Puntos de avance: numerados; el aro se rellena mientras la barra llega y se marcan con palomita azul al pasarlos -->
                                            <div x-show="!mailSending && !mailStatus" class="no-print pointer-events-none absolute inset-0" :class="liveRun ? 'is-live' : ''" data-backup-milestones>
                                                @foreach ($milestones as $milestoneIndex => $milestone)
                                                    @php
                                                        $milestoneLeft = round(($milestone['cum'] / max(1, $milestoneCum)) * 100, 3);
                                                    @endphp
                                                    <span
                                                        class="backup-milestone"
                                                        data-backup-milestone
                                                        data-cum="{{ $milestone['cum'] }}"
                                                        data-n="{{ $milestoneIndex + 1 }}"
                                                        data-of="{{ count($milestones) }}"
                                                        data-count="{{ $milestone['count'] }}"
                                                        data-name="{{ $milestone['name'] }}"
                                                        data-pct="{{ $milestoneLeft }}"
                                                        data-state="{{ $processedProfiles >= $milestone['cum'] ? 'passed' : 'pending' }}"
                                                        :data-state="dotState({{ $milestone['cum'] }}, {{ $milestoneIndex }})"
                                                        style="left: {{ $milestoneLeft }}%"
                                                        tabindex="0"
                                                        @mouseenter="peek = {{ $milestoneIndex }}"
                                                        @mouseleave="peek = null"
                                                        @focus="peek = {{ $milestoneIndex }}"
                                                        @blur="peek = null"
                                                        role="img"
                                                        aria-label="{{ $milestone['name'] }}"
                                                    >
                                                        <svg class="backup-milestone-ring" viewBox="0 0 34 34" aria-hidden="true"><circle class="bg" cx="17" cy="17" r="15.5" /><circle class="fg" cx="17" cy="17" r="15.5" pathLength="100" /></svg>
                                                        <span class="backup-milestone-n">{{ $milestoneIndex + 1 }}</span>
                                                        <svg class="backup-milestone-check" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.8l4.4 4.4L19 7.4" /></svg>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    @if (count($milestones) > 0)
                                        <!-- Globos emergentes: uno por servidor; aparecen durante su lectura o al pasar el cursor por su punto -->
                                        <ol x-show="!mailSending && !mailStatus" class="backup-balloons no-print" role="list" aria-label="Estado de la lectura por servidor" data-backup-stages>
                                            @foreach ($milestones as $milestoneIndex => $milestone)
                                                @php
                                                    $stageDone = $processedProfiles >= $milestone['cum'];
                                                    $stageLeft = round(($milestone['cum'] / max(1, $milestoneCum)) * 100, 3);
                                                @endphp
                                                <li
                                                    class="backup-balloon"
                                                    style="--x: {{ $stageLeft }}"
                                                    data-backup-stage
                                                    :class="peek === {{ $milestoneIndex }} ? 'is-open' : (peek !== null ? 'is-muted' : '')"
                                                    data-n="{{ $milestoneIndex + 1 }}"
                                                    data-count="{{ $milestone['count'] }}"
                                                    data-name="{{ $milestone['name'] }}"
                                                    data-state="{{ $stageDone ? 'passed' : 'pending' }}"
                                                    :data-state="dotState({{ $milestone['cum'] }}, {{ $milestoneIndex }})"
                                                >
                                                    <span class="backup-balloon-arrow" aria-hidden="true"></span>
                                                    <div class="backup-balloon-card">
                                                        <div class="flex min-w-0 items-center gap-[10px]">
                                                            <span class="backup-balloon-chip" aria-hidden="true">
                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6.5" rx="1.5" /><rect x="3" y="13.5" width="18" height="6.5" rx="1.5" /><path d="M7 7.25h.01M7 16.75h.01" /></svg>
                                                            </span>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="panel-text break-words leading-tight">{{ $milestone['name'] }}</p>
                                                                @if (filled($milestone['ip']))
                                                                    <p class="panel-muted mt-[15px] break-all leading-tight">{{ $milestone['ip'] }}</p>
                                                                @endif
                                                            </div>
                                                            <span class="backup-balloon-num tabular-nums">{{ sprintf('%02d', $milestoneIndex + 1) }}</span>
                                                        </div>
                                                        <div class="backup-balloon-state mt-[20px] flex items-center justify-between gap-[20px] pt-[20px] tabular-nums">
                                                            <span class="inline-flex items-center gap-[10px]">
                                                                <span class="backup-balloon-dot" aria-hidden="true">
                                                                    <svg data-icon="passed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5" /><path d="M8 12.5l2.7 2.7L16 9.5" /></svg>
                                                                    <svg data-icon="current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9" /></svg>
                                                                    <svg data-icon="pending" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5" /><path d="M12 7v5l3 2" /></svg>
                                                                </span>
                                                                <span x-text="stageToken({{ $milestoneIndex }}, {{ $milestone['cum'] }})">{{ $stageDone ? 'leído' : 'pendiente' }}</span>
                                                            </span>
                                                            <span class="inline-flex items-center gap-[10px]">
                                                                <span x-text="stageCount({{ $milestoneIndex }}, {{ $milestone['cum'] }}, {{ $milestone['count'] }})">{{ $stageDone ? $milestone['count'] : 0 }}/{{ $milestone['count'] }}</span>
                                                                <span x-show="stageTime({{ $milestoneIndex }}, {{ $milestone['cum'] }}) !== ''" x-cloak class="backup-balloon-sep" aria-hidden="true">·</span>
                                                                <span x-show="stageTime({{ $milestoneIndex }}, {{ $milestone['cum'] }}) !== ''" x-cloak x-text="stageTime({{ $milestoneIndex }}, {{ $milestone['cum'] }})" data-stage-time></span>
                                                            </span>
                                                        </div>
                                                        <!-- Solo en el servidor que se está leyendo: detiene la lectura y conserva lo ya leído -->
                                                        <button type="button" x-show="dotState({{ $milestone['cum'] }}, {{ $milestoneIndex }}) === 'current'" x-cloak @click="$wire.stopReading(); stopLive()" class="backup-balloon-cancel panel-action-danger mt-[20px] inline-flex h-11 w-full items-center justify-center gap-[10px] leading-none focus:outline-none focus:ring-0">
                                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                                                            <span>Cancelar lectura</span>
                                                        </button>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- Debajo de la barra: estado de la lectura a la izquierda y «Actualizar registros» a la derecha -->
                        <div class="panel-muted mt-[20px] flex flex-col items-start gap-[15px] lg:flex-row lg:items-center lg:justify-between lg:gap-[20px]">
                            <span class="backup-status-line block min-w-0 lg:truncate" x-text="mailSending || mailStatus ? 'El reporte se envía a los destinatarios seleccionados.' : loadingLine()">Lectura de solo acceso, perfil por perfil.</span>
                            <!-- Acciones del informe: enviar por correo, guardar PDF, guía PDF y actualizar, juntas a la derecha -->
                            <div class="no-print backup-actions flex min-w-0 flex-wrap items-center gap-x-[20px] gap-y-[15px] lg:shrink-0">
                            <div class="relative flex items-center select-none">
                                <button type="button" @click="recipientsOpen = !recipientsOpen" :disabled="mailSending || busy || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="panel-action backup-action inline-flex select-none items-center gap-[10px] whitespace-nowrap leading-none focus:outline-none focus:ring-0">
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6.75A1.75 1.75 0 0 1 4.75 5h14.5A1.75 1.75 0 0 1 21 6.75v10.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Zm.5-.5L12 13l8.5-6.75"/></svg>
                                    <span>Enviar reporte por correo</span>
                                </button>
                                <div x-show="recipientsOpen" x-cloak x-transition.opacity.duration.150ms x-on:click.outside="recipientsOpen = false" class="panel-surface panel-border fixed inset-x-[20px] bottom-[20px] z-50 whitespace-normal rounded-xl border p-[20px] shadow-[0_18px_50px_rgba(0,0,0,0.18)] sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:top-full sm:mt-[20px] sm:w-[370px]">
                                    <div class="mb-[20px]">
                                        <strong class="panel-accent block leading-none">Enviar reporte a</strong>
                                        <span class="panel-muted mt-[15px] block leading-tight">Selecciona uno o varios destinatarios.</span>
                                    </div>
                                    <div class="flex flex-col gap-[10px]">
                                        @foreach ($recipientOptions as $email => $label)
                                            <label wire:key="backup-recipient-{{ $email }}" class="backup-recipient panel-border flex cursor-pointer select-none items-center gap-[15px] rounded-xl border px-[20px]">
                                                <input x-model="selectedRecipients" type="checkbox" value="{{ $email }}" class="backup-check">
                                                <span class="min-w-0"><strong class="block">{{ $label }}</strong><span class="panel-muted block truncate">{{ $email }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p x-show="selectedRecipients.length === 0" x-cloak class="backup-tone-red mt-[15px]">Selecciona al menos un correo.</p>
                                    <button type="button" @click.prevent.stop="sendReport()" :disabled="mailSending || busy || total === 0 || processed < total || selectedRecipients.length === 0" class="panel-button ml-auto mt-[20px] flex h-11 select-none items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">
                                        <span x-show="!mailSending">Enviar reporte</span>
                                        <span x-show="mailSending" x-cloak>Enviando...</span>
                                    </button>
                                </div>
                            </div>
                            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" :disabled="busy || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="panel-action backup-action inline-flex items-center gap-[10px] whitespace-nowrap leading-none focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                                <span>Guardar PDF</span>
                            </button>
                                    <button type="button" wire:click="startReading" wire:loading.attr="disabled" :disabled="busy" class="panel-action backup-action no-print inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap leading-none focus:outline-none focus:ring-0">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                                <span>Actualizar registros</span>
                            </button>
                            </div>
                        </div>
                        </div>
                    </div>
                </section>

                <!-- Envío automático semanal: caja aparte, separada 50 px de la de arriba (20 px de la página + 30 px) -->
                <section class="panel-surface panel-border mt-[30px] overflow-hidden rounded-xl border" aria-label="Envío automático">
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
                        $weeklyTimeLabel = $hasTimeParts ? sprintf('%02d:%02d', (int) $timeParts[1], (int) $timeParts[2]) : $weeklyTime;
                        $gmtLabel = 'GMT-6';
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
                            $offsetSeconds = $weeklyZone->getOffset($nextRun);
                            $gmtLabel = 'GMT'.($offsetSeconds < 0 ? '-' : '+').intdiv(abs($offsetSeconds), 3600).(abs($offsetSeconds) % 3600 ? ':'.sprintf('%02d', intdiv(abs($offsetSeconds) % 3600, 60)) : '');
                            $nextRunLabel = ucfirst($weekDays[(int) $nextRun->format('w')]).' '.$nextRun->format('j').' de '.$monthNames[(int) $nextRun->format('n') - 1].' de '.$nextRun->format('Y').', '.$nextRun->format('H:i').' h ('.$gmtLabel.')';
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
                                $lastWeeklyLabel = (new DateTimeImmutable((string) ($lastWeekly['at'] ?? 'now')))->setTimezone($weeklyZone)->format('d/m/Y, H:i').' h ('.$gmtLabel.')';
                            } catch (Throwable) {
                            }
                        }
                    @endphp
                    <!-- Detalle siempre visible: tres datos con icono (programación, próximo envío, último envío) y los destinatarios -->
                    <div class="no-print" data-weekly-report>
                        <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b p-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent flex items-center leading-none"><span>Envío automático</span></h2>
                                <p class="panel-muted mt-[15px] leading-tight">El reporte se envía solo a los destinatarios configurados.</p>
                            </div>
                            <span class="{{ $weeklyEnabled ? 'backup-tone-blue' : 'panel-muted' }} inline-flex items-center gap-[10px] leading-none">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
                                <span>{{ $weeklyEnabled ? 'Activo' : 'Desactivado' }}</span>
                            </span>
                        </header>
                        <div class="panel-border grid grid-cols-1 md:grid-cols-3">
                            {{-- Cada celda: icono y etiqueta en una fila centrada (misma altura de línea) y el dato debajo, alineado con la etiqueta --}}
                            <div class="panel-border min-w-0 border-b p-[20px] md:border-b-0 md:border-r">
                                <div class="flex items-center gap-[10px] leading-none">
                                    <svg class="backup-tone-blue h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 10h18M8 3v4M16 3v4" /></svg>
                                    <span class="panel-accent">Programación</span>
                                </div>
                                <span class="mt-[15px] block pl-[26px] leading-tight">Cada {{ ucfirst($weeklyDayLabel) }}, {{ $weeklyTimeLabel }} h ({{ $gmtLabel }})</span>
                            </div>
                            <div class="panel-border min-w-0 border-b p-[20px] md:border-b-0 md:border-r">
                                <div class="flex items-center gap-[10px] leading-none">
                                    <svg class="backup-tone-blue h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" /><path d="M12 7v5l3 2" /></svg>
                                    <span class="panel-accent">Próximo envío</span>
                                </div>
                                <span class="mt-[15px] block pl-[26px] leading-tight">{{ $weeklyEnabled ? ($nextRunLabel ?? 'Pendiente') : 'Desactivado' }}</span>
                            </div>
                            <div class="min-w-0 p-[20px]">
                                <div class="flex items-center gap-[10px] leading-none">
                                    @if ($lastWeekly && ! ($lastWeekly['sent'] ?? false))
                                        <svg class="backup-tone-red h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" /><path d="M12 7.5v5M12 16.5h.01" /></svg>
                                    @else
                                        <svg class="backup-tone-blue h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 21 4l-7.5 17-2.5-7.5L3 11.5Z" /></svg>
                                    @endif
                                    <span class="panel-accent">Último envío automático</span>
                                </div>
                                @if ($lastWeekly)
                                    <span class="mt-[15px] block pl-[26px] leading-tight {{ ($lastWeekly['sent'] ?? false) ? '' : 'backup-tone-red' }}">{{ ($lastWeekly['sent'] ?? false) ? 'Enviado' : 'No se envió' }}@if ($lastWeeklyLabel) · {{ $lastWeeklyLabel }}@endif</span>
                                    @if (! ($lastWeekly['sent'] ?? false) && filled($lastWeekly['status'] ?? null))
                                        <span class="panel-muted mt-[15px] block pl-[26px] leading-tight">{{ $lastWeekly['status'] }}</span>
                                    @endif
                                @else
                                    <span class="mt-[15px] block pl-[26px] leading-tight">Sin ejecutarse</span>
                                @endif
                            </div>
                        </div>
                        <div class="panel-border flex flex-col gap-[15px] border-t p-[20px]">
                            {{-- Mismo formato que las celdas de arriba: icono y etiqueta, y debajo los correos separados por «·», alineados con la etiqueta --}}
                            <div class="flex items-center gap-[10px] leading-none">
                                <svg class="backup-tone-blue h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                                <span class="panel-accent">Destinatarios del lunes</span>
                            </div>
                            <p class="flex min-w-0 flex-wrap items-center gap-x-[10px] gap-y-[15px] pl-[26px] leading-tight">
                                @forelse ($weeklyRecipients as $weeklyEmail)
                                    @if (! $loop->first)<span class="panel-muted" aria-hidden="true">·</span>@endif
                                    <span class="min-w-0 break-all">{{ $weeklyEmail }}</span>
                                @empty
                                    <span class="backup-tone-red">Sin destinatarios configurados</span>
                                @endforelse
                            </p>
                            <p class="panel-muted leading-tight">El envío manual sigue disponible cuando lo necesites: usa «Enviar reporte por correo» y elige los destinatarios.</p>
                        </div>
                    </div>
                </section>

                <!-- Tabla de servidores y perfiles: caja aparte, separada 50 px de la de arriba (20 px de la página + 30 px) -->
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
                            $serverErrorTotal = collect($serverMeta)->sum('errors');
                        @endphp
                        <section class="panel-surface panel-border mt-[30px] overflow-hidden rounded-xl border" aria-label="Servidores y perfiles">
                            <span
                                class="hidden"
                                wire:key="backup-selection-{{ md5(json_encode([$selectableServers, $selectableProfiles])) }}"
                                x-init="$nextTick(() => { const servers = @js($selectableServers); const profiles = @js($selectableProfiles); if (!servers.includes(selServer)) { selServer = servers[0] ?? ''; selProfile = ''; } if (selProfile && !profiles.includes(selProfile)) { selProfile = ''; } })"
                                aria-hidden="true"
                            ></span>

                            <!-- Encabezado de la tabla: nombre, servidor elegido y todas las acciones en un solo lugar -->
                            <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                                <div class="min-w-0">
                                    <h2 class="panel-accent flex items-center leading-none"><span>Servidores con perfiles</span></h2>
                                    <p class="mt-[15px] leading-tight">Servidores supervisados y los perfiles de respaldo de cada uno.</p>
                                </div>
                                <div class="flex min-w-0 flex-col sm:items-end">
                                    <p class="flex flex-wrap items-center gap-x-[10px] gap-y-[15px] leading-tight sm:justify-end" data-backup-stats>
                                        <span><span class="panel-text tabular-nums">{{ $summary['correct'] }}</span> <span class="panel-muted">correctos</span></span>
                                        <span class="panel-muted" aria-hidden="true">·</span>
                                        <span @if ($summary['errors'] > 0 && $summary['companies'] > 0) title="{{ $summary['companies'] }} {{ $summary['companies'] === 1 ? 'empresa afectada' : 'empresas afectadas' }}" @endif class="{{ $summary['errors'] > 0 ? 'backup-tone-red' : 'panel-muted' }}"><span class="tabular-nums {{ $summary['errors'] > 0 ? '' : 'panel-text' }}">{{ $summary['errors'] }}</span> con error</span>
                                        <span class="panel-muted" aria-hidden="true">·</span>
                                        <span><span class="panel-text tabular-nums">{{ $summary['pending'] }}</span> <span class="panel-muted">sin datos o pendientes</span></span>
                                    </p>
                                </div>
                            </header>

                            <div class="grid min-h-[420px] grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)] xl:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                                <!-- Menú de servidores -->
                                <nav class="no-print panel-border flex min-w-0 flex-col border-b lg:border-b-0 lg:border-r" aria-label="Servidores supervisados">
                                    <header class="panel-surface panel-border flex items-center justify-between gap-[20px] border-b p-[20px]">
                                        <div class="min-w-0">
                                            <h2 class="panel-accent flex items-center gap-[10px] leading-none">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="1.5" /><rect x="3" y="13.5" width="18" height="6.5" rx="1.5" /><path d="M7 7.25h.01M7 16.75h.01" /></svg>
                                            <span>Servidores</span>
                                        </h2>
                                            <p class="mt-[15px] pl-[26px] leading-tight sm:truncate">Equipos que se supervisan.</p>
                                        </div>
                                    <button type="button" wire:click="addServer" class="panel-action backup-action no-print inline-flex shrink-0 items-center gap-[10px] leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Agregar servidor</span></button>
                                    </header>
                                    <div role="tablist" aria-orientation="vertical" class="backup-server-menu flex min-w-0 overflow-x-auto lg:block lg:overflow-visible">
                                        @foreach ($servers as $serverIndex => $server)
                                            @php
                                                $meta = $serverMeta[$serverIndex];
                                                $menuCount = count($server['profiles']);
                                                // Número y texto por separado: el número va en una columna fija para que todos queden alineados en vertical
                                                $menuNum = null;
                                                if ($meta['pending']) { $menuText = $reading ? 'Leyendo…' : 'Pendiente'; }
                                                elseif ($meta['available'] === 0) { $menuText = 'Sin datos'; }
                                                elseif ($meta['errors']) { $menuNum = $meta['errors']; $menuText = 'perfil(es) con error'; }
                                                else { $menuNum = $menuCount; $menuText = 'perfil(es)'; }
                                                $menuTone = $meta['pending'] || $meta['available'] === 0 ? 'panel-muted' : ($meta['errors'] ? 'backup-tone-red' : 'panel-muted');
                                            @endphp
                                            <div wire:key="backup-server-row-{{ $server['key'] }}" class="relative w-[280px] shrink-0 lg:w-full">
                                            <button
                                                type="button"
                                                role="tab"
                                                wire:key="backup-server-tab-{{ $server['key'] }}"
                                                @click="selectServer(@js($server['key']), @js($defaultProfileKeys[$server['key']] ?? ''))"
                                                :aria-selected="selServer === @js($server['key']) ? 'true' : 'false'"
                                                class="backup-server-item panel-border flex w-full flex-col items-start border-b border-r p-[20px] pr-[80px] text-left focus:outline-none focus:ring-0 lg:w-full lg:border-r-0"
                                            >
                                                <span class="flex w-full min-w-0 items-center gap-[10px] leading-none" title="{{ $server['name'] }}">
                                                    <svg class="{{ $meta['errors'] ? 'backup-tone-red' : 'backup-tone-blue' }} h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="1.5" /><rect x="3" y="13.5" width="18" height="6.5" rx="1.5" /><path d="M7 7.25h.01M7 16.75h.01" /></svg>
                                                    <span class="truncate">{{ $server['name'] }}</span>
                                                </span>
                                                <span class="mt-[15px] flex w-full items-center pl-[26px] leading-none {{ $menuTone }}">@if ($menuNum !== null)<span class="w-[20px] shrink-0 tabular-nums">{{ $menuNum }}</span>@endif<span class="truncate">{{ $menuText }}</span></span>
                                            </button>
                                            <span class="no-print absolute right-[20px] top-1/2 flex -translate-y-1/2 items-center gap-[10px]">
                                                <button type="button" wire:click="openServer({{ $serverIndex }})" title="Editar servidor" aria-label="Editar servidor" class="panel-action inline-flex items-center leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.2 5.2 3.6 3.6M4 20l4.2-1 10.1-10.1a2 2 0 0 0 0-2.8l-.4-.4a2 2 0 0 0-2.8 0L5 15.8 4 20Z"/></svg></button>
                                                <button type="button" wire:click="removeServer({{ $serverIndex }})" wire:confirm="¿Eliminar este servidor y sus perfiles del informe?" title="Eliminar servidor" aria-label="Eliminar servidor" class="panel-action inline-flex items-center leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg></button>
                                            </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </nav>

                                <!-- Perfiles y detalle del servidor elegido -->
                                <div class="flex min-w-0 flex-col">
                                    @foreach ($servers as $serverIndex => $server)
                                        @php
                                            $meta = $serverMeta[$serverIndex];
                                            $profileCount = count($server['profiles']);
                                            $rows = [];
                                            foreach ($server['profiles'] as $profileIndex => $profile) {
                                                $isBad = $profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== [];
                                                $hasError = $profile['available'] && $isBad;
                                                $hasIncidents = ($profile['failedFiles'] ?? []) !== [] || $profile['errors'] !== [] || $profile['issues'] !== [] || ($profile['message'] && $profile['done'] && ! $profile['available']);
                                                $rows[$profileIndex] = [
                                                    'key' => $server['key'].':'.$profileIndex,
                                                    'bad' => $isBad,
                                                    'error' => $hasError,
                                                    'tone' => $hasError ? 'backup-tone-red' : ($profile['available'] ? '' : 'panel-muted'),
                                                    'icon' => $hasError ? 'error' : ($profile['available'] ? 'ok' : ($profile['done'] ? 'off' : 'wait')),
                                                    'result' => $profile['available'] ? ($isBad ? 'Error' : ($profile['processed'] === '0/0' ? 'Sin cambios' : 'Todo realizado')) : ($profile['done'] ? 'No disponible' : 'Pendiente'),
                                                    'date' => $profile['available'] ? ($profile['date'] ?: 'Sin fecha') : ($profile['done'] ? 'Sin datos' : ($reading ? 'Leyendo…' : 'Sin leer')),
                                                    'processed' => $profile['available'] ? ($profile['processed'] ?: '—') : '—',
                                                    'hasIncidents' => $hasIncidents,
                                                    'incidentCount' => count($profile['errors']) + count($profile['issues']) + (($profile['message'] && ! $profile['available']) ? 1 : 0),
                                                ];
                                                // Cada dígito (y la a/p de a.m./p.m.) en una celda de 9 px: la fuente no tiene números de ancho fijo y así las fechas quedan alineadas carácter por carácter
                                                $rows[$profileIndex]['dateHtml'] = preg_replace('/\d|[ap](?=\.m\.)/u', '<span class="inline-block w-[9px] text-center">$0</span>', e($rows[$profileIndex]['date']));
                                            }
                                        @endphp
                                        <div wire:key="backup-server-panel-{{ $server['key'] }}" x-show="selServer === @js($server['key'])" x-cloak role="tabpanel" class="flex flex-1 flex-col" data-server-panel>
                                            <h2 class="backup-print-title panel-accent leading-none">{{ $server['name'] }}</h2>
                                            <p class="backup-print-meta panel-muted leading-tight">{{ $server['ip'] }} · {{ $server['share'] }}</p>

                                            <div class="grid flex-1 grid-cols-1 xl:grid-cols-2">
                                                <!-- Perfiles -->
                                                <div class="min-w-0">
                                                    <header class="panel-surface panel-border flex items-center justify-between gap-[20px] border-b p-[20px]">
                                                        <div class="min-w-0">
                                                            <h2 class="panel-accent flex items-center gap-[10px] leading-none">
                                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" /></svg>
                                                            <span>Perfiles</span>
                                                        </h2>
                                                            <p class="mt-[15px] pl-[26px] leading-tight sm:truncate">Respaldos de este servidor.</p>
                                                        </div>
                                                        <button type="button" wire:click="addProfile({{ $serverIndex }})" class="panel-action backup-action no-print inline-flex shrink-0 items-center gap-[10px] leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Agregar perfil</span></button>
                                                    </header>
                                                    @if ($profileCount === 0)
                                                        <p class="panel-border border-b p-[20px] leading-[25px]">Este servidor aún no tiene perfiles. Usa «Agregar perfil» en el encabezado de Perfiles.</p>
                                                    @else
                                                        <ul class="m-0 list-none p-0">
                                                            @foreach ($server['profiles'] as $profileIndex => $profile)
                                                                @php $row = $rows[$profileIndex]; @endphp
                                                                <li
                                                                    wire:key="backup-profile-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}"
                                                                    :class="selProfile === @js($row['key']) ? 'is-selected' : ''"
                                                                    class="backup-profile-item panel-border relative border-b"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        @click="selectProfile(@js($row['key']))"
                                                                        :aria-pressed="selProfile === @js($row['key']) ? 'true' : 'false'"
                                                                        class="block w-full min-w-0 p-[20px] pr-[80px] text-left focus:outline-none focus:ring-0"
                                                                    >
                                                                        <span class="flex items-center justify-between gap-[20px]">
                                                                            <span class="flex min-w-0 items-center gap-[10px] leading-none">
                                                                                @switch($row['icon'])
                                                                                    @case('error')
                                                                                        <svg class="backup-tone-red h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" /><path d="M12 7.5v5M12 16.5h.01" /></svg>
                                                                                        @break
                                                                                    @case('ok')
                                                                                        <svg class="backup-tone-blue h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" /><path d="M8 12.5l2.7 2.7L16 9.5" /></svg>
                                                                                        @break
                                                                                    @default
                                                                                        <svg class="panel-muted h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" /><path d="M12 7v5l3 2" /></svg>
                                                                                @endswitch
                                                                                <span class="truncate">{{ $profile['name'] }}</span>
                                                                            </span>
                                                                        </span>
                                                                        <span class="panel-muted mt-[15px] flex min-w-0 items-center gap-[10px] pl-[26px] leading-none"><span class="w-[19ch] shrink-0 truncate">{!! $row['dateHtml'] !!}</span><span aria-hidden="true">·</span><span class="shrink-0 tabular-nums">{{ $row['processed'] }}</span></span>
                                                                    </button>
                                                                    <span class="no-print absolute right-[20px] top-1/2 flex -translate-y-1/2 items-center gap-[10px]">
                                                                        <button type="button" wire:click="openProfile({{ $serverIndex }}, {{ $profileIndex }})" title="Editar perfil" aria-label="Editar perfil" class="panel-action inline-flex items-center leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.2 5.2 3.6 3.6M4 20l4.2-1 10.1-10.1a2 2 0 0 0 0-2.8l-.4-.4a2 2 0 0 0-2.8 0L5 15.8 4 20Z"/></svg></button>
                                                                        <button type="button" wire:click="removeProfile({{ $serverIndex }}, {{ $profileIndex }})" wire:confirm="¿Eliminar este perfil del informe?" title="Eliminar perfil" aria-label="Eliminar perfil" class="panel-action inline-flex items-center leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg></button>
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>

                                                <!-- Detalle del perfil -->
                                                <div class="panel-border min-w-0 xl:border-l">
                                                    <div wire:key="backup-detail-empty-{{ $server['key'] }}" x-show="!selProfile" x-cloak data-detail-empty>
                                                        <header class="panel-surface panel-border flex items-center justify-between gap-[20px] border-b p-[20px]">
                                                            <div class="min-w-0">
                                                                <h2 class="panel-accent flex items-center gap-[10px] leading-none">
                                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5" /></svg>
                                                                <span>Detalle</span>
                                                            </h2>
                                                                <p class="mt-[15px] pl-[26px] leading-tight sm:truncate">Elige un perfil de la lista.</p>
                                                            </div>
                                                        </header>
                                                        <div class="grid min-h-[260px] place-items-center p-[20px] text-center">
                                                            <div class="max-w-[380px]">
                                                                <span class="panel-surface panel-border panel-text mx-auto grid h-14 w-14 place-items-center rounded-xl border">
                                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7m6-4 3 3m0 0-3 3m3-3h-6"/></svg>
                                                                </span>
                                                                <p class="mt-[20px] leading-[25px]">{{ $profileCount === 0 ? 'Agrega un perfil para ver aquí su detalle.' : 'Elige un perfil de la lista para ver sus incidencias y el detalle de su última ejecución.' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @foreach ($server['profiles'] as $profileIndex => $profile)
                                                        @php $row = $rows[$profileIndex]; @endphp
                                                        <div wire:key="backup-profile-detail-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}" x-show="selProfile === @js($row['key'])" x-cloak data-profile-detail="{{ $row['key'] }}">
                                                            <header class="panel-surface panel-border flex items-center justify-between gap-[20px] border-b p-[20px]">
                                                                <div class="min-w-0">
                                                                    <h2 class="panel-accent flex items-center gap-[10px] leading-none">
                                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5" /></svg>
                                                                    <span>Detalle</span>
                                                                </h2>
                                                                    <p class="mt-[15px] pl-[26px] leading-tight sm:truncate">Última ejecución del perfil {{ $profile['name'] }}.</p>
                                                                </div>
                                                                <button type="button" wire:click="openProfile({{ $serverIndex }}, {{ $profileIndex }})" class="panel-action backup-action no-print inline-flex shrink-0 items-center gap-[10px] leading-none focus:outline-none focus:ring-0"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.2 5.2 3.6 3.6M4 20l4.2-1 10.1-10.1a2 2 0 0 0 0-2.8l-.4-.4a2 2 0 0 0-2.8 0L5 15.8 4 20Z"/></svg><span>Editar perfil</span></button>
                                                            </header>
                                                            <div class="panel-border border-b p-[20px]">
                                                                <div class="flex items-center gap-[10px] leading-none">
                                                                    <svg class="backup-tone-blue h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="6.5" rx="1.5" /><rect x="3" y="13.5" width="18" height="6.5" rx="1.5" /><path d="M7 7.25h.01M7 16.75h.01" /></svg>
                                                                    <span class="panel-accent">Servidor</span>
                                                                </div>
                                                                <span class="mt-[15px] flex flex-wrap items-center gap-x-[10px] gap-y-[15px] pl-[26px] leading-tight"><span>{{ $server['name'] }}</span><span aria-hidden="true">·</span><span>{{ $server['ip'] }}</span><span aria-hidden="true">·</span><span class="break-all">{{ $server['share'] }}</span></span>
                                                            </div>
                                                            {{-- Cada dato: icono y etiqueta centrados en una fila y el valor debajo a 10 px, alineado con la etiqueta --}}
                                                                                                                                                                                    <div class="p-[20px]">
                                                                @if ($row['hasIncidents'])
                                                                    <div class="{{ $row['bad'] ? 'panel-danger-box' : 'panel-subtle panel-border' }} overflow-hidden rounded-xl border">
                                                                        <div class="backup-box-head p-[20px]"><span class="leading-none">Incidencias ({{ $row['incidentCount'] }})</span></div>
                                                                        <ul class="backup-incident-list flex flex-col gap-[15px] py-[20px]">
                                                                            {{-- Solo el nombre de cada empresa (una vez, aunque falle su .mdf y su .ldf), de la más corta a la más larga; si es muy largo se corta con «…» y el nombre completo sale al pasar el cursor --}}
                                                                            @foreach (collect($profile['errors'])->sortBy(fn ($company) => [mb_strlen($company), mb_strtolower($company)])->values() as $error)
                                                                                <li class="truncate px-[20px]" title="{{ str_replace('_', ' ', $error) }}">{{ str_replace('_', ' ', $error) }}</li>
                                                                            @endforeach
                                                                            @foreach ($profile['issues'] as $issue)
                                                                                <li class="break-words px-[20px]">{{ $issue }}</li>
                                                                            @endforeach
                                                                            @if ($profile['message'] && ! $profile['available'])
                                                                                <li class="break-words px-[20px]">{{ $profile['message'] }}</li>
                                                                            @endif
                                                                        </ul>
                                                                    </div>
                                                                @else
                                                                    <p class="panel-muted leading-[25px]">{{ $profile['available'] ? ($row['bad'] ? 'El perfil terminó con error, pero no se registró el detalle.' : 'Este perfil no tiene incidencias.') : ($profile['done'] ? 'No hay datos disponibles de este perfil.' : ($reading ? 'Leyendo este perfil…' : 'Este perfil aún no se ha leído. Presiona “Actualizar registros”.')) }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endif
            </main>
        </div>
    </div>
    <!-- Control de pantalla completa (igual que en Panel general, Administración y Reloj Checador); semitransparente mientras no se usa para no tapar el contenido -->
    <div class="no-print panel-surface panel-border fixed bottom-[50px] right-[50px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] opacity-40 shadow-[0_8px_24px_rgba(0,0,0,0.10)] transition-opacity duration-200 hover:opacity-100 focus-within:opacity-100 lg:flex">
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
                            <header class="panel-surface panel-border flex flex-shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                <div class="flex min-w-0 items-start gap-[15px] sm:items-center">
                                    <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 id="backup-profile-editor-title" class="panel-accent truncate leading-none">{{ $editingProfile === null ? 'Agregar perfil' : 'Editar perfil' }}</h3>
                                        <p class="mt-[15px] leading-tight sm:truncate">Los ajustes solo afectan el informe actual.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showProfileEditor', false)" class="backup-modal-close inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0" aria-label="Cerrar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </header>

                            <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                                    <div class="flex flex-col gap-[20px] p-[20px]">
                                        <section class="backup-modal-section">
                                            <h4 class="panel-accent text-[15px] font-normal leading-none">Datos del perfil</h4>
                                            <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                                <div class="min-w-0">
                                                    <label for="backup-profile-name" class="mb-[10px] block">Perfil *</label>
                                                    <input id="backup-profile-name" type="text" wire:model="profileForm.name" placeholder="Ej. Comprobantes33" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                    @error('profileForm.name')<p class="backup-tone-red mt-[15px]">{{ $message }}</p>@enderror
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-log" class="mb-[10px] block">Nombre del log</label>
                                                    <input id="backup-profile-log" type="text" wire:model="profileForm.log" placeholder="Ej. Comprobantes33" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-date" class="mb-[10px] block">Fecha y hora</label>
                                                    <input id="backup-profile-date" type="text" wire:model="profileForm.date" placeholder="Ej. 31/10/2026 04:00 p. m." class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-processed" class="mb-[10px] block">Procesados</label>
                                                    <input id="backup-profile-processed" type="text" wire:model="profileForm.processed" placeholder="Ej. 158/176" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                </div>
                                            </div>
                                        </section>

                                        <section class="backup-modal-section">
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
                                        </section>

                                        <section class="backup-modal-section">
                                            <h4 class="panel-accent text-[15px] font-normal leading-none">Informe</h4>
                                            <div class="mt-[20px] grid grid-cols-1 gap-[20px]">
                                                <label class="flex cursor-pointer items-center gap-[10px]">
                                                    <input wire:model="profileForm.available" type="checkbox" class="backup-check">
                                                    <span>Incluir este perfil en el PDF</span>
                                                </label>
                                                <details class="panel-border border-t pt-[20px]">
                                                    <summary class="panel-accent cursor-pointer">Datos técnicos</summary>
                                                    <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-source" class="mb-[10px] block">Origen</label>
                                                            <input id="backup-profile-source" type="text" wire:model="profileForm.source" placeholder="Ej. C:\Compac\" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-destination" class="mb-[10px] block">Destino</label>
                                                            <input id="backup-profile-destination" type="text" wire:model="profileForm.destination" placeholder="Ej. D:\Contpaq\" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-duration" class="mb-[10px] block">Duración</label>
                                                            <input id="backup-profile-duration" type="text" wire:model="profileForm.duration" placeholder="Ej. 00:02:10" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>

                            <footer class="panel-border flex flex-shrink-0 flex-wrap items-center justify-end gap-[10px] border-t p-[20px]">
                                @if ($editingProfile !== null)<button type="button" wire:click="deleteProfile" wire:confirm="¿Eliminar este perfil del informe?" class="backup-modal-delete inline-flex h-11 w-full items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0 sm:mr-auto sm:w-auto">Eliminar</button>@endif
                                <button type="button" wire:click="$set('showProfileEditor', false)" class="backup-modal-outline inline-flex h-11 min-w-28 flex-1 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0 sm:flex-none">Cancelar</button>
                                <button type="submit" class="backup-modal-primary inline-flex h-11 min-w-28 flex-1 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0 sm:flex-none">Guardar</button>
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
                            <header class="panel-surface panel-border flex flex-shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                                <div class="flex min-w-0 items-start gap-[15px] sm:items-center">
                                    <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 6.5h14v5H5v-5Zm0 6h14v5H5v-5ZM8 9h.01M8 15h.01"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 id="backup-server-editor-title" class="panel-accent truncate leading-none">{{ $addingServer ? 'Agregar servidor' : 'Editar servidor' }}</h3>
                                        <p class="mt-[15px] leading-tight sm:truncate">Define el nombre y el recurso compartido donde están los logs.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showServerEditor', false)" class="backup-modal-close inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0" aria-label="Cerrar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </header>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <div class="p-[20px]">
                                    <section class="backup-modal-section">
                                        <h4 class="panel-accent text-[15px] font-normal leading-none">Datos del servidor</h4>
                                        <div class="mt-[20px] grid grid-cols-1 gap-[20px]">
                                            <div class="min-w-0">
                                                <label for="backup-server-name" class="mb-[10px] block">Servidor *</label>
                                                <input id="backup-server-name" type="text" wire:model="serverForm.name" placeholder="Ej. Ifacture" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                                @error('serverForm.name')<p class="backup-tone-red mt-[15px]">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="min-w-0">
                                                <label for="backup-server-ip" class="mb-[10px] block">Dirección IP</label>
                                                <input id="backup-server-ip" type="text" wire:model="serverForm.ip" placeholder="Ej. 192.168.2.178" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                            </div>
                                            <div class="min-w-0">
                                                <label for="backup-server-share" class="mb-[10px] block">Carpeta compartida</label>
                                                <input id="backup-server-share" type="text" wire:model="serverForm.share" placeholder="Ej. \\SRVIFAC\log" class="panel-field block h-11 w-full rounded-xl border px-[15px] focus:ring-0">
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>

                            <footer class="panel-border flex flex-shrink-0 flex-wrap items-center justify-end gap-[10px] border-t p-[20px]">
                                @if (! $addingServer)<button type="button" wire:click="deleteServer" wire:confirm="¿Eliminar este servidor y sus perfiles del informe?" class="backup-modal-delete inline-flex h-11 w-full items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0 sm:mr-auto sm:w-auto">Eliminar</button>@endif
                                <button type="button" wire:click="$set('showServerEditor', false)" class="backup-modal-outline inline-flex h-11 min-w-28 flex-1 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0 sm:flex-none">Cancelar</button>
                                <button type="submit" class="backup-modal-primary inline-flex h-11 min-w-28 flex-1 items-center justify-center rounded-xl px-[20px] focus:outline-none focus:ring-0 sm:flex-none">Guardar</button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
