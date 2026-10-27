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
                        :class="expandedItem === item.id ? 'bg-white' : 'bg-white hover:bg-zinc-50'"
                        class="overflow-hidden border-b border-zinc-300 transition-colors last:border-b-0"
                    >
                        <div class="flex min-w-0 items-center gap-[20px] p-[20px]" style="height: 76px;">
                            <button
                                type="button"
                                @click="toggleItem(item)"
                                :class="selectedCatalog === 'users' ? 'cursor-default' : 'cursor-pointer'"
                                class="flex min-w-0 flex-1 items-center gap-[10px] text-left focus:outline-none focus:ring-0"
                                :aria-expanded="selectedCatalog === 'users' ? null : expandedItem === item.id"
                            >
                                <span
                                    :class="expandedItem === item.id ? 'bg-black text-white' : 'border border-zinc-300 bg-white text-black'"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[11px] font-normal transition-colors"
                                    x-text="initials(item.title)"
                                ></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-normal leading-none text-[#1A3A6B]" x-text="item.title" :title="item.title"></span>
                                    <span class="mt-[10px] block truncate text-[11px] leading-none text-black" x-text="item.subtitle" :title="item.subtitle"></span>
                                </span>
                            </button>

                            <div class="flex shrink-0 items-center gap-[20px] self-stretch border-l border-zinc-300 pl-[20px]">
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
                                <button
                                    x-show="selectedCatalog !== 'users'"
                                    x-cloak
                                    type="button"
                                    @click="toggleItem(item)"
                                    class="flex h-4 w-4 items-center justify-center text-black transition-colors hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                    :aria-label="expandedItem === item.id ? `Ocultar datos de ${item.title}` : `Mostrar datos de ${item.title}`"
                                >
                                    <svg :class="expandedItem === item.id ? 'rotate-180' : ''" class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                                </button>
                            </div>
                        </div>

                        <div
                            x-show="selectedCatalog !== 'users' && expandedItem === item.id"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-zinc-300 bg-white"
                        >
                            <template x-if="selectedCatalog !== 'roles'">
                                <dl class="grid min-w-0 grid-cols-2 xl:grid-cols-4">
                                    <template x-for="detail in item.details" :key="detail.label">
                                        <div class="flex min-h-[78px] min-w-0 flex-col justify-center border-r border-zinc-300 px-[20px] py-[15px] last:border-r-0">
                                            <dt class="truncate font-medium text-black" x-text="detail.label"></dt>
                                            <dd class="mt-[3px] truncate text-zinc-500" x-text="detail.value" :title="detail.value"></dd>
                                        </div>
                                    </template>
                                </dl>
                            </template>

                            <template x-if="selectedCatalog === 'roles'">
                                <div class="p-[20px]">
                                    <label class="relative block w-full">
                                        <span class="sr-only">Buscar usuarios del rol</span>
                                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                                        <input type="search" x-model.debounce.120ms="roleUserQuery" class="h-[44px] w-full rounded-xl border border-zinc-300 bg-white pl-10 pr-4 text-[13px] text-black shadow-none outline-none placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0" placeholder="Buscar usuario por nombre o correo..." autocomplete="off">
                                    </label>

                                    <div class="mt-[15px] grid min-w-0 grid-cols-1 overflow-hidden rounded-xl border border-zinc-300 lg:grid-cols-2">
                                        <section class="min-w-0 border-b border-zinc-300 bg-white lg:border-b-0 lg:border-r">
                                            <header class="flex min-h-[54px] items-center justify-between gap-3 border-b border-zinc-300 bg-zinc-50 px-[15px]">
                                                <div>
                                                    <h3 class="text-[13px] font-semibold text-black">Usuarios asignados</h3>
                                                    <p class="text-[11px] text-zinc-500">Selecciona para mover a otro rol.</p>
                                                </div>
                                                <span class="shrink-0 rounded-full bg-zinc-200 px-2.5 py-1 text-[11px] tabular-nums text-zinc-700" x-text="item.assigned_users.length"></span>
                                            </header>
                                            <div class="administration-record-scroll max-h-[230px] min-h-[150px] overflow-y-auto">
                                                <template x-for="user in filteredRoleUsers(item.assigned_users)" :key="`assigned-${item.id}-${user.id}`">
                                                    <label :class="user.id === currentUserId ? 'cursor-not-allowed bg-zinc-50' : 'cursor-pointer hover:bg-zinc-50'" class="flex min-h-[58px] items-center gap-3 border-b border-zinc-100 px-[15px] py-[10px] last:border-b-0">
                                                        <input type="checkbox" x-model.number="assignedRoleUserIds" :value="user.id" :disabled="user.id === currentUserId || roleAssignmentBusy" class="h-4 w-4 rounded border-zinc-300 text-black shadow-none focus:ring-0">
                                                        <span class="min-w-0 flex-1">
                                                            <strong class="block truncate text-[12px] font-semibold text-black" x-text="user.title"></strong>
                                                            <span class="mt-[2px] block truncate text-[10px] text-zinc-500" x-text="user.id === currentUserId ? `${user.email} · TU USUARIO` : user.email"></span>
                                                        </span>
                                                    </label>
                                                </template>
                                                <p x-show="filteredRoleUsers(item.assigned_users).length === 0" class="px-[15px] py-8 text-center text-[12px] text-zinc-500">No hay usuarios asignados.</p>
                                            </div>
                                            <div class="grid grid-cols-1 gap-[10px] border-t border-zinc-300 bg-zinc-50 p-[15px] sm:grid-cols-[minmax(0,1fr)_auto]">
                                                <select x-model.number="destinationRoleId" :disabled="roleAssignmentBusy" class="h-[42px] min-w-0 rounded-lg border border-zinc-300 bg-white px-3 text-[12px] text-black shadow-none outline-none focus:border-zinc-300 focus:ring-0">
                                                    <option value="">Mover al rol...</option>
                                                    <template x-for="roleOption in otherRoles(item)" :key="`destination-${item.id}-${roleOption.id}`">
                                                        <option :value="roleOption.id" x-text="roleOption.title"></option>
                                                    </template>
                                                </select>
                                                <button type="button" @click="moveRoleUsers(item)" :disabled="roleAssignmentBusy || assignedRoleUserIds.length === 0 || !destinationRoleId" class="h-[42px] rounded-lg bg-black px-4 text-[12px] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Cambiar rol</button>
                                            </div>
                                        </section>

                                        <section class="min-w-0 bg-white">
                                            <header class="flex min-h-[54px] items-center justify-between gap-3 border-b border-zinc-300 bg-zinc-50 px-[15px]">
                                                <div>
                                                    <h3 class="text-[13px] font-semibold text-black">Usuarios disponibles</h3>
                                                    <p class="text-[11px] text-zinc-500">Usuarios que actualmente tienen otro rol.</p>
                                                </div>
                                                <span class="shrink-0 rounded-full bg-zinc-200 px-2.5 py-1 text-[11px] tabular-nums text-zinc-700" x-text="item.available_users.length"></span>
                                            </header>
                                            <div class="administration-record-scroll max-h-[230px] min-h-[150px] overflow-y-auto">
                                                <template x-for="user in filteredRoleUsers(item.available_users)" :key="`available-${item.id}-${user.id}`">
                                                    <label :class="user.id === currentUserId ? 'cursor-not-allowed bg-zinc-50' : 'cursor-pointer hover:bg-zinc-50'" class="flex min-h-[58px] items-center gap-3 border-b border-zinc-100 px-[15px] py-[10px] last:border-b-0">
                                                        <input type="checkbox" x-model.number="availableRoleUserIds" :value="user.id" :disabled="user.id === currentUserId || roleAssignmentBusy" class="h-4 w-4 rounded border-zinc-300 text-black shadow-none focus:ring-0">
                                                        <span class="min-w-0 flex-1">
                                                            <strong class="block truncate text-[12px] font-semibold text-black" x-text="user.title"></strong>
                                                            <span class="mt-[2px] block truncate text-[10px] text-zinc-500" x-text="`${user.email} · ${user.current_role}${user.id === currentUserId ? ' · TU USUARIO' : ''}`"></span>
                                                        </span>
                                                    </label>
                                                </template>
                                                <p x-show="filteredRoleUsers(item.available_users).length === 0" class="px-[15px] py-8 text-center text-[12px] text-zinc-500">No hay usuarios disponibles.</p>
                                            </div>
                                            <div class="flex min-h-[73px] items-center justify-end border-t border-zinc-300 bg-zinc-50 p-[15px]">
                                                <button type="button" @click="assignRoleUsers(item)" :disabled="roleAssignmentBusy || availableRoleUserIds.length === 0" class="h-[42px] rounded-lg bg-black px-4 text-[12px] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Asignar al rol</button>
                                            </div>
                                        </section>
                                    </div>

                                    <p x-show="roleAssignmentStatus" x-cloak :class="roleAssignmentError ? 'text-red-600' : 'text-emerald-700'" class="mt-[10px] text-[12px]" x-text="roleAssignmentStatus"></p>
                                </div>
                            </template>
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
