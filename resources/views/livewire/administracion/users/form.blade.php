@props(['isAuxiliar' => false])

@php
    $modalTitle = match ($managementTab) {
        'editar' => $mode === 'edit' ? 'Editar usuario' : 'Seleccionar usuario',
        'eliminar' => 'Eliminar usuario',
        default => 'Crear nuevo usuario',
    };
    $modalSubtitle = match ($managementTab) {
        'editar' => $mode === 'edit' ? 'Actualiza los datos de acceso y el perfil organizacional.' : 'Busca el usuario que deseas modificar.',
        'eliminar' => 'Selecciona y confirma el usuario que deseas eliminar.',
        default => 'Completa los datos de acceso y el perfil organizacional.',
    };

    $editingDisabled = $managementTab === 'editar' && ! filled($managementUserId);
    $deleteDisabled = $managementTab === 'eliminar' && ! filled($managementUserId);
    $inputClasses = 'mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:bg-transparent focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#F1F3F6] disabled:text-[#98A4B3]';
@endphp

<x-administration-form-modal
    submit="save"
    cancel-action="cancel"
    modal-id="user-form"
    :title="$modalTitle"
    :subtitle="$modalSubtitle"
    :carousel-style="true"
>
    <x-slot name="icon">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3M13 7a4 4 0 11-8 0 4 4 0 018 0zM3 21a6 6 0 0112 0" />
        </svg>
    </x-slot>

    @if (! $embedded)
    <x-slot name="navigation">
        @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
            <button type="button" wire:click="setManagementTab('{{ $tab }}')"
                class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $managementTab === $tab ? ($tab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </x-slot>
    @else
        <span class="sr-only">Crear Editar Eliminar</span>
    @endif

    <x-slot name="form">
        @if ($managementTab === 'editar')
            <section class="mb-5 rounded-xl border border-[#CAD7E7] p-5">
                <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Seleccionar usuario</h2>
                <label for="management-edit-user" class="block text-[15px] font-medium text-gray-700">Usuario a editar</label>
                <x-administration-search-picker input-id="management-edit-user" model="managementUserId" :selected="$managementUserId"
                    :items="$manageableUsers->map(fn ($manageableUser) => ['id' => $manageableUser->id, 'label' => trim($manageableUser->name.' '.$manageableUser->last_name), 'meta' => $manageableUser->email])"
                    placeholder="Buscar usuario por nombre o correo..." empty-message="No se encontraron usuarios." />
                <x-input-error for="managementUserId" class="mt-2 text-[15px]" />
            </section>
        @endif

        @if (in_array($managementTab, ['crear', 'editar'], true))
        <div class="flex flex-col gap-5 {{ $editingDisabled ? 'opacity-75' : '' }}">
            <section class="rounded-xl border border-[#CAD7E7] p-5">
                <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Datos personales</h2>

                <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <div class="min-w-0">
                        <label for="name" class="block text-[15px] font-medium text-gray-700">Nombres</label>
                        <input id="name" type="text" maxlength="255" wire:model="name" class="{{ $inputClasses }}" @disabled($editingDisabled)>
                        <x-input-error for="name" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="last_name" class="block text-[15px] font-medium text-gray-700">Apellidos</label>
                        <input id="last_name" type="text" maxlength="255" wire:model="last_name" class="{{ $inputClasses }}" @disabled($editingDisabled)>
                        <x-input-error for="last_name" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0 sm:col-span-2">
                        <label for="email" class="block text-[15px] font-medium text-gray-700">Correo electrónico</label>
                        <input id="email" type="email" maxlength="255" wire:model="email" class="{{ $inputClasses }}" autocomplete="off" @disabled($editingDisabled)>
                        <x-input-error for="email" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="password" class="block text-[15px] font-medium text-gray-700">{{ $managementTab === 'editar' ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                        <div class="relative mt-[10px]">
                            <input id="password" type="text" maxlength="255" wire:model="password" class="{{ $inputClasses }} !mt-0 pr-12" autocomplete="new-password" placeholder="{{ $managementTab === 'editar' ? 'Déjala vacía para conservar la actual' : '' }}" @disabled($editingDisabled)>
                            <button type="button" wire:click="generateRandomPassword" @disabled($editingDisabled) class="absolute right-1 top-0 flex h-11 w-11 items-center justify-center rounded-md text-[#55749D] transition hover:bg-[#E7F0FB] hover:text-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40" title="Generar otra contraseña" aria-label="Generar otra contraseña aleatoria">
                                <svg class="h-[19px] w-[19px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="3" stroke-width="1.7"/><circle cx="9" cy="9" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="9" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="9" cy="15" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="15" r="1" fill="currentColor" stroke="none"/></svg>
                            </button>
                        </div>
                        <x-input-error for="password" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="password_confirmation" class="block text-[15px] font-medium text-gray-700">{{ $managementTab === 'editar' ? 'Confirmar nueva contraseña' : 'Confirmar contraseña' }}</label>
                        <input id="password_confirmation" type="password" maxlength="255" wire:model="password_confirmation" class="{{ $inputClasses }}" autocomplete="new-password" @copy.prevent @cut.prevent @paste.prevent @disabled($editingDisabled)>
                        <x-input-error for="password_confirmation" class="mt-2 text-[15px]" />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-[#CAD7E7] p-5">
                <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Perfil organizacional</h2>

                <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <div class="min-w-0 sm:col-span-2">
                        <label for="role_id" class="block text-[15px] font-medium text-gray-700">Rol</label>
                        <x-administration-search-picker input-id="role_id" model="role_id" :selected="$role_id"
                            :items="$roles->map(fn ($role) => ['id' => $role->id, 'label' => $role->role])"
                            placeholder="Buscar rol..." empty-message="No se encontraron roles." :disabled="$editingDisabled" />
                        <x-input-error for="role_id" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="job_position_id" class="block text-[15px] font-medium text-gray-700">Puesto de trabajo</label>
                        <x-administration-search-picker input-id="job_position_id" model="job_position_id" :selected="$job_position_id"
                            :items="$jobPositions->map(fn ($position) => ['id' => $position->id, 'label' => $position->name, 'meta' => $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo'])"
                            placeholder="Buscar puesto..." empty-message="No se encontraron puestos." :disabled="$editingDisabled" />
                        <x-input-error for="job_position_id" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="physical_area_id" class="block text-[15px] font-medium text-gray-700">Área / Departamento</label>
                        <x-administration-search-picker input-id="physical_area_id" model="physical_area_id" :selected="$physical_area_id"
                            :items="$physicalAreas->map(fn ($area) => ['id' => $area->id, 'label' => $area->name])"
                            placeholder="Buscar área o departamento..." empty-message="No se encontraron áreas." :disabled="$editingDisabled" />
                        <x-input-error for="physical_area_id" class="mt-2 text-[15px]" />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-[#CAD7E7] p-5">
                <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Configuración del checador y nómina</h2>

                <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <div class="min-w-0 sm:col-span-2">
                        <label for="employee_id" class="block text-[15px] font-medium text-gray-700">ID Checador (Hikvision)</label>
                        <x-administration-search-picker input-id="employee_id" model="employee_id" :selected="$employee_id"
                            :items="$employeeIdSuggestions->map(fn ($suggestion) => ['id' => $suggestion->employeeID, 'label' => $suggestion->employeeID, 'meta' => trim((string) ($suggestion->personName ?: 'Nombre no disponible'))])"
                            placeholder="Buscar ID o persona..." empty-message="No hay IDs registrados en el checador." placement="bottom" results-id="create-employee-id-suggestions" :disabled="$editingDisabled" />
                        <x-input-error for="employee_id" class="mt-2 text-[15px]" />
                    </div>

                    @if ($isHourlyPosition)
                        <div class="min-w-0">
                            <label for="hourly_rate" class="block text-[15px] font-medium text-gray-700">Precio por hora ($)</label>
                            <input id="hourly_rate" type="number" step="0.01" min="0" wire:model="hourly_rate" class="{{ $inputClasses }}" @disabled($editingDisabled)>
                            <x-input-error for="hourly_rate" class="mt-2 text-[15px]" />
                        </div>

                        <div class="min-w-0">
                            <label for="food_allowance" class="block text-[15px] font-medium text-gray-700">Apoyo económico por día ($)</label>
                            <input id="food_allowance" type="number" step="0.01" min="0" wire:model="food_allowance" class="{{ $inputClasses }}" @disabled($editingDisabled)>
                            <x-input-error for="food_allowance" class="mt-2 text-[15px]" />
                        </div>
                    @endif
                </div>
            </section>
        </div>
        @else
            <div class="flex flex-col gap-5">
                <section class="rounded-xl border border-[#CAD7E7] p-5">
                    <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Seleccionar usuario</h2>
                    <label for="management-delete-user" class="block text-[15px] font-medium text-gray-700">Usuario a eliminar</label>
                    <x-administration-search-picker input-id="management-delete-user" model="managementUserId" :selected="$managementUserId"
                        :items="$manageableUsers->map(fn ($manageableUser) => ['id' => $manageableUser->id, 'label' => trim($manageableUser->name.' '.$manageableUser->last_name), 'meta' => $manageableUser->email])"
                        placeholder="Buscar usuario por nombre o correo..." empty-message="No se encontraron usuarios." />
                    <x-input-error for="managementUserId" class="mt-2 text-[15px]" />
                </section>

                <section class="rounded-xl border p-5 transition-colors {{ $deleteDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                    <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] {{ $deleteDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Confirmar eliminación</h2>
                    <div class="grid grid-cols-1 gap-[15px]">
                    <div>
                        <label for="delete-confirmation-name" class="block text-[15px] font-medium text-gray-700">Repite el nombre completo</label>
                        <input id="delete-confirmation-name" type="text" wire:model.defer="deleteConfirmationName" class="{{ $inputClasses }}" placeholder="{{ $deleteDisabled ? 'Selecciona primero un usuario' : 'Escribe manualmente el nombre completo' }}" @disabled($deleteDisabled) autocomplete="off">
                        <x-input-error for="deleteConfirmationName" class="mt-2 text-[15px]" />
                    </div>

                    <div>
                        <label for="delete-confirmation-email" class="block text-[15px] font-medium text-gray-700">Confirma su correo electrónico</label>
                        <input id="delete-confirmation-email" type="email" wire:model.defer="deleteConfirmationEmail" class="{{ $inputClasses }}" placeholder="{{ $deleteDisabled ? 'Selecciona primero un usuario' : 'Escribe manualmente el correo exacto' }}" @disabled($deleteDisabled) autocomplete="off">
                        <x-input-error for="deleteConfirmationEmail" class="mt-2 text-[15px]" />
                    </div>

                    <div>
                        <label for="delete-confirmation-phrase" class="block text-[15px] font-medium text-gray-700">Escribe <strong class="text-red-700">ELIMINAR</strong> para confirmar</label>
                        <input id="delete-confirmation-phrase" type="text" wire:model.defer="deleteConfirmationPhrase" class="{{ $inputClasses }}" placeholder="ELIMINAR" @disabled($deleteDisabled) autocomplete="off">
                        <x-input-error for="deleteConfirmationPhrase" class="mt-2 text-[15px]" />
                    </div>
                    </div>
                </section>
                </div>
        @endif
    </x-slot>

    <x-slot name="actions">
        @if (in_array($managementTab, ['crear', 'editar'], true))
        <button
            type="button"
            wire:click="cancel"
            class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0"
        >
            Cancelar
        </button>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            @disabled($editingDisabled)
            class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-[#1A3A6B] transition hover:bg-[#E7F0FB] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60"
        >
            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span wire:loading.remove wire:target="save">{{ $managementTab === 'editar' ? 'Actualizar' : 'Guardar' }}</span>
            <span wire:loading wire:target="save">Guardando...</span>
        </button>
        @else
            <button type="button" wire:click="cancel" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0">Cancelar</button>
            <button type="button" wire:click="deleteManagedUser" wire:loading.attr="disabled" wire:target="deleteManagedUser" @disabled($deleteDisabled) class="inline-flex min-w-28 items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">Eliminar</button>
        @endif
    </x-slot>
</x-administration-form-modal>
