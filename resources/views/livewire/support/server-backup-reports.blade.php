<div
    class="support-backup-report-page relative isolate min-h-[calc(100dvh-100px)] w-full overflow-hidden bg-white text-[15px] text-black"
    x-data="{ viewScale: 90, isFullscreen: false, isCompact: window.innerWidth < 1024, selServer: @js($servers[0]['key'] ?? ''), selProfile: '', autoScanStarted: false, currentServer: '', currentProfile: '', processed: $wire.entangle('processedProfiles'), total: $wire.entangle('totalProfiles'), scanning: $wire.entangle('reading'), statusMessage: $wire.entangle('statusMessage'), recipientsOpen: false, selectedRecipients: $wire.entangle('emailRecipients'), mailSending: false, mailStatus: @js($emailStatus), mailSent: {{ $emailSent ? 'true' : 'false' }}, init() { if (!this.autoScanStarted) { this.autoScanStarted = true; setTimeout(() => $wire.startReading(), 800); } }, selectProfile(key) { this.selProfile = this.selProfile === key ? '' : key; if (this.selProfile && window.innerWidth < 1280) { this.$nextTick(() => { const el = document.querySelector('[data-profile-detail=\'' + key + '\']'); if (el) { el.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } }); } }, async sendReport() { if (this.mailSending) return; const scrollPosition = window.scrollY; this.mailSending = true; this.mailStatus = ''; try { const result = await this.$wire.sendEmail(); this.mailSent = result.sent; this.mailStatus = result.status; if (result.sent) this.recipientsOpen = false; } finally { this.mailSending = false; this.$nextTick(() => window.scrollTo(0, scrollPosition)); } }, saveScale() { localStorage.setItem('backup-reports-view-scale', String(this.viewScale)); }, async toggleFullscreen(container) { if (document.fullscreenElement === container) return document.exitFullscreen(); if (document.fullscreenElement) await document.exitFullscreen(); await container.requestFullscreen(); } }"
    x-on:backup-read-next.window="setTimeout(() => $wire.readNext(), 300)"
    x-on:backup-scan-progress.window="processed = $event.detail.processed; total = $event.detail.total; scanning = $event.detail.reading; statusMessage = $event.detail.message; currentServer = $event.detail.server || ''; currentProfile = $event.detail.profile || ''; if ($event.detail.reading) { mailStatus = ''; mailSent = false; }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
    @resize.window.debounce.150ms="isCompact = window.innerWidth < 1024"
