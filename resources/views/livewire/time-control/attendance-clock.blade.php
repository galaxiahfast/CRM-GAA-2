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

        <div id="panel_resultados_checador" class="mx-20 mb-10 space-y-8 text-[15px]">
            <section aria-label="Acumulados del periodo" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="flex h-14 items-center border-b border-[#d1d5db] bg-[#f4f4f4] px-3 font-semibold leading-5 text-gray-600">Tiempo Neto</header>
                    <div id="kpi_tiempo_neto" class="truncate px-4 py-5 font-mono text-[15px] font-semibold text-[#1A3A6B]" title="00h 00m 00s" aria-live="polite">00h 00m 00s</div>
                </article>
                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="flex h-14 items-center border-b border-[#d1d5db] bg-[#f4f4f4] px-3 font-semibold leading-5 text-gray-600">Horas Decimales</header>
                    <div id="kpi_horas_decimales" class="truncate px-4 py-5 text-[15px] font-semibold text-[#1A3A6B]" title="0.00" aria-live="polite">0.00</div>
                </article>
                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="flex h-14 items-center border-b border-[#d1d5db] bg-[#f4f4f4] px-3 font-semibold leading-5 text-gray-600">Pago Base</header>
                    <div id="kpi_pago_base" class="truncate px-4 py-5 text-[15px] font-semibold text-[#1A3A6B]" title="$0.00" aria-live="polite">$0.00</div>
                </article>
                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="flex h-14 items-center border-b border-[#d1d5db] bg-[#f4f4f4] px-3 font-semibold leading-5 text-gray-600">Bono</header>
                    <div id="kpi_bono" class="truncate px-4 py-5 text-[15px] font-semibold text-[#1A3A6B]" title="$0.00" aria-live="polite">$0.00</div>
                </article>
                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="flex h-14 items-center border-b border-[#d1d5db] bg-[#f4f4f4] px-3 font-semibold leading-5 text-gray-600">Total Día</header>
                    <div id="kpi_total_dia" class="truncate px-4 py-5 text-[15px] font-semibold text-emerald-600" title="$0.00" aria-live="polite">$0.00</div>
                </article>
            </section>

            <section aria-label="Estadísticas del periodo" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="border-b border-[#d1d5db] bg-[#f4f4f4] px-5 py-4">
                        <h2 class="font-semibold text-gray-800">Evolución del Tiempo Neto por día</h2>
                        <p class="mt-1 text-gray-500">Horas efectivas calculadas dentro del periodo seleccionado.</p>
                    </header>
                    <div class="relative h-[300px] p-5">
                        <div id="grafica_tiempo_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de tiempo neto por día"></div>
                        <div id="grafica_tiempo_vacia" class="absolute inset-0 flex items-center justify-center px-6 text-center text-gray-400">Sin datos para mostrar en el periodo.</div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                    <header class="border-b border-[#d1d5db] bg-[#f4f4f4] px-5 py-4">
                        <h2 class="font-semibold text-gray-800">Evolución de ganancias por día</h2>
                        <p class="mt-1 text-gray-500">Importe total diario generado dentro del periodo seleccionado.</p>
                    </header>
                    <div class="relative h-[300px] p-5">
                        <div id="grafica_ganancias_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de ganancias por día"></div>
                        <div id="grafica_ganancias_vacia" class="absolute inset-0 flex items-center justify-center px-6 text-center text-gray-400">Sin datos para mostrar en el periodo.</div>
                    </div>
                </article>
            </section>

            <div id="checador_export_bar" class="hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] px-6 py-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="mr-2 font-semibold text-gray-700">Exportar reporte del periodo:</span>
                    @foreach (['csv', 'pdf', 'txt'] as $format)
                        <button type="button" onclick="exportarChecador('{{ $format }}')"
                            class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            {{ strtoupper($format) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <section aria-label="Detalle diario del checador" class="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#f4f4f4] shadow-sm">
                <div class="attendance-clock-scrollbar max-h-[560px] overflow-x-auto overflow-y-auto">
                    <table class="divide-y divide-[#e5e7eb] text-[15px]" style="margin: 20px; width: calc(100% - 40px); min-width: 1500px; table-layout: fixed;">
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
                        <thead class="sticky top-0 z-10 bg-[#f4f4f4]">
                            <tr>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">
                                    Fecha Jornada
                                    <span class="ml-1 font-medium text-[#1A3A6B]">({{ auth()->user()->employee_id ?? 'Sin ID' }})</span>
                                </th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">Marcas / Chequeos</th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">Tiempo Neto</th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">Hrs Decimales</th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">Pago Base</th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">Bono</th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-[#1A3A6B]">Total del Día</th>
                                <th class="truncate px-6 py-4 text-left font-semibold text-gray-500">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_checador_body" class="divide-y divide-[#d1d5db] bg-[#f4f4f4] text-[15px]">
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                                    Consultando las marcas de los últimos 15 días...
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t border-[#d1d5db] bg-[#f4f4f4] font-bold text-gray-900">
                            <tr>
                                <td class="truncate px-6 py-4">TOTAL ACUMULADO</td>
                                <td class="px-6 py-4"></td>
                                <td id="total_tiempo" class="truncate px-6 py-4 font-mono text-gray-700">00h 00m 00s</td>
                                <td id="total_decimal" class="truncate px-6 py-4">0.00</td>
                                <td id="total_pago_h" class="truncate px-6 py-4">$0.00</td>
                                <td id="total_bonos" class="truncate px-6 py-4">$0.00</td>
                                <td id="total_general" class="truncate px-6 py-4 font-semibold text-emerald-600">$0.00</td>
                                <td class="px-6 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
function asignarValorChecador(id, valor) {
    const elemento = document.getElementById(id);

    if (!elemento) return;

    elemento.textContent = valor;
    elemento.title = valor;
}

function actualizarAcumuladosChecador(totales = {}) {
    const valores = {
        tiempo: totales.tiempo || '00h 00m 00s',
        decimal: totales.decimal || '0.00',
        pago: totales.pago_h || '$0.00',
        bonos: totales.bonos || '$0.00',
        general: totales.general || '$0.00',
    };

    asignarValorChecador('kpi_tiempo_neto', valores.tiempo);
    asignarValorChecador('kpi_horas_decimales', valores.decimal);
    asignarValorChecador('kpi_pago_base', valores.pago);
    asignarValorChecador('kpi_bono', valores.bonos);
    asignarValorChecador('kpi_total_dia', valores.general);

    asignarValorChecador('total_tiempo', valores.tiempo);
    asignarValorChecador('total_decimal', valores.decimal);
    asignarValorChecador('total_pago_h', valores.pago);
    asignarValorChecador('total_bonos', valores.bonos);
    asignarValorChecador('total_general', valores.general);
}

function alternarEstadoGraficaChecador(graficaId, vacioId, mostrarGrafica, mensaje = 'Sin datos para mostrar en el periodo.') {
    const grafica = document.getElementById(graficaId);
    const vacio = document.getElementById(vacioId);

    grafica?.classList.toggle('hidden', !mostrarGrafica);
    vacio?.classList.toggle('hidden', mostrarGrafica);

    if (vacio && !mostrarGrafica) {
        vacio.textContent = mensaje;
    }
}

function formatearSegundosChecador(segundos) {
    const total = Math.max(0, Math.round(Number(segundos) || 0));
    const horas = Math.floor(total / 3600);
    const minutos = Math.floor((total % 3600) / 60);
    const segundosRestantes = total % 60;

    return String(horas).padStart(2, '0')
        + 'h ' + String(minutos).padStart(2, '0')
        + 'm ' + String(segundosRestantes).padStart(2, '0') + 's';
}

const svgNamespaceChecador = 'http://www.w3.org/2000/svg';

function crearElementoSvgChecador(etiqueta, atributos = {}) {
    const elemento = document.createElementNS(svgNamespaceChecador, etiqueta);

    Object.entries(atributos).forEach(([nombre, valor]) => {
        elemento.setAttribute(nombre, String(valor));
    });

    return elemento;
}

function agregarTextoSvgChecador(svg, texto, atributos = {}) {
    const elemento = crearElementoSvgChecador('text', {
        fill: '#6b7280',
        'font-family': 'Poppins, sans-serif',
        'font-size': 15,
        ...atributos,
    });
    elemento.textContent = texto;
    svg.appendChild(elemento);
}

function fechaCortaChecador(fecha) {
    const partes = String(fecha).split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] : String(fecha);
}

function activarMarcaGraficaChecador(elemento, opacidadActiva) {
    elemento.style.cursor = 'pointer';
    elemento.style.transition = 'opacity 160ms ease';
    elemento.setAttribute('tabindex', '0');

    ['mouseenter', 'focus'].forEach(evento => {
        elemento.addEventListener(evento, () => elemento.setAttribute('opacity', opacidadActiva));
    });
    ['mouseleave', 'blur'].forEach(evento => {
        elemento.addEventListener(evento, () => elemento.setAttribute('opacity', '1'));
    });
}

function renderizarGraficaSvgChecador(contenedorId, etiquetas, valores, configuracion) {
    const contenedor = document.getElementById(contenedorId);
    if (!contenedor) return;

    contenedor.replaceChildren();

    const ancho = Math.max(320, Math.round(contenedor.clientWidth || 620));
    const alto = Math.max(240, Math.round(contenedor.clientHeight || 260));
    const margen = { superior: 18, derecho: 18, inferior: 48, izquierdo: 72 };
    const anchoGrafica = Math.max(1, ancho - margen.izquierdo - margen.derecho);
    const altoGrafica = Math.max(1, alto - margen.superior - margen.inferior);
    const baseY = margen.superior + altoGrafica;
    const maximoDatos = Math.max(...valores, 0);
    const maximoEje = maximoDatos > 0 ? maximoDatos * 1.12 : 1;
    const pasos = 4;

    const svg = crearElementoSvgChecador('svg', {
        viewBox: '0 0 ' + ancho + ' ' + alto,
        width: '100%',
        height: '100%',
        role: 'img',
        'aria-label': configuracion.descripcion,
    });

    for (let paso = 0; paso <= pasos; paso++) {
        const proporcion = paso / pasos;
        const y = margen.superior + (altoGrafica * proporcion);
        const valorEje = maximoEje * (1 - proporcion);

        svg.appendChild(crearElementoSvgChecador('line', {
            x1: margen.izquierdo,
            x2: ancho - margen.derecho,
            y1: y,
            y2: y,
            stroke: '#e5e7eb',
            'stroke-width': 1,
            'stroke-dasharray': paso === pasos ? '0' : '4 5',
        }));

        agregarTextoSvgChecador(svg, configuracion.formatearEje(valorEje), {
            x: margen.izquierdo - 10,
            y: y + 5,
            'text-anchor': 'end',
        });
    }

    const cantidad = Math.max(1, valores.length);
    const obtenerX = indice => cantidad === 1
        ? margen.izquierdo + (anchoGrafica / 2)
        : margen.izquierdo + (anchoGrafica * indice / (cantidad - 1));
    const obtenerY = valor => baseY - ((Number(valor) || 0) / maximoEje * altoGrafica);
    const maximoEtiquetas = Math.max(2, Math.floor(anchoGrafica / 78));
    const saltoEtiquetas = Math.max(1, Math.ceil(cantidad / maximoEtiquetas));

    etiquetas.forEach((etiqueta, indice) => {
        if (indice % saltoEtiquetas !== 0 && indice !== etiquetas.length - 1) return;

        agregarTextoSvgChecador(svg, fechaCortaChecador(etiqueta), {
            x: obtenerX(indice),
            y: alto - 16,
            'text-anchor': 'middle',
        });
    });

    if (configuracion.tipo === 'linea') {
        const puntos = valores.map((valor, indice) => obtenerX(indice) + ',' + obtenerY(valor));

        if (puntos.length > 1) {
            const area = crearElementoSvgChecador('polygon', {
                points: margen.izquierdo + ',' + baseY + ' ' + puntos.join(' ') + ' ' + obtenerX(valores.length - 1) + ',' + baseY,
                fill: configuracion.color,
                opacity: 0.1,
            });
            svg.appendChild(area);
        }

        svg.appendChild(crearElementoSvgChecador('polyline', {
            points: puntos.join(' '),
            fill: 'none',
            stroke: configuracion.color,
            'stroke-width': 3,
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
        }));

        valores.forEach((valor, indice) => {
            const punto = crearElementoSvgChecador('circle', {
                cx: obtenerX(indice),
                cy: obtenerY(valor),
                r: 5,
                fill: configuracion.color,
                stroke: '#ffffff',
                'stroke-width': 2,
                'aria-label': etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice),
            });
            const titulo = crearElementoSvgChecador('title');
            titulo.textContent = etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice);
            punto.appendChild(titulo);
            activarMarcaGraficaChecador(punto, '0.62');
            svg.appendChild(punto);
        });
    } else {
        const espacio = anchoGrafica / cantidad;
        const anchoBarra = Math.min(42, Math.max(8, espacio * 0.62));

        valores.forEach((valor, indice) => {
            const altoBarra = maximoDatos > 0 ? Math.max(2, (Number(valor) || 0) / maximoEje * altoGrafica) : 2;
            const barra = crearElementoSvgChecador('rect', {
                x: obtenerX(indice) - (anchoBarra / 2),
                y: baseY - altoBarra,
                width: anchoBarra,
                height: altoBarra,
                rx: 5,
                fill: configuracion.color,
                'aria-label': etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice),
            });
            const titulo = crearElementoSvgChecador('title');
            titulo.textContent = etiquetas[indice] + ': ' + configuracion.formatearDetalle(valor, indice);
            barra.appendChild(titulo);
            activarMarcaGraficaChecador(barra, '0.68');
            svg.appendChild(barra);
        });
    }

    contenedor.appendChild(svg);
}

