@php
    $initialCatalog = array_key_first($organizationDirectory);
@endphp

<section
    x-data="{
        catalogs: @js($organizationDirectory),
        selectedCatalog: @js($initialCatalog),
        expandedItem: null,
        query: '',
        normalize(value) {
            return String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase();
        },
        selectCatalog(catalog) {
            this.selectedCatalog = catalog;
            this.expandedItem = null;
            this.query = '';
        },
        manage(action, item = null) {
            const recordId = item?.id ?? null;
            if (this.selectedCatalog === 'users') {
                this.$wire.openUserManagement(action, recordId);
            } else if (this.selectedCatalog === 'roles') {
                this.$wire.openRoleManagement(action, recordId);
            } else if (this.selectedCatalog === 'positions') {
                this.$wire.openJobPositionModal(action, recordId);
            } else if (this.selectedCatalog === 'areas') {
                this.$wire.openPhysicalAreaModal(action, recordId);
            } else if (this.selectedCatalog === 'permissions') {
                this.$wire.openPermissionsModal(action, recordId);
            } else if (this.selectedCatalog === 'customers') {
                this.$wire.openCustomerManagement(action, recordId);
            } else if (this.selectedCatalog === 'assignments') {
                this.$wire.openAssignmentModal(action, recordId);
            } else if (this.selectedCatalog === 'activities') {
                this.$wire.openActivityManagement(action, recordId);
            }
        },
        get activeCatalog() {
            return this.catalogs[this.selectedCatalog] ?? { label: '', description: '', items: [] };
        },
        get filteredItems() {
            const term = this.normalize(this.query.trim());
            if (!term) return this.activeCatalog.items;

            return this.activeCatalog.items.filter((item) => this.normalize([
                item.title,
                item.subtitle,
                ...item.details.map((detail) => `${detail.label} ${detail.value}`),
            ].join(' ')).includes(term));
        },
        initials(name) {
            const words = String(name ?? '').trim().split(/\s+/).filter(Boolean);
            return words.slice(0, 2).map((word) => word.charAt(0)).join('') || '?';
        },
    }"
    x-show="administrationView === 'directory'"
    x-cloak
    class="mt-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-none"
    aria-label="Directorio de administración"
