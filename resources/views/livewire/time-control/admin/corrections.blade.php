@php
    $fmt = function (int $s) {
        $s = max(0, $s);
        return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
    };
@endphp

<div class="max-w-6xl mx-auto space-y-4">
    <div class="space-y-4">
        <x-time-admin-tabs active="corrections" />
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded shadow-sm">{{ session('success') }}</div>
    @endif

    @if ($editing)
        {{-- EDICIÓN DE INTERVALOS DE LA ACTIVIDAD --}}
        <div class="bg-white rounded shadow p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">
                Editando Registro #{{ $editing->id }} — 
                <span class="text-blue-700">{{ trim(($editing->user->name ?? '').' '.($editing->user->last_name ?? '')) }}</span>
            </h2>

            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded grid sm:grid-cols-2 gap-2">
                <div><strong>Cliente / Cuenta:</strong> {{ $editing->customer->name ?? '—' }}</div>
                <div><strong>Sub-servicio / Tarea:</strong> {{ $editing->subService->sub_service ?? '—' }}</div>
            </div>

            <div class="space-y-3">
                @foreach ($intervals as $idx => $row)
                    <div class="grid sm:grid-cols-2 gap-3 border-b pb-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Inicio intervalo #{{ $row['id'] }}</label>
                            <input type="datetime-local" wire:model="intervals.{{ $idx }}.started_at" class="w-full border-gray-300 rounded shadow-sm focus:border-blue-500" />
                            @error("intervals.$idx.started_at") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Fin intervalo</label>
                            <input type="datetime-local" wire:model="intervals.{{ $idx }}.ended_at" class="w-full border-gray-300 rounded shadow-sm focus:border-blue-500" />
                            @error("intervals.$idx.ended_at") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de la corrección (obligatorio)</label>
                <textarea wire:model="reason" rows="2" class="w-full border-gray-300 rounded shadow-sm focus:border-blue-500"
                    placeholder="Ej. El colaborador dejó la actividad corriendo por olvido durante su descanso."></textarea>
                @error('reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button wire:click="save" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium shadow-sm transition">
                    Guardar cambios
                </button>
                <button wire:click="cancel" class="px-4 py-2 bg-gray-400 hover:bg-gray-500 text-white rounded font-medium shadow-sm transition">
                    Cancelar
                </button>
            </div>

            @if ($editing->audits && $editing->audits->isNotEmpty())
                <div class="mt-6 border-t pt-4">
                    <h3 class="font-semibold text-sm text-gray-700 mb-2">Historial de auditoría de este registro</h3>
                    <div class="space-y-1 bg-gray-50 p-3 rounded max-h-40 overflow-y-auto">
                        @foreach ($editing->audits as $audit)
                            <div class="text-xs text-gray-600 border-b border-gray-200 py-1 last:border-0">
                                <span class="font-medium text-gray-800">{{ $audit->created_at->format('d/m/Y H:i') }}</span> —
                                <span class="text-blue-600">{{ $audit->admin->name ?? 'Admin' }}</span>: {{ $audit->reason }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- LISTADO DE ACTIVIDADES REGISTRADAS --}}
        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="text-left text-gray-500 bg-gray-50 border-b font-medium">
                        <th class="p-3 pl-4">#</th>
                        <th class="p-3">Colaborador</th>
                        <th class="p-3">Cliente / Cuenta</th>
                        <th class="p-3">Actividad Realizada</th>
                        <th class="p-3">Estado</th>
                        <th class="p-3 text-right pr-4">Duración Efectiva</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3 pl-4 font-medium text-gray-400">#{{ $entry->id }}</td>
                            <td class="p-3 font-medium text-gray-900">{{ trim(($entry->user->name ?? '—').' '.($entry->user->last_name ?? '')) }}</td>
                            <td class="p-3 text-gray-600">{{ $entry->customer->name ?? '—' }}</td>
                            <td class="p-3 text-gray-600">{{ $entry->subService->sub_service ?? '—' }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $entry->status === \App\Models\TimeEntry::STATUS_AUTO_CLOSED ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                                    {{ $entry->status_label }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-mono text-gray-900 pr-4 font-semibold">{{ $fmt($entry->total_duration_seconds) }}</td>
                            <td class="p-3 text-right pr-4">
                                <button wire:click="edit({{ $entry->id }})" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition shadow-sm">
                                    Corregir tiempos
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-gray-500 text-center bg-gray-50">
                                No se encontraron registros de actividades finalizados en el periodo seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
