@php
    $initialCatalog = array_key_first($organizationDirectory);
@endphp

<section
    wire:key="administration-organization-directory"
    x-data="{
        catalogs: @js($organizationDirectory),
        selectedCatalog: sessionStorage.getItem('administration-directory-catalog') || @js($initialCatalog),
        expandedItem: null,
        query: '',
        roleUserQuery: '',
        assignedRoleUserIds: [],
        availableRoleUserIds: [],
        destinationRoleId: null,
        roleAssignmentBusy: false,
        roleAssignmentStatus: '',
        roleAssignmentError: false,
        currentUserId: @js((int) auth()->id()),
        init() {
            if (!this.catalogs[this.selectedCatalog]) {
                this.selectedCatalog = @js($initialCatalog);
            }
            sessionStorage.setItem('administration-directory-catalog', this.selectedCatalog);
        },
        normalize(value) {
            return String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase();
        },
        selectCatalog(catalog) {
            this.selectedCatalog = catalog;
            sessionStorage.setItem('administration-directory-catalog', catalog);
            this.expandedItem = null;
            this.query = '';
            this.resetRoleAssignment();
        },
        toggleItem(item) {
            if (this.selectedCatalog === 'users') {
                this.expandedItem = null;
                return;
            }
            this.expandedItem = this.expandedItem === item.id ? null : item.id;
            this.resetRoleAssignment();
        },
        resetRoleAssignment() {
            this.roleUserQuery = '';
            this.assignedRoleUserIds = [];
            this.availableRoleUserIds = [];
            this.destinationRoleId = null;
            this.roleAssignmentStatus = '';
            this.roleAssignmentError = false;
        },
        filteredRoleUsers(users) {
            const term = this.normalize(this.roleUserQuery.trim());
            if (!term) return users ?? [];
            return (users ?? []).filter((user) => this.normalize(`${user.title} ${user.email} ${user.current_role}`).includes(term));
        },
        otherRoles(item) {
            return (this.catalogs.roles?.items ?? []).filter((role) => role.id !== item.id);
        },
        async assignRoleUsers(item) {
            if (this.availableRoleUserIds.length === 0) {
                this.roleAssignmentError = true;
                this.roleAssignmentStatus = 'Selecciona al menos un usuario disponible.';
                return;
            }

            this.roleAssignmentBusy = true;
            this.roleAssignmentStatus = '';
            try {
                const catalog = await this.$wire.assignDirectoryRoleUsers(item.id, this.availableRoleUserIds);
                this.catalogs = { ...this.catalogs, roles: catalog };
                this.availableRoleUserIds = [];
                this.roleAssignmentError = false;
                this.roleAssignmentStatus = 'Usuarios asignados correctamente.';
                this.expandedItem = item.id;
            } catch (error) {
                this.roleAssignmentError = true;
                this.roleAssignmentStatus = 'No se pudieron asignar los usuarios seleccionados.';
            } finally {
                this.roleAssignmentBusy = false;
            }
        },
        async moveRoleUsers(item) {
            if (this.assignedRoleUserIds.length === 0 || !this.destinationRoleId) {
                this.roleAssignmentError = true;
                this.roleAssignmentStatus = 'Selecciona usuarios y el rol al que se moverán.';
                return;
            }

            this.roleAssignmentBusy = true;
            this.roleAssignmentStatus = '';
            try {
                const catalog = await this.$wire.moveDirectoryRoleUsers(item.id, this.assignedRoleUserIds, Number(this.destinationRoleId));
                this.catalogs = { ...this.catalogs, roles: catalog };
                this.assignedRoleUserIds = [];
                this.destinationRoleId = null;
                this.roleAssignmentError = false;
                this.roleAssignmentStatus = 'Usuarios cambiados de rol correctamente.';
                this.expandedItem = item.id;
            } catch (error) {
                this.roleAssignmentError = true;
                this.roleAssignmentStatus = 'No se pudieron cambiar los usuarios de rol.';
            } finally {
                this.roleAssignmentBusy = false;
            }
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
    class="mt-[20px] overflow-hidden rounded-xl border border-zinc-300 bg-white shadow-none"
    aria-label="Directorio de administración"
>
    <div class="grid min-h-[630px] xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="min-h-0 overflow-hidden border-b border-zinc-300 bg-white xl:border-b-0 xl:border-r">
            <div class="flex shrink-0 items-center justify-center border-b border-zinc-300 bg-zinc-100 p-[20px] text-center text-[#1A3A6B]" style="height: 86px;">
                <span class="truncate font-normal leading-none">Secciones administrativas</span>
            </div>

            <nav aria-label="Catálogos organizacionales">
                @foreach ($organizationDirectory as $catalogKey => $catalog)
                    <button
                        type="button"
                        @click="selectCatalog(@js($catalogKey))"
                        :class="selectedCatalog === @js($catalogKey) ? 'bg-white text-[#1A3A6B]' : 'bg-white text-black'"
                        class="flex w-full items-center gap-[10px] border-b border-zinc-300 px-[20px] text-left transition-colors last:border-b-0 focus:outline-none focus:ring-0"
                        style="height: 76px;"
                        :aria-current="selectedCatalog === @js($catalogKey) ? 'page' : null"
                    >
                        <span
                            :class="selectedCatalog === @js($catalogKey) ? 'text-[#1A3A6B]' : 'text-black'"
                            class="flex h-[17px] w-[17px] shrink-0 items-center justify-center"
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
                        <span class="min-w-0 flex-1 truncate text-[13px] font-normal leading-none">{{ $catalog['label'] }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="relative flex min-h-0 min-w-0 flex-col">
            <header class="flex shrink-0 flex-nowrap items-center justify-between gap-[20px] border-b border-zinc-300 bg-zinc-100 p-[20px]" style="height: 86px;">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <h2 class="font-normal leading-none text-[#1A3A6B]" x-text="activeCatalog.label"></h2>
                        <span class="leading-none text-[#1A3A6B]" x-text="`(${filteredItems.length})`"></span>
                    </div>
                    <p class="mt-[10px] text-[12px] leading-none text-black" x-text="activeCatalog.description"></p>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-[20px]" aria-label="Acciones del catálogo seleccionado">
                    <button type="button" @click="manage('crear')" class="inline-flex items-center gap-[10px] bg-transparent px-[20px] py-[15px] leading-none text-[12px] font-normal text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0" :title="`Agregar en ${activeCatalog.label}`">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" /></svg>
                        <span x-text="'Agregar ' + ({ users: 'usuario', roles: 'rol', positions: 'puesto', areas: 'área', permissions: 'grupo de permisos', customers: 'cliente', assignments: 'asignación', activities: 'actividad' }[selectedCatalog] ?? '')"></span>
                    </button>
                </div>

                <label class="relative block w-full max-w-[280px]">
                    <span class="sr-only">Buscar en el catálogo seleccionado</span>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                    <input
                        type="search"
                        x-model.debounce.120ms="query"
                        class="h-[44px] w-full rounded-xl border border-zinc-300 bg-white pl-10 pr-4 text-[13px] text-black shadow-none outline-none placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0"
                        style="border-color: #e4e4e7 !important; outline: none !important; box-shadow: none !important; --tw-ring-color: transparent !important; --tw-ring-offset-shadow: 0 0 #0000 !important; --tw-ring-shadow: 0 0 #0000 !important;"
                        :placeholder="`Buscar en ${activeCatalog.label.toLocaleLowerCase()}...`"
                        autocomplete="off"
                    >
                </label>
            </header>

            <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="relative min-h-[560px] flex-1">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-record-scroll absolute inset-x-0 top-0 overflow-y-auto" style="bottom: -1px;">
                <template x-for="item in filteredItems" :key="`${selectedCatalog}-${item.id}`">
                    <article
                        :class="'bg-white hover:bg-zinc-50'"
                        class="overflow-hidden border-b border-[#d4d4d8] transition-colors last:border-b-0"
                    >
                        {{-- El artículo aporta el borde inferior de 1 px; 75 + 1 mantiene
                             cada renglón alineado con los botones laterales de 76 px. --}}
                        <div class="flex min-w-0 items-center gap-[20px] p-[20px]" style="height: 75px;">
                            <div class="flex min-w-0 flex-1 items-center gap-[10px] text-left">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-zinc-300 bg-white text-[11px] font-normal text-black"
                                    x-text="initials(item.title)"
                                ></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-normal leading-none text-[#1A3A6B]" x-text="item.title" :title="item.title"></span>
                                    <span class="mt-[10px] block truncate text-[11px] leading-none text-black" x-text="item.subtitle" :title="item.subtitle"></span>
                                </span>
                            </div>

                            <div class="flex shrink-0 items-center gap-[20px] self-stretch border-l border-[#d4d4d8] pl-[20px]">
                                <button
                                    type="button"
                                    @click.stop="manage('editar', item)"
                                    class="flex h-4 w-4 items-center justify-center text-black transition-colors hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                    :aria-label="`Editar ${item.title}`"
                                    :title="`Editar ${item.title}`"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="manage('eliminar', item)"
                                    class="flex h-4 w-4 items-center justify-center text-black transition-colors hover:text-red-600 focus:outline-none focus:ring-0"
                                    :aria-label="`Eliminar ${item.title}`"
                                    :title="`Eliminar ${item.title}`"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                                </button>
                            </div>
                        </div>
                    </article>
                </template>

                <div x-show="filteredItems.length === 0" class="flex min-h-[420px] flex-col items-center justify-center text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                    </span>
                    <p class="mt-[20px] text-[13px] font-normal leading-none text-[#1A3A6B]">No encontramos registros</p>
                    <p class="mt-[10px] text-[11px] leading-none text-black">Prueba con otro nombre, correo o dato.</p>
                </div>
                </div>
                <span x-show="visible" x-cloak class="administration-record-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </div>
</section>
