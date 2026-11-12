@props([
    'property' => null,
    'selected' => null,
    'options' => [],
    'id' => null,
    'label' => '',
    'icon' => null,
    'searchable' => false,
    'placeholder' => 'Seleccione…',
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
        q: '',
        value: {!! $initial !!},
        options: @js($list),
        idHtml(value) { return 'ID ' + String(value).padStart(5, '0').replace(/[0-9]/g, (digit) => `<span style='display:inline-block;width:0.64em;text-align:center'>${digit}</span>`); },
        initials(text) { return String(text || '').trim().split(/\s+/).slice(0, 2).map(word => word.charAt(0)).join('').toUpperCase(); },
        norm(text) { return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
        get shown() {
            const tokens = this.norm(this.q).split(/\s+/).filter(Boolean);
            return tokens.length ? this.options.filter(option => { const label = this.norm(option.label); return tokens.every(token => label.includes(token)); }) : this.options;
        },
        active: 0,
        openList() {
            if (this.open) return;
            this.open = true;
            this.q = '';
            this.active = 0;
            this.$nextTick(() => {
                const scroller = this.$root.querySelector('.panel-scroll');
                const selected = scroller?.querySelector('[aria-selected=true]');
                // La lista de 5 filas exactas se abre alineada a la fila elegida (sin cortar ninguna).
                if (scroller && selected) scroller.scrollTop = selected.offsetTop;
            });
        },
        move(delta) {
            this.open = true;
            this.active = Math.max(0, Math.min(this.shown.length - 1, this.active + delta));
            this.$nextTick(() => this.$root.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }));
        },
        pickActive() { const option = this.shown[this.active]; if (option) this.pick(option); },
        get current() { return this.options.find(option => String(option.value) === String(this.value)); },
        toggle() {
            this.open = ! this.open;
            if (! this.open) return;
            this.q = '';
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
    @if ($searchable)
        <div class="panel-field flex w-full items-center gap-[10px] rounded-xl border px-[20px] py-[15px]">
            @if ($icon)
                <svg class="panel-accent h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}" /></svg>
            @endif
            <input type="text" x-ref="field" autocomplete="off" role="combobox" aria-haspopup="listbox" :aria-expanded="open" @if ($id) id="{{ $id }}" @endif @if ($label) aria-label="{{ $label }}" @endif
                :placeholder="current ? current.label : @js($placeholder)" :value="open ? q : (current ? current.label : '')"
                @focus="openList()" @click="openList()" @input="q = $event.target.value; active = 0; open = true"
                @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="pickActive()"
                class="panel-text min-w-0 flex-1 truncate leading-none" style="border:0 !important; box-shadow:none !important; outline:none !important; padding:0 !important; margin:0; background:transparent !important; min-height:0; height:auto; color:inherit; --tw-ring-shadow:0 0 #0000;">
            <button type="button" tabindex="-1" @click="open ? open = false : $refs.field.focus()" aria-label="Mostrar opciones" class="shrink-0 leading-none focus:outline-none focus:ring-0">
                <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
            </button>
        </div>
    @else
        <button type="button" @if ($id) id="{{ $id }}" @endif @click="toggle()" aria-haspopup="listbox" :aria-expanded="open" @if ($label) aria-label="{{ $label }}" @endif
        class="panel-field flex w-full items-center gap-[10px] rounded-xl border px-[20px] py-[15px] text-left leading-none focus:outline-none focus:ring-0">
        @if ($icon)
            <svg class="panel-accent h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}" /></svg>
        @endif
        <span class="min-w-0 flex-1 truncate" x-text="current ? current.label : ''"></span>
        <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
    </button>
    @endif

    <div x-cloak x-show="open" class="panel-surface panel-border absolute left-0 top-full z-40 mt-[10px] w-full overflow-hidden rounded-xl border" role="listbox">
        <div x-data="backupOverlayScroll()" x-effect="open; q; $nextTick(() => update())" class="relative">
            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" @scroll.debounce.150ms="(() => { if (! $el.hasAttribute('data-row-step')) return; const rows = [...$el.querySelectorAll('.panel-option')]; if (! rows.length) return; const height = rows[0].offsetHeight; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const target = Math.min(max, Math.round($el.scrollTop / height) * height); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" :data-row-step="@js($searchable ? '.panel-option' : null)" :style="@js($searchable ? 'height: 400px;' : 'max-height: 275px;')" class="panel-scroll relative overflow-y-auto overscroll-contain">
                <template x-for="(option, index) in shown" :key="option.value">
                    @if ($searchable)
                    <button type="button" role="option" :aria-selected="String(option.value) === String(value)" :data-active="active === index" :class="{ 'is-active': active === index }" @mouseenter="active = index" @click="pick(option)"
                        class="panel-option flex h-[80px] w-full items-center gap-[15px] px-[20px] text-left leading-none focus:outline-none focus:ring-0">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate" x-text="option.label"></span>
                            <span class="mt-[15px] block truncate" x-html="idHtml(option.value)"></span>
                        </span>
                    </button>
                    @else
                    <button type="button" role="option" :aria-selected="String(option.value) === String(value)" :data-active="active === index" :class="{ 'is-active': active === index }" @mouseenter="active = index" @click="pick(option)"
                        class="panel-option block w-full truncate p-[20px] text-left leading-none focus:outline-none focus:ring-0" x-text="option.label"></button>
                    @endif
                </template>
                <p x-show="shown.length === 0" x-cloak class="p-[20px] leading-none">Sin resultados</p>
            </div>
            <span x-show="visible" x-cloak class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
        </div>
    </div>
</div>
