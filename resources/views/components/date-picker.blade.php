@props([
    'model',
    'inputId' => null,
    'min' => '1900-01-01',
    'max' => null,
    'placeholder' => 'Elige una fecha',
])

@php
    $pickerId = $inputId ?: 'date-picker-'.str_replace(['.', '[', ']'], '-', $model);
    $maxDate = $max ?: now()->subDay()->format('Y-m-d');
@endphp

{{--
    Calendario de la app (en lugar del calendario del navegador): mismo diseño que los demás paneles — bordes de 18px,
    20px de relleno, días en cuadrícula, el día elegido en negro y una vista de años para llegar rápido a fechas lejanas.
--}}
<div
    class="relative"
    {{ $attributes->only('style') }}
    x-data="{
        value: $wire.entangle(@js($model)),
        open: false,
        view: 'days',
        month: 0,
        year: 2000,
        min: @js($min),
        max: @js($maxDate),
        style: '',
        months: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
        iso(y, m, d) { return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`; },
        get label() {
            if (! this.value) return '';
            const [y, m, d] = String(this.value).split('-').map(Number);
            return `${d} de ${this.months[m - 1]} de ${y}`;
        },
        get title() { return this.months[this.month].charAt(0).toUpperCase() + this.months[this.month].slice(1) + ' ' + this.year; },
        get cells() {
            const first = (new Date(this.year, this.month, 1).getDay() + 6) % 7;
            const total = new Date(this.year, this.month + 1, 0).getDate();
            const today = new Date();
            const todayIso = this.iso(today.getFullYear(), today.getMonth(), today.getDate());
            const cells = Array.from({ length: first }, (_, i) => ({ key: 'b' + i, blank: true }));
            for (let d = 1; d <= total; d++) {
                const value = this.iso(this.year, this.month, d);
                cells.push({ key: value, day: d, value, blank: false, disabled: value < this.min || value > this.max, selected: value === this.value, today: value === todayIso });
            }
            return cells;
        },
        get years() {
            const top = Number(this.max.slice(0, 4)), bottom = Number(this.min.slice(0, 4));
            return Array.from({ length: top - bottom + 1 }, (_, i) => top - i);
        },
        get canPrev() { return this.iso(this.year, this.month, 1) > this.min.slice(0, 8) + '01'; },
        get canNext() { return this.iso(this.year, this.month, 1) < this.max.slice(0, 8) + '01'; },
        show() {
            const base = this.value || this.max;
            const [y, m] = String(base).split('-').map(Number);
            this.year = y; this.month = m - 1; this.view = 'days';
            this.place();
            this.open = true;
        },
        place() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const height = 400, width = Math.max(rect.width, 320);
            const below = window.innerHeight - rect.bottom - 10;
            const top = below < height && rect.top > below ? rect.top - 10 - height : rect.bottom + 10;
            const left = Math.min(rect.left, window.innerWidth - width - 10);
            this.style = `position: fixed; top: ${Math.max(10, top)}px; left: ${Math.max(10, left)}px; width: ${width}px; z-index: 200100;`;
        },
        hide() { this.open = false; },
        step(delta) {
            const d = new Date(this.year, this.month + delta, 1);
            this.year = d.getFullYear(); this.month = d.getMonth();
        },
        pick(cell) { if (cell.blank || cell.disabled) return; this.value = cell.value; this.open = false; },
        chooseYear(y) {
            this.year = y;
            if (this.iso(y, this.month, 1) > this.max) this.month = Number(this.max.slice(5, 7)) - 1;
            this.view = 'days';
        },
        openYears() {
            this.view = 'years';
            this.$nextTick(() => this.$refs.years?.querySelector('[data-current]')?.scrollIntoView({ block: 'center' }));
        },
        clear() { this.value = null; this.open = false; },
    }"
    @keydown.escape.window="hide()"
    @scroll.window.capture="open && floatingScroll($event, $root, $refs.panel, () => hide(), () => place())"
    @resize.window="hide()"
>
    <button type="button" id="{{ $pickerId }}" x-ref="trigger" @click="open ? hide() : show()" {{ $attributes->except('style')->merge(['class' => 'app-date-trigger']) }} :aria-expanded="open" aria-haspopup="dialog">
        <span x-text="label || @js($placeholder)" :class="label ? '' : 'app-date-placeholder'"></span>
        <svg class="app-date-chevron" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
    </button>

    <template x-teleport="body">
        <div x-ref="panel" x-cloak x-show="open" @click.outside="if (! $refs.trigger.contains($event.target)) hide()" :style="style" data-panel class="app-date-panel" role="dialog" aria-label="Elegir fecha">
            <div class="app-date-head">
                <button type="button" class="app-date-nav" @click="step(-1)" :disabled="view === 'years' || ! canPrev" aria-label="Mes anterior">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button type="button" class="app-date-title" @click="view === 'years' ? view = 'days' : openYears()" x-text="view === 'years' ? 'Elige el año' : title"></button>
                <button type="button" class="app-date-nav" @click="step(1)" :disabled="view === 'years' || ! canNext" aria-label="Mes siguiente">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>

            <div x-show="view === 'days'">
                <div class="app-date-week"><span>lu</span><span>ma</span><span>mi</span><span>ju</span><span>vi</span><span>sá</span><span>do</span></div>
                <div class="app-date-grid">
                    <template x-for="cell in cells" :key="cell.key">
                        <button type="button" class="app-date-day" @click="pick(cell)" :disabled="cell.blank || cell.disabled"
                            :class="{ 'is-blank': cell.blank, 'is-selected': cell.selected, 'is-today': cell.today }" x-text="cell.blank ? '' : cell.day"></button>
                    </template>
                </div>
            </div>

            <div x-show="view === 'years'" x-ref="years" class="app-date-years administration-form-scrollbar">
                <template x-for="y in years" :key="y">
                    <button type="button" class="app-date-year" @click="chooseYear(y)" :class="{ 'is-selected': y === year }" :data-current="y === year ? '' : null" x-text="y"></button>
                </template>
            </div>

            <div class="app-date-foot">
                <button type="button" class="app-date-link" @click="clear()">Borrar fecha</button>
                <button type="button" class="app-date-link" @click="hide()">Cerrar</button>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .app-date-trigger { min-height: 50px; padding: 0 20px; border: 1px solid var(--panel-border, #a1a1aa); border-radius: 18px; background: var(--panel-surface, #ffffff); display: flex !important; align-items: center; justify-content: space-between; gap: 20px; width: 100%; text-align: left; cursor: pointer; }
        .app-date-placeholder { color: color-mix(in srgb, var(--panel-text, #18181b) 58%, transparent); }
        .app-date-chevron { height: 16px; width: 16px; flex-shrink: 0; margin-right: -4px; color: #71717a; transition: transform 150ms ease; }
        html.module-dark-theme .app-date-chevron { color: #ffffff; }
        .app-date-panel { padding: 20px; border: 1px solid var(--panel-border, #a1a1aa); border-radius: 18px; background: var(--panel-surface, #ffffff); color: var(--panel-text, #18181b); box-shadow: 0 0 30px rgba(15, 23, 42, .12); font-size: 15px; }
        html.module-dark-theme .app-date-panel { box-shadow: 0 0 30px rgba(0, 0, 0, .6); }
        .app-date-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .app-date-title { flex: 1 1 auto; padding: 10px; border-radius: 12px; font-weight: 600 !important; text-align: center; transition: background-color 150ms ease; }
        .app-date-title:hover, .app-date-nav:hover:not(:disabled) { background: var(--panel-subtle, #f4f4f5); }
        .app-date-nav { display: grid; height: 40px; width: 40px; flex-shrink: 0; place-items: center; border-radius: 12px; transition: background-color 150ms ease; }
        .app-date-nav svg { height: 18px; width: 18px; }
        .app-date-nav:disabled { opacity: .3; cursor: default; }
        .app-date-week { display: grid; grid-template-columns: repeat(7, 1fr); margin-top: 20px; text-align: center; color: color-mix(in srgb, var(--panel-text, #18181b) 58%, transparent); }
        .app-date-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-top: 10px; }
        .app-date-day { height: 38px; border: 1px solid transparent; border-radius: 12px; transition: background-color 150ms ease; }
        .app-date-day:hover:not(:disabled) { background: var(--panel-subtle, #f4f4f5); }
        .app-date-day.is-today { border-color: var(--panel-border, #a1a1aa); }
        .app-date-day.is-selected, .app-date-year.is-selected { background: var(--panel-text, #18181b) !important; color: var(--panel-surface, #ffffff); }
        .app-date-day:disabled:not(.is-blank) { opacity: .3; cursor: default; }
        .app-date-day.is-blank { visibility: hidden; }
        .app-date-years { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; max-height: 258px; margin-top: 20px; overflow-y: auto; }
        .app-date-year { height: 38px; border-radius: 12px; transition: background-color 150ms ease; }
        .app-date-year:hover { background: var(--panel-subtle, #f4f4f5); }
        .app-date-foot { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--panel-border, #a1a1aa); }
        .app-date-link { color: #15803d; font-weight: 500; transition: opacity 150ms ease; }
        html.module-dark-theme .app-date-link { color: #4ade80; }
        .app-date-link:hover { opacity: .7; }
    </style>
@endonce
