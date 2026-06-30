@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
    $pct = fn (int $part, int $whole) => $whole > 0 ? round($part * 100 / $whole) : 0;
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Mi productividad</h1>
        <a href="{{ route('time.index') }}" class="text-sm text-blue-700 underline">Volver al cronómetro</a>
    </div>

    <div class="bg-white rounded shadow p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-700 mb-1">Desde</label>
            <input type="date" wire:model.live="from" class="border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm text-gray-700 mb-1">Hasta</label>
            <input type="date" wire:model.live="to" class="border-gray-300 rounded" />
        </div>
        <div class="ml-auto text-right">
            <div class="text-sm text-gray-500">Horas efectivas en el periodo</div>
            <div class="text-2xl font-mono text-gray-900">{{ $fmt($totalSeconds) }}</div>
        </div>
    </div>

    <div class="bg-white rounded shadow p-4">
        <x-export-buttons :formats="$exportFormats" />
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold text-gray-800 mb-3">Distribución por cliente</h2>
            @forelse ($byCustomer as $name => $seconds)
                <div class="flex justify-between text-sm py-1 border-b">
                    <span>{{ $name }}</span>
                    <span class="font-mono">{{ $fmt($seconds) }} ({{ $pct($seconds, $totalSeconds) }}%)</span>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Sin datos.</p>
            @endforelse
        </div>

        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold text-gray-800 mb-3">Distribución por actividad</h2>
            @forelse ($byActivity as $name => $seconds)
                <div class="flex justify-between text-sm py-1 border-b">
                    <span>{{ $name }}</span>
                    <span class="font-mono">{{ $fmt($seconds) }} ({{ $pct($seconds, $totalSeconds) }}%)</span>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Sin datos.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold text-gray-800 mb-3">Detalle de actividades por día</h2>
        <x-time-activity-detail :columns="$activityDetail['columns']" :groups="$activityDetail['groups']" />
    </div>

    <div class="bg-white rounded shadow p-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Control de Asistencia Biométrico (Checador)</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">ID Colaborador / Huella</label>
                <input type="text" id="employee_id" value="{{ auth()->user()->employee_id ?? '' }}" placeholder="Ej: 101" 
                    class="w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Fecha Inicio</label>
                <input type="date" id="fecha_inicio" value="{{ date('Y-m-d', strtotime('-7 days')) }}" 
                    class="w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Fecha Fin</label>
                <input type="date" id="fecha_fin" value="{{ date('Y-m-d') }}" 
                    class="w-full border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
            
            <input type="hidden" id="pago_por_hora" value="100.00">
            <input type="hidden" id="bono_diario" value="50.00">

            <div>
                <button type="button" onclick="revisarHorasChecador()" 
                    class="w-full bg-gray-900 hover:bg-black text-white font-medium px-5 py-2 rounded shadow transition-colors text-sm">
                    Revisar Horas
                </button>
            </div>
        </div>

        <div id="panel_resultados_checador" class="hidden mt-6 border border-gray-100 rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Fecha Jornada</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Tiempo Neto</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Hrs Decimales</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Pago Base</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Bono</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Total del Día</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider text-xs">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla_checador_body" class="bg-white divide-y divide-gray-100">
                        </tbody>
                    <tfoot class="bg-gray-50 font-bold text-gray-900 border-t-2 border-gray-200">
                        <tr>
                            <td class="px-6 py-4">TOTAL ACUMULADO</td>
                            <td id="total_tiempo" class="px-6 py-4 font-mono text-gray-700">00h 00m 00s</td>
                            <td id="total_decimal" class="px-6 py-4">0.00</td>
                            <td id="total_pago_h" class="px-6 py-4">$0.00</td>
                            <td id="total_bonos" class="px-6 py-4">$0.00</td>
                            <td id="total_general" class="px-6 py-4 text-emerald-600 font-semibold">$0.00</td>
                            <td class="px-6 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function revisarHorasChecador() {
    const employeeId = document.getElementById('employee_id').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const pago = document.getElementById('pago_por_hora').value;
    const bono = document.getElementById('bono_diario').value;

    if (!employeeId || !fechaInicio || !fechaFin) {
        alert('Por favor introduce el ID del colaborador y el rango de fechas.');
        return;
    }

    const payload = {
        employee_id: employeeId,
        pago: parseFloat(pago),
        bono: parseFloat(bono),
        inicio: fechaInicio,
        fin: fechaFin
    };

    fetch('{{ route("control-horas.consultar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) throw new Error('Error de servidor.');
        return response.json();
    })
    .then(data => {
        const tbody = document.getElementById('tabla_checador_body');
        tbody.innerHTML = ''; 

        if (!data.resumen || data.resumen.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400 italic">
                        No hay marcas registradas en el dispositivo para este ID en las fechas seleccionadas.
                    </td>
                </tr>`;
            document.getElementById('panel_resultados_checador').classList.remove('hidden');
            return;
        }

        data.resumen.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = item.requiere_revision 
                ? 'bg-red-50/70 hover:bg-red-100/80 transition-colors' 
                : 'hover:bg-gray-50 transition-colors';

            tr.innerHTML = `
                <td class="px-6 py-3.5 font-medium text-gray-900">${item.fecha}</td>
                <td class="px-6 py-3.5 font-mono text-gray-600">${item.neto}</td>
                <td class="px-6 py-3.5 text-gray-600">${item.horas_decimal}</td>
                <td class="px-6 py-3.5 text-gray-600">${item.pago_horas}</td>
                <td class="px-6 py-3.5 text-gray-600">${item.bono}</td>
                <td class="px-6 py-3.5 font-semibold text-gray-800">${item.total}</td>
                <td class="px-6 py-3.5">
                    ${item.requiere_revision 
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">⚠️ Impar / Revisar</span>' 
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Correcto</span>'
                    }
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Totales calculados en el pipeline del servicio
        document.getElementById('total_tiempo').innerText = data.totales_pie.tiempo;
        document.getElementById('total_decimal').innerText = data.totales_pie.decimal;
        document.getElementById('total_pago_h').innerText = data.totales_pie.pago_h;
        document.getElementById('total_bonos').innerText = data.totales_pie.bonos;
        document.getElementById('total_general').innerText = data.totales_pie.general;

        document.getElementById('panel_resultados_checador').classList.remove('hidden');
    })
    .catch(err => {
        console.error(err);
        alert('Hubo un error al intentar consultar las marcas del checador.');
    });
}
</script>