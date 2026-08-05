@php
    $inputClasses = 'mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0';
    $textareaClasses = 'mt-2 block w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 py-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0';

    $customerSearchOptions = $customers->map(static function ($customer): array {
        $name = trim($customer->name.' '.$customer->last_name.' '.$customer->maternal_last_name);
        $rfc = trim((string) $customer->rfc);

        return [
            'id' => (int) $customer->id,
            'label' => $rfc !== '' ? $name.' — '.$rfc : $name,
        ];
    })->values();

    $accountantSearchOptions = $availableAccountants->map(static function ($accountant): array {
        $name = trim($accountant->name.' '.$accountant->last_name);
        $email = trim((string) $accountant->email);

        return [
            'id' => (int) $accountant->id,
            'label' => $email !== '' ? $name.' — '.$email : $name,
        ];
    })->values();

    $selectedCustomerSearchLabel = $customerSearchOptions->firstWhere('id', (int) $selectedCustomerId)['label'] ?? '';
    $selectedAccountantSearchLabel = $accountantSearchOptions->firstWhere('id', (int) $principalAccountantId)['label'] ?? '';
@endphp

<div>
    <button
        type="button"
        role="menuitem"
        wire:click="openModal"
        wire:loading.attr="disabled"
        wire:target="openModal"
        @click="open = false"
        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-[15px] font-medium text-gray-700 transition hover:bg-gray-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60"
    >
        <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm10-3v6m3-3h-6" />
        </svg>
        <span>Agregar Cliente</span>
    </button>

    @if ($showModal)
        @teleport('body')
            <div>
                <x-administration-form-modal
                    submit="save"
                    cancel-action="closeModal"
                    modal-id="customer-catalog-management"
                    title="Gestión de clientes"
                    subtitle="Crea, edita o elimina clientes sin afectar su historial."
                >
                    <x-slot name="icon">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm10-3v6m3-3h-6" />
                        </svg>
                    </x-slot>

                    <x-slot name="navigation">
                        @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
                            <button
                                type="button"
                                wire:click="setActiveTab('{{ $tab }}')"
                                class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $activeTab === $tab ? ($tab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </x-slot>

                    <x-slot name="form">
                        <div class="space-y-5">
                            @if ($notice)
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-[15px] text-emerald-800" role="status">
                                    {{ $notice }}
                                </div>
                            @endif

                            @error('catalog')
                                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-[15px] text-red-700" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror

                            @if (in_array($activeTab, ['editar', 'eliminar'], true))
                                <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5 shadow-sm">
                                    <label for="managed-customer-id" class="block text-[15px] font-medium text-gray-700">
                                        Cliente
                                    </label>
                                    <div
                                        class="relative mt-2"
                                        x-data="{
                                            open: false,
                                            query: '',
                                            options: @js($customerSearchOptions),
                                            selectedId: @js($selectedCustomerId),
                                            selectedLabel: @js($selectedCustomerSearchLabel),
                                            normalize(value) {
                                                return String(value ?? '')
                                                    .normalize('NFD')
                                                    .replace(/[\u0300-\u036f]/g, '')
                                                    .toLowerCase();
                                            },
                                            openList() {
                                                this.query = '';
                                                this.open = true;
                                            },
                                            selectOption(option) {
                                                this.selectedId = option.id;
                                                this.selectedLabel = option.label;
                                                this.query = '';
                                                this.open = false;
                                                $wire.set('selectedCustomerId', option.id);
                                            },
                                            get filteredOptions() {
                                                const term = this.normalize(this.query);

                                                return term === ''
                                                    ? this.options
                                                    : this.options.filter(option => this.normalize(option.label).includes(term));
                                            },
                                        }"
                                        @click.outside="open = false; query = ''"
                                        @customer-catalog-updated.window="if ($event.detail.action === 'deleted') { options = options.filter(option => String(option.id) !== String($event.detail.customerId)); selectedId = null; selectedLabel = ''; query = ''; open = false; }"
                                    >
                                        <input
                                            id="managed-customer-id"
                                            x-ref="customerSearchInput"
                                            data-catalog-search="customer"
                                            type="text"
                                            autocomplete="off"
                                            :value="open ? query : selectedLabel"
                                            @focus="openList()"
                                            @click="openList()"
                                            @input="query = $event.target.value; open = true"
                                            @keydown.escape.stop="open = false; query = ''; $event.target.blur()"
                                            @keydown.enter.prevent="if (filteredOptions.length) selectOption(filteredOptions[0])"
                                            class="{{ $inputClasses }} !mt-0 pr-12 {{ $activeTab === 'eliminar' ? 'focus:border-red-500' : '' }}"
                                            placeholder="Buscar cliente por nombre o RFC"
                                            role="combobox"
                                            aria-autocomplete="list"
                                            aria-controls="managed-customer-options-{{ $activeTab }}"
                                            :aria-expanded="open"
                                        >

                                        <button
                                            type="button"
                                            class="absolute right-0 top-0 flex h-11 w-12 items-center justify-center text-gray-500 hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                            @click="open ? (open = false, query = '') : openList(); if (open) $nextTick(() => $refs.customerSearchInput.focus())"
                                            aria-label="Mostrar clientes"
                                        >
                                            <svg class="h-4 w-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div
                                            id="managed-customer-options-{{ $activeTab }}"
                                            wire:ignore
                                            x-cloak
                                            x-show="open"
                                            class="administration-form-scrollbar absolute z-[70] mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                            role="listbox"
                                            aria-label="Clientes disponibles"
                                        >
                                            <template x-for="option in filteredOptions" :key="option.id">
                                                <button
                                                    type="button"
                                                    @click="selectOption(option)"
                                                    :class="String(selectedId) === String(option.id) ? 'bg-blue-50 font-semibold text-[#1A3A6B]' : 'text-gray-700 hover:bg-gray-50'"
                                                    class="block w-full rounded-lg px-4 py-3 text-left text-[15px] focus:bg-blue-50 focus:outline-none focus:ring-0"
                                                    role="option"
                                                    :aria-selected="String(selectedId) === String(option.id)"
                                                >
                                                    <span class="block truncate" x-text="option.label"></span>
                                                </button>
                                            </template>

                                            <p x-show="filteredOptions.length === 0" class="px-4 py-3 text-[15px] text-gray-500">
                                                No se encontraron clientes.
                                            </p>
                                        </div>
                                    </div>
                                    <x-input-error for="selectedCustomerId" class="mt-2 text-[15px]" />
                                </section>
                            @endif

                            @if ($activeTab === 'crear' || ($activeTab === 'editar' && $selectedCustomerId))
                                <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5 shadow-sm">
                                    <div class="mb-5">
                                        <h3 class="text-[15px] font-semibold text-gray-900">Datos del cliente</h3>
                                        <p class="mt-2 text-[15px] text-gray-500">Información fiscal y de contacto del registro.</p>
                                    </div>

                                    <div class="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <label for="customer-name" class="block text-[15px] font-medium text-gray-700">Nombre o razón social</label>
                                            <input id="customer-name" type="text" maxlength="255" wire:model.defer="name" class="{{ $inputClasses }}" autocomplete="off">
                                            <x-input-error for="name" class="mt-2 text-[15px]" />
                                        </div>
                                        <div>
                                            <label for="customer-rfc" class="block text-[15px] font-medium text-gray-700">RFC</label>
                                            <input id="customer-rfc" type="text" maxlength="15" wire:model.defer="rfc" class="{{ $inputClasses }} uppercase" autocomplete="off">
                                            <x-input-error for="rfc" class="mt-2 text-[15px]" />
                                        </div>
                                        <div>
                                            <label for="customer-last-name" class="block text-[15px] font-medium text-gray-700">Apellido paterno</label>
                                            <input id="customer-last-name" type="text" maxlength="255" wire:model.defer="lastName" class="{{ $inputClasses }}" autocomplete="off">
                                            <x-input-error for="lastName" class="mt-2 text-[15px]" />
                                        </div>
                                        <div>
                                            <label for="customer-maternal-name" class="block text-[15px] font-medium text-gray-700">Apellido materno</label>
                                            <input id="customer-maternal-name" type="text" maxlength="255" wire:model.defer="maternalLastName" class="{{ $inputClasses }}" autocomplete="off">
                                            <x-input-error for="maternalLastName" class="mt-2 text-[15px]" />
                                        </div>
                                        <div>
                                            <label for="customer-email" class="block text-[15px] font-medium text-gray-700">Correo electrónico</label>
                                            <input id="customer-email" type="email" maxlength="255" wire:model.defer="email" class="{{ $inputClasses }}" autocomplete="off">
                                            <x-input-error for="email" class="mt-2 text-[15px]" />
                                        </div>
                                        <div>
                                            <label for="customer-phone" class="block text-[15px] font-medium text-gray-700">Teléfono</label>
                                            <div class="mt-2 grid grid-cols-[90px_minmax(0,1fr)] gap-2">
                                                <div class="relative">
                                                    <span data-customer-phone-prefix class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[15px] text-gray-500" aria-hidden="true">+</span>
                                                    <input id="customer-code-phone" type="text" inputmode="numeric" maxlength="4" wire:model.defer="codePhone" class="block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] pl-7 pr-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Código de país">
                                                </div>
                                                <input id="customer-phone" type="text" maxlength="15" wire:model.defer="phone" class="block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0" autocomplete="off">
                                            </div>
                                            <x-input-error for="codePhone" class="mt-2 text-[15px]" />
                                            <x-input-error for="phone" class="mt-2 text-[15px]" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="customer-address" class="block text-[15px] font-medium text-gray-700">Dirección</label>
                                            <input id="customer-address" type="text" maxlength="255" wire:model.defer="address" class="{{ $inputClasses }}" autocomplete="off">
                                            <x-input-error for="address" class="mt-2 text-[15px]" />
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="customer-observation" class="block text-[15px] font-medium text-gray-700">Observaciones</label>
                                            <textarea id="customer-observation" rows="3" maxlength="225" wire:model.defer="observation" class="{{ $textareaClasses }}"></textarea>
                                            <x-input-error for="observation" class="mt-2 text-[15px]" />
                                        </div>
                                    </div>
                                </section>

                                @if ($activeTab === 'crear')
                                    <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5 shadow-sm">
                                        <h3 class="text-[15px] font-semibold text-gray-900">Asignación inicial</h3>
                                        <p class="mt-2 text-[15px] text-gray-500">Todo cliente debe conservar un contador principal.</p>

                                        <label for="customer-principal-accountant" class="mt-5 block text-[15px] font-medium text-gray-700">Contador principal</label>
                                        <div
                                            class="relative mt-2"
                                            x-data="{
                                                open: false,
                                                query: '',
                                                options: @js($accountantSearchOptions),
                                                selectedId: @js($principalAccountantId),
                                                selectedLabel: @js($selectedAccountantSearchLabel),
                                                normalize(value) {
                                                    return String(value ?? '')
                                                        .normalize('NFD')
                                                        .replace(/[\u0300-\u036f]/g, '')
                                                        .toLowerCase();
                                                },
                                                openList() {
                                                    this.query = '';
                                                    this.open = true;
                                                },
                                                selectOption(option) {
                                                    this.selectedId = option.id;
                                                    this.selectedLabel = option.label;
                                                    this.query = '';
                                                    this.open = false;
                                                    $wire.set('principalAccountantId', option.id);
                                                },
                                                get filteredOptions() {
                                                    const term = this.normalize(this.query);

                                                    return term === ''
                                                        ? this.options
                                                        : this.options.filter(option => this.normalize(option.label).includes(term));
                                                },
                                            }"
                                            @click.outside="open = false; query = ''"
                                            @customer-catalog-updated.window="if ($event.detail.action === 'created') { selectedId = null; selectedLabel = ''; query = ''; open = false; }"
                                        >
                                            <input
                                                id="customer-principal-accountant"
                                                x-ref="accountantSearchInput"
                                                data-catalog-search="accountant"
                                                type="text"
                                                autocomplete="off"
                                                :value="open ? query : selectedLabel"
                                                @focus="openList()"
                                                @click="openList()"
                                                @input="query = $event.target.value; open = true"
                                                @keydown.escape.stop="open = false; query = ''; $event.target.blur()"
                                                @keydown.enter.prevent="if (filteredOptions.length) selectOption(filteredOptions[0])"
                                                class="{{ $inputClasses }} !mt-0 pr-12"
                                                placeholder="Buscar contador por nombre o correo"
                                                role="combobox"
                                                aria-autocomplete="list"
                                                aria-controls="customer-principal-accountant-options"
                                                :aria-expanded="open"
                                            >

                                            <button
                                                type="button"
                                                class="absolute right-0 top-0 flex h-11 w-12 items-center justify-center text-gray-500 hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                                @click="open ? (open = false, query = '') : openList(); if (open) $nextTick(() => $refs.accountantSearchInput.focus())"
                                                aria-label="Mostrar contadores"
                                            >
                                                <svg class="h-4 w-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <div
                                                id="customer-principal-accountant-options"
                                                wire:ignore
                                                x-cloak
                                                x-show="open"
                                                class="administration-form-scrollbar absolute z-[70] mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                                role="listbox"
                                                aria-label="Contadores disponibles"
                                            >
                                                <template x-for="option in filteredOptions" :key="option.id">
                                                    <button
                                                        type="button"
                                                        @click="selectOption(option)"
                                                        :class="String(selectedId) === String(option.id) ? 'bg-blue-50 font-semibold text-[#1A3A6B]' : 'text-gray-700 hover:bg-gray-50'"
                                                        class="block w-full rounded-lg px-4 py-3 text-left text-[15px] focus:bg-blue-50 focus:outline-none focus:ring-0"
                                                        role="option"
                                                        :aria-selected="String(selectedId) === String(option.id)"
                                                    >
                                                        <span class="block truncate" x-text="option.label"></span>
                                                    </button>
                                                </template>

                                                <p x-show="filteredOptions.length === 0" class="px-4 py-3 text-[15px] text-gray-500">
                                                    No se encontraron contadores.
                                                </p>
                                            </div>
                                        </div>
                                        <x-input-error for="principalAccountantId" class="mt-2 text-[15px]" />

                                        @if ($availableAccountants->isEmpty())
                                            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-[15px] text-amber-800">
                                                Primero debe existir al menos un usuario con rol Contador o Coordinador.
                                            </p>
                                        @endif
                                    </section>
                                @else
                                    <section class="rounded-xl border border-blue-100 bg-blue-50 p-5 text-[15px] text-blue-900">
                                        <p>Las asignaciones, servicios y documentos existentes se conservarán sin cambios.</p>
                                        <a href="{{ route('customers.edit', $selectedCustomerId) }}" class="mt-3 inline-flex font-medium text-[#1A3A6B] underline underline-offset-4">
                                            Abrir configuración completa del cliente
                                        </a>
                                    </section>
                                @endif
                            @elseif ($activeTab === 'editar')
                                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5 text-[15px] text-blue-900">
                                    Selecciona el cliente cuyos datos deseas modificar.
                                </div>
                            @endif

                            @if ($activeTab === 'eliminar')
                                <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-[15px] text-red-800">
                                    <p class="font-semibold">Eliminación segura</p>
                                    <p class="mt-2">El cliente dejará de aparecer en listados y selectores, pero sus documentos, asignaciones e historial de horas permanecerán intactos.</p>
                                    @if ($selectedCustomer)
                                        <div class="mt-4 rounded-lg border border-red-200 bg-white p-4 text-gray-700">
                                            <p class="font-medium text-gray-900">{{ trim($selectedCustomer->name.' '.$selectedCustomer->last_name.' '.$selectedCustomer->maternal_last_name) }}</p>
                                            <p class="mt-1">RFC: {{ $selectedCustomer->rfc }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </x-slot>

                    <x-slot name="actions">
                        <button type="button" wire:click="closeModal" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="inline-flex min-w-28 items-center justify-center rounded-lg px-5 py-3 text-[15px] font-medium text-white transition focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60 {{ $activeTab === 'eliminar' ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1A3A6B] hover:bg-[#15305a]' }}"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $activeTab === 'crear' ? 'Guardar' : ($activeTab === 'editar' ? 'Guardar cambios' : 'Eliminar') }}
                            </span>
                            <span wire:loading wire:target="save">Procesando...</span>
                        </button>
                    </x-slot>
                </x-administration-form-modal>
            </div>
        @endteleport
    @endif
</div>