>
    <div class="grid min-h-[630px] xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="min-h-0 overflow-hidden border-b border-zinc-200 bg-white xl:border-b-0 xl:border-r">
            <div class="flex h-[70px] shrink-0 items-center justify-center border-b border-zinc-300 bg-zinc-100 px-[20px] text-center text-zinc-700">
                <span class="truncate font-semibold">Secciones administrativas</span>
            </div>

            <nav aria-label="Catálogos organizacionales">
                @foreach ($organizationDirectory as $catalogKey => $catalog)
                    <button
                        type="button"
                        @click="selectCatalog(@js($catalogKey))"
                        :class="selectedCatalog === @js($catalogKey) ? 'bg-black text-white' : (@js($loop->odd) ? 'bg-zinc-50 text-zinc-700 hover:bg-zinc-100' : 'bg-white text-zinc-700 hover:bg-zinc-100')"
                        class="flex min-h-[70px] w-full items-center gap-[10px] border-b border-zinc-200 px-[20px] py-[12px] text-left transition-colors last:border-b-0 focus:outline-none focus:ring-0"
                        :aria-current="selectedCatalog === @js($catalogKey) ? 'page' : null"
                    >
                        <span
                            :class="selectedCatalog === @js($catalogKey) ? 'text-white' : 'text-zinc-500'"
                            class="flex h-8 w-8 shrink-0 items-center justify-center"
                        >
                            @switch($catalogKey)
                                @case('users') <x-feathericon-users class="h-[17px] w-[17px]" /> @break
                                @case('roles') <x-feathericon-lock class="h-[17px] w-[17px]" /> @break
                                @case('positions') <x-feathericon-briefcase class="h-[17px] w-[17px]" /> @break
                                @case('areas') <x-feathericon-home class="h-[17px] w-[17px]" /> @break
                                @case('permissions') <x-feathericon-shield class="h-[17px] w-[17px]" /> @break
                                @case('customers') <x-feathericon-user-check class="h-[17px] w-[17px]" /> @break
                                @case('assignments') <x-feathericon-git-merge class="h-[17px] w-[17px]" /> @break
                                @default <x-feathericon-clock class="h-[17px] w-[17px]" />
                            @endswitch
                        </span>
                        <span class="min-w-0 flex-1 truncate text-[13px] font-semibold">{{ $catalog['label'] }}</span>
                        <span
                            :class="selectedCatalog === @js($catalogKey) ? 'text-white/70' : 'text-zinc-500'"
                            class="inline-flex min-w-7 items-center justify-end tabular-nums"
                        >{{ count($catalog['items']) }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="relative flex min-h-0 min-w-0 flex-col">
            <header class="flex h-[70px] shrink-0 flex-nowrap items-center justify-between gap-[15px] border-b border-zinc-300 bg-zinc-100 px-[20px] py-[12px]">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <h2 class="font-semibold text-black" x-text="activeCatalog.label"></h2>
                        <span class="text-zinc-500" x-text="`(${filteredItems.length})`"></span>
                    </div>
                    <p class="mt-[3px] text-[12px] text-zinc-500" x-text="activeCatalog.description"></p>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-1.5" aria-label="Acciones del catálogo seleccionado">
                    <button type="button" @click="manage('crear')" class="inline-flex items-center gap-1.5 rounded-lg bg-black px-[20px] py-[15px] leading-none text-[12px] font-semibold text-white transition-colors hover:bg-zinc-800 focus:outline-none focus:ring-0" :title="`Agregar en ${activeCatalog.label}`">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" /></svg>
                        <span>Agregar</span>
                    </button>
                </div>

                <label class="relative block w-full max-w-[280px]">
                    <span class="sr-only">Buscar en el catálogo seleccionado</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                    <input
                        type="search"
                        x-model.debounce.120ms="query"
                        class="h-[44px] w-full rounded-xl border border-zinc-200 bg-white pl-10 pr-4 text-[13px] text-black shadow-none outline-none placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0"
                        style="border-color: #e4e4e7 !important; outline: none !important; box-shadow: none !important; --tw-ring-color: transparent !important; --tw-ring-offset-shadow: 0 0 #0000 !important; --tw-ring-shadow: 0 0 #0000 !important;"
                        :placeholder="`Buscar en ${activeCatalog.label.toLocaleLowerCase()}...`"
                        autocomplete="off"
                    >
                </label>
            </header>

            <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="relative h-[560px] min-h-0">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-record-scroll h-full overflow-y-auto">
                <template x-for="item in filteredItems" :key="`${selectedCatalog}-${item.id}`">
                    <article
                        :class="expandedItem === item.id ? 'bg-white' : 'bg-white hover:bg-zinc-50'"
                        class="overflow-hidden border-b border-zinc-200 transition-colors last:border-b-0"
                    >
                        <div class="flex min-h-[70px] min-w-0 items-center gap-[15px] px-[20px] py-[12px]">
                            <button
                                type="button"
                                @click="expandedItem = expandedItem === item.id ? null : item.id"
                                class="flex min-w-0 flex-1 items-center gap-3 text-left focus:outline-none focus:ring-0"
                                :aria-expanded="expandedItem === item.id"
                            >
                                <span
                                    :class="expandedItem === item.id ? 'bg-black text-white' : 'border border-zinc-200 bg-white text-zinc-700'"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[11px] font-bold transition-colors"
                                    x-text="initials(item.title)"
                                ></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-bold text-black" x-text="item.title" :title="item.title"></span>
                                    <span class="mt-[5px] block truncate text-[11px] text-zinc-500" x-text="item.subtitle" :title="item.subtitle"></span>
                                </span>
                            </button>

                            <div class="flex shrink-0 items-center gap-1.5 border-l border-zinc-200 pl-3">
                                <button
                                    type="button"
                                    @click.stop="manage('editar', item)"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0"
                                    :aria-label="`Editar ${item.title}`"
                                    :title="`Editar ${item.title}`"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="manage('eliminar', item)"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-0"
                                    :aria-label="`Eliminar ${item.title}`"
                                    :title="`Eliminar ${item.title}`"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                                </button>
                                <button
                                    type="button"
                                    @click="expandedItem = expandedItem === item.id ? null : item.id"
                                    class="ml-1 flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0"
                                    :aria-label="expandedItem === item.id ? `Ocultar datos de ${item.title}` : `Mostrar datos de ${item.title}`"
                                >
                                    <svg :class="expandedItem === item.id ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                                </button>
                            </div>
                        </div>

                        <div
                            x-show="expandedItem === item.id"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-zinc-200 bg-white"
                        >
                            <dl class="grid min-w-0 grid-cols-2 xl:grid-cols-4">
                                <template x-for="detail in item.details" :key="detail.label">
                                    <div class="flex min-h-[78px] min-w-0 flex-col justify-center border-r border-zinc-200 px-[20px] py-[15px] last:border-r-0">
                                        <dt class="truncate font-medium text-black" x-text="detail.label"></dt>
                                        <dd class="mt-[3px] truncate text-zinc-500" x-text="detail.value" :title="detail.value"></dd>
                                    </div>
                                </template>
                            </dl>
                        </div>
                    </article>
                </template>

                <div x-show="filteredItems.length === 0" class="flex min-h-[420px] flex-col items-center justify-center text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                    </span>
                    <p class="mt-3 text-[13px] font-semibold text-black">No encontramos registros</p>
                    <p class="mt-[5px] text-[11px] text-zinc-500">Prueba con otro nombre, correo o dato.</p>
                </div>
                </div>
                <span x-show="visible" x-cloak class="administration-record-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </div>
</section>
