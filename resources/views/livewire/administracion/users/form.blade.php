@props(['isAuxiliar' => false])
<div class="w-full">
    <x-form-custom submit="save">
        <x-slot name="title">{{ $mode === 'edit' ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</x-slot>

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
                    <x-label for="password_confirmation" class="block">Confirmar contraseña</x-label>
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

            <!-- 🆕 Campos Añadidos: ID Checador, Precio Hora y Apoyo Comida -->
            <div class="col-span-6">
                <hr class="my-3 border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Configuración del Checador y Nómina</span>
            </div>

            <div class="col-span-6">
                <x-label for="employee_id" class="block">ID Checador (Hikvision)</x-label>
                <x-input id="employee_id" type="text" maxlength="50" wire:model="employee_id"
                    class="w-full rounded border" placeholder="Ej: 1024" />
                <x-input-error for="employee_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="hourly_rate" class="block">Precio por Hora ($)</x-label>
                <x-input id="hourly_rate" type="number" step="0.01" wire:model="hourly_rate"
                    class="w-full rounded border" />
                <x-input-error for="hourly_rate" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="food_allowance" class="block">Apoyo de Comida por Día ($)</x-label>
                <x-input id="food_allowance" type="number" step="0.01" wire:model="food_allowance"
                    class="w-full rounded border" />
                <x-input-error for="food_allowance" class="mt-2" />
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