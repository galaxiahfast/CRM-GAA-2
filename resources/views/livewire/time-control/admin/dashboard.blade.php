@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Supervisión de horas</h1>
        <a href="{{ route('time.admin.corrections') }}" class="text-sm text-blue-700 underline">Correcciones</a>
    </div>

    <div class="bg-white rounded shadow p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-700 mb-1">Colaborador</label>
            <select wire:model.live="userId" class="border-gray-300 rounded">
                <option value="">Todos los colaboradores</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ trim($u->name.' '.$u->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-700 mb-1">Desde</label>
            <input type="date" wire:model.live="from" class="border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm text-gray-700 mb-1">Hasta</label>
            <input type="date" wire:model.live="to" class="border-gray-300 rounded" />
        </div>
        <div class="ml-auto text-right">
            <div class="text-sm text-gray-500">Horas efectivas totales</div>
            <div class="text-2xl font-mono">{{ $fmt($total) }}</div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Cierres automáticos</div>
            <div class="text-2xl font-mono text-red-600">{{ $autoClosedCount }}</div>
        </div>
    </div>

    <div class="bg-white rounded shadow p-4">
        <x-export-buttons :formats="$exportFormats" />
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        @foreach ([
            'Por colaborador' => $byCollaborator,
            'Por cliente' => $byCustomer,
            'Por puesto profesional' => $byPosition,
            'Por área física' => $byArea,
        ] as $title => $rows)
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold text-gray-800 mb-3">{{ $title }}</h2>
                @forelse ($rows as $row)
                    <div class="flex justify-between text-sm py-1 border-b">
                        <span>{{ $row['name'] }}</span>
                        <span class="font-mono">{{ $fmt($row['seconds']) }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Sin datos.</p>
                @endforelse
            </div>
        @endforeach
    </div>
</div>
