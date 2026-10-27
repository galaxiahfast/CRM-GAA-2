@php
    $isDelete = $activeTab === 'delete';
    $isSystemRole = $selectedRole && in_array($selectedRole->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
    $hasAssignedUsers = (int) ($selectedRole?->users_count ?? 0) > 0;
    $canDelete = $selectedRole && ! $isSystemRole && ! $hasAssignedUsers;
    $roleOptions = $roles->map(fn ($listedRole) => [
        'id' => $listedRole->id,
        'label' => mb_strtoupper($listedRole->role),
        'meta' => (int) $listedRole->users_count.' usuarios',
        'disabled' => $isDelete && in_array($listedRole->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true),
    ]);
    $replacementRoleOptions = $roles
        ->reject(fn ($listedRole) => (int) $listedRole->id === (int) $editingRoleId)
        ->map(fn ($listedRole) => [
            'id' => $listedRole->id,
            'label' => mb_strtoupper($listedRole->role),
            'meta' => (int) $listedRole->users_count.' usuarios',
        ]);
    $input = 'mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#F1F3F6] disabled:text-[#98A4B3]';
@endphp

<x-administration-panel-modal
    :title="$isDelete ? 'Eliminar rol' : 'Editar rol y asignar permisos'"
    modal-id="roles-management"
    cancel-action="cancel"
    :carousel-style="true"
>
    <x-slot name="icon">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2a5 5 0 0 0-10 0v2M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-5">
            @if (session()->has('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[14px] text-emerald-700">{{ session('success') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[14px] text-red-700">{{ session('error') }}</div>
            @endif

            <section class="rounded-xl border border-[#CAD7E7] bg-white/45 p-5">
                <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Seleccionar rol</h2>
                <x-administration-search-picker input-id="role-management-picker" model="editingRoleId" :selected="$editingRoleId" :disabled="$lockedRoleId !== null" :items="$roleOptions" placeholder="Buscar rol por nombre..." empty-message="No se encontraron roles." placement="bottom" />
                <x-input-error for="editingRoleId" class="mt-2 text-[14px]" />
            </section>

            <section class="rounded-xl border border-[#CAD7E7] bg-white/45 p-5 {{ $editingRoleId ? '' : 'opacity-60' }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Usuarios del rol</h2>
                        <p class="mt-1 text-[13px] text-[#55749D]">Consulta quién tiene este rol, agrega usuarios o muévelos a otro.</p>
                    </div>
                    <span class="rounded-full bg-[#DCE9F8] px-3 py-1 text-[11px] font-semibold text-[#1A3A6B]">{{ (int) ($selectedRole?->users_count ?? 0) }} usuarios</span>
                </div>

                <div class="relative mt-[15px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6B7C93]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                    <input type="search" wire:model.live.debounce.300ms="userSearch" class="{{ $input }} mt-0 pl-10" placeholder="Buscar usuario por nombre o correo..." @disabled(! $editingRoleId)>
                </div>

                @if ($editingRoleId)
                    <div class="mt-[15px] rounded-xl border border-[#D7E2F0] bg-white/60">
                        <div class="flex items-center justify-between border-b border-[#D7E2F0] px-4 py-3">
                            <span class="text-[13px] font-semibold text-[#102A52]">Asignados actualmente</span>
                            <span class="text-[12px] text-[#55749D]">Selecciona para cambiar de rol</span>
                        </div>
                        <div class="administration-picker-scrollbar max-h-48 overflow-y-auto">
                            @forelse ($assignedUsers as $assignedUser)
                                @php($isCurrentUser = (int) $assignedUser->id === (int) auth()->id())
                                <label wire:key="assigned-role-user-{{ $assignedUser->id }}" class="flex min-h-[58px] items-center gap-3 border-b border-[#E4EAF2] px-4 py-3 last:border-b-0 {{ $isCurrentUser ? 'cursor-not-allowed bg-[#F4F6F9]' : 'cursor-pointer hover:bg-[#F6F9FD]' }}">
                                    <input type="checkbox" wire:model.defer="selectedAssignedUserIds" value="{{ $assignedUser->id }}" class="h-4 w-4 rounded border-[#AEB9C8] text-[#202020] shadow-none focus:ring-0" @disabled($isCurrentUser)>
                                    <span class="min-w-0 flex-1">
                                        <strong class="block truncate text-[14px] text-[#102A52]">{{ trim($assignedUser->name.' '.$assignedUser->last_name) }}</strong>
                                        <span class="block truncate text-[12px] text-[#6B7C93]">{{ $assignedUser->email }}{{ $isCurrentUser ? ' · Tu usuario' : '' }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-4 py-5 text-center text-[13px] text-[#6B7C93]">{{ $userSearch ? 'No hay usuarios asignados que coincidan con la búsqueda.' : 'Este rol no tiene usuarios asignados.' }}</p>
                            @endforelse
                        </div>
                    </div>
                    <x-input-error for="selectedAssignedUserIds" class="mt-2 text-[14px]" />

                    <div class="mt-[15px] grid grid-cols-1 items-end gap-[10px] md:grid-cols-[1fr_auto]">
                        <div>
                            <label class="block text-[13px] font-medium text-[#102A52]">Nuevo rol para los seleccionados</label>
                            <x-administration-search-picker input-id="replacement-role-picker" model="replacementRoleId" :selected="$replacementRoleId" :items="$replacementRoleOptions" placeholder="Buscar rol de destino..." empty-message="No hay otro rol disponible." placement="top" />
                        </div>
                        <button type="button" wire:click="moveUsersToAnotherRole" wire:loading.attr="disabled" wire:target="moveUsersToAnotherRole" class="h-11 rounded-lg bg-[#202020] px-5 text-[14px] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Cambiar rol</button>
                    </div>
                    <x-input-error for="replacementRoleId" class="mt-2 text-[14px]" />

                    @if (! $isDelete)
                        <div class="mt-5 border-t border-[#D7E2F0] pt-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[13px] font-semibold text-[#102A52]">Agregar usuarios a {{ $selectedRole?->role }}</span>
                                <span class="text-[12px] text-[#55749D]">Se cambiará su rol actual</span>
                            </div>
                            <div class="administration-picker-scrollbar mt-[10px] max-h-48 overflow-y-auto rounded-xl border border-[#D7E2F0] bg-white/60">
                                @forelse ($availableUsers as $availableUser)
                                    @php($isCurrentUser = (int) $availableUser->id === (int) auth()->id())
                                    <label wire:key="available-role-user-{{ $availableUser->id }}" class="flex min-h-[58px] items-center gap-3 border-b border-[#E4EAF2] px-4 py-3 last:border-b-0 {{ $isCurrentUser ? 'cursor-not-allowed bg-[#F4F6F9]' : 'cursor-pointer hover:bg-[#F6F9FD]' }}">
                                        <input type="checkbox" wire:model.defer="selectedAvailableUserIds" value="{{ $availableUser->id }}" class="h-4 w-4 rounded border-[#AEB9C8] text-[#202020] shadow-none focus:ring-0" @disabled($isCurrentUser)>
                                        <span class="min-w-0 flex-1">
                                            <strong class="block truncate text-[14px] text-[#102A52]">{{ trim($availableUser->name.' '.$availableUser->last_name) }}</strong>
                                            <span class="block truncate text-[12px] text-[#6B7C93]">{{ $availableUser->email }} · {{ $availableUser->role?->role ?? 'Sin rol' }}{{ $isCurrentUser ? ' · Tu usuario' : '' }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-4 py-5 text-center text-[13px] text-[#6B7C93]">No hay otros usuarios que coincidan con la búsqueda.</p>
                                @endforelse
                            </div>
                            <x-input-error for="selectedAvailableUserIds" class="mt-2 text-[14px]" />
                            <div class="mt-[10px] flex justify-end">
                                <button type="button" wire:click="assignUsersToRole" wire:loading.attr="disabled" wire:target="assignUsersToRole" class="h-11 rounded-lg border border-[#202020] bg-white px-5 text-[14px] font-semibold text-[#202020] disabled:cursor-not-allowed disabled:opacity-50">Asignar al rol</button>
                            </div>
                        </div>
                    @elseif ($hasAssignedUsers)
                        <p class="mt-[15px] rounded-lg bg-amber-50 px-4 py-3 text-[13px] text-amber-800">Mueve todos los usuarios a otro rol para habilitar la eliminación.</p>
                    @endif
                @endif
            </section>

            @if (! $isDelete)
                <section class="rounded-xl border border-[#CAD7E7] bg-white/45 p-5 {{ $editingRoleId ? '' : 'opacity-60' }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Información del rol</h2>
                    <div class="mt-[15px] grid grid-cols-1 gap-[15px] md:grid-cols-2">
                        <div>
                            <label for="editing-role-name" class="block text-[15px] font-medium text-[#102A52]">Nombre</label>
                            <input id="editing-role-name" wire:model.defer="editingRoleName" class="{{ $input }}" maxlength="255" @disabled(! $editingRoleId || $isSystemRole)>
                            <x-input-error for="editingRoleName" class="mt-2 text-[14px]" />
                        </div>
                        <div>
                            <label for="editing-role-description" class="block text-[15px] font-medium text-[#102A52]">Descripción</label>
                            <input id="editing-role-description" wire:model.defer="editingRoleDescription" class="{{ $input }}" maxlength="255" @disabled(! $editingRoleId)>
                            <x-input-error for="editingRoleDescription" class="mt-2 text-[14px]" />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-[#CAD7E7] bg-white/45 p-5 {{ $editingRoleId ? '' : 'opacity-60' }}">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Grupo de permisos</h2>
                        <span class="rounded-full bg-[#DCE9F8] px-3 py-1 text-[11px] font-semibold text-[#1A3A6B]">{{ count($permissionIds) }} accesos</span>
                    </div>
                    <div class="mt-[15px] grid grid-cols-1 gap-[10px] md:grid-cols-2">
                        @forelse ($permissionGroups as $group)
                            <button type="button" wire:click="selectPermissionGroup({{ $group->id }})" wire:key="managed-role-group-{{ $group->id }}" @disabled(! $editingRoleId)
                                class="min-w-0 rounded-xl border p-4 text-left transition focus:outline-none focus:ring-0 disabled:cursor-not-allowed {{ (int) $permissionGroupId === (int) $group->id ? 'border-[#1A3A6B] bg-[#EAF2FC]' : 'border-[#CAD7E7] bg-white/60 hover:border-[#1A3A6B]' }}">
                                <span class="flex items-center justify-between gap-3"><strong class="truncate text-[14px] text-[#102A52]">{{ mb_strtoupper($group->name) }}</strong><span class="shrink-0 rounded-full bg-[#DCE9F8] px-2 py-1 text-[10px] font-semibold text-[#1A3A6B]">{{ $group->permissions_count }}</span></span>
                            </button>
                        @empty
                            <p class="md:col-span-2 rounded-lg border border-dashed border-[#B7CEEA] p-4 text-center text-[14px] text-[#55749D]">Primero crea un grupo desde la tarjeta Permisos.</p>
                        @endforelse
                    </div>
                    <x-input-error for="permissionGroupId" class="mt-2 text-[14px]" />
                </section>

                <section class="rounded-xl border border-[#CAD7E7] bg-white/45 p-5 {{ $editingRoleId ? '' : 'opacity-60' }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Vista previa de accesos</h2>
                    <div class="mt-[15px] space-y-[15px]">
                        @forelse ($availablePermissions->groupBy(fn ($permission) => $permission->module ?: 'General') as $module => $modulePermissions)
                            <fieldset class="rounded-xl border border-[#D7E2F0] bg-white/55 p-4">
                                <legend class="px-2 text-[13px] font-semibold text-[#1A3A6B]">{{ mb_strtoupper($module) }}</legend>
                                <div class="grid grid-cols-1 gap-[10px] md:grid-cols-2">
                                    @foreach ($modulePermissions as $permission)
                                        @php($permissionSelected = in_array((int) $permission->id, $permissionIds, true))
                                        <x-permission-access-card wire:key="managed-role-permission-{{ $permission->id }}" :permission="$permission" :selected="$permissionSelected" />
                                    @endforeach
                                </div>
                            </fieldset>
                        @empty
                            <p class="text-[14px] text-[#55749D]">No hay permisos activos.</p>
                        @endforelse
                    </div>
                </section>
            @else
                <section class="rounded-xl border border-[#CAD7E7] bg-white/45 p-5 {{ $selectedRole ? '' : 'opacity-60' }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Verificación</h2>
                    <div class="mt-[15px]">
                        <label for="delete-role-confirmation" class="block text-[15px] font-medium text-[#102A52]">Escribe el nombre exacto del rol</label>
                        <input id="delete-role-confirmation" type="text" wire:model.defer="deleteConfirmationName" autocomplete="new-password" onpaste="return false" @disabled(! $canDelete)
                            class="{{ $input }} uppercase">
                        <x-input-error for="deleteConfirmationName" class="mt-2 text-[14px]" />
                    </div>
                    <div class="mt-[15px]">
                        <label for="delete-role-word" class="block text-[15px] font-medium text-[#102A52]">Escribe ELIMINAR</label>
                        <input id="delete-role-word" type="text" wire:model.defer="deleteConfirmationWord" autocomplete="new-password" onpaste="return false" @disabled(! $canDelete)
                            class="{{ $input }} uppercase">
                        <x-input-error for="deleteConfirmationWord" class="mt-2 text-[14px]" />
                    </div>
                </section>
            @endif
        </div>
    </x-slot>

    <x-slot name="actions">
        <button type="button" wire:click="cancel" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white hover:bg-white/20 focus:outline-none focus:ring-0">Cerrar</button>
        @if ($isDelete)
            <button type="button" wire:click="deleteRole({{ $editingRoleId ?: 0 }})" wire:confirm="¿Confirmas que deseas eliminar este rol?" wire:loading.attr="disabled" wire:target="deleteRole" @disabled(! $canDelete)
                class="inline-flex min-w-28 items-center justify-center rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-red-700 disabled:cursor-not-allowed disabled:opacity-50">Eliminar</button>
        @else
            <button type="button" wire:click="saveEditedRole" wire:loading.attr="disabled" wire:target="saveEditedRole" @disabled(! $editingRoleId)
                class="inline-flex min-w-28 items-center justify-center rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-[#1A3A6B] disabled:cursor-not-allowed disabled:opacity-50">Guardar cambios</button>
        @endif
    </x-slot>
</x-administration-panel-modal>
