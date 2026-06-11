<div class="space-y-6 p-6">
    <!-- Tabs -->
    <div class="flex items-center justify-between">
        <div class="flex gap-4 border-b">
            <button class="border-b-2 border-black pb-2">Incompletos</button>
            <button class="pb-2 text-gray-500">Completos</button>
        </div>
        <div class="flex gap-2">
            <select wire:model.lazy="selectedMonth"
                class="rounded-lg border border-gray-300 py-1 text-sm">
                @foreach ($months as $item)
                    <option value="{{ $item['number'] }}">{{ $item['name'] }}</option>
                @endforeach
            </select>
            <select wire:model.lazy="selectedYear"
                class="rounded-lg border border-gray-300 py-1 text-sm">
                @foreach ($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
            <input wire:model.live.debounce.500ms="search" type="text"
                class="rounded-lg border border-gray-300 p-1 text-sm" placeholder="Buscar...">
        </div>
    </div>

    <!-- Top Stats -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="bg-white p-4 shadow-xl">
            <p class="text-sm text-gray-500">Archivos mensuales subidos</p>
            <p class="text-2xl font-semibold">{{ $totalFilesMonth }}</p>
        </div>

        <div class="bg-white p-4 shadow-xl">
            <p class="text-sm text-gray-500">Archivos anuales subidos </p>
            <p class="text-2xl font-semibold">{{ $totalFilesYear }}</p>
        </div>

        <div class="bg-white p-4 shadow-xl">
            <p class="text-sm text-gray-500">Archivos totales</p>
            <p class="text-2xl font-semibold">{{ $totalFiles }}</p>
            <p class="text-xs text-red-500">-8% mes a mes</p>
        </div>
    </div>



    <!-- Reporte de clientes -->
    <h2 class="text-lg font-semibold">Informe de clientes</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($customers as $customer)
            @php
                $uniqueServiceIds = $customer->services->pluck('service_id')->unique();
            @endphp
            <a href="{{ route('customers.view', $customer->id) }}"
                class="transition-scale relative cursor-pointer space-y-2 rounded-2xl bg-white p-4 shadow shadow-2xl duration-200 hover:scale-[1.01]">
                @if ($customer->services->isNotEmpty())
                    <div class="absolute right-0 top-0">
                        <x-dropdown align="right" width="30">
                            <x-slot name="trigger">
                                <button wire:click.prevent
                                    class="absolute right-4 top-4 cursor-default rounded-2xl border border-2 border-transparent text-gray-500 transition hover:border-blue-500">
                                    @svg('feathericon-more-vertical')
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <button wire:click.prevent='annualReport({{ $customer->id }})'
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Resumen
                                    anual</button>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endif
                <div class="flex">
                    @if ($customer['url_photo'])
                        <div
                            class="relative h-16 w-16 flex-shrink-0 items-center justify-center rounded-full">
                            <img class="absolute inset-0 h-full w-full rounded-full object-cover"
                                src="{{ asset('storage/photos/' . $customer['url_photo']) }}"
                                alt="{{ $customer['name'] }}">
                        </div>
                    @else
                        <div
                            class="text-1xl flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-400 shadow ring-2">
                            <span>{{ strtoupper(substr($customer['name'], 0, 2)) }}</span>
                        </div>
                    @endif

                    <div class="flex flex-col justify-between py-2 pl-2 pr-6">
                        <span class="text-sm font-medium">
                            {{ Str::limit($customer['name'], 50) }}</span>
                        <span class="text-xs text-gray-500">
                            {{ $customer['rfc'] }}</span>
                    </div>
                </div>

                @if ($customer->services->isNotEmpty())
                    <div class="flex justify-center">
                        @foreach ($uniqueServiceIds as $serviceId)
                            @php
                                $inService = $this->services[$serviceId] ?? null;
                                $percentageKey = "{$customer->id}-{$serviceId}";
                            @endphp
                            <div class="align-center flex flex-col text-xs text-gray-500">
                                <div class="p-2 text-center">
                                    <span>{{ $inService?->service ?? 'Sin servicio' }}</span>
                                </div>
                                <div class="flex flex-col gap-2 text-center">
                                    <x-radial-progress :month="$this->selectedMonth" :customerId="$customer->id"
                                        :percentageKey="$percentageKey" :serviceId="$serviceId" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div
                        class="flex flex-col items-center justify-center border border-red-600 py-6 text-center">
                        <span class="text-sm font-semibold text-red-600">
                            SIN SERVICIOS CONTRATADOS
                        </span>
                        <p class="mt-1 text-xs text-red-400">
                            Este cliente aún no tiene servicios activos.
                        </p>
                    </div>
                @endif
            </a>
        @endforeach


    </div>
</div>
