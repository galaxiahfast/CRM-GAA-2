@php
    $fmtSeconds = function (int $seconds) {
        $seconds = max(0, $seconds);
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    };
@endphp

<x-app-layout>
    <div class="max-w-6xl mx-auto p-6 space-y-4 bg-[#f4f4f4] min-h-screen">
        <!-- Header -->
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-gray-200 pb-4 bg-[#f4f4f4]">
            <div class="flex items-start gap-3">
                <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                <div>
                    <p class="text-[15px] font-semibold text-black">Panel de Control</p>
                    <p class="text-[15px] text-gray-500 mt-[15px]">Tiempo trabajado por día dentro del rango seleccionado.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('time.dashboard') }}" class="flex flex-wrap items-end gap-3">
                @if ($selectedUser)
                    <input id="selected_user_id" type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                @endif

                @if ($isAdmin)
                    <div>
                        <label for="search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Colaborador</label>
                        <input
                            id="search"
                            name="search"
                            value="{{ $search }}"
                            type="search"
                            placeholder="ID, nombre o correo"
                            oninput="document.getElementById('selected_user_id')?.remove()"
                            class="h-11 w-64 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20"
                        >
                    </div>
                @endif

                <div>
                    <label for="fecha_inicio" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Desde</label>
                    <input
                        id="fecha_inicio"
                        name="fecha_inicio"
                        value="{{ $start->toDateString() }}"
                        type="date"
                        class="h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20"
                    >
                </div>

                <div>
                    <label for="fecha_fin" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Hasta</label>
                    <input
                        id="fecha_fin"
                        name="fecha_fin"
                        value="{{ $end->toDateString() }}"
                        type="date"
                        class="h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20"
                    >
                </div>

                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-[#1A3A6B] px-4 text-sm font-medium text-white transition-colors hover:bg-[#15305a]">
                    Buscar
                </button>
            </form>
        </div>

        @if ($isAdmin && $users->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Resultados</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($users as $user)
                        <a
                            href="{{ route('time.dashboard', ['user_id' => $user->id, 'search' => $search, 'fecha_inicio' => $start->toDateString(), 'fecha_fin' => $end->toDateString()]) }}"
                            class="inline-flex items-center rounded-xl border px-3 py-2 text-sm transition-colors {{ $selectedUser?->id === $user->id ? 'border-[#1A3A6B] bg-blue-50 text-[#1A3A6B]' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
                        >
                            <span class="font-medium">{{ trim($user->name.' '.($user->last_name ?? '')) }}</span>
                            <span class="ml-2 text-xs text-gray-500">ID {{ $user->id }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- MENÚ DE PESTAÑAS                                              -->
        <!-- ============================================================ -->
        <div class="mt-6 border-b border-gray-200 bg-white rounded-t-2xl shadow-sm overflow-hidden">
            <div class="flex flex-wrap gap-1 px-4 pt-2">
                <button class="tab-button active px-4 py-2 text-sm font-medium text-[#1A3A6B] border-b-2 border-[#1A3A6B] focus:outline-none transition-colors" data-tab="tab-resumen">
                    📊 Resumen
                </button>
                <button class="tab-button px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 focus:outline-none transition-colors" data-tab="tab-detalles">
                    🔍 Detalles
                </button>
                <button class="tab-button px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 focus:outline-none transition-colors" data-tab="tab-nueva">
                    📈 Cliente + Actividad
                </button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- CONTENEDORES DE PESTAÑAS                                     -->
        <!-- ============================================================ -->
        <div class="space-y-6">

            <!-- ==================== TAB 1: RESUMEN ==================== -->
            <div id="tab-resumen" class="tab-content space-y-6">

                <!-- Gráfica 1: Tiempo trabajado por día -->
                <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                    <div class="flex items-start gap-3 p-4">
                        <svg class="w-[20px] h-[20px] text-[#1A3A6B] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <div>
                            <p class="text-[15px] font-semibold text-black">Tiempo trabajado por día</p>
                            <p class="text-[15px] text-gray-500 mt-[15px]">
                                @if ($selectedUser)
                                    Barras de horas diarias y línea de promedio del rango seleccionado.
                                @else
                                    Busca y selecciona un colaborador para visualizar la gráfica.
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="h-[360px] w-full p-4">
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
                                <p class="text-[15px] font-semibold text-black">Distribución por cliente</p>
                                <p class="text-[15px] text-gray-500 mt-[15px]">
                                    Porcentaje de tiempo trabajado por cada cliente en el periodo seleccionado.
                                </p>
                            </div>
                        </div>
                        <div class="h-[360px] w-full p-4">
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
                                <p class="text-[15px] font-semibold text-black">Distribución por actividad</p>
                                <p class="text-[15px] text-gray-500 mt-[15px]">
                                    Tiempo trabajado por cada actividad en el periodo seleccionado.
                                </p>
                            </div>
                        </div>
                        <div class="h-[400px] w-full p-4">
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
                                        <p class="text-[15px] font-semibold text-black">Detalle por actividad</p>
                                        <p class="text-[15px] text-gray-500 mt-[15px]">
                                            Distribución diaria de la actividad seleccionada.
                                        </p>
                                    </div>
                                    <div class="w-64">
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Seleccionar actividad</label>
                                        <select id="activitySelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
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
                                        <p class="text-[15px] font-semibold text-black">Detalle por cliente</p>
                                        <p class="text-[15px] text-gray-500 mt-[15px]">
                                            Distribución diaria del cliente seleccionado.
                                        </p>
                                    </div>
                                    <div class="w-64">
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Seleccionar cliente</label>
                                        <select id="clientSelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
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
                                    <p class="text-[15px] font-semibold text-black">Tiempo por cliente + actividad</p>
                                    <p class="text-[15px] text-gray-500 mt-[15px]">
                                        Distribución diaria de una combinación específica.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <div class="w-48">
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Cliente</label>
                                        <select id="clientActivityClientSelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
                                            <option value="">Todos los clientes</option>
                                            @foreach ($clientLabels as $index => $label)
                                                <option value="{{ $clientIds[$index] ?? $index }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-48">
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Actividad</label>
                                        <select id="clientActivityActivitySelector" class="w-full h-11 rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20 bg-white">
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
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            type: 'bar',
                            label: 'Horas trabajadas',
                            data: @json($hours),
                            backgroundColor: 'rgba(26, 58, 107, 0.78)',
                            borderColor: 'rgba(26, 58, 107, 0)',
                            borderWidth: 0,
                            borderRadius: 6,
                            maxBarThickness: 42,
                            order: 2,
                        }, {
                            type: 'line',
                            label: 'Tendencia diaria',
                            data: @json($hours),
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
                        layout: { padding: { top: 0 } },
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
                    },
                    plugins: [{
                        id: 'legendSpacing',
                        beforeInit(chart) {
                            const fit = chart.legend.fit;
                            chart.legend.fit = function() {
                                fit.call(this);
                                this.height += 15;
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