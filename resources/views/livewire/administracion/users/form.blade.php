@props(['isAuxiliar' => false])

@php
    $modalTitle = $mode === 'edit' ? 'Editar usuario' : 'Crear nuevo usuario';
    $modalSubtitle = $mode === 'edit'
        ? 'Actualiza los datos de acceso y el perfil organizacional.'
        : 'Completa los datos de acceso y el perfil organizacional.';

    $inputClasses = 'mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0';
@endphp

<x-administration-form-modal
    submit="save"
    cancel-action="cancel"
    modal-id="user-form"
    :title="$modalTitle"
    :subtitle="$modalSubtitle"
>
    <x-slot name="icon">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3M13 7a4 4 0 11-8 0 4 4 0 018 0zM3 21a6 6 0 0112 0" />
        </svg>
    </x-slot>

    <x-slot name="navigation">
        @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
            <button type="button" wire:click="setManagementTab('{{ $tab }}')"
                class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $managementTab === $tab ? ($tab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </x-slot>

    <x-slot name="form">
        @if ($managementTab === 'crear' || ($managementTab === 'editar' && $mode === 'edit'))
        <div class="flex flex-col gap-5">
            <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                <div class="mb-[15px]">
                    <h2 class="text-[15px] font-semibold text-gray-900">Datos personales</h2>
                    <p class="mt-1 text-[15px] text-gray-500">Información de identificación y acceso del usuario.</p>
                </div>

                <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <div class="min-w-0">
                        <label for="name" class="block text-[15px] font-medium text-gray-700">Nombres</label>
                        <input id="name" type="text" maxlength="255" wire:model="name" class="{{ $inputClasses }}">
                        <x-input-error for="name" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="last_name" class="block text-[15px] font-medium text-gray-700">Apellidos</label>
                        <input id="last_name" type="text" maxlength="255" wire:model="last_name" class="{{ $inputClasses }}">
                        <x-input-error for="last_name" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0 sm:col-span-2">
                        <label for="email" class="block text-[15px] font-medium text-gray-700">Correo electrónico</label>
                        <input id="email" type="email" maxlength="255" wire:model="email" class="{{ $inputClasses }}">
                        <x-input-error for="email" class="mt-2 text-[15px]" />
                    </div>

                    @if (! $user || ! $user->exists)
                        <div class="min-w-0">
                            <label for="password" class="block text-[15px] font-medium text-gray-700">Contraseña</label>
                            <input id="password" type="password" maxlength="255" wire:model="password" class="{{ $inputClasses }}">
                            <x-input-error for="password" class="mt-2 text-[15px]" />
                        </div>

                        <div class="min-w-0">
                            <label for="password_confirmation" class="block text-[15px] font-medium text-gray-700">Confirmar contraseña</label>
                            <input id="password_confirmation" type="password" maxlength="255" wire:model="password_confirmation" class="{{ $inputClasses }}">
                            <x-input-error for="password_confirmation" class="mt-2 text-[15px]" />
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                <div class="mb-[15px]">
                    <h2 class="text-[15px] font-semibold text-gray-900">Perfil organizacional</h2>
                    <p class="mt-1 text-[15px] text-gray-500">Define el rol, puesto y área de trabajo.</p>
                </div>

                <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <div class="min-w-0 sm:col-span-2">
                        <label for="role_id" class="block text-[15px] font-medium text-gray-700">Rol</label>
                        <select id="role_id" name="role_id" wire:model.live="role_id" class="{{ $inputClasses }}">
                            <option disabled value="">Seleccione un rol</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->role }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="role_id" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="job_position_id" class="block text-[15px] font-medium text-gray-700">Puesto de trabajo</label>
                        <select id="job_position_id" wire:model.live="job_position_id" class="{{ $inputClasses }}">
                            <option value="">Seleccione un puesto</option>
                            @foreach ($jobPositions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }} — {{ $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo' }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="job_position_id" class="mt-2 text-[15px]" />
                    </div>

                    <div class="min-w-0">
                        <label for="physical_area_id" class="block text-[15px] font-medium text-gray-700">Área / Departamento</label>
                        <select id="physical_area_id" wire:model="physical_area_id" class="{{ $inputClasses }}">
                            <option value="">Seleccione un área</option>
                            @foreach ($physicalAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="physical_area_id" class="mt-2 text-[15px]" />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                <div class="mb-[15px]">
                    <h2 class="text-[15px] font-semibold text-gray-900">Configuración del checador y nómina</h2>
                    <p class="mt-1 text-[15px] text-gray-500">Vincula el identificador biométrico y, cuando aplique, los importes del auxiliar.</p>
                </div>

                <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                    <div class="min-w-0 sm:col-span-2">
                        <label for="employee_id" class="block text-[15px] font-medium text-gray-700">ID Checador (Hikvision)</label>
                        <div
                            class="relative"
                            x-data="{ open: false, search: @js((string) ($employee_id ?? '')) }"
                            @click.outside="open = false"
                        >
                            <input
                                id="employee_id"
                                type="text"
                                maxlength="50"
                                autocomplete="off"
                                wire:model="employee_id"
                                x-model="search"
                                @input="open = true"
                                @focus="open = true"
                                @click="open = true"
                                @keydown.escape="open = false"
                                class="{{ $inputClasses }} pr-12"
                                placeholder="Ej: 1024"
                                aria-autocomplete="list"
                                aria-controls="create-employee-id-suggestions"
                                x-bind:aria-expanded="open"
                            >

                            <button
                                type="button"
                                class="absolute right-0 top-2 flex h-11 w-12 items-center justify-center text-gray-500 transition hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                @click="open = !open"
                                aria-label="Mostrar sugerencias del reloj checador"
                            >
                                <svg class="h-4 w-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                id="create-employee-id-suggestions"
                                x-cloak
                                x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="administration-form-scrollbar absolute z-[60] mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                role="listbox"
                                aria-label="IDs disponibles del reloj checador"
                            >
                                @forelse ($employeeIdSuggestions as $employeeIdSuggestion)
                                    @php
                                        $suggestedPersonName = trim((string) ($employeeIdSuggestion->personName ?: 'Nombre no disponible'));
                                    @endphp
                                    <button
                                        type="button"
                                        data-employee-id="{{ $employeeIdSuggestion->employeeID }}"
                                        data-person-name="{{ $suggestedPersonName }}"
                                        x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())"
                                        @click="search = $el.dataset.employeeId; $wire.set('employee_id', $el.dataset.employeeId); open = false"
                                        class="flex w-full min-w-0 items-center gap-4 rounded-lg px-4 py-3 text-left transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none focus:ring-0"
                                        role="option"
                                    >
                                        <span class="w-20 shrink-0 truncate text-[15px] font-semibold text-[#1A3A6B]" title="{{ $employeeIdSuggestion->employeeID }}">
                                            {{ $employeeIdSuggestion->employeeID }}
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-[15px] text-gray-600" title="{{ $suggestedPersonName }}">
                                            {{ $suggestedPersonName }}
                                        </span>
                                    </button>
                                @empty
                                    <p class="px-4 py-3 text-[15px] text-gray-500">No hay IDs registrados en el checador.</p>
                                @endforelse
                            </div>
                        </div>
                        <p class="mt-2 text-[15px] text-gray-500">Selecciona un ID registrado; cada sugerencia muestra el nombre completo detectado por el checador.</p>
                        <x-input-error for="employee_id" class="mt-2 text-[15px]" />
                    </div>

                    @if ($isHourlyPosition)
                        <div class="min-w-0">
                            <label for="hourly_rate" class="block text-[15px] font-medium text-gray-700">Precio por hora ($)</label>
                            <input id="hourly_rate" type="number" step="0.01" min="0" wire:model="hourly_rate" class="{{ $inputClasses }}">
                            <x-input-error for="hourly_rate" class="mt-2 text-[15px]" />
                        </div>

                        <div class="min-w-0">
                            <label for="food_allowance" class="block text-[15px] font-medium text-gray-700">Apoyo económico por día ($)</label>
                            <input id="food_allowance" type="number" step="0.01" min="0" wire:model="food_allowance" class="{{ $inputClasses }}">
                            <x-input-error for="food_allowance" class="mt-2 text-[15px]" />
                        </div>
                    @endif
                </div>
            </section>
        </div>
        @elseif ($managementTab === 'editar')
            <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                <h2 class="text-[15px] font-semibold text-gray-900">Selecciona el usuario a editar</h2>
                <p class="mt-1 text-[15px] text-gray-500">Abrirá el mismo formulario con los datos actuales del usuario.</p>
                <label for="management-edit-user" class="mt-[15px] block text-[15px] font-medium text-gray-700">Usuario</label>
                <select id="management-edit-user" wire:model="managementUserId" class="{{ $inputClasses }}">
                    <option value="">Seleccione un usuario</option>
                    @foreach ($manageableUsers as $manageableUser)
                        <option value="{{ $manageableUser->id }}">{{ trim($manageableUser->name.' '.$manageableUser->last_name) }} — {{ $manageableUser->email }}</option>
                    @endforeach
                </select>
                <x-input-error for="managementUserId" class="mt-2 text-[15px]" />
            </section>
        @else
            <section class="rounded-xl border border-red-200 bg-red-50 p-5">
                <h2 class="text-[15px] font-semibold text-red-800">Eliminar usuario</h2>
                <p class="mt-1 text-[15px] text-red-700">Esta acción es irreversible. Las relaciones jerárquicas del usuario se desvincularán antes de eliminarlo.</p>
                <label for="management-delete-user" class="mt-[15px] block text-[15px] font-medium text-gray-700">Usuario</label>
                <select id="management-delete-user" wire:model.live="managementUserId" class="{{ $inputClasses }}">
                    <option value="">Seleccione un usuario</option>
                    @foreach ($manageableUsers as $manageableUser)
                        <option value="{{ $manageableUser->id }}">{{ trim($manageableUser->name.' '.$manageableUser->last_name) }} — {{ $manageableUser->email }}</option>
                    @endforeach
                </select>
                <x-input-error for="managementUserId" class="mt-2 text-[15px]" />
                @if ($managementUserId)
                    @php($selectedManagedUser = $manageableUsers->firstWhere('id', $managementUserId))
                    <label for="delete-confirmation-name" class="mt-[15px] block text-[15px] font-medium text-gray-700">Escribe el nombre completo para confirmar</label>
                    <input id="delete-confirmation-name" type="text" wire:model.defer="deleteConfirmationName" class="{{ $inputClasses }}" placeholder="{{ $selectedManagedUser ? trim($selectedManagedUser->name.' '.$selectedManagedUser->last_name) : '' }}">
                    <x-input-error for="deleteConfirmationName" class="mt-2 text-[15px]" />
                @endif
            </section>
        @endif
    </x-slot>

    <x-slot name="actions">
        @if ($managementTab === 'crear' || ($managementTab === 'editar' && $mode === 'edit'))
        <button
            type="button"
            wire:click="cancel"
            wire:loading.attr="disabled"
            class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-50"
        >
            Cancelar
        </button>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60"
        >
            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span wire:loading.remove wire:target="save">{{ $mode === 'edit' ? 'Actualizar' : 'Guardar' }}</span>
            <span wire:loading wire:target="save">Guardando...</span>
        </button>
        @elseif ($managementTab === 'editar')
            <button type="button" wire:click="cancel" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">Cancelar</button>
            <button type="button" wire:click="editManagedUser" wire:loading.attr="disabled" wire:target="editManagedUser" class="inline-flex min-w-28 items-center justify-center rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:opacity-60">Editar</button>
        @else
            <button type="button" wire:click="cancel" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">Cancelar</button>
            <button type="button" wire:click="deleteManagedUser" wire:loading.attr="disabled" wire:target="deleteManagedUser" class="inline-flex min-w-28 items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-0 disabled:opacity-60">Eliminar</button>
        @endif
    </x-slot>
</x-administration-form-modal>
