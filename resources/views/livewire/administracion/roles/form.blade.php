@php
    $isProtectedSystemRole = $mode === 'edit'
        && in_array($role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
    $modalTitle = $mode === 'edit' ? 'Editar rol' : 'Crear nuevo rol';
    $modalSubtitle = $mode === 'edit'
        ? ($isProtectedSystemRole
            ? 'Actualiza la descripción sin alterar el identificador de seguridad.'
            : 'Actualiza el nombre y la descripción del rol.')
        : 'Define un nombre y una descripción clara para el rol.';

    $inputClasses = 'mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0';
@endphp

<x-administration-form-modal
    submit="save"
    cancel-action="cancel"
    modal-id="role-form"
    :title="$modalTitle"
    :subtitle="$modalSubtitle"
>
    <x-slot name="icon">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
    </x-slot>

    <x-slot name="navigation">
        <a
            href="{{ route('administracion.role.create') }}"
            @class([
                'flex flex-1 items-center justify-center gap-[10px] border-b-2 px-4 py-4 text-[15px] font-medium transition focus:outline-none focus:ring-0',
                'border-[#1A3A6B] text-[#1A3A6B]' => $mode === 'create',
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $mode !== 'create',
            ])
            @if ($mode === 'create') aria-current="page" @endif
        >
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Crear Rol
        </a>

        <a
            href="{{ route('administracion.role') }}"
            @class([
                'flex flex-1 items-center justify-center gap-[10px] border-b-2 px-4 py-4 text-[15px] font-medium transition focus:outline-none focus:ring-0',
                'border-[#1A3A6B] text-[#1A3A6B]' => $mode === 'edit',
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $mode !== 'edit',
            ])
            @if ($mode === 'edit') aria-current="page" @endif
        >
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Editar Rol
        </a>

        <a
            href="{{ route('administracion.role', ['tab' => 'delete']) }}"
            class="flex flex-1 items-center justify-center gap-[10px] border-b-2 border-transparent px-4 py-4 text-[15px] font-medium text-gray-500 transition hover:border-gray-300 hover:text-gray-700 focus:outline-none focus:ring-0"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0h8l-1-3H9L8 7z" />
            </svg>
            Eliminar Roles
        </a>
    </x-slot>

    <x-slot name="form">
        <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
            <div class="mb-[15px]">
                <h2 class="text-[15px] font-semibold text-gray-900">Información del rol</h2>
                <p class="mt-1 text-[15px] text-gray-500">Define la identidad del rol y conserva intactas las reglas de acceso vigentes.</p>
            </div>

            <div class="grid grid-cols-1 gap-[15px]">
                <div class="min-w-0">
                    <label for="role" class="block text-[15px] font-medium text-gray-700">Rol</label>
                    <input
                        id="role"
                        type="text"
                        maxlength="255"
                        wire:model.defer="role"
                        class="{{ $inputClasses }} {{ $isProtectedSystemRole ? 'cursor-not-allowed text-gray-500' : '' }}"
                        @readonly($isProtectedSystemRole)
                    >
                    <x-input-error for="role" class="mt-2 text-[15px]" />
                    @if ($isProtectedSystemRole)
                        <p class="mt-2 text-[15px] leading-6 text-amber-700">
                            El nombre de este rol forma parte de las reglas de acceso del sistema. Puedes actualizar su descripción sin renombrarlo.
                        </p>
                    @endif
                </div>

                <div class="min-w-0">
                    <label for="description" class="block text-[15px] font-medium text-gray-700">Descripción</label>
                    <textarea id="description" maxlength="255" rows="4" wire:model.defer="description" class="mt-2 block w-full resize-none rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 py-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0"></textarea>
                    <div class="mt-2 flex items-start justify-between gap-4">
                        <x-input-error for="description" class="text-[15px]" />
                        <span class="ml-auto shrink-0 text-[15px] text-gray-400">Máximo 255 caracteres</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-5 rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
            <div class="min-w-0">
                <h2 class="text-[15px] font-semibold text-gray-900">Permisos del rol</h2>
                <p class="mt-1 text-[15px] leading-6 text-gray-500">
                    Asigna los accesos dinámicos que correspondan. Si una clave se elimina del catálogo, deja de otorgar acceso sin modificar los usuarios asociados al rol.
                </p>
            </div>

            <div class="administration-form-scrollbar mt-[15px] max-h-72 overflow-y-auto pr-1">
                @forelse ($availablePermissions->groupBy(fn ($permission) => $permission->module ?: 'General') as $module => $modulePermissions)
                    <fieldset class="mb-[15px] rounded-xl border border-gray-200 bg-white p-4 last:mb-0">
                        <legend class="px-2 text-[15px] font-semibold text-[#1A3A6B]">{{ $module }}</legend>

                        <div class="grid grid-cols-1 gap-[10px] md:grid-cols-2">
                            @foreach ($modulePermissions as $permission)
                                <label
                                    wire:key="role-permission-{{ $permission->id }}"
                                    class="flex min-w-0 cursor-pointer items-start gap-[10px] rounded-lg border border-gray-200 bg-[#F3F3F3] p-3 transition hover:bg-gray-200"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $permission->id }}"
                                        wire:model.defer="permissionIds"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0"
                                    >
                                    <span class="min-w-0">
                                        <span class="block truncate text-[15px] font-medium text-gray-800" title="{{ $permission->name }}">
                                            {{ $permission->name }}
                                        </span>
                                        <span class="mt-1 block truncate text-[15px] text-gray-500" title="{{ $permission->description ?: $permission->key }}">
                                            {{ $permission->description ?: $permission->key }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <div class="rounded-xl border border-gray-200 bg-white p-5 text-center">
                        <p class="text-[15px] font-medium text-gray-700">No hay permisos activos en el catálogo.</p>
                        <p class="mt-2 text-[15px] text-gray-500">El rol se guardará sin accesos dinámicos asociados.</p>
                    </div>
                @endforelse
            </div>

            <x-input-error for="permissionIds" class="mt-2 text-[15px]" />
            <x-input-error for="permissionIds.*" class="mt-2 text-[15px]" />
        </section>
    </x-slot>

    <x-slot name="actions">
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
    </x-slot>
</x-administration-form-modal>
