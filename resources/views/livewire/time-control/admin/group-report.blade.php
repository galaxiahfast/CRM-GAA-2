@php
    $fmt = fn (int $seconds) => sprintf('%02d:%02d:%02d', intdiv(max(0, $seconds), 3600), intdiv(max(0, $seconds) % 3600, 60), max(0, $seconds) % 60);
    $usersByArea = $groupUsers->groupBy('area_name');
    $superiors = $groupUsers->flatMap(fn (array $user) => $user['superiors'])->unique('id')->sortBy('name');
    $areaOptions = $usersByArea->map(fn ($users, $name) => ['id' => $users->first()['area_id'], 'name' => $name, 'count' => $users->count(), 'user_ids' => $users->pluck('id')->map(fn ($id) => (int) $id)->values()->all()])->filter(fn ($area) => $area['id'])->values();
    $superiorOptions = $superiors->map(fn ($superior) => ['id' => $superior['id'], 'name' => $superior['name'], 'user_ids' => $groupUsers->filter(fn (array $user) => collect($user['superiors'])->contains('id', $superior['id']))->pluck('id')->map(fn ($id) => (int) $id)->values()->all()])->values();
    $userOptions = $groupUsers->map(fn ($user) => ['id' => $user['id'], 'name' => $user['name']])->values();
@endphp

