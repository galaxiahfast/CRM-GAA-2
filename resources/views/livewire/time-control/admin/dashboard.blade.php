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
            
            // Si no hay nada escrito O si hay texto pero no se seleccionó un colaborador válido
            if (!textWritten || (textWritten && !this.localUserId)) {
                this.showErrorBanner = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            this.showErrorBanner = false;
            this.open = false;
            $wire.$refresh();
        }
     }">
    
    <div 
        x-show="showErrorBanner" 
        x-transition
        class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex justify-between items-center"
        x-cloak
    >
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-medium">No se puede revisar el reporte: El colaborador ingresado no es válido o no existe.</span>
        </div>
        <button @click="showErrorBanner = false" class="text-red-700 hover:text-red-900 font-bold text-sm">✕</button>
    </div>

    {{-- Cabecera del Dashboard con enlaces de navegación --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-800">Supervisión de horas</h1>
            <p class="text-xs text-slate-500">Métricas, reportes ejecutivos y distribución de tiempos de actividades.</p>
        </div>
        <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
            <a href="{{ route('time.admin.corrections') }}" class="px-3 py-1 text-xs font-semibold text-slate-700 bg-white rounded-md shadow-sm border border-slate-200 hover:text-blue-600 transition">
                📝 Corrección de Actividades
            </a>
            <a href="{{ route('time.admin.attendance') }}" class="px-3 py-1 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm transition">
                ⏰ Corrección Horas (Checador)
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch w-full">
        
        <div class="lg:col-span-9 bg-white border border-slate-100 rounded-xl shadow-md p-4 flex flex-col gap-4">
            
            <div class="relative w-full">
                <label class="flex items-center text-sm text-black mb-4 h-3 font-semibold tracking-tight">
                    Colaborador
                </label>
                <input 
                    type="text" 
                    x-model="search"
                    @input="open = true; showErrorBanner = false; if(localUserId) { localUserId = null; $wire.clearCollaborator(); }"
                    @click="open = true"
                    @click.away="open = false"
                    @keydown.enter.prevent="open = false"
                    @keydown.escape.prevent="open = false"
                    placeholder="Buscar colaborador..."
                    class="border-slate-200 rounded-lg text-sm w-full h-[38px] shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 placeholder-slate-400"
                />

                <div 
                    x-show="open" 
                    class="absolute z-50 mt-2 w-full bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-50"
                    x-cloak
                >
                    <template x-for="user in filteredUsers" :key="user.id">
                        <button 
                            type="button"
                            @click="localUserId = user.id; search = user.full_name; open = false; showErrorBanner = false"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 text-slate-700 font-medium transition-colors duration-150"
                            x-text="user.full_name"
                        ></button>
                    </template>

                    <div x-show="filteredUsers.length === 0" class="px-4 py-3 text-sm text-red-600 font-medium bg-red-50/50 backdrop-blur-sm">
                        No se encuentra el colaborador
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4 items-end w-full">
                <div class="col-span-12 sm:col-span-4 flex flex-col">
                    <label class="flex items-center text-sm text-black mb-4 h-3 font-semibold tracking-tight">Desde</label>
                    <input type="date" wire:model.live="from" class="border-slate-200 rounded-lg text-sm w-full h-[38px] shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 px-3" />
                </div>

                <div class="col-span-12 sm:col-span-4 flex flex-col">
                    <label class="flex items-center text-sm text-black mb-4 h-3 font-semibold tracking-tight">Hasta</label>
                    <input type="date" wire:model.live="to" class="border-slate-200 rounded-lg text-sm w-full h-[38px] shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 px-3" />
                </div>

                <div class="col-span-12 sm:col-span-4">
                    <button 
                        type="button" 
                        @click="clickBotonBuscar()"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm px-4 rounded-lg shadow-sm transition-all duration-200 active:scale-[0.98] w-full h-[38px] flex items-center justify-center whitespace-nowrap"
                    >
                        Revisar Horas
                    </button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 bg-white border border-slate-100 rounded-xl shadow-md p-4 flex flex-col gap-4">
            <span class="flex items-center text-sm text-black mb-1 h-3 font-semibold tracking-tight">Reportes</span>
            
            <div class="flex flex-row flex-wrap sm:flex-col gap-2.5 w-full">
                
                <button wire:click="export('csv')" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs px-4 py-2 rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 h-[34px] w-full sm:w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span class="font-semibold tracking-wide">CSV</span>
                </button>

                <button wire:click="export('txt')" class="bg-neutral-600 hover:bg-neutral-500 text-white font-medium text-xs px-4 py-2 rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 h-[34px] w-full sm:w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span class="font-semibold tracking-wide">TXT</span>
                </button>

                <button wire:click="export('pdf')" class="bg-red-600 hover:bg-red-500 text-white font-medium text-xs px-4 py-2 rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 h-[34px] w-full sm:w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span class="font-semibold tracking-wide">PDF</span>
                </button>

            </div>
        </div>
        
    </div>

    <div class="bg-white rounded-xl border border-slate-100 shadow-md p-4 max-w-xl">
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <div class="text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">Horas efectivas totales</div>
                <div class="text-2xl font-mono text-slate-900 font-bold">{{ $fmt($total) }}</div>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <div class="text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">Cierres automáticos</div>
                <div class="text-2xl font-mono text-red-600 font-bold">{{ $autoClosedCount }}</div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        @foreach ([
            'Por colaborador' => $byCollaborator,
            'Por cliente' => $byCustomer,
            'Por puesto profesional' => $byPosition,
            'Por área física' => $byArea,
        ] as $title => $rows)
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                <h2 class="font-semibold text-gray-800 mb-3">{{ $title }}</h2>
                @forelse ($rows as $row)
                    <div class="flex justify-between text-sm py-1 border-b border-slate-100">
                        <span>{{ $row['name'] }}</span>
                        <span class="font-mono">{{ $fmt($row['seconds']) }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Sin datos.</p>
                @endforelse
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
        <h2 class="font-semibold text-gray-800 mb-3">Detalle de actividades por día</h2>
        <x-time-activity-detail :columns="$activityDetail['columns']" :groups="$activityDetail['groups']" />
    </div>
</div>