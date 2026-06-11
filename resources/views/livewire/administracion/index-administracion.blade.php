<div class="p-6 space-y-8">
    <!-- Estadísticas principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <x-conteiner class="border rounded-2xl hover:shadow-2xl transition-all duration-300" title="{{ $totalUsers }}"
            subtitle="Usuarios" />

        <x-conteiner class="border rounded-2xl hover:shadow-2xl transition-all duration-300" title="{{ $totalRoles }}"
            subtitle="Roles" />

        <x-conteiner class="border rounded-2xl hover:shadow-2xl transition-all duration-300" title="30"
            subtitle="Permisos" />
    </div>

    <!-- Accesos rápidos -->
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-xl font-semibold text-gray-700">Secciones principales</h2>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-6 p-4 justify-items-center">
            <x-access-icon wire:click="goToSecction('users')" color="blue" icon="feathericon-users" text="Usuarios" />
            <x-access-icon wire:click="goToSecction('roles')" color="red" icon="feathericon-lock" text="Roles" />
            <x-access-icon wire:click="goToSecction('permissions')" color="green" icon="feathericon-shield"
                text="Permisos" />
            <x-access-icon wire:click="goToSecction('interns')" color="purple" icon="feathericon-user"
                text="Becarios" />
            <x-access-icon wire:click="goToSecction('relationships')" color="orange" icon="feathericon-git-merge"
                text="Relaciones" />
        </div>

    </div>
</div>
