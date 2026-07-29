<div wire:ignore class="min-h-[calc(100dvh-90px)] w-full min-w-[800px] bg-[#f4f4f4] text-[15px]">

<style>
    .attendance-clock-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .attendance-clock-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
    .attendance-clock-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
    .attendance-clock-scrollbar { scrollbar-width: thin; scrollbar-color: #1A3A6B #f8fafc; }
</style>

<div class="min-h-[calc(100dvh-90px)] w-full min-w-[800px] bg-[#f4f4f4]">
    <div class="w-full min-w-[800px]">
        <div class="flex items-center justify-between gap-20 whitespace-nowrap border-b-2 border-[#e5e7eb] px-10 py-10 text-[15px] text-gray-500">
            <div class="flex items-center gap-[15px]">
                <span class="font-medium">Actividades</span>
                <span class="font-light text-gray-300">&gt;</span>
                <span class="font-medium">Control de Horas</span>
                <span class="font-light text-gray-300">&gt;</span>
                <span class="font-semibold text-[#1A3A6B]">Reloj Checador</span>
            </div>
            <div class="flex items-center gap-[30px]">
                <button type="button" onclick="exportarChecador('pdf')" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar PDF
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" />
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>

        <div class="mx-20 mt-20 flex items-start gap-[15px]">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center bg-[#1A3A6B]/10 text-[#1A3A6B]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Reloj Checador</h1>
                <p class="mt-1 text-[15px] text-gray-500">Consulta tus marcas biométricas y el resumen acumulado del periodo.</p>
            </div>
        </div>

        <p class="mx-20 mt-6 max-w-2xl text-[15px] leading-8 text-gray-500">
            Visualiza las marcas registradas por día,<br>
            con cálculo de tiempo y acumulados durante el periodo seleccionado.
        </p>

        <input type="hidden" id="employee_id" value="{{ auth()->user()->employee_id ?? '' }}">

        <div class="mx-20 mb-10 mt-10 flex flex-nowrap items-end gap-[30px]">
            <div class="w-[180px] shrink-0">
                <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Fecha Inicio</label>
                <input type="date" id="fecha_inicio" value="{{ date('Y-m-d', strtotime('-14 days')) }}"
                    class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10">
            </div>
            <div class="w-[180px] shrink-0">
                <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Fecha Fin</label>
                <input type="date" id="fecha_fin" value="{{ date('Y-m-d') }}"
                    class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10">
            </div>
            <div class="w-[180px] shrink-0">
                <button id="btn_revisar_horas" type="button" onclick="revisarHorasChecador()"
                    class="inline-flex h-[50px] w-full items-center justify-center gap-2 rounded-none border-0 bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-md transition-colors hover:bg-[#15305a] disabled:cursor-wait disabled:opacity-70">
                    <svg id="icono_buscar_checador" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <svg id="carga_checador" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="texto_boton_checador">Revisar Horas</span>
                </button>
            </div>
        </div>

        <div id="panel_resultados_checador" class="mx-20 mb-10 overflow-hidden bg-[#ffffff] shadow-sm">
            <!-- Contenedor con borde punteado que abarca la barra de exportación y la tabla -->
            <div style="border: 2px dashed #9ca3af; border-radius: 12px; background-color: #ffffff; padding: 4px; font-size: 15px;">
                <div id="checador_export_bar" class="hidden border-b border-[#e5e7eb] bg-[#f3f4f6] px-6 py-4" style="font-size: 15px;">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="mr-2 font-semibold text-gray-700" style="font-size: 15px;">Exportar reporte del periodo:</span>
                        @foreach (['csv', 'pdf', 'txt'] as $format)
                            <button type="button" onclick="exportarChecador('{{ $format }}')"
                                class="inline-flex h-11 items-center justify-center rounded-none px-5 font-semibold text-white shadow-sm transition-colors {{ $format === 'csv' ? 'bg-emerald-600 hover:bg-emerald-700' : ($format === 'pdf' ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700') }}"
                                style="font-size: 15px;">
                                {{ strtoupper($format) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="attendance-clock-scrollbar max-h-[560px] overflow-x-auto overflow-y-auto" style="font-size: 15px;">
                    <!-- Tabla estática con min-width: 1500px y anchos fijos en px -->
                    <table class="divide-y divide-[#e5e7eb]" style="margin: 20px; width: calc(100% - 40px); min-width: 1500px; table-layout: fixed; font-size: 15px;">
                        <colgroup>
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                            <col style="width: 187.5px;">
                        </colgroup>
                        <thead class="sticky top-0 z-10 bg-[#f8fafc]">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">
                                    Fecha Jornada
                                    <span class="ml-1 font-medium text-[#1A3A6B]">({{ auth()->user()->employee_id ?? 'Sin ID' }})</span>
                                </th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">Marcas / Chequeos</th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">Tiempo Neto</th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">Hrs Decimales</th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">Pago Base</th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">Bono</th>
                                <th class="px-6 py-4 text-left font-semibold text-[#1A3A6B]" style="font-size: 15px;">Total del Día</th>
                                <th class="px-6 py-4 text-left font-semibold text-gray-500" style="font-size: 15px;">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_checador_body" class="divide-y divide-[#f3f4f6] bg-[#ffffff]" style="font-size: 15px;">
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400" style="font-size: 15px;">
                                    Consultando las marcas de los últimos 15 días...
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold text-gray-900 border-t-2 border-gray-200" style="font-size: 15px;">
                            <tr>
                                <td class="px-6 py-4">TOTAL ACUMULADO</td>
                                <td class="px-6 py-4"></td>
                                <td id="total_tiempo" class="px-6 py-4 font-mono text-gray-700" style="font-size: 15px;">00h 00m 00s</td>
                                <td id="total_decimal" class="px-6 py-4" style="font-size: 15px;">0.00</td>
                                <td id="total_pago_h" class="px-6 py-4" style="font-size: 15px;">$0.00</td>
                                <td id="total_bonos" class="px-6 py-4" style="font-size: 15px;">$0.00</td>
                                <td id="total_general" class="px-6 py-4 text-emerald-600 font-semibold" style="font-size: 15px;">$0.00</td>
                                <td class="px-6 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!-- Fin del contenedor con borde punteado -->
        </div>
    </div>
</div>

<script>
function establecerCargaChecador(cargando) {
    const boton = document.getElementById('btn_revisar_horas');
    const iconoBuscar = document.getElementById('icono_buscar_checador');
    const iconoCarga = document.getElementById('carga_checador');
    const texto = document.getElementById('texto_boton_checador');

    if (!boton || !iconoBuscar || !iconoCarga || !texto) return;

    boton.disabled = cargando;
    iconoBuscar.classList.toggle('hidden', cargando);
    iconoCarga.classList.toggle('hidden', !cargando);
    texto.textContent = cargando ? 'Consultando...' : 'Revisar Horas';
}

function revisarHorasChecador() {
    const employeeId = document.getElementById('employee_id').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    if (!employeeId || !fechaInicio || !fechaFin) {
        alert('Tu usuario no tiene ID de checador asignado o faltan fechas.');
        return;
    }

    establecerCargaChecador(true);

    const payload = {
        employee_id: employeeId,
        inicio: fechaInicio,
        fin: fechaFin,
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
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Error de servidor.');
        }
        return data;
    })
    .then(data => {
        const tbody = document.getElementById('tabla_checador_body');
        tbody.innerHTML = '';

        if (!data.resumen || data.resumen.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-10 text-center text-gray-400 italic" style="font-size: 15px;">
                        No hay marcas registradas en el dispositivo para este ID en las fechas seleccionadas.
                    </td>
                </tr>`;
            document.getElementById('checador_export_bar').classList.add('hidden');
            document.getElementById('panel_resultados_checador').classList.remove('hidden');
            return;
        }

        data.resumen.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = item.requiere_revision
                ? 'bg-red-50/70 hover:bg-red-100/80 transition-colors'
                : 'hover:bg-gray-50 transition-colors';

            tr.innerHTML = `
                <td class="px-6 py-3.5 font-medium text-gray-900" style="font-size: 15px;">${item.fecha}</td>
                <td class="max-w-xs truncate px-6 py-3.5 font-mono text-gray-500" style="font-size: 15px;" title="${item.detalles_marcas}">${item.detalles_marcas}</td>
                <td class="px-6 py-3.5 font-mono text-gray-600" style="font-size: 15px;">${item.neto}</td>
                <td class="px-6 py-3.5 text-gray-600" style="font-size: 15px;">${item.horas_decimal}</td>
                <td class="px-6 py-3.5 text-gray-600" style="font-size: 15px;">${item.pago_horas}</td>
                <td class="px-6 py-3.5 text-gray-600" style="font-size: 15px;">${item.bono}</td>
                <td class="px-6 py-3.5 font-semibold text-gray-800" style="font-size: 15px;">${item.total}</td>
                <td class="px-6 py-3.5" style="font-size: 15px;">
                    ${item.requiere_revision
                        ? '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-100 px-3 py-1 font-semibold text-red-800" style="font-size: 15px;">⚠️ Impar / Revisar</span>'
                        : '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-green-100 px-3 py-1 font-semibold text-green-800" style="font-size: 15px;">Correcto</span>'
                    }
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('total_tiempo').innerText = data.totales_pie.tiempo;
        document.getElementById('total_decimal').innerText = data.totales_pie.decimal;
        document.getElementById('total_pago_h').innerText = data.totales_pie.pago_h;
        document.getElementById('total_bonos').innerText = data.totales_pie.bonos;
        document.getElementById('total_general').innerText = data.totales_pie.general;

        document.getElementById('checador_export_bar').classList.remove('hidden');
        document.getElementById('panel_resultados_checador').classList.remove('hidden');
    })
    .catch(err => {
        console.error(err);
        alert(err.message || 'Hubo un error al intentar consultar las marcas del checador.');
    })
    .finally(() => {
        establecerCargaChecador(false);
    });
}

function exportarChecador(format) {
    const employeeId = document.getElementById('employee_id').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    if (!employeeId || !fechaInicio || !fechaFin) {
        alert('No hay datos suficientes para exportar.');
        return;
    }

    const params = new URLSearchParams({
        employee_id: employeeId,
        inicio: fechaInicio,
        fin: fechaFin,
    });

    window.location.href = `{{ url('/control-horas/export') }}/${format}?${params.toString()}`;
}

function cargarPeriodoInicialChecador() {
    const employeeId = document.getElementById('employee_id')?.value;

    if (employeeId) {
        revisarHorasChecador();
        return;
    }

    const tbody = document.getElementById('tabla_checador_body');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-10 text-center text-gray-400" style="font-size: 15px;">
                    Tu usuario no tiene un ID de checador asignado.
                </td>
            </tr>`;
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarPeriodoInicialChecador, { once: true });
} else {
    cargarPeriodoInicialChecador();
}
</script>
</div>