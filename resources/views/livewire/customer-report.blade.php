<div>
    @if ($notFound)
        <x-not-found></x-not-found>
    @else
        <div class="mx-auto w-full max-w-5xl bg-white p-8 text-gray-800 shadow-xl">

            @php
                $uniqueServiceIds = $customer->services->pluck('service_id')->unique();
            @endphp

            <!-- Encabezado del reporte -->
            <div class="mb-8 flex flex-col gap-1 border-b pb-6">
                <span
                    class="flex items-center gap-3 bg-gradient-to-r from-blue-800 via-purple-700 to-pink-600 bg-clip-text text-lg font-extrabold text-transparent">
                    @svg('feathericon-bar-chart', 'text-blue-600')
                    Desglose Anual
                </span>
                <span class="sm:text-1xl mt-1 text-xl font-semibold text-gray-800">
                    {{ $customer->name ?? 'Sin cliente' }}
                </span>
                <span class="text-sm text-gray-400 sm:text-base">
                    RFC: {{ $customer->rfc }}
                </span>
            </div>


            <!-- Tabla de meses y servicios -->
            @if ($customer->services->isNotEmpty())
                <div class="overflow-x-auto">
                    <div class="flex flex-col gap-6">
                        @foreach ($months as $month)
                            @php
                                // Solo el mes seleccionado estará abierto
                                $isOpen = $month['number'] == $selectedMonth;
                            @endphp

                            <!-- Acordeón por mes -->
                            <section x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }"
                                class="overflow-hidden rounded-2xl bg-gradient-to-b from-white via-blue-50 to-purple-50 shadow-sm transition-all">

                                <!-- Encabezado acordeón -->
                                <header @click="open = !open"
                                    class="flex cursor-pointer items-center justify-between rounded-t-2xl bg-blue-50 p-4 transition-colors hover:bg-blue-100">
                                    <h2
                                        class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-blue-800">
                                        {{ $month['name'] }}
                                        <span class="text-sm text-blue-600 sm:text-base"
                                            x-show="open" x-transition>
                                            @svg('feathericon-corner-right-up')
                                        </span>
                                        <span class="text-sm text-blue-600 sm:text-base"
                                            x-show="!open" x-transition>
                                            @svg('feathericon-corner-right-down')
                                        </span>
                                    </h2>
                                </header>

                                <!-- Contenido del acordeón -->
                                <div x-show="open" x-transition
                                    class="md:grid-cols-{{ max(2, $uniqueServiceIds->count()) }} grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                                    @foreach ($uniqueServiceIds as $serviceId)
                                        @php
                                            $inService = $this->services[$serviceId] ?? null;
                                            $percentageKey = "{$customer->id}-{$serviceId}-{$month['number']}";
                                        @endphp

                                        <div
                                            class="flex flex-col items-center space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-md transition-shadow hover:shadow-lg">
                                            <span
                                                class="block text-center text-sm font-semibold text-gray-800 sm:text-base">
                                                {{ $inService?->service ?? 'Sin servicio' }}
                                            </span>

                                            <div class="flex justify-center">
                                                <x-radial-progress :month="$month['number']"
                                                    :customerId="$customer->id" :percentageKey="$percentageKey"
                                                    :serviceId="$serviceId" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            @else
                <span>
                    No se encontraron servicios contratados.
                </span>
            @endif

            <!-- Footer del reporte -->
            <div class="mt-8 flex justify-end">
                <button wire:navigate href="{{ route('dashboard') }}"
                    class="bg-blue-800 px-6 py-2 text-white shadow-md transition-colors duration-200 hover:bg-blue-700">
                    Cerrar reporte
                </button>
            </div>
        </div>
    @endif

</div>
