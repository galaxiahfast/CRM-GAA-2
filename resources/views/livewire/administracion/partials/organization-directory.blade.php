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
        manage(action, item) {
            if (this.selectedCatalog === 'users') {
                this.$wire.openUserManagement(action, item.id);
            } else if (this.selectedCatalog === 'roles') {
                this.$wire.openRoleManagement(action, item.id);
            } else if (this.selectedCatalog === 'positions') {
                this.$wire.openJobPositionModal(action, item.id);
            } else if (this.selectedCatalog === 'areas') {
                this.$wire.openPhysicalAreaModal(action, item.id);
            } else if (this.selectedCatalog === 'permissions') {
                Livewire.dispatch('open-permission-catalog-from-directory', { tab: action, recordId: item.id });
            } else if (this.selectedCatalog === 'customers') {
                Livewire.dispatch('open-customer-catalog-from-directory', { tab: action, recordId: item.id });
            } else if (this.selectedCatalog === 'assignments') {
                Livewire.dispatch('open-assignment-catalog-from-directory', { mode: action, recordId: item.id });
            } else if (this.selectedCatalog === 'activities') {
                Livewire.dispatch('open-activity-catalog-from-directory', { tab: action, recordId: item.id });
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
    class="mt-[20px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.05)]"
    aria-label="Directorio de administración"
>
    <div class="grid min-h-[620px] grid-cols-[230px_minmax(0,1fr)]">
        <aside class="border-r border-zinc-200 bg-zinc-50 p-[20px]">
            <div class="mb-4 px-3 pt-2">
                <span class="text-[10px] font-bold uppercase tracking-[.16em] text-zinc-700">Catálogos</span>
                <p class="mt-[5px] text-[12px] leading-5 text-zinc-500">Selecciona una sección para consultar sus registros.</p>
            </div>

            <nav class="space-y-1.5" aria-label="Catálogos organizacionales">
                @foreach ($organizationDirectory as $catalogKey => $catalog)
                    <button
                        type="button"
                        @click="selectCatalog(@js($catalogKey))"
                        :class="selectedCatalog === @js($catalogKey) ? 'bg-black text-white' : 'text-zinc-700 hover:bg-white hover:text-black'"
                        class="flex w-full items-center gap-[10px] rounded-lg px-[15px] py-[15px] text-left transition-colors focus:outline-none focus:ring-0"
                        :aria-current="selectedCatalog === @js($catalogKey) ? 'page' : null"
                    >
                        <span
                            :class="selectedCatalog === @js($catalogKey) ? 'border-white/20 bg-white/10' : 'border-zinc-200 bg-white'"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border"
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
                            :class="selectedCatalog === @js($catalogKey) ? 'bg-white/15 text-white' : 'bg-zinc-200 text-zinc-700'"
                            class="inline-flex min-w-7 items-center justify-center rounded-full px-2 py-1 text-[10px] font-bold"
                        >{{ count($catalog['items']) }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 p-[20px]">
            <header class="flex flex-wrap items-start justify-between gap-[20px] border-b border-zinc-200 pb-[20px]">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-black" x-text="activeCatalog.label"></h2>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-[10px] font-bold text-zinc-700" x-text="`${filteredItems.length} registros`"></span>
                    </div>
                    <p class="mt-[5px] text-[12px] text-zinc-500" x-text="activeCatalog.description"></p>
                </div>

                <label class="relative block w-full max-w-[330px]">
                    <span class="sr-only">Buscar en el catálogo seleccionado</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                    <input
                        type="search"
                        x-model.debounce.120ms="query"
                        class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-4 text-[13px] text-black shadow-none outline-none placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0"
                        :placeholder="`Buscar en ${activeCatalog.label.toLocaleLowerCase()}...`"
                        autocomplete="off"
                    >
                </label>
            </header>

            <div class="unassigned-users-scrollbar mt-4 max-h-[535px] space-y-2 overflow-y-auto pr-2">
                <template x-for="item in filteredItems" :key="`${selectedCatalog}-${item.id}`">
                    <article
                        :class="expandedItem === item.id ? 'border-zinc-300 bg-white shadow-[0_8px_24px_rgba(0,0,0,.05)]' : 'border-zinc-200 bg-white hover:border-zinc-300'"
                        class="overflow-hidden rounded-lg border transition-colors"
                    >
                        <div class="flex min-w-0 items-center gap-3 px-4 py-3">
                            <button
                                type="button"
                                @click="expandedItem = expandedItem === item.id ? null : item.id"
                                class="flex min-w-0 flex-1 items-center gap-3 text-left focus:outline-none focus:ring-0"
                                :aria-expanded="expandedItem === item.id"
                            >
                                <span
                                    :class="expandedItem === item.id ? 'bg-black text-white' : 'bg-zinc-100 text-zinc-700'"
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
                            class="border-t border-zinc-200 bg-zinc-50 px-4 py-4"
                        >
                            <dl class="grid min-w-0 grid-cols-2 gap-3 xl:grid-cols-4">
                                <template x-for="detail in item.details" :key="detail.label">
                                    <div class="min-w-0 rounded-lg border border-zinc-200 bg-white px-3 py-2.5">
                                        <dt class="truncate text-[9px] font-bold uppercase tracking-[.1em] text-zinc-500" x-text="detail.label"></dt>
                                        <dd class="mt-[5px] break-words text-[11px] font-semibold leading-5 text-zinc-700" x-text="detail.value"></dd>
                                    </div>
                                </template>
                            </dl>
                        </div>
                    </article>
                </template>

                <div x-show="filteredItems.length === 0" class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                    </span>
                    <p class="mt-3 text-[13px] font-semibold text-black">No encontramos registros</p>
                    <p class="mt-[5px] text-[11px] text-zinc-500">Prueba con otro nombre, correo o dato.</p>
                </div>
            </div>
        </div>
    </div>
</section>
