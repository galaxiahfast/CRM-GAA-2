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

<div data-panel class="backup-storage-monochrome relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full overflow-hidden text-[15px]"
    x-data="{
        viewScale: Number(null || 90),
        isFullscreen: false,
        saveScale() { localStorage.setItem('backup-storage-view-scale-v3', String(this.viewScale)) },
        zoomOut() { this.viewScale = Math.max(70, this.viewScale - 5); this.saveScale() },
        zoomIn() { this.viewScale = Math.min(100, this.viewScale + 5); this.saveScale() },
        async toggleFullscreen() { if (document.fullscreenElement) await document.exitFullscreen(); else await $root.requestFullscreen() }
    }"
    x-init="document.addEventListener('fullscreenchange', () => isFullscreen = Boolean(document.fullscreenElement))"
    @if($exportIsActive)
        wire:poll.1s="refreshSharedExportStatus"
    @else
        wire:poll.15s="refreshSharedExportStatus"
    @endif>
    <x-panel-styles />
    <style>
        .backup-catalog-scroll { scrollbar-width: none; overscroll-behavior: contain; }
        .backup-catalog-scroll::-webkit-scrollbar { width: 0; height: 0; }
        .backup-today-scroll { scroll-snap-type: y mandatory; scroll-padding-top: 60px; }
        .backup-today-scroll tbody tr { scroll-snap-align: start; }
        .backup-history-scroll { scroll-snap-type: y mandatory; scroll-padding-top: 70px; }
        .backup-history-scroll tbody tr { scroll-snap-align: start; }
        .backup-catalog-scroll-thumb { position: absolute; right: 0; top: 0; z-index: 20; width: 3px; min-height: 32px; border-radius: 9999px; background: var(--panel-accent); opacity: 0; pointer-events: none; transition: opacity 140ms ease; }
        .backup-catalog-scroll-thumb-x { position: absolute; bottom: 0; left: 0; z-index: 21; height: 14px; min-width: 48px; background: transparent; opacity: 0; cursor: ew-resize; touch-action: none; transition: opacity 140ms ease; }
        .backup-catalog-scroll-thumb-x::after { content: ''; position: absolute; right: 0; bottom: 0; left: 0; height: 3px; border-radius: 9999px; background: var(--panel-accent); }
        .backup-catalog-scroll-region:hover > .backup-catalog-scroll-thumb { opacity: 1; }
        .backup-catalog-scroll-region:hover > .backup-catalog-scroll-thumb-x { opacity: 1; }
        .backup-catalog-scroll-vertical-visible > .backup-catalog-scroll-thumb { opacity: 1; }
        .backup-catalog-scroll-horizontal-visible > .backup-catalog-scroll-thumb-x { opacity: 1; }
        .backup-record-group > tr { background: inherit; }
        .backup-record-gray > tr > td { background-color: var(--panel-subtle); }
        .backup-record-white > tr > td { background-color: var(--panel-surface); }
        .backup-storage-monochrome :where(th, td, thead, tbody) { font-weight: 400; }
        .backup-record-detail > div > :where(a, div) { background: transparent !important; }
        .backup-storage-monochrome { --backup-green: #15803d; font-size: 15px; }
        html.module-dark-theme .backup-storage-monochrome { --backup-green: #4ade80; }
        .backup-green { color: var(--backup-green); }
        .backup-action-button { background-color: #15803d; color: #fff; }
        .backup-action-button:hover:not(:disabled) { background-color: #166534; }
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
        .backup-storage-monochrome:fullscreen { overflow: auto; }
        html.module-dark-theme #main-content .backup-storage-monochrome table :is(thead, tfoot) { background: var(--panel-header) !important; }
        html.module-dark-theme #main-content .backup-storage-monochrome table :is(tbody, tr) { background-color: transparent !important; }
        html.module-dark-theme #main-content .backup-storage-monochrome .backup-record-gray > tr > td { background-color: var(--panel-subtle) !important; }
        html.module-dark-theme #main-content .backup-storage-monochrome .backup-record-white > tr > td { background-color: var(--panel-surface) !important; }
        /* Menú de carpetas destino */
        .backup-folder-menu { box-shadow: 0 18px 40px -14px rgba(0, 0, 0, .28); }
        .backup-folder-row { transition: background-color 150ms ease; }
        .backup-folder-row:hover { background: var(--panel-subtle); }
        .backup-folder-row:hover > button:first-child, .backup-folder-row.is-selected > button:first-child { color: var(--panel-accent); }
        .backup-folder-open { display: grid; place-items: center; padding: 0 20px; color: var(--panel-accent); border-left: 1px solid var(--panel-border); transition: background-color 150ms ease; }
        .backup-folder-open:hover { background: var(--panel-hover); }
        .backup-folder-back { display: grid; place-items: center; flex: none; height: 15px; width: 15px; color: var(--panel-accent); }
        /* Tabla de empresas adaptable: columnas en porcentaje; en pantallas muy angostas se desplaza lateralmente, nunca se corta */
        .backup-records-table { min-width: 760px; }
        .backup-records-table td, .backup-records-table th { overflow-wrap: anywhere; }
        /* Al desplazar, las listas de fechas y de empresas se detienen en el inicio de una fila: nunca queda una cortada */
        [data-dates-scroller] { scroll-snap-type: y mandatory; }
        [data-dates-scroller] > [data-date-row] { scroll-snap-align: start; height: var(--backup-date-h, auto); }
        [data-records-scroller] tbody.backup-record-group > tr:first-child { height: var(--backup-row-h, auto); }
        [data-records-scroller] { scroll-snap-type: y mandatory; }
        [data-records-scroller] tbody.backup-record-group { scroll-snap-align: start; }
        /* Tarjeta de respaldos: en pantallas grandes mide exactamente lo que queda de pantalla debajo de la cabecera
           (cabecera de 58px y separación igual al margen de la página), así no se sale de la pantalla */
        @media (min-width: 1280px) { .backup-main-card { height: calc(var(--app-dvh) - 123px - 50px - 58px - 50px - 50px); min-height: 640px; } }
        /* La tarjeta del explorador mide exactamente lo que queda de pantalla (menos el margen de la página): al bajar se ve completa, sin pedazos de la tarjeta de arriba */
        .backup-explorer-card { height: calc(var(--app-dvh) - 123px - 40px); min-height: 640px; }
        @media (min-width: 640px) { .backup-explorer-card { height: calc(var(--app-dvh) - 123px - 60px); } }
        @media (min-width: 1280px) { .backup-explorer-card { height: calc(var(--app-dvh) - 123px - 100px); } }
        /* Explorador de la memoria: como el Explorador de Windows (barra de dirección, Atrás/Subir y clic para entrar) */
        .backup-explorer .explorer-tool { display: inline-grid; flex: none; place-items: center; padding: 20px; border: 1px solid var(--panel-border); border-radius: 12px; background: var(--panel-surface); color: var(--panel-text); transition: background-color 150ms ease; }
        .backup-explorer .explorer-tool:hover:not(:disabled) { background: var(--panel-subtle); }
        .backup-explorer .explorer-tool:disabled { opacity: .55; cursor: not-allowed; }
        .backup-explorer .explorer-tool svg { stroke-width: 2.5; }
        .backup-explorer .explorer-address { display: flex; min-width: 0; flex: 1 1 auto; align-items: center; line-height: 1; gap: 10px; overflow-x: auto; white-space: nowrap; border: 1px solid var(--panel-border); border-radius: 12px; background: var(--panel-surface); padding: 20px; scrollbar-width: none; }
        .backup-explorer .explorer-address::-webkit-scrollbar { display: none; }
        .backup-explorer .explorer-crumb { flex: none; border-radius: 8px; padding: 0; line-height: 1; transition: background-color 150ms ease; }
        .backup-explorer .explorer-crumb:hover { background: var(--panel-subtle); }
        .backup-explorer .explorer-cols, .backup-explorer .explorer-row { display: grid; grid-template-columns: minmax(0, 1fr) 130px 100px 180px; align-items: center; gap: 20px; padding: 20px; line-height: 1; border: 1px solid transparent; }
        .backup-explorer .explorer-cols { color: var(--panel-accent); }
        .backup-explorer .explorer-scroll { scroll-snap-type: y mandatory; }
        .backup-explorer .explorer-scroll .explorer-row { height: var(--explorer-row-h, auto); }
        .backup-explorer .explorer-scroll .explorer-row { scroll-snap-align: start; flex: none; }
        .backup-explorer .explorer-list { display: flex; flex-direction: column; gap: 20px; }
        .backup-explorer .explorer-row { width: 100%; border: 1px solid var(--panel-border); border-radius: 12px; background: var(--panel-surface); text-align: left; transition: background-color 150ms ease, border-color 150ms ease; }
        .backup-explorer button.explorer-row { cursor: pointer; }
        .backup-explorer button.explorer-row:hover { background: var(--panel-subtle); color: var(--panel-accent); }
        .backup-explorer .explorer-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .backup-explorer-card .dg { display: inline-block; width: 0.62em; text-align: center; }
        .backup-explorer .explorer-size { display: grid; grid-template-columns: minmax(0, 1fr) 26px; justify-content: end; column-gap: 6px; white-space: nowrap; }
        .backup-explorer .explorer-size > span:first-child { text-align: right; }
        .backup-explorer .explorer-date { display: grid; grid-template-columns: 20px 38px 38px 10px 44px; justify-content: end; align-items: center; column-gap: 3px; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .backup-explorer .explorer-date > span:nth-child(1), .backup-explorer .explorer-date > span:nth-child(3), .backup-explorer .explorer-date > span:nth-child(5) { text-align: right; }
        .backup-explorer .explorer-date > span:nth-child(2) { text-align: left; padding-left: 4px; }
        .backup-explorer .explorer-date-sep { text-align: center; }
        .backup-explorer .explorer-ext { min-width: 50px; border-radius: 6px; box-shadow: inset 0 0 0 1px var(--panel-border); padding: 0 8px; text-align: center; line-height: 15px; color: var(--panel-accent); }
        .backup-explorer .explorer-tag { border-radius: 9999px; box-shadow: inset 0 0 0 1px var(--panel-accent); padding: 0 10px; color: var(--panel-accent); line-height: 15px; }
        .backup-explorer .explorer-row svg { width: 15px !important; height: 15px !important; }
        .backup-explorer .explorer-row > *, .backup-explorer .explorer-cols > * { line-height: 15px; }
        .backup-memory-button { display: inline-flex; align-items: center; justify-content: center; gap: 10px; border: 1px solid var(--panel-border); border-radius: 12px; background: var(--panel-surface); padding: 20px; line-height: 1; transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease; }
        /* Todos los botones con 20px de relleno; sus iconos miden lo mismo que el texto (15px) para que todos tengan el mismo alto */
        .backup-memory-button svg, .backup-refresh-button svg, .backup-explorer .explorer-tool svg, .backup-control svg { width: 15px !important; height: 15px !important; }
        .backup-memory-button:hover { background: var(--panel-subtle); }
        .backup-memory-button[aria-pressed="true"] { border-color: var(--backup-green); background: color-mix(in srgb, var(--backup-green) 12%, var(--panel-surface)); color: var(--backup-green); }
        .backup-refresh-button { display: inline-flex; align-items: center; gap: 10px; border: 1px solid var(--backup-green); border-radius: 12px; background: color-mix(in srgb, var(--backup-green) 12%, var(--panel-surface)); color: var(--backup-green); padding: 20px; line-height: 1; transition: background-color 150ms ease; }
        .backup-refresh-button:hover:not(:disabled) { background: color-mix(in srgb, var(--backup-green) 20%, var(--panel-surface)); }
        .backup-refresh-button:disabled { opacity: .6; }
        @media (max-width: 1023px) { .backup-explorer .explorer-cols, .backup-explorer .explorer-row { grid-template-columns: minmax(0, 1fr) 100px; } .backup-explorer .explorer-hide-md { display: none; } }
    </style>

    @if($exportError)
        @teleport('body')
            <div wire:key="backup-export-error-{{ $exportErrorVersion }}-{{ md5((string) $exportError.($exportStatus['completed_at'] ?? '')) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 5000)" x-show="visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3" data-panel class="app-zoom-overlay fixed left-1/2 top-[20px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-center gap-[10px] rounded-xl border border-[color:var(--panel-danger-line)] panel-surface p-[20px] text-[color:var(--panel-danger)] shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="alert">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg panel-danger-box"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg></span>
                <span class="min-w-0 flex-1">{{ $exportError }}</span>
                <button type="button" @click="visible = false" aria-label="Cerrar aviso" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition hover:bg-[color:var(--panel-hover)]"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
            </div>
        @endteleport
    @endif

    @if($syncResult)
        @teleport('body')
            <div wire:key="backup-sync-result-{{ md5(json_encode($syncResult)) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 6000)" x-show="visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3" data-panel class="app-zoom-overlay backup-sync-toast fixed left-1/2 top-[20px] z-[199998] w-[calc(100%-40px)] max-w-3xl -translate-x-1/2 rounded-xl border {{ ($syncResult['error'] ?? null) ? 'border-[color:var(--panel-danger-line)]' : 'panel-border' }} panel-surface p-[20px] shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="status" aria-live="polite">
                <div class="flex items-start gap-[12px]">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ ($syncResult['error'] ?? null) ? 'panel-danger-box text-[color:var(--panel-danger)]' : 'panel-header panel-accent' }}">
                        @if($syncResult['error'] ?? null)
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.6 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m8 12 2.5 2.5L16 9"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        @if($syncResult['error'] ?? null)
                            <p class="text-[color:var(--panel-danger)]">No se pudo comprobar</p>
                            <p class="mt-[15px] text-[color:var(--panel-danger)]">{{ $syncResult['error'] }}</p>
                        @else
                            <p class="">Sincronización comprobada</p>
                            <p class="mt-[15px] ">Parejas BAK + ZIP/RAR y tamaños comprobados {{ \Carbon\CarbonImmutable::parse($syncResult['checked_at'])->translatedFormat('d M Y · H:i:s') }}</p>
                            <div class="mt-[10px] flex flex-wrap gap-x-[15px] gap-y-[5px]">
                                <span class="panel-accent">{{ $syncResult['server_complete'] }} completas en servidor</span>
                                <span class="text-[color:var(--panel-danger)]">{{ $syncResult['server_incomplete'] }} incompletas</span>
                                <span class="panel-accent">{{ $syncResult['synced'] }} sincronizadas</span>
                                <span class="">{{ $syncResult['pending'] }} pendientes</span>
                                @if($syncResult['outdated'] > 0)<span class="">{{ $syncResult['outdated'] }} por actualizar</span>@endif
                                @if($syncResult['copying'] > 0)<span class="">{{ $syncResult['copying'] }} copiándose</span>@endif
                            </div>
                        @endif
                    </div>
                    <button type="button" @click="visible = false" aria-label="Cerrar resultado" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition hover:bg-[color:var(--panel-hover)]"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                </div>
            </div>
        @endteleport
    @endif

    @if($actionMessage)
        @teleport('body')
            <div wire:key="backup-action-result-{{ md5($actionMessage) }}" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 5000)" x-show="visible" x-transition data-panel class="fixed left-1/2 top-[20px] z-[200000] flex w-[calc(100%-40px)] max-w-xl -translate-x-1/2 items-center gap-[10px] rounded-xl border panel-border panel-surface p-[20px] panel-accent shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="status">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9"/></svg>
                <span class="min-w-0 flex-1">{{ $actionMessage }}</span>
                <button type="button" @click="visible = false" aria-label="Cerrar aviso" class="inline-flex h-8 w-8 items-center justify-center "><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
            </div>
        @endteleport
    @endif

    @if($showDeleteConfirmation)
        @teleport('body')
            <div class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-backups-title">
                <div data-panel class="panel-border w-full max-w-lg rounded-xl border p-[20px] shadow-2xl">
                    <h2 id="delete-backups-title" class="">Eliminar {{ count($pendingDeleteRecords) === 1 ? 'respaldo' : count($pendingDeleteRecords).' respaldos' }}</h2>
                    <p class="mt-[5px] ">Solo se eliminarán las copias de la memoria externa. Los archivos originales del servidor no se modificarán.</p>
                    <div class="mt-[20px] space-y-[15px]">
                        <div>
                            <label for="delete-backup-password" class="mb-[10px] block leading-none">Contraseña</label>
                            <input id="delete-backup-password" type="password" wire:model="deletePassword" autocomplete="current-password" class="panel-field h-[50px] w-full rounded-xl border px-[15px] ">
                            @error('deletePassword')<p class="mt-[5px] text-[color:var(--panel-danger)]">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="delete-backup-code" class="mb-[10px] block leading-none">Código del autenticador</label>
                            <input id="delete-backup-code" type="text" inputmode="numeric" maxlength="6" wire:model="deleteTwoFactorCode" autocomplete="one-time-code" class="panel-field h-[50px] w-full rounded-xl border px-[15px] tracking-[0.2em]">
                            @error('deleteTwoFactorCode')<p class="mt-[5px] text-[color:var(--panel-danger)]">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mt-[20px] grid grid-cols-2 gap-[10px]">
                        <button type="button" wire:click="closeDeleteConfirmation" wire:loading.attr="disabled" wire:target="deleteDestinationBackups" class="panel-button-secondary h-[50px] w-full rounded-xl px-[15px] leading-none">Cancelar</button>
                        <button type="button" wire:click="deleteDestinationBackups" wire:loading.attr="disabled" wire:target="deleteDestinationBackups" class="h-[50px] w-full rounded-xl bg-red-700 px-[15px] text-white hover:bg-red-800 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="deleteDestinationBackups">Eliminar definitivamente</span><span wire:loading wire:target="deleteDestinationBackups">Verificando…</span></button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if($exportIsActive)
        @teleport('body')
            <div wire:key="shared-backup-progress-{{ $exportStatus['id'] ?? 'active' }}" data-panel class="app-zoom-overlay fixed left-1/2 top-[20px] z-[199999] w-[calc(100%-40px)] max-w-2xl -translate-x-1/2 rounded-xl border panel-border panel-surface p-[20px] shadow-[0_14px_40px_rgba(0,0,0,0.20)]" role="status" aria-live="polite">
                <div class="flex items-center justify-between gap-[15px]">
                    <div class="min-w-0">
                        <p class="truncate ">
                            @if(($exportStatus['status'] ?? '') === 'queued') Respaldo en espera
                            @elseif(($exportStatus['status'] ?? '') === 'cancelling') Cancelando respaldo…
                            @else Respaldando {{ $exportStatus['current_company'] ?? 'archivos' }}
                            @endif
                        </p>
                        <p class="mt-[15px] truncate ">Todos los usuarios pueden ver este avance. No se puede iniciar otro respaldo hasta que termine.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-[15px]">
                        <span class="tabular-nums ">{{ $exportPercent }}% · {{ $formatBytes((int) ($exportStatus['copied_bytes'] ?? 0)) }} / {{ $formatBytes((int) ($exportStatus['total_bytes'] ?? 0)) }}</span>
                        <button type="button" wire:click="cancelExport" wire:loading.attr="disabled" wire:target="cancelExport" @disabled(($exportStatus['status'] ?? null) === 'cancelling') class="text-[color:var(--panel-danger)] transition hover:opacity-70 disabled:cursor-wait disabled:opacity-50">{{ ($exportStatus['status'] ?? null) === 'cancelling' ? 'Cancelando…' : 'Cancelar respaldo' }}</button>
                    </div>
                </div>
                <div class="mt-[12px] h-[6px] overflow-hidden rounded-full bg-[color:var(--panel-track)]"><span class="block h-full rounded-full bg-[color:var(--panel-accent)] transition-all duration-300" style="width: {{ $exportPercent }}%"></span></div>
            </div>
        @endteleport
    @endif


    <main class="flex w-full flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]">
        <section class="backup-page-header flex flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
            <div class="min-w-0">
                <h1 class="panel-accent text-xl leading-none tracking-tight">Gestión de Respaldos</h1>
                <p class="mt-[5px] leading-[30px]">Selecciona una carpeta para comprobar cuáles ya están respaldadas y definir el destino.</p>
            </div>

            <div class="flex flex-wrap items-center gap-[20px] lg:justify-end">
                {{-- La comprobación se hace sola cada 5 minutos mientras la pantalla está a la vista; no corre al entrar para no retrasar la carga --}}
                <span wire:poll.300s.visible="autoVerify" class="hidden" aria-hidden="true"></span>
                <div class="flex items-center gap-[20px]" role="group" aria-label="Secciones de respaldos">
                    <button type="button" wire:click="selectView('backups')" class="backup-memory-button" aria-pressed="{{ $activeView === 'backups' ? 'true' : 'false' }}"><svg class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v7c0 1.66 3.13 3 7 3s7-1.34 7-3V5M5 12v7c0 1.66 3.13 3 7 3s7-1.34 7-3v-7"/></svg><span>Respaldos</span></button>
                    <button type="button" wire:click="selectView('history')" class="backup-memory-button" aria-pressed="{{ $activeView === 'history' ? 'true' : 'false' }}"><svg class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2M4.9 4.9A10 10 0 1 1 2 12m0 0V6m0 6h6"/></svg><span>Historial</span></button>
                    <button type="button" wire:click="verifySynchronization" wire:loading.attr="disabled" wire:target="verifySynchronization" class="backup-refresh-button">
                        <svg wire:loading.class="animate-spin" wire:target="verifySynchronization" class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0-2.34 5.66M20 4v7h-7"/></svg>
                        <span wire:loading.remove wire:target="verifySynchronization">Actualizar</span>
                        <span wire:loading wire:target="verifySynchronization" style="display: none;">Actualizando…</span>
                    </button>
                </div>
            </div>
        </section>

        @if ($error && $activeView !== 'history')
            <section class="panel-danger-box rounded-xl border p-[20px]">
                <div class="flex items-start gap-[12px]">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/></svg>
                    <div><p class="leading-none">No se pudo leer el servidor</p><p class="mt-[15px] leading-none">{{ $error }} No se realizó ningún cambio en la ubicación de origen.</p></div>
                </div>
            </section>
        @else
            <section @if ($activeView === 'backups') x-data="backupAlign()" @endif class="{{ $activeView === 'backups' ? 'backup-main-card' : '' }} flex flex-col overflow-hidden rounded-xl border panel-border panel-surface shadow-none">
                @if ($activeView === 'backups')
                    <div class="grid min-h-[520px] xl:min-h-0 xl:flex-1 xl:grid-cols-[340px_minmax(0,1fr)]">
                        <aside class="h-[520px] min-h-0 overflow-hidden border-b panel-border panel-surface xl:relative xl:h-auto xl:border-b-0 xl:border-r" aria-label="Tabla de días con respaldos">
                            <div class="flex h-full min-h-0 flex-col xl:absolute xl:inset-0">
                                <div data-dates-header wire:ignore.self class="flex shrink-0 items-center justify-center border-b panel-border panel-header px-[20px] py-[20px] text-center "><span class="panel-accent flex min-w-0 items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg><span class="truncate">Fecha de respaldos</span></span></div>
                                {{-- A la altura de los filtros: más fechas --}}
                                <div data-dates-tools wire:ignore.self class="flex shrink-0 items-center justify-center border-b panel-border px-[20px] py-[20px] text-center">
                                    @if($hasMoreBackupDates)
                                        <button type="button" wire:click="loadMoreDates" wire:loading.attr="disabled" wire:target="loadMoreDates" class="backup-green leading-none transition hover:opacity-70 disabled:opacity-50">
                                            <span wire:loading.remove wire:target="loadMoreDates">Mostrar fechas anteriores</span>
                                            <span wire:loading wire:target="loadMoreDates">Cargando…</span>
                                        </button>
                                    @else
                                        <span class="leading-none">{{ count($visibleBackupDates) }} {{ count($visibleBackupDates) === 1 ? 'fecha' : 'fechas' }}</span>
                                    @endif
                                </div>
                                {{-- A la altura del encabezado de la tabla --}}
                                <div data-dates-head wire:ignore.self class="flex shrink-0 items-center border-b panel-border panel-header px-[20px] py-[20px] leading-none">Fecha</div>
                                <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region relative min-h-0 flex-1">
                                    <div x-ref="scroller" data-dates-scroller wire:ignore.self @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll h-full overflow-y-auto">
                                @forelse ($visibleBackupDates as $day)
                                    @php
                                        $dayDate = \Carbon\CarbonImmutable::parse($day['date']);
                                        $fullDayLabel = ucfirst($dayDate->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y'));
                                    @endphp
                                    <button type="button" wire:key="backup-day-{{ $day['date'] }}" wire:click="selectDate('{{ $day['date'] }}')" wire:loading.attr="disabled" wire:target="selectDate" data-date-row class="flex w-full min-w-0 flex-col justify-center border-b panel-border p-[20px] text-left transition disabled:cursor-wait {{ $selectedDate === $day['date'] ? 'panel-header panel-accent' : ($loop->odd ? 'panel-subtle' : 'panel-surface') }} hover:bg-[color:var(--panel-hover)]">
                                        <span class="block w-full truncate leading-none" title="{{ $fullDayLabel }}">{{ $fullDayLabel }}</span>
                                        <span class="mt-[15px] block w-full truncate leading-none" title="{{ $day['complete'] }} completos · {{ $day['partial'] }} parciales">{{ $day['complete'] }} completos · {{ $day['partial'] }} parciales</span>
                                    </button>
                                @empty
                                    <p class="truncate p-[20px] leading-none" title="No se encontraron respaldos con fecha válida.">No se encontraron respaldos con fecha válida.</p>
                                @endforelse
                                    </div>
                                    <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>
                            </div>
                        </aside>

                        <div class="relative flex min-h-0 min-w-0 flex-col">
                            <div class="order-2 flex flex-col gap-[12px] border-b panel-border p-[20px]">
                                <div class="flex w-full items-center justify-evenly gap-[16px] overflow-x-auto overflow-y-hidden whitespace-nowrap" aria-label="Filtrar respaldos por estado">
                                    <button type="button" wire:click="$set('status', 'all')" class="panel-tab flex items-center justify-center gap-[10px] px-[10px] leading-none" aria-selected="{{ $status === 'all' ? 'true' : 'false' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg><span>Todos</span></button>
                                    <button type="button" wire:click="$set('status', 'complete')" class="panel-tab flex items-center justify-center gap-[10px] px-[10px] leading-none" aria-selected="{{ $status === 'complete' ? 'true' : 'false' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9"/></svg><span>Completos</span></button>
                                    <button type="button" wire:click="$set('status', 'available')" class="panel-tab flex items-center justify-center gap-[10px] px-[10px] leading-none" aria-selected="{{ $status === 'available' ? 'true' : 'false' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg><span>Por respaldar</span></button>
                                    <button type="button" wire:click="$set('status', 'backed_up')" class="panel-tab flex items-center justify-center gap-[10px] px-[10px] leading-none" aria-selected="{{ $status === 'backed_up' ? 'true' : 'false' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 7h14v12H5zM8 4h8v3"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 13 2 2 4-5"/></svg><span>Ya respaldadas</span></button>
                                    <button type="button" wire:click="$set('status', 'partial')" class="panel-tab flex items-center justify-center gap-[10px] px-[10px] leading-none" aria-selected="{{ $status === 'partial' ? 'true' : 'false' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v6m0 4h.01"/></svg><span>Incompletos</span></button>
                                </div>
                            </div>
                            <div class="order-1 shrink-0 border-b panel-border panel-header p-[20px]">
                                        <div class="grid gap-[15px] lg:grid-cols-3">
                                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                                    <button type="button" @click="if (!{{ $exportIsActive ? 'true' : 'false' }}) open = !open" class="backup-control flex w-full items-center justify-between rounded-xl border panel-border panel-surface p-[20px] text-left leading-none ">
                                                        <span class="truncate">{{ $currentBackupProfile['label'] }}</span>
                                                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                                    </button>
                                                    <div x-show="open" x-cloak class="absolute top-full z-40 mt-[8px] w-full overflow-hidden rounded-xl border panel-border panel-surface py-[4px] shadow-lg">
                                                        @foreach($backupProfiles as $profile)
                                                            <button type="button" @click="$wire.set('backupProfile', @js($profile['key'])); open = false" @disabled(!($profile['enabled'] ?? false)) class="flex min-h-[50px] w-full items-center justify-between gap-[12px] px-[15px] text-left hover:bg-[color:var(--panel-hover)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-[color:var(--panel-hover)]">
                                                                <span class="truncate">{{ $profile['label'] }}@if(!($profile['enabled'] ?? false)) · Próximamente @endif</span>
                                                                @if($backupProfile === $profile['key'])<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>@endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                                    <button type="button" @click="if ({{ $exportDestination['connected'] && ! $exportIsActive ? 'true' : 'false' }}) { open = !open; if (open) $wire.openDestinationBrowser() }" @disabled(!$exportDestination['connected'] || $exportIsActive) class="backup-control flex w-full items-center justify-between rounded-xl border panel-border panel-surface p-[20px] text-left leading-none disabled:cursor-not-allowed disabled:opacity-50">
                                                        <span class="truncate" title="{{ str_replace('/', '\\', $destinationFolder) }}">{{ $destinationFolder !== '' ? str_replace('/', '\\', $destinationFolder) : 'Selecciona una carpeta…' }}</span>
                                                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                                    </button>
                                                    <div x-show="open" x-cloak
                                                        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                                        class="backup-folder-menu absolute left-0 right-0 top-full z-40 mt-[10px] min-w-[340px] overflow-hidden rounded-xl border panel-border panel-surface">
                                                        {{-- Ruta: Memoria › carpeta › subcarpeta (cada parte vuelve a ese nivel) --}}
                                                        <div class="panel-header flex items-center gap-[10px] border-b panel-border p-[20px] leading-none">
                                                            @if($destinationBrowsePath !== '')
                                                                <button type="button" @click.stop="$wire.browseDestinationUp()" class="backup-folder-back" title="Volver a la carpeta anterior" aria-label="Volver a la carpeta anterior">
                                                                    <svg class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m0 0 6-6m-6 6 6 6"/></svg>
                                                                </button>
                                                            @endif
                                                            <span class="flex min-w-0 flex-1 items-center gap-[8px] overflow-hidden whitespace-nowrap" title="{{ $destinationBrowsePath !== '' ? str_replace('/', '\\', $destinationBrowsePath) : 'Inicio de la USB' }}">
                                                                <span class="{{ $destinationBrowsePath === '' ? 'panel-accent' : '' }} shrink-0">Inicio de la USB</span>
                                                                @foreach($destinationBrowsePath !== '' ? explode('/', $destinationBrowsePath) : [] as $segment)
                                                                    <span class="shrink-0 opacity-60" aria-hidden="true">›</span>
                                                                    <span class="truncate {{ $loop->last ? 'panel-accent' : '' }}">{{ $segment }}</span>
                                                                @endforeach
                                                            </span>
                                                            <span wire:loading wire:target="browseDestinationFolder, browseDestinationUp, openDestinationBrowser" class="shrink-0 opacity-60" style="display: none;">Abriendo…</span>
                                                        </div>
                                                        <div x-data="backupOverlayScroll()" x-effect="if (open) $nextTick(() => update())" class="backup-catalog-scroll-region backup-catalog-scroll-vertical-visible relative">
                                                            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll max-h-[280px] divide-y divide-[color:var(--panel-border)] overflow-y-auto overscroll-contain">
                                                            @if(! $destinationBrowserOpened)
                                                                <div class="p-[20px] leading-none">Cargando carpetas…</div>
                                                            @else
                                                            @forelse($visibleDestinationFolders as $folder)
                                                                <div wire:key="destination-folder-{{ md5($folder['path']) }}" class="backup-folder-row flex items-stretch {{ $destinationFolder === $folder['path'] ? 'is-selected' : '' }}">
                                                                    <button type="button" @click="$wire.set('destinationFolder', @js($folder['path'])); open = false" @disabled(! $folder['writable']) class="flex min-w-0 flex-1 items-center gap-[10px] p-[20px] text-left leading-none disabled:cursor-not-allowed disabled:opacity-50" title="Elegir {{ $folder['name'] }}">
                                                                        <svg class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>
                                                                        <span class="truncate">{{ $folder['name'] }}</span>
                                                                        @if($destinationFolder === $folder['path'])
                                                                            <svg class="ml-auto h-[15px] w-[15px] shrink-0 backup-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                                                                        @endif
                                                                    </button>
                                                                    @if($folder['has_children'])
                                                                        <button type="button" @click.stop="$wire.browseDestinationFolder(@js($folder['path']))" class="backup-folder-open" aria-label="Abrir {{ $folder['name'] }}" title="Ver las carpetas de {{ $folder['name'] }}">
                                                                            <svg class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <div class="p-[20px] leading-none">Esta carpeta no contiene subcarpetas.</div>
                                                            @endforelse
                                                            @endif
                                                            </div>
                                                            <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                                        </div>
                                                        {{-- Dentro de una carpeta también se puede elegir la carpeta actual --}}
                                                        @if($destinationBrowserOpened && $destinationBrowsePath !== '')
                                                            <div class="border-t panel-border p-[20px]">
                                                                <button type="button" @click="$wire.set('destinationFolder', @js($destinationBrowsePath)); open = false" class="backup-control backup-refresh-button w-full justify-center p-[20px]">Usar esta carpeta</button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="grid w-full {{ $selectedDeletableCount > 0 && auth()->user()->can('manage-system-backups') ? 'grid-cols-2' : 'grid-cols-1' }} gap-[10px]">
                                                    <button type="button" wire:click="startExport" wire:loading.attr="disabled" wire:target="startExport" @disabled(!$exportDestination['connected'] || $destinationFolder === '' || $selectedExportCount === 0 || $exportIsActive) class="backup-control backup-action-button inline-flex w-full items-center justify-center gap-[10px] rounded-xl p-[20px] leading-none transition disabled:cursor-not-allowed disabled:opacity-50">
                                                        <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h6l2 2h8v10H4V7Zm8-4v10m0 0 3-3m-3 3-3-3"/></svg>
                                                        <span wire:loading.remove wire:target="startExport" class="truncate">@if($selectedExportCount === 0) Selecciona empresas @elseif($selectedDeletableCount === $selectedExportCount) Reemplazar {{ $selectedExportCount === 1 ? 'respaldo' : $selectedExportCount.' respaldos' }} @elseif($selectedExportCount === 1) Respaldar 1 empresa @else Respaldar {{ $selectedExportCount }} empresas @endif</span>
                                                        <span wire:loading wire:target="startExport">Validando…</span>
                                                    </button>
                                                    @if($selectedDeletableCount > 0 && auth()->user()->can('manage-system-backups'))
                                                        <button type="button" wire:click="openDeleteConfirmation" wire:loading.attr="disabled" wire:target="openDeleteConfirmation" @disabled($exportIsActive) class="backup-control inline-flex w-full items-center justify-center gap-[10px] rounded-xl border border-red-200 panel-surface p-[20px] leading-none text-[color:var(--panel-danger)] transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                                                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/></svg>
                                                            <span class="truncate">Eliminar {{ $selectedDeletableCount }}</span>
                                                        </button>
                                                    @endif
                                                </div>
                                        </div>
                            </div>
                            <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region backup-catalog-scroll-vertical-visible backup-catalog-scroll-horizontal-visible relative order-3 h-[700px] min-h-0 flex-none xl:h-auto xl:flex-1">
                                <div wire:loading.flex wire:target="selectDate" class="absolute inset-0 z-30 hidden flex-col items-center justify-center panel-surface ">
                                    <svg class="h-9 w-9 animate-spin panel-accent" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-20" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z"/></svg>
                                    <span class="mt-[12px] ">Cargando respaldos…</span>
                                </div>
                                <div wire:loading.remove wire:target="selectDate" x-ref="scroller" data-records-scroller wire:ignore.self data-scroll-top-offset="70" data-scroll-bottom-offset="3" data-scroll-right-offset="3" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll h-full overflow-auto">
                                <table class="backup-records-table w-full table-fixed border-collapse text-left">
                                    <colgroup><col style="width: 35%"><col style="width: 11%"><col style="width: 29%"><col style="width: 25%"></colgroup>
                                    <thead class="sticky top-0 z-10 panel-header shadow-[inset_0_-1px_0_#d4d4d8]"><tr class="whitespace-nowrap text-center"><th class="border-r panel-border p-[20px] "><span class="flex items-center gap-[15px] text-left"><input type="checkbox" wire:click="toggleAllAvailable" wire:loading.attr="disabled" wire:target="toggleAllAvailable" @checked($allAvailableSelected) @disabled($headerSelectableCount === 0 || $exportIsActive || $status === 'partial') aria-label="Seleccionar todas las empresas disponibles" class="h-4 w-4 shrink-0 rounded border panel-border panel-surface panel-accent focus:ring-0 focus:ring-offset-0 disabled:cursor-not-allowed  "><span class="inline-flex items-baseline gap-[6px]">Empresa <span class="" title="{{ $selectedExportCount }} {{ $selectedExportCount === 1 ? 'empresa seleccionada' : 'empresas seleccionadas' }}">({{ $selectedExportCount }})</span></span></span></th><th class="border-r panel-border p-[20px] ">Hora</th><th class="border-r panel-border p-[20px] ">Respaldo en memoria</th><th class="p-[20px] ">Archivos del servidor</th></tr></thead>
                                    @forelse ($records as $record)
                                        @php
                                            $recordState = $recordAvailability[$record['id']] ?? 'incomplete';
                                            $recordDestinations = $completedDestinations[$record['id']] ?? [];
                                            $latestDestination = $recordDestinations[0] ?? null;
                                        @endphp
                                        <tbody wire:key="record-{{ $record['id'] }}" x-data="{ open: false }" class="backup-record-group {{ $loop->odd ? 'backup-record-gray' : 'backup-record-white' }} border-b panel-border">
                                            <tr @click="$refs.rowCheckbox.click()" style="cursor: {{ in_array($recordState, ['available', 'outdated', 'backed_up', 'destination_missing', 'folder_missing'], true) && ! $exportIsActive ? 'pointer' : 'default' }};">
                                                <td class="min-w-0 border-r panel-border p-[20px]"><div class="flex min-w-0 items-center gap-[15px]"><span class="shrink-0" @click.stop><input x-ref="rowCheckbox" type="checkbox" wire:model.live="selectedRecords" value="{{ $record['id'] }}" @disabled(!in_array($recordState, ['available', 'outdated', 'backed_up', 'destination_missing', 'folder_missing'], true) || $exportIsActive) aria-label="Seleccionar {{ $record['company'] }}" class="h-4 w-4 rounded border panel-border panel-surface panel-accent focus:ring-0 focus:ring-offset-0 disabled:cursor-not-allowed   disabled:opacity-100"></span><span class="min-w-0"><span class="block truncate leading-none" title="{{ $record['company'] }}">{{ $record['company'] }}</span><span class="mt-[15px] block truncate " title="{{ $record['stem'] }}">{{ $record['company_folder'] ?? 'Carpeta no identificada' }}</span></span></div></td>
                                                <td class="border-r panel-border p-[20px] text-center tabular-nums ">{{ $record['time'] }}</td>
                                                <td class="min-w-0 border-r panel-border p-[20px] text-center">
                                                    @if($latestDestination)
                                                        <div class="mx-auto min-w-0" title="{{ $latestDestination['label'] }} · {{ $latestDestination['path'] }}"><p class="truncate leading-none">{{ $latestDestination['label'] }}@if(count($recordDestinations) > 1) · +{{ count($recordDestinations) - 1 }} {{ count($recordDestinations) === 2 ? 'destino' : 'destinos' }}@endif</p><p class="mt-[15px] truncate leading-none">{{ $latestDestination['path'] }}</p></div>
                                                    @elseif($recordState === 'available')<span class="panel-accent">Disponible</span>
                                                    @elseif($recordState === 'backed_up')<span class="panel-accent">Ya respaldada</span>
                                                    @elseif($recordState === 'copying')<span class="">Copiando</span>
                                                    @elseif($recordState === 'outdated')<span class="">Requiere actualización</span>
                                                    @elseif($recordState === 'older')<span class="" title="Solo se permite el respaldo completo más reciente de la empresa para este día.">Hay uno más reciente</span>
                                                    @elseif($recordState === 'destination_missing')<span class="">Conecta la memoria</span>
                                                    @elseif($recordState === 'folder_missing')<span class="">Selecciona carpeta</span>
                                                    @else<span class="text-[color:var(--panel-danger)]">Incompleta</span>@endif
                                                </td>
                                                <td class="p-[20px]"><div class="flex items-center justify-between gap-[10px]"><span class="truncate">@if($record['database'] && $record['archive'])<span class="panel-accent">BAK e INDEX</span>@elseif(!$record['database'] && !$record['archive'])<span class="text-[color:var(--panel-danger)]">Faltan BAK e INDEX</span>@elseif(!$record['database'])<span class="text-[color:var(--panel-danger)]">Falta BAK</span>@else<span class="text-[color:var(--panel-danger)]">Falta INDEX</span>@endif</span><button type="button" @click.stop="open = !open" class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-xl transition hover:bg-[color:var(--panel-hover)] hover:opacity-70" :aria-expanded="open"><svg class="h-5 w-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg></button></div></td>
                                            </tr>
                                            <tr x-show="open" x-cloak>
                                                <td colspan="4" class="backup-record-detail bg-inherit p-0">
                                                    <div class="grid border-t panel-border md:grid-cols-2 md:divide-x md:divide-[color:var(--panel-border)]">
                                                        @if($record['database'])
                                                            <a @click.stop href="{{ route('activity-backups.server.download', ['kind' => 'database', 'token' => $record['database']['token']]) }}" class="flex min-h-[78px] min-w-0 items-center justify-between gap-[15px] panel-surface p-[20px] transition hover:bg-[color:var(--panel-hover)]" title="Descargar {{ $record['database']['name'] }}"><span class="flex min-w-0 items-center gap-[12px]"><span class="grid h-10 w-10 shrink-0 place-items-center border panel-border panel-surface "><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM14 2v5h5"/></svg></span><span class="min-w-0"><span class="block leading-none">Base de datos · BAK</span><span class="mt-[15px] block truncate ">{{ $record['database']['name'] }} · {{ $formatBytes($record['database']['size']) }}</span></span></span><svg class="h-5 w-5 shrink-0 " fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></a>
                                                        @else
                                                            <div class="flex min-h-[78px] items-center gap-[12px] panel-surface p-[20px] "><span class="grid h-10 w-10 shrink-0 place-items-center border panel-border panel-surface"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM9 13h6"/></svg></span><span><span class="block leading-none">Base de datos · BAK</span><span>Archivo no encontrado</span></span></div>
                                                        @endif
                                                        @if($record['archive'])
                                                            <a @click.stop href="{{ route('activity-backups.server.download', ['kind' => 'archive', 'token' => $record['archive']['token']]) }}" class="flex min-h-[78px] min-w-0 items-center justify-between gap-[15px] panel-surface p-[20px] transition hover:bg-[color:var(--panel-hover)]" title="Descargar {{ $record['archive']['name'] }}"><span class="flex min-w-0 items-center gap-[12px]"><span class="grid h-10 w-10 shrink-0 place-items-center border panel-border panel-surface "><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM14 2v5h5M10 11h4m-4 4h4"/></svg></span><span class="min-w-0"><span class="block leading-none">Documento digital · {{ strtoupper($record['archive']['extension']) }}</span><span class="mt-[15px] block truncate ">{{ $record['archive']['name'] }} · {{ $formatBytes($record['archive']['size']) }}</span></span></span><svg class="h-5 w-5 shrink-0 " fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></a>
                                                        @else
                                                            <div class="flex min-h-[78px] items-center gap-[12px] panel-surface p-[20px] "><span class="grid h-10 w-10 shrink-0 place-items-center border panel-border panel-surface"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM9 13h6"/></svg></span><span><span class="block leading-none">Documento digital</span><span>ZIP o RAR no encontrado</span></span></div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    @empty
                                        <tbody><tr class="h-[474px]"><td colspan="4" class="px-[20px] text-center align-middle"><svg class="mx-auto h-10 w-10 panel-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 7h7l2 2h9v11H3V7Z"/></svg>@if($status !== 'all' && count($catalog['records']) > 0)<p class="mt-[12px] leading-none">No hay respaldos en este estado</p><p class="mt-[15px] leading-none">La fecha tiene {{ count($catalog['records']) }} {{ count($catalog['records']) === 1 ? 'respaldo' : 'respaldos' }} en otros estados.</p><button type="button" wire:click="$set('status', 'all')" class="mt-[15px] panel-accent transition hover:opacity-70">Mostrar todos los respaldos</button>@else<p class="mt-[12px] leading-none">No hay resultados</p><p class="mt-[15px] leading-none">Cambia la fecha o el departamento.</p>@endif</td></tr></tbody>
                                    @endforelse
                                </table>
                                </div>
                                <span wire:loading.remove wire:target="selectDate" x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                <span wire:loading.remove wire:target="selectDate" x-show="horizontalVisible" x-cloak @pointerdown="startHorizontalDrag($event)" class="backup-catalog-scroll-thumb-x" :style="`width:${width}px; transform:translateX(${left}px)`" aria-label="Desplazar tabla horizontalmente"></span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="border-b panel-border panel-header p-[20px]">
                        <h2 class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2M4.9 4.9A10 10 0 1 1 2 12m0 0V6m0 6h6"/></svg><span>Historial de movimientos</span></h2>
                        <p class="mt-[15px] leading-none">Respaldos, reemplazos, cancelaciones, errores y eliminaciones registrados por el sistema.</p>
                    </div>
                    <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region backup-catalog-scroll-vertical-visible relative h-[620px]">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll backup-history-scroll h-full overflow-auto">
                            <table class="w-full min-w-[1050px] table-fixed border-collapse text-left">
                                <colgroup><col class="w-[170px]"><col class="w-[190px]"><col class="w-[290px]"><col class="w-[230px]"><col class="w-[190px]"></colgroup>
                                <thead class="sticky top-0 z-10 panel-header shadow-[inset_0_-1px_0_#d4d4d8]"><tr><th class="border-r panel-border p-[20px] ">Fecha y hora</th><th class="border-r panel-border p-[20px] text-center">Movimiento</th><th class="border-r panel-border p-[20px] ">Empresas</th><th class="border-r panel-border p-[20px] ">Destino</th><th class="p-[20px] ">Usuario</th></tr></thead>
                                <tbody class="divide-y divide-[color:var(--panel-border)]">
                                    @forelse($movementHistory as $movement)
                                        @php
                                            $movementLabel = match($movement['type']) {
                                                'backup' => 'Respaldo creado',
                                                'replacement' => 'Respaldo reemplazado',
                                                'deleted' => 'Respaldo eliminado',
                                                'cancelled' => 'Respaldo cancelado',
                                                'failed' => 'Respaldo fallido',
                                                'queued' => 'En espera',
                                                'copying' => 'Copia en proceso',
                                                'cancelling' => 'Cancelando',
                                                default => 'Movimiento registrado',
                                            };
                                            $movementColor = match($movement['type']) {
                                                'deleted', 'failed' => 'text-[color:var(--panel-danger)]',
                                                'cancelled', 'cancelling' => '',
                                                'copying', 'queued' => '',
                                                'replacement' => 'panel-accent',
                                                default => 'panel-accent',
                                            };
                                            $companyNames = implode(', ', $movement['companies'] ?? []);
                                            $destinationText = trim(($movement['destination_label'] ?? 'Memoria externa').' · '.str_replace('/', '\\', $movement['destination_folder'] ?? ''), ' ·');
                                        @endphp
                                        <tr wire:key="history-{{ $movement['id'] }}" class="h-[110px] {{ $loop->odd ? 'panel-subtle' : 'panel-surface' }}">
                                            @php($movementAt = \Carbon\CarbonImmutable::parse($movement['occurred_at'])->setTimezone(config('backup-storage.display_timezone'))->locale('es'))
                                            <td class="border-r panel-border p-[20px]"><p class="leading-none">{{ $movementAt->translatedFormat('d M Y') }}</p><p class="mt-[15px] leading-none">{{ $movementAt->format('H:i:s') }}</p></td>
                                            <td class="border-r panel-border p-[20px] text-center"><p class="{{ $movementColor }} leading-none">{{ $movementLabel }}</p><p class="mt-[15px] leading-none tabular-nums">{{ $movement['item_count'] }} {{ $movement['item_count'] === 1 ? 'empresa' : 'empresas' }}</p>@if($movement['error'])<p class="mt-[15px] truncate text-[color:var(--panel-danger)] leading-none" title="{{ $movement['error'] }}">{{ $movement['error'] }}</p>@endif</td>
                                            <td class="border-r panel-border p-[20px]"><p class="truncate leading-none" title="{{ $companyNames }}">{{ $companyNames !== '' ? $companyNames : 'Sin empresas registradas' }}</p>@if($movement['backup_date'])<p class="mt-[15px] leading-none">Respaldo del {{ \Carbon\CarbonImmutable::parse($movement['backup_date'])->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>@endif</td>
                                            <td class="border-r panel-border p-[20px]"><p class="truncate leading-none" title="{{ $destinationText }}">{{ $movement['destination_label'] ?? 'Memoria externa' }}</p><p class="mt-[15px] truncate leading-none" title="{{ $movement['destination_folder'] }}">{{ str_replace('/', '\\', $movement['destination_folder'] ?? '') ?: 'Carpeta no registrada' }}</p></td>
                                            <td class="p-[20px]"><p class="truncate leading-none" title="{{ $movement['user_name'] }}">{{ $movement['user_name'] }}</p></td>
                                        </tr>
                                    @empty
                                        <tr class="h-[550px]"><td colspan="5" class="px-[20px] text-center align-middle"><svg class="mx-auto h-10 w-10 panel-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2M4.9 4.9A10 10 0 1 1 2 12m0 0V6m0 6h6"/></svg><p class="mt-[12px] leading-none">Todavía no hay movimientos registrados</p><p class="mt-[15px] leading-none">Los próximos respaldos y eliminaciones aparecerán aquí.</p></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                @endif

                <footer class="relative z-10 -mt-px flex flex-col gap-[15px] border-t panel-border panel-subtle p-[20px] md:flex-row md:items-center md:justify-between"><span>Origen de solo lectura: {{ $catalog['root'] }}</span>@if($catalog['scanned_at'])<span>Última lectura: {{ \Carbon\CarbonImmutable::parse($catalog['scanned_at'])->translatedFormat('d M Y · H:i:s') }}</span>@endif</footer>
            </section>
            @if ($activeView === 'backups')
                {{-- Explorador de la memoria (solo lectura): se recorre como el Explorador de Windows para comprobar que los
                     respaldos realmente se crearon. Todo ocurre en el navegador (wire:ignore): cada carpeta se pide una sola vez. --}}
                @php($defaultTree = collect($treeDestinations)->first(fn ($destination) => in_array($activeTreeKey, $destination['keys'] ?? [$destination['key']], true)) ?? ($treeDestinations[0] ?? null))
                @if ($defaultTree)
                <section wire:ignore
                    x-data="memoryExplorer(@js($defaultTree['key']), @js(config('backup-storage.display_timezone', 'America/Mexico_City')), @js(now(config('backup-storage.display_timezone', 'America/Mexico_City'))->format('d-m-Y')))"
                    class="backup-explorer-card flex flex-col overflow-hidden rounded-xl border panel-border panel-surface" aria-label="Estructura de la memoria">
                    <header class="panel-header panel-border border-b p-[20px]">
                        <p class="panel-accent leading-none">Estructura de la memoria</p>
                        <p class="mt-[15px] leading-none">Recorre la memoria como en el Explorador de Windows: haz clic en una carpeta para entrar.</p>
                    </header>

                    {{-- Elegir memoria y actualizar --}}
                    <div class="panel-border flex flex-wrap items-center justify-between gap-[20px] border-b p-[20px]">
                        <div class="flex flex-wrap items-center gap-[20px]" role="group" aria-label="Memorias de respaldo">
                            @foreach ($treeDestinations as $treeDestination)
                                <button type="button" @click="select(@js($treeDestination['key']))" class="backup-memory-button" :aria-pressed="key === @js($treeDestination['key']) ? 'true' : 'false'" title="{{ $treeDestination['root'] }}">
                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><ellipse cx="12" cy="5" rx="7" ry="3" /><path d="M5 5v7c0 1.66 3.13 3 7 3s7-1.34 7-3V5M5 12v7c0 1.66 3.13 3 7 3s7-1.34 7-3v-7" /></svg>
                                    <span>{{ $treeDestination['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <button type="button" @click="refresh()" :disabled="refreshing" class="backup-refresh-button">
                            <svg :class="refreshing && 'animate-spin'" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0-2.34 5.66M20 4v7h-7" /></svg>
                            <span x-text="refreshing ? 'Leyendo memoria…' : 'Actualizar estructura'">Actualizar estructura</span>
                        </button>
                    </div>

                    <div x-show="loading && ! folder" class="p-[20px]"><p class="leading-[30px]">Leyendo la memoria…</p></div>
                    <div x-show="! loading && ! connected" x-cloak class="p-[20px]">
                        <p class="text-[color:var(--panel-danger)] leading-none">No se pudo leer la memoria.</p>
                        <p class="mt-[15px] leading-[30px]">Conecta la memoria, comprueba que esté disponible y pulsa «Actualizar estructura».</p>
                    </div>
                    <div x-show="failed" x-cloak class="px-[20px] pt-[20px]"><p class="text-[color:var(--panel-danger)] leading-none">No se pudo abrir la carpeta. Intenta de nuevo.</p></div>

                    <template x-if="connected && info && folder">
                        <div class="flex min-h-0 flex-1 flex-col">
                            <div class="grid [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))] border-b panel-border">
                                <div class="panel-border border-r p-[20px]"><p class="panel-accent leading-none">Memoria</p><p class="mt-[15px] truncate leading-none" x-text="info.label"></p></div>
                                <div class="panel-border border-r p-[20px]"><p class="panel-accent leading-none">Espacio libre</p><p class="mt-[15px] truncate leading-none tabular-nums" x-html="digits(info.free_bytes !== null ? bytes(info.free_bytes) : '—')"></p></div>
                                <div class="panel-border border-r p-[20px]"><p class="panel-accent leading-none">Carpetas de fecha</p><p class="mt-[15px] truncate leading-none tabular-nums" x-html="digits(number(info.stats.dates))"></p></div>
                                <div class="panel-border border-r p-[20px]"><p class="panel-accent leading-none">Archivos</p><p class="mt-[15px] truncate leading-none tabular-nums" x-html="digits(number(info.stats.files))"></p></div>
                                <div class="p-[20px]"><p class="panel-accent leading-none">Tamaño de los respaldos</p><p class="mt-[15px] truncate leading-none tabular-nums" x-html="digits(bytes(info.stats.bytes))"></p></div>
                            </div>

                            <div class="backup-explorer flex min-h-0 flex-1 flex-col gap-[20px] p-[20px]">
                                {{-- Barra como la del Explorador: Atrás, Subir y la ruta (cada parte lleva a esa carpeta) --}}
                                <div class="flex items-center gap-[20px]">
                                    <button type="button" @click="back()" class="explorer-tool" :disabled="! history.length" title="Atrás" aria-label="Atrás">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m0 0 6-6m-6 6 6 6" /></svg>
                                    </button>
                                    <button type="button" @click="up()" class="explorer-tool" :disabled="path === ''" title="Subir un nivel" aria-label="Subir un nivel">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6" /></svg>
                                    </button>
                                    <nav class="explorer-address" aria-label="Ruta de la carpeta" :title="info.root + (path ? '\\' + path : '')">
                                        <template x-for="(crumb, index) in crumbs" :key="crumb.path">
                                            <span class="flex flex-none items-center gap-[10px]">
                                                <span x-show="index > 0" class="opacity-60" aria-hidden="true">›</span>
                                                <button type="button" @click="index < crumbs.length - 1 && open(crumb.path)" class="explorer-crumb" :class="index === crumbs.length - 1 && 'panel-accent'" x-text="crumb.name"></button>
                                            </span>
                                        </template>
                                        <span x-show="loading" class="ml-auto flex-none opacity-60">Abriendo…</span>
                                    </nav>
                                </div>

                                <div class="explorer-cols">
                                    <span>Nombre</span>
                                    <span class="explorer-num">Archivos</span>
                                    <span class="explorer-num explorer-hide-md">Tamaño</span>
                                    <span class="explorer-num explorer-hide-md">Modificado</span>
                                </div>

                                {{-- Ocupa el alto que queda en la pantalla, con su propio scroll pegado a la derecha --}}
                                <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region explorer-scroll-region relative -mr-[20px] min-h-0 flex-1">
                                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll explorer-scroll h-full max-h-full overflow-y-auto pr-[20px]">
                                        <p x-show="! folder.subfolders.length && ! folder.files.length" class="px-[20px] leading-[30px]">Esta carpeta está vacía.</p>
                                        <div class="explorer-list">
                                            <template x-for="sub in folder.subfolders" :key="'d|' + sub.path">
                                                <button type="button" @click="open(sub.path)" @mouseenter="prefetch(sub.path)" class="explorer-row" :title="'Abrir ' + sub.name">
                                                    <span class="flex min-w-0 items-center gap-[10px]">
                                                        <svg class="h-[18px] w-[18px] shrink-0" :class="isToday(sub.name) && 'panel-accent'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" /></svg>
                                                        <span class="truncate" :class="isHighlighted(sub.name) && 'panel-accent'" x-text="sub.name"></span>
                                                        <span x-show="isToday(sub.name)" class="explorer-tag">Hoy</span>
                                                    </span>
                                                    <span class="explorer-num" x-html="digits(files(sub.files_total))"></span>
                                                    <span class="explorer-size explorer-hide-md" x-data="{ get z() { return sizeParts(sub.bytes_total) } }"><span x-html="digits(z.num)"></span><span x-text="z.unit"></span></span>
                                                    <span class="explorer-date explorer-hide-md" x-data="{ get d() { return dateParts(sub.modified) } }"><span x-html="digits(d.day)"></span><span x-text="d.month"></span><span x-html="digits(d.year)"></span><span class="explorer-date-sep" x-show="d.time">·</span><span x-html="digits(d.time)"></span></span>
                                                </button>
                                            </template>
                                            <template x-for="file in folder.files" :key="'f|' + file.name">
                                                <div class="explorer-row">
                                                    <span class="flex min-w-0 items-center gap-[10px]">
                                                        <span class="explorer-ext" x-text="(file.extension || 'archivo').toUpperCase()"></span>
                                                        <span class="truncate" :title="file.name" x-text="file.name"></span>
                                                    </span>
                                                    <span class="explorer-num"></span>
                                                    <span class="explorer-size explorer-hide-md" x-data="{ get z() { return sizeParts(file.size) } }"><span x-html="digits(z.num)"></span><span x-text="z.unit"></span></span>
                                                    <span class="explorer-date explorer-hide-md" x-data="{ get d() { return dateParts(file.mtime) } }"><span x-html="digits(d.day)"></span><span x-text="d.month"></span><span x-html="digits(d.year)"></span><span class="explorer-date-sep" x-show="d.time">·</span><span x-html="digits(d.time)"></span></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </section>
                @endif
            @endif
        @endif
    </main>
</div>
