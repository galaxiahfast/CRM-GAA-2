@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
    $pct = fn (int $part, int $whole) => $whole > 0 ? round($part * 100 / $whole) : 0;
@endphp

<div class="min-h-screen bg-[#f4f4f4]">
    <div class="max-w-5xl mx-auto space-y-6 p-6">
        
        <!-- Título Mi productividad -->
        <div class="max-w-[700px] mx-auto">
            <h1 class="text-2xl font-semibold text-black text-left">Mi productividad</h1>
        </div>

        <!-- Contenedor 1: Rango de fechas -->
        <div class="bg-white rounded shadow p-[15px] max-w-[700px] mx-auto">

            <!-- Encabezado -->
            <div class="flex items-center gap-[15px] mb-[15px]">
                <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                </svg>

                <h2 class="text-[15px] font-semibold leading-[15px] text-black">
                    Rango de fechas
                </h2>
            </div>

            <!-- Descripción -->
            <p class="text-[15px] leading-[30px] text-gray-500 text-justify">
                Selecciona el periodo que deseas consultar para visualizar tus horas efectivas,
                la distribución por cliente y actividad, así como el detalle diario de tu productividad.
            </p>

            <!-- Filtros -->
            <div class="flex flex-wrap items-end gap-[15px] mt-[15px]">

                <!-- Desde -->
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[15px] leading-[15px] text-black mb-[15px]">
                        Desde
                    </label>

                    <input
                        type="date"
                        wire:model="from"
                        class="border border-gray-300 rounded text-[15px] text-black p-[15px] w-full h-[56px]"
                    />
                </div>

                <!-- Hasta -->
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[15px] leading-[15px] text-black mb-[15px]">
                        Hasta
                    </label>

                    <input
                        type="date"
                        wire:model="to"
                        class="border border-gray-300 rounded text-[15px] text-black p-[15px] w-full h-[56px]"
                    />
                </div>

                <!-- Botón Buscar -->
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[15px] leading-[15px] text-black mb-[15px]">
                        &nbsp;
                    </label>

                    <button
                        wire:click="applyFilters"
                        class="bg-[#1a3a6b] hover:bg-[#15305a] text-white text-[15px] font-medium rounded transition-colors duration-200 flex items-center justify-center gap-[10px] w-full h-[56px]"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Buscar
                    </button>
                </div>

            </div>

        </div>

        <!-- Contenedor 2: Exportar + Horas efectivas -->
        <div class="bg-white rounded shadow p-[15px] max-w-[700px] mx-auto">

            <!-- Encabezado -->
            <div class="flex items-center gap-[15px] mb-[15px]">
                <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>

                <h2 class="text-[15px] font-semibold leading-[15px] text-black">
                    Exportar información
                </h2>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Exportar botones -->
                <div class="flex-1 min-w-[150px]">
                    <x-export-buttons :formats="$exportFormats" class="p-[15px]" />
                </div>

                <!-- Horas efectivas -->
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[15px] leading-[15px] text-black mb-[15px] text-right">
                        Horas efectivas
                    </label>

                    <div class="border border-gray-300 bg-white rounded text-[15px] text-black p-[15px] flex items-center justify-between h-[56px]">

                        <span>
                            {{ $fmt($totalSeconds) }}
                        </span>

                        <svg class="w-5 h-5 text-black shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                        </svg>

                    </div>

                </div>
            </div>

        </div>

        <!-- Distribución por cliente -->
        <div class="bg-white rounded shadow p-4 max-w-[700px] mx-auto">
            <h2 class="font-semibold text-black mb-3 text-[15px]">Distribución por cliente</h2>
            @forelse ($byCustomer as $name => $seconds)
                <div class="flex justify-between text-[15px] py-1 border-b text-black">
                    <span class="text-black">{{ $name }}</span>
                    <span class="font-mono text-black">{{ $fmt($seconds) }} ({{ $pct($seconds, $totalSeconds) }}%)</span>
                </div>
            @empty
                <p class="text-[15px] text-black">Sin datos.</p>
            @endforelse
        </div>

        <!-- Distribución por actividad -->
        <div class="bg-white rounded shadow p-4 max-w-[700px] mx-auto">
            <h2 class="font-semibold text-black mb-3 text-[15px]">Distribución por actividad</h2>
            @forelse ($byActivity as $name => $seconds)
                <div class="flex justify-between text-[15px] py-1 border-b text-black">
                    <span class="text-black">{{ $name }}</span>
                    <span class="font-mono text-black">{{ $fmt($seconds) }} ({{ $pct($seconds, $totalSeconds) }}%)</span>
                </div>
            @empty
                <p class="text-[15px] text-black">Sin datos.</p>
            @endforelse
        </div>

        <!-- Detalle de actividades por día - Versión Cards con etiquetas -->
