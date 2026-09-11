@props([
    'items' => [],
    'model',
    'selected' => null,
    'placeholder' => 'Buscar...',
    'emptyMessage' => 'No se encontraron resultados.',
    'inputId' => null,
    'danger' => false,
])

@php
    $pickerId = $inputId ?: 'search-picker-'.str_replace(['.', '[', ']'], '-', $model);
    $normalizedItems = collect($items)->map(static fn ($item) => [
        'id' => (string) data_get($item, 'id'),
        'label' => (string) data_get($item, 'label'),
        'meta' => (string) data_get($item, 'meta', ''),
    ])->values();
    $selectedItem = $normalizedItems->firstWhere('id', (string) $selected);
@endphp

<div
    class="relative mt-2"
    x-data="{
        open: false,
        query: '',
        selectedId: @js(filled($selected) ? (string) $selected : null),
        selectedLabel: @js($selectedItem['label'] ?? ''),
        options: @js($normalizedItems),
        normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        },
        get filteredOptions() {
            const term = this.normalize(this.query).trim();
            return term === '' ? this.options : this.options.filter(option => this.normalize(`${option.label} ${option.meta}`).includes(term));
        },
        openList() {
            this.query = '';
            this.open = true;
            this.$nextTick(() => this.$refs.input.focus());
        },
        closeList() {
            this.query = '';
            this.open = false;
        },
        choose(option) {
            this.selectedId = option.id;
            this.selectedLabel = option.label;
            this.query = '';
            this.open = false;
            $wire.set(@js($model), option.id);
        }
    }"
    @click.outside="closeList()"
    @keydown.escape.window="closeList()"
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
        placeholder="{{ $placeholder }}"
        class="block h-11 w-full rounded-lg border bg-[#F3F3F3] px-3 pr-11 text-[15px] text-gray-800 shadow-none focus:outline-none focus:ring-0 {{ $danger ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-[#1A3A6B]' }}"
        role="combobox"
        aria-autocomplete="list"
        aria-controls="{{ $pickerId }}-results"
        :aria-expanded="open"
    >

    <button type="button" @click="open ? closeList() : openList()" class="absolute right-0 top-0 flex h-11 w-11 items-center justify-center text-[#55749D] focus:outline-none focus:ring-0" aria-label="Mostrar todas las sugerencias">
        <svg class="h-4 w-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
    </button>

    <div id="{{ $pickerId }}-results" x-ref="results" x-cloak x-show="open" class="administration-form-scrollbar absolute z-[80] mt-2 max-h-64 w-full overflow-y-auto overscroll-contain rounded-xl border border-[#CAD7E7] bg-white p-1.5 shadow-lg" role="listbox">
        <template x-for="option in filteredOptions" :key="option.id">
            <button type="button" @click="choose(option)" @keydown.enter.prevent="choose(option)" class="flex w-full min-w-0 items-center justify-between gap-3 rounded-lg px-4 py-3 text-left hover:bg-[#EEF5FF] focus:bg-[#EEF5FF] focus:outline-none" role="option" :aria-selected="String(selectedId) === String(option.id)">
                <span class="min-w-0 flex-1 truncate text-[15px] font-medium text-[#102A52]" x-text="option.label"></span>
                <span x-show="option.meta" class="max-w-[45%] shrink-0 truncate text-[12px] text-[#55749D]" x-text="option.meta"></span>
            </button>
        </template>
        <p x-show="filteredOptions.length === 0" class="px-4 py-4 text-center text-[14px] text-[#55749D]">{{ $emptyMessage }}</p>
    </div>
</div>
