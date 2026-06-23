<div>
    <div>
        <div class="mb-4 mt-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-semibold text-gray-700">Gestión de roles</h1>
                <p class="text-gray-500">Administra los roles de la plataforma</p>
            </div>
            <div class="mt-4">
                <input type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar rol..."
                    class="border rounded-md px-2 py-1">
                <x-a-button href="{{ route('administracion.role.create') }}" color="blue">Agregar rol</x-a-button>
            </div>
        </div>

        <x-table-structure>
            <x-slot name="head">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Rol</th>
                    <th scope="col" class="px-6 py-3">Descripción</th>
                    <th scope="col" class="px-6 py-3">Creación</th>
                    <th scope="col" class="px-6 py-3">Actualización</th>
                    <th scope="col" class="px-6 py-3">Acciones</th>
                </tr>
            </x-slot>
            <x-slot name="body">
                @foreach ($roles as $role)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $role['id'] }}</td>
                        <td class="px-6 py-4">{{ $role['role'] }}</td>
                        <td class="px-6 py-4">{{ Str::limit($role['description'], 50) }}</td>
                        <td class="px-6 py-4">{{ $role['created_at'] }}</td>
                        <td class="px-6 py-4">{{ $role['updated_at'] }}</td>
                        <td class="px-6 py-4">
                            <x-a-button href="{{ route('administracion.role.edit', $role['id']) }}"
                                color="blue">Editar</x-a-button>
                            <x-danger-button wire:click="deleteRole({{ $role['id'] }})"
                                wire:confirm="¿Estás seguro de que deseas eliminar este rol?"
                                color="red">Eliminar</x-danger-button>
                        </td>
                    </tr>
                @endforeach

            </x-slot>
        </x-table-structure>
    </div>
