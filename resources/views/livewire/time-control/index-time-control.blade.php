@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    };
@endphp

<div class="max-w-5xl mx-auto space-y-6 p-6">
    {{-- ENCABEZADO --}}
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Control de Horas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestión de tiempos y métricas de productividad.</p>
        </div>
        <a href="{{ route('time.reports') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors group">
            Mi productividad 
            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    @if ($errors->has('timer'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $errors->first('timer') }}
        </div>
    @endif

    {{-- TARJETA DEL CRONÓMETRO ACTIVO (LOOK PREMIUM) --}}
    @if ($active)
        <div class="relative overflow-hidden rounded-xl border transition-all duration-300 shadow-sm
            {{ $active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS 
                ? 'bg-gradient-to-br from-white via-white to-green-50/30 border-green-200 ring-1 ring-green-100' 
                : 'bg-gradient-to-br from-white via-white to-amber-50/30 border-amber-200 ring-1 ring-amber-100' }}"
            x-data="{
                display: '00:00:00',
                timerId: null,
                tick() {
                    if (! this.$el.isConnected) {
                        clearInterval(this.timerId);
                        return;
                    }
                    let total = parseInt(this.$el.dataset.accumulated || '0', 10);
                    let openStart = this.$el.dataset.openStart;
                    if (openStart) {
                        total += Math.max(0, Math.floor(Date.now() / 1000) - parseInt(openStart, 10));
                    }
                    let h = Math.floor(total / 3600);
                    let m = Math.floor((total % 3600) / 60);
                    let s = total % 60;
                    this.display = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                }
            }"
            x-init="tick(); timerId = setInterval(() => tick(), 1000)"
            data-accumulated="{{ $accumulatedSeconds }}"
            data-open-start="{{ $openStartedAt ? $openStartedAt->timestamp : '' }}"
        >
            {{-- Efecto decorativo de fondo --}}
            <div class="absolute top-0 right-0 -mt-6 -mr-6 w-32 h-32 rounded-full opacity-20 blur-2xl
                {{ $active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS ? 'bg-green-400' : 'bg-amber-400' }}"></div>

            <div class="p-6 relative z-10">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm
                            {{ $active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 animate-pulse {{ $active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                            {{ $active->status_label }}
                        </span>
                        
                        <div class="text-sm text-gray-500 mt-2">
                            Cliente: <span class="font-semibold text-gray-900 font-mono">{{ $active->customer->name ?? '—' }}</span>
                            <span class="mx-2 text-gray-300">|</span>
                            Actividad: <span class="font-semibold text-gray-900">{{ $active->subService->sub_service ?? '—' }}</span>
                        </div>
                    </div>
                    
                    {{-- Contador Principal --}}
                    <div class="text-5xl sm:text-6xl font-black font-mono tracking-tight text-gray-950 tabular-nums select-none" x-text="display">
                        00:00:00
                    </div>
                </div>

                {{-- Observación actual de la tarea en curso --}}
                @if($active->description)
                    <div class="mt-4 p-3 bg-gray-50 border border-gray-100 rounded-lg text-sm text-gray-600">
                        <span class="font-semibold text-gray-700 block text-xs uppercase tracking-wider mb-1">Nota inicial:</span>
                        {{ $active->description }}
                    </div>
                @endif

                {{-- Controles del Cronómetro --}}
                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4">
                    @if ($active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS)
                        <button wire:click="pause" wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pausar jornada
                        </button>
                    @else
                        <button wire:click="resume" wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 cursor-pointer">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Reanudar actividad
                        </button>
                    @endif

                    <button wire:click="finish"
                        wire:confirm="¿Finalizar esta actividad? No podrás reabrirla."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 cursor-pointer ml-auto">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-10h4a1 1 0 011 1v4a1 1 0 01-1 1H10a1 1 0 01-1-1v-4z"/>
                        </svg>
                        Finalizar registro
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- PANEL DE CONFIGURACIÓN E INICIO --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            
            <h3 class="text-base font-semibold uppercase tracking-wide text-gray-800 w-fit leading-[0.] overflow-hidden mb-4">
                Configuración de Bloque de Tiempo
            </h3>
            
            {{-- CONTENEDOR AJUSTADO --}}
            <div class="w-full mb-6 overflow-hidden">
                <p class="text-gray-500 text-sm text-justify leading-[30px] -mt-[8px] -mb-[7px]">
                    No tienes ninguna actividad en curso. Configura los parámetros iniciales para abrir un nuevo bloque de tiempo efectivo.
                </p>
            </div>

            <form wire:submit="start" class="space-y-6">
                
                <div class="grid sm:grid-cols-2 gap-6 items-start">
                    
                    {{-- BUSCADOR AUTOCOMPLETABLE DE CLIENTES --}}
                    <div class="relative" 
                        x-data="{ 
                            open: false, 
                            search: '', 
                            customers: {{ json_encode($customers) }},
                            selectedId: @entangle('customerId'),
                            selectedName: '',
                            
                            init() {
                                if (this.selectedId) {
                                    let match = this.customers.find(c => c.id == this.selectedId);
                                    if (match) this.selectedName = match.search_name.toUpperCase();
                                }
                                $watch('selectedId', value => {
                                    if (!value) { this.selectedName = ''; this.search = ''; }
                                });
                            },
                            select(id, name) {
                                this.selectedId = id;
                                this.selectedName = name;
                                this.search = '';
                                this.open = false;
                            },
                            get filteredCustomers() {
                                if (this.search === '') return this.customers;
                                return this.customers.filter(c => c.search_name.includes(this.search.toLowerCase()));
                            }
                        }"
                        @open-dropdown.window="if ($event.detail.id !== 'customer') open = false"
                        @click.away="open = false; search = ''">
                        
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-4 w-fit leading-[0.8] overflow-hidden">
                            Cliente
                        </label>
                        
                        <div class="relative">
                            <input type="text" 
                                x-model="search"
                                @focus="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                                @click="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                                :placeholder="selectedName || '— Escribe o selecciona un cliente —'"
                                {{-- p-4 = 1rem | pr-10 = suficiente espacio para el icono --}}
                                class="w-full bg-white border border-gray-300 rounded-lg p-4 pr-10 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-shadow truncate"
                                @keydown.escape="open = false">
                            
                            <button type="button" 
                                    @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'customer' }) }" 
                                    {{-- pr-4 asegura que el icono tenga 1rem de espacio desde el borde derecho --}}
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer">
                                <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Dropdown oculto --}}
                        <div x-show="open" 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto py-5 px-6">
                            
                            <template x-for="c in filteredCustomers" :key="c.id">
                                <button type="button" 
                                        @click="select(c.id, c.search_name.toUpperCase())"
                                        :class="{'bg-blue-50 text-blue-900 font-semibold': selectedId == c.id}"
                                        {{-- mb-4 para todos, last:mb-0 para quitar el margen al ultimo --}}
                                        class="w-full text-left text-sm hover:bg-gray-50 rounded-md text-gray-700 transition-colors truncate block mb-4 last:mb-0">
                                    <span x-text="c.search_name.toUpperCase()"></span>
                                </button>
                            </template>
                        </div>
                        @error('customerId') 
                            <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    {{-- BUSCADOR AUTOCOMPLETABLE DE ACTIVIDADES --}}
                    <div class="relative" 
                        x-data="{ 
                            open: false, 
                            search: '', 
                            services: {{ json_encode($subServices) }},
                            selectedId: @entangle('subServiceId'),
                            selectedName: '',
                            
                            init() {
                                if (this.selectedId) {
                                    let match = this.services.find(s => s.id == this.selectedId);
                                    if (match) this.selectedName = match.search_name.toUpperCase();
                                }
                                $watch('selectedId', value => {
                                    if (!value) { this.selectedName = ''; this.search = ''; }
                                });
                            },
                            select(id, name) {
                                this.selectedId = id;
                                this.selectedName = name;
                                this.search = '';
                                this.open = false;
                            },
                            get filteredServices() {
                                if (this.search === '') return this.services;
                                return this.services.filter(s => s.search_name.includes(this.search.toLowerCase()));
                            }
                        }"
                        @open-dropdown.window="if ($event.detail.id !== 'service') open = false"
                        @click.away="open = false; search = ''">
                        
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-4 w-fit leading-[0.8] overflow-hidden">
                            Actividad
                        </label>
                        <div class="relative">
                            <input type="text" 
                                x-model="search"
                                @focus="open = true; $dispatch('open-dropdown', { id: 'service' })"
                                @click="open = true; $dispatch('open-dropdown', { id: 'service' })"
                                :placeholder="selectedName || '— Escribe o selecciona una actividad —'"
                                {{-- p-4 (1rem) de relleno interno en todos los lados --}}
                                class="w-full bg-white border border-gray-300 rounded-lg p-4 pr-10 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-shadow truncate"
                                @keydown.escape="open = false">
                            
                            <button type="button" 
                                    @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'service' }) }" 
                                    {{-- pr-4 (1rem) asegura que la flecha mantenga la distancia correcta del borde derecho --}}
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer">
                                <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Dropdown con el estilo solicitado --}}
                        <div x-show="open" 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            {{-- Clases aplicadas del primer ejemplo: py-5 px-6 --}}
                            class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto py-5 px-6">
                            
                            <template x-for="s in filteredServices" :key="s.id">
                                <button type="button" 
                                        @click="select(s.id, s.search_name.toUpperCase())"
                                        :class="{'bg-blue-50 text-blue-900 font-semibold': selectedId == s.id}"
                                        {{-- Clases aplicadas del primer ejemplo: mb-4, last:mb-0 --}}
                                        class="w-full text-left text-sm hover:bg-gray-50 rounded-md text-gray-700 transition-colors truncate block mb-4 last:mb-0">
                                    <span x-text="s.search_name.toUpperCase()"></span>
                                </button>
                            </template>
                        </div>
                        @error('subServiceId') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div wire:key="description-field">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-4">
                        Descripción / Observaciones de la tarea
                    </label>
                    <div class="w-full">
                        <textarea 
                            wire:model="description" 
                            placeholder="Detalla brevemente la actividad..."
                            {{-- 'p-4' aplica 1rem en los 4 lados --}}
                            class="w-full bg-white border border-gray-300 rounded-lg text-sm p-4 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-shadow placeholder-gray-400 block no-scrollbar leading-normal"
                            style="height: 84px; overflow: hidden;"
                        ></textarea>
                    </div>
                </div>

                <style>
                    .no-scrollbar::-webkit-scrollbar { display: none; }
                    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                    textarea { resize: none !important; }
                </style>

                {{-- BOTÓN ACCIÓN CENTRADO CON AJUSTE DE MARGEN SUPERIOR --}}
                <div class="flex justify-center w-full -mt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="start"
                        {{-- CAMBIO: p-[0.7rem] reemplazado por p-4 --}}
                        class="inline-flex items-center justify-center p-4 min-w-[200px] bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg shadow-sm disabled:opacity-60 disabled:cursor-not-allowed transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
                        
                        {{-- CONTENEDOR ÚNICO FLEX-ROW CONTROLADO POR LIVEWIRE --}}
                        <span class="flex flex-row items-center justify-center gap-1.5 w-full">
                            
                            {{-- ESTADO NORMAL (ICONO PLAY) --}}
                            <svg wire:loading.remove wire:target="start" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>

                            {{-- ESTADO DE CARGA (SPINNER) --}}
                            <svg wire:loading wire:target="start" class="animate-spin h-4 w-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" style="display: none;">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            {{-- TEXTO DINÁMICO --}}
                            <span wire:loading.remove wire:target="start">Ingresar actividad</span>
                            <span wire:loading wire:target="start" style="display: none;">Sincronizando...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- HISTORIAL DEL DÍA --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        
        {{-- Cambio de inline-block a flex + w-fit --}}
        <div class="flex w-fit">
            <h2 class="text-base font-semibold uppercase tracking-wide text-gray-800">
                Actividades de hoy
            </h2>
        </div>

        @if ($todayEntries->isEmpty())
            <div class="text-center py-6 text-gray-400 text-sm italic">
                Aún no has registrado bloques de tiempo efectivo.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        {{-- El padding superior (pt-6) se mantiene aquí --}}
                        <tr class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                            <th class="pl-0 pr-4 pt-6 pb-0 font-medium w-full align-bottom">Cliente</th>
                            <th class="px-4 pt-6 pb-0 font-medium whitespace-nowrap align-bottom">Actividad</th>
                            <th class="px-4 pt-6 pb-0 font-medium whitespace-nowrap align-bottom">Tiempo</th>
                            <th class="pl-4 pr-0 pt-6 pb-0 font-medium text-left whitespace-nowrap align-bottom">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 border-b border-gray-100">
                        @foreach ($todayEntries as $entry)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="pl-0 pr-4 pt-6 pb-0 text-gray-900 truncate align-bottom">
                                    {{ $entry->customer->name ?? '—' }}
                                </td>
                                
                                <td class="px-4 pt-6 pb-0 text-gray-600 whitespace-nowrap align-bottom">
                                    {{ $entry->subService->sub_service ?? '—' }}
                                </td>

                                <td class="px-4 pt-6 pb-0 font-mono text-gray-600 whitespace-nowrap align-bottom">
                                    {{ $fmt($entry->total_duration_seconds) }}
                                </td>
                                
                                <td class="pl-4 pr-0 pt-6 pb-0 align-bottom whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        {{ ($entry->status === \App\Models\TimeEntry::STATUS_FINISHED || strtolower($entry->status_label) === 'finalizada') 
                                        ? 'bg-green-50 text-green-700 border border-green-200' 
                                        : 'bg-gray-100 text-gray-800 border border-gray-200' }}">
                                        {{ $entry->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- TOTAL EFECTIVO --}}
            <div class="pt-6 flex justify-end">
                <div class="text-sm text-gray-600 font-medium align-bottom">
                    Total efectivo del día: <span class="font-mono text-gray-900 ml-2">{{ $fmt($todayTotalSeconds) }}</span>
                </div>
            </div>
        @endif
    </div>
</div> 