<div class="bg-white rounded shadow p-4 w-[90%] mx-auto my-0" x-data="{
    expandedRows: new Set(),
    toggleRow(index) {
        if (this.expandedRows.has(index)) {
            this.expandedRows.delete(index);
        } else {
            this.expandedRows.add(index);
        }
    },
    isExpanded(index) {
        return this.expandedRows.has(index);
    }
}">
    <h2 class="font-semibold text-black mb-3 text-[15px]">Detalle de actividades por día</h2>

    @php
        $colIndex = array_flip($activityDetail['columns']);
        $idxInicio = $colIndex['Inicio'] ?? 0;
        $idxFin = $colIndex['Fin'] ?? 1;
        $idxActividad = $colIndex['Actividad'] ?? 2;
        $idxCliente = $colIndex['Cliente'] ?? 3;
        $idxTiempo = $colIndex['Tiempo efectivo'] ?? 4;
        $idxPuesto = $colIndex['Puesto profesional'] ?? 5;
        $idxArea = $colIndex['Área física'] ?? 6;
        $idxObservaciones = $colIndex['Observaciones'] ?? 7;
        $rowCounter = 0;
    @endphp

    <div class="space-y-3">
        @forelse ($activityDetail['groups'] as $group)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <!-- Cabecera del día -->
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[15px] text-black font-medium">📅 {{ $group['date'] }}</span>
                    <span class="text-[15px] text-black">
                        {{ count($group['rows']) }} actividad{{ count($group['rows']) > 1 ? 'es' : '' }}
                    </span>
                </div>
                
                <!-- Items del día -->
                @foreach ($group['rows'] as $row)
                    @php $rowId = $rowCounter++; @endphp
                    <div class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition-colors duration-150">
                        <!-- Resumen de la actividad -->
                        <div class="flex flex-wrap items-center gap-2 px-4 py-3">
                            <!-- Icono de actividad -->
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            
                            <!-- Hora -->
                            <span class="text-[13px] text-gray-500 whitespace-nowrap">
                                {{ $row[$idxInicio] ?? '-' }} - {{ $row[$idxFin] ?? '-' }}
                            </span>
                            
                            <span class="text-[13px] text-gray-300">|</span>
                            
                            <!-- Actividad -->
                            <span class="text-[15px] text-black font-medium">{{ $row[$idxActividad] ?? '-' }}</span>
                            
                            <span class="text-[13px] text-gray-300">|</span>
                            
                            <!-- Cliente -->
                            <span class="text-[15px] text-black">🏢 {{ $row[$idxCliente] ?? '-' }}</span>
                            
                            <!-- Tiempo y porcentaje (a la derecha) -->
                            <div class="flex items-center gap-3 ml-auto">
                                <span class="text-[15px] text-black">⏱️ {{ $row[$idxTiempo] ?? '00:00:00' }}</span>
                                @php
                                    $segundos = 0;
                                    if (isset($row[$idxTiempo]) && preg_match('/(\d{2}):(\d{2}):(\d{2})/', $row[$idxTiempo], $matches)) {
                                        $segundos = intval($matches[1]) * 3600 + intval($matches[2]) * 60 + intval($matches[3]);
                                    }
                                @endphp
                                <span class="inline-block bg-gray-100 px-3 py-1 rounded-full text-[15px] text-black min-w-[50px] text-center">
                                    {{ $pct($segundos, $totalSeconds) }}%
                                </span>
                                <!-- Botón expandir -->
                                <button @click="toggleRow({{ $rowId }})" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 focus:outline-none ml-1">
                                    <svg x-show="!isExpanded({{ $rowId }})" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <svg x-show="isExpanded({{ $rowId }})" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Detalles expandibles -->
                        <div x-show="isExpanded({{ $rowId }})" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="bg-gray-50 px-4 py-3 border-t border-gray-100">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-[15px]">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-500 min-w-[140px]">🧑‍💼 Puesto profesional:</span>
                                    <span class="text-black">{{ $row[$idxPuesto] ?? '—' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-500 min-w-[140px]">📍 Área física:</span>
                                    <span class="text-black">{{ $row[$idxArea] ?? '—' }}</span>
                                </div>
                                <div class="flex items-center gap-2 col-span-2">
                                    <span class="font-medium text-gray-500 min-w-[140px]">📝 Observaciones:</span>
                                    <span class="text-black">{{ $row[$idxObservaciones] ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z" />
                </svg>
                <p class="text-[15px] text-black">Sin actividades registradas en el periodo seleccionado.</p>
            </div>
        @endforelse
    </div>
</div>

    </div>
</div>