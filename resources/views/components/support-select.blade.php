@props([
    'name',
    'value' => '',
    'options' => [],
    'allLabel' => 'Todos',
])

@php
    $list = collect([['value' => '', 'label' => $allLabel]])
        ->concat(collect($options)->map(fn ($option) => is_array($option) ? ['value' => (string) $option['value'], 'label' => (string) $option['label']] : ['value' => (string) $option, 'label' => (string) $option]))
        ->values()
        ->all();
@endphp

{{-- Menú desplegable con el diseño de Administración; envía su valor en un campo oculto. --}}
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
            const k = (typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100 || 1;
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

    <button x-ref="trigger" type="button" @click="toggle()" class="flex h-[44px] w-full items-center justify-between gap-[10px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] text-left font-normal leading-none text-black outline-none focus:outline-none focus:ring-0" aria-haspopup="listbox" :aria-expanded="open">
        <span class="min-w-0 flex-1 truncate" x-text="label"></span>
        <svg class="h-4 w-4 shrink-0 text-black transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
    </button>

    <template x-teleport="body">
        <div x-ref="list" data-support-dropdown x-cloak x-show="open" :style="listStyle" class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white" role="listbox">
            <div x-data="backupOverlayScroll()" x-effect="open; options; $nextTick(() => update())" class="relative">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll overflow-y-auto overscroll-contain" :style="`max-height: ${listHeight}px; scrollbar-width: none;`">
                    <template x-for="option in options" :key="option.value">
                        <button type="button" role="option" @click="const changed = value !== option.value; value = option.value; open = false; if (changed) $nextTick(() => $refs.trigger.closest('form')?.requestSubmit())" :class="option.value === value ? 'text-[#1A3A6B]' : 'text-black'" class="block w-full truncate bg-white p-[20px] text-left text-[15px] font-normal leading-none hover:bg-zinc-50 focus:outline-none focus:ring-0" x-text="option.label"></button>
                    </template>
                </div>
                <span x-show="visible" x-cloak class="support-service-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </template>
</div>
