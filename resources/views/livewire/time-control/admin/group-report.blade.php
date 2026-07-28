@php
    $fmt = fn (int $seconds) => sprintf('%02d:%02d:%02d', intdiv(max(0, $seconds), 3600), intdiv(max(0, $seconds) % 3600, 60), max(0, $seconds) % 60);
    $usersByArea = $groupUsers->groupBy('area_name');
    $positions = $groupUsers->filter(fn (array $user) => $user['position_id'])->unique('position_id')->sortBy('position_name');
    $superiors = $groupUsers->flatMap(fn (array $user) => $user['superiors'])->unique('id')->sortBy('name');
    $areaOptions = $usersByArea->map(fn ($users, $name) => ['id' => $users->first()['area_id'], 'name' => $name, 'count' => $users->count()])->filter(fn ($area) => $area['id'])->values();
    $positionOptions = $positions->map(fn ($position) => ['id' => $position['position_id'], 'name' => $position['position_name']])->values();
    $superiorOptions = $superiors->map(fn ($superior) => ['id' => $superior['id'], 'name' => $superior['name']])->values();
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
</style>

<div class="w-full bg-[#f4f4f4]" style="overflow-x: auto; padding: 32px 40px 40px;">

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
            <div style="display: flex; align-items: center; gap: 30px; white-space: nowrap; flex-shrink: 0;">
                <button wire:click="exportGroup('pdf')" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display: flex; align-items: center; gap: 15px; padding: 0; border: none; background-color: transparent; color: #6b7280; font-size: 15px; cursor: pointer; transition: all 0.2s; hover:color: #1A3A6B; white-space: nowrap; disabled:opacity-40;">
                    <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descargar PDF
                </button>
            </div>
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
                            {{ $selectedGroupUsers->count() }} seleccionados
                        </span>
                    </div>

                </div>
            </div>

            <!-- Filtros de fechas -->
            <div style="background-color: transparent; margin-top: 24px; min-width: max-content;">

                <form wire:submit.prevent="generateGroupReport" style="display: flex; flex-wrap: nowrap; align-items: flex-end; gap: 30px;">

                    <div style="flex: 0 0 auto;">
                        <label for="from" style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;">
                            Desde
                        </label>
                        <input id="from" type="date" wire:model.defer="from" style="height: 50px; width: 180px; border-radius: 0px; border: 1px solid #d1d5db; background-color: transparent; padding: 0 20px; font-size: 15px; color: #374151; transition: all 0.2s; outline: none; flex-shrink: 0;" onfocus="this.style.borderColor='#1A3A6B'; this.style.boxShadow='0 0 0 4px rgba(26,58,107,0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                    </div>

                    <div style="flex: 0 0 auto;">
                        <label for="to" style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;">
                            Hasta
                        </label>
                        <input id="to" type="date" wire:model.defer="to" style="height: 50px; width: 180px; border-radius: 0px; border: 1px solid #d1d5db; background-color: transparent; padding: 0 20px; font-size: 15px; color: #374151; transition: all 0.2s; outline: none; flex-shrink: 0;" onfocus="this.style.borderColor='#1A3A6B'; this.style.boxShadow='0 0 0 4px rgba(26,58,107,0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" style="display: inline-flex; height: 50px; align-items: center; justify-content: center; gap: 8px; border-radius: 0px; background-color: #1A3A6B; padding: 0 28px; font-size: 15px; font-weight: 600; color: white; box-shadow: 0 4px 6px -1px rgba(26,58,107,0.2); border: none; cursor: pointer; transition: all 0.2s; flex-shrink: 0; disabled:opacity-50;" onmouseover="this.style.backgroundColor='#15305a'" onmouseout="this.style.backgroundColor='#1A3A6B'">
                        <svg style="height: 20px; width: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Generar Reporte
                    </button>

                </form>

            </div>

        </div>

        <!-- ============================================================ -->
        <!-- LISTA DE COLABORADORES - ÁREA PUNTEADA                       -->
        <!-- ============================================================ -->
        <div class="group-scrollbar" style="position: relative; margin-top: 30px; margin-bottom: 30px; background-color: transparent; overflow: hidden; max-height: 600px; overflow-y: auto; border: 2px dashed #9ca3af; border-radius: 12px; background-color: #ffffff;">

            <!-- Barra superior con filtros de búsqueda - FONDO TRANSPARENTE/BLANCO CON BLUR -->
            <div style="position: sticky; top: 0; z-index: 10; display: flex; align-items: center; gap: 20px; padding: 16px 20px; background-color: rgba(255, 255, 255, 0.5); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(229, 231, 235, 0.15);">

                <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; white-space: nowrap;">Filtrar por:</span>
                
                <x-group-search-filter label="Área..." :options="$areaOptions" select-method="selectCollaboratorsByArea" count-key="count" empty-message="No se encontraron áreas" />
                <x-group-search-filter label="Puesto..." :options="$positionOptions" select-method="selectCollaboratorsByPosition" empty-message="No se encontraron puestos" />
                <x-group-search-filter label="Jefe directo..." :options="$superiorOptions" select-method="selectCollaboratorsBySuperior" empty-message="No se encontraron jefes" />

            </div>

            <!-- Contenido con padding - CADA CONTENEDOR CON PADDING DE 20px -->
            <div style="padding: 20px;">

                @forelse ($usersByArea as $areaName => $areaUsers)
                    <div style="border: 1px solid #e5e7eb; margin-bottom: 20px; border-radius: 10px; overflow: hidden; background-color: #fafafa;">

                        <div style="background-color: #f3f4f6; padding: 10px 16px; font-size: 14px; font-weight: 600; color: #374151; display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb;">
                            <span>{{ $areaName }}</span>
                            <span style="color: #9ca3af; font-weight: 400;">{{ $areaUsers->count() }} colaboradores</span>
                        </div>

                        <!-- Contenedor interno con padding de 20px -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 20px; background-color: #ffffff;">
                            @foreach ($areaUsers as $user)
                                <label style="display: flex; align-items: center; gap: 20px; font-size: 14px; color: #374151; background-color: #fafafa; border-radius: 10px; cursor: pointer; padding: 20px; transition: all 0.15s;"
                                    onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.borderRadius='4px'; this.style.padding='4px 8px'; this.style.margin='0 -8px';"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.padding='4px 0'; this.style.margin='0';">
                                    <input type="checkbox" wire:model.defer="selectedCollaboratorIds" value="{{ $user['id'] }}" style="border-radius: 4px; border: 1px solid #d1d5db; accent-color: #1A3A6B; width: 16px; height: 16px; flex-shrink: 0;" />
                                    <span>
                                        {{ $user['name'] }}
                                        <small style="display: block; font-size: 12px; color: #9ca3af; margin-top: 5px;">{{ $user['position_name'] }}</small>
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
                    <button type="button" wire:click="selectAllCollaborators" 
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 0; border: none; background-color: transparent; color: #1A3A6B; font-size: 14px; font-weight: 600; cursor: pointer; transition: color 0.2s; white-space: nowrap;"
                        onmouseover="this.style.color='#15305a'"
                        onmouseout="this.style.color='#1A3A6B'">
                        <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Seleccionar todos
                    </button>
                    <button type="button" wire:click="clearGroupSelection" 
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
        <!-- BOTONES DE EXPORTACIÓN                                        -->
        <!-- ============================================================ -->
        <div style="padding: 0 0 30px 0; background-color: transparent; overflow: hidden; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-bottom: 2px solid #e5e7eb;">
            <div style="font-size: 14px; color: #6b7280;">Las selecciones se aplican sólo al generar el informe.</div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button wire:click="exportGroup('csv')" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display: inline-flex; height: 44px; align-items: center; justify-content: center; gap: 8px; border-radius: 0px; background-color: #059669; padding: 0 24px; font-size: 14px; font-weight: 600; color: white; border: none; cursor: pointer; transition: all 0.2s; flex-shrink: 0; disabled:opacity-40;" onmouseover="this.style.backgroundColor='#047857'" onmouseout="this.style.backgroundColor='#059669'">
                    <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    CSV
                </button>
                <button wire:click="exportGroup('txt')" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display: inline-flex; height: 44px; align-items: center; justify-content: center; gap: 8px; border-radius: 0px; background-color: #374151; padding: 0 24px; font-size: 14px; font-weight: 600; color: white; border: none; cursor: pointer; transition: all 0.2s; flex-shrink: 0; disabled:opacity-40;" onmouseover="this.style.backgroundColor='#1f2937'" onmouseout="this.style.backgroundColor='#374151'">
                    <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    TXT
                </button>
                <button wire:click="exportGroup('pdf')" @disabled(! $groupReportIsCurrent || $reportedGroupUsers->isEmpty()) style="display: inline-flex; height: 44px; align-items: center; justify-content: center; gap: 8px; border-radius: 0px; background-color: #dc2626; padding: 0 24px; font-size: 14px; font-weight: 600; color: white; border: none; cursor: pointer; transition: all 0.2s; flex-shrink: 0; disabled:opacity-40;" onmouseover="this.style.backgroundColor='#b91c1c'" onmouseout="this.style.backgroundColor='#dc2626'">
                    <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                </button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- MÉTRICAS PRINCIPALES                                          -->
        <!-- ============================================================ -->
        @if($groupReportIsCurrent && !$reportedGroupUsers->isEmpty())
        <div style="padding: 30px 0 0 0; background-color: transparent; overflow: hidden;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

                <!-- Horas efectivas del grupo -->
                <div style="border: 1px solid #e5e7eb; border-radius: 12px; background-color: #f9fafb; padding: 20px 24px;">
                    <p style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 600;">Horas efectivas del grupo</p>
                    <p style="margin-top: 4px; font-size: 28px; font-family: monospace; font-weight: 700; color: #111827;">{{ $fmt($groupData['total']) }}</p>
                </div>

                <!-- Cierres automáticos -->
                <div style="border: 1px solid #fecaca; border-radius: 12px; background-color: #fef2f2; padding: 20px 24px;">
                    <p style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #ef4444; font-weight: 600;">Cierres automáticos</p>
                    <p style="margin-top: 4px; font-size: 28px; font-family: monospace; font-weight: 700; color: #dc2626;">{{ $groupData['autoClosedCount'] }}</p>
                </div>

            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DISTRIBUCIONES                                                -->
        <!-- ============================================================ -->
        <div style="padding: 30px 0 0 0; background-color: transparent; overflow: hidden;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

                @foreach (['Por colaborador' => $groupData['byCollaborator'], 'Por cliente' => $groupData['byCustomer'], 'Por puesto profesional' => $groupData['byPosition'], 'Por área física' => $groupData['byArea']] as $title => $rows)
                    <div style="border: 1px solid #e5e7eb; border-radius: 12px; background-color: white; padding: 16px 20px;">
                        <h2 style="font-weight: 600; color: #374151; margin-bottom: 12px; font-size: 15px;">{{ $title }}</h2>
                        @forelse ($rows as $row)
                            <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px; padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                                <span style="color: #374151;">{{ $row['name'] }}</span>
                                <span style="font-family: monospace; font-size: 14px; color: #111827; font-weight: 500;">{{ $fmt($row['seconds']) }}</span>
                            </div>
                        @empty
                            <p style="font-size: 14px; color: #6b7280;">Selecciona colaboradores para consultar el consolidado.</p>
                        @endforelse
                    </div>
                @endforeach

            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DETALLE DE ACTIVIDADES                                        -->
        <!-- ============================================================ -->
        <div style="padding: 30px 0 0 0; background-color: transparent; overflow: hidden;">
            <details style="border: 1px solid #e5e7eb; border-radius: 12px; background-color: #f9fafb; padding: 16px 20px;">
                <summary style="cursor: pointer; font-weight: 600; color: #374151; font-size: 15px; outline: none;">
                    Ver detalle de actividades ({{ $groupData['entries']->count() }} registros)
                </summary>
                <div style="margin-top: 16px;">
                    <x-time-activity-detail :columns="$groupActivityDetail['columns']" :groups="$groupActivityDetail['groups']" />
                </div>
            </details>
        </div>
        @endif

    </div>
</div>