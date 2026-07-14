@php
    $fmtSeconds = function (int $seconds) {
        $seconds = max(0, $seconds);
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    };
@endphp

<x-app-layout>
    <div class="w-full bg-[#f4f4f4] min-h-screen" style="overflow-x: auto;">

        <!-- Contenedor interno con min-width: 700px -->
        <div style="min-width: 700px; padding: 0; margin: 0;">

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
                    <button style="display: flex; align-items: center; gap: 15px; padding: 0; border: none; background-color: transparent; color: #6b7280; font-size: 15px; cursor: pointer; transition: all 0.2s; hover:color: #1A3A6B; white-space: nowrap;">
                        <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar
                    </button>

                    <button style="display: flex; align-items: center; gap: 15px; padding: 0; border: none; background-color: transparent; color: #6b7280; font-size: 15px; cursor: pointer; transition: all 0.2s; hover:color: #1A3A6B; white-space: nowrap;">
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
                            <div style="min-width: 260px; flex: 0 0 auto;">
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
                                        oninput="document.getElementById('selected_user_id')?.remove()"
                                        style="height: 50px; width: 260px; border-radius: 0px; border: 1px solid #d1d5db; background-color: white; padding-left: 48px; padding-right: 16px; font-size: 15px; color: #374151; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s; outline: none; flex-shrink: 0;"
                                        onfocus="this.style.borderColor='#1A3A6B'; this.style.boxShadow='0 0 0 4px rgba(26,58,107,0.1)'"
                                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.05)'"
                                    >

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

            @if ($isAdmin && $users->isNotEmpty())

            <div style="border: 1px solid #e5e7eb; border-radius: 0px; background-color: white; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; min-width: max-content;">

                <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">

                    <div>
                        <h2 style="font-size: 18px; font-weight: 600; color: #111827; white-space: nowrap;">
                            Resultados
                        </h2>

                        <p style="margin-top: 4px; font-size: 15px; color: #6b7280; white-space: nowrap;">
                            Selecciona un colaborador para visualizar su información
                        </p>
                    </div>

                </div>

                <div style="display: flex; flex-wrap: nowrap; gap: 12px; overflow: hidden;">

                    @foreach ($users as $user)

                        <a
                            href="{{ route('time.dashboard', [
                                'user_id' => $user->id,
                                'search' => $search,
                                'fecha_inicio' => $start->toDateString(),
                                'fecha_fin' => $end->toDateString()
                            ]) }}"
                            style="display: inline-flex; align-items: center; gap: 12px; border-radius: 0px; border: 1px solid {{ $selectedUser?->id === $user->id ? '#1A3A6B' : '#e5e7eb' }}; padding: 12px 20px; text-decoration: none; transition: all 0.2s; background-color: {{ $selectedUser?->id === $user->id ? 'rgba(26,58,107,0.05)' : 'white' }}; box-shadow: {{ $selectedUser?->id === $user->id ? '0 1px 2px rgba(0,0,0,0.05)' : 'none' }}; flex-shrink: 0;"
                            onmouseover="this.style.borderColor='rgba(26,58,107,0.3)'; this.style.backgroundColor='#f9fafb'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.05)'"
                            onmouseout="this.style.borderColor='{{ $selectedUser?->id === $user->id ? '#1A3A6B' : '#e5e7eb' }}'; this.style.backgroundColor='{{ $selectedUser?->id === $user->id ? 'rgba(26,58,107,0.05)' : 'white' }}'; this.style.boxShadow='{{ $selectedUser?->id === $user->id ? '0 1px 2px rgba(0,0,0,0.05)' : 'none' }}'"
                        >

                            <div style="display: flex; height: 36px; width: 36px; align-items: center; justify-content: center; border-radius: 0px; background-color: {{ $selectedUser?->id === $user->id ? '#1A3A6B' : '#f3f4f6' }}; color: {{ $selectedUser?->id === $user->id ? 'white' : '#4b5563' }}; font-weight: 600; font-size: 15px; flex-shrink: 0;">
                                {{ strtoupper(substr($user->name,0,1)) }}
                            </div>

                            <div>

                                <p style="font-size: 15px; font-weight: 600; color: #111827; margin: 0; white-space: nowrap;">
                                    {{ trim($user->name.' '.($user->last_name ?? '')) }}
                                </p>

                                <p style="font-size: 15px; color: #6b7280; margin: 0; white-space: nowrap;">
                                    ID {{ $user->id }}
                                </p>

                            </div>

                        </a>

                    @endforeach

                </div>

            </div>

            @endif

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
            // 1. LOGICA DE PESTAÑAS
            // ============================================================
            const tabs = document.querySelectorAll('.tab-button');
            const contents = {
                resumen: document.getElementById('tab-resumen'),
                detalles: document.getElementById('tab-detalles'),
                nueva: document.getElementById('tab-nueva')
            };

            function switchTab(tabId) {
                Object.values(contents).forEach(el => el.classList.add('hidden'));
                const target = document.getElementById(tabId);
                if (target) target.classList.remove('hidden');

                tabs.forEach(btn => {
                    btn.classList.remove('active', 'text-[#1A3A6B]', 'border-[#1A3A6B]');
                    btn.classList.add('text-gray-500', 'border-transparent');
                    if (btn.dataset.tab === tabId) {
                        btn.classList.add('active', 'text-[#1A3A6B]', 'border-[#1A3A6B]');
                        btn.classList.remove('text-gray-500', 'border-transparent');
                    }
                });

                const visibleCanvas = target.querySelectorAll('canvas');
                visibleCanvas.forEach(canvas => {
                    const chart = Chart.getChart(canvas);
                    if (chart) {
                        chart.resize();
                    }
                });
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    switchTab(this.dataset.tab);
                });
            });

            // ============================================================
            // 2. GRÁFICO 1: Barras - Horas por día
            // ============================================================
            const canvas = document.getElementById('workedTimeChart');
            if (canvas && typeof Chart !== 'undefined') {
                // Obtener los datos de horas del rango SELECCIONADO
                const hoursData = @json($hours);
                const maxHours = Math.max(...hoursData, 0);
                
                // Si el máximo es 0, usar 0.1 para que se vea algo
                const maxY = Math.max(maxHours, 0.1);
                
                // Calcular stepSize para 5 marcas exactas (0, 25%, 50%, 75%, 100%)
                const stepSize = maxY / 4;
                
                // REDONDEAR stepSize para evitar problemas con números muy pequeños
                const roundedStepSize = Math.ceil(stepSize * 10000) / 10000;
                
                console.log('📊 Datos de horas:', hoursData);
                console.log('📈 Máximo del rango:', maxY);
                console.log('📐 Step size calculado:', stepSize);
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
                                max: maxY,
                                grid: { display: true, color: 'rgba(0, 0, 0, 0.06)' },
                                border: { display: true, color: 'rgba(0, 0, 0, 0.12)' },
                                ticks: {
                                    font: { size: 15 },
                                    stepSize: roundedStepSize,
                                    // Forzar 5 marcas exactas
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
            // 3. GRÁFICO 2: Pastel - Clientes
            // ============================================================
            const pieCanvas = document.getElementById('clientPieChart');
            if (pieCanvas && typeof Chart !== 'undefined') {
                const clientLabels = @json($clientLabels);
                const clientData = @json($clientData);

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

                if (clientLabels.length > 0) {
                    const legendFontSize = clientLabels.length > 8 ? 12 : 14;
                    new Chart(pieCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: clientLabels,
                            datasets: [{
                                data: clientData,
                                backgroundColor: getColors(clientLabels.length),
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
                                        padding: clientLabels.length > 8 ? 8 : 15,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        ...(clientLabels.length > 10 && {
                                            generateLabels: function(chart) {
                                                const data = chart.data;
                                                return data.labels.map((label, i) => ({
                                                    text: label,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: false,
                                                    index: i,
                                                }));
                                            }
                                        }),
                                    },
                                },
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
                                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) :
                                                0;
                                            const horas = context.parsed.toFixed(2);
                                            return `${context.label}: ${horas} horas (${percentage}%)`;
                                        },
                                    },
                                },
                            },
                            ...(clientLabels.length > 8 && {
                                layout: { padding: { bottom: 20 } },
                            }),
                        },
                    });
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
        });
    </script>
</x-app-layout>