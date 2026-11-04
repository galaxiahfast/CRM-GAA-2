@props([
    'property' => null,
    'selected' => null,
    'options' => [],
    'id' => null,
    'label' => '',
    'icon' => null,
])

@php
    $list = collect($options)
        ->map(fn ($option) => ['value' => $option['value'], 'label' => (string) $option['label']])
        ->values()
        ->all();

    // Con "property" el valor se sincroniza con Livewire igual que wire:model; sin ella solo avisa la opción elegida
    // con el evento "panel-select" (se usa para los botones que ejecutan un método, como elegir un servicio).
    $initial = $property
        ? '$wire.$entangle('.\Illuminate\Support\Js::from($property).', true)'
        : (string) \Illuminate\Support\Js::from($selected);
@endphp

{{-- Lista desplegable con el diseño de Reportes de soporte y Administración (sustituye al select nativo del navegador). --}}
<div
    x-data="{
        open: false,
        value: {!! $initial !!},
        options: @js($list),
        get current() { return this.options.find(option => String(option.value) === String(this.value)); },
        toggle() {
            this.open = ! this.open;
            if (! this.open) return;
            this.$nextTick(() => {
                const scroller = this.$root.querySelector('.panel-scroll');
                const selected = scroller?.querySelector('[aria-selected=true]');
                if (scroller && selected) scroller.scrollTop = Math.max(0, selected.offsetTop - (scroller.clientHeight - selected.offsetHeight) / 2);
            });
        },
        pick(option) {
            this.open = false;
            if (String(option.value) !== String(this.value)) {
                this.value = option.value;
                this.$dispatch('panel-select', { value: option.value });
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    {{ $attributes->class(['relative']) }}
>
    <button type="button" @if ($id) id="{{ $id }}" @endif @click="toggle()" aria-haspopup="listbox" :aria-expanded="open" @if ($label) aria-label="{{ $label }}" @endif
        class="panel-field flex h-11 w-full items-center rounded-xl border text-left leading-none focus:outline-none focus:ring-0 {{ $icon ? 'gap-[8px] pl-[12px] pr-[10px]' : 'gap-[10px] px-[15px]' }}">
        @if ($icon)
            <svg class="panel-accent h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}" /></svg>
        @endif
        <span class="min-w-0 flex-1 truncate" x-text="current ? current.label : ''"></span>
        <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
    </button>

    <div x-cloak x-show="open" class="panel-surface panel-border absolute left-0 top-full z-40 mt-[10px] w-full overflow-hidden rounded-xl border" role="listbox">
        <div x-data="backupOverlayScroll()" x-effect="open; $nextTick(() => update())" class="relative">
            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll relative overflow-y-auto overscroll-contain" style="max-height: 275px;">
                <template x-for="option in options" :key="option.value">
                    <button type="button" role="option" :aria-selected="String(option.value) === String(value)" @click="pick(option)"
                        class="panel-option block w-full truncate p-[20px] text-left leading-none focus:outline-none focus:ring-0" x-text="option.label"></button>
                </template>
            </div>
            <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
        </div>
    </div>
</div>
