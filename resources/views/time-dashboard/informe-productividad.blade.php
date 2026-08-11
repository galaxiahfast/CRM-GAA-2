@php
    $fmtSeconds = function (int $seconds) {
        $seconds = max(0, $seconds);
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    };
@endphp

<x-app-layout>
    <div class="w-full bg-[#f4f4f4] min-h-screen" style="overflow-x: auto;">

        <!-- Contenedor interno con min-width: 700px -->
        <div style="min-width: 600px; padding: 0; margin: 0;">

            <!-- Header Superior con Migas de Pan -->
            <div style="padding: 40px; border-bottom: 2px solid #e5e7eb; background-color: transparent; display: flex; align-items: center; justify-content: space-between; min-width: max-content; overflow: hidden; gap: 80px;">
                <div style="display: flex; align-items: center; gap: 15px; font-size: 15px; color: #6b7280; white-space: nowrap; flex-shrink: 0;">
                    <span style="font-weight: 500;">Actividades</span>
                    <span style="color: #d1d5db; font-weight: 300;">></span>
                    <span style="font-weight: 500;">Control de Horas</span>
                    <span style="color: #d1d5db; font-weight: 300;">></span>
                    <span style="color: #1A3A6B; font-weight: 600;">Panel de Control</span>
                </div>

                <!-- Botones de acción -->
                <div style="display: flex; align-items: center; gap: 30px; white-space: nowrap; flex-shrink: 0;">
                    <button id="downloadPdfBtn" style="display: flex; align-items: center; gap: 15px; padding: 0; border: none; background-color: transparent; color: #6b7280; font-size: 15px; cursor: pointer; transition: all 0.2s; hover:color: #1A3A6B; white-space: nowrap;">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar PDF
                    </button>

                    <button id="printBtn" style="display: flex; align-items: center; gap: 15px; padding: 0; border: none; background-color: transparent; color: #6b7280; font-size: 15px; cursor: pointer; transition: all 0.2s; hover:color: #1A3A6B; white-space: nowrap;">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimir
                    </button>
                </div>
            </div>

            <!-- Header principal -->
            <div style="background-color: transparent; padding: 80px 80px 80px 80px; overflow: hidden; min-width: max-content;">

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
                                        Panel de Control
                                    </h1>

                                    <p style="font-size: 15px; color: #6b7280; white-space: nowrap;">
                                        Supervisión y análisis de productividad
                                    </p>
                                </div>

                            </div>

                            <p style="max-width: 672px; font-size: 15px; line-height: 28px; color: #6b7280;">
                                Visualización del tiempo trabajado por día,<br>con distribución por cliente y actividad durante el período seleccionado
                            </p>

                        </div>

                    </div>
                </div>

                <!-- Filtros -->
                <div style="background-color: transparent; margin-top: 24px; min-width: max-content;">

                    <form method="GET" action="{{ route('time.dashboard') }}" style="display: flex; flex-wrap: nowrap; align-items: flex-end; gap: 30px;">

                        @if ($selectedUser)
                            <input id="selected_user_id" type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                        @endif

                        @if ($isAdmin)
                            <div style="min-width: 260px; flex: 0 0 auto; position: relative;">
                                <label
                                    for="search"
                                    style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;"
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
                                        style="height: 50px; width: 260px; border-radius: 0px; border: 1px solid #d1d5db; background-color: white; padding-left: 48px; padding-right: 16px; font-size: 15px; color: #374151; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s; outline: none; flex-shrink: 0;"
                                        onfocus="this.style.borderColor='#1A3A6B'; this.style.boxShadow='0 0 0 4px rgba(26,58,107,0.1)'"
                                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.05)'"
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
                                style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;"
                            >
                                Fecha inicial
                            </label>

                            <input
                                id="fecha_inicio"
                                name="fecha_inicio"
                                value="{{ $start->toDateString() }}"
                                type="date"
                                style="height: 50px; width: 180px; border-radius: 0px; border: 1px solid #d1d5db; background-color: transparent; padding: 0 20px; font-size: 15px; color: #374151; transition: all 0.2s; outline: none; flex-shrink: 0;"
                                onfocus="this.style.borderColor='#1A3A6B'; this.style.boxShadow='0 0 0 4px rgba(26,58,107,0.1)'"
                                onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                            >
                        </div>

                        <div style="flex: 0 0 auto;">
                            <label
                                for="fecha_fin"
                                style="margin-bottom: 8px; display: block; font-size: 15px; font-weight: 500; color: #1A3A6B; white-space: nowrap;"
                            >
                                Fecha final
                            </label>

                            <input
                                id="fecha_fin"
                                name="fecha_fin"
                                value="{{ $end->toDateString() }}"
                                type="date"
                                style="height: 50px; width: 180px; border-radius: 0px; border: 1px solid #d1d5db; background-color: transparent; padding: 0 20px; font-size: 15px; color: #374151; transition: all 0.2s; outline: none; flex-shrink: 0;"
                                onfocus="this.style.borderColor='#1A3A6B'; this.style.boxShadow='0 0 0 4px rgba(26,58,107,0.1)'"
                                onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                            >
                        </div>

                        <button
                            type="submit"
                            style="display: inline-flex; height: 50px; width: 180px; align-items: center; justify-content: center; gap: 8px; border-radius: 0px; background-color: #1A3A6B; padding: 0 28px; font-size: 15px; font-weight: 600; color: white; box-shadow: 0 4px 6px -1px rgba(26,58,107,0.2); border: none; cursor: pointer; transition: all 0.2s; flex-shrink: 0;"
                            onmouseover="this.style.backgroundColor='#15305a'"
                            onmouseout="this.style.backgroundColor='#1A3A6B'"
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
            <div style="padding-left: 80px; padding-right: 80px; margin-bottom: 50px; background-color: transparent; overflow: hidden;">
                <div style="display: flex; flex-wrap: nowrap; gap: 0px; padding: 0; margin: 0;">
                    <button class="tab-button active" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 15px; font-weight: 500; color: #1A3A6B; border-bottom: 2px solid #1A3A6B; background-color: transparent; cursor: pointer; transition: all 0.2s; outline: none; white-space: nowrap; flex: 1; margin: 0;" data-tab="tab-resumen">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Resumen
                    </button>
                    <button class="tab-button" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 15px; font-weight: 500; color: #6b7280; border-bottom: 2px solid #d1d5db; background-color: transparent; cursor: pointer; transition: all 0.2s; outline: none; white-space: nowrap; flex: 1; margin: 0;" data-tab="tab-detalles">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 7v6m0 0l3-3m-3 3l-3-3"/>
                        </svg>
                        Detalle
                    </button>
                    <button class="tab-button" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 15px; font-weight: 500; color: #6b7280; border-bottom: 2px solid #d1d5db; background-color: transparent; cursor: pointer; transition: all 0.2s; outline: none; white-space: nowrap; flex: 1; margin: 0;" data-tab="tab-nueva">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Cliente-Actividad
                    </button>
                    <button class="tab-button" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 15px; font-weight: 500; color: #6b7280; border-bottom: 2px solid #d1d5db; background-color: transparent; cursor: pointer; transition: all 0.2s; outline: none; white-space: nowrap; flex: 1; margin: 0;" data-tab="tab-general">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        General
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- CONTENEDORES DE PESTAÑAS                                     -->
            <!-- ============================================================ -->
            <div class="space-y-6" style="padding-left: 80px; padding-right: 80px; min-width: 765px;">

                <!-- ==================== TAB 1: RESUMEN ==================== -->
                <div id="tab-resumen" class="tab-content space-y-6">

                    <!-- Gráfica 1: Tiempo trabajado por día -->
                    <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                        <div class="flex items-start gap-3">
                            <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            <div>
                                <p class="text-[15px] font-semibold text-black" style="margin-bottom: 8px;">Tiempo trabajado por día</p>
                                <p class="text-[15px] text-gray-500" style="margin-bottom: 8px;">
                                    @if ($selectedUser)
                                        Barras de horas diarias y línea de promedio del rango seleccionado.
                                    @else
                                        Busca y selecciona un colaborador para visualizar la gráfica.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="h-[360px] w-full">
                            <canvas id="workedTimeChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfica 2: Distribución por cliente -->
                    @if ($selectedUser && !empty($clientLabels))
                        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="flex items-start gap-3 p-4">
                                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <div>
                                    <p class="text-[15px] mb-[8px] font-semibold text-black">Distribución por cliente</p>
                                    <p class="text-[15px] text-gray-500">
                                        Porcentaje de tiempo trabajado por cada cliente en el periodo seleccionado.
                                    </p>
                                </div>
                            </div>
                            <div class="h-[360px] w-full">
                                <canvas id="clientPieChart"></canvas>
                            </div>
                        </div>
                    @endif

                    <!-- Gráfica 3: Distribución por actividad -->
                    @if ($selectedUser && !empty($activityLabels))
                        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="flex items-start gap-3 p-4">
                                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <div>
                                    <p class="text-[15px] mb-[8px] font-semibold text-black">Distribución por actividad</p>
                                    <p class="text-[15px] text-gray-500">
                                        Tiempo trabajado por cada actividad en el periodo seleccionado.
                                    </p>
                                </div>
                            </div>
                            <div class="h-[400px] w-full">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- ==================== TAB 2: DETALLES ==================== -->
                <div id="tab-detalles" class="tab-content space-y-6 hidden">

                    <!-- Gráfica 4: Detalle por actividad -->
                    @if ($selectedUser && !empty($activityLabels))
                        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="flex items-start gap-3 p-4">
                                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div>
                                            <p class="text-[15px] mb-[8px] font-semibold text-black">Detalle por actividad</p>
                                            <p class="text-[15px] text-gray-500">
                                                Distribución diaria de la actividad seleccionada.
                                            </p>
                                        </div>
                                        <div class="w-64">
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Seleccionar actividad</label>
                                            <select id="activitySelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                                <option value="">Selecciona una actividad...</option>
                                                @foreach ($activityLabels as $index => $label)
                                                    <option value="{{ $activityIds[$index] ?? $index + 1 }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[360px] w-full p-4">
                                <canvas id="activityDetailChart"></canvas>
                            </div>
                        </div>
                    @endif

                    <!-- Gráfica 5: Detalle por cliente -->
                    @if ($selectedUser && !empty($clientLabels))
                        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                            <div class="flex items-start gap-3 p-4">
                                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div>
                                            <p class="text-[15px] mb-[8px] font-semibold text-black">Detalle por cliente</p>
                                            <p class="text-[15px] text-gray-500">
                                                Distribución diaria del cliente seleccionado.
                                            </p>
                                        </div>
                                        <div class="w-64">
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Seleccionar cliente</label>
                                            <select id="clientSelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                                <option value="">Selecciona un cliente...</option>
                                                @foreach ($clientLabels as $index => $label)
                                                    <option value="{{ $clientIds[$index] ?? $index }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[360px] w-full p-4">
                                <canvas id="clientDetailChart"></canvas>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- ==================== TAB 3: CLIENTE + ACTIVIDAD ==================== -->
                <div id="tab-nueva" class="tab-content space-y-6 hidden">

                    <!-- Gráfica de Cliente + Actividad -->
                    <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                        <div class="flex items-start gap-3 p-4">
                            <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            <div class="flex-1">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <div>
                                        <p class="text-[15px] mb-[8px] font-semibold text-black">Tiempo por cliente + actividad</p>
                                        <p class="text-[15px] text-gray-500">
                                            Distribución diaria de una combinación específica.
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <div class="w-48">
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Cliente</label>
                                            <select id="clientActivityClientSelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                                <option value="">Todos los clientes</option>
                                                @foreach ($clientLabels as $index => $label)
                                                    <option value="{{ $clientIds[$index] ?? $index }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="w-48">
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Actividad</label>
                                            <select id="clientActivityActivitySelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                                <option value="">Todas las actividades</option>
                                                @foreach ($activityLabels as $index => $label)
                                                    <option value="{{ $activityIds[$index] ?? $index + 1 }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button id="loadClientActivityBtn" class="self-end h-11 rounded-xl bg-[#1A3A6B] px-4 text-sm font-medium text-white hover:bg-[#15305a]">
                                            Cargar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="h-[360px] w-full p-4">
                            <canvas id="clientActivityChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Primero cargas Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <!-- ============================================================ -->
    <!-- JAVASCRIPT PARA PESTAÑAS Y REDIMENSIONAMIENTO DE GRÁFICAS    -->
    <!-- ============================================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // ============================================================
            // 1. LOGICA DE PESTAÑAS - CORREGIDA (HOVER TEMPORAL + CLICK PERMANENTE)
            // ============================================================
            const tabs = document.querySelectorAll('.tab-button');
            const contents = {
                'tab-resumen': document.getElementById('tab-resumen'),
                'tab-detalles': document.getElementById('tab-detalles'),
                'tab-nueva': document.getElementById('tab-nueva'),
                'tab-general': document.getElementById('tab-general')
            };

            // Variable para guardar la pestaña activa (seleccionada por click)
            let activeTabId = null;

            // Función para aplicar estilo "inactivo" (gris)
            function setInactiveStyle(btn) {
                btn.style.color = '#6b7280';
                btn.style.borderBottomColor = '#d1d5db';
                btn.style.borderBottomWidth = '2px';
                btn.style.borderBottomStyle = 'solid';
                btn.classList.remove('active');
            }

            // Función para aplicar estilo "activo" (azul)
            function setActiveStyle(btn) {
                btn.style.color = '#1A3A6B';
                btn.style.borderBottomColor = '#1A3A6B';
                btn.style.borderBottomWidth = '2px';
                btn.style.borderBottomStyle = 'solid';
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
                if (btn.classList.contains('active') || btn.style.color === '#1A3A6B') {
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
                            backgroundColor: 'rgba(26, 58, 107, 0.78)',
                            borderColor: 'rgba(26, 58, 107, 0)',
                            borderWidth: 0,
                            borderRadius: 6,
                            maxBarThickness: 42,
                            order: 2,
                        }, {
                            type: 'line',
                            label: 'Tendencia diaria',
                            data: hoursData,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            borderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgb(16, 185, 129)',
                            pointBorderColor: 'rgba(16, 185, 129, 0)',
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
            'rgba(26, 58, 107, 0.8)', 'rgba(16, 185, 129, 0.8)',
            'rgba(239, 68, 68, 0.8)', 'rgba(245, 158, 11, 0.8)',
            'rgba(139, 92, 246, 0.8)', 'rgba(236, 72, 153, 0.8)',
            'rgba(14, 165, 233, 0.8)', 'rgba(249, 115, 22, 0.8)',
            'rgba(34, 197, 94, 0.8)', 'rgba(168, 85, 247, 0.8)',
            'rgba(236, 64, 122, 0.8)', 'rgba(0, 188, 212, 0.8)',
            'rgba(255, 193, 7, 0.8)', 'rgba(76, 175, 80, 0.8)',
            'rgba(233, 30, 99, 0.8)',
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
                        'rgba(26, 58, 107, 0.8)', 'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)', 'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)', 'rgba(236, 72, 153, 0.8)',
                        'rgba(14, 165, 233, 0.8)', 'rgba(249, 115, 22, 0.8)',
                        'rgba(34, 197, 94, 0.8)', 'rgba(168, 85, 247, 0.8)',
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
                }
            }

            // ============================================================
            // 5. GRÁFICO 4: Detalle por Actividad
            // ============================================================
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
                            detailChart = null;
                        }
                        const ctx = detailCanvas.getContext('2d');
                        ctx.clearRect(0, 0, detailCanvas.width, detailCanvas.height);
                        ctx.font = '16px Arial';
                        ctx.fillStyle = '#999';
                        ctx.textAlign = 'center';
                        ctx.fillText('Selecciona una actividad para ver su detalle', detailCanvas.width / 2, detailCanvas
                            .height / 2);
                        return;
                    }

                    fetch(
                            `{{ route('time.activity-data') }}?activity_id=${activityId}&fecha_inicio={{ $start->toDateString() }}&fecha_fin={{ $end->toDateString() }}`
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
                                        backgroundColor: 'rgba(26, 58, 107, 0.78)',
                                        borderColor: 'rgba(26, 58, 107, 0)',
                                        borderWidth: 0,
                                        borderRadius: 6,
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
                    }
                }
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
                            clientDetailChart = null;
                        }
                        const ctx = clientDetailCanvas.getContext('2d');
                        ctx.clearRect(0, 0, clientDetailCanvas.width, clientDetailCanvas.height);
                        ctx.font = '16px Arial';
                        ctx.fillStyle = '#999';
                        ctx.textAlign = 'center';
                        ctx.fillText('Selecciona un cliente para ver su detalle', clientDetailCanvas.width / 2,
                            clientDetailCanvas.height / 2);
                        return;
                    }

                    fetch(
                            `{{ route('time.client-data') }}?client_id=${clientId}&fecha_inicio={{ $start->toDateString() }}&fecha_fin={{ $end->toDateString() }}`
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
                                        backgroundColor: 'rgba(16, 185, 129, 0.78)',
                                        borderColor: 'rgba(16, 185, 129, 0)',
                                        borderWidth: 0,
                                        borderRadius: 6,
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
                    }
                }
            }

            // ============================================================
            // 7. GRÁFICO 6: Cliente + Actividad
            // ============================================================
            const clientActivityCanvas = document.getElementById('clientActivityChart');
            let clientActivityChart = null;

            if (clientActivityCanvas && typeof Chart !== 'undefined') {
                clientActivityChart = new Chart(clientActivityCanvas, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Horas trabajadas',
                            data: [],
                            backgroundColor: 'rgba(26, 58, 107, 0.78)',
                            borderColor: 'rgba(26, 58, 107, 0)',
                            borderWidth: 0,
                            borderRadius: 6,
                            maxBarThickness: 42,
                        }]
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
                                        const minutos = Math.floor((totalSegundos % 3600) / 60);
                                        const segundos = totalSegundos % 60;
                                        return `${String(horas).padStart(2, '0')}h:${String(minutos).padStart(2, '0')}m:${String(segundos).padStart(2, '0')}s`;
                                    },
                                },
                            },
                        },
                    }
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
                    fecha_inicio: '{{ $start->toDateString() }}',
                    fecha_fin: '{{ $end->toDateString() }}'
                });

                fetch('{{ route("dashboard.client-activity-data") }}?' + params)
                    .then(res => res.json())
                    .then(data => {
                        clientActivityChart.data.labels = data.labels;
                        clientActivityChart.data.datasets[0].data = data.hours;

                        const clientSelect = document.getElementById('clientActivityClientSelector');
                        const activitySelect = document.getElementById('clientActivityActivitySelector');
                        const clientName = clientSelect ? clientSelect.options[clientSelect.selectedIndex]?.text : 'Cliente';
                        const activityName = activitySelect ? activitySelect.options[activitySelect.selectedIndex]?.text : 'Actividad';

                        clientActivityChart.data.datasets[0].label = `${clientName} - ${activityName}`;
                        clientActivityChart.update();
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

                    loadClientActivityChart(clientId, activityId);
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