>
    <span
        class="hidden"
        wire:key="backup-scan-state-{{ $processedProfiles }}-{{ $totalProfiles }}-{{ $reading ? 'reading' : 'idle' }}"
        x-init="$nextTick(() => { processed = {{ $processedProfiles }}; total = {{ $totalProfiles }}; scanning = {{ $reading ? 'true' : 'false' }}; statusMessage = @js($statusMessage); })"
        aria-hidden="true"
    ></span>

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm lg:flex">
        <button type="button" @click="viewScale = Math.max(70, viewScale - 5); saveScale()" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista">
        <button type="button" @click="viewScale = Math.min(100, viewScale + 5); saveScale()" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right tabular-nums text-black" x-text="viewScale + '%'">90%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen($root)" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver el informe en pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-100px)] min-w-0 origin-top" :style="isCompact ? 'width: 100%; margin-left: 0; transform: none;' : `width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">
        <main class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
            <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px]">
                <div class="flex max-w-full shrink-0 items-center gap-[15px]">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 18h16M6 15V9m4 6V5m4 10v-3m4 3V7"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-normal leading-none tracking-tight text-[#1A3A6B]">Informe Técnico de Respaldos</h1>
                        <p class="mt-[10px] leading-tight text-black">Supervisa servidores, perfiles e incidencias.</p>
                    </div>
                </div>

                <div class="no-print flex flex-wrap items-center gap-x-[20px]">
                    <button type="button" wire:click="startReading" wire:loading.attr="disabled" :disabled="scanning" class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                        Actualizar registros
                    </button>
                    <div class="relative flex items-center self-center select-none">
                        <button type="button" @click="recipientsOpen = !recipientsOpen" :disabled="mailSending || scanning || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="select-none inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6.75A1.75 1.75 0 0 1 4.75 5h14.5A1.75 1.75 0 0 1 21 6.75v10.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Zm.5-.5L12 13l8.5-6.75"/></svg>
                            <span>Enviar reporte por correo</span>
                        </button>
                            <div x-show="recipientsOpen" x-cloak x-transition.opacity.duration.150ms x-on:click.outside="recipientsOpen = false" class="fixed inset-x-[15px] top-[150px] z-50 whitespace-normal rounded-xl border border-[#d4d4d8] bg-white p-[20px] shadow-[0_18px_50px_rgba(0,0,0,0.18)] sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:w-[370px]">
                                <div class="mb-[15px]">
                                    <strong class="block font-normal leading-none text-[#1A3A6B]">Enviar reporte a</strong>
                                    <span class="mt-[10px] block leading-none text-black">Selecciona uno o varios destinatarios.</span>
                                </div>
                                <div class="space-y-[10px]">
                                    @foreach ($recipientOptions as $email => $label)
                                        <label wire:key="backup-recipient-{{ $email }}" class="flex cursor-pointer select-none items-center gap-[10px] rounded-xl border border-[#d4d4d8] px-[20px] py-[10px] outline-none transition hover:bg-zinc-50 focus-within:border-[#1A3A6B] focus-within:outline-none focus-within:ring-0">
                                            <input x-model="selectedRecipients" type="checkbox" value="{{ $email }}" class="h-4 w-4 rounded border-[#d4d4d8] text-[#1A3A6B] outline-none focus:outline-none focus:ring-0 focus:ring-offset-0">
                                            <span class="min-w-0"><strong class="block font-normal text-black">{{ $label }}</strong><span class="block truncate text-black">{{ $email }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                                <p x-show="selectedRecipients.length === 0" x-cloak class="mt-[10px] text-amber-700">Selecciona al menos un correo.</p>
                                <button type="button" @click.prevent.stop="sendReport()" :disabled="mailSending || scanning || total === 0 || processed < total || selectedRecipients.length === 0" class="mt-[10px] ml-auto flex select-none items-center py-[10px] leading-none text-[#1A3A6B] outline-none transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                                    <span x-show="!mailSending">Enviar reporte</span>
                                    <span x-show="mailSending" x-cloak>Enviando...</span>
                                </button>
                            </div>
                    </div>
                    <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" :disabled="scanning || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                        Guardar PDF
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                <header class="flex shrink-0 flex-col justify-between gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px] lg:flex-row lg:items-center lg:gap-[20px]">
                    <div class="min-w-0">
                        <h2 class="leading-none text-[#1A3A6B]">Resumen del informe</h2>
                        <p class="mt-[10px] leading-none text-black">Estado de los perfiles de respaldo revisados</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-[10px] leading-none text-black"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>Acceso exclusivo para administradores</div>
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
                <div class="border-b border-[#d4d4d8] p-[20px]" aria-live="polite">
                    <div class="block truncate" :class="mailStatus ? (mailSent ? 'text-emerald-700' : 'text-amber-800') : 'text-black'" x-text="mailSending ? 'Generando PDF y enviando el correo...' : (mailStatus || statusMessage)">{{ $statusMessage }}</div>
                    <div class="mt-[15px] flex items-center gap-[15px]">
                        <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-zinc-200">
                            <div x-show="!mailSending && !mailStatus" class="h-full rounded-full bg-[#1A3A6B] transition-all duration-300" :style="`width: ${total > 0 ? Math.min(100, (processed / total) * 100) : 0}%`" style="width: {{ $totalProfiles > 0 ? min(100, ($processedProfiles / $totalProfiles) * 100) : 0 }}%"></div>
                            <div x-show="mailSending" x-cloak class="h-full w-1/2 animate-pulse rounded-full bg-[#1A3A6B]"></div>
                            <div x-show="!mailSending && mailStatus" x-cloak class="h-full rounded-full transition-all duration-300" :class="mailSent ? 'w-full bg-emerald-600' : 'w-1/3 bg-amber-500'"></div>
                        </div>
                        <div class="flex shrink-0 items-center gap-[15px]">
                            <span x-show="!mailSending && !mailStatus" class="tabular-nums text-black"><span x-text="processed">{{ $processedProfiles }}</span> / <span x-text="total">{{ $totalProfiles }}</span></span>
                            <span x-show="mailSending" x-cloak class="text-black">Procesando</span>
                            <span x-show="!mailSending && mailStatus" x-cloak class="" :class="mailSent ? 'text-emerald-700' : 'text-amber-800'" x-text="mailSent ? '100%' : 'Aviso'"></span>
                        </div>
                    </div>
                    <div class="mt-[10px] min-w-0 text-black sm:truncate" x-text="mailSending || mailStatus ? 'El reporte se envía a los destinatarios seleccionados.' : (scanning && currentServer ? `Lectura de solo acceso, perfil por perfil. Cargando ${currentServer} · ${currentProfile}…` : 'Lectura de solo acceso, perfil por perfil.')">Lectura de solo acceso, perfil por perfil.</div>
                </div>
                <div class="grid grid-cols-1 gap-px bg-[#d4d4d8] sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($reportStats as $statIndex => [$label, $value])
                        <div class="flex min-w-0 items-center gap-[15px] bg-white p-[20px] text-black">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-black">
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
                                <span class="block leading-none tabular-nums text-[#1A3A6B]">{{ $value }}</span>
                                <span class="mt-[10px] block truncate leading-none text-black">{{ $label }}</span>
                            </div>
                        </div>
                    @endforeach
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
                <section class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                    <span
                        class="hidden"
                        wire:key="backup-selection-{{ md5(json_encode([$selectableServers, $selectableProfiles])) }}"
                        x-init="$nextTick(() => { const servers = @js($selectableServers); const profiles = @js($selectableProfiles); if (!servers.includes(selServer)) { selServer = servers[0] ?? ''; selProfile = ''; } if (selProfile && !profiles.includes(selProfile)) { selProfile = ''; } })"
                        aria-hidden="true"
                    ></span>

                    <div class="no-print flex flex-wrap items-end gap-x-[20px] border-b border-[#d4d4d8] bg-zinc-100 px-[20px]" role="tablist" aria-label="Servidores supervisados">
                        @foreach ($servers as $serverIndex => $server)
                            @php $meta = $serverMeta[$serverIndex]; @endphp
                            <button
                                type="button"
                                role="tab"
                                wire:key="backup-server-tab-{{ $server['key'] }}"
                                @click="selServer = @js($server['key']); selProfile = ''"
                                :aria-selected="selServer === @js($server['key']) ? 'true' : 'false'"
                                :class="selServer === @js($server['key']) ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-transparent text-black hover:text-[#1A3A6B]'"
                                class="-mb-px inline-flex h-[50px] max-w-full items-center gap-[10px] border-b-2 leading-none transition-colors focus:outline-none focus:ring-0"
                            >
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $meta['errors'] ? 'bg-red-500' : ($meta['pending'] ? 'bg-amber-400' : ($meta['available'] === 0 ? 'bg-zinc-400' : 'bg-emerald-500')) }}"></span>
                                <span class="min-w-0 truncate">{{ $server['name'] }}</span>
                                @if ($meta['errors'] > 0)
                                    <span class="shrink-0 rounded-full border border-red-200 bg-white px-[10px] py-[5px] leading-none tabular-nums text-red-700" title="{{ $meta['errors'] }} {{ $meta['errors'] === 1 ? 'perfil con error' : 'perfiles con error' }}">{{ $meta['errors'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @foreach ($servers as $serverIndex => $server)
                        @php
                            $meta = $serverMeta[$serverIndex];
                            $profileCount = count($server['profiles']);
                            $serverStatus = $meta['pending'] ? 'Leyendo' : ($meta['available'] === 0 ? 'Sin datos' : ($meta['errors'] ? 'Con incidencias' : 'Todo realizado'));
                            $rows = [];
                            foreach ($server['profiles'] as $profileIndex => $profile) {
                                $isBad = $profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== [];
                                $hasError = $profile['available'] && $isBad;
                                $hasIncidents = ($profile['failedFiles'] ?? []) !== [] || $profile['errors'] !== [] || $profile['issues'] !== [] || ($profile['message'] && $profile['done'] && ! $profile['available']);
                                $rows[$profileIndex] = [
                                    'key' => $server['key'].':'.$profileIndex,
                                    'bad' => $isBad,
                                    'error' => $hasError,
                                    'dot' => $hasError ? 'bg-red-500' : ($profile['available'] ? 'bg-emerald-500' : ($profile['done'] ? 'bg-zinc-400' : 'bg-amber-400')),
                                    'result' => $profile['available'] ? ($isBad ? 'Error' : ($profile['processed'] === '0/0' ? 'Sin cambios' : 'Todo realizado')) : ($profile['done'] ? 'No disponible' : 'Pendiente'),
                                    'date' => $profile['available'] ? ($profile['date'] ?: 'Sin fecha') : ($profile['done'] ? 'Sin datos' : 'Leyendo…'),
                                    'processed' => $profile['available'] ? ($profile['processed'] ?: '—') : '—',
                                    'hasIncidents' => $hasIncidents,
                                    'incidentCount' => (($profile['failedFiles'] ?? []) !== [] ? count($profile['failedFiles']) : count($profile['errors'])) + count($profile['issues']) + (($profile['message'] && ! $profile['available']) ? 1 : 0),
                                ];
                            }
                        @endphp
                        <div wire:key="backup-server-panel-{{ $server['key'] }}" x-show="selServer === @js($server['key'])" x-cloak role="tabpanel" data-server-panel>
                            <h2 class="backup-print-title leading-none text-[#1A3A6B]">{{ $server['name'] }}</h2>

                            <div class="flex flex-col items-start justify-between gap-[10px] border-b border-[#d4d4d8] bg-white px-[20px] py-[10px] sm:flex-row sm:items-center sm:gap-[20px]">
                                <div class="flex min-w-0 flex-wrap items-center gap-x-[20px] gap-y-[10px] text-black">
                                    <span class="flex min-w-0 items-center gap-[10px] leading-none">
                                        <span class="shrink-0">{{ $server['ip'] }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span class="truncate font-mono">{{ $server['share'] }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-[10px] leading-none"><span class="h-2 w-2 shrink-0 rounded-full {{ $meta['pending'] ? 'bg-amber-400' : ($meta['available'] === 0 ? 'bg-zinc-400' : ($meta['errors'] ? 'bg-red-500' : 'bg-emerald-500')) }}"></span>{{ $serverStatus }}</span>
                                </div>
                                <div class="no-print flex shrink-0 flex-wrap items-center gap-x-[20px]">
                                    <button type="button" wire:click="openServer({{ $serverIndex }})" class="inline-flex items-center gap-[10px] py-[10px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M9 11l7.6-7.6a2 2 0 0 1 2.8 0l1.2 1.2a2 2 0 0 1 0 2.8L13 15l-4 1 1-4ZM5 19h14"/></svg>Editar servidor</button>
                                    <button type="button" wire:click="addProfile({{ $serverIndex }})" class="inline-flex items-center gap-[10px] py-[10px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14"/></svg>Agregar perfil</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-5">
                                <div class="min-w-0 xl:col-span-2">
                                    <header class="border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                        <h2 class="leading-none text-[#1A3A6B]">Perfiles</h2>
                                        <p class="mt-[10px] flex flex-wrap items-center gap-x-[10px] leading-none text-black">
                                            <span>{{ $profileCount }} {{ $profileCount === 1 ? 'perfil configurado' : 'perfiles configurados' }}</span>
                                            @if ($meta['errors'] > 0)
                                                <span aria-hidden="true">·</span>
                                                <span class="text-red-700">{{ $meta['errors'] }} con error</span>
                                            @endif
                                        </p>
                                    </header>
                                    @if ($profileCount === 0)
                                        <p class="p-[20px] leading-[25px] text-black">Este servidor aún no tiene perfiles. Usa «Agregar perfil» para crear el primero.</p>
                                    @else
                                        <ul class="m-0 list-none p-0">
                                            @foreach ($server['profiles'] as $profileIndex => $profile)
                                                @php $row = $rows[$profileIndex]; @endphp
                                                <li
                                                    wire:key="backup-profile-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}"
                                                    :class="selProfile === @js($row['key']) ? 'bg-zinc-100' : 'hover:bg-zinc-50'"
                                                    class="relative flex items-stretch border-b border-[#d4d4d8] transition-colors last:border-b-0"
                                                >
                                                    <span x-show="selProfile === @js($row['key'])" x-cloak class="absolute inset-y-0 left-0 w-[2px] bg-[#1A3A6B]" aria-hidden="true"></span>
                                                    <button
                                                        type="button"
                                                        @click="selectProfile(@js($row['key']))"
                                                        :aria-pressed="selProfile === @js($row['key']) ? 'true' : 'false'"
                                                        class="min-w-0 flex-1 px-[20px] py-[15px] text-left text-black focus:outline-none focus:ring-0"
                                                    >
                                                        <span class="flex items-center justify-between gap-[15px]">
                                                            <span class="flex min-w-0 items-center gap-[10px] leading-none"><span class="h-2 w-2 shrink-0 rounded-full {{ $row['dot'] }}"></span><span class="truncate" :class="selProfile === @js($row['key']) ? 'text-[#1A3A6B]' : ''">{{ $profile['name'] }}</span></span>
                                                            <span class="shrink-0 leading-none {{ $row['error'] ? 'text-red-700' : 'text-black' }}">{{ $row['result'] }}</span>
                                                        </span>
                                                        <span class="mt-[10px] flex min-w-0 items-center gap-[10px] pl-[18px] leading-none text-black"><span class="truncate">{{ $row['date'] }}</span><span aria-hidden="true">·</span><span class="shrink-0 font-mono">{{ $row['processed'] }}</span></span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <div class="min-w-0 border-t border-[#d4d4d8] xl:col-span-3 xl:border-l xl:border-t-0">
                                    <div wire:key="backup-detail-empty-{{ $server['key'] }}" x-show="!selProfile" x-cloak data-detail-empty>
                                        <header class="border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                            <h2 class="leading-none text-[#1A3A6B]">Detalle del perfil</h2>
                                            <p class="mt-[10px] leading-none text-black">Ningún perfil seleccionado</p>
                                        </header>
                                        <div class="grid min-h-[260px] place-items-center p-[20px] text-center">
                                            <div class="max-w-[380px]">
                                                <span class="mx-auto grid h-14 w-14 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7m6-4 3 3m0 0-3 3m3-3h-6"/></svg>
                                                </span>
                                                <p class="mt-[20px] leading-none text-[#1A3A6B]">Selecciona un perfil de la lista</p>
                                                <p class="mt-[10px] leading-[25px] text-black">Aquí verás sus incidencias y el detalle de su última ejecución.</p>
                                                @if ($meta['errors'] > 0)
                                                    <p class="mt-[20px] leading-[25px] text-red-700">{{ $meta['errors'] }} {{ $meta['errors'] === 1 ? 'perfil con error' : 'perfiles con error' }} en este servidor, marcados en rojo.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @foreach ($server['profiles'] as $profileIndex => $profile)
                                        @php $row = $rows[$profileIndex]; @endphp
                                        <div wire:key="backup-profile-detail-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}" x-show="selProfile === @js($row['key'])" x-cloak data-profile-detail="{{ $row['key'] }}">
                                            <header class="flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                                <div class="min-w-0">
                                                    <h2 class="truncate leading-none text-[#1A3A6B]">{{ $profile['name'] }}</h2>
                                                    <p class="mt-[10px] truncate leading-none text-black">{{ $server['name'] }}</p>
                                                </div>
                                                <div class="flex shrink-0 flex-wrap items-center gap-x-[20px] gap-y-[10px]">
                                                    <span class="inline-flex items-center gap-[10px] leading-none {{ $row['error'] ? 'text-red-700' : 'text-black' }}"><span class="h-2 w-2 shrink-0 rounded-full {{ $row['dot'] }}"></span>{{ $row['result'] }}</span>
                                                    <button type="button" wire:click="openProfile({{ $serverIndex }}, {{ $profileIndex }})" class="no-print inline-flex items-center gap-[10px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M9 11l7.6-7.6a2 2 0 0 1 2.8 0l1.2 1.2a2 2 0 0 1 0 2.8L13 15l-4 1 1-4ZM5 19h14"/></svg>Editar perfil</button>
                                                </div>
                                            </header>
                                            <div class="p-[20px]">
                                                <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                                    <div class="min-w-0"><span class="block leading-none text-[#1A3A6B]">Última ejecución</span><span class="mt-[10px] block truncate leading-none text-black">{{ $row['date'] }}</span></div>
                                                    <div class="min-w-0"><span class="block leading-none text-[#1A3A6B]">Hecho</span><span class="mt-[10px] block truncate font-mono leading-none text-black">{{ $row['processed'] }}</span></div>
                                                    <div class="min-w-0"><span class="block leading-none text-[#1A3A6B]">Incidencias</span><span class="mt-[10px] block leading-none tabular-nums text-black">{{ $row['hasIncidents'] ? $row['incidentCount'] : 0 }}</span></div>
                                                </div>

                                                @if ($row['hasIncidents'])
                                                    <div class="mt-[20px] overflow-hidden rounded-xl border {{ $row['bad'] ? 'border-red-200 bg-red-50' : 'border-[#d4d4d8] bg-zinc-50' }}">
                                                        <div class="flex items-center justify-between gap-[15px] border-b px-[20px] py-[15px] {{ $row['bad'] ? 'border-red-200' : 'border-[#d4d4d8]' }}">
                                                            <span class="inline-flex items-center gap-[10px] leading-none {{ $row['bad'] ? 'text-red-700' : 'text-black' }}">
                                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8v5m0 3h.01M10.3 4.7 3.8 16a2 2 0 0 0 1.7 3h13a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z"/></svg>
                                                                Incidencias
                                                            </span>
                                                            <span class="shrink-0 rounded-full border bg-white px-[10px] py-[5px] leading-none tabular-nums {{ $row['bad'] ? 'border-red-200 text-red-700' : 'border-[#d4d4d8] text-black' }}">{{ $row['incidentCount'] }}</span>
                                                        </div>
                                                        <ul class="m-0 list-none divide-y p-0 {{ $row['bad'] ? 'divide-red-200' : 'divide-[#d4d4d8]' }}">
                                                            @if (($profile['failedFiles'] ?? []) !== [])
                                                                @foreach ($profile['failedFiles'] as $file)
                                                                    <li class="break-all px-[20px] py-[10px] font-mono text-red-700">{{ is_array($file) ? ($file['path'] ?? '') : $file }}</li>
                                                                @endforeach
                                                            @else
                                                                @foreach ($profile['errors'] as $error)
                                                                    <li class="break-all px-[20px] py-[10px] text-red-700">{{ $error }}</li>
                                                                @endforeach
                                                            @endif
                                                            @foreach ($profile['issues'] as $issue)
                                                                <li class="break-words px-[20px] py-[10px] text-red-700">{{ $issue }}</li>
                                                            @endforeach
                                                            @if ($profile['message'] && ! $profile['available'])
                                                                <li class="break-words px-[20px] py-[10px] text-black">{{ $profile['message'] }}</li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                @else
                                                    <div class="mt-[20px] flex items-start gap-[10px] rounded-xl border border-[#d4d4d8] bg-zinc-50 p-[20px] leading-[25px] text-black">
                                                        <span class="mt-[8px] h-2 w-2 shrink-0 rounded-full {{ $row['dot'] }}"></span>
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
                    class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="backup-profile-editor-title"
                    data-administration-modal="backup-profile-editor"
                >
                    <x-administration-modal-styles />

                    <div class="administration-form-panel relative flex w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl" style="max-width: 620px; height: min(620px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;">
                        <form wire:submit="saveProfile" class="flex min-h-0 flex-1 flex-col">
                            <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
                                <div class="flex min-w-0 items-center gap-[15px]">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-[10px]">
                                        <h1 id="backup-profile-editor-title" class="truncate text-[15px] font-semibold leading-none text-black" style="margin: 0;">{{ $editingProfile === null ? 'Agregar perfil' : 'Editar perfil' }}</h1>
                                        <p class="truncate text-[15px] leading-none text-zinc-500" style="margin: 0;">Los ajustes solo afectan el informe actual.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showProfileEditor', false)" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xl leading-none text-zinc-500 transition hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0" aria-label="Cerrar">&times;</button>
                            </header>

                            <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                                    <div class="space-y-[20px] p-[20px] text-[15px]">
                                        <section>
                                            <h2>Datos del perfil</h2>
                                            <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                                <div class="min-w-0">
                                                    <label for="backup-profile-name" class="block text-[15px] text-black">Perfil *</label>
                                                    <input id="backup-profile-name" type="text" wire:model="profileForm.name" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                    @error('profileForm.name')<p class="mt-[10px] text-red-600">{{ $message }}</p>@enderror
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-log" class="block text-[15px] text-black">Nombre del log</label>
                                                    <input id="backup-profile-log" type="text" wire:model="profileForm.log" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-date" class="block text-[15px] text-black">Fecha y hora</label>
                                                    <input id="backup-profile-date" type="text" wire:model="profileForm.date" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-processed" class="block text-[15px] text-black">Hecho</label>
                                                    <input id="backup-profile-processed" type="text" wire:model="profileForm.processed" placeholder="0/0" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                </div>
                                            </div>
                                        </section>

                                        <section>
                                            <h2>Incidencias del perfil</h2>
                                            <div class="grid grid-cols-1 gap-[20px]">
                                                <div class="min-w-0">
                                                    <label for="backup-profile-errors" class="block text-[15px] text-black">Empresas con error</label>
                                                    <textarea id="backup-profile-errors" wire:model="profileForm.errors" rows="4" placeholder="Una empresa por línea" class="mt-[20px] block w-full rounded-xl border border-zinc-200 bg-white px-[15px] py-[12px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0"></textarea>
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="backup-profile-issues" class="block text-[15px] text-black">Incidencias generales</label>
                                                    <textarea id="backup-profile-issues" wire:model="profileForm.issues" rows="3" placeholder="Ej. Directorio destino inválido" class="mt-[20px] block w-full rounded-xl border border-zinc-200 bg-white px-[15px] py-[12px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0"></textarea>
                                                </div>
                                            </div>
                                        </section>

                                        <section>
                                            <h2>Informe</h2>
                                            <div class="grid grid-cols-1 gap-[20px]">
                                                <label class="flex cursor-pointer items-center gap-[10px] text-[15px] text-black">
                                                    <input wire:model="profileForm.available" type="checkbox" class="h-4 w-4 rounded border-zinc-300 focus:ring-0 focus:ring-offset-0" style="accent-color: #1A3A6B;">
                                                    <span>Incluir este perfil en el PDF</span>
                                                </label>
                                                <details class="rounded-xl border border-zinc-200 px-[20px] py-[15px]">
                                                    <summary class="cursor-pointer text-black">Datos técnicos</summary>
                                                    <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-3">
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-source" class="block text-[15px] text-black">Origen</label>
                                                            <input id="backup-profile-source" type="text" wire:model="profileForm.source" placeholder="Origen" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-destination" class="block text-[15px] text-black">Destino</label>
                                                            <input id="backup-profile-destination" type="text" wire:model="profileForm.destination" placeholder="Destino" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label for="backup-profile-duration" class="block text-[15px] text-black">Duración</label>
                                                            <input id="backup-profile-duration" type="text" wire:model="profileForm.duration" placeholder="Duración" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>

                            <footer class="flex flex-shrink-0 items-center justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
                                @if ($editingProfile !== null)<button type="button" wire:click="deleteProfile" wire:confirm="¿Eliminar este perfil del informe?" class="mr-auto inline-flex min-w-28 items-center justify-center px-5 text-[15px] transition focus:outline-none focus:ring-0" style="color: #dc2626 !important; border-color: #fecaca !important;">Eliminar</button>@endif
                                <button type="button" wire:click="$set('showProfileEditor', false)" class="inline-flex min-w-28 items-center justify-center px-5 text-[15px] transition focus:outline-none focus:ring-0">Cancelar</button>
                                <button type="submit" class="inline-flex min-w-28 items-center justify-center px-5 text-[15px] transition focus:outline-none focus:ring-0">Guardar</button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

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
                    class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="backup-server-editor-title"
                    data-administration-modal="backup-server-editor"
                >
                    <x-administration-modal-styles />

                    <div class="administration-form-panel relative flex w-full flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl" style="max-width: 620px; max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;">
                        <form wire:submit="saveServer" class="flex min-h-0 flex-1 flex-col">
                            <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
                                <div class="flex min-w-0 items-center gap-[15px]">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 6.5h14v5H5v-5Zm0 6h14v5H5v-5ZM8 9h.01M8 15h.01"/></svg>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-[10px]">
                                        <h1 id="backup-server-editor-title" class="truncate text-[15px] font-semibold leading-none text-black" style="margin: 0;">Editar servidor</h1>
                                        <p class="truncate text-[15px] leading-none text-zinc-500" style="margin: 0;">Define el nombre y el recurso compartido donde están los logs.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showServerEditor', false)" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xl leading-none text-zinc-500 transition hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0" aria-label="Cerrar">&times;</button>
                            </header>

                            <div class="min-h-0 flex-1 overflow-y-auto bg-white">
                                <div class="p-[20px] text-[15px]">
                                    <section>
                                        <h2>Datos del servidor</h2>
                                        <div class="grid grid-cols-1 gap-[20px]">
                                            <div class="min-w-0">
                                                <label for="backup-server-name" class="block text-[15px] text-black">Servidor *</label>
                                                <input id="backup-server-name" type="text" wire:model="serverForm.name" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                                @error('serverForm.name')<p class="mt-[10px] text-red-600">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="min-w-0">
                                                <label for="backup-server-ip" class="block text-[15px] text-black">Dirección IP</label>
                                                <input id="backup-server-ip" type="text" wire:model="serverForm.ip" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                            </div>
                                            <div class="min-w-0">
                                                <label for="backup-server-share" class="block text-[15px] text-black">Carpeta compartida</label>
                                                <input id="backup-server-share" type="text" wire:model="serverForm.share" placeholder="\\SERVIDOR\log" class="mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] font-mono text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0">
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>

                            <footer class="flex flex-shrink-0 items-center justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
                                <button type="button" wire:click="deleteServer" wire:confirm="¿Eliminar este servidor y sus perfiles del informe?" class="mr-auto inline-flex min-w-28 items-center justify-center px-5 text-[15px] transition focus:outline-none focus:ring-0" style="color: #dc2626 !important; border-color: #fecaca !important;">Eliminar</button>
                                <button type="button" wire:click="$set('showServerEditor', false)" class="inline-flex min-w-28 items-center justify-center px-5 text-[15px] transition focus:outline-none focus:ring-0">Cancelar</button>
                                <button type="submit" class="inline-flex min-w-28 items-center justify-center px-5 text-[15px] transition focus:outline-none focus:ring-0">Guardar</button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <style>
        /* Un solo tamaño y peso de letra en toda la página; solo el título principal (20px) es más grande. */
        .support-backup-report-page :is(h1, h2, h3, p, span, label, button, a, input, textarea, select, summary, th, td, strong) {
            font-weight: 400;
        }

        .support-backup-report-page :is(p, span, label, button, a, input, textarea, select, summary, th, td, strong, h2, h3) {
            font-size: 15px;
        }

        .support-backup-report-page :is(button, a, summary):focus,
        .support-backup-report-page :is(button, a, summary):focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Los formularios emergentes: sin borde morado al enfocar. */
        .support-backup-report-page :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus,
        .support-backup-report-page :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus-visible {
            border-color: #d4d4d8 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .support-backup-report-page input[type="range"],
        .support-backup-report-page input[type="checkbox"] {
            accent-color: #000000;
        }

        .support-backup-report-page input[type="checkbox"]:focus,
        .support-backup-report-page input[type="checkbox"]:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }

        .support-backup-report-page:fullscreen {
            height: 100dvh;
            overflow-y: auto;
        }

        /* ===== Tema oscuro (misma paleta que Preguntas, Reportes de soporte y Notificaciones) ===== */
        html.module-dark-theme {
            --support-accent: #6aa9ff;
            --support-border: #3f3f46;
            --support-surface: #18181b;
            --support-header: #202023;
        }

        html.module-dark-theme #main-content .support-backup-report-page,
        html.module-dark-theme #main-content .support-backup-report-page [class~="bg-white"],
        html.module-dark-theme #main-content .support-backup-report-page [class*="bg-white/95"] {
            background-color: var(--support-surface) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="bg-zinc-100"] {
            background-color: var(--support-header) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="bg-zinc-50"] {
            background-color: #1d1d21 !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="hover:bg-zinc-50"]:hover {
            background-color: #26262b !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page :is(tr, li)[class*="hover:bg-zinc-50"]:not(:hover) {
            background-color: transparent !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="border-[#d4d4d8]"],
        html.module-dark-theme #main-content .support-backup-report-page [class*="border-zinc-200"] {
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="divide-[#d4d4d8]"] > :not([hidden]) ~ :not([hidden]) {
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="bg-[#d4d4d8]"],
        html.module-dark-theme #main-content .support-backup-report-page [class~="bg-zinc-200"],
        html.module-dark-theme #main-content .support-backup-report-page span.w-px {
            background-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="text-black"] {
            color: #e4e4e7 !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="text-[#1A3A6B]"] {
            color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover {
            color: #fafafa !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="bg-[#1A3A6B]"] {
            background-color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="border-[#1A3A6B]"] {
            border-color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="hover:text-[#1A3A6B]"]:hover {
            color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page :is(input, textarea, select) {
            color: #e4e4e7;
        }

        html.module-dark-theme #main-content .support-backup-report-page :is(input[type="range"], input[type="checkbox"]) {
            accent-color: var(--support-accent);
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="bg-red-50"] {
            background-color: rgba(248, 113, 113, 0.08) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="border-red-200"] {
            border-color: rgba(248, 113, 113, 0.4) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class*="divide-red-200"] > :not([hidden]) ~ :not([hidden]) {
            border-color: rgba(248, 113, 113, 0.25) !important;
        }

        html.module-dark-theme #main-content .support-backup-report-page [class~="text-red-700"] {
            color: #f87171 !important;
        }

        /* ===== Formularios emergentes (diseño de Administración) en azul fuerte, sin negro ===== */
        html :is([data-administration-modal="backup-profile-editor"], [data-administration-modal="backup-server-editor"]) :is(header h1, section h2, label, summary) {
            color: #1A3A6B !important;
        }

        html :is([data-administration-modal="backup-profile-editor"], [data-administration-modal="backup-server-editor"]) footer button:last-child {
            border-color: #1A3A6B !important;
            background: #1A3A6B !important;
            color: #ffffff !important;
        }

        html :is([data-administration-modal="backup-profile-editor"], [data-administration-modal="backup-server-editor"]) footer button:last-child:hover {
            border-color: #14305A !important;
            background: #14305A !important;
        }

        html :is([data-administration-modal="backup-profile-editor"], [data-administration-modal="backup-server-editor"]) footer button:not(:last-child) {
            color: #1A3A6B !important;
        }

        html :is([data-administration-modal="backup-profile-editor"], [data-administration-modal="backup-server-editor"]) input[type="checkbox"] {
            accent-color: #1A3A6B;
        }

        /* Título del servidor: solo visible al imprimir (en pantalla lo muestra la pestaña activa). */
        .support-backup-report-page .backup-print-title {
            display: none;
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
            .support-backup-report-page > div { width: 100% !important; margin-left: 0 !important; transform: none !important; }
        }
    </style>
</div>
