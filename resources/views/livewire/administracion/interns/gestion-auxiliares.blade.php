<div>
    <div class="mb-4 mt-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-700">Gestión de auxiliares</h1>
            <p class="text-gray-500">Administra los auxiliares de la plataforma</p>
        </div>
        <div class="mt-4">
            <input type="text" wire:model.live.debounce.250ms="search"
                placeholder="Buscar auxiliar..." class="rounded-md border px-2 py-1">
            <x-a-button href="{{ route('administracion.create.users', ['Intern' => true]) }}"
                color="blue">Agregar
                auxiliar</x-a-button>
        </div>
    </div>
    <x-table-structure>
        <x-slot name="head">
            <tr>
                <th scope="col" class="px-6 py-3">Nombre</th>
                <th scope="col" class="px-6 py-3">Apellido</th>
                <th scope="col" class="px-6 py-3">Email</th>
                <th scope="col" class="px-6 py-3">Rol</th>
                <th scope="col" class="px-6 py-3">Creación</th>
                <th scope="col" class="px-6 py-3">Actualización</th>
                <th scope="col" class="px-6 py-3">Acciones</th>
            </tr>
        </x-slot>
        <x-slot name="body">
            @foreach ($users as $user)
                <tr class="border-b bg-white hover:bg-gray-50">
                    <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                        {{ $user['name'] }}</td>
                    <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                        {{ $user['last_name'] }}</td>
                    <td class="px-6 py-4">{{ Str::limit($user['email'], 25) }}</td>
                    <td class="px-6 py-4">{{ $user->role->role }}</td>
                    <td class="px-6 py-4">{{ Str::limit($user['created_at'], 10) }}</td>
                    <td class="px-6 py-4">{{ Str::limit($user['updated_at'], 10) }}</td>
                    <td class="px-6 py-4">
                        <x-a-button href="{{ route('administracion.edit.users', $user) }}"
                            color="blue">Editar</x-a-button>
                        <x-danger-button wire:click="delete({{ $user->id }})"
                            wire:confirm="¿Estás seguro de que deseas eliminar este usuario?"
                            color="red">Eliminar</x-danger-button>
                    </td>
                </tr>
            @endforeach
        </x-slot>
    </x-table-structure>
</div>
