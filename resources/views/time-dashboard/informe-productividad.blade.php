@php
    $fmtSeconds = function (int $seconds) {
        $seconds = max(0, $seconds);
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    };
@endphp

<x-app-layout>
    <div id="timeDashboardRoot" class="time-dashboard-modern w-full min-h-screen bg-white text-[15px] text-zinc-700" style="overflow-x: hidden;">

        <div style="width: 100%; min-width: 0; padding: 0; margin: 0;">

            <!-- Header Superior con Migas de Pan -->
            <div class="time-dashboard-topbar" style="position: relative; padding: 50px; border-bottom: 1px solid #e4e4e7; background-color: rgba(255,255,255,.75); display: flex; align-items: center; justify-content: space-between; min-width: 0; gap: 30px;">
                <div style="display: flex; align-items: center; gap: 15px; font-size: 15px; color: #6b7280; white-space: nowrap; flex-shrink: 0;">
                    <span style="font-weight: 500;">Actividades</span>
                    <span style="color: #d1d5db; font-weight: 300;">></span>
                    <span style="font-weight: 500;">Control de Horas</span>
                    <span style="color: #d1d5db; font-weight: 300;">></span>
                    <a href="{{ route('time.dashboard') }}" style="color: #000; font-weight: 600; text-decoration: none;">Panel de control</a>
                </div>

                <div class="dashboard-view-controls" role="group" aria-label="Controles de visualización">
                    <button id="dashboardZoomOut" type="button" aria-label="Alejar panel" title="Alejar">−</button>
                    <button id="dashboardZoomReset" type="button" class="dashboard-zoom-value" aria-label="Restablecer zoom" title="Restablecer zoom">100%</button>
                    <button id="dashboardZoomIn" type="button" aria-label="Acercar panel" title="Acercar">+</button>
                    <span class="dashboard-control-divider" aria-hidden="true"></span>
                    <button id="dashboardFullscreen" type="button" aria-label="Ver en pantalla completa" title="Pantalla completa">
                        <svg class="dashboard-expand-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5m13-5h5v5M8 21H3v-5m18 0v5h-5"/></svg>
                        <svg class="dashboard-compress-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8h5V3m8 0v5h5M8 21v-5H3m18 0h-5v5"/></svg>
                    </button>
                </div>

                <!-- Botones de acción -->
                <div style="display: flex; align-items: center; gap: 20px; white-space: nowrap; flex-shrink: 0;">
                    <button id="downloadPdfBtn" style="display: flex; align-items: center; gap: 10px; padding: 0; border: none; background-color: transparent; color: #000; font-size: 15px; cursor: pointer; transition: color 0.2s; white-space: nowrap;">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar PDF
                    </button>

                    <button id="printBtn" style="display: flex; align-items: center; gap: 10px; padding: 0; border: none; background-color: transparent; color: #000; font-size: 15px; cursor: pointer; transition: color 0.2s; white-space: nowrap;">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimir
                    </button>
                </div>
            </div>

            <div class="time-dashboard-panel relative m-[50px] overflow-hidden rounded-xl border border-zinc-200 bg-white p-[20px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
            <!-- Header principal -->
            <div class="time-dashboard-intro" style="display: grid; grid-template-columns: 380px minmax(0, 1fr); align-items: center; gap: 40px; background-color: transparent; padding: 0; margin-bottom: 20px; overflow: visible; min-width: max-content;">

                <!-- Encabezado -->
                <div style="border-bottom: none; min-width: max-content;">
                    <div style="display: flex; flex-wrap: nowrap; align-items: flex-start; justify-content: space-between; gap: 32px;">

                        <div style="max-width: 672px; flex-shrink: 0;">

                            <div style="display: flex; align-items: center; gap: 20px;">

                                <div style="display: flex; height: 56px; width: 56px; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid #e4e4e7; background-color: rgba(255,255,255,.8); flex-shrink: 0;">
                                    <svg style="height: 28px; width: 28px; color: #000; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                </div>

                                <div class="time-dashboard-intro-copy min-w-0">
                                    <h1 style="font-size: 20px; font-weight: 600; color: #000; white-space: nowrap;">Filtros de productividad</h1>

                                    <p title="Selecciona un colaborador y un periodo para consultar resultados." style="margin-top: 5px; font-size: 15px; color: #71717a; white-space: nowrap;">
                                        Selecciona un colaborador y un periodo para consultar resultados.
                                    </p>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

                <!-- Filtros -->
                <div class="time-dashboard-filters" style="justify-self: end; background-color: transparent; margin-top: 0; min-width: max-content; border: 0; border-radius: 0; padding: 0; box-shadow: none;">

                    <form method="GET" action="{{ route('time.dashboard') }}" style="display: flex; flex-wrap: nowrap; align-items: flex-end; justify-content: flex-end; gap: 20px;">

                        @if ($selectedUser)
                            <input id="selected_user_id" type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                        @endif

                        @if ($isAdmin)
                            <div style="min-width: 260px; flex: 0 0 auto; position: relative;">
                                <label
                                    for="search"
                                    style="margin-bottom: 10px; display: block; font-size: 15px; font-weight: 600; color: #000; white-space: nowrap;"
                                >
                                    Colaborador
                                </label>

                                <div style="position: relative;">

                                    <svg style="pointer-events: none; position: absolute; left: 16px; top: 50%; height: 20px; width: 20px; transform: translateY(-50%); color: #d1d5db; flex-shrink: 0;"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                                    </svg>

                                    <input
                                        id="search"
                                        name="search"
                                        value="{{ $search }}"
                                        type="search"
                                        placeholder="Buscar por ID, nombre o correo..."
                                        autocomplete="off"
                                        style="width: 260px; border-radius: 12px; border: 1px solid #e4e4e7; background-color: white; padding: 15px 20px 15px 48px; font-size: 15px; color: #000; box-shadow: none; outline: none; flex-shrink: 0;"
                                    >

                                    <!-- ✅ DROPDOWN DE COLABORADORES -->
                                    <div id="userDropdown" style="position: absolute; top: 100%; left: 0; right: 0; margin-top: 4px; background: white; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1000; display: none;">
                                        @foreach ($users as $user)
                                            <a href="{{ route('time.dashboard', ['user_id' => $user->id, 'search' => $search, 'fecha_inicio' => $start->toDateString(), 'fecha_fin' => $end->toDateString()]) }}" 
                                            class="user-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors duration-150 border-b border-gray-100 last:border-b-0"
                                            style="text-decoration: none; color: inherit;"
                                            data-name="{{ strtolower($user->name . ' ' . ($user->last_name ?? '')) }}"
                                            data-id="{{ $user->id }}"
                                            data-email="{{ strtolower($user->email) }}">
                                                <div style="display: flex; height: 36px; width: 36px; align-items: center; justify-content: center; border-radius: 50%; background-color: #f3f4f6; color: #4b5563; font-weight: 600; font-size: 15px; flex-shrink: 0;">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p style="font-size: 15px; font-weight: 600; color: #111827; margin: 0;">
                                                        {{ trim($user->name . ' ' . ($user->last_name ?? '')) }}
                                                    </p>
                                                    <p style="font-size: 13px; color: #6b7280; margin: 0;">
                                                        ID {{ $user->id }} • {{ $user->email }}
                                                    </p>
                                                </div>
                                            </a>
                                        @endforeach
                                        
                                        @if ($users->isEmpty())
                                            <div style="padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">
                                                No se encontraron colaboradores
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div style="flex: 0 0 auto;">
                            <label
                                for="fecha_inicio"
                                style="margin-bottom: 10px; display: block; font-size: 15px; font-weight: 600; color: #000; white-space: nowrap;"
                            >
                                Fecha inicial
                            </label>

                            <input
                                id="fecha_inicio"
                                name="fecha_inicio"
                                value="{{ $start->toDateString() }}"
                                type="date"
                                style="width: 180px; border-radius: 12px; border: 1px solid #e4e4e7; background-color: white; padding: 15px 20px; font-size: 15px; color: #000; outline: none; flex-shrink: 0;"
                            >
                        </div>

                        <div style="flex: 0 0 auto;">
                            <label
                                for="fecha_fin"
                                style="margin-bottom: 10px; display: block; font-size: 15px; font-weight: 600; color: #000; white-space: nowrap;"
                            >
                                Fecha final
                            </label>

                            <input
                                id="fecha_fin"
                                name="fecha_fin"
                                value="{{ $end->toDateString() }}"
                                type="date"
                                style="width: 180px; border-radius: 12px; border: 1px solid #e4e4e7; background-color: white; padding: 15px 20px; font-size: 15px; color: #000; outline: none; flex-shrink: 0;"
                            >
                        </div>

                        <button
                            type="submit"
                            style="display: inline-flex; align-items: center; justify-content: center; gap: 10px; border-radius: 12px; background-color: #000; padding: 15px 20px; font-size: 15px; font-weight: 400; color: white; border: none; cursor: pointer; flex-shrink: 0;"
                        >

                            <svg style="height: 20px; width: 20px; flex-shrink: 0;"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                            </svg>

                            Buscar

                        </button>

                    </form>

                </div>

            </div>

            <!-- ============================================================ -->
            <!-- MENÚ DE PESTAÑAS                                              -->
            <!-- ============================================================ -->
            <section class="-mx-[20px] grid overflow-visible border-t border-zinc-200 px-[20px] pt-[20px] xl:grid-cols-[380px_minmax(0,1fr)] xl:gap-[20px]">
            <div class="border-b border-zinc-200 pb-[20px] xl:border-b-0 xl:pb-0 xl:pr-[20px]" style="background-color: transparent; overflow: hidden;">
                <div class="time-dashboard-tabs" style="display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; padding: 0; margin: 0; border: 0; background: transparent;">
                    <button class="tab-button active" style="display: flex; min-height: 150px; align-items: stretch; justify-content: space-between; gap: 20px; padding: 20px; font-size: 15px; color: white; border: 1px solid #000; border-radius: 12px; background-color: #000; cursor: pointer; transition: transform .2s, box-shadow .2s, background-color .2s; outline: none; text-align: left; margin: 0;" data-tab="tab-resumen">
                        <span style="display:flex; min-width:0; flex-direction:column; justify-content:space-between; gap:20px;"><span><strong style="display:block; font-size:20px; font-weight:600;">Resumen</strong><span style="display:block; margin-top:5px; line-height:1.5; opacity:.72;">Consulta horas, promedios y distribución general.</span></span><span style="display:inline-flex; align-items:center; gap:10px; font-weight:500;">Abrir informe <span aria-hidden="true">›</span></span></span>
                        <svg style="width:105px; height:80px; flex-shrink:0; align-self:center;" fill="none" stroke="currentColor" viewBox="0 0 120 80" aria-hidden="true"><path opacity=".2" d="M5 70h110M5 50h110M5 30h110M5 10h110"/><path stroke-width="7" stroke-linecap="round" d="M18 65V44M42 65V25M66 65V37M90 65V14"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m14 36 26-17 25 10 29-21"/></svg>
                    </button>
                    <button class="tab-button" style="display:flex; min-height:150px; align-items:stretch; justify-content:space-between; gap:20px; padding:20px; font-size:15px; color:#000; border:1px solid #e4e4e7; border-radius:12px; background-color:#fff; cursor:pointer; transition:transform .2s, box-shadow .2s, background-color .2s; outline:none; text-align:left; margin:0;" data-tab="tab-detalles">
                        <span style="display:flex; min-width:0; flex-direction:column; justify-content:space-between; gap:20px;"><span><strong style="display:block; font-size:20px; font-weight:600;">Detalle</strong><span style="display:block; margin-top:5px; line-height:1.5; opacity:.62;">Explora cada actividad o cliente por separado.</span></span><span style="display:inline-flex; align-items:center; gap:10px; font-weight:500;">Abrir informe <span aria-hidden="true">›</span></span></span>
                        <svg style="width:105px; height:80px; flex-shrink:0; align-self:center;" fill="none" stroke="currentColor" viewBox="0 0 120 80" aria-hidden="true"><circle cx="42" cy="40" r="27" stroke-width="10" opacity=".18"/><path stroke-width="10" stroke-linecap="round" d="M42 13a27 27 0 0 1 23 41"/><path stroke-width="3" stroke-linecap="round" d="m64 60 18 14"/></svg>
                    </button>
                    <button class="tab-button" style="display:flex; min-height:150px; align-items:stretch; justify-content:space-between; gap:20px; padding:20px; font-size:15px; color:#000; border:1px solid #e4e4e7; border-radius:12px; background-color:#fff; cursor:pointer; transition:transform .2s, box-shadow .2s, background-color .2s; outline:none; text-align:left; margin:0;" data-tab="tab-nueva">
                        <span style="display:flex; min-width:0; flex-direction:column; justify-content:space-between; gap:20px;"><span><strong style="display:block; font-size:20px; font-weight:600;">Cliente y actividad</strong><span style="display:block; margin-top:5px; line-height:1.5; opacity:.62;">Compara una combinación específica por día.</span></span><span style="display:inline-flex; align-items:center; gap:10px; font-weight:500;">Abrir informe <span aria-hidden="true">›</span></span></span>
                        <svg style="width:105px; height:80px; flex-shrink:0; align-self:center;" fill="none" stroke="currentColor" viewBox="0 0 120 80" aria-hidden="true"><path opacity=".2" d="M5 70h110M5 50h110M5 30h110M5 10h110"/><path stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="m8 62 20-21 18 9 22-31 18 17 25-28"/><circle cx="28" cy="41" r="4" fill="currentColor" stroke="none"/><circle cx="68" cy="19" r="4" fill="currentColor" stroke="none"/><circle cx="111" cy="8" r="4" fill="currentColor" stroke="none"/></svg>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- CONTENEDORES DE PESTAÑAS                                     -->
            <!-- ============================================================ -->
            <div class="min-w-0 self-center space-y-[20px]" style="width: 100%; min-width: 0;">

                <!-- ==================== TAB 1: RESUMEN ==================== -->
                <div id="tab-resumen" class="tab-content space-y-6">

                    <!-- Gráfica 1: Tiempo trabajado por día -->
                    <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                        <div class="flex items-start gap-3">
                            <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            <div class="dashboard-chart-copy">
                                <p class="text-[15px] font-semibold text-black" style="margin-bottom: 8px;">Tiempo trabajado por día</p>
                                <p class="text-[15px] text-gray-500">
                                    @if ($selectedUser)
                                        Barras de horas diarias y línea de promedio del rango seleccionado.
                                    @else
                                        Busca y selecciona un colaborador para visualizar la gráfica.
                                    @endif
                                </p>
                            </div>
                            <button type="button" class="dashboard-chart-refresh" data-refresh-chart="worked" aria-label="Actualizar tiempo trabajado por día" title="Actualizar datos">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg><span>Actualizar</span>
                            </button>
                        </div>
                        <div class="dashboard-chart-frame">
                            <canvas id="workedTimeChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfica 2: Distribución por cliente -->
                        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="flex items-start gap-3 p-4">
                                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <div class="dashboard-chart-copy">
                                    <p class="text-[15px] mb-[8px] font-semibold text-black">Distribución por cliente</p>
                                    <p class="text-[15px] text-gray-500">
                                        Porcentaje de tiempo trabajado por cada cliente en el periodo seleccionado.
                                    </p>
                                </div>
                                <button type="button" class="dashboard-chart-refresh" data-refresh-chart="clients" aria-label="Actualizar distribución por cliente" title="Actualizar datos">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg><span>Actualizar</span>
                                </button>
                            </div>
                            <div class="dashboard-chart-frame client-distribution-frame">
                                <canvas id="clientPieChart"></canvas>
                            </div>
                        </div>

                    <!-- Gráfica 3: Distribución por actividad -->
                        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="flex items-start gap-3 p-4">
                                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <div class="dashboard-chart-copy">
                                    <p class="text-[15px] mb-[8px] font-semibold text-black">Distribución por actividad</p>
                                    <p class="text-[15px] text-gray-500">
                                        Tiempo trabajado por cada actividad en el periodo seleccionado.
                                    </p>
                                </div>
                                <button type="button" class="dashboard-chart-refresh" data-refresh-chart="activities" aria-label="Actualizar distribución por actividad" title="Actualizar datos">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg><span>Actualizar</span>
                                </button>
                            </div>
                            <div class="dashboard-chart-frame activity-distribution-frame">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                </div>

                <!-- ==================== TAB 2: DETALLES ==================== -->
                <div id="tab-detalles" class="tab-content space-y-6 hidden">

                    <!-- Gráfica 4: Detalle por actividad -->
                        <div class="dashboard-detail-card dashboard-detail-card--activity rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="dashboard-detail-header">
                                <div class="dashboard-detail-heading">
                                    <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                    <div class="dashboard-chart-copy">
                                        <p class="text-[15px] font-semibold text-black">Detalle por actividad</p>
                                        <p class="mt-[5px] text-[15px] text-gray-500">Distribución diaria de la actividad seleccionada.</p>
                                    </div>
                                </div>
                                <div class="dashboard-detail-filter">
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Seleccionar actividad</label>
                                    <select id="activitySelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                        <option value="">{{ empty($activityLabels) ? 'Sin actividades disponibles' : 'Selecciona una actividad...' }}</option>
                                        @foreach ($activityLabels as $index => $label)
                                            <option value="{{ $activityIds[$index] ?? $index + 1 }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button id="refreshActivityDetailBtn" type="button" class="dashboard-chart-refresh" aria-label="Actualizar detalle por actividad" title="Actualizar datos">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg><span>Actualizar</span>
                                </button>
                            </div>
                            <div class="dashboard-chart-frame">
                                <canvas id="activityDetailChart"></canvas>
                            </div>
                        </div>

                    <!-- Gráfica 5: Detalle por cliente -->
                        <div class="dashboard-detail-card rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="dashboard-detail-header">
                                <div class="dashboard-detail-heading">
                                    <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                    <div class="dashboard-chart-copy">
                                        <p class="text-[15px] font-semibold text-black">Detalle por cliente</p>
                                        <p class="mt-[5px] text-[15px] text-gray-500">Distribución diaria del cliente seleccionado.</p>
                                    </div>
                                </div>
                                <div class="dashboard-detail-filter">
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Seleccionar cliente</label>
                                    <select id="clientSelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                        <option value="">{{ empty($clientLabels) ? 'Sin clientes disponibles' : 'Selecciona un cliente...' }}</option>
                                        @foreach ($clientLabels as $index => $label)
                                            <option value="{{ $clientIds[$index] ?? $index }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button id="refreshClientDetailBtn" type="button" class="dashboard-chart-refresh" aria-label="Actualizar detalle por cliente" title="Actualizar datos">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/></svg><span>Actualizar</span>
                                </button>
                            </div>
                            <div class="dashboard-chart-frame">
                                <canvas id="clientDetailChart"></canvas>
                            </div>
                        </div>
                </div>

                <!-- ==================== TAB 3: CLIENTE + ACTIVIDAD ==================== -->
                <div id="tab-nueva" class="tab-content space-y-6 hidden">

                    <!-- Gráfica de Cliente + Actividad -->
                    <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                        <div class="grid min-w-0 items-center gap-[20px] lg:grid-cols-[minmax(240px,1fr)_minmax(0,auto)]">
                            <div class="flex min-w-0 items-center gap-[20px]">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m5 10V5m5 14v-7m5 7V3" />
                                    </svg>
                                </span>
                                <div class="dashboard-chart-copy">
                                    <p class="text-[15px] font-semibold text-black" title="Tiempo por cliente + actividad">Tiempo por cliente + actividad</p>
                                    <p class="mt-[5px] text-[15px] text-zinc-500" title="Distribución diaria de una combinación específica.">Distribución diaria de una combinación específica.</p>
                                </div>
                            </div>
                            <div class="grid min-w-0 items-end gap-[20px] sm:grid-cols-2 lg:grid-cols-[minmax(0,220px)_minmax(0,220px)_auto]">
                                        <div class="min-w-0 w-full">
                                            <label class="mb-[10px] block text-[15px] font-semibold text-black">Cliente</label>
                                            <select id="clientActivityClientSelector" class="min-h-[56px] w-full truncate rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] text-black outline-none focus:border-zinc-400 focus:ring-0" title="Selecciona un cliente">
                                                <option value="">Todos los clientes</option>
                                                @foreach ($clientLabels as $index => $label)
                                                    <option value="{{ $clientIds[$index] ?? $index }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="min-w-0 w-full">
                                            <label class="mb-[10px] block text-[15px] font-semibold text-black">Actividad</label>
                                            <select id="clientActivityActivitySelector" class="min-h-[56px] w-full truncate rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] text-black outline-none focus:border-zinc-400 focus:ring-0" title="Selecciona una actividad">
                                                <option value="">Todas las actividades</option>
                                                @foreach ($activityLabels as $index => $label)
                                                    <option value="{{ $activityIds[$index] ?? $index + 1 }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button id="loadClientActivityBtn" class="dashboard-chart-refresh inline-flex min-h-[56px] items-center justify-center gap-[10px] self-end rounded-xl bg-black px-[20px] py-[15px] text-[15px] font-normal leading-none text-white outline-none hover:bg-zinc-800 focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5" /></svg>
                                            Actualizar
                                        </button>
                            </div>
                        </div>
                        <div class="dashboard-chart-frame flex flex-col overflow-hidden rounded-xl p-0">
                            <div class="flex shrink-0 items-center justify-center gap-[10px] pb-[10px] text-[15px] text-black">
                                <span class="h-[14px] w-[14px] shrink-0 rounded-[4px] bg-black" aria-hidden="true"></span>
                                <span id="clientActivityLegendLabel" class="max-w-[70%] truncate">Horas trabajadas</span>
                            </div>
                            <div class="min-h-0 flex-1"><canvas id="clientActivityChart"></canvas></div>
                        </div>
                    </div>
                </div>

            </div>
            </section>
            </div>

        </div>
    </div>

    <style>
        .time-dashboard-modern input:focus,
        .time-dashboard-modern select:focus,
        .time-dashboard-modern button:focus,
        .time-dashboard-modern button:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-color: transparent !important;
        }
        .time-dashboard-modern button:not(.tab-button) {
            column-gap: 10px;
        }
        .time-dashboard-modern .time-dashboard-intro-copy,
        .time-dashboard-modern .dashboard-chart-copy {
            min-width: 0;
            max-width: 100%;
            flex: 1 1 auto;
            overflow: hidden;
        }
        .time-dashboard-modern .time-dashboard-intro > div:first-child {
            box-sizing: border-box;
            width: 100%;
            min-width: 0 !important;
            padding-right: 20px;
            overflow: hidden;
        }
        .time-dashboard-modern .time-dashboard-intro > div:first-child > div,
        .time-dashboard-modern .time-dashboard-intro > div:first-child > div > div,
        .time-dashboard-modern .time-dashboard-intro > div:first-child > div > div > div {
            min-width: 0;
            max-width: 100% !important;
        }
        .time-dashboard-modern .time-dashboard-intro > div:first-child > div > div {
            width: 100%;
            flex-shrink: 1 !important;
        }
        .time-dashboard-modern .time-dashboard-intro > div:first-child > div > div > div:last-child {
            flex: 1 1 auto;
            overflow: hidden;
        }
        .time-dashboard-modern .time-dashboard-intro-copy > h1,
        .time-dashboard-modern .time-dashboard-intro-copy > p,
        .time-dashboard-modern .dashboard-chart-copy > p {
            display: block;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap !important;
            text-overflow: ellipsis;
        }
        .time-dashboard-modern select {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .time-dashboard-modern:fullscreen {
            overflow: auto;
            background: #fff;
        }
        .dashboard-view-controls {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 20;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
            transform: translate(-50%, -50%);
        }
        .dashboard-view-controls button {
            display: inline-flex;
            width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #18181b;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
        }
        .dashboard-view-controls button:hover { background: #f4f4f5; }
        .dashboard-view-controls .dashboard-zoom-value {
            width: 54px;
            font-size: 13px;
            font-weight: 600;
        }
        .dashboard-view-controls svg { width: 18px; height: 18px; }
        .dashboard-view-controls .dashboard-compress-icon { display: none; }
        .dashboard-view-controls.is-fullscreen .dashboard-expand-icon { display: none; }
        .dashboard-view-controls.is-fullscreen .dashboard-compress-icon { display: block; }
        .dashboard-control-divider { width: 1px; height: 24px; margin: 0 3px; background: #e4e4e7; }
        .dashboard-empty-chart {
            display: flex;
            width: 100%;
            height: 100%;
            min-height: 240px;
            align-items: center;
            justify-content: center;
            padding: 24px;
            border-radius: 12px;
            background: #fafafa;
            color: #71717a;
            text-align: center;
        }
        .time-dashboard-modern .dashboard-chart-frame {
            box-sizing: border-box;
            width: 100%;
            height: 360px;
            margin-top: 8px;
            padding: 0;
        }
        .time-dashboard-modern .dashboard-chart-frame > canvas,
        .time-dashboard-modern .dashboard-chart-frame > .min-h-0 {
            width: 100% !important;
            max-width: 100%;
        }
        .time-dashboard-modern .activity-distribution-frame { margin-top: 0; }
        .time-dashboard-modern .client-distribution-frame { margin-top: 10px; }
        @media (min-width: 1280px) {
            .time-dashboard-modern .time-dashboard-panel::before {
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                left: 400px;
                width: 1px;
                background: #e4e4e7;
                pointer-events: none;
            }
        }
        .time-dashboard-modern .tab-content > div {
            box-sizing: border-box;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            background: transparent !important;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .05);
        }
        .time-dashboard-modern .time-dashboard-tabs .tab-button {
            width: 100%;
        }
        .time-dashboard-modern .time-dashboard-tabs .tab-button > svg {
            width: 56px !important;
            height: 52px !important;
        }
        .time-dashboard-modern .tab-content {
            position: relative;
            min-width: 0;
            padding: 0;
        }
        .time-dashboard-modern .tab-content:not(.hidden) {
            display: grid;
            grid-template-columns: 48px minmax(0, 1fr) 48px;
            align-items: center;
            column-gap: 20px;
            row-gap: 10px;
        }
        .time-dashboard-modern .tab-content:not(.hidden) > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0 !important;
        }
        .time-dashboard-modern .tab-content > div {
            min-width: 0;
            grid-column: 2;
            grid-row: 1;
        }
        .time-dashboard-modern .tab-content > div > .flex.items-start {
            align-items: center !important;
            gap: 20px !important;
            padding: 0 !important;
        }
        .time-dashboard-modern .tab-content > div > .flex.items-start > svg {
            box-sizing: border-box;
            width: 48px !important;
            height: 48px !important;
            margin: 0 !important;
            padding: 12px;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            background: #fff;
            color: #000 !important;
        }
        .time-dashboard-modern .tab-content > div > .flex.items-start p.font-semibold {
            margin-bottom: 0 !important;
        }
        .time-dashboard-modern .tab-content > div > .flex.items-start p.font-semibold + p {
            margin-top: 5px !important;
        }
        .time-dashboard-modern .dashboard-detail-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 256px auto;
            align-items: end;
            gap: 20px;
            min-width: 0;
        }
        .time-dashboard-modern .dashboard-detail-heading {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 20px;
        }
        .time-dashboard-modern .dashboard-detail-heading > svg {
            box-sizing: border-box;
            width: 48px !important;
            height: 48px !important;
            margin: 0 !important;
            padding: 12px;
            flex: 0 0 auto;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            background: #fff;
            color: #000 !important;
        }
        .time-dashboard-modern .dashboard-detail-filter { width: 256px; min-width: 0; }
        .time-dashboard-modern .dashboard-detail-card .dashboard-chart-frame { margin-top: 0; }
        .time-dashboard-modern .dashboard-detail-card--activity .dashboard-detail-header { align-items: center; }
        .time-dashboard-modern .dashboard-chart-refresh {
            display: inline-flex;
            min-height: 44px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-left: auto;
            padding: 10px 14px;
            border: 1px solid #000;
            border-radius: 12px;
            background: #000;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }
        .time-dashboard-modern .dashboard-chart-refresh:hover { background: #27272a; }
        .time-dashboard-modern .dashboard-chart-refresh:disabled { cursor: wait; opacity: .65; }
        .time-dashboard-modern .dashboard-chart-refresh > svg {
            width: 18px !important;
            height: 18px !important;
            color: #fff !important;
            stroke: #fff !important;
        }
        .time-dashboard-modern .dashboard-chart-refresh.is-refreshing > svg { animation: dashboard-refresh-spin .8s linear infinite; }
        @keyframes dashboard-refresh-spin { to { transform: rotate(360deg); } }
        .time-dashboard-modern .graph-viewer-arrow {
            z-index: 15;
            display: inline-flex;
            width: 48px;
            height: 48px;
            align-items: center;
            justify-content: center;
            border: 1px solid #000;
            border-radius: 12px;
            background: #000;
            color: #fff;
            transition: background-color .2s, color .2s, opacity .2s;
        }
        .time-dashboard-modern .graph-viewer-arrow--prev { grid-column: 1; grid-row: 1; }
        .time-dashboard-modern .graph-viewer-arrow--next { grid-column: 3; grid-row: 1; }
        .time-dashboard-modern .graph-viewer-arrow:hover:not(:disabled) { background: #27272a; }
        .time-dashboard-modern .graph-viewer-arrow:disabled {
            border-color: #000;
            background: #000;
            color: #fff;
            cursor: not-allowed;
            opacity: 1;
        }
        .time-dashboard-modern .graph-viewer-arrow svg {
            width: 20px !important;
            height: 20px !important;
            color: currentColor !important;
            stroke: currentColor !important;
        }
        .time-dashboard-modern .graph-viewer-counter {
            grid-column: 2;
            grid-row: 2;
            margin: 0;
            text-align: center;
            color: #71717a;
            font-size: 15px;
        }
        .time-dashboard-modern .tab-content svg { color: #000 !important; }
        .time-dashboard-modern .tab-content .graph-viewer-arrow:not(:disabled) svg {
            color: #fff !important;
            stroke: #fff !important;
        }
        .time-dashboard-modern .tab-content .graph-viewer-arrow:disabled svg {
            color: #fff !important;
            stroke: #fff !important;
        }
        .time-dashboard-modern select {
            height: auto !important;
            border-color: #e4e4e7 !important;
            padding: 15px 20px !important;
            font-size: 15px !important;
            color: #000 !important;
        }
        .time-dashboard-modern #loadClientActivityBtn {
            height: auto !important;
            background: #000 !important;
            padding: 15px 20px !important;
            font-size: 15px !important;
            font-weight: 400 !important;
        }
        .time-dashboard-modern #loadClientActivityBtn:hover { background: #27272a !important; }
        .time-dashboard-modern #loadClientActivityBtn svg {
            color: #fff !important;
            stroke: #fff !important;
        }
        .time-dashboard-modern #clientActivityChart {
            border-radius: 12px;
            background: #fff;
        }
        .time-dashboard-modern #userDropdown {
            margin-top: 10px !important;
            border-color: #e4e4e7 !important;
            border-radius: 12px !important;
        }
        .time-dashboard-modern .time-dashboard-tabs .tab-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, .10);
        }
        .time-dashboard-modern .time-dashboard-tabs .tab-button:active {
            transform: translateY(0);
        }
        .time-dashboard-modern * { scrollbar-width: thin; scrollbar-color: #000 transparent; }
        .time-dashboard-modern *::-webkit-scrollbar { width: 6px; height: 6px; }
        .time-dashboard-modern *::-webkit-scrollbar-track { background: transparent; }
        .time-dashboard-modern *::-webkit-scrollbar-thumb { border-radius: 9999px; background: #000; }
        @media (max-width: 1180px) {
            .dashboard-view-controls {
                position: static;
                order: 3;
                flex: 0 0 auto;
                transform: none;
            }
            .time-dashboard-topbar { flex-wrap: wrap; }
        }
        @media (max-width: 1279px) {
            .time-dashboard-intro {
                display: block !important;
                min-width: 0 !important;
            }
            .time-dashboard-intro > div,
            .time-dashboard-filters { min-width: 0 !important; width: 100%; }
            .time-dashboard-filters { margin-top: 20px !important; }
            .time-dashboard-filters form { flex-wrap: wrap !important; justify-content: flex-start !important; }
        }
        @media (max-width: 767px) {
            .time-dashboard-topbar { padding: 20px !important; gap: 20px !important; }
            .time-dashboard-topbar > div:first-child { width: 100%; overflow-x: auto; }
            .time-dashboard-topbar > div:last-child { order: 3; width: 100%; justify-content: flex-start; overflow-x: auto; }
            .dashboard-view-controls { order: 2; }
            .time-dashboard-panel { margin: 20px !important; }
            .time-dashboard-filters form > div,
            .time-dashboard-filters input { width: 100% !important; min-width: 0 !important; }
            .time-dashboard-modern .tab-content > div { padding: 15px; }
            .time-dashboard-modern .tab-content > div > .flex.items-start,
            .time-dashboard-modern #tab-nueva > div > div:first-child { align-items: flex-start !important; }
            .time-dashboard-modern .tab-content > div > .flex.items-start > svg { width: 42px !important; height: 42px !important; padding: 10px; }
            .time-dashboard-modern .dashboard-detail-header { grid-template-columns: minmax(0, 1fr); }
            .time-dashboard-modern .dashboard-detail-filter { width: 100%; }
            .time-dashboard-modern .dashboard-detail-heading > svg { width: 42px !important; height: 42px !important; padding: 10px; }
            .time-dashboard-modern .dashboard-chart-refresh { margin-left: 0; }
            .time-dashboard-modern .tab-content:not(.hidden) {
                grid-template-columns: 42px minmax(0, 1fr) 42px;
                column-gap: 8px;
            }
            .time-dashboard-modern .graph-viewer-arrow { width: 42px; height: 42px; }
        }
    </style>

    <!-- Primero cargas Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <!-- ============================================================ -->
    <!-- JAVASCRIPT PARA PESTAÑAS Y REDIMENSIONAMIENTO DE GRÁFICAS    -->
    <!-- ============================================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.time-dashboard-intro-copy > h1, .time-dashboard-intro-copy > p, .dashboard-chart-copy > p').forEach((element) => {
                if (!element.title) element.title = element.textContent.trim();
            });

            if (typeof Chart !== 'undefined') {
                Chart.defaults.animation = false;
                Chart.register({
                    id: 'dashboardShared3dBars',
                    beforeDatasetDraw(chart, args) {
                        if (chart.canvas.id === 'clientActivityChart') return;
                        const dataset = chart.data.datasets[args.index];
                        const isBarDataset = (dataset.type || chart.config.type) === 'bar';
                        if (!isBarDataset) return;

                        const horizontal = chart.options.indexAxis === 'y';
                        const depth = 8;
                        const { ctx } = chart;
                        ctx.save();

                        args.meta.data.forEach((bar) => {
                            if (horizontal) {
                                const p = bar.getProps(['x', 'y', 'base', 'height'], true);
                                if (!Number.isFinite(p.x) || Math.abs(p.x - p.base) < 1) return;
                                const half = p.height / 2;
                                ctx.beginPath();
                                ctx.moveTo(p.x, p.y - half);
                                ctx.lineTo(p.x + depth, p.y - half - depth);
                                ctx.lineTo(p.x + depth, p.y + half - depth);
                                ctx.lineTo(p.x, p.y + half);
                                ctx.closePath();
                                ctx.fillStyle = '#3f3f46';
                                ctx.fill();
                                ctx.beginPath();
                                ctx.moveTo(p.base, p.y - half);
                                ctx.lineTo(p.base + depth, p.y - half - depth);
                                ctx.lineTo(p.x + depth, p.y - half - depth);
                                ctx.lineTo(p.x, p.y - half);
                                ctx.closePath();
                                ctx.fillStyle = '#a1a1aa';
                                ctx.fill();
                            } else {
                                const p = bar.getProps(['x', 'y', 'base', 'width'], true);
                                if (!Number.isFinite(p.y) || Math.abs(p.base - p.y) < 1) return;
                                const half = p.width / 2;
                                ctx.beginPath();
                                ctx.moveTo(p.x + half, p.y);
                                ctx.lineTo(p.x + half + depth, p.y - depth);
                                ctx.lineTo(p.x + half + depth, p.base - depth);
                                ctx.lineTo(p.x + half, p.base);
                                ctx.closePath();
                                ctx.fillStyle = '#3f3f46';
                                ctx.fill();
                                ctx.beginPath();
                                ctx.moveTo(p.x - half, p.y);
                                ctx.lineTo(p.x - half + depth, p.y - depth);
                                ctx.lineTo(p.x + half + depth, p.y - depth);
                                ctx.lineTo(p.x + half, p.y);
                                ctx.closePath();
                                ctx.fillStyle = '#a1a1aa';
                                ctx.fill();
                            }
                        });
                        ctx.restore();
                    },
                });
            }
            
            // ============================================================
            // 1. LOGICA DE PESTAÑAS - CORREGIDA (HOVER TEMPORAL + CLICK PERMANENTE)
            // ============================================================
            const tabs = document.querySelectorAll('.tab-button');
            const contents = {
                'tab-resumen': document.getElementById('tab-resumen'),
                'tab-detalles': document.getElementById('tab-detalles'),
                'tab-nueva': document.getElementById('tab-nueva')
            };

            // Variable para guardar la pestaña activa (seleccionada por click)
            let activeTabId = null;

            // Función para aplicar estilo "inactivo" (gris)
            function setInactiveStyle(btn) {
                btn.style.color = '#000';
                btn.style.backgroundColor = '#fff';
                btn.style.borderColor = '#e4e4e7';
                btn.style.borderWidth = '1px';
                btn.style.borderStyle = 'solid';
                btn.style.borderRadius = '12px';
                btn.classList.remove('active');
            }

            // Función para aplicar estilo activo monocromático
            function setActiveStyle(btn) {
                btn.style.color = '#fff';
                btn.style.backgroundColor = '#000';
                btn.style.borderColor = '#000';
                btn.style.borderWidth = '1px';
                btn.style.borderStyle = 'solid';
                btn.style.borderRadius = '12px';
                btn.classList.add('active');
            }

            // Función para actualizar todas las pestañas según el estado
            function updateTabs(hoveredTab = null) {
                tabs.forEach(btn => {
                    // Si es la pestaña activa Y no hay hover en otra pestaña
                    if (btn.dataset.tab === activeTabId && !hoveredTab) {
                        setActiveStyle(btn);
                    } 
                    // Si es la pestaña que está en hover
                    else if (hoveredTab && btn === hoveredTab) {
                        setActiveStyle(btn);
                    }
                    // Si no, estilo inactivo
                    else {
                        setInactiveStyle(btn);
                    }
                });
            }

            // Función para cambiar la pestaña activa (click)
            function switchTab(tabId) {
                // Guardar la nueva pestaña activa
                activeTabId = tabId;
                
                // Ocultar todos los contenidos
                Object.values(contents).forEach(el => {
                    if (el) el.classList.add('hidden');
                });
                
                // Mostrar el contenido seleccionado
                const target = contents[tabId];
                if (target) {
                    target.classList.remove('hidden');
                }

                // Actualizar estilos (sin hover)
                updateTabs(null);

                // Redimensionar gráficos en la pestaña visible
                if (target) {
                    const visibleCanvas = target.querySelectorAll('canvas');
                    visibleCanvas.forEach(canvas => {
                        const chart = Chart.getChart(canvas);
                        if (chart) {
                            setTimeout(() => chart.resize(), 100);
                        }
                    });
                }
            }

            // Agregar eventos a cada pestaña
            tabs.forEach(btn => {
                // Evento CLICK - cambia la pestaña permanentemente
                btn.addEventListener('click', function(e) {
                    const tabId = this.dataset.tab;
                    if (tabId) {
                        switchTab(tabId);
                    }
                });
                
                // Evento HOVER (mouse entra) - resalta temporalmente
                btn.addEventListener('mouseenter', function() {
                    // Resaltar esta pestaña (pero no cambiar la activa)
                    updateTabs(this);
                });
                
                // Evento HOVER (mouse sale) - vuelve a la pestaña activa
                btn.addEventListener('mouseleave', function() {
                    // Volver al estado original (pestaña activa)
                    updateTabs(null);
                });
            });

            // Inicializar: detectar la pestaña activa
            tabs.forEach(btn => {
                // Buscar la que tiene la clase 'active' o estilo azul
                if (btn.classList.contains('active') || btn.style.backgroundColor === 'rgb(0, 0, 0)') {
                    activeTabId = btn.dataset.tab;
                }
            });

            // Si no hay pestaña activa, activar la primera
            if (!activeTabId && tabs.length > 0) {
                activeTabId = tabs[0].dataset.tab;
            }

            // Aplicar la pestaña activa
            if (activeTabId) {
                // Asegurar que el contenido correcto está visible
                Object.values(contents).forEach(el => {
                    if (el) el.classList.add('hidden');
                });
                const target = contents[activeTabId];
                if (target) {
                    target.classList.remove('hidden');
                }
                // Aplicar estilos
                updateTabs(null);
            }

            async function runChartRefresh(button, task) {
                if (!button || button.disabled) return;
                button.disabled = true;
                button.classList.add('is-refreshing');

                try {
                    await task();
                } catch (error) {
                    console.error('No fue posible actualizar la gráfica:', error);
                } finally {
                    button.disabled = false;
                    button.classList.remove('is-refreshing');
                }
            }

            // Visor por apartado: conserva una sola gráfica visible y permite recorrerlas.
            document.querySelectorAll('.tab-content').forEach((section) => {
                const graphCards = Array.from(section.children).filter((element) => element.tagName === 'DIV');
                let graphIndex = 0;

                const previousButton = document.createElement('button');
                previousButton.type = 'button';
                previousButton.className = 'graph-viewer-arrow graph-viewer-arrow--prev';
                previousButton.setAttribute('aria-label', 'Ver gráfica anterior');
                previousButton.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6"/></svg>';

                const nextButton = document.createElement('button');
                nextButton.type = 'button';
                nextButton.className = 'graph-viewer-arrow graph-viewer-arrow--next';
                nextButton.setAttribute('aria-label', 'Ver gráfica siguiente');
                nextButton.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6"/></svg>';

                const counter = document.createElement('p');
                counter.className = 'graph-viewer-counter';
                counter.setAttribute('aria-live', 'polite');

                section.append(previousButton, nextButton, counter);

                const showGraph = (nextIndex) => {
                    if (!graphCards.length) {
                        previousButton.disabled = true;
                        nextButton.disabled = true;
                        counter.textContent = 'Sin gráficas disponibles';
                        return;
                    }

                    graphIndex = (nextIndex + graphCards.length) % graphCards.length;
                    graphCards.forEach((card, index) => {
                        card.style.display = index === graphIndex ? '' : 'none';
                    });

                    const hasMultipleGraphs = graphCards.length > 1;
                    previousButton.disabled = !hasMultipleGraphs;
                    nextButton.disabled = !hasMultipleGraphs;
                    counter.textContent = `${graphIndex + 1} de ${graphCards.length}`;

                    window.setTimeout(() => {
                        graphCards[graphIndex].querySelectorAll('canvas').forEach((chartCanvas) => {
                            Chart.getChart(chartCanvas)?.resize();
                        });
                    }, 30);
                };

                previousButton.addEventListener('click', () => showGraph(graphIndex - 1));
                nextButton.addEventListener('click', () => showGraph(graphIndex + 1));
                showGraph(0);
            });

            // ============================================================
            // 2. GRÁFICO 1: Barras - Horas por día (CON 110% DE MÁRGEN)
            // ============================================================
            const canvas = document.getElementById('workedTimeChart');
            if (canvas && typeof Chart !== 'undefined') {
                const hoursData = @json($hours);
                const maxHours = Math.max(...hoursData, 0);
                
                // 🔥 CALCULAR EL 110% DEL MÁXIMO
                const maxY = Math.max(maxHours * 1.1, 0.1); // 110% del máximo
                
                // Calcular stepSize para 5 marcas exactas (0, 25%, 50%, 75%, 100%)
                const stepSize = maxY / 4;
                
                // REDONDEAR stepSize para evitar problemas con números muy pequeños
                const roundedStepSize = Math.ceil(stepSize * 10000) / 10000;
                
                console.log('📊 Datos de horas:', hoursData);
                console.log('📈 Máximo del rango:', maxHours);
                console.log('📈 Máximo ajustado (110%):', maxY);
                console.log('📐 Step size redondeado:', roundedStepSize);
                
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            type: 'bar',
                            label: 'Horas trabajadas',
                            data: hoursData,
                            backgroundColor: '#09090b',
                            borderColor: 'transparent',
                            borderWidth: 0,
                            borderRadius: 4,
                            maxBarThickness: 42,
                            order: 2,
                        }, {
                            type: 'line',
                            label: 'Tendencia diaria',
                            data: hoursData,
                            borderColor: '#71717a',
                            backgroundColor: 'rgba(113, 113, 122, .10)',
                            borderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#18181b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 0,
                            tension: 0.35,
                            fill: false,
                            order: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { 
                            padding: { 
                                top: -25,     
                                bottom: 0,
                                left: 0,
                                right: 0
                            } 
                        },
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    boxWidth: 14,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 30,
                                    font: { size: 15 },
                                },
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1f2937',
                                bodyColor: '#1f2937',
                                titleFont: { size: 15 },
                                bodyFont: { size: 15 },
                                borderColor: 'rgba(0, 0, 0, 0.1)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label(context) {
                                        const totalSegundos = Math.round(context.parsed.y * 3600);
                                        const horas = Math.floor(totalSegundos / 3600);
                                        const minutos = Math.floor((totalSegundos % 3600) / 60);
                                        const segundos = totalSegundos % 60;
                                        const tiempo = `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                        return `${context.dataset.label}: ${tiempo}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                ticks: { display: true, font: { size: 15 } },
                            },
                            y: {
                                beginAtZero: true,
                                max: maxY, // ✅ USAR EL 110% DEL MÁXIMO
                                grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                ticks: {
                                    font: { size: 15 },
                                    stepSize: roundedStepSize,
                                    maxTicksLimit: 5,
                                    callback(value) {
                                        const totalSegundos = Math.round(value * 3600);
                                        const horas = Math.floor(totalSegundos / 3600);
                                        const minutos = Math.floor((totalSegundos % 3600) / 60);
                                        const segundos = totalSegundos % 60;
                                        return `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                    },
                                },
                            },
                        },
                    },
                    plugins: [{
                        id: 'legendSpacing',
                        beforeInit(chart) {
                            const fit = chart.legend.fit;
                            chart.legend.fit = function() {
                                fit.call(this);
                                this.height += 10;
                            };
                        }
                    }]
                });
            }

// ============================================================
// 3. GRÁFICO 2: Pastel - Clientes (CON TOOLTIP MEJORADO)
// ============================================================
const pieCanvas = document.getElementById('clientPieChart');
if (pieCanvas && typeof Chart !== 'undefined') {
    const clientLabels = @json($clientLabels);
    const clientData = @json($clientData);

    // Función para truncar texto
    const truncateText = (text, maxLength = 20) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    // Función para formatear horas a HHh:MMm:SSs CON ESPACIADO
    const formatHours = (hours) => {
        const totalSegundos = Math.round(hours * 3600);
        const h = Math.floor(totalSegundos / 3600);
        const m = Math.floor((totalSegundos % 3600) / 60);
        const s = totalSegundos % 60;
        // ✅ Formato con espacios entre caracteres: 0 0 h : 0 0 m : 0 0 s
        const hStr = String(h).padStart(2, '0').split('').join(' ');
        const mStr = String(m).padStart(2, '0').split('').join(' ');
        const sStr = String(s).padStart(2, '0').split('').join(' ');
        return `${hStr} h : ${mStr} m : ${sStr} s`;
    };

    // Función para generar colores
    const getColors = (count) => {
        const baseColors = [
            '#09090b', '#27272a', '#3f3f46', '#52525b', '#71717a',
            '#a1a1aa', '#18181b', '#404040', '#737373', '#a3a3a3',
            '#262626', '#525252', '#d4d4d4', '#171717', '#737373',
        ];
        while (baseColors.length < count) {
            const idx = baseColors.length % baseColors.length;
            const color = baseColors[idx];
            const opacity = 0.5 + (idx % 3) * 0.15;
            baseColors.push(color.replace(/0\.8/, opacity.toFixed(1)));
        }
        return baseColors.slice(0, count);
    };

    // ✅ Función para obtener el color de un cliente específico
    const getColorForClient = (index) => {
        const colors = getColors(clientLabels.length);
        return colors[index] || 'rgba(200, 200, 200, 0.8)';
    };

    if (clientLabels.length > 0) {
        // Preparar datos: máximo 7 clientes + "Otros"
        let processedLabels = [...clientLabels];
        let processedData = [...clientData];
        let othersData = [];
        let othersColors = [];
        
        if (clientLabels.length > 7) {
            const topLabels = clientLabels.slice(0, 7);
            const topData = clientData.slice(0, 7);
            const othersSum = clientData.slice(7).reduce((a, b) => a + b, 0);
            
            othersData = clientLabels.slice(7).map((label, index) => ({
                label: label,
                value: clientData.slice(7)[index],
                color: getColorForClient(7 + index)
            }));
            
            processedLabels = [...topLabels, 'Otros'];
            processedData = [...topData, othersSum];
        }

        // Truncar nombres (excepto "Otros")
        const truncatedLabels = processedLabels.map(label => 
            label === 'Otros' ? label : truncateText(label, 20)
        );

        const colors = getColors(processedLabels.length);
        const legendFontSize = processedLabels.length > 8 ? 12 : 14;
        
        const chart = new Chart(pieCanvas, {
            type: 'doughnut',
            data: {
                labels: truncatedLabels,
                datasets: [{
                    data: processedData,
                    backgroundColor: colors,
                    borderColor: '#f4f4f4',
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { size: legendFontSize },
                            padding: processedLabels.length > 8 ? 8 : 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: label,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i,
                                    fullName: clientLabels[i] || label,
                                }));
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.98)',
                        titleColor: '#1A3A6B',
                        bodyColor: '#374151',
                        titleFont: { size: 15, weight: '600', family: "'Segoe UI', Arial, sans-serif" },
                        bodyFont: { size: 15, weight: '400', family: "'Segoe UI', Arial, sans-serif" },
                        borderColor: 'rgba(26, 58, 107, 0.15)',
                        borderWidth: 2,
                        cornerRadius: 12,
                        padding: 20,
                        titleMarginBottom: 12,
                        bodySpacing: 8,
                        boxPadding: 6,
                        usePointStyle: true,
                        // ✅ Configurar para mostrar la bolita de color
                        boxWidth: 12,
                        boxHeight: 12,
                        callbacks: {
                            title: function(tooltipItems) {
                                const item = tooltipItems[0];
                                const index = item.dataIndex;
                                if (processedLabels[index] === 'Otros') {
                                    return '📦 Otros Clientes';
                                }
                                return clientLabels[index] || processedLabels[index];
                            },
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                const tiempoFormateado = formatHours(context.parsed);
                                
                                // ✅ Formato: "02h:30m:15s (45.5%)"
                                if (context.label === 'Otros' && othersData.length > 0) {
                                    return `${tiempoFormateado} (${percentage}%)  •  ${othersData.length} clientes`;
                                }
                                return `${tiempoFormateado} (${percentage}%)`;
                            },
                            // ✅ Personalizar el color de la bolita
                            labelColor: function(context) {
                                const index = context.dataIndex;
                                return {
                                    borderColor: 'rgba(255, 255, 255, 0.8)',
                                    backgroundColor: context.dataset.backgroundColor[index] || 'rgba(200, 200, 200, 0.8)',
                                    borderWidth: 2,
                                    borderRadius: 50,
                                    width: 14,
                                    height: 14,
                                };
                            },
                            afterBody: function(tooltipItems) {
                                const item = tooltipItems[0];
                                const index = item.dataIndex;
                                
                                if (processedLabels[index] === 'Otros' && othersData.length > 0) {
                                    const totalOthers = othersData.reduce((sum, c) => sum + c.value, 0);
                                    
                                    // ✅ Formato con hora espaciada, porcentaje y bolita de color
                                    const clientLines = othersData.map(c => {
                                        const pct = totalOthers > 0 ? ((c.value / totalOthers) * 100).toFixed(1) : 0;
                                        // ✅ Usar el formato con espaciado
                                        const timeStr = formatHours(c.value);
                                        return `${c.label}  ${timeStr} (${pct}%)`;
                                    });
                                    
                                    const maxLines = 8;
                                    let displayLines = clientLines;
                                    let extraCount = 0;
                                    
                                    if (clientLines.length > maxLines) {
                                        displayLines = clientLines.slice(0, maxLines);
                                        extraCount = clientLines.length - maxLines;
                                    }
                                    
                                    const result = [
                                        '▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                                        'Clientes agrupados'
                                    ];
                                    
                                    result.push(...displayLines);
                                    
                                    if (extraCount > 0) {
                                        result.push(`⋯ y ${extraCount} cliente${extraCount > 1 ? 's' : ''} más`);
                                        result.push(`Total  ${formatHours(totalOthers)}`);
                                    }
                                    
                                    result.push('▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬');
                                    result.push('🖱 Haz clic para ver todos');
                                    
                                    return result;
                                }
                                return null;
                            }
                        }
                    },
                },
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        if (processedLabels[index] === 'Otros' && othersData.length > 0) {
                            const totalOthers = othersData.reduce((sum, c) => sum + c.value, 0);
                            const clientList = othersData
                                .map((c, i) => {
                                    const pct = totalOthers > 0 ? ((c.value / totalOthers) * 100).toFixed(1) : 0;
                                    return `${String(i + 1).padStart(2, ' ')}. ${c.label.padEnd(35)} ${formatHours(c.value)} (${pct}%)`;
                                })
                                .join('\n');
                            
                            alert(
                                `📦 CLIENTES EN "OTROS"\n` +
                                `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n` +
                                `${clientList}\n\n` +
                                `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                                `Total  ${formatHours(totalOthers)}\n` +
                                `Clientes  ${othersData.length}`
                            );
                        }
                    }
                },
                ...(processedLabels.length > 8 && {
                    layout: { padding: { bottom: 20 } },
                }),
            },
        });

        // Tooltip personalizado para la leyenda (hover)
        const legendContainer = pieCanvas.closest('.rounded-2xl');
        if (legendContainer) {
            const tooltipEl = document.createElement('div');
            tooltipEl.id = 'legendTooltip';
            tooltipEl.style.cssText = `
                position: fixed;
                background: rgba(255, 255, 255, 0.98);
                color: #1A3A6B;
                padding: 10px 18px;
                border-radius: 8px;
                border: 1.5px solid rgba(26, 58, 107, 0.15);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                font-size: 14px;
                font-weight: 600;
                font-family: 'Segoe UI', Arial, sans-serif;
                pointer-events: none;
                display: none;
                z-index: 1000;
                max-width: 320px;
                word-wrap: break-word;
                letter-spacing: 0.1px;
            `;
            document.body.appendChild(tooltipEl);
            
            const legendItems = legendContainer.querySelectorAll('li');
            legendItems.forEach((item, index) => {
                item.addEventListener('mouseenter', function(e) {
                    if (index < clientLabels.length) {
                        tooltipEl.textContent = clientLabels[index] || '';
                        tooltipEl.style.display = 'block';
                        tooltipEl.style.left = (e.clientX + 14) + 'px';
                        tooltipEl.style.top = (e.clientY - 10) + 'px';
                    }
                });
                
                item.addEventListener('mousemove', function(e) {
                    tooltipEl.style.left = (e.clientX + 14) + 'px';
                    tooltipEl.style.top = (e.clientY - 10) + 'px';
                });
                
                item.addEventListener('mouseleave', function() {
                    tooltipEl.style.display = 'none';
                });
            });
        }

        window.clientPieChart = chart;
    } else {
        window.clientPieChart = new Chart(pieCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Sin datos'],
                datasets: [{ data: [1], backgroundColor: ['#e4e4e7'], borderColor: '#f4f4f4', borderWidth: 3 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
            },
            plugins: [{
                id: 'emptyClientDistribution',
                afterDraw(chart) {
                    if (chart.$hasLiveData) return;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    ctx.save();
                    ctx.fillStyle = '#71717a';
                    ctx.font = '500 15px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText('Sin clientes registrados', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                    ctx.restore();
                },
            }],
        });
        window.clientPieChart.$hasLiveData = false;
    }
}

            // ============================================================
            // 4. GRÁFICO 3: Barras horizontales - Actividades
            // ============================================================
            const activityCanvas = document.getElementById('activityChart');
            if (activityCanvas && typeof Chart !== 'undefined') {
                const activityLabels = @json($activityLabels);
                const activityData = @json($activityData);

                if (activityLabels.length > 0) {
                    const activityColors = [
                        '#09090b', '#27272a', '#3f3f46', '#52525b', '#71717a',
                        '#a1a1aa', '#18181b', '#404040', '#737373', '#a3a3a3',
                    ];

                    new Chart(activityCanvas, {
                        type: 'bar',
                        data: {
                            labels: activityLabels,
                            datasets: [{
                                data: activityData,
                                backgroundColor: activityColors.slice(0, activityLabels.length),
                                borderColor: 'rgba(255, 255, 255, 0.8)',
                                borderWidth: 1,
                                borderRadius: 4,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                    titleColor: '#1f2937',
                                    bodyColor: '#1f2937',
                                    borderColor: 'rgba(0, 0, 0, 0.1)',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    padding: 12,
                                    callbacks: {
                                        label(context) {
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((context.parsed.x / total) * 100).toFixed(
                                                1) : 0;
                                            const horas = context.parsed.x.toFixed(2);
                                            return `${horas} horas (${percentage}%)`;
                                        },
                                    },
                                },
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    reverse: true,
                                    grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                    border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                    ticks: {
                                        font: { size: 13 },
                                        callback(value) {
                                            const totalSegundos = Math.round(value * 3600);
                                            const horas = Math.floor(totalSegundos / 3600);
                                            const minutos = Math.floor((totalSegundos % 3600) / 60);
                                            const segundos = totalSegundos % 60;
                                            return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')} h`;
                                        },
                                    },
                                },
                                y: {
                                    position: 'right',
                                    grid: { display: false },
                                    border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                    ticks: { font: { size: 13 } },
                                },
                            },
                        },
                    });
                } else {
                    new Chart(activityCanvas, {
                        type: 'bar',
                        data: {
                            labels: ['Sin actividades registradas'],
                            datasets: [{ data: [0], backgroundColor: ['#d4d4d8'], borderRadius: 4 }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false }, tooltip: { enabled: false } },
                            scales: {
                                x: { beginAtZero: true, suggestedMax: 1, grid: { color: 'rgba(0, 0, 0, .06)' }, border: { color: 'rgba(0, 0, 0, .12)' } },
                                y: { position: 'right', grid: { display: false }, border: { color: 'rgba(0, 0, 0, .12)' } },
                            },
                        },
                    });
                }
            }

            const dashboardDataParams = new URLSearchParams({
                user_id: '{{ $selectedUser ? $selectedUser->id : 0 }}',
                fecha_inicio: '{{ $start->toDateString() }}',
                fecha_fin: '{{ $end->toDateString() }}',
            });

            const fetchDashboardData = async () => {
                const response = await fetch(`{{ route('time.dashboard-data') }}?${dashboardDataParams}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error(`Error ${response.status}`);
                return response.json();
            };

            document.querySelector('[data-refresh-chart="worked"]')?.addEventListener('click', function() {
                runChartRefresh(this, async () => {
                    const data = await fetchDashboardData();
                    const chart = Chart.getChart(document.getElementById('workedTimeChart'));
                    if (!chart) return;
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.hours;
                    chart.data.datasets[1].data = data.hours;
                    chart.update('none');
                });
            });

            document.querySelector('[data-refresh-chart="clients"]')?.addEventListener('click', function() {
                runChartRefresh(this, async () => {
                    const data = await fetchDashboardData();
                    const chart = Chart.getChart(document.getElementById('clientPieChart'));
                    if (!chart) return;
                    const hasData = data.clientLabels.length > 0;
                    const labels = data.clientLabels.slice(0, 7);
                    const values = data.clientData.slice(0, 7);
                    if (data.clientLabels.length > 7) {
                        labels.push('Otros');
                        values.push(data.clientData.slice(7).reduce((total, value) => total + value, 0));
                    }
                    chart.$hasLiveData = hasData;
                    chart.data.labels = hasData ? labels : ['Sin datos'];
                    chart.data.datasets[0].data = hasData ? values : [1];
                    chart.data.datasets[0].backgroundColor = hasData
                        ? ['#09090b', '#27272a', '#3f3f46', '#52525b', '#71717a', '#a1a1aa', '#18181b', '#d4d4d4'].slice(0, labels.length)
                        : ['#e4e4e7'];
                    chart.options.plugins.legend.display = hasData;
                    chart.update('none');
                });
            });

            document.querySelector('[data-refresh-chart="activities"]')?.addEventListener('click', function() {
                runChartRefresh(this, async () => {
                    const data = await fetchDashboardData();
                    const chart = Chart.getChart(document.getElementById('activityChart'));
                    if (!chart) return;
                    const hasData = data.activityLabels.length > 0;
                    chart.data.labels = hasData ? data.activityLabels : ['Sin actividades registradas'];
                    chart.data.datasets[0].data = hasData ? data.activityData : [0];
                    chart.data.datasets[0].backgroundColor = hasData
                        ? ['#09090b', '#27272a', '#3f3f46', '#52525b', '#71717a', '#a1a1aa', '#18181b', '#404040'].slice(0, data.activityLabels.length)
                        : ['#d4d4d8'];
                    chart.update('none');
                });
            });

            // ============================================================
            // 5. GRÁFICO 4: Detalle por Actividad
            // ============================================================
            const detailLegendSpacing = {
                id: 'detailLegendSpacing',
                beforeInit(chart) {
                    const fit = chart.legend.fit;
                    chart.legend.fit = function() {
                        fit.call(this);
                        this.height += 10;
                    };
                },
            };

            function createEmptyDetailChart(chartCanvas, message) {
                const emptyLabels = @json($labels);
                const labelsToShow = emptyLabels.length ? emptyLabels : ['Sin datos'];

                return new Chart(chartCanvas, {
                    type: 'bar',
                    data: {
                        labels: labelsToShow,
                        datasets: [{
                            label: message,
                            data: labelsToShow.map(() => 0),
                            backgroundColor: '#d4d4d8',
                            borderRadius: 4,
                            maxBarThickness: 42,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, labels: { boxWidth: 14, usePointStyle: true, pointStyle: 'circle', font: { size: 15 } } },
                            tooltip: { enabled: false },
                        },
                        scales: {
                            x: { grid: { color: 'rgba(0, 0, 0, .06)' }, border: { color: 'rgba(0, 0, 0, .12)' } },
                            y: {
                                beginAtZero: true,
                                suggestedMax: 1,
                                grid: { color: 'rgba(0, 0, 0, .06)' },
                                border: { color: 'rgba(0, 0, 0, .12)' },
                                ticks: { callback: (value) => `${value} h` },
                            },
                        },
                    },
                    plugins: [detailLegendSpacing, {
                        id: `emptyDetailMessage-${chartCanvas.id}`,
                        afterDraw(chart) {
                            const { ctx, chartArea } = chart;
                            if (!chartArea) return;
                            ctx.save();
                            ctx.fillStyle = '#71717a';
                            ctx.font = '500 15px Arial';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(message, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                            ctx.restore();
                        },
                    }],
                });
            }

            const detailCanvas = document.getElementById('activityDetailChart');
            let detailChart = null;

            if (detailCanvas && typeof Chart !== 'undefined') {
                const activitySelector = document.getElementById('activitySelector');
                const activityLabels = @json($activityLabels);
                const activityIds = @json($activityIds);

                function loadActivityData(activityId) {
                    if (!activityId) {
                        if (detailChart) {
                            detailChart.destroy();
                        }
                        detailChart = createEmptyDetailChart(detailCanvas, 'Sin actividades registradas');
                        return;
                    }

                    return fetch(
                            `{{ route('time.activity-data') }}?activity_id=${activityId}&user_id={{ $selectedUser ? $selectedUser->id : 0 }}&fecha_inicio={{ $start->toDateString() }}&fecha_fin={{ $end->toDateString() }}`
                        )
                        .then(response => response.json())
                        .then(data => {
                            if (detailChart) {
                                detailChart.destroy();
                            }
                            const selectedOption = activitySelector.options[activitySelector.selectedIndex];
                            const activityName = selectedOption ? selectedOption.text : 'Actividad';

                            detailChart = new Chart(detailCanvas, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: activityName,
                                        data: data.hours,
                                        backgroundColor: '#09090b',
                                        borderColor: 'transparent',
                                        borderWidth: 0,
                                        borderRadius: 4,
                                        maxBarThickness: 42,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            labels: {
                                                boxWidth: 14,
                                                usePointStyle: true,
                                                pointStyle: 'circle',
                                                padding: 15,
                                                font: { size: 15 },
                                            },
                                        },
                                        tooltip: {
                                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                            titleColor: '#1f2937',
                                            bodyColor: '#1f2937',
                                            titleFont: { size: 15 },
                                            bodyFont: { size: 15 },
                                            borderColor: 'rgba(0, 0, 0, 0.1)',
                                            borderWidth: 1,
                                            cornerRadius: 8,
                                            padding: 12,
                                            callbacks: {
                                                label(context) {
                                                    const totalSegundos = Math.round(context.parsed.y *
                                                    3600);
                                                    const horas = Math.floor(totalSegundos / 3600);
                                                    const minutos = Math.floor((totalSegundos % 3600) /
                                                        60);
                                                    const segundos = totalSegundos % 60;
                                                    const tiempo =
                                                        `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                                    return `${context.dataset.label}: ${tiempo}`;
                                                },
                                            },
                                        },
                                    },
                                    scales: {
                                        x: {
                                            grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                            border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                            ticks: { display: true, font: { size: 15 } },
                                        },
                                        y: {
                                            beginAtZero: true,
                                            grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                            border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                            ticks: {
                                                font: { size: 15 },
                                                callback(value) {
                                                    const totalSegundos = Math.round(value * 3600);
                                                    const horas = Math.floor(totalSegundos / 3600);
                                                    const minutos = Math.floor((totalSegundos % 3600) /
                                                        60);
                                                    const segundos = totalSegundos % 60;
                                                    return `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                                },
                                            },
                                        },
                                    },
                                },
                                plugins: [detailLegendSpacing],
                            });
                        })
                        .catch(error => console.error('Error cargando datos de actividad:', error));
                }

                if (activitySelector) {
                    activitySelector.addEventListener('change', function() {
                        loadActivityData(this.value);
                    });

                    if (activityLabels.length > 0 && activityIds.length > 0) {
                        const defaultActivityId = activityIds[0];
                        activitySelector.value = defaultActivityId;
                        loadActivityData(defaultActivityId);
                    } else {
                        loadActivityData('');
                    }
                }

                document.getElementById('refreshActivityDetailBtn')?.addEventListener('click', function() {
                    runChartRefresh(this, () => loadActivityData(activitySelector?.value || ''));
                });
            }

            // ============================================================
            // 6. GRÁFICO 5: Detalle por Cliente
            // ============================================================
            const clientDetailCanvas = document.getElementById('clientDetailChart');
            let clientDetailChart = null;

            if (clientDetailCanvas && typeof Chart !== 'undefined') {
                const clientSelector = document.getElementById('clientSelector');
                const clientLabels = @json($clientLabels);
                const clientIds = @json($clientIds);

                function loadClientData(clientId) {
                    if (!clientId) {
                        if (clientDetailChart) {
                            clientDetailChart.destroy();
                        }
                        clientDetailChart = createEmptyDetailChart(clientDetailCanvas, 'Sin clientes registrados');
                        return;
                    }

                    return fetch(
                            `{{ route('time.client-data') }}?client_id=${clientId}&user_id={{ $selectedUser ? $selectedUser->id : 0 }}&fecha_inicio={{ $start->toDateString() }}&fecha_fin={{ $end->toDateString() }}`
                        )
                        .then(response => response.json())
                        .then(data => {
                            if (clientDetailChart) {
                                clientDetailChart.destroy();
                            }
                            const selectedOption = clientSelector.options[clientSelector.selectedIndex];
                            const clientName = selectedOption ? selectedOption.text : 'Cliente';

                            clientDetailChart = new Chart(clientDetailCanvas, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: clientName,
                                        data: data.hours,
                                        backgroundColor: '#09090b',
                                        borderColor: 'transparent',
                                        borderWidth: 0,
                                        borderRadius: 4,
                                        maxBarThickness: 42,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            labels: {
                                                boxWidth: 14,
                                                usePointStyle: true,
                                                pointStyle: 'circle',
                                                padding: 15,
                                                font: { size: 15 },
                                            },
                                        },
                                        tooltip: {
                                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                            titleColor: '#1f2937',
                                            bodyColor: '#1f2937',
                                            titleFont: { size: 15 },
                                            bodyFont: { size: 15 },
                                            borderColor: 'rgba(0, 0, 0, 0.1)',
                                            borderWidth: 1,
                                            cornerRadius: 8,
                                            padding: 12,
                                            callbacks: {
                                                label(context) {
                                                    const totalSegundos = Math.round(context.parsed.y *
                                                    3600);
                                                    const horas = Math.floor(totalSegundos / 3600);
                                                    const minutos = Math.floor((totalSegundos % 3600) /
                                                        60);
                                                    const segundos = totalSegundos % 60;
                                                    const tiempo =
                                                        `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                                    return `${context.dataset.label}: ${tiempo}`;
                                                },
                                            },
                                        },
                                    },
                                    scales: {
                                        x: {
                                            grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                            border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                            ticks: { display: true, font: { size: 15 } },
                                        },
                                        y: {
                                            beginAtZero: true,
                                            grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                            border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                            ticks: {
                                                font: { size: 15 },
                                                callback(value) {
                                                    const totalSegundos = Math.round(value * 3600);
                                                    const horas = Math.floor(totalSegundos / 3600);
                                                    const minutos = Math.floor((totalSegundos % 3600) /
                                                        60);
                                                    const segundos = totalSegundos % 60;
                                                    return `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                                },
                                            },
                                        },
                                    },
                                },
                                plugins: [detailLegendSpacing],
                            });
                        })
                        .catch(error => console.error('Error cargando datos del cliente:', error));
                }

                if (clientSelector) {
                    clientSelector.addEventListener('change', function() {
                        loadClientData(this.value);
                    });

                    if (clientIds.length > 0) {
                        const defaultClientId = clientIds[0];
                        clientSelector.value = defaultClientId;
                        loadClientData(defaultClientId);
                    } else {
                        loadClientData('');
                    }
                }

                document.getElementById('refreshClientDetailBtn')?.addEventListener('click', function() {
                    runChartRefresh(this, () => loadClientData(clientSelector?.value || ''));
                });
            }

            // ============================================================
            // 7. GRÁFICO 6: Cliente + Actividad
            // ============================================================
            const clientActivityCanvas = document.getElementById('clientActivityChart');
            let clientActivityChart = null;

            if (clientActivityCanvas && typeof Chart !== 'undefined') {
                const clientActivity3dPlugin = {
                    id: 'clientActivity3dBars',
                    beforeDatasetDraw(chart, args) {
                        if (args.index !== 0) return;

                        const { ctx } = chart;
                        const depth = 10;
                        ctx.save();

                        args.meta.data.forEach((bar) => {
                            const properties = bar.getProps(['x', 'y', 'base', 'width'], true);
                            const halfWidth = properties.width / 2;
                            if (!Number.isFinite(properties.x) || !Number.isFinite(properties.y)) return;
                            if (Math.abs(properties.base - properties.y) < 1) return;

                            ctx.beginPath();
                            ctx.moveTo(properties.x + halfWidth, properties.y);
                            ctx.lineTo(properties.x + halfWidth + depth, properties.y - depth);
                            ctx.lineTo(properties.x + halfWidth + depth, properties.base - depth);
                            ctx.lineTo(properties.x + halfWidth, properties.base);
                            ctx.closePath();
                            ctx.fillStyle = '#3f3f46';
                            ctx.fill();

                            ctx.beginPath();
                            ctx.moveTo(properties.x - halfWidth, properties.y);
                            ctx.lineTo(properties.x - halfWidth + depth, properties.y - depth);
                            ctx.lineTo(properties.x + halfWidth + depth, properties.y - depth);
                            ctx.lineTo(properties.x + halfWidth, properties.y);
                            ctx.closePath();
                            ctx.fillStyle = '#a1a1aa';
                            ctx.fill();
                        });

                        ctx.restore();
                    },
                };
                clientActivityChart = new Chart(clientActivityCanvas, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Horas trabajadas',
                            data: [],
                            backgroundColor: '#09090b',
                            hoverBackgroundColor: '#27272a',
                            borderColor: '#09090b',
                            borderWidth: 0,
                            borderRadius: 4,
                            borderSkipped: false,
                            maxBarThickness: 46,
                            categoryPercentage: .7,
                            barPercentage: .72,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        layout: { padding: 0 },
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                backgroundColor: 'rgba(9, 9, 11, .96)',
                                titleColor: '#fff',
                                bodyColor: '#e4e4e7',
                                titleFont: { size: 15, lineHeight: 1.5 },
                                bodyFont: { size: 15, lineHeight: 1.5 },
                                borderColor: 'rgba(255, 255, 255, .16)',
                                borderWidth: 1,
                                cornerRadius: 12,
                                padding: { top: 15, right: 20, bottom: 15, left: 20 },
                                titleMarginBottom: 10,
                                bodySpacing: 10,
                                boxPadding: 10,
                                caretPadding: 10,
                                boxWidth: 14,
                                boxHeight: 14,
                                usePointStyle: true,
                                callbacks: {
                                    label(context) {
                                        const totalSegundos = Math.round(context.parsed.y * 3600);
                                        const horas = Math.floor(totalSegundos / 3600);
                                        const minutos = Math.floor((totalSegundos % 3600) / 60);
                                        const segundos = totalSegundos % 60;
                                        const tiempo =
                                            `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                        return `${context.dataset.label}: ${tiempo}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { display: true, color: '#52525b', font: { size: 15, weight: '500' } },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { display: true, color: 'rgba(0, 0, 0, .06)', drawTicks: false },
                                border: { display: false },
                                ticks: {
                                    font: { size: 15 },
                                    callback(value) {
                                        const totalSegundos = Math.round(value * 3600);
                                        const horas = Math.floor(totalSegundos / 3600);
                                        const minutos = Math.floor((totalSegundos % 3600) / 60);
                                        const segundos = totalSegundos % 60;
                                        return `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                    },
                                },
                            },
                        },
                    },
                    plugins: [clientActivity3dPlugin],
                });
            }

            function loadClientActivityChart(clientId, activityId) {
                clientId = parseInt(clientId);
                activityId = parseInt(activityId);

                if (isNaN(clientId) || isNaN(activityId) || !clientId || !activityId) {
                    return;
                }

                if (!clientActivityChart) {
                    return;
                }

                const params = new URLSearchParams({
                    client_id: clientId,
                    activity_id: activityId,
                    user_id: '{{ $selectedUser ? $selectedUser->id : 0 }}',
                    fecha_inicio: '{{ $start->toDateString() }}',
                    fecha_fin: '{{ $end->toDateString() }}'
                });

                return fetch('{{ route("dashboard.client-activity-data") }}?' + params)
                    .then(res => res.json())
                    .then(data => {
                        clientActivityChart.data.labels = data.labels;
                        clientActivityChart.data.datasets[0].data = data.hours;

                        const clientSelect = document.getElementById('clientActivityClientSelector');
                        const activitySelect = document.getElementById('clientActivityActivitySelector');
                        const clientName = clientSelect ? clientSelect.options[clientSelect.selectedIndex]?.text : 'Cliente';
                        const activityName = activitySelect ? activitySelect.options[activitySelect.selectedIndex]?.text : 'Actividad';

                        clientActivityChart.data.datasets[0].label = `${clientName} - ${activityName}`;
                        const legendLabel = document.getElementById('clientActivityLegendLabel');
                        if (legendLabel) {
                            legendLabel.textContent = `${clientName} - ${activityName}`;
                            legendLabel.title = `${clientName} - ${activityName}`;
                        }
                        clientActivityChart.update('none');
                    })
                    .catch(error => console.error('Error cargando gráfica:', error));
            }

            @if($topClientActivity && $selectedUser)
                const defaultClientId = parseInt({{ $topClientActivity['client_id'] }});
                const defaultActivityId = parseInt({{ $topClientActivity['activity_id'] }});

                const clientSelect = document.getElementById('clientActivityClientSelector');
                const activitySelect = document.getElementById('clientActivityActivitySelector');

                if (clientSelect) clientSelect.value = defaultClientId;
                if (activitySelect) activitySelect.value = defaultActivityId;

                loadClientActivityChart(defaultClientId, defaultActivityId);
            @endif

            const loadBtn = document.getElementById('loadClientActivityBtn');
            if (loadBtn) {
                loadBtn.addEventListener('click', function() {
                    const clientSelect = document.getElementById('clientActivityClientSelector');
                    const activitySelect = document.getElementById('clientActivityActivitySelector');

                    const clientId = parseInt(clientSelect?.value);
                    const activityId = parseInt(activitySelect?.value);

                    if (isNaN(clientId) || isNaN(activityId) || !clientId || !activityId) {
                        alert('Selecciona tanto un cliente como una actividad');
                        return;
                    }

                    runChartRefresh(this, () => loadClientActivityChart(clientId, activityId));
                });
            }


            // ============================================================
            // 8. DESCARGA DE PDF CON GRÁFICAS CAPTURADAS
            // ============================================================

            const downloadBtn = document.getElementById('downloadPdfBtn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Cambiar estado del botón
                    const originalHtml = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '⏳ Generando PDF...';

                    // Pequeño retraso para asegurar que los canvas terminen de renderizar
                    setTimeout(() => {
                        // Capturar solo canvas con contenido
                        const canvases = document.querySelectorAll('canvas');
                        const images = [];

                        canvases.forEach((canvas, index) => {
                            // Si el canvas no tiene dimensiones, saltar
                            if (canvas.width === 0 || canvas.height === 0) return;

                            try {
                                // Obtener datos de la imagen
                                const dataUrl = canvas.toDataURL('image/png');
                                
                                // Verificar que sea una imagen válida (más de 50 caracteres y comienza con data:image)
                                if (dataUrl && dataUrl.startsWith('data:image/') && dataUrl.length > 100) {
                                    let title = 'Gráfica ' + (index + 1);
                                    
                                    // Intentar obtener el título desde el contenedor cercano
                                    const parent = canvas.closest('.rounded-2xl');
                                    if (parent) {
                                        const titleEl = parent.querySelector('.font-semibold');
                                        if (titleEl) title = titleEl.textContent.trim();
                                    }
                                    
                                    images.push({ title: title, src: dataUrl });
                                }
                            } catch (error) {
                                console.warn('Error capturando canvas:', error);
                            }
                        });

                        // Si no hay imágenes, mostrar advertencia y restaurar botón
                        if (images.length === 0) {
                            alert('⚠️ No se encontraron gráficas para exportar. Asegúrate de que haya datos cargados y las gráficas estén visibles.');
                            downloadBtn.disabled = false;
                            downloadBtn.innerHTML = originalHtml;
                            return;
                        }

                        // Obtener datos de filtros desde el formulario
                        const form = document.querySelector('form[action="{{ route('time.dashboard') }}"]');
                        const formData = new FormData(form);
                        const params = new URLSearchParams(formData);

                        // Construir payload
                        const payload = {
                            images: images,
                            user_id: {{ $selectedUser ? $selectedUser->id : 0 }},
                            fecha_inicio: params.get('fecha_inicio') || '{{ $start->toDateString() }}',
                            fecha_fin: params.get('fecha_fin') || '{{ $end->toDateString() }}',
                            // Datos adicionales por si se usan en el PDF (opcional)
                            labels: @json($labels),
                            hours: @json($hours),
                            clientLabels: @json($clientLabels),
                            clientData: @json($clientData),
                            activityLabels: @json($activityLabels),
                            activityData: @json($activityData),
                            totalSeconds: @json($totalSeconds),
                        };

                        // Actualizar estado del botón
                        downloadBtn.innerHTML = '📄 Descargando...';

                        // Enviar por POST a la ruta de generación
                        fetch('{{ route('dashboard.pdf.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.error || 'Error en el servidor');
                                });
                            }
                            return response.blob();
                        })
                        .then(blob => {
                            // Crear enlace de descarga
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'dashboard_tiempo_' + new Date().toISOString().slice(0,10) + '.pdf';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);

                            // Restaurar botón
                            downloadBtn.disabled = false;
                            downloadBtn.innerHTML = originalHtml;
                        })
                        .catch(error => {
                            console.error('Error al generar PDF:', error);
                            alert('❌ Hubo un error al generar el PDF: ' + error.message);
                            downloadBtn.disabled = false;
                            downloadBtn.innerHTML = originalHtml;
                        });
                    }, 500); // 500ms de retraso (puedes ajustar a 300ms si es suficiente)
                });
            }

            // Botón de impresión
            const printBtn = document.getElementById('printBtn');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }

            // Controles superiores de zoom y pantalla completa.
            const dashboardRoot = document.getElementById('timeDashboardRoot');
            const zoomValue = document.getElementById('dashboardZoomReset');
            const zoomOut = document.getElementById('dashboardZoomOut');
            const zoomIn = document.getElementById('dashboardZoomIn');
            const fullscreenButton = document.getElementById('dashboardFullscreen');
            const viewControls = document.querySelector('.dashboard-view-controls');
            let dashboardZoom = 100;

            const resizeDashboardCharts = () => {
                window.setTimeout(() => {
                    if (typeof Chart === 'undefined') return;
                    document.querySelectorAll('.time-dashboard-modern canvas').forEach((chartCanvas) => {
                        Chart.getChart(chartCanvas)?.resize();
                    });
                }, 80);
            };

            const setDashboardZoom = (nextZoom) => {
                dashboardZoom = Math.min(130, Math.max(70, nextZoom));
                if (dashboardRoot) dashboardRoot.style.zoom = `${dashboardZoom}%`;
                if (zoomValue) zoomValue.textContent = `${dashboardZoom}%`;
                if (zoomOut) zoomOut.disabled = dashboardZoom === 70;
                if (zoomIn) zoomIn.disabled = dashboardZoom === 130;
                resizeDashboardCharts();
            };

            zoomOut?.addEventListener('click', () => setDashboardZoom(dashboardZoom - 10));
            zoomIn?.addEventListener('click', () => setDashboardZoom(dashboardZoom + 10));
            zoomValue?.addEventListener('click', () => setDashboardZoom(100));

            fullscreenButton?.addEventListener('click', async () => {
                if (!dashboardRoot) return;
                try {
                    if (document.fullscreenElement === dashboardRoot) await document.exitFullscreen();
                    else {
                        if (document.fullscreenElement) await document.exitFullscreen();
                        await dashboardRoot.requestFullscreen();
                    }
                } catch (error) {
                    console.error('No fue posible cambiar el modo de pantalla completa:', error);
                }
            });

            document.addEventListener('fullscreenchange', () => {
                const isFullscreen = document.fullscreenElement === dashboardRoot;
                viewControls?.classList.toggle('is-fullscreen', isFullscreen);
                fullscreenButton?.setAttribute('aria-label', isFullscreen ? 'Salir de pantalla completa' : 'Ver en pantalla completa');
                fullscreenButton?.setAttribute('title', isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa');
                resizeDashboardCharts();
            });


        });

        // ============================================================
        // 9. DROPDOWN DE COLABORADORES - FILTRO EN TIEMPO REAL
        // ============================================================
        function filterUsers(searchTerm) {
            const dropdown = document.getElementById('userDropdown');
            if (!dropdown) return;
            
            const items = dropdown.querySelectorAll('.user-item');
            const searchLower = searchTerm.toLowerCase().trim();
            
            let hasVisible = false;
            
            items.forEach(item => {
                const name = item.dataset.name || '';
                const email = item.dataset.email || '';
                const id = item.dataset.id || '';
                
                const matches = name.includes(searchLower) || 
                            email.includes(searchLower) || 
                            id.includes(searchLower);
                
                if (matches) {
                    item.style.display = '';
                    hasVisible = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // ✅ Siempre mostrar el dropdown si hay usuarios
            if (items.length > 0) {
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
            
            // Mostrar mensaje si no hay resultados
            const noResults = dropdown.querySelector('.no-results');
            if (!hasVisible && items.length > 0) {
                if (!noResults) {
                    const msg = document.createElement('div');
                    msg.className = 'no-results';
                    msg.style.cssText = 'padding: 20px; text-align: center; color: #6b7280; font-size: 14px;';
                    msg.textContent = 'No se encontraron colaboradores';
                    dropdown.appendChild(msg);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }

        // ✅ Al hacer clic en el input, mostrar el dropdown con todos los usuarios
        document.getElementById('search')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('userDropdown');
            if (!dropdown) return;
            
            // Mostrar todos los usuarios (resetear filtro)
            const items = dropdown.querySelectorAll('.user-item');
            items.forEach(item => item.style.display = '');
            // Eliminar mensaje de "no resultados" si existe
            const noResults = dropdown.querySelector('.no-results');
            if (noResults) noResults.remove();
            dropdown.style.display = 'block';
        });

        // ✅ Al escribir en el input, filtrar
        document.getElementById('search')?.addEventListener('input', function() {
            filterUsers(this.value);
        });

        // ✅ Ocultar el dropdown al hacer clic fuera
        document.addEventListener('click', function(e) {
            const searchInput = document.getElementById('search');
            const dropdown = document.getElementById('userDropdown');
            if (searchInput && dropdown) {
                if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            }
        });

        // ✅ Inicializar: mostrar el dropdown al cargar la página (solo si hay usuarios)
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.getElementById('userDropdown');
            const searchInput = document.getElementById('search');
            
            if (dropdown && searchInput) {
                const items = dropdown.querySelectorAll('.user-item');
                // Si hay usuarios, mostrar el dropdown
                if (items.length > 0) {
                    // Asegurar que todos los items estén visibles
                    items.forEach(item => item.style.display = '');
                    // Mostrar el dropdown
                    dropdown.style.display = 'block';
                }
            }
        });
    


    </script>
</x-app-layout>
