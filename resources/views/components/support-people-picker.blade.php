{{-- Selector múltiple de a quién se atendió (colaboradores con su departamento y clientes externos).
     Se usa dentro del x-for de servicios: lee `directory`, `quantities` y `targets` del componente
     padre y escribe targets[item.key] (un nombre por línea, el formato que recibe el servidor).
     El máximo de personas es la cantidad indicada en el servicio. --}}
<div
    x-data="{
        open: false,
        openedAt: 0,
        q: '',
        tab: 'user',
        listStyle: '',
        listHeight: 0,
        normalize(value) {
            return String(value ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleLowerCase('es');
        },
        get list() {
            return String(targets[item.key] ?? '').split(/\r\n|\r|\n/).map(value => value.trim()).filter(Boolean);
        },
        get max() {
            return maxPeople(item.key);
        },
        get full() {
            return this.list.length >= this.max;
        },
        get over() {
            return this.list.length > this.max;
        },
        get rows() {
            const known = new Set(directory.map(person => person.norm));
            const extra = this.list
                .filter(name => ! known.has(this.normalize(name)))
                .map(name => ({ name: name, group: '', type: 'client', norm: this.normalize(name) }));
            return [...directory, ...extra];
        },
        get filtered() {
            const term = this.normalize(this.q).trim();
            const rows = this.rows.filter(person => (this.tab === 'user') === (person.type === 'user'));
            return term === '' ? rows : rows.filter(person => person.norm.includes(term));
        },
        get shown() {
            return this.filtered.slice(0, 100);
        },
        get canAdd() {
            const term = this.normalize(this.q).trim();
            return this.tab === 'client' && term !== '' && ! this.rows.some(person => person.norm === term);
        },
        countOf(type) {
            const chosen = new Set(this.list);
            return this.rows.filter(person => person.type === type && chosen.has(person.name)).length;
        },
        has(name) {
            return this.list.includes(name);
        },
        toggle(name) {
            const current = this.list;
            if (current.includes(name)) {
                targets[item.key] = current.filter(value => value !== name).join('\n');
            } else if (current.length < this.max) {
                targets[item.key] = [...current, name].join('\n');
            }
        },
        add() {
            const name = this.q.trim();
            if (name === '' || this.full) return;
            if (! this.list.includes(name)) targets[item.key] = [...this.list, name].join('\n');
            this.q = '';
        },
        enter() {
            if (this.canAdd) { this.add(); return; }
            if (this.shown.length === 1) this.toggle(this.shown[0].name);
        },
        setTab(value) {
            this.tab = value;
            this.q = '';
            this.$nextTick(() => { if (this.open) this.position(); });
        },
        position() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const k = (typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100 || 1;
            const gap = 10 * k;
            const chrome = 62;
            const rows = Math.max(1, Math.min(this.shown.length + (this.canAdd ? 1 : 0), 5));
            const wanted = (chrome + rows * 56) * k;
            const below = window.innerHeight - rect.bottom - gap - 8;
            const above = rect.top - gap - 8;
            const up = below < Math.min(wanted, 290 * k) && above > below;
            const height = Math.min(wanted, up ? above : below);
            const top = up ? rect.top - gap - height : rect.bottom + gap;
            const width = Math.min(Math.max(rect.width, 400 * k), window.innerWidth - 16);
            const left = Math.min(rect.left, window.innerWidth - width - 8);
            this.listHeight = Math.max(56, height / k - chrome);
            this.listStyle = `position: fixed; left: ${left}px; top: ${top}px; width: ${width / k}px; z-index: 200100; transform: scale(${k}); transform-origin: top left;`;
        },
        show() {
            if (this.open) { this.open = false; return; }
            this.q = '';
            this.position();
            this.openedAt = Date.now();
            this.open = true;
            this.$nextTick(() => this.$refs.search?.focus({ preventScroll: true }));
        }
    }"
    @click.outside="if (! ($refs.list && $refs.list.contains($event.target))) open = false"
    @keydown.escape.window="open = false"
    @scroll.window.capture="if (open && Date.now() - openedAt > 250 && ! ($refs.list && $refs.list.contains($event.target))) open = false"
    @resize.window="if (open) position()"
    {{ $attributes->class(['relative min-w-0']) }}
