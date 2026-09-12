@php
    $customerOptions = $customers->map(fn ($customer) => ['id' => $customer->id, 'label' => mb_strtoupper(trim($customer->name.' '.$customer->last_name.' '.$customer->maternal_last_name)), 'meta' => mb_strtoupper((string) $customer->rfc)]);
    $accountantOptions = $accountants->map(fn ($accountant) => ['id' => $accountant->id, 'label' => mb_strtoupper(trim($accountant->name.' '.$accountant->last_name)), 'meta' => mb_strtoupper((string) $accountant->email)]);
    $assignmentDisabled = ! $selectedCustomer;
    $customerFullName = $selectedCustomerModel ? trim($selectedCustomerModel->name.' '.$selectedCustomerModel->last_name.' '.$selectedCustomerModel->maternal_last_name) : '';
@endphp

<div class="space-y-5">
    @if ($notice)<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-[15px] text-emerald-800">{{ $notice }}</div>@endif
    <section class="rounded-xl border border-[#CAD7E7] bg-transparent p-5">
        <h2 class="mb-[10px] text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">{{ $mode === 'crear' ? 'Cliente de la nueva asignación' : 'Seleccionar asignación' }}</h2>
        <label class="block text-[15px] font-medium text-gray-700">Cliente</label>
        <x-administration-search-picker input-id="assignment-customer" model="selectedCustomer" :selected="$selectedCustomer" :items="$customerOptions" placeholder="Buscar cliente por nombre o RFC..." empty-message="No se encontraron clientes." placement="bottom" />
        <x-input-error for="selectedCustomer" class="mt-2" />
    </section>

    <section class="rounded-xl border p-5 {{ $assignmentDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
        <h2 class="mb-[10px] text-[13px] font-bold uppercase tracking-[.12em] {{ $assignmentDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Responsable principal</h2>
        <label class="block text-[15px] font-medium text-gray-700">Contador o coordinador</label>
        <x-administration-search-picker input-id="assignment-accountant" model="selectedAccountantId" :selected="$selectedAccountantId" :items="$accountantOptions" placeholder="Buscar responsable..." empty-message="No se encontraron responsables." :disabled="$assignmentDisabled || $mode === 'eliminar'" placement="bottom" />
        <x-input-error for="selectedAccountantId" class="mt-2" />
    </section>

    <section class="rounded-xl border p-5 {{ $assignmentDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
        <div class="flex items-center justify-between gap-3"><h2 class="text-[13px] font-bold uppercase tracking-[.12em] {{ $assignmentDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Auxiliares asignados</h2><span class="rounded-full bg-[#DCE9F8] px-3 py-1 text-[11px] font-semibold text-[#1A3A6B]">{{ count($assignedInterns) }} seleccionados</span></div>
        <fieldset class="mt-[10px] grid grid-cols-1 gap-[10px] sm:grid-cols-2" @disabled($assignmentDisabled || $mode === 'eliminar')>
            @forelse ($interns as $intern)
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-[#B7CEEA] bg-white/45 p-3 text-[#102A52] hover:bg-[#EEF5FF]">
                    <span class="min-w-0"><span class="block truncate text-[14px] font-semibold">{{ mb_strtoupper(trim($intern->name.' '.$intern->last_name)) }}</span><span class="mt-1 block truncate text-[12px] text-[#55749D]">{{ mb_strtoupper($intern->email) }}</span></span>
                    <input type="checkbox" value="{{ $intern->id }}" wire:model="assignedInterns" class="h-4 w-4 shrink-0 rounded border-[#B7CEEA] text-[#1A3A6B] focus:ring-0">
                </label>
            @empty
                <p class="col-span-full py-4 text-center text-[14px] text-[#55749D]">No hay auxiliares disponibles.</p>
            @endforelse
        </fieldset>
    </section>

    @if ($mode === 'eliminar')
        <section class="rounded-xl border p-5 {{ $assignmentDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
            <h2 class="mb-[10px] text-[13px] font-bold uppercase tracking-[.12em] {{ $assignmentDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Confirmación de desvinculación</h2>
            <label class="block text-[15px] font-medium text-gray-700">Escribe manualmente el nombre completo: <strong>{{ mb_strtoupper($customerFullName) }}</strong></label>
            <input wire:model.defer="deleteConfirmation" autocomplete="off" onpaste="return false" @disabled($assignmentDisabled) class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] focus:border-[#B7CEEA] focus:outline-none focus:ring-0 disabled:bg-[#E8EBF0]">
            <x-input-error for="deleteConfirmation" class="mt-2" />
        </section>
    @endif
</div>
