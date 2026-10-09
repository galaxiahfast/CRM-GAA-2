@php
    $fmt = fn (int $seconds) => sprintf('%02d:%02d:%02d', intdiv(max(0, $seconds), 3600), intdiv(max(0, $seconds) % 3600, 60), max(0, $seconds) % 60);
    $usersByArea = $groupUsers->groupBy('area_name');
    $superiors = $groupUsers->flatMap(fn (array $user) => $user['superiors'])->unique('id')->sortBy('name');
    $areaOptions = $usersByArea->map(fn ($users, $name) => ['id' => $users->first()['area_id'], 'name' => $name, 'count' => $users->count(), 'user_ids' => $users->pluck('id')->map(fn ($id) => (int) $id)->values()->all()])->filter(fn ($area) => $area['id'])->values();
    $superiorOptions = $superiors->map(fn ($superior) => ['id' => $superior['id'], 'name' => $superior['name'], 'user_ids' => $groupUsers->filter(fn (array $user) => collect($user['superiors'])->contains('id', $superior['id']))->pluck('id')->map(fn ($id) => (int) $id)->values()->all()])->values();
    $userOptions = $groupUsers->map(fn ($user) => ['id' => $user['id'], 'name' => $user['name']])->values();
    $selectedAreaCount = $selectedGroupUsers->pluck('area_id')->filter()->unique()->count();
@endphp