function actualizarGraficasChecador(resumen = []) {
    const filas = Array.isArray(resumen)
        ? [...resumen].sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
        : [];

    if (filas.length === 0) {
        document.getElementById('grafica_tiempo_checador')?.replaceChildren();
        document.getElementById('grafica_ganancias_checador')?.replaceChildren();
        alternarEstadoGraficaChecador('grafica_tiempo_checador', 'grafica_tiempo_vacia', false);
        alternarEstadoGraficaChecador('grafica_ganancias_checador', 'grafica_ganancias_vacia', false);
        return;
    }

    const etiquetas = filas.map(item => item.fecha);
    const tiemposSegundos = filas.map(item => Number(item.tiempo_segundos) || 0);
    const tiemposHoras = tiemposSegundos.map(segundos => segundos / 3600);
    const ganancias = filas.map(item => Number(item.total_raw) || 0);
    const formatoMoneda = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
    });

    alternarEstadoGraficaChecador('grafica_tiempo_checador', 'grafica_tiempo_vacia', true);
    alternarEstadoGraficaChecador('grafica_ganancias_checador', 'grafica_ganancias_vacia', true);

    renderizarGraficaSvgChecador('grafica_tiempo_checador', etiquetas, tiemposHoras, {
        tipo: 'linea',
        color: '#1A3A6B',
        descripcion: 'Evolución del tiempo neto por día',
        formatearEje: valor => (Math.round(valor * 10) / 10) + ' h',
        formatearDetalle: (_valor, indice) => formatearSegundosChecador(tiemposSegundos[indice]),
    });

    renderizarGraficaSvgChecador('grafica_ganancias_checador', etiquetas, ganancias, {
        tipo: 'barras',
        color: '#10B981',
        descripcion: 'Evolución de las ganancias por día',
        formatearEje: valor => formatoMoneda.format(valor),
        formatearDetalle: valor => formatoMoneda.format(valor),
    });
}

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
        actualizarAcumuladosChecador(data.totales_pie || {});
        actualizarGraficasChecador(data.resumen || []);

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
    actualizarAcumuladosChecador();
    actualizarGraficasChecador([]);

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

window.revisarHorasChecador = revisarHorasChecador;
window.exportarChecador = exportarChecador;
window.actualizarAcumuladosChecador = actualizarAcumuladosChecador;
window.actualizarGraficasChecador = actualizarGraficasChecador;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarPeriodoInicialChecador, { once: true });
} else {
    cargarPeriodoInicialChecador();
}
</script>
</div>
