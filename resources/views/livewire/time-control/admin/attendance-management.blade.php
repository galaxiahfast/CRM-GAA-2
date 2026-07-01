@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
@endphp

<div class="max-w-6xl mx-auto space-y-4" 
     x-data="{ 
        showErrorBanner: false,
        search: @entangle('searchCollaborator'),
        localUserId: @entangle('userId'),
        open: false,
        users: {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'full_name' => trim($u->name.' '.$u->last_name)])) }},
        
        get filteredUsers() {
            if (!this.search) return this.users;
            const query = this.search.toLowerCase();
            return this.users.filter(u => u.full_name.toLowerCase().includes(query))
                .sort((a, b) => {
                    const nameA = a.full_name.toLowerCase();
                    const nameB = b.full_name.toLowerCase();
                    const startsA = nameA.startsWith(query);
                    const startsB = nameB.startsWith(query);
                    if (startsA && !startsB) return -1;
                    if (!startsA && startsB) return 1;
                    return nameA.localeCompare(nameB);
                });
        },
        
        clickBotonBuscar() {
            const textWritten = this.search ? this.search.trim().length > 0 : false;
            if (!textWritten || (textWritten && !this.localUserId)) {
                this.showErrorBanner = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            this.showErrorBanner = false;
            this.open = false;
            // 💡 Forzamos al componente a renderizar con el ID de usuario actualizado
            $wire.$refresh();
        }
     }">
    
    {{-- Banner de error --}}
    <div x-show="showErrorBanner" x-transition class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex justify-between items-center" x-cloak>
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-medium">El colaborador ingresado no es válido para revisar asistencia.</span>
        </div>
        <button @click="showErrorBanner = false" class="text-red-700 hover:text-red-900 font-bold text-sm">✕</button>
    </div>

    {{-- Encabezado con Rutas Cruzadas --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-800">Control de Asistencia Biométrico (Checador)</h1>
            <p class="text-xs text-slate-500">Administración de marcas brutas, asignación de apoyos y bonos.</p>
        </div>
        <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
            <a href="{{ route('time.admin.dashboard') }}" class="px-3 py-1 text-xs font-semibold text-slate-700 bg-white rounded-md shadow-sm border border-slate-200 hover:text-blue-600 transition">
                📊 Supervision General
            </a>
            <a href="{{ route('time.admin.corrections') }}" class="px-3 py-1 text-xs font-semibold text-slate-700 bg-white rounded-md shadow-sm border border-slate-200 hover:text-blue-600 transition">
                📝 Actividades
            </a>
        </div>
    </div>

    {{-- Filtros Avanzados (Buscador superior) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch w-full">
        <div class="lg:col-span-12 bg-white border border-slate-100 rounded-xl shadow-md p-4 flex flex-col gap-4">
            <div class="relative w-full">
                <label class="flex items-center text-sm text-black mb-4 h-3 font-semibold tracking-tight">Colaborador / ID Huella</label>
                <input type="text" x-model="search" @input="open = true; showErrorBanner = false; if(localUserId) { localUserId = null; $wire.clearCollaborator(); }" @click="open = true" @click.away="open = false" placeholder="Buscar colaborador por ID o nombre..." class="border-slate-200 rounded-lg text-sm w-full h-[38px] shadow-sm focus:border-indigo-500 text-slate-800" />
                
                <div x-show="open" class="absolute z-50 mt-2 w-full bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-50" x-cloak>
                    <template x-for="user in filteredUsers" :key="user.id">
                        <button type="button" @click="localUserId = user.id; search = user.full_name; open = false; showErrorBanner = false; $wire.set('userId', user.id)" class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 text-slate-700 font-medium" x-text="user.full_name"></button>
                    </template>
                    <div x-show="filteredUsers.length === 0" class="px-4 py-3 text-sm text-red-600 font-medium bg-red-50">No se encuentra el colaborador</div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-end w-full">
                <div class="col-span-12 sm:col-span-4 flex flex-col">
                    <label class="flex items-center text-sm text-black mb-4 h-3 font-semibold tracking-tight">Fecha Inicio</label>
                    <input type="date" wire:model.live="from" class="border-slate-200 rounded-lg text-sm w-full h-[38px] shadow-sm text-slate-800 px-3" />
                </div>
                <div class="col-span-12 sm:col-span-4 flex flex-col">
                    <label class="flex items-center text-sm text-black mb-4 h-3 font-semibold tracking-tight">Fecha Fin</label>
                    <input type="date" wire:model.live="to" class="border-slate-200 rounded-lg text-sm w-full h-[38px] shadow-sm text-slate-800 px-3" />
                </div>
                <div class="col-span-12 sm:col-span-4">
                    <button type="button" @click="clickBotonBuscar()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm px-4 rounded-lg shadow-sm w-full h-[38px] flex items-center justify-center">
                        Revisar Horas
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla Basada en tu Diseño de Imagen Histórica --}}
    <div class="bg-white border border-slate-100 rounded-xl shadow-md overflow-hidden">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="text-xs uppercase tracking-wider text-gray-500 bg-slate-50/70 font-semibold border-b border-slate-100">
                    <th class="p-4">Fecha Jornada</th>
                    <th class="p-4">Marcas / Chequeos</th>
                    <th class="p-4 text-center">Tiempo Neto</th>
                    <th class="p-4 text-center">Hrs Decimales</th>
                    <th class="p-4 text-center">Precio x Hora</th>
                    <th class="p-4 text-center">Apoyo Comida</th>
                    <th class="p-4 text-right">Bono</th>
                    <th class="p-4 text-right pr-6 text-indigo-700 font-bold">Pago Base</th>
                    <th class="p-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                @forelse ($attendanceRecords as $record)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-4 font-semibold text-slate-900">{{ $record->date }}</td>
                        <td class="p-4 text-xs font-mono text-slate-500">{{ $record->punches ?? '—' }}</td>
                        <td class="p-4 text-center text-slate-600 font-mono text-xs">{{ $record->net_time_label ?? '00h 00m 00s' }}</td>
                        <td class="p-4 text-center font-mono">{{ number_format($record->decimal_hours, 2) }}</td>
                        <td class="p-4 text-center font-mono text-slate-500">${{ number_format($record->hourly_rate, 2) }}</td>
                        <td class="p-4 text-center">
                            <span class="px-2 py-0.5 rounded text-xs {{ $record->food_allowance > 0 ? 'bg-amber-50 text-amber-700 font-semibold' : 'text-slate-400' }}">
                                ${{ number_format($record->food_allowance, 2) }}
                            </span>
                        </td>
                        <td class="p-4 text-right text-green-600 font-semibold">${{ number_format($record->bonus, 2) }}</td>
                        <td class="p-4 text-right font-bold text-slate-900 pr-6">${{ number_format(($record->decimal_hours * $record->hourly_rate) + $record->food_allowance + $record->bonus, 2) }}</td>
                        <td class="p-4 text-center">
                            <button type="button" wire:click="editRow({{ $record->id }})" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs px-2.5 py-1 rounded-md transition font-semibold border border-slate-200 shadow-sm">
                                Ajustar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-400 bg-slate-50/20">Selecciona un colaborador válido y rango de fechas para cargar las jornadas diarias.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🆕 MODAL DE AJUSTES INTEGRADO --}}
    @if($showAttendanceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-100 p-4 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Ajustar Jornada Laboral</h3>
                        <p class="text-xs text-slate-500" x-text="'Colaborador: ' + '{{ $selectedEmployeeName }}'"></p>
                    </div>
                    <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>
                
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Horas Decimales</label>
                        <input type="number" step="0.01" wire:model="modalDecimalHours" class="w-full text-sm border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Precio por Hora ($)</label>
                        <input type="number" step="0.01" wire:model="modalHourlyRate" class="w-full text-sm border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Apoyo Comida ($)</label>
                        <select wire:model="modalFoodAllowance" class="w-full text-sm border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="0">0.00</option>
                            <option value="50">50.00</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Bono Extra ($)</label>
                        <input type="number" step="0.01" wire:model="modalExtraBonus" class="w-full text-sm border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="bg-slate-50 px-4 py-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" wire:click="closeModal" class="px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-200 rounded-lg bg-white hover:bg-slate-50">Cancelar</button>
                    <button type="button" wire:click="saveAdjustment" class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm">Guardar Cambios</button>
                </div>
            </div>
        </div>
    @endif
</div>