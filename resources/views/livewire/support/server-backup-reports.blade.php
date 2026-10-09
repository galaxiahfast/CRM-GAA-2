<div
    data-clock-particle-network
    class="relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
    x-data="{ viewScale: 100, isFullscreen: false, isCompact: window.innerWidth < 1024, autoScanStarted: false, currentServer: '', currentProfile: '', processed: $wire.entangle('processedProfiles'), total: $wire.entangle('totalProfiles'), scanning: $wire.entangle('reading'), statusMessage: $wire.entangle('statusMessage'), recipientsOpen: false, selectedRecipients: $wire.entangle('emailRecipients'), mailSending: false, mailStatus: @js($emailStatus), mailSent: {{ $emailSent ? 'true' : 'false' }}, init() { const saved = Number(localStorage.getItem('backup-reports-view-scale')); if (saved >= 70 && saved <= 100) this.viewScale = saved; if (!this.autoScanStarted) { this.autoScanStarted = true; setTimeout(() => $wire.startReading(), 800); } }, async sendReport() { if (this.mailSending) return; const scrollPosition = window.scrollY; this.mailSending = true; this.mailStatus = ''; try { const result = await this.$wire.sendEmail(); this.mailSent = result.sent; this.mailStatus = result.status; if (result.sent) this.recipientsOpen = false; } finally { this.mailSending = false; this.$nextTick(() => window.scrollTo(0, scrollPosition)); } }, saveScale() { localStorage.setItem('backup-reports-view-scale', String(this.viewScale)); }, async toggleFullscreen(container) { if (document.fullscreenElement === container) return document.exitFullscreen(); if (document.fullscreenElement) await document.exitFullscreen(); await container.requestFullscreen(); } }"
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
    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.55]" aria-hidden="true"></canvas>

    <div class="fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm lg:flex">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen($root)" class="rounded-lg p-[5px] text-black transition hover:bg-zinc-100" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-0 origin-top" :style="isCompact ? 'width: 100%; margin-left: 0; transform: none;' : `width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">
        <header class="flex flex-col items-start justify-between gap-[24px] border-b border-zinc-200 bg-white px-[20px] py-[24px] md:px-[30px] lg:flex-row lg:items-center lg:gap-[30px] lg:whitespace-nowrap lg:p-[50px]">
            <div class="flex items-center gap-[15px] text-zinc-500">
                <span class="font-medium">Soporte</span>
                <span class="text-gray-300">&gt;</span>
                <span class="font-semibold text-black">Reporte de Respaldos</span>
            </div>
            <div class="flex w-full flex-wrap items-center gap-x-[24px] gap-y-[16px] lg:w-auto lg:flex-nowrap lg:gap-[30px]">
                <button type="button" wire:click="startReading" wire:loading.attr="disabled" :disabled="scanning" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-semibold text-black transition-colors hover:text-zinc-600 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                    Actualizar registros
                </button>
                <div class="relative flex h-[24px] items-center self-center select-none">
                    <button type="button" @click="recipientsOpen = !recipientsOpen" :disabled="mailSending || scanning || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="inline-flex h-full select-none items-center gap-[10px] border-0 bg-transparent p-0 font-semibold leading-none text-black outline-none transition-colors hover:text-zinc-600 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6.75A1.75 1.75 0 0 1 4.75 5h14.5A1.75 1.75 0 0 1 21 6.75v10.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Zm.5-.5L12 13l8.5-6.75"/></svg>
                        <span>Enviar reporte por correo</span>
                    </button>
                        <div x-show="recipientsOpen" x-cloak x-transition.opacity.duration.150ms x-on:click.outside="recipientsOpen = false" class="fixed inset-x-[15px] top-[150px] z-50 whitespace-normal rounded-xl border border-zinc-200 bg-white p-[18px] shadow-[0_18px_50px_rgba(0,0,0,0.18)] sm:absolute sm:inset-x-auto sm:right-0 sm:top-[36px] sm:w-[370px]">
                            <div class="mb-[14px]">
                                <strong class="block text-black">Enviar reporte a</strong>
                                <span class="mt-[3px] block text-sm text-zinc-500">Selecciona uno o varios destinatarios.</span>
                            </div>
                            <div class="space-y-[8px]">
                                @foreach ($recipientOptions as $email => $label)
                                    <label wire:key="backup-recipient-{{ $email }}" class="flex cursor-pointer select-none items-center gap-[12px] rounded-xl border border-zinc-200 px-[13px] py-[11px] outline-none transition hover:bg-zinc-50 focus-within:border-zinc-300 focus-within:outline-none focus-within:ring-0">
                                        <input x-model="selectedRecipients" type="checkbox" value="{{ $email }}" class="h-4 w-4 rounded border-zinc-300 text-blue-950 outline-none focus:outline-none focus:ring-0 focus:ring-offset-0">
                                        <span class="min-w-0"><strong class="block text-sm text-black">{{ $label }}</strong><span class="block truncate text-xs text-zinc-500">{{ $email }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                            <p x-show="selectedRecipients.length === 0" x-cloak class="mt-[12px] text-sm font-medium text-amber-700">Selecciona al menos un correo.</p>
                            <button type="button" @click.prevent.stop="sendReport()" :disabled="mailSending || scanning || total === 0 || processed < total || selectedRecipients.length === 0" class="mt-[14px] w-full select-none rounded-xl bg-black px-[16px] py-[11px] text-sm font-medium text-white outline-none transition hover:bg-zinc-800 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500">
                                <span x-show="!mailSending">Enviar reporte</span>
                                <span x-show="mailSending" x-cloak>Enviando...</span>
                            </button>
                        </div>
                </div>
                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" :disabled="scanning || total === 0 || processed < total" @disabled($reading || $totalProfiles === 0 || $processedProfiles < $totalProfiles) class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-semibold text-black transition-colors hover:text-zinc-600 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                    Guardar PDF
                </button>
            </div>
        </header>

        <main class="mx-auto w-full p-[15px] sm:p-[25px] lg:p-[50px]">
            <section class="flex flex-col items-start justify-between gap-[20px] rounded-t-xl border border-zinc-200 p-[20px] md:flex-row md:items-center md:gap-[30px]">
                <div class="flex items-center gap-[20px]">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 18h16M6 15V9m4 6V5m4 10v-3m4 3V7"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold text-black">Informe Técnico de Respaldos</h1>
                        <p class="mt-[5px] text-zinc-500">Supervisa servidores, perfiles e incidencias.</p>
                    </div>
                </div>
                <div class="flex items-center gap-[10px] font-semibold text-black"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>Acceso exclusivo para administradores</div>
            </section>

            <section class="overflow-hidden rounded-b-xl border-x border-b border-zinc-200">
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
                <div class="grid grid-cols-1 divide-y divide-zinc-200 bg-white sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                @foreach ($reportStats as $statIndex => [$label, $value])
                    @if ($statIndex === 3)
                        </div>
                        <div class="border-y border-zinc-200 p-[20px]" aria-live="polite">
                            <div class="block truncate font-semibold" :class="mailStatus ? (mailSent ? 'text-emerald-700' : 'text-amber-800') : 'text-black'" x-text="mailSending ? 'Generando PDF y enviando el correo...' : (mailStatus || statusMessage)">{{ $statusMessage }}</div>
                            <div class="mt-[15px] flex items-center gap-[12px] sm:gap-[15px]">
                                <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-zinc-200">
                                    <div x-show="!mailSending && !mailStatus" class="h-full rounded-full bg-black transition-all duration-300" :style="`width: ${total > 0 ? Math.min(100, (processed / total) * 100) : 0}%`" style="width: {{ $totalProfiles > 0 ? min(100, ($processedProfiles / $totalProfiles) * 100) : 0 }}%"></div>
                                    <div x-show="mailSending" x-cloak class="h-full w-1/2 animate-pulse rounded-full bg-black"></div>
                                    <div x-show="!mailSending && mailStatus" x-cloak class="h-full rounded-full transition-all duration-300" :class="mailSent ? 'w-full bg-emerald-600' : 'w-1/3 bg-amber-500'"></div>
                                </div>
                                <div class="flex shrink-0 items-center gap-[15px]">
                                    <span x-show="!mailSending && !mailStatus" class="font-semibold tabular-nums text-black"><span x-text="processed">{{ $processedProfiles }}</span> / <span x-text="total">{{ $totalProfiles }}</span></span>
                                    <span x-show="mailSending" x-cloak class="font-semibold text-black">Procesando</span>
                                    <span x-show="!mailSending && mailStatus" x-cloak class="font-semibold" :class="mailSent ? 'text-emerald-700' : 'text-amber-800'" x-text="mailSent ? '100%' : 'Aviso'"></span>
                                </div>
                            </div>
                            <div class="mt-[9px] min-w-0 text-zinc-500 sm:truncate" x-text="mailSending || mailStatus ? 'El reporte se envía a los destinatarios seleccionados.' : (scanning && currentServer ? `Lectura de solo acceso, perfil por perfil. Cargando ${currentServer} · ${currentProfile}…` : 'Lectura de solo acceso, perfil por perfil.')">Lectura de solo acceso, perfil por perfil.</div>
                        </div>
                        <div class="grid grid-cols-1 divide-y divide-zinc-200 bg-white sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    @endif
                    <div class="flex min-h-[92px] flex-col items-center justify-center bg-white px-[20px] py-[15px] text-center text-black">
                        <span class="block text-base font-semibold tabular-nums text-black">{{ $value }}</span>
                        <span class="mt-[7px] inline-flex items-center justify-center gap-[8px] font-semibold text-black">
                            @switch($statIndex)
                                @case(0)
                                    <svg class="h-[18px] w-[18px] text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M5 6.5h14v5H5v-5Zm0 6h14v5H5v-5ZM8 9h.01M8 15h.01"/></svg>
                                    @break
                                @case(1)
                                    <svg class="h-[18px] w-[18px] text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M8 4h8m-9 3h10a2 2 0 0 1 2 2v9H5V9a2 2 0 0 1 2-2Zm2 4h6m-6 3h4"/></svg>
                                    @break
                                @case(2)
                                    <svg class="h-[17px] w-[17px] text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m5 12 4 4L19 6"/></svg>
                                    @break
                                @case(3)
                                    <svg class="h-[18px] w-[18px] text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8v5m0 3h.01M10.3 4.7 3.8 16a2 2 0 0 0 1.7 3h13a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z"/></svg>
                                    @break
                                @case(4)
                                    <svg class="h-[18px] w-[18px] text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 19h16M6 19V8h5v11m2 0V4h5v15M8 11h1m6-4h1"/></svg>
                                    @break
                                @default
                                    <svg class="h-[18px] w-[18px] text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @endswitch
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
                </div>

                <section class="border-t border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 bg-white p-[20px]">
                    <div class="flex flex-col items-start justify-between gap-[10px] sm:flex-row sm:items-center sm:gap-[30px]">
                        <div><h2 class="font-semibold text-black">Servidores supervisados</h2><p class="mt-[4px] text-zinc-500">Consulta el detalle completo de todos los servidores y sus perfiles.</p></div>
                        <span class="shrink-0 whitespace-nowrap font-medium text-black">{{ count($servers) }} servidores configurados</span>
                    </div>
                </div>

                <div class="min-w-0 space-y-[20px] bg-white p-[20px]">
                    @foreach ($servers as $serverIndex => $server)
                        @php
                            $availableCount = collect($server['profiles'])->where('available', true)->count();
                            $errorCount = collect($server['profiles'])->filter(fn ($profile) => $profile['available'] && ($profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []))->count();
                            $pendingCount = collect($server['profiles'])->where('done', false)->count();
                        @endphp
                        <article wire:key="backup-server-panel-{{ $server['key'] }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                            <header class="flex flex-col items-start justify-between gap-[16px] border-b border-zinc-200 px-[20px] py-[18px] md:flex-row md:items-center md:gap-[20px]">
                                <div class="min-w-0 text-black">
                                    <h2 class="truncate text-xl font-semibold">{{ $server['name'] }}</h2>
                                    <span class="mt-[5px] flex min-w-0 items-center gap-[8px] text-sm font-normal text-zinc-500">
                                        <span class="shrink-0">{{ $server['ip'] }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span class="truncate font-mono">{{ $server['share'] }}</span>
                                    </span>
                                </div>
                                <div class="flex w-full flex-wrap items-center justify-between gap-[15px] md:w-auto md:shrink-0 md:justify-start md:gap-[20px]">
                                    <span class="inline-flex items-center gap-[8px] font-semibold text-black"><span class="h-2 w-2 rounded-full {{ $errorCount ? 'bg-red-500' : 'bg-black' }}"></span>{{ $pendingCount ? 'Leyendo' : ($availableCount === 0 ? 'Sin datos' : ($errorCount ? 'Con incidencias' : 'Todo realizado')) }}</span>
                                    <button type="button" wire:click="openServer({{ $serverIndex }})" class="inline-flex items-center gap-[10px] font-semibold text-black hover:text-zinc-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M9 11l7.6-7.6a2 2 0 0 1 2.8 0l1.2 1.2a2 2 0 0 1 0 2.8L13 15l-4 1 1-4ZM5 19h14"/></svg>Editar servidor</button>
                                </div>
                            </header>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[780px] table-fixed text-left lg:min-w-[980px]">
                                    <thead class="border-b border-zinc-200 bg-white text-black"><tr><th class="w-[20%] border-r border-zinc-200 px-[20px] py-[12px] font-semibold">Perfil</th><th class="w-[28%] border-r border-zinc-200 px-[20px] py-[12px] font-semibold">Última ejecución</th><th class="w-[14%] border-r border-zinc-200 px-[20px] py-[12px] font-semibold">Hecho</th><th class="w-[23%] border-r border-zinc-200 px-[20px] py-[12px] font-semibold">Resultado</th><th class="w-[15%] px-[20px] py-[12px] font-semibold">Acción</th></tr></thead>
                                    @foreach ($server['profiles'] as $profileIndex => $profile)
                                        @php $isBad = $profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []; @endphp
                                        <tbody wire:key="backup-profile-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}" class="border-t border-zinc-200">
                                            <tr class="transition hover:bg-zinc-50">
                                                <td class="border-r border-zinc-200 px-[20px] py-[15px] font-medium text-zinc-500">{{ $profile['name'] }}</td>
                                                <td class="border-r border-zinc-200 px-[20px] py-[15px] text-zinc-500">{{ $profile['available'] ? ($profile['date'] ?: 'Sin fecha') : ($profile['done'] ? 'Sin datos' : 'Leyendo…') }}</td>
                                                <td class="border-r border-zinc-200 px-[20px] py-[15px] font-mono text-zinc-500">{{ $profile['available'] ? ($profile['processed'] ?: '—') : '—' }}</td>
                                                <td class="border-r border-zinc-200 px-[20px] py-[15px] font-medium text-zinc-500">{{ $profile['available'] ? ($isBad ? 'Error' : ($profile['processed'] === '0/0' ? 'Sin cambios' : 'Todo realizado')) : ($profile['done'] ? 'No disponible' : 'Pendiente') }}</td>
                                                <td class="px-[20px] py-[15px]"><button type="button" wire:click="openProfile({{ $serverIndex }}, {{ $profileIndex }})" class="font-medium text-zinc-500 hover:text-black">Editar</button></td>
                                            </tr>
                                            @if (($profile['failedFiles'] ?? []) !== [] || $profile['errors'] !== [] || $profile['issues'] !== [] || ($profile['message'] && $profile['done'] && !$profile['available']))
                                                <tr>
                                                    <td colspan="5" class="px-[20px] pb-[16px] pt-[2px]">
                                                        <div class="ml-[20px] border-l-2 {{ $isBad ? 'border-red-400' : 'border-zinc-300' }} pl-[15px]">
                                                            <div class="font-semibold text-black">Incidencias</div>
                                                            <div class="mt-[5px] space-y-[3px] leading-6">
                                                                @if (($profile['failedFiles'] ?? []) !== [])
                                                                    @foreach ($profile['failedFiles'] as $file)
                                                                        <div class="break-all font-mono text-red-600">{{ is_array($file) ? ($file['path'] ?? '') : $file }}</div>
                                                                    @endforeach
                                                                @else
                                                                    @foreach ($profile['errors'] as $error)
                                                                        <div class="break-all text-red-600">{{ $error }}</div>
                                                                    @endforeach
                                                                @endif
                                                                @foreach ($profile['issues'] as $issue)
                                                                    <div class="break-words text-red-600">{{ $issue }}</div>
                                                                @endforeach
                                                                @if ($profile['message'] && !$profile['available'])
                                                                    <div class="break-words text-zinc-500">{{ $profile['message'] }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                            <div class="flex flex-col items-start justify-between gap-[12px] border-t border-zinc-200 bg-white px-[20px] py-[15px] sm:flex-row sm:items-center">
                                <span class="font-medium text-black">{{ count($server['profiles']) }} perfiles configurados</span>
                                <button type="button" wire:click="addProfile({{ $serverIndex }})" class="inline-flex items-center gap-[8px] font-semibold text-black hover:text-zinc-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14"/></svg>Agregar perfil</button>
                            </div>
                        </article>
                    @endforeach
                </div>
                </section>
            </section>
        </main>
    </div>

    @if ($showProfileEditor)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/45 p-[15px] sm:p-[30px]" wire:key="profile-editor">
            <form wire:submit="saveProfile" class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-[20px] shadow-2xl sm:p-[30px]">
                <div class="flex items-start justify-between"><div><h2 class="text-xl font-semibold text-black">{{ $editingProfile === null ? 'Agregar perfil' : 'Editar perfil' }}</h2><p class="mt-[5px] text-zinc-500">Los ajustes solo afectan el informe actual.</p></div><button type="button" wire:click="$set('showProfileEditor', false)" class="rounded-lg p-[8px] text-zinc-500 hover:bg-zinc-100">✕</button></div>
                <div class="mt-[25px] grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Perfil *</span><input wire:model="profileForm.name" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Nombre del log</span><input wire:model="profileForm.log" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Fecha y hora</span><input wire:model="profileForm.date" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Hecho</span><input wire:model="profileForm.processed" placeholder="0/0" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block sm:col-span-2"><span class="mb-[7px] block font-medium text-black">Empresas con error</span><textarea wire:model="profileForm.errors" rows="4" placeholder="Una empresa por línea" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></textarea></label>
                    <label class="block sm:col-span-2"><span class="mb-[7px] block font-medium text-black">Incidencias generales</span><textarea wire:model="profileForm.issues" rows="3" placeholder="Ej. Directorio destino inválido" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></textarea></label>
                    <label class="flex items-center gap-[10px] rounded-xl border border-zinc-200 p-[15px] sm:col-span-2"><input wire:model="profileForm.available" type="checkbox" class="rounded border-zinc-300 text-blue-950 focus:ring-blue-900"><span class="font-medium text-black">Incluir este perfil en el PDF</span></label>
                    <details class="rounded-xl border border-zinc-200 p-[15px] sm:col-span-2"><summary class="cursor-pointer font-medium text-black">Datos técnicos</summary><div class="mt-[15px] grid grid-cols-1 gap-[12px] sm:grid-cols-3"><input wire:model="profileForm.source" placeholder="Origen" class="rounded-xl border-zinc-200 bg-zinc-50"><input wire:model="profileForm.destination" placeholder="Destino" class="rounded-xl border-zinc-200 bg-zinc-50"><input wire:model="profileForm.duration" placeholder="Duración" class="rounded-xl border-zinc-200 bg-zinc-50"></div></details>
                </div>
                @error('profileForm.name')<span class="mt-[10px] block text-red-600">{{ $message }}</span>@enderror
                <div class="mt-[25px] flex justify-between">@if ($editingProfile !== null)<button type="button" wire:click="deleteProfile" wire:confirm="¿Eliminar este perfil del informe?" class="rounded-xl border border-red-200 bg-red-50 px-[18px] py-[12px] font-medium text-red-600">Eliminar</button>@else<span></span>@endif<div class="flex gap-[10px]"><button type="button" wire:click="$set('showProfileEditor', false)" class="rounded-xl border border-zinc-200 px-[18px] py-[12px] font-medium text-black">Cancelar</button><button type="submit" class="rounded-xl bg-black px-[18px] py-[12px] font-medium text-white">Guardar</button></div></div>
            </form>
        </div>
    @endif

    @if ($showServerEditor)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/45 p-[15px] sm:p-[30px]" wire:key="server-editor">
            <form wire:submit="saveServer" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-[20px] shadow-2xl sm:p-[30px]">
                <div class="flex items-start justify-between"><div><h2 class="text-xl font-semibold text-black">Editar servidor</h2><p class="mt-[5px] text-zinc-500">Define el nombre y el recurso compartido donde están los logs.</p></div><button type="button" wire:click="$set('showServerEditor', false)" class="rounded-lg p-[8px] text-zinc-500 hover:bg-zinc-100">✕</button></div>
                <div class="mt-[25px] space-y-[15px]"><label class="block"><span class="mb-[7px] block font-medium text-black">Servidor *</span><input wire:model="serverForm.name" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label><label class="block"><span class="mb-[7px] block font-medium text-black">Dirección IP</span><input wire:model="serverForm.ip" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label><label class="block"><span class="mb-[7px] block font-medium text-black">Carpeta compartida</span><input wire:model="serverForm.share" placeholder="\\SERVIDOR\log" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] font-mono focus:border-blue-900 focus:ring-blue-900"></label></div>
                @error('serverForm.name')<span class="mt-[10px] block text-red-600">{{ $message }}</span>@enderror
                <div class="mt-[25px] flex justify-between"><button type="button" wire:click="deleteServer" wire:confirm="¿Eliminar este servidor y sus perfiles del informe?" class="rounded-xl border border-red-200 bg-red-50 px-[18px] py-[12px] font-medium text-red-600">Eliminar</button><div class="flex gap-[10px]"><button type="button" wire:click="$set('showServerEditor', false)" class="rounded-xl border border-zinc-200 px-[18px] py-[12px] font-medium text-black">Cancelar</button><button type="submit" class="rounded-xl bg-black px-[18px] py-[12px] font-medium text-white">Guardar</button></div></div>
            </form>
        </div>
    @endif
</div>
