@php
    $fmtSeconds = function (int $seconds) {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    };
@endphp

<x-app-layout>
    <div class="max-w-6xl mx-auto p-6 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Panel de Control</h1>
                <p class="mt-1 text-sm text-gray-500">Tiempo trabajado por dia dentro del rango seleccionado.</p>
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
                            class="h-11 w-64 rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
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
                        class="h-11 rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                </div>

                <div>
                    <label for="fecha_fin" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">Hasta</label>
                    <input
                        id="fecha_fin"
                        name="fecha_fin"
                        value="{{ $end->toDateString() }}"
                        type="date"
                        class="h-11 rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                </div>

                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-medium text-white transition-colors hover:bg-blue-800">
                    Buscar
                </button>
            </form>
        </div>

        @if ($isAdmin && $users->isNotEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Resultados</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($users as $user)
                        <a
                            href="{{ route('time.dashboard', ['user_id' => $user->id, 'search' => $search, 'fecha_inicio' => $start->toDateString(), 'fecha_fin' => $end->toDateString()]) }}"
                            class="inline-flex items-center rounded-lg border px-3 py-2 text-sm transition-colors {{ $selectedUser?->id === $user->id ? 'border-blue-600 bg-blue-50 text-blue-800' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
                        >
                            <span class="font-medium">{{ trim($user->name.' '.($user->last_name ?? '')) }}</span>
                            <span class="ml-2 text-xs text-gray-500">ID {{ $user->id }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Colaborador</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ $selectedUser ? trim($selectedUser->name.' '.($selectedUser->last_name ?? '')) : 'Selecciona un usuario' }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rango</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total trabajado</p>
                <p class="mt-2 font-mono text-lg font-semibold text-gray-900">{{ $fmtSeconds($totalSeconds) }}</p>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Tiempo trabajado por dia</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        @if ($selectedUser)
                            Barras de horas diarias y linea de promedio del rango seleccionado.
                        @else
                            Busca y selecciona un colaborador para visualizar la grafica.
                        @endif
                    </p>
                </div>
            </div>

            <div class="h-[360px]">
                <canvas id="workedTimeChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('workedTimeChart');

            if (! canvas || typeof Chart === 'undefined') {
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: @json($labels),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Horas trabajadas',
                            data: @json($hours),
                            backgroundColor: 'rgba(37, 99, 235, 0.78)',
                            borderColor: 'rgb(29, 78, 216)',
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 42,
                            order: 2,
                        },
                        {
                            type: 'line',
                            label: 'Tendencia diaria', // Cambiado de 'Promedio del periodo'
                            data: @json($hours),       // Usamos la misma variable de las barras azules
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            borderWidth: 2,
                            pointRadius: 4,            // Un poco más grande para que resalte sobre la barra
                            pointHoverRadius: 6,
                            tension: 0.35,             // Mantiene la curvatura suave entre puntos
                            fill: false,
                            order: 1,                  // 'order: 1' asegura que la línea se dibuje por ENCIMA de las barras
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(2)} horas`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback(value) {
                                    return `${value} h`;
                                },
                            },
                        },
                    },
                },
            });
        });
    </script>
</x-app-layout>
