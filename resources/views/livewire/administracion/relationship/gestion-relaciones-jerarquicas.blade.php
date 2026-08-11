<div class="h-auto rounded-2xl border border-gray-200 bg-gray-50 p-8">
    <div class="flex items-start justify-between gap-6">

        {{-- Panel de Clientes --}}
        <div class="w-1/2">
            <p class="mb-2 text-sm text-gray-500">{{ auth()->user()->email }}</p>
            <h2 class="text-2xl font-bold text-gray-800">Clientes</h2>
            <p class="mb-6 text-sm text-gray-500">Lista de tus clientes asignados</p>

            <div class="space-y-3">
                {{-- Cliente individual --}}
                @if ($customers)
                    @foreach ($customers as $customer)
                        @php
                            $hasInterns = in_array($customer->id, $customersWithInterns);
                            $isSelected = $selectedCustomer === $customer->id;
                        @endphp
                        <a wire:click='selectCustomer({{ $customer->id }})'
                            class="{{ $isSelected
                                ? 'border-blue-500 ring-blue-300 bg-blue-50'
                                : ($hasInterns
                                    ? 'border-green-500 bg-green-100'
                                    : 'border-gray-300 bg-white') }} flex cursor-pointer flex-col rounded-xl border p-3 transition hover:shadow">
                            <div class="flex flex-col">
                                <span
                                    class="{{ $isSelected ? 'bg-blue-400 text-gray-800' : 'bg-green-400 text-gray-900' }} rounded-lg px-3 py-1 text-sm font-semibold">
                                    {{ $customer->name }}
                                </span>
                                <span
                                    class="{{ $isSelected ? 'text-blue-600' : ($hasInterns ? 'text-green-600' : 'text-gray-600') }} mt-1 text-xs">
                                    {{ $customer->rfc }}
                                </span>
                                <span
                                    class="{{ $isSelected ? 'text-blue-600' : ($hasInterns ? 'text-green-600' : 'text-gray-600') }} mt-1 text-xs">
                                    Porcentage actual: {{ $customer->percentage_period }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                    <div class="mt-4 flex justify-center text-sm">
                        <div class="scale-90">
                            {{ $customers->links() }} </div>
                    </div>
                @else
                    <span>
                        Sin clientes asignados
                    </span>
                @endif
            </div>
        </div>


        {{-- Panel de Auxiliares --}}
        <div class="flex h-full w-1/2 flex-col">
            <div class="mb-4 w-full rounded-2xl border border-gray-200 bg-white p-4 shadow">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-800">Auxiliares</h2>
                    <x-a-button href="{{ route('administracion.interns') }}">Ver</x-a-button>
                </div>
                <p class="text-sm text-gray-500">Lista de tus auxiliares registrados</p>
            </div>

            @if ($selectedCustomer)
                <div
                    class="space-y-3 rounded-lg border bg-white-full p-4 transition-all duration-300">
                    @forelse ($this->interns as $intern)
                        @php
                            $isAssigned = in_array($intern->id, $assignedInterns);
                        @endphp

                        <label
                            class="{{ $isAssigned ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-white' }} flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all duration-200 hover:shadow-md">
                            <div>
                                <p
                                    class="{{ $isAssigned ? 'text-blue-800' : 'text-gray-800' }} font-semibold">
                                    {{ $intern->name }} {{ $intern->last_name }}
                                </p>
                                <p
                                    class="{{ $isAssigned ? 'text-blue-600' : 'text-gray-500' }} text-sm">
                                    {{ $intern->email }}
                                </p>
                            </div>
                            <input type="checkbox"
                                class="peer hidden h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                wire:click='registerInternToCustomer({{ $intern->id }})' />
                        </label>
                    @empty
                        <div class="p-4 text-center text-sm italic text-gray-500">
                            Sin auxiliares creados
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
