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
    $input = 'mt-[15px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] text-[15px] leading-[30px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50';
    $section = 'rounded-xl border border-[#CAD7E7] bg-transparent p-[20px]';
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
        <div class="grid grid-cols-1 gap-[20px]">
            @if (session()->has('success'))
                <p class="role-notice">{{ session('success') }}</p>
            @endif
            @if (session()->has('error'))
                <p class="role-notice is-error">{{ session('error') }}</p>
            @endif

            <section class="{{ $section }}">
                <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Seleccionar rol</h2>
                <x-administration-search-picker input-id="role-management-picker" model="editingRoleId" :selected="$editingRoleId" :disabled="$lockedRoleId !== null" :items="$roleOptions" placeholder="Buscar rol por nombre..." empty-message="No se encontraron roles." placement="bottom" />
                <x-input-error for="editingRoleId" class="mt-[15px] text-[15px]" />
            </section>

            @if (! $isDelete)
                <section class="{{ $section }} {{ $editingRoleId ? '' : 'opacity-60' }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Información del rol</h2>
                    <div class="mt-[15px] grid grid-cols-1 gap-[20px] md:grid-cols-2">
                        <div class="min-w-0">
                            <label for="editing-role-name" class="block text-[15px] leading-[30px] text-[#102A52]">Nombre</label>
                            <input id="editing-role-name" wire:model.defer="editingRoleName" class="{{ $input }}" maxlength="255" @disabled(! $editingRoleId || $isSystemRole)>
                            <x-input-error for="editingRoleName" class="mt-[15px] text-[15px]" />
                        </div>
                        <div class="min-w-0">
                            <label for="editing-role-description" class="block text-[15px] leading-[30px] text-[#102A52]">Descripción</label>
                            <input id="editing-role-description" wire:model.defer="editingRoleDescription" class="{{ $input }}" maxlength="255" @disabled(! $editingRoleId)>
                            <x-input-error for="editingRoleDescription" class="mt-[15px] text-[15px]" />
                        </div>
                    </div>
                </section>

                <section class="{{ $section }} {{ $editingRoleId ? '' : 'opacity-60' }}">
                    <div class="flex items-center justify-between gap-[20px]">
                        <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Grupo de permisos</h2>
                        <span class="text-[15px] leading-[30px] text-[#1d4ed8] dark:text-[#60a5fa]">{{ count($permissionIds) }} accesos</span>
                    </div>
                    <div class="mt-[15px] grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                        @forelse ($permissionGroups as $group)
                            <button type="button" wire:click="selectPermissionGroup({{ $group->id }})" wire:key="managed-role-group-{{ $group->id }}" @disabled(! $editingRoleId)
                                aria-pressed="{{ (int) $permissionGroupId === (int) $group->id ? 'true' : 'false' }}"
                                class="role-choice focus:outline-none focus:ring-0">
                                <span class="min-w-0 truncate" title="{{ $group->name }}">{{ $group->name }}</span>
                                <span class="role-choice-count" aria-label="{{ $group->permissions_count }} apartados">{{ $group->permissions_count }}</span>
                            </button>
                        @empty
                            <p class="role-notice sm:col-span-2">Primero crea un grupo desde la tarjeta Permisos.</p>
                        @endforelse
                    </div>
                    <x-input-error for="permissionGroupId" class="mt-[15px] text-[15px]" />
                </section>

                <section class="{{ $section }} {{ $editingRoleId ? '' : 'opacity-60' }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Accesos del rol</h2>
                    <div class="mt-[15px]">
                        <x-role-permission-preview :permissions="$availablePermissions" :selected-ids="$permissionIds" />
                    </div>
                </section>
            @else
                @if ($selectedRole && $hasAssignedUsers)
                    <p class="role-notice is-warning">Traslada los usuarios a otro rol para poder eliminar «{{ $selectedRole->role }}».</p>
                @endif
                <section class="{{ $section }} {{ $selectedRole ? '' : 'opacity-60' }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Verificación</h2>
                    <div class="mt-[15px]">
                        <label for="delete-role-confirmation" class="block text-[15px] leading-[30px] text-[#102A52]">Escribe el nombre exacto del rol</label>
                        <input id="delete-role-confirmation" type="text" wire:model.defer="deleteConfirmationName" autocomplete="new-password" onpaste="return false" @disabled(! $canDelete)
                            class="{{ $input }} uppercase">
                        <x-input-error for="deleteConfirmationName" class="mt-[15px] text-[15px]" />
                    </div>
                    <div class="mt-[20px]">
                        <label for="delete-role-word" class="block text-[15px] leading-[30px] text-[#102A52]">Escribe ELIMINAR</label>
                        <input id="delete-role-word" type="text" wire:model.defer="deleteConfirmationWord" autocomplete="new-password" onpaste="return false" @disabled(! $canDelete)
                            class="{{ $input }} uppercase">
                        <x-input-error for="deleteConfirmationWord" class="mt-[15px] text-[15px]" />
                    </div>
                </section>
            @endif

            @if ($selectedRole)
                <section class="{{ $section }}">
                    <h2 class="text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Usuarios del rol</h2>
                    <div class="mt-[15px] grid grid-cols-1 gap-[20px] md:grid-cols-2">
                        <div class="min-w-0">
                            <h3 class="text-[15px] leading-[30px]">Asignados ({{ $assignedUsers->count() }})</h3>
                            <div class="role-user-box mt-[15px]">
                                @forelse ($assignedUsers as $user)
                                    <label class="role-user-row is-check" wire:key="role-assigned-{{ $user->id }}">
                                        <input type="checkbox" value="{{ $user->id }}" wire:model="selectedUserIds" class="h-4 w-4 shrink-0 rounded border-[#B7CEEA] text-[#15803d] focus:ring-0">
                                        <span title="{{ trim($user->name.' '.$user->last_name) }} · {{ $user->email }}">{{ trim($user->name.' '.$user->last_name) }}</span>
                                    </label>
                                @empty
                                    <p class="role-user-empty">Sin usuarios asignados.</p>
                                @endforelse
                            </div>
                            <label for="role-destination" class="mt-[20px] block text-[15px] leading-[30px]">Mover seleccionados a</label>
                            <select id="role-destination" wire:model="destinationRoleId" class="{{ $input }}">
                                <option value="">Selecciona un rol</option>
                                @foreach ($roles->where('id', '!=', $selectedRole->id) as $otherRole)
                                    <option value="{{ $otherRole->id }}">{{ $otherRole->role }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="destinationRoleId" class="mt-[15px] text-[15px]" />
                            <x-input-error for="selectedUserIds" class="mt-[15px] text-[15px]" />
                            <button type="button" wire:click="moveSelectedUsers" class="admin-btn admin-btn-primary mt-[20px] inline-flex w-full items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] focus:outline-none focus:ring-0">Mover seleccionados</button>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[15px] leading-[30px]">Sin este rol ({{ $otherUsers->count() }})</h3>
                            <div class="role-user-box mt-[15px]">
                                @forelse ($otherUsers as $user)
                                    <div class="role-user-row" wire:key="role-other-{{ $user->id }}">
                                        <span title="{{ trim($user->name.' '.$user->last_name) }} · {{ $user->email }}">{{ trim($user->name.' '.$user->last_name) }}</span>
                                        <button type="button" wire:click="assignUser({{ $user->id }})" class="admin-btn admin-btn-secondary shrink-0 rounded-xl px-[20px] py-[10px] text-[15px] focus:outline-none focus:ring-0">Asignar</button>
                                    </div>
                                @empty
                                    <p class="role-user-empty">Todos tienen este rol.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </x-slot>

    <x-slot name="actions">
        <button type="button" wire:click="cancel" class="admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] focus:outline-none focus:ring-0">Cerrar</button>
        @if ($isDelete)
            <button type="button" wire:click="deleteRole({{ $editingRoleId ?: 0 }})" wire:confirm="¿Confirmas que deseas eliminar este rol?" wire:loading.attr="disabled" wire:target="deleteRole" @disabled(! $canDelete)
                class="inline-flex min-w-28 items-center justify-center rounded-xl border border-red-600 bg-red-600/10 px-[20px] py-[15px] text-[15px] text-red-600 disabled:cursor-not-allowed disabled:opacity-50">Eliminar</button>
        @else
            <button type="button" wire:click="saveEditedRole" wire:loading.attr="disabled" wire:target="saveEditedRole" @disabled(! $editingRoleId)
                class="admin-btn admin-btn-primary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] disabled:cursor-not-allowed disabled:opacity-50">Guardar cambios</button>
        @endif
    </x-slot>
</x-administration-panel-modal>