<style>
    .group-filter-scrollbar::-webkit-scrollbar,
    .group-scrollbar::-webkit-scrollbar { width: 6px !important; height: 6px !important; }
    .group-filter-scrollbar::-webkit-scrollbar-track,
    .group-scrollbar::-webkit-scrollbar-track { background: #f8fafc !important; }
    .group-filter-scrollbar::-webkit-scrollbar-thumb,
    .group-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B !important; border-radius: 9999px !important; }
    .group-filter-scrollbar,
    .group-scrollbar { scrollbar-width: thin; scrollbar-color: #1A3A6B #f8fafc; }
    @keyframes group-report-spin { to { transform: rotate(360deg); } }
</style>

<div
    data-group-selection-root
    data-selected-ids='@json(array_values(array_map('intval', $selectedCollaboratorIds)))'
    data-reported-ids='@json(array_values(array_map('intval', $reportedCollaboratorIds)))'
    data-report-current="{{ $groupReportIsCurrent ? 'true' : 'false' }}"
    class="w-full bg-[#f4f4f4]"
    style="overflow-x: auto; padding: 32px 40px 40px;"
>

    <!-- Contenedor interno con min-width -->
    <div style="min-width: 800px; padding: 0; margin: 0; width: 100%;">

        <!-- Header Superior con Migas de Pan -->
        <div style="padding: 0 0 40px 0; border-bottom: 2px solid #e5e7eb; background-color: transparent; display: flex; align-items: center; justify-content: space-between; min-width: max-content; overflow: hidden; gap: 80px;">
            <div style="display: flex; align-items: center; gap: 15px; font-size: 15px; color: #6b7280; white-space: nowrap; flex-shrink: 0;">
                <span style="font-weight: 500;">Actividades</span>
                <span style="color: #d1d5db; font-weight: 300;">></span>
                <span style="font-weight: 500;">Control de Horas</span>
                <span style="color: #d1d5db; font-weight: 300;">></span>
                <span style="font-weight: 500;">Supervisión de Horas</span>
                <span style="color: #d1d5db; font-weight: 300;">></span>
                <span style="color: #1A3A6B; font-weight: 600;">Informe General</span>
            </div>

            <!-- Botones de acción -->
        </div>

        <!-- Header principal -->
        <div style="background-color: transparent; padding: 40px 0 0 0; overflow: hidden; min-width: max-content;">

            <!-- Encabezado -->
            <div style="border-bottom: none; min-width: max-content;">
                <div style="display: flex; flex-wrap: nowrap; align-items: flex-start; justify-content: space-between; gap: 32px;">

                    <div style="max-width: 672px; flex-shrink: 0;">

                        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">

                            <div style="display: flex; height: 56px; width: 56px; align-items: center; justify-content: center; border-radius: 0px; background-color: rgba(26, 58, 107, 0.1); flex-shrink: 0;">
                                <svg style="height: 28px; width: 28px; color: #1A3A6B; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </div>

                            <div>
                                <h1 style="font-size: 24px; font-weight: 700; letter-spacing: -0.025em; color: #111827; white-space: nowrap;">
                                    Informe General de Horas
                                </h1>

                                <p style="font-size: 15px; color: #6b7280; white-space: nowrap;">
                                    Selecciona colaboradores y consulta su consolidado en el periodo.
                                </p>
                            </div>

                        </div>

                        <p style="max-width: 672px; font-size: 15px; line-height: 28px; color: #6b7280;">
                            Visualización consolidada del tiempo trabajado por colaboradores,<br>
                            distribuido por cliente, puesto profesional, área física y actividades.
                        </p>

                    </div>

                    <!-- Contador de seleccionados -->
                    <div style="flex-shrink: 0; margin-top: 16px;">
                        <span style="display: inline-flex; align-items: center; border-radius: 9999px; background-color: rgba(26, 58, 107, 0.1); padding: 8px 20px; font-size: 14px; font-weight: 600; color: #1A3A6B; white-space: nowrap;">
                            <span data-selected-count>{{ $selectedGroupUsers->count() }}</span>&nbsp;seleccionados
                        </span>
                    </div>

                </div>
            </div>

            <!-- Filtros de fechas -->
        </div>

        <!-- ============================================================ -->
        <!-- LISTA DE COLABORADORES - ÁREA PUNTEADA                       -->
        <!-- ============================================================ -->
        <div wire:ignore data-selection-list class="group-scrollbar" style="position: relative; margin-top: 30px; margin-bottom: 30px; background-color: transparent; overflow: hidden; max-height: 600px; overflow-y: auto; overscroll-behavior: contain; border: 2px dashed #9ca3af; border-radius: 12px; background-color: #F4F4F4;">

            <!-- Barra superior con filtros de búsqueda -->
            <div style="position: sticky; top: 0; z-index: 10; display: inline-flex; align-items: center; gap: 20px; padding: 20px; background-color: rgba(255, 255, 255, 0.5); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(229, 231, 235, 0.15); border-radius: 0 0 12px 0; width: auto;">

                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Filtrar por:</span>
                
                <x-group-search-filter label="Área..." :options="$areaOptions" count-key="count" empty-message="No se encontraron áreas" />
                <x-group-search-filter label="Usuario / Colaborador..." :options="$userOptions" empty-message="No se encontraron colaboradores" />
                <x-group-search-filter label="Jefe directo..." :options="$superiorOptions" empty-message="No se encontraron jefes" />

            </div>

            <!-- Contenido con padding -->
            <div style="padding: 0px 20px 0px 20px;">

                @forelse ($usersByArea as $areaName => $areaUsers)
                    @php($areaUserIds = $areaUsers->pluck('id')->map(fn ($id) => (int) $id)->all())
                    <div style="border: 1px solid #e5e7eb; margin-bottom: {{ $loop->last ? '0px' : '20px' }}; border-radius: 10px; overflow: hidden; background-color: #fafafa;">

                        <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #374151; display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb;">
                            <label style="display: flex; align-items: center; gap: 20px; min-width: 0; flex: 1; cursor: pointer;">
                                <input data-area-checkbox data-user-ids='@json($areaUserIds)' type="checkbox" class="rounded border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0" style="border-radius: 4px; border: 1px solid #d1d5db; accent-color: #1A3A6B; width: 16px; height: 16px; flex-shrink: 0; outline: none; box-shadow: none;" />
                                <span class="min-w-0 flex-1 truncate">{{ $areaName }}</span>
                            </label>
                            <span style="color: #9ca3af; font-weight: 400;">{{ $areaUsers->count() }} colaboradores</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 20px; background-color: #ffffff;">
                            @foreach ($areaUsers as $user)
                                <label style="display: flex; align-items: center; gap: 20px; font-size: 14px; color: #374151; background-color: #fafafa; border-radius: 10px; cursor: pointer; padding: 20px; transition: background-color 0.15s;"
                                    onmouseover="this.style.backgroundColor='#f3f4f6';"
                                    onmouseout="this.style.backgroundColor='#fafafa';">
                                    <input data-area-collaborator data-collaborator-id="{{ $user['id'] }}" type="checkbox" wire:model.defer="selectedCollaboratorIds" value="{{ $user['id'] }}" class="focus:outline-none focus:ring-0 focus:ring-offset-0" style="border-radius: 4px; border: 1px solid #d1d5db; accent-color: #1A3A6B; width: 16px; height: 16px; flex-shrink: 0; outline: none; box-shadow: none;" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate">{{ $user['name'] }}</span>
                                        <small class="block truncate" style="font-size: 12px; color: #9ca3af; margin-top: 5px;">{{ $user['position_name'] }}</small>
                                    </span>
                                </label>
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

            <!-- Botones fijos abajo a la derecha -->
            <div style="position: sticky; bottom: 0; z-index: 10; display: flex; justify-content: flex-end; padding: 20px; background-color: rgba(255, 255, 255, 0.4); backdrop-filter: blur(8px); border-top: 1px solid rgba(229, 231, 235, 0.1); width: fit-content; margin-left: auto; border-radius: 12px 0 0 0;">
                <div style="display: flex; gap: 24px; background-color: transparent;">
                    <button type="button" data-select-all
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 0; border: none; background-color: transparent; color: #1A3A6B; font-size: 14px; font-weight: 600; cursor: pointer; transition: color 0.2s; white-space: nowrap;"
                        onmouseover="this.style.color='#15305a'"
                        onmouseout="this.style.color='#1A3A6B'">
                        <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Seleccionar todos
                    </button>
                    <button type="button" data-clear-selection
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 0; border: none; background-color: transparent; color: #6b7280; font-size: 14px; font-weight: 500; cursor: pointer; transition: color 0.2s; white-space: nowrap;"
                        onmouseover="this.style.color='#374151'"
                        onmouseout="this.style.color='#6b7280'">
                        <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Deseleccionar todos
                    </button>
                </div>
            </div>

        </div>

        <!-- ============================================================ -->
        <!-- BOTONES DE EXPORTACIÓN Y REPORTE                             -->
        <!-- ============================================================ -->
        <form data-report-form style="display: flex; align-items: flex-end; gap: 20px; margin: 0 0 24px; min-width: max-content; padding: 20px 24px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 1px 2px rgba(15,23,42,0.05);">
            <div style="flex: 0 0 auto;">
                <label for="from" style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;">Desde</label>
                <input id="from" type="date" wire:model.defer="from" style="height: 48px; width: 180px; border: 1px solid #d1d5db; border-radius: 12px; background-color: #ffffff; padding: 0 16px; font-size: 14px; color: #374151; outline: none;">
            </div>
            <div style="flex: 0 0 auto;">
                <label for="to" style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;">Hasta</label>
                <input id="to" type="date" wire:model.defer="to" style="height: 48px; width: 180px; border: 1px solid #d1d5db; border-radius: 12px; background-color: #ffffff; padding: 0 16px; font-size: 14px; color: #374151; outline: none;">
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="generateGroupReport" style="display: inline-flex; height: 48px; align-items: center; justify-content: center; border-radius: 12px; background-color: #1A3A6B; padding: 0 28px; font-size: 14px; font-weight: 600; color: white; border: none; cursor: pointer; box-shadow: 0 1px 2px rgba(15,23,42,0.12); transition: background-color .2s; disabled:opacity-50;" onmouseover="this.style.backgroundColor='#15305a'" onmouseout="this.style.backgroundColor='#1A3A6B'">Generar Reporte</button>
        </form>

        <div wire:loading.flex wire:target="generateGroupReport" style="display: none; min-height: 220px; align-items: center; justify-content: center; flex-direction: column; gap: 14px; border: 1px solid #dbe4f0; border-radius: 16px; background: #ffffff; color: #1A3A6B; box-shadow: 0 1px 2px rgba(15,23,42,0.05);">
            <span style="width: 32px; height: 32px; border: 3px solid #dbe4f0; border-top-color: #1A3A6B; border-radius: 9999px; animation: group-report-spin .7s linear infinite;"></span>
            <span style="font-size: 14px; font-weight: 600;">Calculando métricas y procesando datos...</span>
        </div>

        <div wire:loading.remove wire:target="generateGroupReport" wire:key="group-report-results-{{ $groupReportVersion }}">

            <!-- Contenedor con borde punteado que envuelve botones de exportación, métricas y distribuciones -->
            <div style="margin-top: 30px; border: 2px dashed #9ca3af; border-radius: 12px; padding: 20px; background-color: #F4F4F4; font-size: 15px;">

                <!-- Botones de exportación (dentro del borde punteado) -->
                <div style="padding: 0 0 20px 0; background-color: transparent; overflow: hidden; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-bottom: 2px solid #e5e7eb;">
                    <div style="font-size: 14px; color: #6b7280;">Las selecciones se aplican sólo al generar el informe.</div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button data-export-individual wire:click="exportSelectedIndividualReport" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->count() !== 1) style="display: inline-flex; height: 48px; align-items: center; justify-content: center; gap: 8px; border: 1px solid #1A3A6B; border-radius: 12px; background: transparent; color: #1A3A6B; padding: 0 18px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color .2s; disabled:opacity-40;" onmouseover="this.style.backgroundColor='#eef2f7'" onmouseout="this.style.backgroundColor='transparent'">Descargar Individual</button>
                        <button data-export-group wire:click="exportSelectedIndividualBatch" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display: inline-flex; height: 48px; align-items: center; justify-content: center; gap: 8px; border: 1px solid #1A3A6B; border-radius: 12px; background: #1A3A6B; color: white; padding: 0 18px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(15,23,42,0.12); transition: background-color .2s; disabled:opacity-40;" onmouseover="this.style.backgroundColor='#15305a'" onmouseout="this.style.backgroundColor='#1A3A6B'">Descargar Grupal</button>
                        <button data-export-general wire:click="exportSelectedGeneralReport" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display: inline-flex; height: 48px; align-items: center; justify-content: center; gap: 8px; border: 1px solid #1A3A6B; border-radius: 12px; background: transparent; color: #1A3A6B; padding: 0 18px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color .2s; disabled:opacity-40;" onmouseover="this.style.backgroundColor='#eef2f7'" onmouseout="this.style.backgroundColor='transparent'">Descargar General</button>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- MÉTRICAS PRINCIPALES (2 bloques)                              -->
                <!-- ============================================================ -->
                <div style="padding: 20px 0 0 0; background-color: transparent; overflow: hidden;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

                        <!-- Horas efectivas del grupo -->
                        <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background-color: #fafafa;">
                            <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                Horas efectivas del grupo
                            </div>
                            <div style="padding: 16px; background-color: #ffffff;">
                                <p style="font-family: monospace; font-size: 24px; font-weight: 700; color: #111827; margin: 0;">{{ $fmt($groupData['total']) }}</p>
                            </div>
                        </div>

                        <!-- Cierres automáticos -->
                        <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background-color: #fafafa;">
                            <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #ef4444; border-bottom: 1px solid #e5e7eb;">
                                Cierres automáticos
                            </div>
                            <div style="padding: 16px; background-color: #ffffff;">
                                <p style="font-family: monospace; font-size: 24px; font-weight: 700; color: #dc2626; margin: 0;">{{ $groupData['autoClosedCount'] }}</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- DISTRIBUCIONES (4 bloques)                                    -->
                <!-- ============================================================ -->
                <div style="padding: 30px 0 0 0; background-color: transparent; overflow: hidden;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

                        @foreach (['Por colaborador' => $groupData['byCollaborator'], 'Por cliente' => $groupData['byCustomer'], 'Por puesto profesional' => $groupData['byPosition'], 'Por área física' => $groupData['byArea']] as $title => $rows)
                            <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background-color: #fafafa;">
                                <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">
                                    {{ $title }}
                                </div>
                                <div style="padding: 12px 16px; background-color: #ffffff; max-height: 224px; overflow-y: auto; overscroll-behavior: contain;">
                                    @forelse ($rows as $row)
                                        <div style="display: flex; justify-content: space-between; gap: 12px; min-width: 0; font-size: 14px; padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                                            <span class="truncate" style="min-width: 0; flex: 1; color: #374151;">{{ $row['name'] }}</span>
                                            <span style="flex: 0 0 auto; font-family: monospace; font-size: 14px; color: #111827; font-weight: 500;">{{ $fmt($row['seconds']) }}</span>
                                        </div>
                                    @empty
                                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Sin datos.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- DETALLE DE ACTIVIDADES (1 bloque)                             -->
                <!-- ============================================================ -->
                <div style="padding: 30px 0 0 0; background-color: transparent; overflow: hidden;">
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background-color: #fafafa;">
                        <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">
                            Detalle de actividades
                            <span style="font-weight: 400; color: #6b7280; font-size: 13px; margin-left: 8px;">({{ $groupData['entries']->count() }} registros)</span>
                        </div>
                        <div style="padding: 16px; background-color: #ffffff;">
                            @if ($groupActivityDetail['groups'] === [])
                                <p style="font-size: 14px; color: #6b7280;">Sin registros en el periodo.</p>
                            @else
                                <x-time-activity-detail :columns="$groupActivityDetail['columns']" :groups="$groupActivityDetail['groups']" />
                            @endif
                        </div>
                    </div>
                </div>

            </div> <!-- Fin del contenedor con borde punteado -->

        </div>

    </div>
</div>

<script>
    (() => {
        const initialiseGroupReport = (root) => {
            if (!root || root.dataset.selectionInitialised === 'true') return;

            root.dataset.selectionInitialised = 'true';

            const selectionList = root.querySelector('[data-selection-list]');
            const count = root.querySelector('[data-selected-count]');
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

                const selectionMatchesReport = selectedIds.size === reportedIds.size
                    && [...selectedIds].every((id) => reportedIds.has(id));
                const canExport = reportIsCurrent && selectionMatchesReport && selectedIds.size > 0;

                root.querySelector('[data-export-individual]').disabled = !canExport || selectedIds.size !== 1;
                root.querySelector('[data-export-group]').disabled = !canExport;
                root.querySelector('[data-export-general]').disabled = !canExport;
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
            window.Livewire?.hook('morph.updated', ({ el }) => {
                if (el.matches?.('[data-group-selection-root]')) initialiseGroupReport(el);
            });
        }, { once: true });
    })();
</script>