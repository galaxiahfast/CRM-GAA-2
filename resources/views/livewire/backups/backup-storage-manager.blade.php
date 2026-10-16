@php
    $formatBytes = static function (int $bytes): string {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    };
    $exportIsActive = in_array($exportStatus['status'] ?? null, ['queued', 'copying', 'cancelling'], true);
    $exportPercent = ($exportStatus['total_bytes'] ?? 0) > 0
        ? min(100, (int) round(($exportStatus['copied_bytes'] ?? 0) * 100 / $exportStatus['total_bytes']))
        : 0;
    $visibleDestinationFolders = collect($destinationFolders)->values();
    $allAvailableSelected = $headerSelectableCount > 0 && $selectedExportCount === $headerSelectableCount;
    $bothLocationsOnline = ($catalog['connected'] ?? false) && ($exportDestination['connected'] ?? false);
    $exportError = $exportMessage ?: (($exportStatus['status'] ?? null) === 'failed' ? ($exportStatus['error'] ?? 'La copia no pudo completarse.') : null);
@endphp

<div class="backup-storage-monochrome relative isolate min-h-[calc(100dvh-100px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
    x-data="{
        viewScale: Number(localStorage.getItem('backup-storage-view-scale-v3') || 90),
        isFullscreen: false,
        saveScale() { localStorage.setItem('backup-storage-view-scale-v3', String(this.viewScale)) },
        zoomOut() { this.viewScale = Math.max(70, this.viewScale - 5); this.saveScale() },
        zoomIn() { this.viewScale = Math.min(100, this.viewScale + 5); this.saveScale() },
        async toggleFullscreen() { if (document.fullscreenElement) await document.exitFullscreen(); else await $root.requestFullscreen() }
    }"
    x-init="document.addEventListener('fullscreenchange', () => isFullscreen = Boolean(document.fullscreenElement))"
    wire:poll.1s="refreshSharedExportStatus">
    <style>
        .backup-catalog-scroll { scrollbar-width: none; overscroll-behavior: contain; }
        .backup-catalog-scroll::-webkit-scrollbar { width: 0; height: 0; }
        .backup-catalog-scroll-thumb { position: absolute; right: 0; top: 0; z-index: 20; width: 3px; min-height: 32px; border-radius: 9999px; background: #1A3A6B; opacity: 0; pointer-events: none; transition: opacity 140ms ease; }
        .backup-catalog-scroll-thumb-x { position: absolute; bottom: 0; left: 0; z-index: 21; height: 14px; min-width: 48px; background: transparent; opacity: 0; cursor: ew-resize; touch-action: none; transition: opacity 140ms ease; }
        .backup-catalog-scroll-thumb-x::after { content: ''; position: absolute; right: 0; bottom: 0; left: 0; height: 3px; border-radius: 9999px; background: #1A3A6B; }
        .backup-catalog-scroll-region:hover > .backup-catalog-scroll-thumb { opacity: 1; }
        .backup-catalog-scroll-region:hover > .backup-catalog-scroll-thumb-x { opacity: 1; }
        .backup-catalog-scroll-vertical-visible > .backup-catalog-scroll-thumb { opacity: 1; }
        .backup-catalog-scroll-horizontal-visible > .backup-catalog-scroll-thumb-x { opacity: 1; }
        html.module-dark-theme .backup-catalog-scroll-thumb { background: #a9a4cf; }
        html.module-dark-theme .backup-catalog-scroll-thumb-x::after { background: #a9a4cf; }
        .backup-record-group > tr { background: inherit; }
        .backup-record-gray > tr > td { background-color: #f4f4f5; }
        .backup-record-white > tr > td { background-color: #fff; }
        .backup-record-detail > div > :where(a, div) { background: transparent !important; }
        .backup-storage-monochrome { background: #fff; color: #3f3f46; font-size: 15px; }
        .backup-storage-monochrome :where(p, span, button, input, select, option, label, th, td, h2, h3, small) {
            font-size: 15px !important;
        }
        .backup-storage-monochrome h1 { font-size: 20px !important; }
        .backup-storage-monochrome button,
        .backup-storage-monochrome input { box-shadow: none; }
        .backup-storage-monochrome button:focus,
        .backup-storage-monochrome button:focus-visible,
        .backup-storage-monochrome input:focus,
        .backup-storage-monochrome input:focus-visible { outline: none !important; box-shadow: none !important; --tw-ring-shadow: 0 0 #0000 !important; }
        .backup-storage-monochrome:fullscreen { overflow: auto; background: #fff; }
        html.module-dark-theme .backup-status-card { background: #18181b !important; }
        html.module-dark-theme .backup-soft-panel { background: #111113 !important; }
        html.module-dark-theme .backup-storage-monochrome:fullscreen { background: #09090b; }
    </style>

    @if($exportError)
        @teleport('body')
            <div wire:key="backup-export-error-{{ $exportErrorVersion }}-{{ md5((string) $exportError.($exportStatus['completed_at'] ?? '')) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 5000)" x-show="visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3" class="fixed left-1/2 top-[20px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-center gap-[10px] rounded-xl border border-red-200 bg-white px-[20px] py-[15px] text-red-700 shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="alert">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg></span>
                <span class="min-w-0 flex-1">{{ $exportError }}</span>
                <button type="button" @click="visible = false" aria-label="Cerrar aviso" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-black transition hover:bg-zinc-100"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
            </div>
        @endteleport
    @endif

    @if($syncResult)
        @teleport('body')
            <div wire:key="backup-sync-result-{{ md5(json_encode($syncResult)) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 6000)" x-show="visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3" class="backup-sync-toast fixed left-1/2 top-[20px] z-[199998] w-[calc(100%-40px)] max-w-3xl -translate-x-1/2 rounded-xl border {{ ($syncResult['error'] ?? null) ? 'border-red-200' : 'border-emerald-200' }} bg-white px-[20px] py-[15px] shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="status" aria-live="polite">
                <div class="flex items-start gap-[12px]">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ ($syncResult['error'] ?? null) ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                        @if($syncResult['error'] ?? null)
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m8 12 2.5 2.5L16 9"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        @if($syncResult['error'] ?? null)
                            <p class="font-semibold text-red-700">No se pudo comprobar</p>
                            <p class="mt-[3px] text-red-700">{{ $syncResult['error'] }}</p>
                        @else
                            <p class="font-semibold text-black">Sincronización comprobada</p>
                            <p class="mt-[3px] text-zinc-500">Parejas BAK + ZIP/RAR y tamaños comprobados {{ \Carbon\CarbonImmutable::parse($syncResult['checked_at'])->translatedFormat('d M Y · H:i:s') }}</p>
                            <div class="mt-[10px] flex flex-wrap gap-x-[15px] gap-y-[5px]">
                                <span class="text-emerald-700">{{ $syncResult['server_complete'] }} completas en servidor</span>
                                <span class="text-red-700">{{ $syncResult['server_incomplete'] }} incompletas</span>
                                <span class="text-[#1A3A6B]">{{ $syncResult['synced'] }} sincronizadas</span>
                                <span class="text-amber-700">{{ $syncResult['pending'] }} pendientes</span>
                                @if($syncResult['outdated'] > 0)<span class="text-orange-700">{{ $syncResult['outdated'] }} por actualizar</span>@endif
                                @if($syncResult['copying'] > 0)<span class="text-violet-700">{{ $syncResult['copying'] }} copiándose</span>@endif
                            </div>
                        @endif
                    </div>
                    <button type="button" @click="visible = false" aria-label="Cerrar resultado" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-black transition hover:bg-zinc-100"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                </div>
            </div>
        @endteleport
    @endif

    @if($actionMessage)
        @teleport('body')
            <div wire:key="backup-action-result-{{ md5($actionMessage) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 5000)" x-show="visible" x-transition class="fixed left-1/2 top-[20px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-center gap-[10px] rounded-xl border border-emerald-200 bg-white px-[20px] py-[15px] text-emerald-700 shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="status">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9"/></svg>
                <span class="min-w-0 flex-1">{{ $actionMessage }}</span>
                <button type="button" @click="visible = false" aria-label="Cerrar aviso" class="inline-flex h-8 w-8 items-center justify-center text-black"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
            </div>
        @endteleport
    @endif

    @if($showDeleteConfirmation)
        @teleport('body')
            <div class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-backups-title">
                <div class="w-full max-w-lg rounded-2xl bg-white p-[20px] shadow-2xl">
                    <h2 id="delete-backups-title" class="font-semibold text-black">Eliminar {{ count($pendingDeleteRecords) === 1 ? 'respaldo' : count($pendingDeleteRecords).' respaldos' }}</h2>
                    <p class="mt-[5px] text-zinc-500">Solo se eliminarán las copias de la memoria externa. Los archivos originales del servidor no se modificarán.</p>
                    <div class="mt-[20px] space-y-[15px]">
                        <div>
                            <label for="delete-backup-password" class="mb-[7px] block font-medium text-black">Contraseña</label>
                            <input id="delete-backup-password" type="password" wire:model="deletePassword" autocomplete="current-password" class="h-[44px] w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-black focus:border-[#1A3A6B]">
                            @error('deletePassword')<p class="mt-[5px] text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="delete-backup-code" class="mb-[7px] block font-medium text-black">Código del autenticador</label>
                            <input id="delete-backup-code" type="text" inputmode="numeric" maxlength="6" wire:model="deleteTwoFactorCode" autocomplete="one-time-code" class="h-[44px] w-full rounded-xl border border-zinc-200 bg-white px-[15px] font-mono tracking-[0.2em] text-black focus:border-[#1A3A6B]">
                            @error('deleteTwoFactorCode')<p class="mt-[5px] text-red-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mt-[20px] grid grid-cols-2 gap-[10px]">
                        <button type="button" wire:click="closeDeleteConfirmation" wire:loading.attr="disabled" wire:target="deleteDestinationBackups" class="h-[44px] w-full rounded-xl border border-zinc-200 bg-white px-[15px] font-medium text-black hover:bg-zinc-50">Cancelar</button>
                        <button type="button" wire:click="deleteDestinationBackups" wire:loading.attr="disabled" wire:target="deleteDestinationBackups" class="h-[44px] w-full rounded-xl bg-red-700 px-[15px] font-medium text-white hover:bg-red-800 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="deleteDestinationBackups">Eliminar definitivamente</span><span wire:loading wire:target="deleteDestinationBackups">Verificando…</span></button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if($exportIsActive)
        @teleport('body')
            <div wire:key="shared-backup-progress-{{ $exportStatus['id'] ?? 'active' }}" class="fixed left-1/2 top-[20px] z-[199999] w-[calc(100%-40px)] max-w-2xl -translate-x-1/2 rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-zinc-700 shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="status" aria-live="polite">
                <div class="flex items-center justify-between gap-[15px]">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-black">
                            @if(($exportStatus['status'] ?? '') === 'queued') Respaldo en espera
                            @elseif(($exportStatus['status'] ?? '') === 'cancelling') Cancelando respaldo…
                            @else Respaldando {{ $exportStatus['current_company'] ?? 'archivos' }}
                            @endif
                        </p>
                        <p class="mt-[3px] truncate text-zinc-500">Todos los usuarios pueden ver este avance. No se puede iniciar otro respaldo hasta que termine.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-[15px]">
                        <span class="tabular-nums text-zinc-500">{{ $exportPercent }}% · {{ $formatBytes((int) ($exportStatus['copied_bytes'] ?? 0)) }} / {{ $formatBytes((int) ($exportStatus['total_bytes'] ?? 0)) }}</span>
                        <button type="button" wire:click="cancelExport" wire:loading.attr="disabled" wire:target="cancelExport" @disabled(($exportStatus['status'] ?? null) === 'cancelling') class="font-medium text-red-700 transition hover:text-red-900 disabled:cursor-wait disabled:text-zinc-400">{{ ($exportStatus['status'] ?? null) === 'cancelling' ? 'Cancelando…' : 'Cancelar respaldo' }}</button>
                    </div>
                </div>
                <div class="mt-[12px] h-[6px] overflow-hidden rounded-full bg-zinc-100"><span class="block h-full rounded-full bg-[#1A3A6B] transition-all duration-300" style="width: {{ $exportPercent }}%"></span></div>
            </div>
        @endteleport
    @endif

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <button type="button" @click="zoomOut()" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de Gestión de Respaldos">
        <button type="button" @click="zoomIn()" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">90%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <main class="w-full origin-top space-y-[20px] p-[50px]" :style="`width:${10000 / viewScale}%;margin-left:${(100 - (10000 / viewScale)) / 2}%;transform:scale(${viewScale / 100});`">
        <section class="flex flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
            <div class="flex min-w-0 items-center gap-[15px]">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-zinc-200 bg-white text-black">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v7c0 1.66 3.13 3 7 3s7-1.34 7-3V5M5 12v7c0 1.66 3.13 3 7 3s7-1.34 7-3v-7"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold tracking-tight text-black">Gestión de Respaldos</h1>
                    <p class="mt-[4px] text-zinc-500">Selecciona una carpeta para comprobar cuáles ya están respaldadas y definir el destino.</p>
                </div>
            </div>

            <div class="flex flex-col items-end gap-[5px]">
                <button type="button" wire:click="verifySynchronization" wire:loading.attr="disabled" wire:target="verifySynchronization" class="inline-flex items-center gap-[8px] bg-transparent p-0 font-medium text-black transition hover:text-zinc-600 disabled:opacity-50">
                    <svg wire:loading.class="animate-spin" wire:target="verifySynchronization" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8 8 0 0 0-15.5-2M4 4v5h5m-5 4a8 8 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                    <span wire:loading.remove wire:target="verifySynchronization">Actualizar y comprobar</span>
                    <span wire:loading wire:target="verifySynchronization">Actualizando…</span>
                </button>
                <span class="inline-flex items-center gap-[7px] {{ $bothLocationsOnline ? 'text-emerald-700' : 'text-red-700' }}" title="Origen: {{ ($catalog['connected'] ?? false) ? 'en línea' : 'fuera de línea' }} · Memoria: {{ ($exportDestination['connected'] ?? false) ? 'en línea' : 'fuera de línea' }}"><span class="h-1.5 w-1.5 rounded-full {{ $bothLocationsOnline ? 'bg-emerald-500' : 'bg-red-500' }}"></span>{{ $bothLocationsOnline ? 'En línea' : 'Fuera de línea' }}</span>
            </div>
        </section>

        @if ($error)
            <section class="rounded-2xl border border-red-200 bg-red-50 p-[20px] text-red-800">
                <div class="flex items-start gap-[12px]">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/></svg>
                    <div><p class="font-semibold">No se pudo leer el servidor</p><p class="mt-1 text-[13px]">{{ $error }} No se realizó ningún cambio en la ubicación de origen.</p></div>
                </div>
            </section>
        @else
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none">
                @if ($activeView === 'backups')
                    <div class="grid min-h-[520px] xl:grid-cols-[260px_minmax(0,1fr)]">
                        <aside class="backup-soft-panel h-[520px] min-h-0 overflow-hidden border-b border-zinc-200 bg-white xl:relative xl:h-auto xl:border-b-0 xl:border-r" aria-label="Tabla de días con respaldos">
                            <div class="flex h-full min-h-0 flex-col xl:absolute xl:inset-0">
                                <div class="flex h-[70px] shrink-0 items-center justify-center border-b border-zinc-300 bg-zinc-100 px-[20px] text-center text-zinc-700"><span class="truncate font-semibold">Fecha de respaldos</span></div>
                                <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region relative min-h-0 flex-1">
                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll h-full overflow-y-auto">
                                @forelse ($catalog['dates'] as $day)
                                    @php
                                        $dayDate = \Carbon\CarbonImmutable::parse($day['date']);
                                        $fullDayLabel = ucfirst($dayDate->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y'));
                                    @endphp
                                    <button type="button" wire:key="backup-day-{{ $day['date'] }}" wire:click="selectDate('{{ $day['date'] }}')" wire:loading.attr="disabled" wire:target="selectDate" class="flex min-h-[90px] w-full min-w-0 flex-col justify-center border-b border-zinc-200 px-[20px] py-[12px] text-left transition disabled:cursor-wait {{ $selectedDate === $day['date'] ? 'bg-black text-white' : ($loop->odd ? 'bg-zinc-50 text-zinc-700 hover:bg-zinc-100' : 'bg-white text-zinc-700 hover:bg-zinc-100') }}">
                                        <span class="block w-full truncate font-medium" title="{{ $fullDayLabel }}">{{ $fullDayLabel }}</span>
                                        <span class="mt-[3px] flex w-full min-w-0 items-center {{ $selectedDate === $day['date'] ? 'text-white/70' : 'text-zinc-500' }}" title="{{ $day['complete'] }} completos"><span class="w-[36px] shrink-0 text-right tabular-nums">{{ $day['complete'] }}</span><span class="ml-[8px] truncate">completos</span></span>
                                        <span class="mt-[2px] flex w-full min-w-0 items-center {{ $selectedDate === $day['date'] ? 'text-white/70' : 'text-zinc-500' }}" title="{{ $day['partial'] }} parciales"><span class="w-[36px] shrink-0 text-right tabular-nums">{{ $day['partial'] }}</span><span class="ml-[8px] truncate">parciales</span></span>
                                    </button>
                                @empty
                                    <p class="truncate p-[20px] text-zinc-400" title="No se encontraron respaldos con fecha válida.">No se encontraron respaldos con fecha válida.</p>
                                @endforelse
                                    </div>
                                    <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>
                            </div>
                        </aside>

                        <div class="relative flex min-h-0 min-w-0 flex-col">
                            <div class="order-2 flex flex-col gap-[12px] border-b border-zinc-200 px-[20px] py-[15px]">
                                <div class="flex w-full items-center justify-evenly gap-[16px] overflow-x-auto whitespace-nowrap" aria-label="Filtrar respaldos por estado">
                                    <button type="button" wire:click="$set('status', 'all')" class="flex min-h-[44px] items-center justify-center gap-[8px] px-[10px] transition {{ $status === 'all' ? 'font-medium text-black' : 'text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg><span>Todos</span></button>
                                    <button type="button" wire:click="$set('status', 'complete')" class="flex min-h-[44px] items-center justify-center gap-[8px] px-[10px] transition {{ $status === 'complete' ? 'font-medium text-black' : 'text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9"/></svg><span>Completos</span></button>
                                    <button type="button" wire:click="$set('status', 'available')" class="flex min-h-[44px] items-center justify-center gap-[8px] px-[10px] transition {{ $status === 'available' ? 'font-medium text-black' : 'text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg><span>Por respaldar</span></button>
                                    <button type="button" wire:click="$set('status', 'backed_up')" class="flex min-h-[44px] items-center justify-center gap-[8px] px-[10px] transition {{ $status === 'backed_up' ? 'font-medium text-black' : 'text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 7h14v12H5zM8 4h8v3"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 13 2 2 4-5"/></svg><span>Ya respaldadas</span></button>
                                    <button type="button" wire:click="$set('status', 'partial')" class="flex min-h-[44px] items-center justify-center gap-[8px] px-[10px] transition {{ $status === 'partial' ? 'font-medium text-black' : 'text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v6m0 4h.01"/></svg><span>Incompletos</span></button>
                                </div>
                            </div>
                            <div class="order-1 h-[70px] shrink-0 border-b border-zinc-300 bg-zinc-100 px-[20px] py-[13px]">
                                        <div class="grid gap-[15px] md:grid-cols-3">
                                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                                    <button type="button" @click="if (!{{ $exportIsActive ? 'true' : 'false' }}) open = !open" class="flex h-[44px] w-full items-center justify-between rounded-xl border border-zinc-200 bg-white px-[15px] text-left text-black">
                                                        <span class="truncate">{{ $currentBackupProfile['label'] }}</span>
                                                        <svg class="h-4 w-4 shrink-0 text-zinc-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                                    </button>
                                                    <div x-show="open" x-cloak class="absolute top-full z-40 mt-[8px] w-full overflow-hidden rounded-xl border border-zinc-200 bg-white py-[4px] shadow-lg">
                                                        @foreach($backupProfiles as $profile)
                                                            <button type="button" @click="$wire.set('backupProfile', @js($profile['key'])); open = false" @disabled(!($profile['enabled'] ?? false)) class="flex min-h-[44px] w-full items-center justify-between gap-[12px] px-[15px] text-left text-black hover:bg-zinc-50 disabled:cursor-not-allowed disabled:text-zinc-400 disabled:hover:bg-white">
                                                                <span class="truncate">{{ $profile['label'] }}@if(!($profile['enabled'] ?? false)) · Próximamente @endif</span>
                                                                @if($backupProfile === $profile['key'])<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>@endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                                    <button type="button" @click="if ({{ $exportDestination['connected'] && ($visibleDestinationFolders->isNotEmpty() || $destinationBrowsePath !== '') && ! $exportIsActive ? 'true' : 'false' }}) open = !open" @disabled(!$exportDestination['connected'] || ($visibleDestinationFolders->isEmpty() && $destinationBrowsePath === '') || $exportIsActive) class="flex h-[44px] w-full items-center justify-between rounded-xl border border-zinc-200 bg-white px-[15px] text-left text-black disabled:cursor-not-allowed disabled:text-zinc-400">
                                                        <span class="truncate" title="{{ str_replace('/', '\\', $destinationFolder) }}">{{ $destinationFolder !== '' ? str_replace('/', '\\', $destinationFolder) : 'Selecciona una carpeta…' }}</span>
                                                        <svg class="h-4 w-4 shrink-0 text-zinc-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                                    </button>
                                                    <div x-show="open" x-cloak class="absolute right-0 top-full z-40 mt-[8px] w-[min(430px,calc(100vw-40px))] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg">
                                                        <div class="flex items-center gap-[15px] border-b border-zinc-200 px-[20px] py-[15px]">
                                                            @if($destinationBrowsePath !== '')
                                                                <button type="button" @click.stop="$wire.browseDestinationUp()" class="inline-flex shrink-0 items-center gap-[10px] font-semibold text-[#1A3A6B] transition hover:text-[#102a52]" title="Volver a la carpeta anterior">
                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6"/></svg>
                                                                    <span>Volver</span>
                                                                </button>
                                                                <span class="h-5 w-px shrink-0 bg-zinc-300" aria-hidden="true"></span>
                                                            @endif
                                                            <span class="min-w-0 truncate font-medium {{ $destinationBrowsePath !== '' ? 'text-zinc-600' : 'text-black' }}" title="{{ $destinationBrowsePath !== '' ? str_replace('/', '\\', $destinationBrowsePath) : 'Inicio de la USB' }}">{{ $destinationBrowsePath !== '' ? str_replace('/', '  ›  ', $destinationBrowsePath) : 'Inicio de la USB' }}</span>
                                                        </div>
                                                        <div x-data="backupOverlayScroll()" x-effect="if (open) $nextTick(() => update())" class="backup-catalog-scroll-region backup-catalog-scroll-vertical-visible relative">
                                                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll max-h-[240px] divide-y divide-zinc-100 overflow-y-auto overscroll-contain">
                                                            @forelse($visibleDestinationFolders as $folder)
                                                                <div class="flex items-stretch bg-white transition hover:bg-zinc-50">
                                                                    <button type="button" @click="$wire.set('destinationFolder', @js($folder['path'])); open = false" @disabled(! $folder['writable']) class="flex min-w-0 flex-1 items-center gap-[10px] px-[20px] py-[15px] text-left text-black disabled:cursor-not-allowed disabled:text-zinc-400" title="{{ $folder['writable'] ? 'Seleccionar '.str_replace('/', '\\', $folder['path']) : 'Sin permiso de escritura' }}">
                                                                        <svg class="h-[18px] w-[18px] shrink-0 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 7h7l2 2h9v10H3V7Z"/></svg>
                                                                        <span class="truncate">{{ $folder['name'] }}</span>
                                                                        @if($destinationFolder === $folder['path'])
                                                                            <svg class="ml-auto h-4 w-4 shrink-0 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                                                        @endif
                                                                    </button>
                                                                    @if($folder['has_children'])
                                                                        <button type="button" @click.stop="$wire.browseDestinationFolder(@js($folder['path']))" class="inline-flex shrink-0 items-center justify-center gap-[10px] border-l border-zinc-200 px-[20px] py-[15px] text-zinc-500 transition hover:bg-white hover:text-black" aria-label="Abrir {{ $folder['name'] }}" title="Ver subcarpetas">
                                                                            <span>Abrir</span>
                                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6"/></svg>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <div class="flex items-center gap-[10px] px-[20px] py-[15px] text-zinc-500">
                                                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 7h7l2 2h9v10H3V7Z"/></svg>
                                                                    <span>Esta carpeta no contiene subcarpetas.</span>
                                                                </div>
                                                            @endforelse
                                                            </div>
                                                            <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="grid w-full {{ $selectedDeletableCount > 0 && auth()->user()->can('manage-system-backups') ? 'grid-cols-2' : 'grid-cols-1' }} gap-[10px]">
                                                    <button type="button" wire:click="startExport" wire:loading.attr="disabled" wire:target="startExport" @disabled(!$exportDestination['connected'] || $destinationFolder === '' || $selectedExportCount === 0 || $exportIsActive) class="inline-flex h-[44px] w-full items-center justify-center gap-[9px] rounded-xl bg-black px-[12px] font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-black disabled:text-white">
                                                        <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h6l2 2h8v10H4V7Zm8-4v10m0 0 3-3m-3 3-3-3"/></svg>
                                                        <span wire:loading.remove wire:target="startExport" class="truncate">@if($selectedExportCount === 0) Selecciona empresas @elseif($selectedDeletableCount === $selectedExportCount) Reemplazar {{ $selectedExportCount === 1 ? 'respaldo' : $selectedExportCount.' respaldos' }} @elseif($selectedExportCount === 1) Respaldar 1 empresa @else Respaldar {{ $selectedExportCount }} empresas @endif</span>
                                                        <span wire:loading wire:target="startExport">Validando…</span>
                                                    </button>
                                                    @if($selectedDeletableCount > 0 && auth()->user()->can('manage-system-backups'))
                                                        <button type="button" wire:click="openDeleteConfirmation" wire:loading.attr="disabled" wire:target="openDeleteConfirmation" @disabled($exportIsActive) class="inline-flex h-[44px] w-full items-center justify-center gap-[9px] rounded-xl border border-red-200 bg-white px-[12px] font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:text-zinc-400">
                                                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/></svg>
                                                            <span class="truncate">Eliminar {{ $selectedDeletableCount }}</span>
                                                        </button>
                                                    @endif
                                                </div>
                                        </div>
                            </div>
                            <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region backup-catalog-scroll-vertical-visible backup-catalog-scroll-horizontal-visible relative h-[544px] order-3 min-h-0 flex-none">
                                <div wire:loading.flex wire:target="selectDate" class="absolute inset-0 z-30 hidden flex-col items-center justify-center bg-white text-zinc-500">
                                    <svg class="h-9 w-9 animate-spin text-[#1A3A6B]" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-20" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z"/></svg>
                                    <span class="mt-[12px] font-medium text-black">Cargando respaldos…</span>
                                </div>
                                <div wire:loading.remove wire:target="selectDate" x-ref="scroller" data-scroll-top-offset="70" data-scroll-bottom-offset="3" data-scroll-right-offset="3" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll h-full overflow-auto">
                                <table class="w-full min-w-[1010px] table-fixed border-collapse text-left">
                                    <colgroup><col class="w-[350px]"><col class="w-[110px]"><col class="w-[260px]"><col class="w-[290px]"></colgroup>
                                    <thead class="sticky top-0 z-10 bg-zinc-100 text-zinc-700 shadow-[inset_0_-1px_0_#d4d4d8]"><tr class="h-[70px] whitespace-nowrap text-center"><th class="border-r border-zinc-300 px-[20px] font-semibold"><span class="flex items-center gap-[15px] text-left"><input type="checkbox" wire:click="toggleAllAvailable" wire:loading.attr="disabled" wire:target="toggleAllAvailable" @checked($allAvailableSelected) @disabled($headerSelectableCount === 0 || $exportIsActive || $status === 'partial') aria-label="Seleccionar todas las empresas disponibles" class="h-4 w-4 shrink-0 rounded border border-zinc-400 bg-white text-[#1A3A6B] focus:ring-0 focus:ring-offset-0 disabled:cursor-not-allowed disabled:border-zinc-300 disabled:bg-zinc-100"><span class="inline-flex items-baseline gap-[6px]">Empresa <span class="font-medium text-zinc-500" title="{{ $selectedExportCount }} {{ $selectedExportCount === 1 ? 'empresa seleccionada' : 'empresas seleccionadas' }}">({{ $selectedExportCount }})</span></span></span></th><th class="border-r border-zinc-300 px-[15px] font-semibold">Hora</th><th class="border-r border-zinc-300 px-[15px] font-semibold">Archivos del servidor</th><th class="px-[15px] font-semibold">Respaldo en memoria</th></tr></thead>
                                    @forelse ($records as $record)
                                        @php
                                            $recordState = $recordAvailability[$record['id']] ?? 'incomplete';
                                            $recordDestinations = $completedDestinations[$record['id']] ?? [];
                                            $latestDestination = $recordDestinations[0] ?? null;
                                        @endphp
                                        <tbody wire:key="record-{{ $record['id'] }}" x-data="{ open: false }" class="backup-record-group {{ $loop->odd ? 'backup-record-gray' : 'backup-record-white' }} border-b border-zinc-200 last:border-b-0">
                                            <tr class="h-[70px]">
                                                <td class="min-w-0 border-r border-zinc-200 px-[20px] py-[15px]"><div class="flex min-w-0 items-center gap-[15px]"><span class="shrink-0" @click.stop><input type="checkbox" wire:model.live="selectedRecords" value="{{ $record['id'] }}" @disabled(!in_array($recordState, ['available', 'outdated', 'backed_up', 'destination_missing', 'folder_missing'], true) || $exportIsActive) aria-label="Seleccionar {{ $record['company'] }}" class="h-4 w-4 rounded border border-zinc-400 bg-white text-[#1A3A6B] focus:ring-0 focus:ring-offset-0 disabled:cursor-not-allowed disabled:border-zinc-300 disabled:bg-zinc-100 disabled:opacity-100"></span><span class="min-w-0"><span class="block truncate font-medium text-black" title="{{ $record['company'] }}">{{ $record['company'] }}</span><span class="mt-[3px] block truncate text-zinc-500" title="{{ $record['stem'] }}">{{ $record['company_folder'] ?? 'Carpeta no identificada' }}</span></span></div></td>
                                                <td class="border-r border-zinc-200 px-[15px] py-[15px] text-center font-mono tabular-nums text-zinc-600">{{ $record['time'] }}</td>
                                                <td class="border-r border-zinc-200 px-[15px] py-[15px]"><div class="flex items-center justify-between gap-[10px]"><span class="truncate">@if($record['database'] && $record['archive'])<span class="text-emerald-700">BAK e INDEX</span>@elseif(!$record['database'] && !$record['archive'])<span class="text-red-700">Faltan BAK e INDEX</span>@elseif(!$record['database'])<span class="text-red-700">Falta BAK</span>@else<span class="text-red-700">Falta INDEX</span>@endif</span><button type="button" @click.stop="open = !open" class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-black" :aria-expanded="open"><svg class="h-5 w-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg></button></div></td>
                                                <td class="min-w-0 px-[15px] py-[15px] text-center">
                                                    @if($latestDestination)
                                                        <div class="mx-auto min-w-0" title="{{ $latestDestination['label'] }} · {{ $latestDestination['path'] }}"><p class="truncate font-medium text-black">{{ $latestDestination['label'] }}</p><p class="mt-[3px] truncate text-zinc-500">{{ $latestDestination['path'] }}</p>@if(count($recordDestinations) > 1)<p class="mt-[2px] text-zinc-500">+{{ count($recordDestinations) - 1 }} {{ count($recordDestinations) === 2 ? 'destino' : 'destinos' }}</p>@endif</div>
                                                    @elseif($recordState === 'available')<span class="text-emerald-700">Disponible</span>
                                                    @elseif($recordState === 'backed_up')<span class="text-[#1A3A6B]">Ya respaldada</span>
                                                    @elseif($recordState === 'copying')<span class="text-violet-700">Copiando</span>
                                                    @elseif($recordState === 'outdated')<span class="text-orange-700">Requiere actualización</span>
                                                    @elseif($recordState === 'older')<span class="text-zinc-500" title="Solo se permite el respaldo completo más reciente de la empresa para este día.">Hay uno más reciente</span>
                                                    @elseif($recordState === 'destination_missing')<span class="text-amber-700">Conecta la memoria</span>
                                                    @elseif($recordState === 'folder_missing')<span class="text-zinc-600">Selecciona carpeta</span>
                                                    @else<span class="text-red-700">Incompleta</span>@endif
                                                </td>
                                            </tr>
                                            <tr x-show="open" x-cloak>
                                                <td colspan="4" class="backup-record-detail bg-inherit p-0">
                                                    <div class="grid border-t border-zinc-200 md:grid-cols-2 md:divide-x md:divide-zinc-200">
                                                        @if($record['database'])
                                                            <a @click.stop href="{{ route('activity-backups.server.download', ['kind' => 'database', 'token' => $record['database']['token']]) }}" class="flex min-h-[78px] min-w-0 items-center justify-between gap-[15px] bg-white px-[20px] py-[15px] transition hover:bg-zinc-50" title="Descargar {{ $record['database']['name'] }}"><span class="flex min-w-0 items-center gap-[12px]"><span class="grid h-10 w-10 shrink-0 place-items-center border border-zinc-200 bg-white text-black"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM14 2v5h5"/></svg></span><span class="min-w-0"><span class="block font-medium text-black">Base de datos · BAK</span><span class="mt-[2px] block truncate text-zinc-500">{{ $record['database']['name'] }} · {{ $formatBytes($record['database']['size']) }}</span></span></span><svg class="h-5 w-5 shrink-0 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></a>
                                                        @else
                                                            <div class="flex min-h-[78px] items-center gap-[12px] bg-white px-[20px] py-[15px] text-zinc-400"><span class="grid h-10 w-10 shrink-0 place-items-center border border-zinc-200 bg-white"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM9 13h6"/></svg></span><span><span class="block font-medium">Base de datos · BAK</span><span>Archivo no encontrado</span></span></div>
                                                        @endif
                                                        @if($record['archive'])
                                                            <a @click.stop href="{{ route('activity-backups.server.download', ['kind' => 'archive', 'token' => $record['archive']['token']]) }}" class="flex min-h-[78px] min-w-0 items-center justify-between gap-[15px] bg-white px-[20px] py-[15px] transition hover:bg-zinc-50" title="Descargar {{ $record['archive']['name'] }}"><span class="flex min-w-0 items-center gap-[12px]"><span class="grid h-10 w-10 shrink-0 place-items-center border border-zinc-200 bg-white text-black"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM14 2v5h5M10 11h4m-4 4h4"/></svg></span><span class="min-w-0"><span class="block font-medium text-black">Documento digital · {{ strtoupper($record['archive']['extension']) }}</span><span class="mt-[2px] block truncate text-zinc-500">{{ $record['archive']['name'] }} · {{ $formatBytes($record['archive']['size']) }}</span></span></span><svg class="h-5 w-5 shrink-0 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></a>
                                                        @else
                                                            <div class="flex min-h-[78px] items-center gap-[12px] bg-white px-[20px] py-[15px] text-zinc-400"><span class="grid h-10 w-10 shrink-0 place-items-center border border-zinc-200 bg-white"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM9 13h6"/></svg></span><span><span class="block font-medium">Documento digital</span><span>ZIP o RAR no encontrado</span></span></div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    @empty
                                        <tbody><tr class="h-[474px]"><td colspan="4" class="px-[20px] text-center align-middle"><svg class="mx-auto h-10 w-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 7h7l2 2h9v11H3V7Z"/></svg>@if($status !== 'all' && count($catalog['records']) > 0)<p class="mt-[12px] font-medium text-black">No hay respaldos en este estado</p><p class="mt-[4px] text-[13px] text-zinc-400">La fecha tiene {{ count($catalog['records']) }} {{ count($catalog['records']) === 1 ? 'respaldo' : 'respaldos' }} en otros estados.</p><button type="button" wire:click="$set('status', 'all')" class="mt-[15px] font-medium text-[#1A3A6B] transition hover:text-[#102a52]">Mostrar todos los respaldos</button>@else<p class="mt-[12px] font-medium text-black">No hay resultados</p><p class="mt-[4px] text-[13px] text-zinc-400">Cambia la fecha o el departamento.</p>@endif</td></tr></tbody>
                                    @endforelse
                                </table>
                                </div>
                                <span wire:loading.remove wire:target="selectDate" x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                <span wire:loading.remove wire:target="selectDate" x-show="horizontalVisible" x-cloak @pointerdown="startHorizontalDrag($event)" class="backup-catalog-scroll-thumb-x" :style="`width:${width}px; transform:translateX(${left}px)`" aria-label="Desplazar tabla horizontalmente"></span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="border-b border-zinc-200 px-[20px] py-[15px]"><h2 class="font-semibold text-black">Estado de respaldo de hoy</h2><p class="mt-[3px] text-[12px] text-zinc-400">Una empresa se considera completa cuando existen su archivo BAK y su documento ZIP o RAR.</p></div>
                    <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region relative max-h-[650px]">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll max-h-[650px] overflow-auto">
                        <table class="w-full min-w-[780px] border-collapse text-left">
                            <thead class="sticky top-0 z-10 bg-zinc-50 text-[12px] uppercase tracking-[0.05em] text-zinc-400"><tr><th class="px-[20px] py-[13px]">Empresa</th><th class="px-[15px] py-[13px]">Estado hoy</th><th class="px-[15px] py-[13px]">Respaldos hoy</th><th class="px-[20px] py-[13px]">Último respaldo localizado</th></tr></thead>
                            <tbody class="divide-y divide-zinc-200">
                                @forelse($companies as $company)
                                    <tr wire:key="company-{{ $company['key'] }}" class="hover:bg-zinc-50/70"><td class="px-[20px] py-[15px]"><p class="font-medium text-black">{{ $company['name'] }}</p><p class="mt-[2px] text-[11px] text-zinc-400">{{ $company['folder'] }}</p></td><td class="px-[15px] py-[15px]">@if($company['today_status'] === 'complete')<span class="inline-flex rounded-full bg-emerald-50 px-[10px] py-[5px] text-[12px] font-medium text-emerald-700">Respaldada</span>@elseif($company['today_status'] === 'partial')<span class="inline-flex rounded-full bg-amber-50 px-[10px] py-[5px] text-[12px] font-medium text-amber-700">Incompleta</span>@else<span class="inline-flex rounded-full bg-zinc-100 px-[10px] py-[5px] text-[12px] font-medium text-zinc-500">Pendiente</span>@endif</td><td class="px-[15px] py-[15px] tabular-nums">{{ $company['today_records'] }}</td><td class="px-[20px] py-[15px] text-[13px] text-zinc-500">@if($company['last_backup']){{ \Carbon\CarbonImmutable::parse($company['last_backup']['occurred_at'])->translatedFormat('d M Y · H:i') }}@else<span class="text-zinc-400">Sin respaldo localizado</span>@endif</td></tr>
                                @empty<tr><td colspan="4" class="px-[20px] py-[70px] text-center text-zinc-400">No hay empresas que coincidan con la búsqueda.</td></tr>@endforelse
                            </tbody>
                        </table>
                        </div>
                        <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                @endif

                <footer class="flex flex-col gap-[5px] border-t border-zinc-200 bg-zinc-50 px-[20px] py-[12px] text-[11px] text-zinc-400 md:flex-row md:items-center md:justify-between"><span>Origen de solo lectura: {{ $catalog['root'] }}</span><span>Última lectura: {{ \Carbon\CarbonImmutable::parse($catalog['scanned_at'])->translatedFormat('d M Y · H:i:s') }}</span></footer>
            </section>
        @endif
    </main>
</div>
