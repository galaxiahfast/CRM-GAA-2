@php
    $fieldsDisabled = in_array($activeTab, ['editar', 'eliminar'], true) && ! $selectedGroupId;
    $title = match ($activeTab) { 'editar' => 'Editar grupo de permisos', 'eliminar' => 'Eliminar grupo de permisos', default => 'Crear grupo de permisos' };
    $groupOptions = $groups->map(fn ($group) => [
        'id' => $group->id,
        'label' => mb_strtoupper($group->name),
        'meta' => $group->permissions_count.' APARTADOS · '.$group->roles_count.' ROLES',
    ]);
    $selectedGroup = $selectedGroupId ? $groups->firstWhere('id', $selectedGroupId) : null;
    $input = 'mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] shadow-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]';
@endphp

<div @class(['col-span-3 grid h-full grid-cols-3' => $cardActions])>
    @if ($cardActions)
        @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
            <button type="button" wire:click="openModal('{{ $tab }}')" wire:loading.attr="disabled" wire:target="openModal" class="inline-flex items-center justify-center gap-2 rounded-lg px-2 text-[13px] font-semibold text-white focus:outline-none disabled:opacity-60">
                @if ($tab === 'crear')<span class="text-lg">+</span>@elseif ($tab === 'editar')<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>@else<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13" /></svg>@endif
                {{ $label }}
            </button>
        @endforeach
    @endif

    @if ($showModal)
        @teleport('body')
        <div>
            <x-administration-form-modal submit="save" cancel-action="closeModal" modal-id="permission-catalog-management" :title="$title" subtitle="Define grupos reutilizables y asígnalos después a los roles." :carousel-style="true">
                <x-slot name="icon"><x-feathericon-shield class="h-6 w-6" /></x-slot>
                <x-slot name="form">
                    <div class="space-y-5">
                        @if ($notice)<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ $notice }}</div>@endif

                        @if (in_array($activeTab, ['editar', 'eliminar'], true))
                            <section class="rounded-xl border border-[#CAD7E7] bg-transparent p-5">
                                <h2 class="mb-[10px] text-[13px] font-bold uppercase tracking-[.12em] text-[#1A3A6B]">Seleccionar grupo de permisos</h2>
                                <label class="block text-[15px] font-medium text-gray-700">Grupo</label>
                                <x-administration-search-picker input-id="permission-group-picker" model="selectedGroupId" :selected="$selectedGroupId" :items="$groupOptions" placeholder="Buscar grupo por nombre..." empty-message="No se encontraron grupos." placement="bottom" />
                                <x-input-error for="selectedGroupId" class="mt-2" />
                            </section>
                        @endif

                        <section class="rounded-xl border p-5 {{ $fieldsDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase tracking-[.12em] {{ $fieldsDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Información del grupo</h2>
                            <fieldset class="grid grid-cols-1 gap-[15px]" @disabled($fieldsDisabled || $activeTab === 'eliminar')>
                                <div><label class="block text-[15px] font-medium text-gray-700">Nombre</label><input wire:model.defer="name" class="{{ $input }}" autocomplete="off"><x-input-error for="name" class="mt-2" /></div>
                                <div><label class="block text-[15px] font-medium text-gray-700">Descripción</label><textarea wire:model.defer="description" rows="3" maxlength="255" class="mt-[10px] block w-full resize-none rounded-lg border border-[#B7CEEA] bg-transparent px-3 py-3 text-[15px] text-[#102A52] focus:border-[#B7CEEA] focus:outline-none focus:ring-0 disabled:bg-[#E8EBF0]"></textarea><x-input-error for="description" class="mt-2" /></div>
                            </fieldset>
                            @if ($activeTab === 'eliminar' && $selectedGroup)
                                <div class="grid grid-cols-2 gap-[10px] text-center"><div class="rounded-lg bg-[#E8EEF7] p-3"><strong class="block text-xl text-[#102A52]">{{ $selectedGroup->permissions_count }}</strong><span class="text-[11px] uppercase text-[#55749D]">Apartados</span></div><div class="rounded-lg bg-[#E8EEF7] p-3"><strong class="block text-xl text-[#102A52]">{{ $selectedGroup->roles_count }}</strong><span class="text-[11px] uppercase text-[#55749D]">Roles asignados</span></div></div>
                            @endif
                        </section>

                        <section class="rounded-xl border p-5 {{ $fieldsDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                            <div class="flex items-center justify-between gap-3"><h2 class="text-[13px] font-bold uppercase tracking-[.12em] {{ $fieldsDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Apartados incluidos</h2><span class="rounded-full bg-[#DCE9F8] px-3 py-1 text-[11px] font-semibold text-[#1A3A6B]">{{ count($permissionIds) }} seleccionados</span></div>
                            <fieldset class="mt-[10px] grid grid-cols-1 gap-[10px] sm:grid-cols-2" @disabled($fieldsDisabled || $activeTab === 'eliminar')>
                                @foreach ($availablePermissions->groupBy(fn ($permission) => $permission->module ?: 'General') as $module => $modulePermissions)
                                    <div class="rounded-xl border border-[#CAD7E7] bg-white/50 p-4">
                                        <h3 class="mb-[10px] text-[11px] font-bold uppercase tracking-[.1em] text-[#55749D]">{{ $module }}</h3>
                                        <div class="space-y-2">
                                            @foreach ($modulePermissions as $permission)
                                                <x-permission-access-card wire:key="permission-catalog-access-{{ $permission->id }}" :permission="$permission" :selected="in_array((int) $permission->id, $permissionIds, true)" :editable="$activeTab !== 'eliminar'" />
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </fieldset>
                            <x-input-error for="permissionIds" class="mt-2" />
                        </section>

                        @if ($activeTab === 'eliminar')
                            <section class="rounded-xl border p-5 {{ $fieldsDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                                <h2 class="mb-[10px] text-[13px] font-bold uppercase tracking-[.12em] {{ $fieldsDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Confirmación</h2>
                                <label class="block text-[15px] font-medium text-gray-700">Escribe manualmente el nombre del grupo</label>
                                <input wire:model.defer="deleteConfirmation" class="{{ $input }}" autocomplete="off" onpaste="return false" @disabled($fieldsDisabled || $selectedGroup?->is_system || ($selectedGroup?->roles_count ?? 0) > 0)>
                                @if ($selectedGroup?->is_system)<p class="mt-2 text-[13px] text-amber-700">Este grupo base está protegido.</p>@elseif (($selectedGroup?->roles_count ?? 0) > 0)<p class="mt-2 text-[13px] text-amber-700">Primero debes desasignarlo de sus roles.</p>@endif
                                <x-input-error for="deleteConfirmation" class="mt-2" />
                            </section>
                        @endif
                    </div>
                </x-slot>
                <x-slot name="actions"><button type="button" wire:click="closeModal" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-white">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex min-w-28 items-center justify-center rounded-lg bg-white px-5 py-3 font-semibold text-[#1A3A6B] disabled:opacity-50" @disabled($fieldsDisabled || ($activeTab === 'eliminar' && ($selectedGroup?->is_system || ($selectedGroup?->roles_count ?? 0) > 0)))>{{ $activeTab === 'eliminar' ? 'Eliminar' : ($activeTab === 'editar' ? 'Actualizar' : 'Guardar') }}</button></x-slot>
            </x-administration-form-modal>
        </div>
        @endteleport
    @endif
</div>
