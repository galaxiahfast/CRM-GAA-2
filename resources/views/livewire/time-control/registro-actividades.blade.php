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

<div class="support-monochrome min-h-[calc(100dvh-90px)] w-full bg-white text-[15px] text-zinc-700">
    <header class="flex items-center justify-between gap-[20px] whitespace-nowrap border-b border-gray-200 p-[50px]">
        <div class="flex items-center gap-[15px] text-gray-500">
            <span class="font-medium">Actividades</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-medium">Control de horas</span>
            <span class="text-gray-300">&gt;</span>
            <a href="{{ route('time.index') }}" class="font-semibold text-black transition-colors hover:text-zinc-600">Cronómetro</a>
        </div>

        <div class="no-print flex items-center gap-[20px]">
            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-medium text-black transition-colors hover:text-zinc-600 disabled:cursor-wait disabled:opacity-60">
                <svg wire:loading.remove wire:target="downloadPdf" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg wire:loading wire:target="downloadPdf" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true" style="display: none;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <span>Descargar PDF</span>
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-medium text-black transition-colors hover:text-zinc-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" />
                </svg>
                <span>Imprimir</span>
            </button>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1500px] space-y-[20px] p-[50px]">
    <section class="flex flex-wrap items-end justify-between gap-[20px]">
        <div class="flex items-center gap-[20px]">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-black">Cronómetro</h1>
                <p class="mt-[5px] text-[15px] text-zinc-500">Registra y administra el tiempo dedicado a tus actividades.</p>
            </div>
        </div>
        <div class="flex items-center gap-[10px]">
            <a href="{{ route('time.reports') }}" class="group inline-flex items-center rounded-xl bg-black px-[20px] py-[15px] text-[15px] font-normal text-white transition-colors hover:bg-zinc-800">
                Mi productividad
                <svg class="ml-[10px] h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <a href="{{ route('time.dashboard') }}" class="group inline-flex items-center rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-normal text-black transition-colors hover:bg-zinc-100">
                Panel de control
                <svg class="ml-[10px] h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </section>

    @if ($errors->has('timer'))
        <div class="flex items-center rounded-xl border border-red-200 bg-red-50 p-[20px] text-[15px] text-red-700">
            <svg class="w-5 h-5 mr-2 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $errors->first('timer') }}
        </div>
    @endif

    <section class="grid min-h-[520px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.05)] xl:grid-cols-[360px_minmax(0,1fr)]">
    <aside class="border-b border-zinc-200 bg-zinc-50/40 p-[20px] xl:border-b-0 xl:border-r">
        <div class="mb-[20px] border-b border-zinc-200 pb-[20px]">
            <h2 class="text-[15px] font-semibold text-black">Nuevo registro</h2>
            <p class="mt-[5px] text-[15px] leading-6 text-zinc-500">Selecciona dónde trabajarás.</p>
        </div>
        <form wire:submit="start">
            <div class="grid gap-[20px]">
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
                    <label class="mb-[10px] block text-[15px] font-semibold text-black">
                        Cliente
                    </label>

                    <div class="relative">
                        <input
                            type="text"
                            x-model="search"
                            @focus="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                            @click="open = true; $dispatch('open-dropdown', { id: 'customer' })"
                            :placeholder="selectedName || '-- Escribe o selecciona un cliente --'"
                            class="w-full truncate rounded-xl border border-zinc-200 bg-white py-[15px] pl-[20px] pr-[46px] text-[15px] text-zinc-800 placeholder-zinc-400 transition-shadow focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
                            @keydown.escape="open = false"
                        >

                        <button type="button" @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'customer' }) }" class="absolute inset-y-0 right-[20px] flex cursor-pointer items-center">
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div
                        x-show="open"
                        x-transition
                        class="notification-scrollbar absolute z-50 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_12px_32px_rgba(0,0,0,0.08)]"
                    >
                        <template x-for="c in filteredCustomers" :key="c.id">
                            <button type="button" @click="select(c.id, c.search_name.toUpperCase())" :class="{'bg-black text-white font-semibold': selectedId == c.id}" class="mb-[10px] block w-full truncate rounded-lg px-[15px] py-[10px] text-left text-[15px] text-zinc-700 transition-colors last:mb-0 hover:bg-zinc-100">
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
                    <label class="mb-[10px] block text-[15px] font-semibold text-black">
                        Actividad
                    </label>

                    <div class="relative">
                        <input
                            type="text"
                            x-model="search"
                            @focus="open = true; $dispatch('open-dropdown', { id: 'service' })"
                            @click="open = true; $dispatch('open-dropdown', { id: 'service' })"
                            :placeholder="selectedName || '-- Escribe o selecciona una actividad --'"
                            class="w-full truncate rounded-xl border border-zinc-200 bg-white py-[15px] pl-[20px] pr-[46px] text-[15px] text-zinc-800 placeholder-zinc-400 transition-shadow focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
                            @keydown.escape="open = false"
                        >

                        <button type="button" @click.stop="open = !open; if(open) { $dispatch('open-dropdown', { id: 'service' }) }" class="absolute inset-y-0 right-[20px] flex cursor-pointer items-center">
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div
                        x-show="open"
                        x-transition
                        class="notification-scrollbar absolute z-50 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_12px_32px_rgba(0,0,0,0.08)]"
                    >
                        <template x-for="s in filteredServices" :key="s.id">
                            <button type="button" @click="select(s.id, s.search_name.toUpperCase())" :class="{'bg-black text-white font-semibold': selectedId == s.id}" class="mb-[10px] block w-full truncate rounded-lg px-[15px] py-[10px] text-left text-[15px] text-zinc-700 transition-colors last:mb-0 hover:bg-zinc-100">
                                <span x-text="s.search_name.toUpperCase()"></span>
                            </button>
                        </template>
                    </div>

                    @error('subServiceId')
                        <span class="text-xs text-red-600 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="start" class="mt-[5px] inline-flex w-full cursor-pointer items-center justify-center rounded-xl bg-black px-[20px] py-[15px] text-[15px] font-semibold text-white transition-colors hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-black/10 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                    <span class="flex w-full flex-row items-center justify-center gap-[10px]">
                        <svg wire:loading.remove wire:target="start" class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8 5.14v13.72L19 12 8 5.14z"/>
                        </svg>

                        <svg wire:loading wire:target="start" class="animate-spin h-4 w-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="start">Iniciar actividad</span>
                        <span wire:loading wire:target="start" style="display: none;">Sincronizando...</span>
                    </span>
                </button>
            </div>
        </form>
        <div class="mt-[20px] rounded-xl border border-zinc-200 bg-white p-[20px] text-[15px] leading-6 text-zinc-500">
            Solo puede existir un cronómetro activo. Al iniciar otro registro, el anterior se pausa automáticamente.
        </div>
    </aside>

    <div
        class="min-w-0 bg-white p-[20px]"
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
        <div class="flex flex-wrap items-center justify-between gap-[15px] border-b border-zinc-200 pb-[20px]">
            <div>
                <h2 class="text-[15px] font-semibold text-black">Hoy</h2>
                <p class="mt-[5px] text-[15px] text-zinc-500">Tiempo registrado por cliente y actividad.</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-[20px] py-[15px] text-[15px] font-semibold text-black">
                <span class="mr-[10px] font-normal text-zinc-500">Tiempo total</span>
                <span class="font-mono tabular-nums" x-text="totalDisplay">{{ $fmt($todayTotalSeconds) }}</span>
            </div>
        </div>

        @if ($todayEntries->isEmpty())
            <div class="flex min-h-[350px] flex-col items-center justify-center text-center text-[15px] text-zinc-500">
                <span class="mb-[15px] flex h-12 w-12 items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 text-zinc-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <p class="font-semibold text-zinc-700">Tu jornada aún no tiene registros</p>
                <p class="mt-[5px]">Selecciona un cliente y una actividad para comenzar.</p>
            </div>
        @else
            <div class="notification-scrollbar overflow-x-auto">
                <table class="w-full border-collapse text-left text-[15px]">
                    <thead class="border-b border-zinc-200">
                        <tr class="text-[15px] font-bold text-black">
                            <th class="min-w-[180px] p-[20px] font-bold align-middle">Cliente</th>
                            <th class="whitespace-nowrap p-[20px] font-bold align-middle">Actividad</th>
                            <th class="whitespace-nowrap p-[20px] font-bold align-middle">Tiempo</th>
                            <th class="whitespace-nowrap p-[20px] font-bold align-middle">Estado</th>
                            <th class="whitespace-nowrap p-[20px] text-left font-bold align-middle">Acción</th>
                            <th class="w-px whitespace-nowrap p-[20px] text-left font-bold align-middle"><span class="sr-only">Eliminar</span></th>
                        </tr>
                    </thead>
                    <tbody data-activity-sortable class="divide-y divide-zinc-300">
                        @foreach ($todayEntries as $entry)
                            <tr
                                wire:key="time-entry-{{ $entry->id }}"
                                data-activity-id="{{ $entry->id }}"
                                class="transition-[opacity,background-color] duration-150 hover:bg-zinc-50"
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
                                <td class="max-w-[260px] p-[20px] align-middle text-gray-900">
                                    <div class="truncate" title="{{ $entry->customer->name ?? '--' }}">
                                        {{ $entry->customer->name ?? '--' }}
                                    </div>
                                </td>

                                <td class="max-w-[220px] p-[20px] align-middle text-gray-600">
                                    <div class="truncate" title="{{ $entry->subService->sub_service ?? '--' }}">
                                        {{ $entry->subService->sub_service ?? '--' }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap p-[20px] align-middle font-mono font-bold text-black tabular-nums" x-text="display">
                                    {{ $fmt($entry->calculateEffectiveSeconds()) }}
                                </td>

                                <td class="whitespace-nowrap p-[20px] align-middle">
                                    <span class="inline-flex w-[150px] items-center justify-center rounded-full border px-[20px] py-[15px] text-[15px] font-semibold {{ match ($entry->status) {
                                        \App\Models\TimeEntry::STATUS_IN_PROGRESS => 'border-[#15803d]/30 bg-[#15803d]/10 text-[#166534]',
                                        \App\Models\TimeEntry::STATUS_PAUSED, \App\Models\TimeEntry::STATUS_FINISHED => 'border-[#881337]/30 bg-[#881337]/10 text-[#881337]',
                                        default => 'border-zinc-300 bg-zinc-100 text-zinc-700',
                                    } }}">
                                        {{ in_array($entry->status, [\App\Models\TimeEntry::STATUS_PAUSED, \App\Models\TimeEntry::STATUS_FINISHED], true) ? 'Pausada' : $entry->status_label }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap p-[20px] align-middle">
                                    <div class="flex items-center justify-start gap-[10px]">
                                        @if ($entry->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS)
                                            <button type="button" wire:click="pause({{ $entry->id }})" wire:loading.attr="disabled" class="inline-flex w-[140px] cursor-pointer items-center justify-center gap-[10px] rounded-xl bg-black px-[20px] py-[15px] text-[15px] font-normal text-white transition-colors hover:bg-zinc-800 disabled:opacity-50" title="Pausar">
                                                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M7 5h4v14H7V5zm6 0h4v14h-4V5z"/>
                                                </svg>
                                                <span>Pausar</span>
                                            </button>
                                        @elseif ($entry->status !== \App\Models\TimeEntry::STATUS_AUTO_CLOSED)
                                            <button type="button" wire:click="resume({{ $entry->id }})" wire:loading.attr="disabled" class="inline-flex w-[140px] cursor-pointer items-center justify-center gap-[10px] rounded-xl bg-black px-[20px] py-[15px] text-[15px] font-normal text-white transition-colors hover:bg-zinc-800 disabled:opacity-50" title="Reanudar">
                                                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M8 5.14v13.72L19 12 8 5.14z"/>
                                                </svg>
                                                <span>Reanudar</span>
                                            </button>
                                        @else
                                            <button type="button" disabled class="inline-flex h-10 w-[140px] cursor-not-allowed items-center justify-center gap-[10px] rounded-xl bg-zinc-100 px-[15px] text-zinc-400" title="Cerrada automáticamente">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Cerrada</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <td class="w-px whitespace-nowrap p-[20px] align-middle">
                                    <div class="flex items-center gap-[10px]">
                                        <button
                                            type="button"
                                            data-activity-drag-handle
                                            class="inline-flex cursor-grab touch-none select-none items-center justify-center rounded-xl border border-zinc-200 bg-white p-[15px] text-black transition-colors hover:bg-zinc-100 active:cursor-grabbing"
                                            aria-label="Mover {{ $entry->subService->sub_service ?? 'actividad' }}"
                                            title="Mover actividad"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle cx="8" cy="6" r="1.5"/><circle cx="16" cy="6" r="1.5"/><circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/><circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="requestDeletion({{ $entry->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="requestDeletion({{ $entry->id }})"
                                            class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 p-[15px] text-red-700 transition-colors hover:border-red-300 hover:bg-red-100 disabled:cursor-wait disabled:opacity-50"
                                            aria-label="Eliminar {{ $entry->subService->sub_service ?? 'actividad' }}"
                                            title="Eliminar actividad"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
    </div>
    </section>
    </main>

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-entry-title">
            <div class="w-full max-w-[520px] rounded-2xl border border-zinc-200 bg-white p-[20px] shadow-[0_24px_80px_rgba(0,0,0,0.18)]">
                <div class="flex items-start justify-between gap-[20px] border-b border-zinc-200 pb-[20px]">
                    <div>
                        <h2 id="delete-entry-title" class="text-xl font-semibold text-black">Eliminar actividad</h2>
                        <p class="mt-[5px] text-[15px] leading-6 text-zinc-500">Esta acción eliminará el registro y todo el tiempo asociado.</p>
                    </div>
                    <button type="button" wire:click="cancelDeletion" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white p-[15px] text-black transition-colors hover:bg-zinc-100" aria-label="Cerrar confirmación">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>

                <div class="py-[20px]">
                    <p class="text-[15px] leading-6 text-zinc-700">Para confirmar, escribe exactamente:</p>
                    <p class="mt-[10px] rounded-xl bg-zinc-100 p-[15px] text-[15px] font-bold text-black">{{ $deleteActivityName }}</p>

                    <label for="delete-activity-confirmation" class="mb-[10px] mt-[20px] block text-[15px] font-semibold text-black">Nombre de la actividad</label>
                    <input
                        id="delete-activity-confirmation"
                        type="text"
                        wire:model="deleteConfirmation"
                        wire:keydown.enter="deleteEntry"
                        autocomplete="off"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] text-black placeholder-zinc-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
                        placeholder="Escribe el nombre completo"
                        autofocus
                    >
                    @error('deleteConfirmation')
                        <p class="mt-[10px] text-[15px] text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-[10px] border-t border-zinc-200 pt-[20px]">
                    <button type="button" wire:click="cancelDeletion" class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-normal text-black transition-colors hover:bg-zinc-100">Cancelar</button>
                    <button type="button" wire:click="deleteEntry" wire:loading.attr="disabled" wire:target="deleteEntry" class="rounded-xl bg-[#881337] px-[20px] py-[15px] text-[15px] font-normal text-white transition-colors hover:bg-[#6f102d] disabled:cursor-wait disabled:opacity-50">
                        <span wire:loading.remove wire:target="deleteEntry">Eliminar actividad</span>
                        <span wire:loading wire:target="deleteEntry">Eliminando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
