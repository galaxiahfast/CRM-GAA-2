@props([
    'name',
    'value' => '',
    'options' => [],
    'allLabel' => 'Todos',
    'label' => null,
])

@php
    $list = collect([['value' => '', 'label' => $allLabel]])
        ->concat(collect($options)->map(fn ($option) => is_array($option) ? ['value' => (string) $option['value'], 'label' => (string) $option['label']] : ['value' => (string) $option, 'label' => (string) $option]))
        ->values()
        ->all();
@endphp

{{-- Menú desplegable con el diseño del panel (mismo de Administración); envía su valor en un campo oculto.
     Lee `effScale` (escala efectiva de la vista) de la página para colocar la lista fuera de la zona escalada. --}}
<div
    x-data="{
        open: false,
        value: @js((string) $value),
        options: @js($list),
        listStyle: '',
        listHeight: 0,
        get label() { return this.options.find(option => option.value === this.value)?.label ?? ''; },
        toggle() {
            if (this.open) { this.open = false; return; }
            const rect = this.$refs.trigger.getBoundingClientRect();
            const k = (typeof effScale === 'undefined' ? 100 * (window.appAutoScale ? window.appAutoScale() : 1) : Number(effScale)) / 100 || 1;
            const gap = 10 * k;
            const wanted = (Math.max(1, Math.min(this.options.length, 5)) * 55 + 2) * k;
            const below = window.innerHeight - rect.bottom - gap - 8;
            const above = rect.top - gap - 8;
            const up = below < Math.min(wanted, 165 * k) && above > below;
            const height = Math.min(wanted, up ? above : below);
            const top = up ? rect.top - gap - height : rect.bottom + gap;
            this.listHeight = Math.max(1, height / k - 2);
            this.listStyle = `position: fixed; left: ${rect.left}px; top: ${top}px; width: ${rect.width / k}px; z-index: 200100; transform: scale(${k}); transform-origin: top left;`;
            this.open = true;
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @scroll.window.capture="if (open && ! ($refs.list && $refs.list.contains($event.target))) open = false"
    {{ $attributes->class(['relative min-w-0']) }}
>
    <input type="hidden" name="{{ $name }}" :value="value">

    <button x-ref="trigger" type="button" @click="toggle()" class="panel-field support-trigger flex h-11 w-full items-center justify-between gap-[10px] rounded-xl border px-[20px] text-left leading-none outline-none focus:outline-none focus:ring-0" aria-haspopup="listbox" :aria-expanded="open">
        @if ($label)<span class="sr-only">{{ $label }}:</span>@endif
        <span class="min-w-0 flex-1 truncate" x-text="label"></span>
        <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
    </button>

    <template x-teleport="body">
        <div x-ref="list" data-panel data-support-dropdown x-cloak x-show="open" :style="listStyle" class="panel-surface panel-border overflow-hidden rounded-xl border" role="listbox">
            <div x-data="backupOverlayScroll()" x-effect="open; options; $nextTick(() => update())" class="relative">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll overflow-y-auto overscroll-contain" :style="`max-height: ${listHeight}px;`">
                    <template x-for="option in options" :key="option.value">
                        <button type="button" role="option" :aria-selected="option.value === value" @click="const changed = value !== option.value; value = option.value; open = false; if (changed) $nextTick(() => $refs.trigger.closest('form')?.requestSubmit())" class="panel-option block w-full truncate p-[20px] text-left leading-none focus:outline-none focus:ring-0" x-text="option.label"></button>
                    </template>
                </div>
                <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </template>
</div>
