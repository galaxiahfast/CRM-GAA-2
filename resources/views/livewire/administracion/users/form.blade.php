@props(['isAuxiliar' => false])
<div class="w-full">
    <x-form-custom submit="save">
        <x-slot
            name="title">{{ $mode === 'edit' ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</x-slot>

        <x-slot name="form">
            <div class="col-span-6 sm:col-span-3">
                <x-label for="name" class="block">Nombres</x-label>
                <x-input id="name" type="text" maxlength="255" wire:model="name"
                    class="w-full rounded border" />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="last_name" class="block">Apellidos</x-label>
                <x-input id="last_name" type="text" maxlength="255" wire:model="last_name"
                    class="w-full rounded border" />
                <x-input-error for="last_name" class="mt-2" />
            </div>


            <div class="col-span-6">
                <x-label for="email" class="block">Correo Electrónico</x-label>
                <x-input id="email" maxlength="255" type="email" wire:model="email"
                    class="w-full rounded border" />
                <x-input-error for="email" class="mt-2" />
            </div>

            @if (!$user || !$user->exists)
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="password" class="block">Contraseña</x-label>
                    <x-input id="password" maxlength="255" type="password" wire:model="password"
                        class="w-full rounded border" />
                    <x-input-error for="password" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="password_confirmation" class="block">Confirmar
                        contraseña</x-label>
                    <x-input id="password_confirmation" maxlength="255" type="password"
                        wire:model="password_confirmation" class="w-full rounded border" />
                    <x-input-error for="password_confirmation" class="mt-2" />
                </div>
            @endif


            <div class="col-span-6">
                <x-label for="role_id" class="block">Rol</x-label>
                <select name="role_id" id="role_id" wire:model="role_id"
                    class="w-full rounded border">
                    <option selected disabled value="">Seleccione un rol</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->role }}</option>
                    @endforeach
                </select>
                <x-input-error for="role_id" class="mt-2" />
            </div>
        </x-slot>
        <x-slot name="actions">
            <x-secondary-button wire:click="cancel">
                Cancelar
            </x-secondary-button>

            <x-button>
                {{ $mode === 'edit' ? 'Actualizar' : 'Guardar' }}
            </x-button>
        </x-slot>
    </x-form-custom>
</div>
