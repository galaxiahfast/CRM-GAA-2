<div class="mx-auto w-full max-w-5xl bg-white p-8 text-gray-800 shadow-xl">
    @php
        $uniqueServiceIds = $customer->services->pluck('service_id')->unique();
    @endphp

    <!-- Encabezado del reporte -->
    <div class="mb-6 flex flex-col gap-1 border-b pb-4">
        <h1 class="text-2xl font-bold">📊 Desglose Anual</h1>
        <span class="text-lg font-semibold">{{ $customer->name ?? 'sin cliente' }}</span>
        <span class="text-sm text-gray-400">RFC: {{ $customer->rfc }}</span>
    </div>

    <!-- Tabla de meses y servicios -->
    @if ($customer->services->isNotEmpty())
        <div class="overflow-x-auto">
            <div class="grid grid-cols-1 gap-8">
                @foreach ($months as $month)
                    <div class="rounded-xl border bg-gray-50 p-4 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold uppercase tracking-wide text-blue-800">
                            {{ $month['name'] }}
                        </h2>

                        <div
                            class="md:grid-cols-{{ max(2, $uniqueServiceIds->count()) }} grid grid-cols-1 gap-6">
                            @foreach ($uniqueServiceIds as $serviceId)
                                @php
                                    $inService = $this->services[$serviceId] ?? null;
                                    $percentageKey = "{$customer->id}-{$serviceId}-{$month['number']}";

                                @endphp

                                <div
                                    class="flex flex-col items-center space-y-3 rounded-lg border bg-white p-4 shadow-sm">
                                    <div class="text-center">
                                        <span class="block text-sm font-semibold text-gray-800">
                                            {{ $inService?->service ?? 'Sin servicio' }}
                                        </span>

                                    </div>

                                    <div class="flex flex-col items-center">
                                        <x-radial-progress :month="$month['number']" :customerId="$customer->id"
                                            :percentageKey="$percentageKey" :serviceId="$serviceId" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
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