>
    <button x-ref="trigger" type="button" @click="show()" :disabled="! selected.includes(item.key)" :style="over ? 'border-color: #dc2626;' : ''" class="flex h-[44px] w-full items-center justify-between gap-[10px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] text-left font-normal leading-none text-black outline-none focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40" aria-haspopup="listbox" :aria-expanded="open">
        <span class="min-w-0 flex-1 truncate" :class="list.length === 0 ? 'text-zinc-500' : 'text-black'" x-text="list.length === 0 ? 'Elegir personas o clientes...' : list.join(', ')"></span>
        <svg class="h-4 w-4 shrink-0 text-black transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
    </button>

    <template x-teleport="body">
        <div x-ref="list" data-support-modal x-cloak x-show="open" :style="listStyle" class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white text-[15px] text-black" role="listbox" aria-multiselectable="true">
            <div class="flex items-center border-b border-[#d4d4d8] bg-white" style="height: 62px;">
                <input x-ref="search" x-model="q" @input="$nextTick(() => { if (open) position(); })" type="search" autocomplete="off" :placeholder="tab === 'user' ? 'Buscar colaborador...' : 'Buscar o agregar...'" @keydown.enter.prevent="enter()" class="block h-full min-w-0 flex-1 border-0 bg-white px-[20px] font-normal text-black outline-none placeholder:text-zinc-500 focus:outline-none focus:ring-0" style="box-shadow: none !important; outline: none !important;">
                <div class="flex shrink-0 items-center gap-[20px] pr-[20px]">
                    <button type="button" @click="setTab('user')" class="text-left font-normal leading-none focus:outline-none focus:ring-0" :class="tab === 'user' ? 'text-[#1A3A6B]' : 'text-black'" x-text="countOf('user') > 0 ? `Colaboradores (${countOf('user')})` : 'Colaboradores'"></button>
                    <button type="button" @click="setTab('client')" class="text-left font-normal leading-none focus:outline-none focus:ring-0" :class="tab === 'client' ? 'text-[#1A3A6B]' : 'text-black'" x-text="countOf('client') > 0 ? `Clientes (${countOf('client')})` : 'Clientes'"></button>
                </div>
            </div>

            <div x-data="backupOverlayScroll()" x-effect="open; shown; canAdd; tab; $nextTick(() => update())" class="relative" style="margin-bottom: -1px;">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll overflow-y-auto overscroll-contain" :style="`max-height: ${listHeight}px;`">
                    <template x-for="person in shown" :key="person.name">
                        <button type="button" role="option" :aria-selected="has(person.name)" :disabled="full && ! has(person.name)" @click="toggle(person.name)" class="flex w-full items-center gap-[20px] border-b border-[#d4d4d8] bg-white p-[20px] text-left font-normal leading-none hover:bg-zinc-50 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white">
                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border border-[#d4d4d8]" :style="has(person.name) ? 'background: #000000; border-color: #000000; color: #ffffff;' : 'background: #ffffff;'">
                                <svg x-show="has(person.name)" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7" /></svg>
                            </span>
                            <span class="min-w-0 flex-1 truncate" :class="has(person.name) ? 'text-[#1A3A6B]' : 'text-black'" x-text="person.name"></span>
                            <span x-show="person.group" x-cloak class="max-w-[45%] shrink-0 truncate leading-none text-black" x-text="person.group"></span>
                        </button>
                    </template>
                    <button x-show="canAdd" x-cloak type="button" :disabled="full" @click="add()" class="flex w-full items-center gap-[10px] border-b border-[#d4d4d8] bg-white p-[20px] text-left font-normal leading-none text-[#1A3A6B] hover:bg-zinc-50 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" /></svg>
                        <span class="min-w-0 flex-1 truncate" x-text="`Agregar cliente «${q.trim()}»`"></span>
                    </button>
                    <p x-show="filtered.length > 100" x-cloak class="border-b border-[#d4d4d8] p-[20px] leading-none text-black">Escribe para acotar los resultados.</p>
                    <p x-show="filtered.length === 0 && ! canAdd" x-cloak class="border-b border-[#d4d4d8] p-[20px] leading-none text-black" x-text="tab === 'client' ? 'Escribe el nombre del cliente para agregarlo.' : 'No hay resultados.'"></p>
                </div>
                <span x-show="visible" x-cloak class="support-service-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </template>
</div>
