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
                <!-- Icono 20x20 -->
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Colaborador</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ $selectedUser ? trim($selectedUser->name.' '.($selectedUser->last_name ?? '')) : 'Selecciona un usuario' }}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rango</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total trabajado</p>
                <p class="mt-2 font-mono text-lg font-semibold text-gray-900">{{ $fmtSeconds($totalSeconds) }}</p>
            </div>
        </div>

        <!-- Chart - Contenedor padre sin bordes -->
        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
                <div class="flex items-start gap-3">
                    <!-- Icono 20x20 -->
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
                
                <div class="h-[360px] w-full">
                    <canvas id="workedTimeChart"></canvas>
                </div>
        </div>

        <!-- Gráfico de pastel - Distribución por cliente -->
        @if ($selectedUser && !empty($clientLabels))
        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
            <div class="flex items-start gap-3">
                <!-- Icono -->
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
            <div class="h-[360px] w-full mt-4">
                <canvas id="clientPieChart"></canvas>
            </div>
        </div>
        @endif

        <!-- Gráfico de actividades - Distribución por actividad -->
        @if ($selectedUser && !empty($activityLabels))
        <div class="rounded-2xl bg-[#f4f4f4] overflow-hidden">
            <div class="flex items-start gap-3">
                <!-- Icono -->
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
            <div class="h-[400px] w-full">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        @endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js">
    </script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('workedTimeChart');

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

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

                layout: {
                    padding: {
                        top: 0,
                    },
                },

                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            boxWidth: 14,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            font: {
                                size: 15,
                            },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1f2937',
                        bodyColor: '#1f2937',
                        titleFont: {
                            size: 15,
                        },
                        bodyFont: {
                            size: 15,
                        },
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
                                const tiempo = `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
                                return `${context.dataset.label}: ${tiempo}`;
                            },
                        },
                    },
                },

                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.06)',
                        },
                        border: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.12)',
                        },
                        ticks: {
                            display: true,
                            font: {
                                size: 15,
                            },
                        },
                    },

                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.06)',
                        },
                        border: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.12)',
                        },
                        ticks: {
                            font: {
                                size: 15,
                            },
                            callback(value) {
                                // Convierte el número decimal a HH:MM:SS
                                const totalSegundos = Math.round(value * 3600);
                                const horas = Math.floor(totalSegundos / 3600);
                                const minutos = Math.floor((totalSegundos % 3600) / 60);
                                const segundos = totalSegundos % 60;
                                // ✅ Agrega "h" al final
                                return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')} h`;
                            },
                        },
                    },
                },
            },

            plugins: [{
                id: 'legendSpacing',
                beforeInit(chart) {
                    const fit = chart.legend.fit;

                    chart.legend.fit = function () {
                        fit.call(this);
                        this.height += 15;
                    };
                }
            }]
        });
    });

    // Gráfico de pastel - Clientes
    const pieCanvas = document.getElementById('clientPieChart');

    if (pieCanvas && typeof Chart !== 'undefined') {
        const clientLabels = @json($clientLabels);
        const clientData = @json($clientData);
        
        // ✅ Función para generar colores automáticamente (incluso si son muchos)
        const getColors = (count) => {
            const baseColors = [
                'rgba(26, 58, 107, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(14, 165, 233, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(236, 64, 122, 0.8)',
                'rgba(0, 188, 212, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(233, 30, 99, 0.8)',
            ];
            
            // Si hay más clientes que colores, repetir con opacidad variada
            while (baseColors.length < count) {
                const idx = baseColors.length % baseColors.length;
                const color = baseColors[idx];
                // Variar la opacidad para distinguirlos
                const opacity = 0.5 + (idx % 3) * 0.15;
                baseColors.push(color.replace(/0\.8/, opacity.toFixed(1)));
            }
            
            return baseColors.slice(0, count);
        };

        if (clientLabels.length > 0) {
            // ✅ Leyenda SIEMPRE a la derecha
            const legendPosition = 'right';  // ← Siempre a la derecha
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
                            position: legendPosition,  // ← Cambia según cantidad
                            labels: {
                                font: {
                                    size: legendFontSize,  // ← Fuente más pequeña si hay muchos
                                },
                                padding: clientLabels.length > 8 ? 8 : 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                // ✅ Si hay muchos clientes, mostrar en columnas
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
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    const horas = context.parsed.toFixed(2);
                                    return `${context.label}: ${horas} horas (${percentage}%)`;
                                },
                            },
                        },
                    },
                    // ✅ Si hay muchos clientes y la leyenda está abajo, ajustar el gráfico
                    ...(clientLabels.length > 8 && {
                        layout: {
                            padding: {
                                bottom: 20,
                            },
                        },
                    }),
                },
            });
        }
    }

    // Gráfico de barras horizontales - Actividades (Barras a la izquierda, etiquetas a la derecha)
    const activityCanvas = document.getElementById('activityChart');

    if (activityCanvas && typeof Chart !== 'undefined') {
        const activityLabels = @json($activityLabels);
        const activityData = @json($activityData);
        
        if (activityLabels.length > 0) {
            // Colores para las actividades
            const activityColors = [
                'rgba(26, 58, 107, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(14, 165, 233, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)',
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
                    indexAxis: 'y',  // ← Barras horizontales
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
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
                                    const percentage = total > 0 ? ((context.parsed.x / total) * 100).toFixed(1) : 0;
                                    const horas = context.parsed.x.toFixed(2);
                                    return `${horas} horas (${percentage}%)`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            reverse: true,  // ← Barras a la izquierda
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.06)',
                            },
                            border: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.12)',
                            },
                            ticks: {
                                font: {
                                    size: 13,
                                },
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
                            position: 'right',  // ← 🔥 ETIQUETAS A LA DERECHA
                            grid: {
                                display: false,
                            },
                            border: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.12)',
                            },
                            ticks: {
                                font: {
                                    size: 13,
                                },
                            },
                        },
                    },
                },
            });
        }
    }
</script>
</x-app-layout>