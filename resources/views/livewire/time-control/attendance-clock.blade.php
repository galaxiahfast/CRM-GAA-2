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
                <article class="relative min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                    <span class="absolute inset-x-0 top-0 h-1 bg-[#1A3A6B]"></span>
                    <header class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#eaf1fb] text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 7v5l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <span class="truncate font-medium text-gray-500">Tiempo Neto</span>
                    </header>
                    <div id="kpi_tiempo_neto" class="mt-5 truncate text-[20px] font-semibold tracking-tight text-[#1A3A6B]" title="00h 00m 00s" aria-live="polite">00h 00m 00s</div>
                </article>
                <article class="relative min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                    <span class="absolute inset-x-0 top-0 h-1 bg-indigo-500"></span>
                    <header class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm1 4h8M8 11h2m4 0h2m-8 4h2m4 0h2" />
                            </svg>
                        </span>
                        <span class="truncate font-medium text-gray-500">Horas Decimales</span>
                    </header>
                    <div id="kpi_horas_decimales" class="mt-5 truncate text-[20px] font-semibold tracking-tight text-indigo-600" title="0.00" aria-live="polite">0.00</div>
                </article>
                <article class="relative min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                    <span class="absolute inset-x-0 top-0 h-1 bg-sky-500"></span>
                    <header class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 015.5 5H18a2 2 0 012 2v2H6a3 3 0 000 6h14v2a2 2 0 01-2 2H5.5A2.5 2.5 0 013 16.5v-9zM20 9v6h-4a3 3 0 010-6h4z" />
                            </svg>
                        </span>
                        <span class="truncate font-medium text-gray-500">Pago Base</span>
                    </header>
                    <div id="kpi_pago_base" class="mt-5 truncate text-[20px] font-semibold tracking-tight text-sky-600" title="$0.00" aria-live="polite">$0.00</div>
                </article>
                <article class="relative min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                    <span class="absolute inset-x-0 top-0 h-1 bg-amber-500"></span>
                    <header class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 12v8H4v-8m-1-5h18v5H3V7zm9 13V7m0 0H8.5A2.5 2.5 0 118 2c2 0 4 5 4 5zm0 0h3.5A2.5 2.5 0 1016 2c-2 0-4 5-4 5z" />
                            </svg>
                        </span>
                        <span class="truncate font-medium text-gray-500">Bono</span>
                    </header>
                    <div id="kpi_bono" class="mt-5 truncate text-[20px] font-semibold tracking-tight text-amber-600" title="$0.00" aria-live="polite">$0.00</div>
                </article>
                <article class="relative min-w-0 overflow-hidden rounded-xl border border-emerald-200 bg-white p-5 shadow-[0_8px_24px_rgba(16,185,129,0.10)]">
                    <span class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></span>
                    <header class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17l5-5 4 4 7-8m0 0h-5m5 0v5" />
                            </svg>
                        </span>
                        <span class="truncate font-medium text-gray-500">Total Día</span>
                    </header>
                    <div id="kpi_total_dia" class="mt-5 truncate text-[20px] font-bold tracking-tight text-emerald-600" title="$0.00" aria-live="polite">$0.00</div>
                </article>
            </section>

            <section aria-label="Estadísticas del periodo" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <article class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                    <header class="flex min-h-[76px] items-center gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#eaf1fb] text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold text-gray-800">Tiempo neto por día</h2>
                            <p class="mt-0.5 truncate text-[13px] text-gray-500">Duración efectiva de cada jornada</p>
                        </div>
                    </header>
                    <div class="grid grid-cols-2 border-b border-gray-200 bg-white">
                        <div class="min-w-0 px-5 py-3">
                            <span class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">Promedio diario</span>
                            <strong id="promedio_tiempo_checador" class="mt-1 block truncate text-[14px] font-semibold text-[#1A3A6B]">00h 00m 00s</strong>
                        </div>
                        <div class="min-w-0 border-l border-gray-200 px-5 py-3">
                            <span class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">Jornada máxima</span>
                            <strong id="maximo_tiempo_checador" class="mt-1 block truncate text-[14px] font-semibold text-gray-700">00h 00m 00s</strong>
                        </div>
                    </div>
                    <div class="relative h-[300px] p-4">
                        <div id="grafica_tiempo_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de tiempo neto por día"></div>
                        <div id="grafica_tiempo_vacia" class="absolute inset-0 flex items-center justify-center px-6 text-center text-gray-400">Sin datos para mostrar en el periodo.</div>
                    </div>
                </article>

                <article class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                    <header class="flex min-h-[76px] items-center gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17l6-6 4 4 7-8m0 0h-5m5 0v5" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold text-gray-800">Ganancias por día</h2>
                            <p class="mt-0.5 truncate text-[13px] text-gray-500">Pago total generado por jornada</p>
                        </div>
                    </header>
                    <div class="grid grid-cols-2 border-b border-gray-200 bg-white">
                        <div class="min-w-0 px-5 py-3">
                            <span class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">Promedio diario</span>
                            <strong id="promedio_ganancias_checador" class="mt-1 block truncate text-[14px] font-semibold text-emerald-700">$0.00</strong>
                        </div>
                        <div class="min-w-0 border-l border-gray-200 px-5 py-3">
                            <span class="block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">Ingreso máximo</span>
                            <strong id="maximo_ganancias_checador" class="mt-1 block truncate text-[14px] font-semibold text-gray-700">$0.00</strong>
                        </div>
                    </div>
                    <div class="relative h-[300px] p-4">
                        <div id="grafica_ganancias_checador" class="hidden h-full w-full" role="img" aria-label="Gráfica de ganancias por día"></div>
                        <div id="grafica_ganancias_vacia" class="absolute inset-0 flex items-center justify-center px-6 text-center text-gray-400">Sin datos para mostrar en el periodo.</div>
                    </div>
                </article>
            </section>

            <section aria-label="Detalle diario del checador" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.07)]">
                <header class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#eaf1fb] text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zm0 5h16M9 4v16m6-11v11" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold text-gray-800">Detalle de jornadas</h2>
                            <p class="mt-0.5 truncate text-[13px] text-gray-500">Marcas, tiempos e importes del periodo consultado</p>
                        </div>
                    </div>
                    <div id="checador_export_bar" class="hidden">
                        <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="exportarChecador('csv')" title="Exportar CSV" aria-label="Exportar reporte en CSV"
                            class="group inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-emerald-700 transition-all hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            <svg class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 14h6m-6 3h4" />
                            </svg>
                            <span class="text-[13px] font-semibold">CSV</span>
                        </button>
                        <button type="button" onclick="exportarChecador('pdf')" title="Exportar PDF" aria-label="Exportar reporte en PDF"
                            class="group inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 text-rose-700 transition-all hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-100">
                            <svg class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 14h6m-6 3h4" />
                            </svg>
                            <span class="text-[13px] font-semibold">PDF</span>
                        </button>
                        <button type="button" onclick="exportarChecador('txt')" title="Exportar TXT" aria-label="Exportar reporte en TXT"
                            class="group inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-100">
                            <svg class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 14h6m-6 3h4" />
                            </svg>
                            <span class="text-[13px] font-semibold">TXT</span>
                        </button>
                        </div>
                    </div>
                </header>

                <div class="p-4">
                    <div class="attendance-clock-scrollbar max-h-[560px] overflow-auto rounded-xl border border-gray-200">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[15px]" style="min-width: 1515px;">
                        <colgroup>
                            <col style="width: 175px;">
                            <col style="width: 365px;">
                            <col style="width: 175px;">
                            <col style="width: 150px;">
                            <col style="width: 145px;">
                            <col style="width: 130px;">
                            <col style="width: 155px;">
                            <col style="width: 220px;">
                        </colgroup>
                        <thead class="sticky top-0 z-20 bg-gray-50 shadow-[0_1px_0_#e5e7eb]">
                            <tr>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Fecha Jornada</th>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Marcas / Chequeos</th>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Tiempo Neto</th>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Hrs Decimales</th>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Pago Base</th>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Bono</th>
                                <th class="px-5 py-4 text-left align-middle text-[15px] font-semibold text-gray-600">Total del Día</th>
                                <th class="py-4 pl-5 pr-8 text-left align-middle text-[15px] font-semibold text-gray-600">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_checador_body" class="bg-white text-[15px] font-normal text-gray-700">
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                                    Consultando las marcas de los últimos 15 días...
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="sticky bottom-0 z-20 bg-[#f3f6fa] font-semibold text-gray-800 shadow-[0_-1px_0_#dbe3ee]">
                            <tr>
                                <td class="px-5 py-4 text-[15px] font-semibold text-gray-800">Total acumulado</td>
                                <td class="px-5 py-4"></td>
                                <td id="total_tiempo" class="whitespace-nowrap px-5 py-4">00h 00m 00s</td>
                                <td id="total_decimal" class="whitespace-nowrap px-5 py-4">0.00</td>
                                <td id="total_pago_h" class="whitespace-nowrap px-5 py-4">$0.00</td>
                                <td id="total_bonos" class="whitespace-nowrap px-5 py-4">$0.00</td>
                                <td id="total_general" class="whitespace-nowrap px-5 py-4 text-emerald-600">$0.00</td>
                                <td class="py-4 pl-5 pr-8"></td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
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

