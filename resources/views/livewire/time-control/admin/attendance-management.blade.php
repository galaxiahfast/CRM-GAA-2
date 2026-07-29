<div class="w-full min-h-screen bg-[#f4f4f4] overflow-x-auto text-[15px]"
     x-data="{
        showErrorBanner: false,
        search: @js($searchCollaborator),
        localUserId: @js($userId),
        open: false,
        showAllUsers: false,
        users: {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'full_name' => trim($u->name.' '.$u->last_name), 'employee_id' => $u->employee_id])) }},

        get filteredUsers() {
            if (this.showAllUsers || !this.search) return this.users;
            const query = this.search.toLowerCase();
            return this.users.filter(u =>
                u.full_name.toLowerCase().includes(query) ||
                (u.employee_id && String(u.employee_id).toLowerCase().includes(query))
            ).sort((a, b) => {
                const nameA = a.full_name.toLowerCase();
                const nameB = b.full_name.toLowerCase();
                const startsA = nameA.startsWith(query);
                const startsB = nameB.startsWith(query);
                if (startsA && !startsB) return -1;
                if (!startsA && startsB) return 1;
                return nameA.localeCompare(nameB);
            });
        },

        selectUser(userId, fullName) {
            this.localUserId = Number(userId);
            this.search = fullName;
            this.open = false;
            this.showAllUsers = false;
            this.showErrorBanner = false;
        },

        async clickBotonBuscar() {
            const textWritten = this.search ? this.search.trim().length > 0 : false;
            if (!textWritten || (textWritten && !this.localUserId)) {
                this.showErrorBanner = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            this.showErrorBanner = false;
            this.open = false;
            this.showAllUsers = false;
            await $wire.set('userId', Number(this.localUserId), false);
            await $wire.set('searchCollaborator', this.search, false);
            await $wire.searchAttendance();
        }
     }">

    <style>
        .attendance-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .attendance-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
        .attendance-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
        .attendance-scrollbar { scrollbar-width: thin; scrollbar-color: #1A3A6B #f8fafc; }
    </style>

    <div class="w-full min-w-[1000px]">

    {{-- Banner de error --}}
    <div x-show="showErrorBanner" x-transition class="mx-10 mt-6 flex items-center justify-between rounded-xl border border-red-200 border-l-4 border-l-red-500 bg-red-50 p-4 text-[15px] text-red-700 shadow-sm" x-cloak>
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-medium">El colaborador ingresado no es válido para revisar asistencia.</span>
        </div>
        <button @click="showErrorBanner = false" class="text-[15px] font-bold text-red-700 hover:text-red-900">✕</button>
    </div>

    @if (session()->has('message'))
        <div class="mx-10 mt-6 rounded-xl border border-green-200 border-l-4 border-l-green-500 bg-green-50 p-4 text-[15px] text-green-700 shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mx-10 mt-6 rounded-xl border border-red-200 border-l-4 border-l-red-500 bg-red-50 p-4 text-[15px] text-red-700 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Encabezado y migas de pan --}}
    <div class="flex items-center justify-between gap-12 whitespace-nowrap border-b-2 border-[#e5e7eb] px-10 py-10">
        <div class="flex items-center gap-[15px] text-[15px] text-gray-500">
            <span class="font-medium">Actividades</span>
            <span class="font-light text-gray-300">&gt;</span>
            <span class="font-medium">Control de Horas</span>
            <span class="font-light text-gray-300">&gt;</span>
            <span class="font-semibold text-[#1A3A6B]">Reloj Checador</span>
        </div>
        <div class="flex items-center gap-[30px]">
            <button type="button"
                wire:click="export('pdf')"
                wire:loading.attr="disabled"
                @disabled(! $searched)
                class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B] disabled:cursor-not-allowed disabled:opacity-40">
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
            <p class="mt-1 text-[15px] text-gray-500">Administración de marcas biométricas, ajustes por día y exportación.</p>
        </div>
    </div>

    <p class="mx-20 mt-6 max-w-2xl text-[15px] leading-8 text-gray-500">
        Consulta y ajusta las marcas biométricas por colaborador,<br>
        con cálculo de tiempo, pagos y acumulados durante el periodo seleccionado.
    </p>

    {{-- Filtros --}}
    <form class="mx-20 mb-10 mt-10 flex flex-nowrap items-end gap-[20px]" @submit.prevent="clickBotonBuscar()">
            <div class="relative w-[260px] shrink-0" @click.outside="open = false; showAllUsers = false">
                <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Colaborador / ID Huella</label>
                <input type="text" x-model="search"
                    @input="open = true; showAllUsers = false; showErrorBanner = false; localUserId = null"
                    @focus="open = true; showAllUsers = true"
                    @click="open = true; showAllUsers = true"
                    placeholder="Buscar por ID o nombre..."
                    class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10" />

            <div x-show="open" class="attendance-scrollbar absolute z-50 mt-2 max-h-56 w-full overflow-y-auto border border-[#e5e7eb] bg-[#ffffff] shadow-xl" x-cloak>
                @foreach ($users as $user)
                    @php
                        $fullName = trim($user->name.' '.$user->last_name);
                    @endphp
                    <button type="button"
                        data-user-id="{{ $user->id }}"
                        data-user-name="{{ $fullName }}"
                        data-employee-id="{{ $user->employee_id ?? '' }}"
                        x-show="showAllUsers || !search || $el.dataset.userName.toLowerCase().includes(search.toLowerCase()) || $el.dataset.employeeId.toLowerCase().includes(search.toLowerCase())"
                        @click="selectUser($el.dataset.userId, $el.dataset.userName)"
                        @disabled(empty($user->employee_id))
                        class="flex w-full min-w-0 items-center border-b border-[#f3f4f6] bg-transparent px-4 py-3 text-left text-[15px] font-medium text-gray-700 last:border-b-0 {{ empty($user->employee_id) ? 'cursor-not-allowed opacity-50' : 'hover:bg-[#f3f4f6]' }}">
                            <span class="min-w-0 flex-1 truncate">{{ $fullName }}</span>
                            <span class="ml-3 shrink-0 text-[15px] text-gray-400">
                                {{ $user->employee_id ? '(ID: '.$user->employee_id.')' : '(Sin ID de checador)' }}
                            </span>
                        </button>
                @endforeach
                <div x-show="filteredUsers.length === 0" class="bg-red-50 px-4 py-3 text-[15px] font-medium text-red-600">No se encuentra el colaborador</div>
            </div>
            </div>

            <div class="w-[170px] shrink-0">
                <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Fecha Inicio</label>
                <input type="date" wire:model="from" class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10" />
            </div>
            <div class="w-[170px] shrink-0">
                <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Fecha Fin</label>
                <input type="date" wire:model="to" class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10" />
            </div>
            <div class="w-[180px] shrink-0">
                <button type="submit" wire:loading.attr="disabled" wire:target="searchAttendance"
                    class="inline-flex h-[50px] w-full items-center justify-center gap-2 rounded-none border-0 bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-md transition-colors hover:bg-[#15305a] disabled:cursor-wait disabled:opacity-70">
                    <svg wire:loading.remove wire:target="searchAttendance" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <svg wire:loading wire:target="searchAttendance" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="searchAttendance">Revisar Horas</span>
                    <span wire:loading wire:target="searchAttendance">Consultando...</span>
                </button>
            </div>
    </form>

    <div class="mx-20 mb-10 space-y-6">
    {{-- Exportación (solo visible tras búsqueda) --}}
    @if ($searched)
        <div class="overflow-hidden border border-[#e5e7eb] bg-[#ffffff] shadow-sm">
            <div class="border-b border-[#e5e7eb] bg-[#f3f4f6] px-6 py-4">
                <h2 class="text-[15px] font-semibold text-gray-800">Exportación del periodo</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3 p-6">
                <span class="mr-2 text-[15px] text-gray-500">Descargar reporte:</span>
                @foreach (['csv', 'pdf', 'txt'] as $format)
                    <button type="button"
                        wire:click="export('{{ $format }}')"
                        wire:loading.attr="disabled"
                        class="inline-flex h-11 items-center justify-center rounded-none px-5 text-[15px] font-semibold text-white shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-60 {{ $format === 'csv' ? 'bg-emerald-600 hover:bg-emerald-700' : ($format === 'pdf' ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700') }}">
                        {{ strtoupper($format) }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tabla de resultados --}}
    <div class="border-2 border-dotted border-gray-400 p-2">
        <div class="overflow-hidden border border-[#e5e7eb] bg-[#ffffff] shadow-sm">
            <div class="border-b border-[#e5e7eb] bg-[#f3f4f6] px-6 py-4">
                <h2 class="text-[15px] font-semibold text-gray-800">Jornadas del periodo</h2>
                <p class="mt-1 text-[15px] text-gray-500">Marcas, tiempo neto, pagos y estado de cada jornada.</p>
            </div>
            <div class="attendance-scrollbar max-h-[560px] overflow-auto">
                <table class="min-w-[1000px] w-full border-collapse text-left text-[15px]">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-[#e5e7eb] bg-[#f8fafc] text-[15px] font-semibold text-gray-500">
                            <th class="p-4">
                                Fecha Jornada
                                @if ($employeeId)
                                    <span class="ml-1 font-medium text-[#1A3A6B]">({{ $employeeId }})</span>
                                @endif
                            </th>
                            <th class="p-4">Marcas / Chequeos</th>
                            <th class="p-4 text-center">Tiempo Neto</th>
                            <th class="p-4 text-center">Hrs Decimales</th>
                            <th class="p-4 text-center">Pago Base</th>
                            <th class="p-4 text-center">Bono</th>
                            <th class="p-4 text-right pr-6 font-bold text-[#1A3A6B]">Total del Día</th>
                            <th class="p-4 text-center">Estado</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3f4f6] font-medium text-gray-700">
                        @forelse ($payrollRows as $row)
                            @php
                                $rowClass = 'hover:bg-slate-50/50';
                                if ($row['modified_individual'] ?? false) {
                                    $rowClass = 'bg-blue-50 hover:bg-blue-100/80 border-l-4 border-l-blue-400';
                                } elseif ($row['requiere_revision'] ?? false) {
                                    $rowClass = 'bg-red-50/70 hover:bg-red-100/80';
                                }
                            @endphp
                            <tr class="{{ $rowClass }} transition-colors">
                                <td class="p-4 font-semibold text-slate-900">{{ $row['fecha'] }}</td>
                                <td class="max-w-xs truncate p-4 font-mono text-[15px] text-gray-500" title="{{ $row['detalles_marcas'] }}">{{ $row['detalles_marcas'] }}</td>
                                <td class="p-4 text-center font-mono text-[15px] text-gray-600">{{ $row['neto'] }}</td>
                                <td class="p-4 text-center font-mono">{{ $row['horas_decimal'] }}</td>
                                <td class="p-4 text-center font-mono">{{ $row['pago_horas'] }}</td>
                                <td class="p-4 text-center font-mono text-green-600">{{ $row['bono'] }}</td>
                                <td class="p-4 text-right font-bold text-slate-900 pr-6">{{ $row['total'] }}</td>
                                <td class="p-4 text-center">
                                    @if ($row['requiere_revision'])
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-100 px-3 py-1 text-[15px] font-semibold text-red-800">⚠️ Impar / Revisar</span>
                                    @else
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-[15px] font-semibold text-green-800">Correcto</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <button type="button" wire:click="editRow('{{ $row['fecha'] }}')" class="rounded-none border border-[#1A3A6B] bg-transparent px-4 py-2 text-[15px] font-semibold text-[#1A3A6B] transition-colors hover:bg-[#f3f4f6]">
                                        Ajustar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="bg-[#f8fafc] p-10 text-center text-[15px] text-gray-400">
                                    Selecciona un colaborador válido y rango de fechas para cargar las jornadas diarias.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($searched && count($payrollRows) > 0)
                        <tfoot class="bg-slate-50 font-bold text-slate-900 border-t-2 border-slate-200">
                            <tr>
                                <td class="p-4">TOTAL ACUMULADO</td>
                                <td class="p-4"></td>
                                <td class="p-4 text-center font-mono">{{ $totalsFooter['tiempo'] ?? '00h 00m 00s' }}</td>
                                <td class="p-4 text-center font-mono">{{ $totalsFooter['decimal'] ?? '0.00' }}</td>
                                <td class="p-4 text-center font-mono">{{ $totalsFooter['pago_h'] ?? '$0.00' }}</td>
                                <td class="p-4 text-center font-mono text-green-600">{{ $totalsFooter['bonos'] ?? '$0.00' }}</td>
                                <td class="p-4 text-right pr-6 text-emerald-600">{{ $totalsFooter['general'] ?? '$0.00' }}</td>
                                <td class="p-4"></td>
                                <td class="p-4"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Leyenda visual --}}
    @if ($searched)
        <div class="flex flex-wrap gap-6 border border-[#e5e7eb] bg-[#ffffff] p-5 text-[15px] text-gray-500 shadow-sm">
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-green-100 border border-green-200"></span> Día correcto (marcas pares)</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-red-100 border border-red-200"></span> Impar / Revisar</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-blue-100 border border-blue-300"></span> Ajuste individual aplicado</span>
        </div>
    @endif
    </div>

    {{-- Modal de ajuste por día --}}
    @if($showAttendanceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="w-full max-w-md overflow-hidden border border-[#e5e7eb] bg-[#ffffff] shadow-xl">
                <div class="flex items-center justify-between border-b border-[#e5e7eb] bg-[#f3f4f6] p-5">
                    <div>
                        <h3 class="text-[15px] font-bold text-gray-800">Ajuste Individual por Día</h3>
                        <p class="mt-1 max-w-xs truncate text-[15px] text-gray-500" title="{{ $selectedEmployeeName }} — {{ $selectedDate }}">{{ $selectedEmployeeName }} — {{ $selectedDate }}</p>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-[15px] text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Precio por Hora ($)</label>
                        <input type="number" step="0.01" wire:model="modalHourlyRate" class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10" />
                    </div>
                    <div>
                        <label class="mb-2 block text-[15px] font-medium text-[#1A3A6B]">Bono del Día ($)</label>
                        <input type="number" step="0.01" wire:model="modalBonusAmount" class="h-[50px] w-full rounded-none border border-[#d1d5db] bg-transparent px-5 text-[15px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10" />
                    </div>
                    <p class="text-[15px] leading-6 text-gray-500">Este ajuste tiene prioridad sobre las tarifas generales hasta que se vuelvan a aplicar.</p>
                </div>

                <div class="flex justify-end gap-3 border-t border-[#e5e7eb] bg-[#f3f4f6] px-6 py-4">
                    <button type="button" wire:click="closeModal" class="h-11 rounded-none border border-[#1A3A6B] bg-transparent px-5 text-[15px] font-semibold text-[#1A3A6B] hover:bg-[#f8fafc]">Cancelar</button>
                    <button type="button" wire:click="saveDayAdjustment" class="h-11 rounded-none border-0 bg-[#1A3A6B] px-5 text-[15px] font-semibold text-white shadow-sm hover:bg-[#15305a]">Guardar Ajuste</button>
                </div>
            </div>
        </div>
    @endif
    </div>
</div>