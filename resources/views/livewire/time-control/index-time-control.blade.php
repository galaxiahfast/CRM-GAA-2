@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    };
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Control de Horas</h1>
        <a href="{{ route('time.reports') }}" class="text-sm text-blue-700 underline">Mi productividad</a>
    </div>

    @if ($errors->has('timer'))
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded">{{ $errors->first('timer') }}</div>
    @endif

    {{-- CRONÓMETRO --}}
    <div class="bg-white rounded shadow p-6">
        @if ($active)
            <div
                x-data="{
                    display: '00:00:00',
                    timerId: null,
                    tick() {
                        // Si Livewire reemplazó este nodo (p. ej. al Finalizar),
                        // detenemos el intervalo para no dejarlo huérfano.
                        if (! this.$el.isConnected) {
                            clearInterval(this.timerId);
                            return;
                        }
                        // Leemos el estado real desde los data-* que Livewire
                        // actualiza en cada render: así el reloj se detiene al
                        // pausar y reanuda al instante, sin desfases.
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
                <div class="text-sm text-gray-500">
                    Cliente: <span class="font-medium text-gray-800">{{ $active->customer->name ?? '—' }}</span>
                    &middot; Actividad: <span class="font-medium text-gray-800">{{ $active->subService->sub_service ?? '—' }}</span>
                </div>

                <div class="text-6xl font-mono my-4 text-gray-900" x-text="display">00:00:00</div>

                <span class="inline-block px-3 py-1 rounded text-white text-sm
                    {{ $active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS ? 'bg-green-600' : 'bg-yellow-600' }}">
                    {{ $active->status_label }}
                </span>

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($active->status === \App\Models\TimeEntry::STATUS_IN_PROGRESS)
                        <button wire:click="pause" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-yellow-600 text-white rounded disabled:opacity-60 disabled:cursor-not-allowed">Pausar</button>
                    @else
                        <button wire:click="resume" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-green-600 text-white rounded disabled:opacity-60 disabled:cursor-not-allowed">Reanudar</button>
                    @endif
                    <button wire:click="finish"
                        wire:confirm="¿Finalizar esta actividad? No podrás reabrirla."
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-red-600 text-white rounded disabled:opacity-60 disabled:cursor-not-allowed">Finalizar</button>
                </div>
            </div>
        @else
            <p class="text-gray-600 mb-4">No tienes ninguna actividad en curso. Selecciona un cliente y una actividad para iniciar.</p>

            <form wire:submit="start" class="grid sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Cliente</label>
                    <select wire:model="customerId" class="w-full border-gray-300 rounded">
                        <option value="">— Selecciona —</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}">{{ trim($c->name.' '.$c->last_name) }}</option>
                        @endforeach
                    </select>
                    @error('customerId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Actividad</label>
                    <select wire:model="subServiceId" class="w-full border-gray-300 rounded">
                        <option value="">— Selecciona —</option>
                        @foreach ($subServices as $s)
                            <option value="{{ $s->id }}">{{ $s->sub_service }}</option>
                        @endforeach
                    </select>
                    @error('subServiceId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="start"
                        class="px-4 py-2 bg-green-600 text-white rounded w-full disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="start">Iniciar</span>
                        <span wire:loading wire:target="start">Iniciando…</span>
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- ACTIVIDADES DE HOY --}}
    <div class="bg-white rounded shadow p-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-800">Actividades de hoy</h2>
            <span class="text-sm text-gray-600">Total efectivo: <span class="font-mono">{{ $fmt($todayTotalSeconds) }}</span></span>
        </div>

        @if ($todayEntries->isEmpty())
            <p class="text-gray-500 text-sm">Aún no has registrado actividades hoy.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Cliente</th>
                        <th class="py-2">Actividad</th>
                        <th class="py-2">Estado</th>
                        <th class="py-2 text-right">Tiempo efectivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todayEntries as $entry)
                        <tr class="border-b">
                            <td class="py-2">{{ $entry->customer->name ?? '—' }}</td>
                            <td class="py-2">{{ $entry->subService->sub_service ?? '—' }}</td>
                            <td class="py-2">{{ $entry->status_label }}</td>
                            <td class="py-2 text-right font-mono">{{ $fmt($entry->total_duration_seconds) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