function partesFechaChecador(fecha) {
    const partes = String(fecha).split('-');

    return partes.length === 3
        ? { corta: partes[2] + '/' + partes[1], anio: partes[0], completa: partes[2] + '/' + partes[1] + '/' + partes[0] }
        : { corta: String(fecha), anio: '', completa: String(fecha) };
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
    const alto = Math.max(220, Math.round(contenedor.clientHeight || 268));
    // Ambas gráficas comparten exactamente la misma retícula y canales de ejes.
    const margen = { superior: 10, derecho: 18, inferior: 50, izquierdo: 72 };
    const anchoGrafica = Math.max(1, ancho - margen.izquierdo - margen.derecho);
    const altoGrafica = Math.max(1, alto - margen.superior - margen.inferior);
    const baseY = margen.superior + altoGrafica;
    const valoresNumericos = valores.map(valor => Math.max(0, Number(valor) || 0));
    const maximoDatos = Math.max(...valoresNumericos, 0);
    const promedio = valoresNumericos.reduce((total, valor) => total + valor, 0) / Math.max(1, valoresNumericos.length);
    const pasos = 4;
    const magnitud = maximoDatos > 0 ? 10 ** Math.floor(Math.log10(maximoDatos / pasos)) : 1;
    const pasoCrudo = maximoDatos > 0 ? maximoDatos / pasos / magnitud : 1;
    const factorPaso = pasoCrudo <= 1 ? 1 : (pasoCrudo <= 2 ? 2 : (pasoCrudo <= 5 ? 5 : 10));
    const pasoEje = factorPaso * magnitud;
    const maximoEje = maximoDatos > 0 ? Math.max(pasoEje, Math.ceil(maximoDatos / pasoEje) * pasoEje) : pasos;

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
        const valorEje = maximoEje - (maximoEje * proporcion);

        svg.appendChild(crearElementoSvgChecador('line', {
            x1: margen.izquierdo,
            x2: ancho - margen.derecho,
            y1: y,
            y2: y,
            stroke: paso === pasos ? '#94a3b8' : '#dbe3ee',
            'stroke-width': paso === pasos ? 1.25 : 1,
            'stroke-dasharray': paso === pasos ? '0' : '4 4',
        }));

        agregarTextoSvgChecador(svg, configuracion.formatearEje(valorEje), {
            x: margen.izquierdo - 14,
            y: y + 4,
            'text-anchor': 'end',
            'font-size': 12,
            'font-family': 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
        });
    }

    const cantidad = Math.max(1, valores.length);
    const rellenoHorizontal = configuracion.tipo === 'barras'
        ? Math.min(22, anchoGrafica / Math.max(2, cantidad * 2))
        : 4;
    const anchoDatos = Math.max(1, anchoGrafica - (rellenoHorizontal * 2));
    const obtenerX = indice => cantidad === 1
        ? margen.izquierdo + (anchoGrafica / 2)
        : margen.izquierdo + rellenoHorizontal + (anchoDatos * indice / (cantidad - 1));
    const obtenerY = valor => baseY - ((Math.max(0, Number(valor) || 0) / maximoEje) * altoGrafica);
    const maximoEtiquetas = Math.max(2, Math.floor(anchoGrafica / 92));
    const cantidadEtiquetas = Math.min(cantidad, maximoEtiquetas);
    const indicesEtiquetas = new Set();

    for (let indiceEtiqueta = 0; indiceEtiqueta < cantidadEtiquetas; indiceEtiqueta++) {
        indicesEtiquetas.add(Math.round(indiceEtiqueta * (cantidad - 1) / Math.max(1, cantidadEtiquetas - 1)));
    }

    etiquetas.forEach((etiqueta, indice) => {
        if (!indicesEtiquetas.has(indice)) return;

        svg.appendChild(crearElementoSvgChecador('line', {
            x1: obtenerX(indice),
            x2: obtenerX(indice),
            y1: margen.superior,
            y2: baseY,
            stroke: '#edf1f6',
            'stroke-width': 1,
            'stroke-dasharray': '3 4',
        }));

        const fecha = partesFechaChecador(etiqueta);
        const textoFecha = crearElementoSvgChecador('text', {
            x: obtenerX(indice),
            y: alto - 25,
            fill: '#64748b',
            'text-anchor': 'middle',
            'font-size': 12,
            'font-family': 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
        });
        const lineaFecha = crearElementoSvgChecador('tspan', { x: obtenerX(indice), dy: 0 });
        lineaFecha.textContent = fecha.corta;
        textoFecha.appendChild(lineaFecha);

        if (fecha.anio) {
            const lineaAnio = crearElementoSvgChecador('tspan', {
                x: obtenerX(indice),
                dy: 15,
                fill: '#94a3b8',
                'font-size': 10,
            });
            lineaAnio.textContent = fecha.anio;
            textoFecha.appendChild(lineaAnio);
        }

        svg.appendChild(textoFecha);
    });

    const promedioY = obtenerY(promedio);
    svg.appendChild(crearElementoSvgChecador('line', {
        x1: margen.izquierdo,
        x2: ancho - margen.derecho,
        y1: promedioY,
        y2: promedioY,
        stroke: configuracion.color,
        'stroke-width': 1.5,
        'stroke-dasharray': '7 5',
        opacity: 0.5,
    }));

    if (configuracion.tipo === 'linea') {
        const puntos = valores.map((valor, indice) => obtenerX(indice) + ',' + obtenerY(valor));

        if (puntos.length > 1) {
            const area = crearElementoSvgChecador('polygon', {
                points: margen.izquierdo + ',' + baseY + ' ' + puntos.join(' ') + ' ' + obtenerX(valores.length - 1) + ',' + baseY,
                fill: configuracion.color,
                opacity: 0.12,
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
                r: cantidad > 24 ? 3.5 : 4.5,
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
            const altoBarra = valor > 0 ? Math.max(2, baseY - obtenerY(valor)) : 0;
            const barra = crearElementoSvgChecador('rect', {
                x: obtenerX(indice) - (anchoBarra / 2),
                y: baseY - altoBarra,
                width: anchoBarra,
                height: altoBarra,
                rx: Math.min(5, anchoBarra / 3),
                fill: configuracion.color,
                opacity: 0.88,
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

let ultimoResumenGraficasChecador = [];
let temporizadorResizeGraficasChecador = null;

function actualizarGraficasChecador(resumen = []) {
    const filas = Array.isArray(resumen)
        ? [...resumen].sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
        : [];
    ultimoResumenGraficasChecador = filas;

    if (filas.length === 0) {
        document.getElementById('grafica_tiempo_checador')?.replaceChildren();
        document.getElementById('grafica_ganancias_checador')?.replaceChildren();
        alternarEstadoGraficaChecador('grafica_tiempo_checador', 'grafica_tiempo_vacia', false);
        alternarEstadoGraficaChecador('grafica_ganancias_checador', 'grafica_ganancias_vacia', false);
        asignarValorChecador('promedio_tiempo_checador', '00h 00m 00s');
        asignarValorChecador('maximo_tiempo_checador', '00h 00m 00s');
        asignarValorChecador('promedio_ganancias_checador', '$0.00');
        asignarValorChecador('maximo_ganancias_checador', '$0.00');
        return;
    }

    const etiquetas = filas.map(item => item.fecha);
    const tiemposSegundos = filas.map(item => Number(item.tiempo_segundos) || 0);
    const ganancias = filas.map(item => Number(item.total_raw) || 0);
    const formatoMoneda = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
    });
    const promedioTiempo = tiemposSegundos.reduce((total, valor) => total + valor, 0) / tiemposSegundos.length;
    const promedioGanancias = ganancias.reduce((total, valor) => total + valor, 0) / ganancias.length;

    asignarValorChecador('promedio_tiempo_checador', formatearSegundosChecador(promedioTiempo));
    asignarValorChecador('maximo_tiempo_checador', formatearSegundosChecador(Math.max(...tiemposSegundos)));
    asignarValorChecador('promedio_ganancias_checador', formatoMoneda.format(promedioGanancias));
    asignarValorChecador('maximo_ganancias_checador', formatoMoneda.format(Math.max(...ganancias)));

    alternarEstadoGraficaChecador('grafica_tiempo_checador', 'grafica_tiempo_vacia', true);
    alternarEstadoGraficaChecador('grafica_ganancias_checador', 'grafica_ganancias_vacia', true);

    renderizarGraficaSvgChecador('grafica_tiempo_checador', etiquetas, tiemposSegundos, {
        tipo: 'linea',
        color: '#1A3A6B',
        descripcion: 'Evolución del tiempo neto por día',
        formatearEje: valor => formatearSegundosChecador(valor).replaceAll('h ', ':').replaceAll('m ', ':').replace('s', ''),
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

window.addEventListener('resize', () => {
    window.clearTimeout(temporizadorResizeGraficasChecador);
    temporizadorResizeGraficasChecador = window.setTimeout(() => {
        if (ultimoResumenGraficasChecador.length > 0) {
            actualizarGraficasChecador(ultimoResumenGraficasChecador);
        }
    }, 160);
});

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
                ? 'bg-red-50/60 transition-colors hover:bg-red-50'
                : 'transition-colors hover:bg-slate-50/70';

            tr.innerHTML = `
                <td class="border-b border-gray-100 px-5 py-4 align-top font-normal text-gray-700" style="font-size: 15px;">${item.fecha}</td>
                <td class="whitespace-normal break-words border-b border-gray-100 px-5 py-4 align-top font-normal leading-7 text-gray-700" style="font-size: 15px;" title="${item.detalles_marcas}">${item.detalles_marcas}</td>
                <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 align-top font-normal text-gray-700" style="font-size: 15px;">${item.neto}</td>
                <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 align-top font-normal text-gray-700" style="font-size: 15px;">${item.horas_decimal}</td>
                <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 align-top font-normal text-gray-700" style="font-size: 15px;">${item.pago_horas}</td>
                <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 align-top font-normal text-gray-700" style="font-size: 15px;">${item.bono}</td>
                <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 align-top font-normal text-emerald-600" style="font-size: 15px;">${item.total}</td>
                <td class="border-b border-gray-100 py-4 pl-5 pr-8 align-top font-normal" style="font-size: 15px;">
                    ${item.requiere_revision
                        ? '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-100 px-3 py-1 font-normal text-red-800" style="font-size: 15px;">⚠️ Impar / Revisar</span>'
                        : '<span class="inline-flex items-center whitespace-nowrap rounded-full bg-green-100 px-3 py-1 font-normal text-green-800" style="font-size: 15px;">Correcto</span>'
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
