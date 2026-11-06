@props([
    'name' => 'q',
    'value' => '',
    'options' => [],
    'placeholder' => 'Buscar...',
    'autosubmit' => true,
    'required' => false,
    'maxlength' => null,
])

@php
    $folios = collect($options)->map(fn ($option) => (string) $option)->values()->all();
@endphp

{{-- Buscador con el diseño del panel (mismo de Administración): al enfocarlo muestra todos los folios y los va
     acotando mientras se escribe; al elegir uno se actualiza la lista. --}}
<div
    x-data="{
        open: false,
        query: @js((string) $value),
        options: @js($folios),
        listStyle: '',
        listHeight: 0,
        normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        },
        get filtered() {
            const term = this.normalize(this.query).trim();
            return term === '' ? this.options : this.options.filter(option => this.normalize(option).includes(term));
        },
        position() {
            const rect = this.$refs.input.getBoundingClientRect();
            const k = (typeof effScale === 'undefined' ? 100 : Number(effScale)) / 100 || 1;
            const gap = 10 * k;
            const wanted = (Math.max(1, Math.min(this.filtered.length, 5)) * 55 + 2) * k;
            const below = window.innerHeight - rect.bottom - gap - 8;
            const above = rect.top - gap - 8;
            const up = below < Math.min(wanted, 165 * k) && above > below;
            const height = Math.min(wanted, up ? above : below);
            const top = up ? rect.top - gap - height : rect.bottom + gap;
            this.listHeight = Math.max(1, height / k - 2);
            this.listStyle = `position: fixed; left: ${rect.left}px; top: ${top}px; width: ${rect.width / k}px; z-index: 200100; transform: scale(${k}); transform-origin: top left;`;
        },
        show() {
            this.open = true;
            this.$nextTick(() => this.position());
        },
        choose(option) {
            this.query = option;
            this.open = false;
            @if ($autosubmit)
            this.$nextTick(() => this.$refs.input.closest('form')?.requestSubmit());
            @endif
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @scroll.window.capture="if (open && ! ($refs.list && $refs.list.contains($event.target))) open = false"
    @resize.window="if (open) position()"
    {{ $attributes->class(['relative min-w-0']) }}
>
    <input
        x-ref="input"
        type="search"
        name="{{ $name }}"
        x-model="query"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @focus="show()"
        @click="show()"
        @input="show()"
        @keydown.escape.prevent="open = false"
        class="panel-field support-trigger block h-11 w-full rounded-xl border px-[20px] focus:outline-none focus:ring-0"
        role="combobox"
        aria-autocomplete="list"
        :aria-expanded="open"
    >

    <template x-teleport="body">
        <div x-ref="list" data-panel data-support-dropdown x-cloak x-show="open && filtered.length > 0" :style="listStyle" class="panel-surface panel-border overflow-hidden rounded-xl border" role="listbox">
            <div x-data="backupOverlayScroll()" x-effect="open; filtered; $nextTick(() => update())" class="relative">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll overflow-y-auto overscroll-contain" :style="`max-height: ${listHeight}px;`">
                    <template x-for="option in filtered" :key="option">
                        <button type="button" role="option" @click="choose(option)" class="panel-option block w-full truncate p-[20px] text-left leading-none focus:outline-none focus:ring-0" x-text="option"></button>
                    </template>
                </div>
                <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </template>
</div>