<style>
    .group-filter-scrollbar::-webkit-scrollbar,
    .group-scrollbar::-webkit-scrollbar { width: 6px !important; height: 6px !important; }
    .group-filter-scrollbar::-webkit-scrollbar-track,
    .group-scrollbar::-webkit-scrollbar-track { background: #f8fafc !important; }
    .group-filter-scrollbar::-webkit-scrollbar-thumb,
    .group-scrollbar::-webkit-scrollbar-thumb { background: #000 !important; border-radius: 9999px !important; }
    .group-filter-scrollbar,
    .group-scrollbar { scrollbar-width: thin; scrollbar-color: #000 #fff; scrollbar-gutter: stable; overscroll-behavior: contain; }
    @keyframes group-report-spin { to { transform: rotate(360deg); } }
    .supervision-monochrome { background: #fff !important; color: #3f3f46; font-size: 15px; }
    .supervision-monochrome :where(p, span, small, label, input, textarea, button, a, strong, h2, h3, th, td) {
        font-size: 15px !important;
    }
    .supervision-monochrome button,
    .supervision-monochrome input,
    .supervision-monochrome textarea { font-size: 15px; box-shadow: none !important; }
    .supervision-monochrome button:focus,
    .supervision-monochrome button:focus-visible,
    .supervision-monochrome input:focus,
    .supervision-monochrome input:focus-visible,
    .supervision-monochrome textarea:focus,
    .supervision-monochrome textarea:focus-visible { outline: none !important; box-shadow: none !important; --tw-ring-shadow: 0 0 #0000 !important; }
    .supervision-monochrome [data-selection-list] { border: 1px solid #e4e4e7 !important; background: #fff !important; }
    .supervision-monochrome [data-report-form] { border-radius: 12px !important; box-shadow: none !important; }
    .supervision-monochrome [style*="color: #1A3A6B"] { color: #000 !important; }
    .supervision-monochrome [style*="background-color: #1A3A6B"],
    .supervision-monochrome [style*="background: #1A3A6B"] { background: #18181b !important; }
    .supervision-monochrome [style*="border: 1px solid #1A3A6B"] { border-color: #18181b !important; }
    .supervision-monochrome [style*="border: 2px dashed"] { border: 1px solid #e4e4e7 !important; }
</style>

<div
    data-group-selection-root
    data-selected-ids='@json(array_values(array_map('intval', $selectedCollaboratorIds)))'
    data-reported-ids='@json(array_values(array_map('intval', $reportedCollaboratorIds)))'
    data-report-current="{{ $groupReportIsCurrent ? 'true' : 'false' }}"
    class="supervision-monochrome w-full bg-white"
    style="overflow-x: auto; padding: 0 50px 50px;"
    x-data="{
        viewScale: Number(localStorage.getItem('supervision-hours-scale') || 100),
        isFullscreen: false,
        saveScale() { localStorage.setItem('supervision-hours-scale', this.viewScale); },
        zoomOut() { this.viewScale = Math.max(70, this.viewScale - 5); this.saveScale(); },
        zoomIn() { this.viewScale = Math.min(100, this.viewScale + 5); this.saveScale(); },
        async toggleFullscreen() {
            if (!document.fullscreenElement) await this.$root.requestFullscreen();
            else await document.exitFullscreen();
        }
    }"
    x-init="document.addEventListener('fullscreenchange', () => isFullscreen = Boolean(document.fullscreenElement))"
>

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <button type="button" @click="zoomOut()" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de Supervisión de Horas">
        <button type="button" @click="zoomIn()" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
        </button>
    </div>

    <!-- Contenedor interno con min-width -->
    <div style="min-width:1000px;padding:0;margin:0;transform-origin:top center;" :style="`width:${10000 / viewScale}%;margin-left:${(100 - (10000 / viewScale)) / 2}%;transform:scale(${viewScale / 100});`">

        <div style="margin:0 -50px;padding:50px;border-bottom:1px solid #e4e4e7;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:80px;white-space:nowrap;">
            <div style="display:flex;align-items:center;gap:15px;color:#71717a;font-size:15px;">
                <span>Actividades</span><span style="color:#d4d4d8">&gt;</span><span>Control de Horas</span><span style="color:#d4d4d8">&gt;</span><span style="font-weight:600;color:#000">Supervisión de Horas</span>
            </div>
            <div style="display:flex;align-items:center;gap:30px;font-size:15px;">
                <button type="button" wire:click="exportSelectedGeneralReport" @disabled(! $groupReportIsCurrent) style="display:inline-flex;align-items:center;gap:10px;border:0;background:transparent;padding:0;color:#000;font-weight:600;disabled:opacity-40;">
                    <svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>Descargar PDF
                </button>
                <button type="button" onclick="window.print()" style="display:inline-flex;align-items:center;gap:10px;border:0;background:transparent;padding:0;color:#71717a;">
                    <svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>Imprimir
                </button>
            </div>
        </div>

        <!-- Header principal -->
        <div style="background-color: #fff; padding: 50px 0 0; overflow: hidden; min-width: max-content;">

            <!-- Encabezado -->
            <div style="border-bottom: none; min-width: max-content;">
                <div style="display: flex; flex-wrap: nowrap; align-items: center; justify-content: space-between; gap: 32px;">

                    <div style="max-width: 672px; flex-shrink: 0;">

                        <div style="display: flex; align-items: center; gap: 20px;">

                            <div style="display: flex; height: 56px; width: 56px; align-items: center; justify-content: center; border:1px solid #e4e4e7; border-radius: 12px; background:#fff; flex-shrink: 0;">
                                <svg style="height: 28px; width: 28px; color: #000; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </div>

                            <div>
                                <h1 style="font-size: 20px; font-weight: 600; letter-spacing: -0.025em; color: #000; white-space: nowrap;">
                                    Supervisión de Horas
                                </h1>

                                <p style="font-size: 15px; color: #6b7280; white-space: nowrap;">
                                    Consulta, compara y exporta las horas registradas por los colaboradores.
                                </p>
                            </div>

                        </div>

                    </div>

                    <nav style="display:flex;align-items:center;gap:20px;flex-shrink:0" aria-label="Secciones de Supervisión de Horas">
                        <button type="button" wire:click="showPreparationSection" style="display:inline-flex;align-items:center;gap:10px;border:0;background:transparent;padding:0;color:{{ $reportSection === 'prepare' ? '#000' : '#71717a' }};font-weight:{{ $reportSection === 'prepare' ? '600' : '400' }};white-space:nowrap"><svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM4 10h16M9 10v9"/></svg>Informe general</button>
                        <a href="{{ route('time.admin.online') }}" style="display:inline-flex;align-items:center;gap:10px;color:#71717a;text-decoration:none;white-space:nowrap"><svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></svg>Actividad en línea</a>
                        <button type="button" wire:click="showResultsSection" style="display:inline-flex;align-items:center;gap:10px;border:0;background:transparent;padding:0;color:{{ $reportSection === 'results' ? '#000' : '#71717a' }};font-weight:{{ $reportSection === 'results' ? '600' : '400' }};white-space:nowrap"><svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M6 16l4-4 3 2 5-7"/></svg>Resultado del reporte</button>
                    </nav>

                </div>
            </div>

            <!-- Filtros de fechas -->
        </div>

        @if ($reportSection === 'prepare')
        <section style="display:grid;grid-template-columns:300px minmax(0,1fr);grid-template-rows:auto auto minmax(0,1fr);height:680px;margin-top:20px;overflow:hidden;border:1px solid #e4e4e7;border-radius:12px;background:#fff;">

        <header style="grid-column:1/-1;display:flex;min-height:80px;align-items:center;justify-content:space-between;gap:20px;border-bottom:1px solid #e4e4e7;padding:15px 20px;background:#fff;">
            <div>
                <h2 style="margin:0;font-size:15px;font-weight:600;color:#000;">Preparar informe</h2>
                <p style="margin:5px 0 0;font-size:15px;color:#71717a;">Configura y genera los resultados fácilmente.</p>
            </div>
            <div style="display:flex;align-items:center;gap:20px;color:#000;font-size:15px;font-weight:500;white-space:nowrap;">
                <span style="display:inline-flex;align-items:center;gap:10px;"><svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg><span data-selected-count>{{ $selectedGroupUsers->count() }}</span> seleccionados</span>
                <span style="display:inline-flex;align-items:center;gap:10px;"><svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
                <span style="display:inline-flex;align-items:center;gap:10px;"><svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01"/></svg><span data-selected-area-count>{{ $selectedAreaCount }}</span> <span data-selected-area-label>{{ $selectedAreaCount === 1 ? 'área participa' : 'áreas participan' }}</span></span>
            </div>
        </header>

        <div style="grid-column:1/-1;display:flex;align-items:center;gap:20px;border-bottom:1px solid #e4e4e7;padding:20px;background:#fff;">
            <label style="position:relative;display:block;min-width:320px;flex:1;">
                <span class="sr-only">Buscar colaborador</span>
                <svg style="position:absolute;left:15px;top:50%;width:20px;height:20px;transform:translateY(-50%);color:#a1a1aa;pointer-events:none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                <input data-general-search type="search" autocomplete="off" placeholder="Buscar colaborador, ID o área..." style="height:50px;width:100%;border:1px solid #e4e4e7;border-radius:12px;background:#fff;padding:0 50px 0 45px;color:#000;outline:none;">
                <button data-voice-search type="button" title="Buscar por voz" aria-label="Buscar por voz" style="position:absolute;right:10px;top:50%;display:inline-flex;width:34px;height:34px;transform:translateY(-50%);align-items:center;justify-content:center;border:0;background:transparent;color:#000;">
                    <svg style="width:20px;height:20px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Zm-7 9v1a7 7 0 0 0 14 0v-1M12 19v3m-4 0h8"/></svg>
                </button>
            </label>
            <button type="button" data-select-all style="display:inline-flex;align-items:center;gap:10px;padding:0;border:0;background:transparent;color:#000;font-size:15px;font-weight:600;white-space:nowrap;"><span>✓</span>Seleccionar todos</button>
            <button type="button" data-clear-selection style="display:inline-flex;align-items:center;gap:10px;padding:0;border:0;background:transparent;color:#71717a;font-size:15px;white-space:nowrap;"><span>×</span>Deseleccionar todos</button>
        </div>

        <!-- ============================================================ -->
        <!-- LISTA DE COLABORADORES - ÁREA PUNTEADA                       -->
        <!-- ============================================================ -->
        <div wire:ignore data-selection-list class="group-scrollbar" style="position:relative;grid-column:2;grid-row:3;overflow:hidden;overflow-y:auto;overscroll-behavior:contain;border-left:1px solid #e4e4e7;background:#fff;">

            <!-- Contenido con padding -->
            <div style="padding: 0px 20px 0px 20px;">

                @forelse ($usersByArea as $areaName => $areaUsers)
                    @php($areaUserIds = $areaUsers->pluck('id')->map(fn ($id) => (int) $id)->all())
                    <div data-area-group style="border: 1px solid #e5e7eb; margin-top:20px; margin-bottom: {{ $loop->last ? '20px' : '0px' }}; border-radius: 10px; overflow: hidden; background-color: #fafafa;">

                        <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #374151; display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb;">
                            <label style="display:flex;align-items:center;gap:10px;min-width:0;cursor:pointer;">
                                <span data-area-name class="min-w-0 truncate">{{ $areaName }}</span>
                                <input data-area-checkbox data-user-ids='@json($areaUserIds)' type="checkbox" class="rounded border-gray-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0" style="border-radius:4px;border:1px solid #d1d5db;accent-color:#000;width:16px;height:16px;flex-shrink:0;outline:none;box-shadow:none;" />
                            </label>
                            <span style="color: #9ca3af; font-weight: 400;">{{ $areaUsers->count() }} colaboradores</span>
                        </div>

                        <div class="grid gap-[20px] bg-white p-[20px] sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($areaUsers as $user)
                                <div data-user-search="{{ mb_strtolower($user['name'].' '.($user['employee_id'] ?? '').' '.($user['position_name'] ?? '').' '.($areaName ?? '')) }}" style="display:flex;min-width:0;align-items:center;gap:10px;border:1px solid #e4e4e7;border-radius:12px;background:#fff;padding:15px 15px 15px 20px;">
                                    <label style="display:flex;min-width:0;flex:1;align-items:center;gap:15px;color:#000;cursor:pointer;">
                                        <input data-area-collaborator data-area-id="{{ $user['area_id'] ?? '' }}" data-collaborator-id="{{ $user['id'] }}" type="checkbox" wire:model.defer="selectedCollaboratorIds" value="{{ $user['id'] }}" class="focus:outline-none focus:ring-0 focus:ring-offset-0" style="border-radius:4px;border:1px solid #d1d5db;accent-color:#000;width:16px;height:16px;flex-shrink:0;outline:none;box-shadow:none;" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate" style="font-size:14px;font-weight:500;">{{ $user['name'] }}</span>
                                            <small class="block truncate" style="font-size:12px;color:#71717a;margin-top:5px;">{{ filled($user['employee_id'] ?? null) ? 'ID Checador: '.$user['employee_id'] : $user['position_name'] }}</small>
                                        </span>
                                    </label>
                                    <button data-edit-collaborator-placeholder type="button" disabled aria-disabled="true" title="Edición disponible próximamente" style="display:inline-flex;width:40px;height:40px;flex:none;align-items:center;justify-content:center;border:0;border-radius:10px;background:#18181b;color:#fff;cursor:not-allowed;opacity:1;">
                                        <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M4 20l4.2-1 10.6-10.6a2.55 2.55 0 0 0-3.6-3.6L4.6 15.4 4 20Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.8 6.2 17.8 10.2"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                    </div>
                @empty
                    <div style="padding: 40px 20px; text-align: center; color: #6b7280; font-size: 15px;">
                        No hay colaboradores disponibles.
                    </div>
                @endforelse

                @error('selectedCollaboratorIds')
                    <p style="margin-top: 8px; font-size: 14px; color: #ef4444; padding: 8px 12px; background-color: #fef2f2; border-radius: 4px; border: 1px solid #fecaca;">
                        {{ $message }}
                    </p>
                @enderror

            </div>

        </div>

        <!-- ============================================================ -->
        <!-- BOTONES DE EXPORTACIÓN Y REPORTE                             -->
        <!-- ============================================================ -->
        <form data-report-form style="grid-column:1;grid-row:3;display:flex;min-width:0;flex-direction:column;align-items:stretch;gap:20px;padding:20px;background:#fff;border:0;border-radius:0;box-shadow:none;">
            <div>
                <h2 style="margin:0;font-size:15px;font-weight:600;color:#000;">Periodo del informe</h2>
                <p style="margin:5px 0 0;font-size:13px;color:#71717a;">Define las fechas que deseas consultar.</p>
            </div>
            <div style="flex: 0 0 auto;">
                <label for="from" style="margin-bottom: 10px; display: block; font-size: 15px; font-weight: 600; color: #000; white-space: nowrap;">Desde</label>
                <input id="from" type="date" wire:model.defer="from" style="height:50px;width:100%;border:1px solid #e4e4e7;border-radius:12px;background:#fff;padding:0 20px;font-size:15px;color:#000;outline:none;">
            </div>
            <div style="flex: 0 0 auto;">
                <label for="to" style="margin-bottom: 10px; display: block; font-size: 15px; font-weight: 600; color: #000; white-space: nowrap;">Hasta</label>
                <input id="to" type="date" wire:model.defer="to" style="height:50px;width:100%;border:1px solid #e4e4e7;border-radius:12px;background:#fff;padding:0 20px;font-size:15px;color:#000;outline:none;">
            </div>
            <div style="display:grid;gap:15px;">
                <button type="submit" wire:loading.attr="disabled" wire:target="generateGroupReport" style="display:inline-flex;align-items:center;justify-content:center;border-radius:12px;background:#18181b;padding:15px 20px;font-size:15px;font-weight:600;color:#fff;border:0;cursor:pointer;box-shadow:none;">Generar informe</button>
                <p style="display:flex;align-items:flex-start;gap:10px;margin:0;color:#71717a;font-size:13px;line-height:1.5;">
                    <svg style="width:16px;height:16px;flex:none;margin-top:2px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span>Último reporte realizado:<br>{{ $lastReportGeneratedAt ?? 'Sin reportes previos' }}</span>
                </p>
            </div>
        </form>
        </section>
        @endif

        <div wire:loading.flex wire:target="generateGroupReport" style="display: none; min-height: 220px; margin-top:20px; align-items: center; justify-content: center; flex-direction: column; gap: 10px; border: 1px solid #e4e4e7; border-radius: 12px; background: #fff; color: #000; box-shadow:none;">
            <span style="width: 32px; height: 32px; border: 3px solid #e4e4e7; border-top-color: #000; border-radius: 9999px; animation: group-report-spin .7s linear infinite;"></span>
            <span style="font-size: 14px; font-weight: 600;">Calculando métricas y procesando datos...</span>
        </div>

        @if ($reportSection === 'results')
        <div wire:loading.remove wire:target="generateGroupReport" wire:key="group-report-results-{{ $groupReportVersion }}">

            @if (! $groupReportIsCurrent)
                <div style="margin-top:20px;border:1px solid #e4e4e7;border-radius:12px;padding:40px 20px;text-align:center;background:#fff;color:#71717a;">
                    Genera un informe para consultar sus resultados y opciones de descarga.
                </div>
            @else

            <!-- Contenedor con borde punteado que envuelve botones de exportación, métricas y distribuciones -->
            <div style="margin-top: 20px; border: 1px solid #e4e4e7; border-radius: 12px; padding: 20px; background-color: #fff; font-size: 15px;">

                <div style="padding:0 0 20px;border-bottom:1px solid #e4e4e7;display:flex;align-items:center;justify-content:space-between;gap:20px;">
                    <div>
                        <h2 style="margin:0;font-size:15px;font-weight:600;color:#000;">Resultado del reporte</h2>
                        <p style="margin:5px 0 0;font-size:15px;color:#71717a;">Consolidado de horas del periodo seleccionado.</p>
                    </div>
                    <div style="display:flex;align-items:center;gap:20px;color:#000;font-size:15px;font-weight:500;">
                        <span>{{ $reportedGroupUsers->count() }} {{ $reportedGroupUsers->count() === 1 ? 'seleccionado' : 'seleccionados' }}</span>
                        <span>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:280px minmax(0,1fr);height:590px;border-bottom:1px solid #d4d4d8;">
                    <aside style="display:flex;min-height:0;flex-direction:column;border-right:1px solid #e4e4e7;background:#fff;">
                        <div style="display:flex;height:70px;box-sizing:border-box;flex:none;flex-direction:column;justify-content:center;border-bottom:1px solid #d4d4d8;padding:10px 20px;">
                            <h3 style="margin:0;font-weight:600;color:#000;">Colaboradores</h3>
                            <p style="margin:5px 0 0;color:#71717a;">Selecciona una persona.</p>
                        </div>
                        <nav class="group-scrollbar" style="min-height:0;flex:1;overflow-y:auto;padding:0;" aria-label="Colaboradores incluidos en el reporte">
                            @foreach ($reportedGroupUsers as $reportedUser)
                                <button type="button" wire:click="selectResultUser({{ $reportedUser['id'] }})" style="display:flex;min-height:70px;width:100%;align-items:center;gap:10px;border:0;border-bottom:1px solid #f4f4f5;background:{{ (int) ($activeResultUser['id'] ?? 0) === (int) $reportedUser['id'] ? '#f4f4f5' : '#fff' }};padding:15px 20px;text-align:left;color:#000;">
                                    <span style="display:flex;width:32px;height:32px;flex:none;align-items:center;justify-content:center;border:1px solid #e4e4e7;border-radius:6px;background:{{ (int) ($activeResultUser['id'] ?? 0) === (int) $reportedUser['id'] ? '#000' : '#fff' }};color:{{ (int) ($activeResultUser['id'] ?? 0) === (int) $reportedUser['id'] ? '#fff' : '#000' }};font-weight:600;">{{ mb_strtoupper(mb_substr($reportedUser['name'], 0, 1)) }}</span>
                                    <span style="min-width:0;flex:1;"><strong style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">{{ $reportedUser['name'] }}</strong><small style="display:block;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#71717a;">{{ filled($reportedUser['employee_id'] ?? null) ? 'ID '.$reportedUser['employee_id'] : $reportedUser['position_name'] }}</small></span>
                                    @if ((int) ($activeResultUser['id'] ?? 0) === (int) $reportedUser['id'])
                                        <span aria-hidden="true">✓</span>
                                    @endif
                                </button>
                            @endforeach
                        </nav>
                    </aside>

                    <div style="display:flex;min-width:0;min-height:0;flex-direction:column;background:#fff;">
                        <div style="display:flex;height:70px;box-sizing:border-box;flex:none;align-items:center;justify-content:space-between;gap:20px;border-bottom:1px solid #d4d4d8;padding:10px 20px;">
                            <h3 style="margin:0;font-weight:600;color:#000;">Detalle de actividades</h3>
                            <div style="display:flex;align-items:center;gap:20px;color:#000;"><span>Tiempo efectivo: <strong style="font-family:monospace;">{{ $fmt((int) ($activeResultData['total'] ?? 0)) }}</strong></span><span>Cierres automáticos: {{ $activeResultData['autoClosedCount'] ?? 0 }}</span></div>
                        </div>
                        <div class="group-scrollbar" style="min-height:0;flex:1;overflow-x:auto;overflow-y:hidden;">
                            <div role="table" aria-label="Detalle de actividades por colaborador" style="display:flex;height:100%;min-width:1360px;flex-direction:column;--activity-columns:160px 210px 210px 180px 180px 210px 160px 90px;">
                                <div role="rowgroup" style="position:relative;z-index:2;flex:none;background:#f4f4f5;">
                                    <div role="row" style="display:grid;grid-template-columns:var(--activity-columns);min-height:76px;align-items:center;border-bottom:1px solid #d4d4d8;">
                                        <div role="columnheader" style="padding:15px 20px;font-weight:600;">Fecha</div>
                                        @foreach ($activeResultActivityDetail['columns'] as $column)
                                            @continue($column === 'Observaciones')
                                            <div role="columnheader" style="padding:15px 20px;font-weight:600;">{{ $column }}</div>
                                        @endforeach
                                        <div role="columnheader" style="padding:15px 20px;text-align:center;font-weight:600;">Acciones</div>
                                    </div>
                                </div>

                                <div role="rowgroup" class="group-scrollbar" style="min-height:0;flex:1;overflow-y:auto;overflow-x:hidden;background:#fff;">
                                    @forelse ($activeResultActivityDetail['groups'] as $dayGroup)
                                        @foreach ($dayGroup['rows'] as $rowIndex => $row)
                                            <div role="row" style="display:grid;grid-template-columns:var(--activity-columns);min-height:70px;align-items:center;border-bottom:1px solid #e4e4e7;background:#fff;">
                                                <div role="cell" style="padding:15px 20px;">{{ $loop->first ? $dayGroup['date'] : '' }}</div>
                                                @foreach ($activeResultActivityDetail['columns'] as $columnIndex => $column)
                                                    @continue($column === 'Observaciones')
                                                    <div role="cell" style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:15px 20px;" title="{{ $row[$columnIndex] ?? '' }}">{{ $row[$columnIndex] ?? '' }}</div>
                                                @endforeach
                                                <div role="cell" style="padding:15px 20px;text-align:center;">
                                                    <button type="button" @if (! $resultIsExample) wire:click="openActivityEditModal({{ (int) ($dayGroup['entry_ids'][$rowIndex] ?? 0) }})" @endif @disabled($resultIsExample) aria-label="{{ $resultIsExample ? 'Edición deshabilitada en la vista de ejemplo' : 'Editar actividad' }}" style="display:inline-flex;width:40px;height:40px;align-items:center;justify-content:center;border:1px solid #e4e4e7;border-radius:10px;background:#fff;color:#000;{{ $resultIsExample ? 'opacity:.35;cursor:not-allowed;' : '' }}">
                                                        <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.2 5.2 3.6 3.6M4 20l4.2-1 10.6-10.6a2.55 2.55 0 0 0-3.6-3.6L4.6 15.4 4 20Z"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @empty
                                        <div role="row" style="display:flex;min-height:160px;align-items:center;justify-content:center;color:#71717a;">Sin registros en el periodo.</div>
                                    @endforelse
                                </div>

                                <div role="rowgroup" style="position:relative;z-index:2;flex:none;background:#f4f4f5;">
                                    <div role="row" style="display:grid;grid-template-columns:var(--activity-columns);min-height:64px;align-items:center;border-top:1px solid #d4d4d8;font-weight:600;color:#000;">
                                        <div role="cell" style="padding:15px 20px;">TOTAL</div>
                                        <div role="cell" style="padding:15px 20px;"></div>
                                        <div role="cell" style="padding:15px 20px;">{{ $activeResultActivityCount }} {{ $activeResultActivityCount === 1 ? 'actividad' : 'actividades' }}</div>
                                        <div role="cell" style="padding:15px 20px;"></div>
                                        <div role="cell" style="padding:15px 20px;font-family:monospace;">{{ $fmt((int) ($activeResultData['total'] ?? 0)) }}</div>
                                        <div role="cell" style="padding:15px 20px;"></div>
                                        <div role="cell" style="padding:15px 20px;">{{ $activeResultUser['area_name'] ?? '' }}</div>
                                        <div role="cell" style="padding:15px 20px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:20px;border-top:1px solid #e4e4e7;">
                    <button data-export-individual wire:click="exportSelectedIndividualReport" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->count() !== 1) style="display:flex;min-height:82px;align-items:center;gap:15px;border:0;border-right:1px solid #e4e4e7;background:#fff;padding:15px 20px;text-align:left;color:#000;cursor:pointer;disabled:opacity-40;">
                        <svg style="width:20px;height:20px;flex:none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                        <span><strong style="display:block;font-size:15px;">Reporte individual</strong><small style="display:block;margin-top:5px;font-size:13px;color:#71717a;">Información de una persona</small></span>
                    </button>
                    <button data-export-group wire:click="exportSelectedIndividualBatch" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display:flex;min-height:82px;align-items:center;gap:15px;border:0;border-right:1px solid #e4e4e7;background:#fff;padding:15px 20px;text-align:left;color:#000;cursor:pointer;disabled:opacity-40;">
                        <svg style="width:20px;height:20px;flex:none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                        <span><strong style="display:block;font-size:15px;">Reporte grupal</strong><small style="display:block;margin-top:5px;font-size:13px;color:#71717a;">Información de seleccionados</small></span>
                    </button>
                    <button data-export-general wire:click="exportSelectedGeneralReport" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display:flex;min-height:82px;align-items:center;gap:15px;border:0;background:#fff;padding:15px 20px;text-align:left;color:#000;cursor:pointer;disabled:opacity-40;">
                        <svg style="width:20px;height:20px;flex:none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                        <span><strong style="display:block;font-size:15px;">Reporte general</strong><small style="display:block;margin-top:5px;font-size:13px;color:#71717a;">Consolidado del periodo</small></span>
                    </button>
                </div>

            </div> <!-- Fin del contenedor con borde punteado -->

            @endif
        </div>
        @endif

    </div>
</div>

@if ($showActivityEditModal)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]"
        role="dialog" aria-modal="true" aria-labelledby="activity-edit-title"
        wire:keydown.escape.window="closeActivityEditModal">
        <div x-data @click.away="$wire.closeActivityEditModal()"
            class="flex max-h-[calc(100vh-40px)] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.25)]">
            <div class="flex items-center justify-between gap-5 border-b border-gray-300 px-5 py-4">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="activity-edit-title" class="truncate text-[15px] font-semibold text-gray-900">Editar horario de actividad</h2>
                        <p class="mt-1 truncate text-[15px] text-gray-500">{{ $editingActivityName }}</p>
                    </div>
                </div>
                <button type="button" wire:click="closeActivityEditModal" aria-label="Cerrar"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form wire:submit="saveActivityTimes" class="flex min-h-0 flex-1 flex-col">
                <div class="overflow-y-auto p-5 text-[15px]">
                    <div class="rounded-xl border border-zinc-200 bg-white p-[20px] shadow-none">
                        <h3 class="text-[15px] font-semibold text-gray-900">Horario registrado</h3>
                        <p class="mt-1 text-[15px] text-gray-500">La duración efectiva se recalculará automáticamente al guardar.</p>

                        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="activity-start-time" class="mb-2 block text-[15px] font-medium text-gray-700">Hora de inicio</label>
                                <input id="activity-start-time" type="time" step="1" wire:model="activityStartTime"
                                    class="h-[50px] w-full rounded-xl border border-zinc-200 bg-white px-[20px] text-[15px] text-black shadow-none focus:border-zinc-200 focus:ring-0">
                                @error('activityStartTime') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="activity-end-time" class="mb-2 block text-[15px] font-medium text-gray-700">Hora de fin</label>
                                <input id="activity-end-time" type="time" step="1" wire:model="activityEndTime"
                                    class="h-[50px] w-full rounded-xl border border-zinc-200 bg-white px-[20px] text-[15px] text-black shadow-none focus:border-zinc-200 focus:ring-0">
                                @error('activityEndTime') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-5">
                            <label for="activity-correction-comment" class="mb-2 block text-[15px] font-medium text-gray-700">
                                Comentario o motivo de la corrección <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <textarea id="activity-correction-comment" wire:model="activityCorrectionComment" rows="4" maxlength="500" required
                                placeholder="Describe por qué es necesario corregir el horario de esta actividad..."
                                class="w-full resize-none rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] text-black shadow-none focus:border-zinc-200 focus:ring-0"></textarea>
                            <div class="mt-2 flex items-start justify-between gap-4">
                                @error('activityCorrectionComment')
                                    <p class="text-[15px] text-red-600">{{ $message }}</p>
                                @else
                                    <p class="text-[15px] text-gray-500">Este comentario quedará registrado en el historial.</p>
                                @enderror
                                <span class="shrink-0 text-[13px] text-gray-400">Máximo 500 caracteres</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-[20px] border-t border-zinc-200 bg-white p-[20px]">
                    <button type="button" wire:click="closeActivityEditModal"
                        class="inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-medium text-black focus:outline-none focus:ring-0">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveActivityTimes"
                        class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-[20px] py-[15px] text-[15px] font-semibold text-white focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveActivityTimes">Guardar cambios</span>
                        <span wire:loading wire:target="saveActivityTimes">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    (() => {
        const initialiseGroupReport = (root) => {
            const selectionList = root.querySelector('[data-selection-list]');
            if (!root || !selectionList || selectionList.dataset.selectionInitialised === 'true') return;

            selectionList.dataset.selectionInitialised = 'true';
            const count = root.querySelector('[data-selected-count]');
            const areaCount = root.querySelector('[data-selected-area-count]');
            const areaLabel = root.querySelector('[data-selected-area-label]');
            const selectedIds = new Set(JSON.parse(root.dataset.selectedIds || '[]').map(Number));
            const reportedIds = new Set(JSON.parse(root.dataset.reportedIds || '[]').map(Number));
            const reportIsCurrent = root.dataset.reportCurrent === 'true';

            const collaboratorCheckboxes = () => [...selectionList.querySelectorAll('[data-collaborator-id]')];
            const areaCheckboxes = () => [...selectionList.querySelectorAll('[data-area-checkbox]')];
            const userIdsForArea = (checkbox) => JSON.parse(checkbox.dataset.userIds || '[]').map(Number);

            const refresh = () => {
                collaboratorCheckboxes().forEach((checkbox) => {
                    checkbox.checked = selectedIds.has(Number(checkbox.dataset.collaboratorId));
                });

                areaCheckboxes().forEach((checkbox) => {
                    const ids = userIdsForArea(checkbox);
                    checkbox.checked = ids.length > 0 && ids.every((id) => selectedIds.has(id));
                });

                if (count) count.textContent = selectedIds.size;
                const selectedAreas = new Set(collaboratorCheckboxes()
                    .filter((checkbox) => selectedIds.has(Number(checkbox.dataset.collaboratorId)) && checkbox.dataset.areaId)
                    .map((checkbox) => checkbox.dataset.areaId));
                if (areaCount) areaCount.textContent = selectedAreas.size;
                if (areaLabel) areaLabel.textContent = selectedAreas.size === 1 ? 'área participa' : 'áreas participan';

                const selectionMatchesReport = selectedIds.size === reportedIds.size
                    && [...selectedIds].every((id) => reportedIds.has(id));
                const canExport = reportIsCurrent && selectionMatchesReport && selectedIds.size > 0;

                const individualExport = root.querySelector('[data-export-individual]');
                const groupExport = root.querySelector('[data-export-group]');
                const generalExport = root.querySelector('[data-export-general]');
                if (individualExport) individualExport.disabled = !canExport || selectedIds.size !== 1;
                if (groupExport) groupExport.disabled = !canExport;
                if (generalExport) generalExport.disabled = !canExport;
            };

            const setCollaborator = (id, selected, notifyLivewire = true) => {
                id = Number(id);

                if (selected) selectedIds.add(id);
                else selectedIds.delete(id);

                const checkbox = selectionList.querySelector('[data-collaborator-id="' + id + '"]');
                if (checkbox && checkbox.checked !== selected) {
                    checkbox.checked = selected;
                    if (notifyLivewire) checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            const setUsers = (ids, selected) => {
                ids.forEach((id) => setCollaborator(id, selected));
                refresh();
            };

            collaboratorCheckboxes().forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    setCollaborator(checkbox.dataset.collaboratorId, checkbox.checked, false);
                    refresh();
                });
            });

            areaCheckboxes().forEach((checkbox) => {
                checkbox.addEventListener('change', () => setUsers(userIdsForArea(checkbox), checkbox.checked));
            });

            root.querySelector('[data-select-all]')?.addEventListener('click', () => {
                setUsers(collaboratorCheckboxes().map((checkbox) => Number(checkbox.dataset.collaboratorId)), true);
            });

            root.querySelector('[data-clear-selection]')?.addEventListener('click', () => {
                setUsers(collaboratorCheckboxes().map((checkbox) => Number(checkbox.dataset.collaboratorId)), false);
            });

            const generalSearch = root.querySelector('[data-general-search]');
            const normaliseSearch = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase()
                .trim();
            const filterCollaborators = () => {
                const term = normaliseSearch(generalSearch?.value);
                selectionList.querySelectorAll('[data-area-group]').forEach((area) => {
                    let visibleUsers = 0;
                    area.querySelectorAll('[data-user-search]').forEach((user) => {
                        const visible = term === '' || normaliseSearch(user.dataset.userSearch).includes(term);
                        user.style.display = visible ? 'flex' : 'none';
                        if (visible) visibleUsers++;
                    });
                    area.style.display = visibleUsers > 0 ? 'block' : 'none';
                });
            };
            generalSearch?.addEventListener('input', filterCollaborators);

            root.querySelector('[data-voice-search]')?.addEventListener('click', () => {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition || !generalSearch) {
                    generalSearch?.focus();
                    return;
                }

                const recognition = new SpeechRecognition();
                recognition.lang = 'es-MX';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;
                recognition.addEventListener('result', (event) => {
                    generalSearch.value = event.results[0][0].transcript;
                    filterCollaborators();
                });
                recognition.start();
            });

            root.addEventListener('group-selection', (event) => setUsers(event.detail.userIds || [], true));

            const reportForm = root.querySelector('[data-report-form]');
            reportForm?.addEventListener('submit', (event) => {
                event.preventDefault();

                const componentRoot = root.closest('[wire\\:id]');
                const component = componentRoot
                    ? window.Livewire?.find(componentRoot.getAttribute('wire:id'))
                    : null;

                if (!component) return;

                component.call(
                    'generateGroupReport',
                    [...selectedIds],
                    reportForm.querySelector('#from')?.value ?? '',
                    reportForm.querySelector('#to')?.value ?? '',
                );
            });

            root.querySelectorAll('[data-group-search]').forEach((search) => {
                const input = search.querySelector('[data-group-search-input]');
                const menu = search.querySelector('[data-group-search-menu]');
                const options = JSON.parse(search.dataset.options || '[]');
                const emptyMessage = JSON.parse(search.dataset.emptyMessage || '"Sin coincidencias"');

                if (menu) {
                    menu.style.maxHeight = '200px';
                    menu.style.overflowY = 'auto';
                }

                const close = () => { menu.style.display = 'none'; };
                const render = () => {
                    const term = input.value.trim().toLocaleLowerCase();
                    const filtered = term === ''
                        ? options
                        : options.filter((option) => option.name.toLocaleLowerCase().includes(term));

                    menu.replaceChildren();

                    if (filtered.length === 0) {
                        const message = document.createElement('p');
                        message.textContent = emptyMessage;
                        message.style.cssText = 'padding: 20px; color: #6b7280; font-size: 14px; margin: 0;';
                        menu.append(message);
                    }

                    filtered.forEach((option) => {
                        const button = document.createElement('button');
                        const label = document.createElement('span');

                        button.type = 'button';
                        button.style.cssText = 'display: block; width: 100%; min-width: 0; padding: 20px; cursor: pointer; font-size: 14px; color: #374151; border: 0; border-bottom: 1px solid #f3f4f6; background: transparent; text-align: left; outline: none;';
                        label.className = 'block truncate';
                        label.textContent = option.name + (option.count ? ' (' + option.count + ')' : '');
                        button.append(label);
                        button.addEventListener('mouseenter', () => { button.style.backgroundColor = '#f3f4f6'; });
                        button.addEventListener('mouseleave', () => { button.style.backgroundColor = 'transparent'; });
                        button.addEventListener('click', () => {
                            input.value = option.name;
                            close();

                            if (Array.isArray(option.user_ids)) {
                                root.dispatchEvent(new CustomEvent('group-selection', { detail: { userIds: option.user_ids } }));
                                return;
                            }

                            const checkbox = selectionList.querySelector('[data-collaborator-id="' + option.id + '"]');
                            if (checkbox && !checkbox.checked) checkbox.click();
                        });
                        menu.append(button);
                    });

                    menu.style.display = 'block';
                };

                input.addEventListener('focus', render);
                input.addEventListener('click', render);
                input.addEventListener('input', render);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') close();
                });

                document.addEventListener('click', (event) => {
                    if (!search.contains(event.target)) close();
                });
            });

            refresh();
        };

        const initialise = () => document.querySelectorAll('[data-group-selection-root]').forEach(initialiseGroupReport);

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialise, { once: true });
        else initialise();
        document.addEventListener('livewire:navigated', initialise);

        document.addEventListener('livewire:init', () => {
            window.Livewire?.hook('morph.updated', initialise);
        }, { once: true });
    })();
</script>
