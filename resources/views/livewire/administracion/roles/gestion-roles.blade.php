@php
    $isDelete = $activeTab === 'delete';
    $isSystemRole = $selectedRole && in_array($selectedRole->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
    $hasAssignedUsers = (int) ($selectedRole?->users_count ?? 0) > 0;
    $canDelete = $selectedRole && ! $isSystemRole && ! $hasAssignedUsers;
    $roleOptions = $roles->map(fn ($listedRole) => [
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
                                <div class="grid grid-cols-1 gap-[10px]">
                                    @foreach ($modulePermissions as $permission)
                                        @php($permissionSelected = in_array((int) $permission->id, $permissionIds, true))
                                        <div wire:key="managed-role-permission-{{ $permission->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-[14px]">
                                            <span class="min-w-0 truncate" title="{{ $permission->name }}">{{ $permission->name }}</span>
                                            <span class="shrink-0 {{ $permissionSelected ? 'text-emerald-700' : 'text-zinc-500' }}">{{ $permissionSelected ? 'Incluido' : 'Sin acceso' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @empty
                            <p class="text-[14px] text-[#55749D]">No hay permisos activos.</p>
                        @endforelse
                    </div>
                </section>
            @else
                @if ($selectedRole && $hasAssignedUsers)
                    <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-[14px] text-amber-900">Traslada los usuarios a otro rol para poder eliminar «{{ $selectedRole->role }}».</p>
                @endif
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

            @if ($selectedRole)
                <section class="rounded-xl border border-zinc-200 bg-white p-5">
                    <h2 class="text-[15px] font-medium">Usuarios del rol</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="min-w-0">
                            <h3 class="mb-2 text-[14px]">Asignados ({{ $assignedUsers->count() }})</h3>
                            <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-2">
                                @forelse ($assignedUsers as $user)
                                    <label class="flex items-center gap-2 rounded-lg p-2 hover:bg-zinc-50">
                                        <input type="checkbox" value="{{ $user->id }}" wire:model="selectedUserIds" class="rounded border-zinc-300 text-black focus:ring-0">
                                        <span class="min-w-0 truncate text-[13px]" title="{{ $user->email }}">{{ trim($user->name.' '.$user->last_name) }}</span>
                                    </label>
                                @empty
                                    <p class="p-2 text-[13px] text-zinc-500">Sin usuarios asignados.</p>
                                @endforelse
                            </div>
                            <label for="role-destination" class="mt-3 block text-[13px]">Mover seleccionados a</label>
                            <select id="role-destination" wire:model="destinationRoleId" class="mt-1 w-full rounded-lg border-zinc-200 text-[14px]">
                                <option value="">Selecciona un rol</option>
                                @foreach ($roles->where('id', '!=', $selectedRole->id) as $otherRole)
                                    <option value="{{ $otherRole->id }}">{{ $otherRole->role }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="destinationRoleId" class="mt-1" />
                            <x-input-error for="selectedUserIds" class="mt-1" />
                            <button type="button" wire:click="moveSelectedUsers" class="mt-2 w-full rounded-lg bg-black px-3 py-2 text-[13px] text-white">Mover seleccionados</button>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-2 text-[14px]">Sin este rol ({{ $otherUsers->count() }})</h3>
                            <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-2">
                                @forelse ($otherUsers as $user)
                                    <div class="flex items-center justify-between gap-2 rounded-lg p-2 hover:bg-zinc-50">
                                        <span class="min-w-0 truncate text-[13px]" title="{{ $user->email }}">{{ trim($user->name.' '.$user->last_name) }}</span>
                                        <button type="button" wire:click="assignUser({{ $user->id }})" class="shrink-0 text-[13px] text-[#1A3A6B]">Asignar</button>
                                    </div>
                                @empty
                                    <p class="p-2 text-[13px] text-zinc-500">Todos tienen este rol.</p>
                                @endforelse
                            </div>
                        </div>
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
