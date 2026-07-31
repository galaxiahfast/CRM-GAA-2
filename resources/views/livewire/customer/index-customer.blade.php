@php
    $role = auth()->user()->role->role;
    $canManageCustomers = app(\App\Services\Authorization\PermissionAccessService::class)
        ->allows(auth()->user(), 'customers.manage');
@endphp

<div class="relative overflow-x-auto">
    <div
        class="flex-column flex flex-wrap items-center justify-between space-y-4 py-4 md:flex-row md:space-y-0">

        @foreach (session()->all() as $key => $message)
            @if (in_array($key, ['success', 'error', 'warning', 'info']))
                <x-alert-message type="{{ $key }}">
                    {{ $message }}
                </x-alert-message>
            @endif
        @endforeach

        <div class="relative ml-1">
            <div
                class="rtl:inset-r-0 pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <svg class="h-4 w-4 text-gray-500" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                </svg>
            </div>
            <input type="text" id="table-search-users" wire:model.live.debounce.500ms="search"
                class="block w-80 rounded-lg border border-gray-300 bg-gray-50 py-2 ps-10 text-sm text-gray-900 focus:border-matisse-500 focus:ring-matisse-500"
                placeholder="Busca un cliente">
        </div>
        @if ($canManageCustomers)
            <a href="{{ route('customers.create') }}"
                class="inline-flex cursor-pointer items-center rounded-md border border-transparent bg-matisse-900 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-matisse-600 focus:bg-matisse-700 focus:outline-none focus:ring-2 focus:ring-matisse-500 focus:ring-offset-2 active:bg-matisse-900 disabled:opacity-50">
                Agregar cliente
            </a>
        @endif
    </div>
    <x-table-structure>
        <x-slot name="head">
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">
                    Contador principal
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">
                    Nombre
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">
                    RFC
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">
                    Telefono
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">
                    Correo
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">
                    Dirección
                </div>
            </th>
            <th scope="col" class="px-6 py-3">
                <div class="flex items-center">Acciones</div>
            </th>
        </x-slot>

        <x-slot name="body">
            @foreach ($this->customers as $customer)
                <tr id="row-id-{{ $customer->id }}"
                    class="border-b border-gray-200 odd:bg-white even:bg-matisse-100">
                    <td data-tooltip-target="tooltip-accountant-{{ $customer->id }}"
                        class="px-6 py-4">
                        <div class="w-fit rounded-lg bg-green-400 p-1 px-2">
                            {{ $customer->accountants->map(fn($a) => $a->name)->join(', ') }}
                        </div>
                    </td>
                    <x-tooltip id="tooltip-accountant-{{ $customer->id }}"
                        content="{{ $customer->accountants->map(fn($a) => $a->name . ' ' . $a->last_name)->join(', ') }}" />

                    <td data-tooltip-target="tooltip-name-{{ $customer->id }}" class="px-6 py-4">
                        {{ Str::limit($customer->name, 15) }}</td>
                    <x-tooltip id="tooltip-name-{{ $customer->id }}"
                        content="{{ $customer->name }}" />
                    <td class="px-6 py-4">{{ $customer->rfc }}</td>
                    <td class="px-6 py-4">{{ '+' . $customer->codePhone . ' ' . $customer->phone }}
                    </td>
                    <td
                        @if ($customer->email) onclick="copyClipboard('{{ $customer->email }}')"
                        data-tooltip-target="tooltip-email-{{ $customer->id }}"
                        class="cursor-pointer px-6 py-4 hover:bg-gray-100"
                    @else
                        class="px-6 py-4" @endif>
                        {{ Str::limit($customer->email, 15) }}</td>
                    <x-tooltip id="tooltip-email-{{ $customer->id }}"
                        content="{{ $customer->email }}" />
                    <td
                        @if ($customer->address) onclick="copyClipboard('{{ $customer->address }}')"
                        data-tooltip-target="tooltip-address-{{ $customer->id }}"
                        class="cursor-pointer px-6 py-4 hover:bg-gray-100"
                    @else
                        class="px-6 py-4" @endif>
                        {{ Str::limit($customer->address, 15) }}
                    </td>
                    <x-tooltip id="tooltip-address-{{ $customer->id }}"
                        content="{{ $customer->address }}" />
                    <td class="flex gap-2 py-4">
                        <a data-tooltip-target="tooltip-view-{{ $customer->id }}"
                            href="{{ route('customers.view', $customer->id) }}"
                            class="text-green-700 hover:text-green-500">
                            <x-hugeicons-view />
                        </a>
                        <x-tooltip id="tooltip-view-{{ $customer->id }}"
                            content="Seguimiento del cliente" />

                        @if ($canManageCustomers)
                            <a data-tooltip-target="tooltip-edit-{{ $customer->id }}"
                                href="{{ route('customers.edit', $customer->id) }}"
                                class="text-yellow-700 hover:text-yellow-500">
                                <x-hugeicons-pencil-edit-02 />
                            </a>
                            <x-tooltip id="tooltip-edit-{{ $customer->id }}"
                                content="Editar cliente" />

                            <button data-tooltip-target="tooltip-delete-{{ $customer->id }}"
                                type="button" wire:click="destroy({{ $customer->id }})"
                                wire:confirm="¿Deseas eliminar este cliente?"
                                class="block w-full text-left text-sm capitalize text-red-700 hover:text-red-500">
                                <x-hugeicons-delete-02 />
                            </button>
                            <x-tooltip id="tooltip-delete-{{ $customer->id }}"
                                content="Eliminar cliente"></x-tooltip>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot>
    </x-table-structure>

    @if ($customers->isEmpty())
        <x-no-data click="renderToCreateCustomer()" title="Sin clientes"
            subTitle="Aún no hay clientes disponibles, agrega nuevos clientes para gestionarlos."
            titleButton="Agregar Cliente"></x-no-data>
    @endif

    <script>
        $(document).ready(function() {
            let table;

            if ($('#search-table').length) {
                table = $('#search-table').DataTable({
                    paging: true,
                    lengthChange: false,
                    pageLength: 10,
                    ordering: true,
                    searching: true,
                    info: false,
                    language: {
                        paginate: {
                            previous: 'Anterior',
                            next: 'Siguiente'
                        }
                    }
                });
            }


            const searchInput = document.getElementById("table-search-users");
            if (searchInput && table) {
                searchInput.addEventListener("input", function() {
                    table.search(this.value).draw();
                });
            }
        });

        function copyClipboard(text) {
            if (text) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        alert('Texto copiado')
                    })
                    .catch(err => {
                        console.error("Error al copiar: ", err);
                    })
            }
        }
    </script>

</div>
