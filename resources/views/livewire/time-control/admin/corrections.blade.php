@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Corrección de registros</h1>
        <a href="{{ route('time.admin.dashboard') }}" class="text-sm text-blue-700 underline">Supervisión</a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded">{{ session('success') }}</div>
    @endif

    @if ($editing)
        {{-- EDICIÓN --}}
        <div class="bg-white rounded shadow p-6 space-y-4">
            <h2 class="font-semibold text-gray-800">
                Editando #{{ $editing->id }} —
                {{ trim(($editing->user->name ?? '').' '.($editing->user->last_name ?? '')) }}
                / {{ $editing->customer->name ?? '—' }} / {{ $editing->subService->sub_service ?? '—' }}
            </h2>

            <div class="space-y-3">
                @foreach ($intervals as $idx => $row)
                    <div class="grid sm:grid-cols-2 gap-3 border-b pb-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Inicio intervalo #{{ $row['id'] }}</label>
                            <input type="datetime-local" wire:model="intervals.{{ $idx }}.started_at" class="w-full border-gray-300 rounded" />
                            @error("intervals.$idx.started_at") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Fin intervalo</label>
                            <input type="datetime-local" wire:model="intervals.{{ $idx }}.ended_at" class="w-full border-gray-300 rounded" />
                            @error("intervals.$idx.ended_at") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">Motivo de la corrección (obligatorio)</label>
                <textarea wire:model="reason" rows="2" class="w-full border-gray-300 rounded"
                    placeholder="Ej. El auxiliar olvidó pausar durante la comida."></textarea>
                @error('reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-3">
                <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded">Guardar cambios</button>
                <button wire:click="cancel" class="px-4 py-2 bg-gray-400 text-white rounded">Cancelar</button>
            </div>

            @if ($editing->audits->isNotEmpty())
                <div class="mt-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Historial de auditoría</h3>
                    @foreach ($editing->audits as $audit)
                        <div class="text-xs text-gray-600 border-b py-1">
                            {{ $audit->created_at->format('d/m/Y H:i') }} —
                            {{ $audit->admin->name ?? 'admin' }}: {{ $audit->reason }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- LISTADO --}}
        <div class="bg-white rounded shadow p-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">#</th>
                        <th class="py-2">Colaborador</th>
                        <th class="py-2">Cliente</th>
                        <th class="py-2">Actividad</th>
                        <th class="py-2">Estado</th>
                        <th class="py-2 text-right">Tiempo</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="border-b">
                            <td class="py-2">{{ $entry->id }}</td>
                            <td class="py-2">{{ trim(($entry->user->name ?? '—').' '.($entry->user->last_name ?? '')) }}</td>
                            <td class="py-2">{{ $entry->customer->name ?? '—' }}</td>
                            <td class="py-2">{{ $entry->subService->sub_service ?? '—' }}</td>
                            <td class="py-2">
                                <span class="{{ $entry->status === \App\Models\TimeEntry::STATUS_AUTO_CLOSED ? 'text-red-600' : '' }}">
                                    {{ $entry->status_label }}
                                </span>
                            </td>
                            <td class="py-2 text-right font-mono">{{ $fmt($entry->total_duration_seconds) }}</td>
                            <td class="py-2 text-right">
                                <button wire:click="edit({{ $entry->id }})" class="px-3 py-1 bg-blue-600 text-white rounded text-xs">Corregir</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-3 text-gray-500">No hay registros finalizados para corregir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
