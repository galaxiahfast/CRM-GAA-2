@php($assignmentDisabled = ! $selectedCustomer)

<div @class(['col-span-3 grid h-full grid-cols-3' => $cardActions])>
    @if ($cardActions)
        @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
            <button type="button" wire:click="openModal('{{ $tab }}')" wire:loading.attr="disabled" wire:target="openModal" class="inline-flex items-center justify-center gap-2 rounded-lg px-2 text-[13px] font-semibold text-white focus:outline-none disabled:opacity-60">
                @if ($tab === 'crear')<span class="text-lg">+</span>@elseif ($tab === 'editar')<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>@else<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13" /></svg>@endif
                {{ $label }}
            </button>
        @endforeach
    @endif

    @if ($embedded)
        @include('livewire.administracion.relationship._assignment-fields')
    @endif

    @if ($showModal)
        @teleport('body')
        <div>
            <x-administration-form-modal submit="save" cancel-action="closeModal" modal-id="assignment-management" :title="match ($mode) { 'editar' => 'Editar asignación', 'eliminar' => 'Eliminar asignación', default => 'Crear asignación' }" subtitle="Relaciona clientes, responsables y auxiliares." :carousel-style="true">
                <x-slot name="icon"><x-feathericon-git-merge class="h-6 w-6" /></x-slot>
                <x-slot name="form">@include('livewire.administracion.relationship._assignment-fields')</x-slot>
                <x-slot name="actions"><button type="button" wire:click="closeModal" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-white">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled($assignmentDisabled) class="inline-flex min-w-28 items-center justify-center rounded-lg bg-white px-5 py-3 font-semibold text-[#1A3A6B] disabled:opacity-50">{{ $mode === 'eliminar' ? 'Eliminar' : ($mode === 'editar' ? 'Actualizar' : 'Guardar') }}</button></x-slot>
            </x-administration-form-modal>
        </div>
        @endteleport
    @endif
</div>
