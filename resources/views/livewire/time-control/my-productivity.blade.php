@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
    $pct = fn (int $part, int $whole) => $whole > 0 ? round($part * 100 / $whole) : 0;
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Mi productividad</h1>
        <a href="{{ route('time.index') }}" class="text-sm text-blue-700 underline">Volver al cronómetro</a>
    </div>

    <div class="bg-white rounded shadow p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-700 mb-1">Desde</label>
            <input type="date" wire:model.live="from" class="border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm text-gray-700 mb-1">Hasta</label>
            <input type="date" wire:model.live="to" class="border-gray-300 rounded" />
        </div>
        <div class="ml-auto text-right">
            <div class="text-sm text-gray-500">Horas efectivas en el periodo</div>
            <div class="text-2xl font-mono text-gray-900">{{ $fmt($totalSeconds) }}</div>
        </div>
    </div>

    <div class="bg-white rounded shadow p-4">
        <x-export-buttons :formats="$exportFormats" />
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold text-gray-800 mb-3">Distribución por cliente</h2>
            @forelse ($byCustomer as $name => $seconds)
                <div class="flex justify-between text-sm py-1 border-b">
                    <span>{{ $name }}</span>
                    <span class="font-mono">{{ $fmt($seconds) }} ({{ $pct($seconds, $totalSeconds) }}%)</span>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Sin datos.</p>
            @endforelse
        </div>

        <div class="bg-white rounded shadow p-4">
            <h2 class="font-semibold text-gray-800 mb-3">Distribución por actividad</h2>
            @forelse ($byActivity as $name => $seconds)
                <div class="flex justify-between text-sm py-1 border-b">
                    <span>{{ $name }}</span>
                    <span class="font-mono">{{ $fmt($seconds) }} ({{ $pct($seconds, $totalSeconds) }}%)</span>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Sin datos.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold text-gray-800 mb-3">Detalle de actividades por día</h2>
        <x-time-activity-detail :columns="$activityDetail['columns']" :groups="$activityDetail['groups']" />
    </div>
</div>
