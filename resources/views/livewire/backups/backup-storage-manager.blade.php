@php
    $formatBytes = static function (int $bytes): string {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    };
    $selectedDateLabel = \Carbon\CarbonImmutable::parse($selectedDate)->isToday()
        ? 'Hoy'
        : \Carbon\CarbonImmutable::parse($selectedDate)->translatedFormat('d M Y');
    $exportIsActive = in_array($exportStatus['status'] ?? null, ['queued', 'copying'], true);
    $exportPercent = ($exportStatus['total_bytes'] ?? 0) > 0
        ? min(100, (int) round(($exportStatus['copied_bytes'] ?? 0) * 100 / $exportStatus['total_bytes']))
        : 0;
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
    wire:poll.60s>
    <span class="hidden" wire:poll.2s="refreshSharedExportStatus" aria-hidden="true"></span>
    <style>
        .backup-catalog-scroll { scrollbar-width: none; overscroll-behavior: contain; }
        .backup-catalog-scroll::-webkit-scrollbar { width: 0; height: 0; }
        .backup-catalog-scroll-thumb { position: absolute; right: 2px; top: 0; z-index: 20; width: 3px; min-height: 32px; border-radius: 9999px; background: #1A3A6B; opacity: 0; pointer-events: none; transition: opacity 140ms ease; }
        .backup-catalog-scroll-region:hover > .backup-catalog-scroll-thumb { opacity: 1; }
        html.module-dark-theme .backup-catalog-scroll-thumb { background: #a9a4cf; }
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
                    <p class="mt-[4px] text-zinc-500">Consulta de solo lectura del servidor.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-[12px]">
                <button type="button" wire:click="verifySynchronization" wire:loading.attr="disabled" wire:target="verifySynchronization" class="inline-flex items-center gap-[8px] bg-transparent p-0 font-medium text-black transition hover:text-zinc-600 disabled:opacity-50">
                    <svg wire:loading.class="animate-spin" wire:target="verifySynchronization" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8 8 0 0 0-15.5-2M4 4v5h5m-5 4a8 8 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                    <span wire:loading.remove wire:target="verifySynchronization">Actualizar y comprobar</span>
                    <span wire:loading wire:target="verifySynchronization">Actualizando…</span>
                </button>
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
            @if($syncResult)
                <section class="rounded-xl border {{ ($syncResult['error'] ?? null) ? 'border-red-200 bg-red-50' : 'border-zinc-200 bg-white' }} p-[15px]">
                    @if($syncResult['error'] ?? null)
                        <p class="text-[13px] text-red-700"><strong>No se pudo comprobar:</strong> {{ $syncResult['error'] }}</p>
                    @else
                        <div class="flex flex-col gap-[10px] lg:flex-row lg:items-center lg:justify-between">
                            <div><h2 class="font-medium text-black">Resultado de sincronización</h2><p class="mt-[2px] text-[11px] text-zinc-400">Parejas BAK + ZIP/RAR y tamaños comprobados {{ \Carbon\CarbonImmutable::parse($syncResult['checked_at'])->translatedFormat('d M Y · H:i:s') }}</p></div>
                            <div class="flex flex-wrap gap-[8px] text-[12px]">
                                <span class="rounded-full bg-emerald-50 px-[10px] py-[6px] text-emerald-700">{{ $syncResult['server_complete'] }} completas en servidor</span>
                                <span class="rounded-full bg-red-50 px-[10px] py-[6px] text-red-700">{{ $syncResult['server_incomplete'] }} incompletas</span>
                                <span class="rounded-full bg-blue-50 px-[10px] py-[6px] text-[#1A3A6B]">{{ $syncResult['synced'] }} sincronizadas</span>
                                <span class="rounded-full bg-amber-50 px-[10px] py-[6px] text-amber-700">{{ $syncResult['pending'] }} pendientes</span>
                                @if($syncResult['outdated'] > 0)<span class="rounded-full bg-orange-50 px-[10px] py-[6px] text-orange-700">{{ $syncResult['outdated'] }} por actualizar</span>@endif
                                @if($syncResult['copying'] > 0)<span class="rounded-full bg-violet-50 px-[10px] py-[6px] text-violet-700">{{ $syncResult['copying'] }} copiándose</span>@endif
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none">
                <header class="flex flex-col gap-[15px] border-b border-zinc-200 p-[20px] xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h2 class="font-semibold text-black">Generar respaldo de la empresa</h2>
                        <p class="mt-[3px] text-[13px] text-zinc-500">Configura y genera los respaldos fácilmente.</p>
                    </div>
                    <label class="relative block w-full xl:w-[400px]">
                        <svg class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar empresa o archivo…" class="h-[46px] w-full rounded-xl border-zinc-200 bg-white pl-[44px] pr-[15px] text-[14px] shadow-none focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/10">
                    </label>
                </header>

                @if ($activeView === 'backups')
                    <div class="grid min-h-[520px] xl:h-[650px] xl:grid-cols-[260px_minmax(0,1fr)]">
                        <aside class="backup-soft-panel flex min-h-0 flex-col border-b border-zinc-200 bg-zinc-50 p-[15px] xl:border-b-0 xl:border-r">
                            <div class="flex items-center justify-between px-[5px] py-[8px]"><h2 class="font-semibold text-black">Días con respaldos</h2><span class="text-[12px] text-zinc-400">{{ count($catalog['dates']) }}</span></div>
                            <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region relative mt-[8px] min-h-0 flex-1">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll h-full space-y-[5px] overflow-y-auto pr-[5px]">
                                @forelse ($catalog['dates'] as $day)
                                    @php $dayDate = \Carbon\CarbonImmutable::parse($day['date']); @endphp
                                    <button type="button" wire:key="backup-day-{{ $day['date'] }}" wire:click="selectDate('{{ $day['date'] }}')" wire:loading.attr="disabled" wire:target="selectDate" class="flex w-full items-center justify-between rounded-xl px-[12px] py-[11px] text-left transition disabled:cursor-wait {{ $selectedDate === $day['date'] ? 'bg-black text-white' : 'text-zinc-600 hover:bg-white' }}">
                                        <span><span class="block font-medium {{ $selectedDate === $day['date'] ? 'text-white' : 'text-black' }}">{{ $dayDate->isToday() ? 'Hoy' : $dayDate->translatedFormat('d M Y') }}</span><span class="mt-[2px] block text-[11px] {{ $selectedDate === $day['date'] ? 'text-white/65' : 'text-zinc-400' }}">{{ $day['complete'] }} completos · {{ $day['partial'] }} parciales</span></span>
                                        <span class="rounded-lg px-[8px] py-[4px] text-[11px] tabular-nums {{ $selectedDate === $day['date'] ? 'bg-white/10' : 'bg-zinc-100' }}">{{ $day['total'] }}</span>
                                    </button>
                                @empty
                                    <p class="p-[12px] text-[13px] text-zinc-400">No se encontraron respaldos con fecha válida.</p>
                                @endforelse
                                </div>
                                <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>
                        </aside>

                        <div class="relative flex min-h-0 min-w-0 flex-col">
                            <div class="flex flex-col gap-[12px] border-b border-zinc-200 px-[20px] py-[15px]">
                                <div><h2 class="font-semibold text-black">{{ $selectedDateLabel }}</h2><p class="mt-[3px] text-[12px] text-zinc-400">{{ $records->count() }} respaldo{{ $records->count() === 1 ? '' : 's' }} visible{{ $records->count() === 1 ? '' : 's' }}</p></div>
                                <div class="grid w-full grid-cols-2 border-b border-zinc-200 sm:grid-cols-3 md:grid-cols-5" aria-label="Filtrar respaldos por estado">
                                    <button type="button" wire:click="$set('status', 'all')" class="flex min-h-[44px] items-center justify-center gap-[8px] border-b-2 px-[10px] transition {{ $status === 'all' ? 'border-black font-medium text-black' : 'border-transparent text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg><span>Todos</span></button>
                                    <button type="button" wire:click="$set('status', 'complete')" class="flex min-h-[44px] items-center justify-center gap-[8px] border-b-2 px-[10px] transition {{ $status === 'complete' ? 'border-black font-medium text-black' : 'border-transparent text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9"/></svg><span>Completos</span></button>
                                    <button type="button" wire:click="$set('status', 'available')" class="flex min-h-[44px] items-center justify-center gap-[8px] border-b-2 px-[10px] transition {{ $status === 'available' ? 'border-black font-medium text-black' : 'border-transparent text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg><span>Por respaldar</span></button>
                                    <button type="button" wire:click="$set('status', 'backed_up')" class="flex min-h-[44px] items-center justify-center gap-[8px] border-b-2 px-[10px] transition {{ $status === 'backed_up' ? 'border-black font-medium text-black' : 'border-transparent text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 7h14v12H5zM8 4h8v3"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 13 2 2 4-5"/></svg><span>Ya respaldadas</span></button>
                                    <button type="button" wire:click="$set('status', 'partial')" class="flex min-h-[44px] items-center justify-center gap-[8px] border-b-2 px-[10px] transition {{ $status === 'partial' ? 'border-black font-medium text-black' : 'border-transparent text-zinc-500 hover:text-black' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v6m0 4h.01"/></svg><span>Incompletos</span></button>
                                </div>
                            </div>
                            <div class="border-b border-zinc-200 bg-zinc-50/70 p-[15px]">
                                <div class="grid gap-[10px] lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.75fr)]">
                                    <section class="rounded-xl border border-zinc-200 bg-white p-[13px]" aria-labelledby="backup-selection-title">
                                        <div class="flex flex-wrap items-center justify-between gap-[10px]">
                                            <div class="flex items-center gap-[10px]">
                                                <span class="grid h-7 w-7 place-items-center rounded-full bg-black text-[12px] font-semibold text-white">1</span>
                                                <div><h3 id="backup-selection-title" class="font-medium text-black">Selecciona las empresas</h3><p class="text-[11px] text-zinc-400">Solo están habilitadas las que se pueden respaldar.</p></div>
                                            </div>
                                            <div class="flex items-center gap-[10px] text-[12px]">
                                                <button type="button" wire:click="selectAllComplete" @disabled($availableExportCount === 0 || $exportIsActive) class="font-medium text-black hover:underline disabled:cursor-not-allowed disabled:text-zinc-300">Seleccionar todas</button>
                                                @if($selectedExportCount > 0)<button type="button" wire:click="clearSelection" class="text-zinc-500 hover:text-black">Limpiar</button>@endif
                                            </div>
                                        </div>
                                        <div class="mt-[10px] flex flex-wrap gap-[8px] text-[11px]">
                                            <span class="rounded-full bg-emerald-50 px-[9px] py-[5px] text-emerald-700">{{ $availableExportCount }} disponibles</span>
                                            <span class="rounded-full bg-blue-50 px-[9px] py-[5px] text-[#1A3A6B]">{{ $backedUpExportCount }} ya respaldadas</span>
                                            @if($outdatedExportCount > 0)<span class="rounded-full bg-orange-50 px-[9px] py-[5px] text-orange-700">{{ $outdatedExportCount }} por actualizar</span>@endif
                                            <span class="rounded-full bg-zinc-100 px-[9px] py-[5px] text-zinc-600">{{ $selectedExportCount }} seleccionadas · {{ $formatBytes($selectedExportBytes) }}</span>
                                        </div>
                                    </section>

                                    <section class="rounded-xl border border-zinc-200 bg-white p-[13px]" aria-labelledby="backup-action-title">
                                        <div class="flex items-center gap-[10px]"><span class="grid h-7 w-7 place-items-center rounded-full bg-black text-[12px] font-semibold text-white">2</span><div class="min-w-0"><h3 id="backup-action-title" class="font-medium text-black">Elige dónde guardar</h3><p class="truncate text-[11px] {{ $exportDestination['connected'] ? 'text-emerald-600' : 'text-amber-600' }}" title="{{ $exportDestination['root'] ?? '' }}">@if(!$exportDestination['configured'])No se encontró esta unidad en el equipo que ejecuta PHP.@elseif(!$exportDestination['connected'])PHP no puede abrir esta unidad.@else {{ $exportDestination['label'] }} disponible; selecciona una carpeta @endif</p></div></div>
                                        <div class="mt-[10px] grid gap-[8px] sm:grid-cols-2">
                                            <label class="block"><span class="mb-[4px] block text-[10px] font-medium uppercase tracking-wide text-zinc-400">Memoria</span><select wire:model.live="destinationKey" @disabled($exportIsActive || $exportDestinations === []) class="h-[40px] w-full rounded-xl border-zinc-200 bg-white px-[12px] text-[13px] text-black focus:border-[#1A3A6B] focus:ring-0 disabled:bg-zinc-100 disabled:text-zinc-400">@forelse($exportDestinations as $destination)<option value="{{ $destination['key'] }}">{{ $destination['label'] }}{{ $destination['connected'] ? '' : ' — no disponible' }}</option>@empty<option value="">No hay memorias disponibles</option>@endforelse</select></label>
                                            <label class="block"><span class="mb-[4px] block text-[10px] font-medium uppercase tracking-wide text-zinc-400">Carpeta</span><select wire:model.live="destinationFolder" @disabled(!$exportDestination['connected'] || $exportIsActive) class="h-[40px] w-full rounded-xl border-zinc-200 bg-white px-[12px] text-[13px] text-black focus:border-[#1A3A6B] focus:ring-0 disabled:bg-zinc-100 disabled:text-zinc-400"><option value="">Selecciona una carpeta…</option>@foreach($destinationFolders as $folder)<option value="{{ $folder['name'] }}" @disabled(!$folder['writable'])>{{ $folder['name'] }}{{ $folder['writable'] ? '' : ' — solo lectura' }}</option>@endforeach</select></label>
                                        </div>
                                        @if($destinationFolder !== '')
                                            <p class="mt-[7px] text-[10px] leading-relaxed text-zinc-500">Se guardará en <strong class="text-black">{{ $destinationFolder }}\{{ \Carbon\CarbonImmutable::parse($selectedDate)->format('d-m-Y') }}\EMPRESA\BAK e INDEX</strong>. Si la fecha o empresa ya existe, se reutiliza sin duplicar archivos.</p>
                                        @endif
                                        <button type="button" wire:click="startExport" wire:loading.attr="disabled" wire:target="startExport" @disabled(!$exportDestination['connected'] || $destinationFolder === '' || $selectedExportCount === 0 || $exportIsActive) class="mt-[10px] inline-flex h-[40px] w-full items-center justify-center gap-[9px] rounded-xl bg-black px-[18px] font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-35">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h6l2 2h8v10H4V7Zm8-4v10m0 0 3-3m-3 3-3-3"/></svg>
                                            <span wire:loading.remove wire:target="startExport">Respaldar seleccionadas</span>
                                            <span wire:loading wire:target="startExport">Validando…</span>
                                        </button>
                                    </section>
                                </div>

                                @if($exportMessage)
                                    <p class="mt-[10px] rounded-lg bg-red-50 px-[12px] py-[9px] text-[12px] text-red-700">{{ $exportMessage }}</p>
                                @endif

                                @if($exportStatus)
                                    <div class="mt-[12px] rounded-xl border border-zinc-200 bg-white p-[12px]">
                                        <div class="flex items-center justify-between gap-[15px] text-[12px]">
                                            <span class="min-w-0 truncate font-medium text-black">
                                                @if(($exportStatus['status'] ?? '') === 'queued') En espera para copiar
                                                @elseif(($exportStatus['status'] ?? '') === 'copying') Copiando {{ $exportStatus['current_company'] ?? 'archivos' }}
                                                @elseif(($exportStatus['status'] ?? '') === 'completed') Copia terminada y verificada
                                                @else Copia interrumpida
                                                @endif
                                            </span>
                                            <span class="shrink-0 tabular-nums text-zinc-500">{{ $exportPercent }}% · {{ $formatBytes((int) ($exportStatus['copied_bytes'] ?? 0)) }} / {{ $formatBytes((int) ($exportStatus['total_bytes'] ?? 0)) }}</span>
                                        </div>
                                        <div class="mt-[9px] h-[5px] overflow-hidden rounded-full bg-zinc-100"><span class="block h-full rounded-full {{ ($exportStatus['status'] ?? '') === 'failed' ? 'bg-red-500' : 'bg-[#1A3A6B]' }} transition-all" style="width: {{ $exportPercent }}%"></span></div>
                                        @if($exportIsActive)
                                            <p class="mt-[8px] truncate text-[11px] text-zinc-500" title="{{ $exportStatus['destination_label'] ?? 'Memoria' }} · {{ $exportStatus['destination_folder'] ?? 'carpeta seleccionada' }}">Destino: {{ $exportStatus['destination_label'] ?? 'Memoria' }} · {{ $exportStatus['destination_folder'] ?? 'carpeta seleccionada' }}</p>
                                        @endif
                                        @if(($exportStatus['status'] ?? '') === 'completed')
                                            <p class="mt-[8px] truncate text-[11px] text-emerald-600" title="{{ $exportStatus['destination_path'] ?? '' }}">Guardado en {{ $exportStatus['destination_path'] ?? '' }}</p>
                                        @elseif(($exportStatus['status'] ?? '') === 'failed')
                                            <p class="mt-[8px] text-[11px] text-red-600">{{ $exportStatus['error'] ?? 'La copia no pudo completarse.' }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div x-data="backupOverlayScroll()" class="backup-catalog-scroll-region relative min-h-0 flex-1" wire:loading.class="opacity-60" wire:target="selectDate">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="backup-catalog-scroll h-full overflow-auto">
                                <table class="w-full min-w-[650px] border-collapse text-left">
                                    <thead class="sticky top-0 z-10 bg-zinc-50 text-[12px] font-medium uppercase tracking-[0.05em] text-zinc-400"><tr><th class="w-[52px] px-[20px] py-[13px]"><span class="sr-only">Seleccionar</span></th><th class="px-[5px] py-[13px]">Empresa</th><th class="w-[80px] px-[12px] py-[13px]">Hora</th><th class="w-[135px] px-[12px] py-[13px]">Estado servidor</th><th class="w-[180px] px-[12px] py-[13px]">Estado memoria</th><th class="w-[60px] px-[15px] py-[13px] text-center"><span class="sr-only">Archivos</span></th></tr></thead>
                                    @forelse ($records as $record)
                                        @php $recordState = $recordAvailability[$record['id']] ?? 'incomplete'; @endphp
                                        <tbody wire:key="record-{{ $record['id'] }}" x-data="{ open: false }" class="border-b border-zinc-200 last:border-b-0">
                                            <tr @click="open = !open" class="cursor-pointer transition hover:bg-zinc-50/70" :class="open ? 'bg-zinc-50/70' : ''">
                                                <td class="px-[20px] py-[16px]" @click.stop>
                                                    <input type="checkbox" wire:model.live="selectedRecords" value="{{ $record['id'] }}" @disabled(!in_array($recordState, ['available', 'outdated'], true) || $exportIsActive) aria-label="Seleccionar {{ $record['company'] }}" class="h-5 w-5 rounded border-2 border-zinc-500 bg-white text-[#1A3A6B] focus:ring-0 focus:ring-offset-0 disabled:cursor-not-allowed disabled:border-zinc-300 disabled:bg-zinc-100 disabled:opacity-100">
                                                </td>
                                                <td class="max-w-[420px] px-[5px] py-[16px]"><p class="truncate font-medium text-black" title="{{ $record['company'] }}">{{ $record['company'] }}</p><p class="mt-[3px] truncate text-[11px] text-zinc-400" title="{{ $record['stem'] }}">{{ $record['company_folder'] ?? 'Carpeta no identificada' }}</p></td>
                                                <td class="px-[12px] py-[16px] tabular-nums text-zinc-500">{{ $record['time'] }}</td>
                                                <td class="px-[12px] py-[16px]">@if($record['complete'])<span class="inline-flex items-center gap-[5px] rounded-full bg-emerald-50 px-[9px] py-[5px] text-[11px] font-medium text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Completo</span>@else<span class="inline-flex items-center gap-[5px] rounded-full bg-red-50 px-[9px] py-[5px] text-[11px] font-medium text-red-700"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Incompleto</span>@endif</td>
                                                <td class="px-[12px] py-[16px]">
                                                    @if($recordState === 'available')<span class="inline-flex items-center gap-[6px] rounded-full bg-emerald-50 px-[10px] py-[5px] text-[12px] font-medium text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Disponible</span>
                                                    @elseif($recordState === 'backed_up')<span class="inline-flex items-center gap-[6px] rounded-full bg-blue-50 px-[10px] py-[5px] text-[12px] font-medium text-[#1A3A6B]"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>Ya respaldada</span>
                                                    @elseif($recordState === 'copying')<span class="inline-flex items-center gap-[6px] rounded-full bg-violet-50 px-[10px] py-[5px] text-[12px] font-medium text-violet-700"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-violet-500"></span>Copiando</span>
                                                    @elseif($recordState === 'outdated')<span class="inline-flex items-center rounded-full bg-orange-50 px-[10px] py-[5px] text-[12px] font-medium text-orange-700">Requiere actualización</span>
                                                    @elseif($recordState === 'older')<span class="inline-flex items-center rounded-full bg-zinc-100 px-[10px] py-[5px] text-[12px] text-zinc-500" title="Solo se permite el respaldo completo más reciente de la empresa para este día.">Hay uno más reciente</span>
                                                    @elseif($recordState === 'destination_missing')<span class="inline-flex items-center rounded-full bg-amber-50 px-[10px] py-[5px] text-[12px] text-amber-700">Conecta la memoria</span>
                                                    @elseif($recordState === 'folder_missing')<span class="inline-flex items-center rounded-full bg-zinc-100 px-[10px] py-[5px] text-[12px] text-zinc-600">Selecciona carpeta</span>
                                                    @else<span class="inline-flex items-center gap-[6px] rounded-full bg-red-50 px-[10px] py-[5px] text-[12px] font-medium text-red-700"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Incompleta</span>@endif
                                                </td>
                                                <td class="px-[15px] py-[16px] text-center"><button type="button" @click.stop="open = !open" class="inline-grid h-9 w-9 place-items-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-black" :aria-expanded="open"><svg class="h-5 w-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg></button></td>
                                            </tr>
                                            <tr x-show="open" x-cloak>
                                                <td colspan="6" class="bg-zinc-50/60 px-[20px] pb-[18px] pt-[2px]">
                                                    <div class="grid gap-[10px] md:grid-cols-2">
                                                        @if($record['database'])
                                                            <a @click.stop href="{{ route('activity-backups.server.download', ['kind' => 'database', 'token' => $record['database']['token']]) }}" class="flex min-w-0 items-center justify-between gap-[15px] rounded-xl border border-zinc-200 bg-white p-[14px] transition hover:border-[#1A3A6B]/40 hover:shadow-sm" title="Descargar {{ $record['database']['name'] }}"><span class="flex min-w-0 items-center gap-[12px]"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM14 2v5h5"/></svg></span><span class="min-w-0"><span class="block text-[12px] font-medium text-black">Base de datos · BAK</span><span class="mt-[2px] block truncate text-[11px] text-zinc-400">{{ $record['database']['name'] }} · {{ $formatBytes($record['database']['size']) }}</span></span></span><svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></a>
                                                        @else
                                                            <div class="flex items-center gap-[12px] rounded-xl border border-dashed border-zinc-300 px-[14px] py-[13px] text-zinc-400"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-zinc-100"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM9 13h6"/></svg></span><span><span class="block text-[12px] font-medium">Base de datos · BAK</span><span class="text-[11px]">Archivo no encontrado</span></span></div>
                                                        @endif
                                                        @if($record['archive'])
                                                            <a @click.stop href="{{ route('activity-backups.server.download', ['kind' => 'archive', 'token' => $record['archive']['token']]) }}" class="flex min-w-0 items-center justify-between gap-[15px] rounded-xl border border-zinc-200 bg-white p-[14px] transition hover:border-[#1A3A6B]/40 hover:shadow-sm" title="Descargar {{ $record['archive']['name'] }}"><span class="flex min-w-0 items-center gap-[12px]"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM14 2v5h5M10 11h4m-4 4h4"/></svg></span><span class="min-w-0"><span class="block text-[12px] font-medium text-black">Documento digital · {{ strtoupper($record['archive']['extension']) }}</span><span class="mt-[2px] block truncate text-[11px] text-zinc-400">{{ $record['archive']['name'] }} · {{ $formatBytes($record['archive']['size']) }}</span></span></span><svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></a>
                                                        @else
                                                            <div class="flex items-center gap-[12px] rounded-xl border border-dashed border-zinc-300 px-[14px] py-[13px] text-zinc-400"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-zinc-100"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 2h8l4 4v16H6zM9 13h6"/></svg></span><span><span class="block text-[12px] font-medium">Documento digital</span><span class="text-[11px]">ZIP o RAR no encontrado</span></span></div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    @empty
                                        <tbody><tr><td colspan="6" class="px-[20px] py-[70px] text-center"><svg class="mx-auto h-10 w-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M3 7h7l2 2h9v11H3V7Z"/></svg><p class="mt-[12px] font-medium text-black">No hay resultados</p><p class="mt-[4px] text-[13px] text-zinc-400">Cambia la fecha, el estado o la búsqueda.</p></td></tr></tbody>
                                    @endforelse
                                </table>
                                </div>
                                <span x-show="visible" x-cloak class="backup-catalog-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
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
