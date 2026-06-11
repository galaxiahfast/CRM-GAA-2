<div>
    @foreach (session()->all() as $key => $message)
        @if (in_array($key, ['success', 'error', 'warning', 'info']))
            <x-alert-message type="{{ $key }}">
                {{ $message }}
            </x-alert-message>
        @endif
    @endforeach
    @error('combinado')
        <x-alert-message type="error">
            {{ $message }}
        </x-alert-message>
    @enderror
    <x-form-custom submit="updateCustomer">
        <x-slot name="title">
            <div class="h-18 flex justify-between">
                <div class="flex flex-col">
                    Actualizar cliente
                    <p><span class="text-xs text-gray-400">Datos del cliente</span></p>
                </div>
                <div class="flex flex-col">
                    <x-profile-photo :photo="$photo" :url="$url_photo" />
                </div>
            </div>
        </x-slot>
        <x-slot name="form">
            <div class="col-span-6">
                <x-label for="name" value="Nombre / Razón social" />
                <x-input id="name" oninput="nameUpperCase()" class="mt-1 block w-full"
                    wire:model.defer="name" maxlength="255" />
                <x-input-error for="name" class="mt-2" />
            </div>

            <x-confirmation-modal wire:model="showModal">
                <x-slot name="title">
                    Confirmar eliminación
                </x-slot>

                <x-slot name="content">
                    ¿Estás seguro de que deseas eliminar esta foto?
                </x-slot>

                <x-slot name="footer">
                    <div class="flex justify-end gap-2">
                        <x-button wire:click="clearPhotoDB">Eliminar</x-button>
                        <x-secondary-button
                            wire:click="$set('showModal', false)">Cancelar</x-secondary-button>
                    </div>
                </x-slot>
            </x-confirmation-modal>

            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center justify-between">
                    <x-label for="last_name" value="Apellido Paterno" />
                </div>

                <x-input id="last_name" class="mt-1 block w-full uppercase" maxlength="255"
                    wire:model.defer="last_name" />
                <x-input-error for="last_name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center justify-between">
                    <x-label for="maternal_last_name" value="Apellido Materno" />
                </div>

                <x-input id="maternal_last_name" class="mt-1 block w-full uppercase" maxlength="255"
                    wire:model.defer="maternal_last_name" />
                <x-input-error for="maternal_last_name" class="mt-2" />
            </div>

            <div class="col-span-6">
                <x-label for="email" value="Correo" />
                <x-input id="email" class="mt-1 block w-full" wire:model.defer="email"
                    maxlength="255" placeholder="ejemplo@gmail.com" />
                <x-input-error for="email" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <div class="flex items-center justify-between">
                    <x-label for="rfc" value="RFC" />
                    <p id="rfcCount" class="text-xs text-gray-400">0/13 caracteres</p>
                </div>

                <x-input id="rfc" class="mt-1 block w-full" maxlength="13"
                    wire:model.defer="rfc" oninput="rfcCount()" placeholder="ej. XAXX010101000" />
                <x-input-error for="rfc" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-phone :selected-country="$selectedCountry" :countries="$countries" />
            </div>

            <div class="col-span-6">
                <label for="address"
                    class="mb-2 block text-sm font-medium text-gray-900">Dirreción</label>
                <x-input id="address" wire:model.defer="address"
                    class="block w-full rounded-lg border border-gray-300 p-2.5 focus:border-matisse-500 focus:ring-matisse-500">
                </x-input>
            </div>

            <div class="col-span-6">
                <label for="observation"
                    class="mb-2 block text-sm font-medium text-gray-900">Observaciones</label>
                <textarea rows="4" id="observation" wire:model.defer="observation"
                    class="block w-full rounded-lg border border-gray-300 p-2.5 focus:border-matisse-500 focus:ring-matisse-500"></textarea>

            </div>
            <div class="col-span-6 mb-4">
                <label for="contadores_asignados"
                    class="mb-2 block text-sm font-medium text-gray-900">Contador
                    asignado</label>

                <div x-data="{
                    selected: @entangle('selectedAccountants'),
                    accountants: @js($accountants),
                    toggle(accountant) {
                        const exists = this.selected.find(item => item.id === accountant.id)
                        if (exists) {
                            this.selected = this.selected.filter(item => item.id !== accountant.id)
                        } else {
                            this.selected.push(accountant)
                        }
                        this.selected = this.selected.map((item, index) => ({
                            ...item,
                            status: index === 0
                        }))
                    }
                }" x-init="$watch('selected', value => { @this.set('selectedAccountants', value) })"
                    class="flex w-full flex-col gap-4 rounded-lg border border-solid bg-white-full p-4 md:flex-row">
                    <!-- Dropdown menu -->
                    <div class="w-full rounded-lg border border-solid bg-white-full md:w-1/2">
                        <div class="p-4">
                            <div class="mb-1">
                                <p class="text-sm font-medium text-gray-900">Seleccionar contador
                                </p>
                            </div>
                            <div id="dropdownSearch" class="z-10 rounded-lg border border-solid">
                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                        <svg class="h-4 w-4 text-gray-500" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2"
                                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="input-group-search"
                                        class="block w-full rounded-lg border-none bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Search user">
                                </div>

                                <ul class="h-48 overflow-y-auto px-3 pb-3 text-sm text-gray-700"
                                    aria-labelledby="dropdownSearchButton">
                                    @foreach ($accountants as $accountant)
                                        <li
                                            class="flex items-center rounded-sm p-2 hover:bg-gray-100">
                                            <input type="checkbox"
                                                :checked="selected.some(item => item.id ===
                                                    {{ $accountant->id }})"
                                                @change="toggle({ id: {{ $accountant->id }}, name: '{{ $accountant->name }}', email: '{{ $accountant->email }}' })"
                                                id="accountant-{{ $accountant->id }}"
                                                class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-blue-600 focus:ring-blue-500">
                                            <label for="accountant-{{ $accountant->id }}"
                                                class="ms-2 w-full rounded-sm text-sm font-medium text-gray-900">
                                                {{ $accountant->name }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Contador seleccionado -->
                    <div
                        class="flex w-full flex-col rounded-lg border border-solid bg-white-full md:w-1/2">
                        <div class="w-full p-4">
                            <div class="mb-1">
                                <p class="text-sm font-medium text-gray-900">Contadores
                                    seleccionados</p>
                            </div>
                            <div wire:sortable="updateAccountantOrder"
                                wire:sortable-group="accountants" class="flex flex-col gap-2">
                                @foreach ($selectedAccountants as $accountant)
                                    <div wire:sortable.item="{{ $accountant['id'] }}"
                                        wire:key="accountant-{{ $accountant['id'] }}"
                                        wire:sortable.handle
                                        class="{{ $accountant['status'] ? 'bg-green-100 text-green-800 border border-green-500' : 'bg-blue-100 text-blue-800' }} cursor-move rounded-lg px-3 py-2 text-sm font-medium shadow">
                                        <p>{{ $accountant['name'] }}</p>
                                        <p class="text-xs">{{ $accountant['email'] }}</p>
                                        @if ($accountant['status'])
                                            <span class="text-xs font-semibold text-green-600">✔
                                                Principal</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if (empty($selectedAccountants))
                                    <div class="flex h-64 items-center justify-center">
                                        <span class="text-sm text-red-600">Ningún contador
                                            seleccionado</span>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>

            </div>
            <div class="mb-1">
                <p><span class="text-xs font-bold text-gray-400 text-gray-900">Servicios
                        contratados</span></p>
            </div>

            {{-- Sección de selección de servicios contratados --}}
            @foreach ($services as $service)
                <x-contracted-services>
                    <x-slot name="title">

                        <label>
                            {{ $service['service'] }}
                        </label>
                    </x-slot>
                    <x-slot name="options">
                        {{-- Recorrido de los subservicios con validación a subservicios especiales --}}
                        @foreach ($service->subServices as $sub_service)
                            {{-- Validación al subservicio número 1 --}}
                            @if ($sub_service->id === 1)
                                <div class="relative inline-block">
                                    <x-button-select
                                        wire:click="handleClick({{ $sub_service->id }})"
                                        class="{{ in_array($sub_service->id, $selectedSubServices) ? 'bg-matisse-900 text-white ' : '' }} w-full">
                                        {{ $sub_service->sub_service }}
                                    </x-button-select>
                                    {{-- Diseño de contador al subservicio --}}
                                    @if (count($selectedStates) > 0)
                                        <span
                                            class="absolute right-1 top-1 rounded-full bg-red-500 px-2 py-1 text-xs font-bold text-white">
                                            {{ count($selectedStates) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Validación al subservicio número 6 --}}
                            @elseif ($sub_service->id === 6)
                                <div class="relative inline-block">
                                    <x-button-select
                                        wire:click="handleClick({{ $sub_service->id }})"
                                        class="{{ in_array($sub_service->id, $selectedSubServices) ? 'bg-matisse-900 text-white ' : '' }} w-full">
                                        {{ $sub_service->sub_service }}
                                    </x-button-select>
                                    {{-- Diseño de contador al subservicio --}}
                                    @if (count($selectedStatements) > 0)
                                        <span
                                            class="absolute right-1 top-1 rounded-full bg-red-500 px-2 py-1 text-xs font-bold text-white">
                                            {{ count($selectedStatements) }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <x-button-select wire:click="handleClick({{ $sub_service->id }})"
                                    class="{{ in_array($sub_service->id, $selectedSubServices) ? 'bg-matisse-900 text-white ' : '' }}">
                                    {{ $sub_service->sub_service }}
                                </x-button-select>
                            @endif
                        @endforeach
                        @if (count($service->subServices) === 0)
                            <span class="text-xs text-gray-500">No hay subservicios
                                disponibles</span>
                        @endif
                    </x-slot>
                </x-contracted-services>
            @endforeach

            {{-- Modal para servicios especiales --}}
            <x-dialog-modal wire:model="showSpecialModal" maxWidth="lg" id="specialModal">
                <x-slot name="title">

                    {{-- Validación para texto del modal --}}
                    @if ($specialModalId === 1)
                        Impuestos sobre nómina
                    @elseif ($specialModalId === 6)
                        Declaraciones
                    @endif
                </x-slot>
                <x-slot name="content">

                    {{-- Validación para el contenido de la modal número 1 --}}
                    @if ($specialModalId === 1)
                        <p class="mb-2 text-sm text-gray-700">Selecciona los estados:</p>
                        <div class="h-18 pb-2">
                            <select id="state" wire:model.defer="selectedState"
                                wire:change="addState">
                                <option value="initial" selected disabled>Catálogo de estados
                                </option>

                                {{-- Recorrido de los estados de México --}}
                                @foreach ($states as $state)
                                    <option value="{{ $state['key'] }}">
                                        {{ $state['key'] . ' - ' . $state['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-col gap-2 overflow-y-auto">
                            <p for="state" class="mb-2 text-xs">Estados seleccionados:</p>
                            <div class="grid grid-cols-6 gap-2">
                                {{-- Recorrido de la sección de estados seleccionados --}}
                                @foreach ($selectedStates as $clave)
                                    @php
                                        $state = collect($states)->firstWhere('key', $clave);
                                    @endphp
                                    <div wire:click="removeState('{{ $clave }}')"
                                        data-tooltip-target="tooltip-state-{{ $clave }}"
                                        class="cursor-no-drop rounded-lg border border-white bg-matisse-900 p-2 text-center text-white hover:bg-red-600">
                                        <span class="text-xs">{{ $clave }}</span>
                                    </div>
                                    <x-tooltip id="tooltip-state-{{ $clave }}"
                                        content="{{ $state['name'] ?? $clave }}" />
                                @endforeach

                            </div>
                        </div>
                    @endif

                    {{-- Validación para el contenido de la modal número 6 --}}
                    @if ($specialModalId === 6)
                        <p class="mb-2 text-sm text-gray-700">Selecciona las Declaraciones
                            (Impuestos):</p>
                        <div class="h-18 pb-2">
                            <select id="state" wire:model.defer="selectedStatement"
                                wire:change="addStatement">
                                <option value="initial" selected disabled>Catálogo de impuestos
                                </option>

                                {{-- Recorrido de los impuestos --}}
                                @foreach ($statements as $key)
                                    <option value="{{ $key['statement'] }}">
                                        {{ $key['statement'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-col gap-2 overflow-y-auto">
                            <p for="state" class="mb-2 text-xs">Impuestos seleccionados:</p>
                            <div
                                class="align-center grid grid-cols-3 justify-center gap-2 text-center">
                                {{-- Recorrido de la sección de impuestos seleccionados --}}
                                @foreach ($selectedStatements as $key)
                                    <div wire:click="removeStatement('{{ $key }}')"
                                        title="{{ $key['statement'] ?? $key }}"
                                        class="align-center cursor-no-drop justify-center rounded-lg border border-white bg-matisse-900 p-2 text-center text-white hover:bg-red-600">
                                        <span class="text-xs">{{ $key }}</span>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    @endif
                    {{-- Etapa de pruebas 
                    <x-button-select wire:click="subirImpuestosDB">
                        subir datos impuestos
                    </x-button-select> --}}
                </x-slot>
                <x-slot name="footer">
                    <x-button type="button" wire:click="$set('showSpecialModal', false)">
                        Cerrar
                    </x-button>

                </x-slot>
            </x-dialog-modal>

        </x-slot>

        <x-slot name="actions">
            <x-secondary-button wire:click="returnPage">
                Cancelar
            </x-secondary-button>

            <x-button>
                Actualizar
            </x-button>
        </x-slot>
        </x-form-section>

        <script>
            function nameUpperCase() {
                const inputField = document.getElementById('name');
                let value = inputField.value.toUpperCase();
                inputField.value = value;
            }

            function rfcCount() {
                const inputField = document.getElementById('rfc');
                const charCount = document.getElementById('rfcCount');
                const maxLength = inputField.getAttribute('maxlength');

                let value = inputField.value.replace(/[^A-Za-z0-9Ñ]/g, '').toUpperCase();
                inputField.value = value;
                charCount.textContent = `${value.length} / ${maxLength} caracteres`;
            }

            function phoneCount() {
                const inputField = document.getElementById('phone');
                const charCount = document.getElementById('phoneCount');
                const currentLength = inputField.value.length;
                const maxLength = inputField.getAttribute('maxlength');
                charCount.textContent = `${currentLength} / ${maxLength} caracteres`;
            }
        </script>
</div>
