@php
    $moduleTimezone = (string) config('app.timezone', 'America/Mexico_City');
    $moduleTimezone = $moduleTimezone === 'UTC' ? 'America/Mexico_City' : $moduleTimezone;

    $fmt = function (int $s) {
        $s = max(0, $s);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    };

    $secondsFor = function ($entry) {
        return (int) $entry->intervals->reduce(function (int $carry, $interval) {
            if (! $interval->ended_at) {
                return $carry;
            }

            return $carry + max(0, $interval->ended_at->diffInSeconds($interval->started_at, true));
        }, 0);
    };

    $timestampFor = function ($date) use ($moduleTimezone) {
        if (! $date) {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($date->format('Y-m-d H:i:s'), $moduleTimezone)->timestamp;
    };

    $openStartFor = function ($entry) use ($timestampFor) {
        if ($entry->status !== \App\Models\TimeEntry::STATUS_IN_PROGRESS) {
            return '';
        }

        return $timestampFor($entry->intervals->firstWhere('ended_at', null)?->started_at);
    };

    $todayLiveBaseSeconds = (int) $todayEntries->sum(fn ($entry) => $secondsFor($entry));
    $todayOpenStart = $active ? $openStartFor($active) : '';
@endphp

<div class="max-w-6xl mx-auto space-y-6 p-6">
    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Control de Horas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestion de tiempos y metricas de productividad.</p>
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

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-base font-semibold uppercase tracking-wide text-gray-800 mb-4">
            Configuracion de Bloque de Tiempo
        </h3>

        <form wire:submit="start" class="space-y-6">
            <div class="grid sm:grid-cols-2 gap-6 items-start">
                <div
                    class="relative"
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
                                if (! value) { this.selectedName = ''; this.search = ''; }
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
                    @click.away="open = false; search = ''"
                >
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-4">
                        Cliente
                    </label>

                    <div class="relative">
                        <input
                            type="text"
                            x-model="search"
                            @focus="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                            @click="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                            :placeholder="selectedName || '-- Escribe o selecciona un cliente --'"
                            class="w-full bg-white border border-gray-300 rounded-lg p-4 pr-10 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-shadow truncate"
                            @keydown.escape="open = false"
                        >

                        <button type="button" @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'customer' }) }" class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer">
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div
                        x-show="open"
                        x-transition
                        class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto py-5 px-6"
                    >
                        <template x-for="c in filteredCustomers" :key="c.id">
                            <button type="button" @click="select(c.id, c.search_name.toUpperCase())" :class="{'bg-blue-50 text-blue-900 font-semibold': selectedId == c.id}" class="w-full text-left text-sm hover:bg-gray-50 rounded-md text-gray-700 transition-colors truncate block mb-4 last:mb-0">
                                <span x-text="c.search_name.toUpperCase()"></span>
                            </button>
                        </template>
                    </div>

                    @error('customerId')
                        <span class="text-xs text-red-600 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div
                    class="relative"
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
                                if (! value) { this.selectedName = ''; this.search = ''; }
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
                    @click.away="open = false; search = ''"
                >
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-4">
                        Actividad
                    </label>

                    <div class="relative">
                        <input
                            type="text"
                            x-model="search"
                            @focus="open = true; $dispatch('open-dropdown', { id: 'service' })"
                            @click="open = true; $dispatch('open-dropdown', { id: 'service' })"
                            :placeholder="selectedName || '-- Escribe o selecciona una actividad --'"
                            class="w-full bg-white border border-gray-300 rounded-lg p-4 pr-10 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-shadow truncate"
                            @keydown.escape="open = false"
                        >

                        <button type="button" @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'service' }) }" class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer">
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div
                        x-show="open"
                        x-transition
                        class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto py-5 px-6"
                    >
                        <template x-for="s in filteredServices" :key="s.id">
                            <button type="button" @click="select(s.id, s.search_name.toUpperCase())" :class="{'bg-blue-50 text-blue-900 font-semibold': selectedId == s.id}" class="w-full text-left text-sm hover:bg-gray-50 rounded-md text-gray-700 transition-colors truncate block mb-4 last:mb-0">
                                <span x-text="s.search_name.toUpperCase()"></span>
                            </button>
                        </template>
                    </div>

                    @error('subServiceId')
                        <span class="text-xs text-red-600 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-center w-full">
                <button type="submit" wire:loading.attr="disabled" wire:target="start" class="inline-flex items-center justify-center p-4 min-w-[200px] bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg shadow-sm disabled:opacity-60 disabled:cursor-not-allowed transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
                    <span class="flex flex-row items-center justify-center gap-1.5 w-full">
                        <svg wire:loading.remove wire:target="start" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>

                        <svg wire:loading wire:target="start" class="animate-spin h-4 w-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="start">Ingresar actividad</span>
                        <span wire:loading wire:target="start" style="display: none;">Sincronizando...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>

    <div
        class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"
        x-data="{
            totalDisplay: '00:00:00',
            totalTimerId: null,
            formatSeconds(seconds) {
                seconds = Math.max(0, parseInt(seconds || 0, 10));
                let h = Math.floor(seconds / 3600);
                let m = Math.floor((seconds % 3600) / 60);
                let s = seconds % 60;

                return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            },
            currentEpochSecond() {
                return Math.floor(Date.now() / 1000);
            },
            elapsedFrom(openStart) {
                if (! openStart) {
                    return 0;
                }

                return Math.max(0, this.currentEpochSecond() - parseInt(openStart, 10));
            },
            tickTotal() {
                if (! this.$el.isConnected) {
                    clearInterval(this.totalTimerId);
                    return;
                }

                let total = parseInt(this.$el.dataset.totalAccumulated || '0', 10);
                total += this.elapsedFrom(this.$el.dataset.totalOpenStart);
                this.totalDisplay = this.formatSeconds(total);
            }
        }"
        x-init="tickTotal(); totalTimerId = setInterval(() => tickTotal(), 1000)"
        data-total-accumulated="{{ $todayLiveBaseSeconds }}"
        data-total-open-start="{{ $todayOpenStart }}"
    >
        <div class="flex w-fit">
            <h2 class="text-base font-semibold uppercase tracking-wide text-gray-800">
                Actividades de hoy
            </h2>
        </div>

        @if ($todayEntries->isEmpty())
            <div class="text-center py-6 text-gray-400 text-sm italic">
                Aun no has registrado bloques de tiempo efectivo.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                            <th class="pl-0 pr-4 pt-6 pb-0 font-medium min-w-[180px] align-bottom">Cliente</th>
                            <th class="px-4 pt-6 pb-0 font-medium whitespace-nowrap align-bottom">Actividad</th>
                            <th class="px-4 pt-6 pb-0 font-medium whitespace-nowrap align-bottom">Tiempo</th>
                            <th class="px-4 pt-6 pb-0 font-medium whitespace-nowrap align-bottom">Estado</th>
                            <th class="pl-4 pr-0 pt-6 pb-0 font-medium text-right whitespace-nowrap align-bottom">Cronometro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 border-b border-gray-100">
                        @foreach ($todayEntries as $entry)
                            <tr
                                wire:key="time-entry-{{ $entry->id }}"
                                class="hover:bg-gray-50/70 transition-colors"
                                x-data="{
                                    display: '00:00:00',
                                    timerId: null,
                                    formatSeconds(seconds) {
                                        seconds = Math.max(0, parseInt(seconds || 0, 10));
                                        let h = Math.floor(seconds / 3600);
                                        let m = Math.floor((seconds % 3600) / 60);
                                        let s = seconds % 60;

                                        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                    },
                                    currentEpochSecond() {
                                        return Math.floor(Date.now() / 1000);
                                    },
                                    elapsedFrom(openStart) {
                                        if (! openStart) {
                                            return 0;
                                        }

                                        return Math.max(0, this.currentEpochSecond() - parseInt(openStart, 10));
                                    },
                                    tick() {
                                        if (! this.$el.isConnected) {
                                            clearInterval(this.timerId);
                                            return;
                                        }

                                        let total = parseInt(this.$el.dataset.accumulated || '0', 10);
                                        total += this.elapsedFrom(this.$el.dataset.openStart);
                                        this.display = this.formatSeconds(total);
                                    }
                                }"
                                x-init="tick(); timerId = setInterval(() => tick(), 1000)"
                                data-accumulated="{{ $secondsFor($entry) }}"
                                data-open-start="{{ $openStartFor($entry) }}"
                            >
                                <td class="pl-0 pr-4 pt-6 pb-0 text-gray-900 truncate align-bottom">
                                    {{ $entry->customer->name ?? '--' }}
                                </td>

                                <td class="px-4 pt-6 pb-0 text-gray-600 whitespace-nowrap align-bottom">
                                    {{ $entry->subService->sub_service ?? '--' }}
                                </td>

                                <td class="px-4 pt-6 pb-0 font-mono text-gray-600 whitespace-nowrap align-bottom tabular-nums" x-text="display">
                                    {{ $fmt($entry->calculateEffectiveSeconds()) }}
                                </td>

                                <td class="px-4 pt-6 pb-0 align-bottom whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ match ($entry->status) {
                                        \App\Models\TimeEntry::STATUS_IN_PROGRESS => 'bg-green-50 text-green-700 border border-green-200',
                                        \App\Models\TimeEntry::STATUS_PAUSED, \App\Models\TimeEntry::STATUS_FINISHED => 'bg-amber-50 text-amber-700 border border-amber-200',
                                        default => 'bg-gray-100 text-gray-800 border border-gray-200',
                                    } }}">
                                        {{ in_array($entry->status, [\App\Models\TimeEntry::STATUS_PAUSED, \App\Models\TimeEntry::STATUS_FINISHED], true) ? 'Pausada' : $entry->status_label }}
                                    </span>
                                </td>

                                <td class="pl-4 pr-0 pt-6 pb-0 align-bottom whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($entry->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS)
                                            <button type="button" wire:click="pause({{ $entry->id }})" wire:loading.attr="disabled" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition-colors disabled:opacity-50 cursor-pointer" title="Pausar">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        @elseif ($entry->status !== \App\Models\TimeEntry::STATUS_AUTO_CLOSED)
                                            <button type="button" wire:click="resume({{ $entry->id }})" wire:loading.attr="disabled" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors disabled:opacity-50 cursor-pointer" title="Iniciar">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        @else
                                            <button type="button" disabled class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed" title="Cerrada automaticamente">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-6 flex justify-end">
                <div class="text-sm text-gray-600 font-medium align-bottom">
                    Total efectivo del dia: <span class="font-mono text-gray-900 ml-2 tabular-nums" x-text="totalDisplay">{{ $fmt($todayTotalSeconds) }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
