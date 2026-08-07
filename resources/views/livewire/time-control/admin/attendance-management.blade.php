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
                                    @if (($row['estado'] ?? '') === 'Corregido')
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-[15px] font-semibold text-blue-800">Corregido</span>
                                    @elseif ($row['requiere_revision'])
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-100 px-3 py-1 text-[15px] font-semibold text-red-800">⚠️ Impar / Revisar</span>
                                    @else
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-[15px] font-semibold text-green-800">Correcto</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" title="Eliminar (próximamente)" aria-label="Eliminar jornada" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                        </button>
                                        <button type="button" title="Copiar (próximamente)" aria-label="Copiar jornada" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3" /></svg>
                                        </button>
                                        <button type="button" wire:click="editRow('{{ $row['fecha'] }}')" title="Editar jornada" aria-label="Editar jornada" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-[#1A3A6B] hover:bg-blue-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                                        </button>
                                    </div>
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
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]" wire:keydown.escape.window="closeModal">
            <div x-data @click.away="$wire.closeModal()" class="flex max-h-[calc(100vh-3rem)] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-[#F3F3F3] shadow-2xl">
                <div class="flex items-center justify-between gap-4 border-b border-gray-300 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#1A3A6B] text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[15px] font-semibold text-gray-900">Editar jornada del día</h3>
                            <p class="mt-1 truncate text-[15px] text-gray-500" title="{{ $selectedEmployeeName }} — {{ $selectedDate }}">{{ $selectedEmployeeName }} — {{ $selectedDate }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Cerrar" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="saveDayAdjustment" class="flex min-h-0 flex-1 flex-col">
                    <div class="attendance-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto p-4 text-[15px]">
                        <section class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-[15px] font-semibold text-gray-900">Marcas / Chequeos</h4>
                                    <p class="mt-1 text-[15px] text-gray-500">Ordena la jornada agregando o eliminando marcas con precisión de segundos.</p>
                                </div>
                                <button type="button" wire:click="addAttendanceMark" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-[#1A3A6B] bg-white px-3 text-[15px] font-medium text-[#1A3A6B] hover:bg-blue-50 focus:outline-none focus:ring-0">
                                    <span class="text-lg leading-none">+</span> Agregar marca
                                </button>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($modalMarks as $index => $mark)
                                    <div class="rounded-lg border border-gray-200 bg-[#F3F3F3] p-2.5" wire:key="attendance-mark-{{ $selectedDate }}-{{ $index }}">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <label for="attendance-mark-{{ $index }}" class="text-[15px] font-medium text-gray-700">Chequeo {{ $index + 1 }}</label>
                                            <span class="rounded-full px-2 py-0.5 text-[12px] font-semibold {{ $index % 2 === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ $index % 2 === 0 ? 'Entrada' : 'Salida' }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <input id="attendance-mark-{{ $index }}" type="time" step="1" wire:model="modalMarks.{{ $index }}" class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0">
                                            <button type="button" wire:click="removeAttendanceMark({{ $index }})" aria-label="Eliminar chequeo {{ $index + 1 }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50 focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                            </button>
                                        </div>
                                        @error('modalMarks.'.$index) <p class="mt-2 text-[13px] text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                            @error('modalMarks') <p class="mt-3 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-3 rounded-lg border px-3 py-2 text-[15px] {{ count($modalMarks) % 2 === 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                {{ count($modalMarks) % 2 === 0 ? 'La jornada tiene marcas pares y quedará como corregida.' : 'La jornada conserva marcas impares y seguirá requiriendo revisión.' }}
                            </p>
                        </section>

                        <section class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                            <h4 class="text-[15px] font-semibold text-gray-900">Pago y bono del día</h4>
                            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-[15px] font-medium text-gray-700">Pago base del día ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model="modalDailyPay" class="h-10 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0">
                                    @error('modalDailyPay') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-[15px] font-medium text-gray-700">Bono del día ($)</label>
                                    <input type="number" min="0" step="0.01" wire:model="modalBonusAmount" class="h-10 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0">
                                    @error('modalBonusAmount') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                            <label for="attendance-change-comment" class="mb-2 block text-[15px] font-medium text-gray-700">Comentario o motivo del cambio <span class="text-red-600">*</span></label>
                            <textarea id="attendance-change-comment" rows="3" maxlength="500" required wire:model="modalChangeComment" placeholder="Describe por qué se corrigió o modificó esta jornada..." class="w-full resize-y rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 py-2 text-[15px] shadow-none focus:border-[#1A3A6B] focus:ring-0"></textarea>
                            @error('modalChangeComment') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-2 text-[15px] text-gray-500">El comentario quedará registrado en el historial del día.</p>
                        </section>
                    </div>

                    <div class="flex shrink-0 justify-end gap-3 border-t border-gray-300 bg-[#F3F3F3] px-4 py-3">
                        <button type="button" wire:click="closeModal" class="inline-flex h-10 items-center justify-center rounded-lg border border-[#1A3A6B] bg-white px-5 text-[15px] font-medium text-[#1A3A6B] hover:bg-gray-100 focus:outline-none focus:ring-0">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveDayAdjustment" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#1A3A6B] px-5 text-[15px] font-medium text-white hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveDayAdjustment">Guardar cambios</span>
                            <span wire:loading wire:target="saveDayAdjustment">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    </div>
</div>
