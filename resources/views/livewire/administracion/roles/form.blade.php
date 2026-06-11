<div class="w-full">
    <x-form-custom submit="save">
        <x-slot name="title">{{ $mode === 'edit' ? 'Editar Role' : 'Crear Nuevo Role' }}</x-slot>

        <x-slot name="form">
            <div class="col-span-3">
                <x-label for="role" class="block">Rol</x-label>
                <x-input id="role" type="text" wire:model.defer="role" class="border rounded w-full" />
                <x-input-error for="role" class="mt-2" />
            </div>

            <div class="col-span-12">
                <x-label for="description" class="block">Descripción</x-label>
                <x-input id="description" type="text" wire:model.defer="description" class="border rounded w-full" />
                <x-input-error for="description" class="mt-2" />
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
