@php
    $role = auth()->user()->role->role;
@endphp

<div class="space-y-8 p-6">
    <!-- Estadísticas principales -->
    @if (in_array($role, ['Administrador', 'Coordinador']))
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
            <x-conteiner class="rounded-2xl border transition-all duration-300 hover:shadow-2xl"
                title="{{ $totalUsers }}" subtitle="Usuarios" />

            <x-conteiner class="rounded-2xl border transition-all duration-300 hover:shadow-2xl"
                title="{{ $totalRoles }}" subtitle="Roles" />

            <x-conteiner class="rounded-2xl border transition-all duration-300 hover:shadow-2xl"
                title="30" subtitle="Permisos" />
        </div>
    @endif

    <!-- Accesos rápidos -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
        <div class="border-b p-4">
            <h2 class="text-xl font-semibold text-gray-700">Secciones principales</h2>
        </div>
        <div class="grid grid-cols-3 justify-items-center gap-6 p-4 sm:grid-cols-4 md:grid-cols-6">

            @if (in_array($role, ['Administrador', 'Coordinador']))
                <x-access-icon wire:click="goToSecction('users')" color="blue"
                    icon="feathericon-users" text="Usuarios" />
            @endif
            @if ($role === 'Administrador')
                <x-access-icon wire:click="goToSecction('roles')" color="red"
                    icon="feathericon-lock" text="Roles" />
                <x-access-icon wire:click="goToSecction('permissions')" color="green"
                    icon="feathericon-shield" text="Permisos" />
            @endif
            <x-access-icon wire:click="goToSecction('interns')" color="purple"
                icon="feathericon-user" text="Auxiliares" />
            <x-access-icon wire:click="goToSecction('relationships')" color="orange"
                icon="feathericon-git-merge" text="Relaciones" />
        </div>

    </div>
</div>
