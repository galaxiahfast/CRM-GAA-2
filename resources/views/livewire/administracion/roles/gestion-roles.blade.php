<x-administration-panel-modal
    title="Gestión de roles"
    subtitle="Crea, edita y administra los roles existentes."
    modal-id="roles-management"
    cancel-action="cancel"
>
    <x-slot name="icon">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
    </x-slot>

    <x-slot name="navigation">
        <a
            href="{{ route('administracion.role.create') }}"
            class="flex flex-1 items-center justify-center gap-[10px] border-b-2 border-transparent px-4 py-4 text-[15px] font-medium text-gray-500 transition hover:border-gray-300 hover:text-gray-700 focus:outline-none focus:ring-0"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Crear Rol
        </a>

        <a
            href="{{ route('administracion.role', ['tab' => 'edit']) }}"
            @class([
                'flex flex-1 items-center justify-center gap-[10px] border-b-2 px-4 py-4 text-[15px] font-medium transition focus:outline-none focus:ring-0',
                'border-[#1A3A6B] text-[#1A3A6B]' => $activeTab === 'edit',
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $activeTab !== 'edit',
            ])
            @if ($activeTab === 'edit') aria-current="page" @endif
        >
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Editar Rol
        </a>

        <a
            href="{{ route('administracion.role', ['tab' => 'delete']) }}"
            @class([
                'flex flex-1 items-center justify-center gap-[10px] border-b-2 px-4 py-4 text-[15px] font-medium transition focus:outline-none focus:ring-0',
                'border-[#1A3A6B] text-[#1A3A6B]' => $activeTab === 'delete',
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $activeTab !== 'delete',
            ])
            @if ($activeTab === 'delete') aria-current="page" @endif
        >
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0h8l-1-3H9L8 7z" />
            </svg>
            Eliminar Roles
        </a>
    </x-slot>

    <x-slot name="content">
        <div class="flex flex-col gap-5">
            <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                <div class="flex flex-col gap-[15px] sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-semibold text-gray-900">
                            {{ $activeTab === 'delete' ? 'Eliminar roles' : 'Roles existentes' }}
                        </h2>
                        <p class="mt-1 text-[15px] leading-6 text-gray-500">
                            {{ $activeTab === 'delete'
                                ? 'Solo pueden eliminarse roles personalizados que no tengan usuarios asociados.'
                                : 'Selecciona un rol para editar su información y modificar sus permisos asociados.' }}
                        </p>
                        @if ($activeTab === 'delete')
                            <p class="mt-2 text-[15px] leading-6 text-amber-700">
                                Los roles base y los roles con usuarios asociados permanecen protegidos para evitar afectar el acceso de la plataforma.
                            </p>
                        @endif
                    </div>

                    <div class="w-full min-w-0 sm:w-64">
                        <label for="role-search" class="block text-[15px] font-medium text-gray-700">Buscar rol</label>
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z" />
                            </svg>
                            <input
                                id="role-search"
                                type="search"
                                wire:model.live.debounce.250ms="search"
                                placeholder="Buscar rol..."
                                class="block h-11 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0"
                            >
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-[15px]">
                @forelse ($roles as $role)
                    @php
                        $isManagedPermissionRole = in_array($role->role, ['Administrador', 'Auxiliar'], true);
                        $isSystemRole = in_array($role->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
                        $hasAssignedUsers = (int) ($role->users_count ?? 0) > 0;
                        $canDeleteRole = ! $isSystemRole && ! $hasAssignedUsers;
                    @endphp

                    <article wire:key="role-{{ $activeTab }}-{{ $role->id }}" class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                        <div class="flex min-w-0 flex-col gap-[15px] lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 flex-wrap items-center gap-[10px]">
                                    <h3 class="max-w-full truncate text-[15px] font-semibold text-gray-900" title="{{ $role->role }}">{{ $role->role }}</h3>
                                    @if ($isManagedPermissionRole)
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-[15px] font-medium text-[#1A3A6B]">Perfil de acceso vigente</span>
                                    @endif
                                    @if ($isSystemRole)
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-[15px] font-medium text-amber-700">Rol del sistema</span>
                                    @elseif ($hasAssignedUsers)
                                        <span class="rounded-full bg-gray-200 px-3 py-1 text-[15px] font-medium text-gray-600">Con usuarios asociados</span>
                                    @endif
                                </div>

                                <p class="mt-[10px] max-w-full truncate text-[15px] text-gray-500" title="{{ $role->description ?: 'Sin descripción' }}">
                                    {{ $role->description ?: 'Sin descripción' }}
                                </p>

                                <div class="mt-[10px] flex flex-wrap gap-x-5 gap-y-2 text-[15px] text-gray-400">
                                    <span><strong class="font-semibold text-gray-500">ID:</strong> {{ $role->id }}</span>
                                    <span><strong class="font-semibold text-gray-500">Permisos:</strong> {{ (int) ($role->access_permissions_count ?? 0) }}</span>
                                    <span><strong class="font-semibold text-gray-500">Usuarios:</strong> {{ (int) ($role->users_count ?? 0) }}</span>
                                    <span><strong class="font-semibold text-gray-500">Actualizado:</strong> {{ optional($role->updated_at)->format('d/m/Y') ?: 'Sin fecha' }}</span>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-[10px]">
                                @if ($activeTab === 'edit')
                                    <a
                                        href="{{ route('administracion.role.edit', $role->id) }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1A3A6B] px-4 py-2.5 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar y asignar permisos
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        @if ($canDeleteRole)
                                            wire:click="deleteRole({{ $role->id }})"
                                            wire:confirm="¿Estás seguro de que deseas eliminar este rol sin usuarios asociados?"
                                            wire:loading.attr="disabled"
                                            wire:target="deleteRole({{ $role->id }})"
                                        @else
                                            disabled
                                            title="{{ $isSystemRole ? 'Los roles del sistema no se eliminan desde esta interfaz.' : 'Desasigna primero a los usuarios vinculados con este rol.' }}"
                                        @endif
                                        @class([
                                            'inline-flex items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-[15px] font-medium transition focus:outline-none focus:ring-0',
                                            'border-red-500 text-red-600 hover:bg-red-50 disabled:cursor-wait disabled:opacity-50' => $canDeleteRole,
                                            'cursor-not-allowed border-gray-300 text-gray-400' => ! $canDeleteRole,
                                        ])
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0h8l-1-3H9L8 7z" />
                                        </svg>
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-8 text-center">
                        <p class="text-[15px] font-medium text-gray-700">No se encontraron roles.</p>
                        <p class="mt-2 text-[15px] text-gray-500">Prueba con otro término de búsqueda.</p>
                    </div>
                @endforelse
            </section>
        </div>
    </x-slot>

    <x-slot name="actions">
        <button
            type="button"
            wire:click="cancel"
            class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0"
        >
            Cerrar
        </button>
    </x-slot>
</x-administration-panel-modal>
