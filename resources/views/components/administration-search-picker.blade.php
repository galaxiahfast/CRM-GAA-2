@props([
    'items' => [],
    'model',
    'selected' => null,
    'placeholder' => 'Buscar...',
    'emptyMessage' => 'No se encontraron resultados.',
    'inputId' => null,
    'danger' => false,
    'placement' => 'auto',
    'resultsId' => null,
    'disabled' => false,
])

@php
    $pickerId = $inputId ?: 'search-picker-'.str_replace(['.', '[', ']'], '-', $model);
    $pickerResultsId = $resultsId ?: $pickerId.'-results';
    $normalizedItems = collect($items)->map(static fn ($item) => [
        'id' => (string) data_get($item, 'id'),
        'label' => (string) data_get($item, 'label'),
        'meta' => (string) data_get($item, 'meta', ''),
        'disabled' => (bool) data_get($item, 'disabled', false),
    ])->values();
    $selectedItem = $normalizedItems->firstWhere('id', (string) $selected);
    // La clave debe permanecer estable. Cambiarla con la selección desmontaba
    // el nodo que Alpine teletransporta y Livewire conservaba referencias a un
    // componente ya eliminado.
    $pickerWireKey = $pickerId;
@endphp

<div
    wire:key="{{ $pickerWireKey }}"
    class="relative mt-[10px]"
    x-data="{
        open: false,
        query: '',
        selectedId: @js(filled($selected) ? (string) $selected : null),
        selectedLabel: @js($selectedItem['label'] ?? ''),
        options: @js($normalizedItems),
        preferredPlacement: @js($placement),
        dropdownStyle: '',
        normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        },
        get filteredOptions() {
            const term = this.normalize(this.query).trim();
            return term === '' ? this.options : this.options.filter(option => this.normalize(`${option.label} ${option.meta}`).includes(term));
        },
        openList() {
            if (this.$refs.input.disabled) return;
            this.query = '';
            this.open = true;
            this.$nextTick(() => {
                this.$refs.input.focus();
                this.positionList();
            });
        },
        positionList() {
            const rect = this.$refs.input.getBoundingClientRect();
            const opensAbove = this.preferredPlacement === 'top' || (this.preferredPlacement === 'auto' && window.innerHeight - rect.bottom < 280 && rect.top > 280);
            const top = opensAbove ? Math.max(8, rect.top - 264 - 8) : rect.bottom + 8;
            const availableHeight = opensAbove ? rect.top - 16 : window.innerHeight - top - 8;
            this.dropdownStyle = `position: fixed; left: ${rect.left}px; top: ${top}px; width: ${rect.width}px; max-height: ${Math.max(72, Math.min(256, availableHeight))}px; z-index: 10050;`;
        },
        closeList() {
            this.query = '';
            this.open = false;
        },
        choose(option) {
            if (option.disabled) return;
            this.selectedId = option.id;
            this.selectedLabel = option.label;
            this.query = '';
            this.open = false;
            $wire.set(@js($model), option.id);
        }
    }"
    @click.outside="closeList()"
    @keydown.escape.window="closeList()"
    @scroll.window="if (open) positionList()"
    @resize.window="if (open) positionList()"
>
    <input
        id="{{ $pickerId }}"
        x-ref="input"
        type="search"
        autocomplete="off"
        :value="open ? query : selectedLabel"
        @focus="openList()"
        @click="openList()"
        @input="query = $event.target.value; open = true"
        @keydown.enter.prevent="if (filteredOptions.length) choose(filteredOptions[0])"
        @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.results?.querySelector('[role=option]')?.focus())"
        @disabled($disabled)
        placeholder="{{ $placeholder }}"
        class="block h-11 w-full rounded-lg border bg-transparent px-3 pr-11 text-[15px] text-gray-800 shadow-none outline-none focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#F1F3F6] disabled:text-[#98A4B3] {{ $danger ? 'border-red-300 focus:border-red-300' : 'border-[#B7CEEA] focus:border-[#B7CEEA]' }}"
        role="combobox"
        aria-autocomplete="list"
        aria-controls="{{ $pickerResultsId }}"
        :aria-expanded="open"
    >

    <button type="button" @click="open ? closeList() : openList()" @disabled($disabled) class="absolute right-0 top-0 flex h-11 w-11 items-center justify-center text-[#55749D] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Mostrar todas las sugerencias">
        <svg class="h-4 w-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
    </button>

    <template x-teleport="body">
        <div id="{{ $pickerResultsId }}" x-ref="results" x-cloak x-show="open" :style="dropdownStyle" class="administration-form-scrollbar max-h-64 overflow-y-auto overscroll-contain rounded-xl border border-[#CAD7E7] bg-white p-1.5 shadow-xl" role="listbox">
            @foreach ($normalizedItems as $option)
                <button type="button"
                    data-item-id="{{ $option['id'] }}"
                    data-employee-id="{{ $option['id'] }}"
                    data-person-name="{{ $option['meta'] }}"
                    x-show="filteredOptions.some(option => String(option.id) === @js($option['id']))"
                    @click="choose(@js($option))"
                    @keydown.enter.prevent="choose(@js($option))"
                    @disabled($option['disabled'])
                    class="flex w-full min-w-0 items-center justify-between gap-3 rounded-lg px-4 py-3 text-left focus:outline-none {{ $option['disabled'] ? 'cursor-not-allowed bg-[#F1F3F6] opacity-55' : 'hover:bg-[#EEF5FF] focus:bg-[#EEF5FF]' }}"
                    role="option"
                    :aria-selected="String(selectedId) === @js($option['id'])">
                    <span class="min-w-0 flex-1 truncate text-[15px] font-medium text-[#102A52]">{{ $option['label'] }}</span>
                    @if ($option['meta'] !== '')
                        <span class="max-w-[45%] shrink-0 truncate text-[12px] text-[#55749D]">{{ $option['meta'] }}</span>
                    @endif
                </button>
            @endforeach
            <p x-show="filteredOptions.length === 0" class="px-4 py-4 text-center text-[14px] text-[#55749D]">{{ $emptyMessage }}</p>
        </div>
    </template>
</div>
