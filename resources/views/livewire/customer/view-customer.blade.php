@php
    $role = auth()->user()->role->role;
@endphp

<div class="relative mt-[24px] p-8">
    @if (isset($notFound) && $notFound)
        <x-not-found></x-not-found>
    @else
        <!-- Tarjeta principal -->
        <div
            class="flex items-center justify-between rounded-lg border border-gray-200 bg-white bg-white-full p-6 shadow-md">
            <!-- Izquierda: Foto + Info -->
            <div class="flex items-center gap-6">
                <!-- Foto circular -->
                @if ($customer->url_photo)
                    <div
                        class="relative flex h-28 w-28 items-center justify-center rounded-full shadow">
                        <img class="absolute inset-0 h-full w-full rounded-full object-cover"
                            src="{{ asset('storage/photos/' . $customer->url_photo) }}">
                    </div>
                @else
                    <div
                        class="flex h-24 w-24 items-center justify-center rounded-full bg-blue-100 text-4xl font-bold text-blue-400 shadow ring-2">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                @endif

                @error('error')
                    <x-alert-message type="error">
                        {{ $messages }}
                    </x-alert-message>
                @enderror

                @error('pdfsValidate')
                    <x-alert-message type="error">
                        {{ $message }}
                    </x-alert-message>
                @enderror
                <!-- Datos -->
                <div>
                    <p class="text-xs text-gray-500">
                        RFC: {{ $customer->rfc }}
                    </p>
                    <h2 class="text-xl font-bold">{{ $customer->name }} @if ($customer->last_name)
                            {{ $customer->last_name }} {{ $customer->maternal_last_name }}
                        @endif
                    </h2>

                    <div>
                        @if ($customer->phone)
                            <span class="text-sm text-gray-600">
                                +{{ $customer->codePhone }} {{ $customer->phone }}
                            </span>
                        @endif
                        @if ($customer->email)
                            <span class="text-sm text-gray-600">
                                | {{ Str::limit($customer->email, 25) }}
                            </span>
                        @endif
                        @if ($customer->address)
                            <span class="text-sm text-gray-600">
                                | {{ Str::limit($customer->address, 22) }}
                            </span>
                        @endif
                    </div>

                    <!-- Servicios y contadores -->
                    <div class="relative mt-2 flex-col gap-4 rounded-sm bg-white px-4 py-2 shadow">
                        <div class="flex items-center gap-2">
                            @foreach ($customer->accountants->sortByDesc(fn($accountant) => $accountant->pivot->status) as $accountant)
                                <span
                                    class="{{ $accountant->pivot->status == 1 ? 'bg-green-500' : 'bg-blue-500' }} transition-scale cursor-default rounded-full p-1 px-2 text-xs uppercase text-white duration-200 hover:scale-105"
                                    data-tooltip-target="tooltip-{{ $accountant->id }}">
                                    {{ substr($accountant->name, 0, 2) }}
                                </span>
                                <x-tooltip id="tooltip-{{ $accountant->id }}"
                                    content="{{ $accountant->name }}" />
                            @endforeach
                        </div>
                        <span class="text-xs text-gray-900">Contadores asignados</span>
                    </div>
                </div>
            </div>

            <!-- Derecha: Fecha y progreso -->
            <div class="flex flex-col items-center gap-3">
                <div class="flex gap-2">
                    <select wire:model.lazy="selectedMonth"
                        class="rounded-lg border border-gray-300 py-1 text-sm">
                        @foreach ($months as $month)
                            <option value="{{ $month['number'] }}">
                                {{ $month['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <select wire:model.lazy="selectedYear"
                        class="rounded-lg border border-gray-300 py-1 text-sm">
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($selectedServiceId)
                    <p
                        class="{{ $percentage == 100 ? 'text-green-500' : 'text-gray-500' }} text-xs">
                        Formatos completados
                        {{ $countPDFs }}/{{ $totalPdfAvailable }}</p>
                @else
                    <p class="text-xs text-gray-500">Sin archivos</p>
                @endif

                <div
                    class="@if ($percentage == 0) border-red-500 text-red-500
                    @elseif ($percentage < 99) border-yellow-500 text-yellow-500
                    @elseif ($percentage == 100) border-green-500 text-green-500
                    @else border-gray-300 text-gray-500 @endif flex h-16 w-16 items-center justify-center rounded-full border text-sm font-semibold">
                    {{ $percentage }}%
                </div>
            </div>
        </div>

        <!-- Botones de impuestos -->
        <div class="mt-4">
            <div class="mt-6 flex gap-4">
                <!-- Botón 1 -->
                <div class="relative">
                    @if (empty($services) || count($services) === 0)
                        <span
                            class="relative cursor-default rounded-lg border border-gray-300 bg-blue-900 px-4 py-2 text-white transition-colors duration-200 hover:border-gray-500">
                            Sin Servicios seleccionados
                        </span>
                    @else
                        @foreach ($serviceRelation as $service)
                            <div class="relative inline-block">
                                <button
                                    wire:click="selectButton({{ $service->id }}, {{ $customer->id }})"
                                    class="{{ $selectedServiceId === $service->id ? 'bg-blue-900 text-white' : '' }} rounded-lg border border-gray-300 px-4 py-2 text-sm transition-colors duration-200 hover:border-gray-500">
                                    {{ $service->service }}
                                </button>
                                <div
                                    class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500 text-xs font-bold text-white">
                                    {{ $serviceCounts[$service->id] ?? 0 }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <ul
                    class="flex flex-wrap border-b border-gray-200 text-center text-sm font-medium text-gray-500">
                    @if ($subServicesCustomer && $subServicesCustomer->isNotEmpty())

                        @foreach ($subServicesCustomer as $subService)
                            <li class="me-2">
                                <a href="#"
                                    wire:click.prevent="selectSubServiceButton({{ $subService->id }})"
                                    class="{{ $selectedSubServices === $subService->id
                                        ? 'text-indigo-600 font-medium'
                                        : 'text-gray-500 hover:text-gray-700' }} duration-400 relative inline-block border-b-2 p-4 transition-all ease-[cubic-bezier(0.4,0,0.2,1)]">

                                    {{ $subService->sub_service }}
                                    <span
                                        class="{{ $selectedSubServices === $subService->id ? 'scale-x-100' : 'scale-x-0' }} absolute bottom-0 left-0 h-0.5 w-full bg-indigo-600 transition-all duration-[0.6s] ease-[cubic-bezier(0.4,0,0.2,1)]">
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

        <!-- Sección de Impuestos -->
        @if ($selectedServiceId)
            <div class="mt-8">
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex flex-col">
                        <span
                            class="font-semibold text-gray-700">{{ $selectedSubService->sub_service ?? 'null' }}</span>
                        <span
                            class="mb-4 text-sm text-gray-700">{{ $selectedSubService->description ?? 'null' }}</span>
                    </div>
                    @if ($selectedSubServiceId !== 1 && $selectedSubServiceId !== 6)
                        @php
                            $complementariaFiles = $pdfsDB['complementaria'][
                                $this->selectedSubServiceId
                            ] ?? [
                                'acuse' => collect(),
                                'comprobante' => collect(),
                            ];

                            $hasComplementaria =
                                $complementariaFiles['acuse']->isNotEmpty() ||
                                $complementariaFiles['comprobante']->isNotEmpty();
                            $buttonText =
                                $hasComplementaria || $isComplementaria ? 'Eliminar' : 'Agregar';
                        @endphp
                        <div>
                            <x-button wire:click="toggle()" @class([
                                'bg-gray-600 focus:bg-gray-500 hover:bg-gray-500 active:bg-gray-500' => $hasComplementaria,
                                'bg-red-600 hover:bg-red-400 focus:bg-red-400 active:bg-red-500' =>
                                    $isComplementaria || $hasComplementaria,
                            ])>
                                {{ $buttonText }}
                                Complementaria
                            </x-button>

                        </div>
                    @endif
                </div>
                @if ($selectedSubServiceId === 1)
                    @foreach ($states as $key => $state)
                        @php
                            $normalFiles = $pdfsDB['normal'][$state->id] ?? [
                                'acuse' => collect(),
                                'comprobante' => collect(),
                            ];
                            $complementariaFiles = $pdfsDB['complementaria'][$state->id] ?? [
                                'acuse' => collect(),
                                'comprobante' => collect(),
                            ];

                            $hasComplementaria =
                                $complementariaFiles['acuse']->isNotEmpty() ||
                                $complementariaFiles['comprobante']->isNotEmpty();
                            $isOpen = $this->isStateOpen($state->id);
                            $buttonText = $hasComplementaria || $isOpen ? 'Eliminar' : 'Agregar';
                        @endphp
                        <div class="mb-8">
                            <div class="mb-4 flex items-center justify-between">
                                <span
                                    class="font-semibold text-gray-700">{{ $state->name }}</span>
                                <x-button @class([
                                    'bg-gray-600 cursor-not-allowed hover:bg-gray-500 active:bg-gray-500 text-white disabled' => $hasComplementaria,
                                    'bg-red-600 hover:bg-red-400 focus:bg-red-400 active:bg-red-500 text-white' =>
                                        $isOpen || $hasComplementaria,
                                ])
                                    wire:click="toggleState({{ $state->id }})">
                                    {{ $buttonText }} complementaria
                                </x-button>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                                <!-- Archivos Normales -->
                                <x-format-pdf model="pdfs.normal.acuse" :pdf="$normalFiles['acuse']"
                                    :successMessage="$successMessage" fileType="Acuse" declarationType="Normal"
                                    :state="$state" />

                                <x-format-pdf model="pdfs.normal.comprobante" :pdf="$normalFiles['comprobante']"
                                    fileType="Comprobante" declarationType="Normal"
                                    :state="$state" :successMessage="$successMessage" :comprobante-normal-is-visible="$complementariaFiles['acuse']->isEmpty() &&
                                        $complementariaFiles['comprobante']->isEmpty()" />

                                <!-- Archivos Complementaria -->
                                @if ($hasComplementaria || $isOpen)
                                    <x-format-pdf model="pdfs.complementaria.acuse"
                                        :pdf="$complementariaFiles['acuse']" fileType="Acuse"
                                        declarationType="Complementaria" :state="$state" />

                                    <x-format-pdf model="pdfs.complementaria.comprobante"
                                        :pdf="$complementariaFiles['comprobante']" fileType="Comprobante"
                                        declarationType="Complementaria" :state="$state" />
                                @endif
                            </div>
                        </div>
                    @endforeach
                @elseif ($selectedSubServiceId === 6)
                    @foreach ($statements as $statement)
                        @php
                            $normalFiles = $pdfsDB['normal'][$statement->id] ?? [
                                'acuse' => collect(),
                                'comprobante' => collect(),
                            ];
                            $complementariaFiles = $pdfsDB['complementaria'][$statement->id] ?? [
                                'acuse' => collect(),
                                'comprobante' => collect(),
                            ];

                            $hasComplementaria =
                                $complementariaFiles['acuse']->isNotEmpty() ||
                                $complementariaFiles['comprobante']->isNotEmpty();
                            $isOpen = $this->isStatementOpen($statement->id);
                            $buttonText = $hasComplementaria || $isOpen ? 'Eliminar' : 'Agregar';
                        @endphp

                        <div class="mb-8">
                            <div class="mb-4 flex items-center justify-between">
                                <span
                                    class="font-semibold text-gray-700">{{ $statement->statement }}</span>
                                <x-button @class([
                                    'bg-gray-600 cursor-not-allowed hover:bg-gray-500 active:bg-gray-500 text-white disabled' => $hasComplementaria,
                                    'bg-red-600 hover:bg-red-400 focus:bg-red-400 text-white' =>
                                        $isOpen || $hasComplementaria,
                                ])
                                    wire:click="toggleStatement({{ $statement->id }})">
                                    {{ $buttonText }} complementaria
                                </x-button>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                                <!-- Archivos Normales -->

                                <x-format-pdf model="pdfs.normal.acuse" :pdf="$normalFiles['acuse']"
                                    :successMessage="$successMessage" fileType="Acuse" declarationType="Normal"
                                    :statement="$statement" />

                                <x-format-pdf model="pdfs.normal.comprobante" :pdf="$normalFiles['comprobante']"
                                    fileType="Comprobante" declarationType="Normal"
                                    :statement="$statement" :successMessage="$successMessage" :comprobante-normal-is-visible="$complementariaFiles['acuse']->isEmpty() &&
                                        $complementariaFiles['comprobante']->isEmpty()" />

                                <!-- Archivos Complementaria -->
                                @if ($hasComplementaria || $isOpen)
                                    <x-format-pdf model="pdfs.complementaria.acuse"
                                        :pdf="$complementariaFiles['acuse']" fileType="Acuse"
                                        declarationType="Complementaria" :statement="$statement" />

                                    <x-format-pdf model="pdfs.complementaria.comprobante"
                                        :pdf="$complementariaFiles['comprobante']" fileType="Comprobante"
                                        declarationType="Complementaria" :statement="$statement" />
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    @php
                        $normalFiles = $pdfsDB['normal'][$selectedSubServiceId] ?? [
                            'acuse' => collect(),
                            'comprobante' => collect(),
                        ];
                        $complementariaFiles = $pdfsDB['complementaria'][$selectedSubServiceId] ?? [
                            'acuse' => collect(),
                            'comprobante' => collect(),
                        ];
                    @endphp

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Archivos Normales -->
                        <x-format-pdf model="pdfs.normal.acuse" :pdf="$normalFiles['acuse']"
                            :successMessage="$successMessage" fileType="Acuse" declarationType="Normal" />

                        <x-format-pdf model="pdfs.normal.comprobante" :pdf="$normalFiles['comprobante']"
                            fileType="Comprobante" declarationType="Normal" :successMessage="$successMessage"
                            :comprobante-normal-is-visible="$complementariaFiles['acuse']->isEmpty() &&
                                $complementariaFiles['comprobante']->isEmpty()" />

                        <!-- Archivos Complementaria -->
                        @if (
                            $complementariaFiles['acuse']->isNotEmpty() ||
                                $complementariaFiles['comprobante']->isNotEmpty() ||
                                $isComplementaria)
                            <x-format-pdf model="pdfs.complementaria.acuse" :pdf="$complementariaFiles['acuse']"
                                fileType="Acuse" declarationType="Complementaria" />

                            <x-format-pdf model="pdfs.complementaria.comprobante" :pdf="$complementariaFiles['comprobante']"
                                fileType="Comprobante" declarationType="Complementaria" />
                        @endif
                    </div>
                @endif

                <div wire:loading wire:target="pdfs.normal.acuse">
                    <x-progress />
                </div>
                <div wire:loading wire:target="pdfs.normal.comprobante">
                    <x-progress />
                </div>

                <div wire:loading wire:target="pdfs.complementaria.acuse">
                    <x-progress />
                </div>
                <div wire:loading wire:target="pdfs.complementaria.comprobante">
                    <x-progress />
                </div>

                @if ($successMessage)
                    <x-toast message="{{ $successMessage }}" typeMessage="{{ $typeMessage }}"
                        icon="{{ $iconSVG }}" />
                @endif
            </div>
        @else
            <div
                class="relative flex h-[200px] items-center justify-center rounded-lg border border-gray-300 bg-white shadow-sm">
                <span
                    class="rounded-md border border-gray-200 bg-gray-50 px-4 py-2 text-base font-semibold text-gray-600 shadow-sm">
                    Sin servicios contratados seleccionados
                </span>
            </div>
        @endif
    @endif
</div>
