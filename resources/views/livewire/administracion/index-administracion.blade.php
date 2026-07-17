@php
    $role = auth()->user()->role->role;

    $missingLabels = [
        'superior' => 'Sin jefe',
        'job_position' => 'Sin puesto',
        'physical_area' => 'Sin área',
    ];
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

    <!-- Organigrama -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
        <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-700">Organigrama</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Estructura jerárquica dinámica. Piloto: Contaduría / Contabilidad.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <label for="physical-area-filter" class="text-sm font-medium text-gray-600">Filtrar por área:</label>
                <select id="physical-area-filter" wire:model.live="selectedPhysicalAreaId"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas las áreas</option>
                    @foreach ($physicalAreas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 p-4 xl:grid-cols-3">
            <!-- Árbol jerárquico -->
            <div class="xl:col-span-2">
                @if (($orgChartStats['cycles_detected'] ?? 0) > 0)
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        Se detectaron {{ $orgChartStats['cycles_detected'] }} ciclo(s) en las relaciones jerárquicas.
                        El árbol se renderiza de forma segura omitiendo ramas circulares.
                    </div>
                @endif

                <div class="mb-3 flex flex-wrap gap-3 text-xs text-gray-500">
                    <span>{{ $orgChartStats['in_tree'] ?? 0 }} en árbol</span>
                    <span>{{ $orgChartStats['relations'] ?? 0 }} relaciones</span>
                    <span>{{ $orgChartStats['total_users'] ?? 0 }} usuarios totales</span>
                </div>

                @if (count($orgChartTree) > 0)
                    <ul class="space-y-4">
                        @foreach ($orgChartTree as $rootNode)
                            <x-administracion.organigrama-node :node="$rootNode" :depth="0" />
                        @endforeach
                    </ul>
                @else
                    <x-no-data title="Sin datos" subTitle="No hay nodos raíz para el filtro seleccionado." />
                @endif
            </div>

            <!-- Usuarios sin asignar -->
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-700">Usuarios sin asignar</h3>
                    <span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ count($unassignedUsers) }}
                    </span>
                </div>

                <p class="mb-4 text-xs text-gray-500">
                    Usuarios que aún no tienen jefe, puesto o área asignada.
                </p>

                @if (count($unassignedUsers) > 0)
                    <ul class="max-h-[520px] space-y-3 overflow-y-auto">
                        @foreach ($unassignedUsers as $user)
                            <li class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                                <p class="text-sm font-medium text-gray-800">{{ $user['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $user['email'] }}</p>

                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($user['missing'] as $missingKey)
                                        <span @class([
                                            'rounded-md px-2 py-0.5 text-[10px] font-medium',
                                            'bg-red-50 text-red-700' => $missingKey === 'superior',
                                            'bg-orange-50 text-orange-700' => $missingKey === 'job_position',
                                            'bg-yellow-50 text-yellow-700' => $missingKey === 'physical_area',
                                        ])>
                                            {{ $missingLabels[$missingKey] ?? $missingKey }}
                                        </span>
                                    @endforeach
                                </div>

                                @if (! empty($user['role']) || ! empty($user['job_position']) || ! empty($user['physical_area']))
                                    <div class="mt-2 flex flex-wrap gap-1 border-t border-gray-100 pt-2">
                                        @if (! empty($user['role']))
                                            <span class="text-[10px] text-gray-400">Rol: {{ $user['role'] }}</span>
                                        @endif
                                        @if (! empty($user['job_position']))
                                            <span class="text-[10px] text-gray-400">Puesto: {{ $user['job_position'] }}</span>
                                        @endif
                                        @if (! empty($user['physical_area']))
                                            <span class="text-[10px] text-gray-400">Área: {{ $user['physical_area'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Todos los usuarios tienen jefe, puesto y área asignados.</p>
                @endif
            </div>
        </div>
    </div>
</div>
