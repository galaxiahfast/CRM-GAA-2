@props(['columns' => [], 'groups' => [], 'actions' => false, 'hiddenColumns' => []])

@php
    $visibleColumnIndexes = collect($columns)
        ->reject(fn ($column) => in_array($column, $hiddenColumns, true))
        ->keys()
        ->values();
    $visibleColumnCount = max(1, $visibleColumnIndexes->count() + ($actions ? 1 : 0));
    $columnWidth = number_format(100 / $visibleColumnCount, 4, '.', '').'%';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-5']) }}>
    @forelse ($groups as $group)
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-gray-100 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#1A3A6B]/10 text-[#1A3A6B]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v3m8-3v3M3 9h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z" /></svg>
                    </span>
                    <div>
                        <h3 class="text-[15px] font-semibold text-gray-800">{{ $group['date'] }}</h3>
                        <p class="mt-0.5 text-[13px] text-gray-500">{{ count($group['rows']) }} {{ count($group['rows']) === 1 ? 'actividad registrada' : 'actividades registradas' }}</p>
                    </div>
                </div>
            </div>
            <div class="group-scrollbar overflow-x-auto">
                <table class="w-full min-w-[1100px] table-fixed border-collapse text-[15px]">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                            @foreach ($visibleColumnIndexes as $columnIndex)
                                <th class="px-4 py-3 text-center font-semibold" style="width: {{ $columnWidth }};">
                                    <span class="block truncate" title="{{ $columns[$columnIndex] }}">{{ $columns[$columnIndex] }}</span>
                                </th>
                            @endforeach
                            @if ($actions)
                                <th class="px-4 py-3 text-center font-semibold" style="width: {{ $columnWidth }};">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($group['rows'] as $rowIndex => $row)
                            <tr class="bg-white transition-colors hover:bg-[#1A3A6B]/[0.035]">
                                @foreach ($visibleColumnIndexes as $columnIndex)
                                    @php
                                        $column = $columns[$columnIndex] ?? '';
                                        $cell = $row[$columnIndex] ?? '';
                                    @endphp
                                    <td class="px-4 py-3 text-left align-middle text-gray-700" style="width: {{ $columnWidth }};">
                                        <span class="block truncate {{ in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true) ? 'font-mono text-[14px] font-medium text-gray-900' : '' }}" title="{{ $cell }}">{{ $cell }}</span>
                                    </td>
                                @endforeach
                                @if ($actions)
                                    @php $entryId = (int) ($group['entry_ids'][$rowIndex] ?? 0); @endphp
                                    <td class="px-4 py-3 align-middle" style="width: {{ $columnWidth }};">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" title="Eliminar (próximamente)" aria-label="Eliminar actividad"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" /></svg>
                                            </button>
                                            <button type="button" title="Copiar (próximamente)" aria-label="Copiar actividad"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3" /></svg>
                                            </button>
                                            <button type="button" wire:click="openActivityEditModal({{ $entryId }})" title="Editar actividad" aria-label="Editar actividad"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-[#1A3A6B] hover:bg-blue-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536M9 11l7.586-7.586a2 2 0 0 1 2.828 0l1.172 1.172a2 2 0 0 1 0 2.828L13 15l-4 1 1-4ZM5 19h14" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="text-gray-500 text-[15px]">Sin registros en el periodo.</p>
    @endforelse
</div>
