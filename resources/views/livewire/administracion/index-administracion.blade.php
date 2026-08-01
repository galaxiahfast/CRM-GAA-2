@php
    $role = auth()->user()->role->role;
    $roleId = (int) auth()->user()->role_id;
    $permissionAccess = app(\App\Services\Authorization\PermissionAccessService::class);
    $canManageOrganization = $permissionAccess->allows(auth()->user(), 'administration.organization.manage');
    $canManageUsers = $permissionAccess->allows(auth()->user(), 'administration.users.manage');
    $canManageRoles = $permissionAccess->allows(auth()->user(), 'administration.roles.manage');
    $canManagePermissions = $permissionAccess->allows(auth()->user(), 'administration.permissions.manage');
    $canManageAssignments = $permissionAccess->allows(auth()->user(), 'administration.assignments.manage');

    $missingLabels = [
        'superior' => 'Sin jefe',
        'job_position' => 'Sin puesto',
        'physical_area' => 'Sin área',
    ];

    // Asegurar que $orgChartStats y $orgChartTree nunca sean null
    $orgChartStats = $orgChartStats ?? ['in_tree' => 0, 'relations' => 0, 'total_users' => 0, 'cycles_detected' => 0];
    $orgChartTree = $orgChartTree ?? [];
@endphp

<div class="w-full min-w-0 space-y-4" style="font-size: 15px; background-color: #F3F3F3;">
    <style>
        .unassigned-users-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .unassigned-users-scrollbar::-webkit-scrollbar { width: 6px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B !important; border-radius: 9999px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-thumb:hover { background: #15305a !important; }

        .administration-modal-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .administration-modal-scrollbar::-webkit-scrollbar { width: 6px; }
        .administration-modal-scrollbar::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .administration-modal-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
        .org-user-modal input,
        .org-user-modal select {
            border-color: #d1d5db;
            border-radius: 0.5rem;
            background-color: #F3F3F3;
            box-shadow: none;
        }
        .org-user-modal .org-modal-fields > div:not(.grid),
        .org-user-modal .org-modal-fields > .grid > div,
        .org-user-modal .org-modal-financial > div {
            min-width: 0;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #F3F3F3;
        }
        .org-user-modal .org-modal-fields p,
        .org-user-modal .org-modal-financial p {
            overflow: visible !important;
            white-space: normal !important;
            text-overflow: clip !important;
            overflow-wrap: anywhere;
        }
        .org-user-modal .hierarchy-selection-card {
            min-width: 0;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background-color: #F3F3F3;
        }
        .org-user-modal .hierarchy-selection-list {
            min-height: 10rem;
            max-height: 12rem;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background-color: #fff;
            color: #1f2937;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar { width: 6px; }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }

        /* Estilo para el contenedor en modo fullscreen */
        body.org-chart-fullscreen {
            overflow: hidden;
        }
        body.org-chart-fullscreen #org-tree-container {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9000 !important;
            width: 100vw !important;
            max-height: none !important;
            height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
            padding: 20px !important;
        }

        /* Botón fullscreen: cuadrado y sin contorno azul en hover/focus */
        #search-results,
        #physical-area-results { scrollbar-width: thin; scrollbar-color: #1A3A6B #f1f1f1; }
        #search-results::-webkit-scrollbar,
        #physical-area-results::-webkit-scrollbar { width: 4px; height: 4px; }
        #search-results::-webkit-scrollbar-track,
        #physical-area-results::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 9999px; }
        #search-results::-webkit-scrollbar-thumb,
        #physical-area-results::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
        #search-results::-webkit-scrollbar-thumb:hover,
        #physical-area-results::-webkit-scrollbar-thumb:hover { background: #15305a; }

        #fullscreen-toggle {
            outline: none !important;
            box-shadow: none !important;
            border: 1px solid #d1d5db;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background-color: rgba(255,255,255,0.8);
            backdrop-filter: blur(4px);
            transition: background-color 0.2s, border-color 0.2s;
        }
        #fullscreen-toggle:hover,
        #fullscreen-toggle:focus,
        #fullscreen-toggle:active {
            outline: none !important;
            box-shadow: none !important;
            border-color: #d1d5db; /* Sin cambio a azul */
            background-color: #ffffff;
        }
        #fullscreen-toggle svg {
            width: 24px;
            height: 24px;
            color: #1A3A6B;
        }

        /* Contenedor del mensaje sin datos ocupa todo el espacio */
        .org-tree-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-height: 400px;
            color: #6b7280;
            font-size: 15px;
            text-align: center;
        }
    </style>

    <!-- Accesos rápidos -->
    <div class="overflow-hidden rounded-2xl shadow-lg" style="background-color: #F3F3F3;">
        <div class="border-b p-4">
            <h2 class="text-[15px] font-semibold text-gray-700">Secciones principales</h2>
        </div>
        <div class="grid grid-cols-6 justify-items-center gap-6 p-4">
            @if ($canManageUsers)
                <a href="{{ route('administracion.section') }}" class="block">
                    <x-access-icon color="blue" icon="feathericon-users" text="Usuarios" />
                </a>
            @endif
            @if ($canManageRoles)
                <a href="{{ route('administracion.role') }}" class="block">
                    <x-access-icon color="red" icon="feathericon-lock" text="Roles" />
                </a>
            @endif
            @if ($canManagePermissions)
                <a href="{{ route('administracion.permissions') }}" class="block">
                    <x-access-icon color="green" icon="feathericon-shield" text="Permisos" />
                </a>
            @endif
            @if ($canManageAssignments)
                <a href="{{ route('administracion.interns') }}" class="block">
                    <x-access-icon color="purple" icon="feathericon-user" text="Auxiliares" />
                </a>
                <a href="{{ route('administracion.relationships') }}" class="block">
                    <x-access-icon color="orange" icon="feathericon-git-merge" text="Relaciones" />
                </a>
            @endif
        </div>
    </div>

    {{-- La información jerárquica solamente se entrega a administradores. --}}
    @if ($canManageOrganization)

    <!-- Organigrama con padding de 80px en todos los lados -->
    <div class="overflow-hidden rounded-2xl shadow-lg" style="padding: 80px; background-color: #F3F3F3;">

        <!-- Encabezado -->
        <div style="padding: 0; background-color: transparent; display: flex; flex-wrap: nowrap; align-items: flex-start; justify-content: space-between; gap: 32px; min-width: max-content;">
            <div style="max-width: 672px; flex-shrink: 0; display: flex; flex-direction: column; gap: 21px;">
                
                <!-- Grupo 1: Icono + Título / Subtítulo -->
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; height: 56px; width: 56px; align-items: center; justify-content: center; border-radius: 0px; background-color: rgba(26, 58, 107, 0.1); flex-shrink: 0;">
                        <svg style="height: 28px; width: 28px; color: #1A3A6B; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: center; gap: 4px;">
                        <h1 style="font-size: 24px; font-weight: 700; letter-spacing: -0.025em; color: #111827; white-space: nowrap; line-height: 1.5; margin: 0;">
                            Organigrama
                        </h1>
                        <p style="font-size: 15px; color: #6b7280; white-space: nowrap; line-height: 1.5; margin: 0;">
                            Estructura jerárquica dinámica
                        </p>
                    </div>
                </div>

                <!-- Grupo 2: Descripción -->
                <p style="max-width: 672px; font-size: 15px; line-height: 2; margin: 0; color: #6b7280;">
                    Gestión integral de la estructura organizacional, líneas de mando<br>y niveles de supervisión entre colaboradores, áreas y departamentos.
                </p>

                <!-- Grupo 3: Estadísticas -->
                <div style="display: flex; flex-wrap: wrap; gap: 20px; font-size: 15px; color: #6b7280;">
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $totalUsers ?? 0 }}</strong> Usuarios totales</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $orgChartStats['in_tree'] ?? 0 }}</strong> En árbol</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $orgChartStats['relations'] ?? 0 }}</strong> Relaciones</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $totalRoles ?? 0 }}</strong> Roles</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">30</strong> Permisos</span>
                </div>

            </div>
        </div>

        <!-- Grupo 4: Acciones centralizadas -->
        <div style="margin-top: 35px; padding: 0 0 80px 0; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div class="relative" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    aria-haspopup="menu"
                    class="inline-flex h-[50px] min-w-[180px] items-center justify-center gap-2 rounded-xl bg-[#1A3A6B] px-5 text-[15px] font-medium text-white shadow-sm transition hover:bg-[#142e55] focus:outline-none focus:ring-0"
                >
                    Agregar...
                    <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                    </svg>
                </button>
                
                <div
                    x-cloak
                    x-show="open"
                    x-transition.origin.top.left
                    role="menu"
                    class="absolute left-0 z-50 mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white p-2 shadow-lg"
                    style="font-size: 15px;"
                >
                    <a href="{{ route('administracion.create.users') }}" role="menuitem"
                        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-[15px] font-medium text-gray-700 transition hover:bg-gray-50 hover:text-[#1A3A6B]">
                        <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9a3 3 0 1 0-6 0 3 3 0 0 0 6 0ZM6 21a6 6 0 0 1 12 0M6 21H3m3 0h3m12 0h-3m3 0h-3M9 9a3 3 0 1 0-6 0 3 3 0 0 0 6 0Zm-6 12a6 6 0 0 1 6-6" />
                        </svg>
                        Agregar Usuario
                    </a>
                    <a href="{{ route('administracion.role.create') }}" role="menuitem"
                        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-[15px] font-medium text-gray-700 transition hover:bg-gray-50 hover:text-[#1A3A6B]">
                        <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15.5 7.5 18l1.2-5L5 9.8l5.1-.4L12 4.7l1.9 4.7 5.1.4-3.7 3.2 1.2 5L12 15.5Z" />
                        </svg>
                        Agregar Rol
                    </a>
                    <button type="button" role="menuitem" wire:click="openJobPositionModal" @click="open = false"
                        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-[15px] font-medium text-gray-700 transition hover:bg-gray-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01" />
                        </svg>
                        Agregar Puesto Operativo
                    </button>
                    <button type="button" role="menuitem" wire:click="openPhysicalAreaModal" @click="open = false"
                        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-[15px] font-medium text-gray-700 transition hover:bg-gray-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v4l2.5 2.5" />
                        </svg>
                        Agregar &Aacute;rea
                    </button>
                </div>
            </div>

            <button type="button" wire:click="openPermissionsModal"
                class="inline-flex h-[50px] min-w-[180px] items-center justify-center rounded-xl bg-[#1A3A6B] px-5 text-[15px] font-medium text-white shadow-sm transition hover:bg-[#142e55] focus:outline-none focus:ring-0">
                Gestionar Permisos
            </button>
        </div>

        <!-- Grid con ancho mínimo para evitar deformación -->
        <div class="grid min-w-0 grid-cols-1 gap-6 p-0 xl:grid-cols-3" style="padding: 0;">

            <!-- Árbol jerárquico -->
            <div class="xl:col-span-2" style="padding: 0;">
                @if (($orgChartStats['cycles_detected'] ?? 0) > 0)
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[15px] text-red-700">
                        Se detectaron {{ $orgChartStats['cycles_detected'] }} ciclo(s) en las relaciones jerárquicas.
                        El árbol se renderiza de forma segura omitiendo ramas circulares.
                    </div>
                @endif

                <!-- Contenedor con pan y zoom (siempre visible) -->
                <div id="org-tree-container" class="relative overflow-hidden border border-dashed border-gray-300 p-5" style="max-height: 520px; height: auto; background: #F3F3F3; border-radius: 0.75rem; touch-action: none;">

                    <!-- LEYENDA (siempre visible) -->
                    <div class="absolute top-0 left-0 p-5 bg-white/80 backdrop-blur-sm rounded-br-xl border-r border-b border-white/30 shadow-sm" style="z-index: 30;">
                        <div class="text-[15px] text-gray-700 flex flex-col items-start gap-[10px]">
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #1e3a8a;"></span> Rol</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #059669;"></span> Puesto</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #7c3aed;"></span> Área</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #d97706;"></span> Múltiples jefes</div>
                        </div>
                    </div>

                    <!-- BUSCADOR + SELECT + BOTÓN FULLSCREEN (siempre visibles) -->
                    <div class="absolute top-0 right-0 p-5 flex items-center gap-5" style="z-index: 30;">
                        <!-- Buscador -->
                        <div style="position: relative; flex: 1;">
                            <input 
                                id="node-search-input"
                                type="text" 
                                placeholder="Buscar colaborador..."
                                class="rounded-lg text-[15px] border-gray-300 bg-white/80 backdrop-blur-sm"
                                style="height: 50px; min-width: 220px; width: 100%; padding: 0 16px; border: 1px solid #d1d5db; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30;"
                                autocomplete="off"
                            >
                            <div 
                                id="search-results"
                                style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: rgba(255,255,255,0.98); backdrop-filter: blur(8px); border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-height: 300px; overflow-y: auto; display: none; z-index: 100;"
                            ></div>
                        </div>

                        <!-- Filtro por área con búsqueda -->
                        <div class="relative w-[200px]" x-data="{
                                open: false, query: '', selected: @js((string) ($selectedPhysicalAreaId ?? '')),
                                areas: @js($physicalAreas->map(fn ($area) => ['id' => (string) $area->id, 'name' => $area->name])->values()),
                                get filteredAreas() { const term = this.query.toLocaleLowerCase().trim(); return term ? this.areas.filter(area => area.name.toLocaleLowerCase().includes(term)) : this.areas; },
                                choose(id) { this.selected = id; this.query = ''; this.open = false; $wire.set('selectedPhysicalAreaId', id); }
                            }" @click.away="open = false; query = ''" @keydown.escape.window="open = false; query = ''">
                            <input id="physical-area-filter" x-ref="areaInput" type="text" x-model="query" @focus="open = true" @input="open = true"
                                :placeholder="selected && !open ? (areas.find(area => area.id === selected)?.name || 'Todas las áreas') : 'Todas las áreas'"
                                class="h-[50px] w-full rounded-lg border border-gray-300 bg-white/80 px-4 pr-10 text-[15px] backdrop-blur-sm focus:border-gray-300 focus:outline-none focus:ring-0"
                                autocomplete="off" aria-label="Buscar o filtrar por área">
                            <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.areaInput.focus())"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-500 focus:outline-none" tabindex="-1" aria-label="Mostrar áreas">
                                <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                            </button>
                            <div x-cloak x-show="open" id="physical-area-results" class="absolute left-0 right-0 top-[54px] max-h-[300px] overflow-y-auto rounded-lg border border-gray-300 bg-white shadow-lg" style="z-index: 100;">
                                <button type="button" @mousedown.prevent="choose('')" class="block w-full border-b border-gray-100 px-4 py-3 text-left text-[15px] hover:bg-[#f0f4ff]">Todas las áreas</button>
                                <template x-for="area in filteredAreas" :key="area.id"><button type="button" @mousedown.prevent="choose(area.id)" x-text="area.name" class="block w-full border-b border-gray-100 px-4 py-3 text-left text-[15px] hover:bg-[#f0f4ff]"></button></template>
                                <div x-show="filteredAreas.length === 0" class="px-4 py-3 text-center text-sm text-gray-500">No se encontraron áreas</div>
                            </div>
                        </div>

                        <!-- BOTÓN DE PANTALLA COMPLETA (cuadrado, sin borde azul) -->
                        <button id="fullscreen-toggle" type="button"
                            aria-label="Alternar pantalla completa">
                            <!-- Icono expandir (visible por defecto) -->
                            <svg id="fullscreen-icon-expand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" />
                            </svg>
                            <!-- Icono comprimir (oculto por defecto) - inverso exacto del expandir -->
                            <svg id="fullscreen-icon-compress" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 9h5V4M20 9h-5V4M4 15h5v5M20 15h-5v5" />
                            </svg>
                        </button>
                    </div>

                    <!-- INSTRUCCIONES (siempre visibles) -->
                    <div class="absolute bottom-0 right-0 p-5 bg-white/80 backdrop-blur-sm rounded-tl-xl border-l border-t border-white/30 shadow-sm" style="z-index: 30;">
                        <div class="text-[15px] text-gray-500">
                            Arrastra para mover · Rueda para zoom · Doble clic para reset
                        </div>
                    </div>

                    <!-- CONTENIDO PRINCIPAL: árbol o mensaje sin datos (profesional) -->
                    @if (count($orgChartTree) > 0)
                        <div id="org-tree-wrapper" class="origin-top-left" style="transform: scale(1) translate(0px, 0px); cursor: grab; width: max-content; min-width: 1000px; padding: 20px;">
                            <div class="flex flex-col items-center gap-6" style="min-width: max-content;">
                                @foreach ($orgChartTree as $rootNode)
                                    <x-administracion.organigrama-node :node="$rootNode" :depth="0" />
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="org-tree-empty">
                            <span>No se encontraron nodos para el filtro seleccionado.</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Usuarios sin asignar -->
            <div class="unassigned-users-scrollbar rounded-xl border border-dashed border-gray-300" style="min-width: 400px; background-color: #F3F3F3; max-height: 520px; overflow-y: auto; padding: 20px;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="flex w-full items-center justify-between" style="line-height: 1; height: 10px;">
                            <h3 class="text-[15px] font-semibold text-gray-700" style="line-height: 1; margin: 0; padding: 0;">
                                Usuarios sin asignar
                            </h3>
                            <span class="rounded-full bg-gray-200 px-2 text-[15px] font-medium text-gray-700"
                                style="line-height: 1; height: 22px; display: inline-flex; align-items: center; justify-content: center;">
                                {{ count($unassignedUsers) }}
                            </span>
                        </div>
                        <p class="text-[15px] text-gray-500" style="line-height: 1; margin: 0; padding: 0;">
                            Sin jefe, puesto o área asignada.
                        </p>
                    </div>
                    @if (count($unassignedUsers) > 0)
                        <ul class="overflow-y-auto" style="display: flex; flex-direction: column; gap: 15px; margin: 0; padding: 0; list-style: none;">
                            @foreach ($unassignedUsers as $user)
                                @php
                                    $nameParts = array_values(array_filter(preg_split('/\s+/', trim((string) $user['name']))));
                                    $initials = implode('', array_map(
                                        static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)),
                                        array_slice($nameParts, 0, 2)
                                    ));
                                @endphp
                                <li style="list-style: none;">
                                    <button type="button" wire:key="unassigned-user-{{ $user['id'] }}" wire:click="selectUser({{ (int) $user['id'] }})"
                                        class="w-full rounded-xl border border-dashed border-gray-300 bg-[#F3F3F3] p-[20px] text-left shadow-sm transition hover:border-[#1e3a8a] hover:shadow-md focus:border-[#1e3a8a] focus:outline-none focus:ring-0"
                                        style="display: flex; flex-direction: column; gap: 20px;">
                                        <div class="flex min-w-0 items-start gap-[20px]">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-xs font-semibold text-white">
                                                {{ $initials !== '' ? $initials : '?' }}
                                            </div>
                                            <div class="min-w-0 flex-1" style="display: flex; flex-direction: column; gap: 13px;">
                                                <div class="flex min-w-0 items-start justify-between gap-[0px]">

                                                    {{--  --}}
                                                    <p class="min-w-0 flex-1 truncate text-[15px] font-medium text-gray-800" title="{{ $user['name'] }}" style="line-height: 1; margin: 0; padding: 0;">
                                                        {{ $user['name'] }}
                                                    </p>

                                                    {{--  --}}
                                                    @if(! empty($user['created_at']))
                                                        <span class="shrink-0 text-[13px] text-gray-500" style="line-height: 1; margin: 0; padding: 0; white-space: nowrap;">
                                                            {{ \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="shrink-0 text-[13px] text-gray-400" style="line-height: 1; margin: 0; padding: 0; white-space: nowrap;">
                                                            Fecha no disponible
                                                        </span>
                                                    @endif
                                                </div>


                                                {{--  --}}
                                                <p class="truncate text-[15px] text-gray-500" title="{{ $user['email'] }}" style="line-height: 1; margin: 0; padding: 0;">{{ $user['email'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-[10px]">
                                            @foreach ($user['missing'] as $missingKey)
                                                <span @class([
                                                    'inline-flex w-[130px] shrink-0 items-center justify-center gap-[10px] rounded-md px-[20px] py-[10px] text-[15px] font-medium text-white',
                                                    'bg-[#D9383A]' => $missingKey === 'superior',
                                                    'bg-[#028A58]' => $missingKey === 'job_position',
                                                    'bg-[#8B3DFF]' => $missingKey === 'physical_area',
                                                ]) style="line-height: 1;">
                                                    @if($missingKey === 'superior')
                                                        <svg class="h-[10px] w-[10px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                        <span class="min-w-0 truncate">Sin supervisor</span>
                                                    @elseif($missingKey === 'job_position')
                                                        <svg class="h-[10px] w-[10px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7l1 12h12l1-12M9 7V5a3 3 0 016 0v2" /></svg>
                                                        <span class="min-w-0 truncate">Sin puesto</span>
                                                    @elseif($missingKey === 'physical_area')
                                                        <svg class="h-[10px] w-[10px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4zM8 8h2v2H8zm4 0h2v2h-2zm-4 4h2v2H8zm4 0h2v2h-2z" /></svg>
                                                        <span class="min-w-0 truncate">Sin área</span>
                                                    @else
                                                        <span class="min-w-0 truncate">{{ $missingLabels[$missingKey] ?? $missingKey }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                        @if (! empty($user['role']) || ! empty($user['job_position']) || ! empty($user['physical_area']))
                                            <div class="flex flex-wrap gap-[20px]">
                                                @if (! empty($user['role']))
                                                    <span class="text-[15px] text-gray-400" style="line-height: 1; margin: 0; padding: 0;"><strong class="font-semibold text-gray-500">Rol:</strong> {{ $user['role'] }}</span>
                                                @endif
                                                @if (! empty($user['job_position']))
                                                    <span class="text-[15px] text-gray-400" style="line-height: 1; margin: 0; padding: 0;"><strong class="font-semibold text-gray-500">Puesto:</strong> {{ $user['job_position'] }}</span>
                                                @endif
                                                @if (! empty($user['physical_area']))
                                                    <span class="text-[15px] text-gray-400" style="line-height: 1; margin: 0; padding: 0;"><strong class="font-semibold text-gray-500">Área:</strong> {{ $user['physical_area'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-[15px] text-gray-500" style="line-height: 1.3; margin: 0; padding: 0;">
                            Todos los usuarios tienen jefe, puesto y área asignados.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL PARA AGREGAR PUESTO DE TRABAJO                        --}}
    {{-- ============================================================ --}}
    @if ($showJobPositionModal)
        <x-administration-compact-form-modal
            submit="saveJobPosition"
            close-action="closeJobPositionModal"
            modal-id="job-position-form"
            title="Puestos operativos"
            subtitle="Administra las posiciones organizacionales."
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7h-4V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2H4a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM10 7V5h4v2m-2 4v4" />
                </svg>
            </x-slot>

            <x-slot name="navigation">
                @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
                    <button type="button" wire:click="setJobPositionModalTab('{{ $tab }}')"
                        class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $jobPositionModalTab === $tab ? ($tab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </x-slot>

            <x-slot name="form">
                @if ($jobPositionModalTab === 'crear')
                <label for="new-job-position-name" class="block text-[15px] font-medium text-gray-700">
                    Nombre del puesto
                </label>
                <input
                    id="new-job-position-name"
                    type="text"
                    maxlength="255"
                    autocomplete="off"
                    wire:model.defer="newJobPositionName"
                    class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0"
                    placeholder="Ej. Contador Senior"
                    autofocus
                >
                <x-input-error for="newJobPositionName" class="mt-2 text-[15px]" />
                <fieldset class="mt-[15px]">
                    <legend class="block text-[15px] font-medium text-gray-700">Tipo de pago</legend>
                    <div class="mt-2 grid grid-cols-1 gap-[10px] sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-[10px] rounded-lg border border-gray-300 bg-white p-3">
                            <input type="radio" value="full_time" wire:model="newJobPositionPaymentType" class="mt-0.5 h-4 w-4 border-gray-300 text-[#1A3A6B] focus:ring-0">
                            <span><span class="block text-[15px] font-medium text-gray-800">Tiempo completo</span><span class="mt-1 block text-[13px] text-gray-500">Sin cálculo de tarifa por hora.</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-[10px] rounded-lg border border-gray-300 bg-white p-3">
                            <input type="radio" value="hourly" wire:model="newJobPositionPaymentType" class="mt-0.5 h-4 w-4 border-gray-300 text-[#1A3A6B] focus:ring-0">
                            <span><span class="block text-[15px] font-medium text-gray-800">Pago por hora</span><span class="mt-1 block text-[13px] text-gray-500">Habilita tarifa y apoyo económico.</span></span>
                        </label>
                    </div>
                    <x-input-error for="newJobPositionPaymentType" class="mt-2 text-[15px]" />
                </fieldset>
                @elseif ($jobPositionModalTab === 'editar')
                    <div class="space-y-[15px]">
                        <div>
                            <label for="edit-job-position-id" class="block text-[15px] font-medium text-gray-700">Puesto operativo</label>
                            <select id="edit-job-position-id" wire:model.live="selectedJobPositionId" class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-0">
                                <option value="">Seleccione un puesto</option>
                                @foreach ($jobPositions as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="selectedJobPositionId" class="mt-2 text-[15px]" />
                        </div>
                        @if ($selectedJobPositionId)
                            <div>
                                <label for="edit-job-position-name" class="block text-[15px] font-medium text-gray-700">Nuevo nombre</label>
                                <input id="edit-job-position-name" type="text" maxlength="255" wire:model.defer="editJobPositionName" class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-0">
                                <x-input-error for="editJobPositionName" class="mt-2 text-[15px]" />
                            </div>
                            <fieldset>
                                <legend class="block text-[15px] font-medium text-gray-700">Tipo de pago</legend>
                                <div class="mt-2 grid grid-cols-1 gap-[10px] sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center gap-[10px] rounded-lg border border-gray-300 bg-white p-3 text-[15px] text-gray-700">
                                        <input type="radio" value="full_time" wire:model="editJobPositionPaymentType" class="h-4 w-4 border-gray-300 text-[#1A3A6B] focus:ring-0"> Tiempo completo
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-[10px] rounded-lg border border-gray-300 bg-white p-3 text-[15px] text-gray-700">
                                        <input type="radio" value="hourly" wire:model="editJobPositionPaymentType" class="h-4 w-4 border-gray-300 text-[#1A3A6B] focus:ring-0"> Pago por hora
                                    </label>
                                </div>
                                <x-input-error for="editJobPositionPaymentType" class="mt-2 text-[15px]" />
                            </fieldset>
                        @endif
                    </div>
                @else
                    <div class="space-y-[15px]">
                        <p class="rounded-xl border border-red-200 bg-red-50 p-4 text-[15px] text-red-700">El puesto se eliminará y los usuarios que lo tengan quedarán sin puesto asignado.</p>
                        <div>
                            <label for="delete-job-position-id" class="block text-[15px] font-medium text-gray-700">Puesto operativo</label>
                            <select id="delete-job-position-id" wire:model.live="selectedJobPositionId" class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 focus:border-red-500 focus:outline-none focus:ring-0">
                                <option value="">Seleccione un puesto</option>
                                @foreach ($jobPositions as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="selectedJobPositionId" class="mt-2 text-[15px]" />
                        </div>
                    </div>
                @endif
            </x-slot>

            <x-slot name="actions">
                @if ($jobPositionModalTab === 'crear')
                <button type="button" wire:click="closeJobPositionModal"
                    class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveJobPosition"
                    class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                    <svg wire:loading wire:target="saveJobPosition" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span wire:loading.remove wire:target="saveJobPosition">Guardar</span>
                    <span wire:loading wire:target="saveJobPosition">Guardando...</span>
                </button>
                @else
                    <button type="button" wire:click="closeJobPositionModal"
                        class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">
                        Cancelar
                    </button>
                    @if ($jobPositionModalTab === 'editar')
                        <button type="button" wire:click="updateJobPosition" wire:loading.attr="disabled" wire:target="updateJobPosition"
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:opacity-60">
                            Guardar cambios
                        </button>
                    @else
                        <button type="button" wire:click="deleteJobPosition" wire:loading.attr="disabled" wire:target="deleteJobPosition"
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-0 disabled:opacity-60">
                            Eliminar
                        </button>
                    @endif
                @endif
            </x-slot>
        </x-administration-compact-form-modal>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL PARA AGREGAR ÁREA / DEPARTAMENTO                      --}}
    {{-- ============================================================ --}}
    @if ($showPhysicalAreaModal)
        <x-administration-compact-form-modal
            submit="savePhysicalArea"
            close-action="closePhysicalAreaModal"
            modal-id="physical-area-form"
            title="Áreas y departamentos"
            subtitle="Administra las unidades organizacionales."
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V5a2 2 0 012-2h6a2 2 0 012 2v16m4 0V9a2 2 0 00-2-2h-2M8 7h4m-4 4h4m-4 4h4" />
                </svg>
            </x-slot>

            <x-slot name="navigation">
                @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
                    <button type="button" wire:click="setPhysicalAreaModalTab('{{ $tab }}')"
                        class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $physicalAreaModalTab === $tab ? ($tab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </x-slot>

            <x-slot name="form">
                @if ($physicalAreaModalTab === 'crear')
                <label for="new-physical-area-name" class="block text-[15px] font-medium text-gray-700">
                    Nombre del área o departamento
                </label>
                <input
                    id="new-physical-area-name"
                    type="text"
                    maxlength="255"
                    autocomplete="off"
                    wire:model.defer="newPhysicalAreaName"
                    class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0"
                    placeholder="Ej. Auditoría"
                    autofocus
                >
                <x-input-error for="newPhysicalAreaName" class="mt-2 text-[15px]" />
                @elseif ($physicalAreaModalTab === 'editar')
                    <div class="space-y-[15px]">
                        <div>
                            <label for="edit-physical-area-id" class="block text-[15px] font-medium text-gray-700">Área o departamento</label>
                            <select id="edit-physical-area-id" wire:model.live="selectedPhysicalAreaManagementId" class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-0">
                                <option value="">Seleccione un área</option>
                                @foreach ($physicalAreas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="selectedPhysicalAreaManagementId" class="mt-2 text-[15px]" />
                        </div>
                        @if ($selectedPhysicalAreaManagementId)
                            <div>
                                <label for="edit-physical-area-name" class="block text-[15px] font-medium text-gray-700">Nuevo nombre</label>
                                <input id="edit-physical-area-name" type="text" maxlength="255" wire:model.defer="editPhysicalAreaName" class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 focus:border-[#1A3A6B] focus:outline-none focus:ring-0">
                                <x-input-error for="editPhysicalAreaName" class="mt-2 text-[15px]" />
                            </div>
                        @endif
                    </div>
                @else
                    <div class="space-y-[15px]">
                        <p class="rounded-xl border border-red-200 bg-red-50 p-4 text-[15px] text-red-700">El área se eliminará y los usuarios que la tengan quedarán sin área asignada.</p>
                        <div>
                            <label for="delete-physical-area-id" class="block text-[15px] font-medium text-gray-700">Área o departamento</label>
                            <select id="delete-physical-area-id" wire:model.live="selectedPhysicalAreaManagementId" class="mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 focus:border-red-500 focus:outline-none focus:ring-0">
                                <option value="">Seleccione un área</option>
                                @foreach ($physicalAreas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="selectedPhysicalAreaManagementId" class="mt-2 text-[15px]" />
                        </div>
                    </div>
                @endif
            </x-slot>

            <x-slot name="actions">
                @if ($physicalAreaModalTab === 'crear')
                <button type="button" wire:click="closePhysicalAreaModal"
                    class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="savePhysicalArea"
                    class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                    <svg wire:loading wire:target="savePhysicalArea" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span wire:loading.remove wire:target="savePhysicalArea">Guardar</span>
                    <span wire:loading wire:target="savePhysicalArea">Guardando...</span>
                </button>
                @else
                    <button type="button" wire:click="closePhysicalAreaModal"
                        class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">
                        Cancelar
                    </button>
                    @if ($physicalAreaModalTab === 'editar')
                        <button type="button" wire:click="updatePhysicalArea" wire:loading.attr="disabled" wire:target="updatePhysicalArea"
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0 disabled:opacity-60">
                            Guardar cambios
                        </button>
                    @else
                        <button type="button" wire:click="deletePhysicalArea" wire:loading.attr="disabled" wire:target="deletePhysicalArea"
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-0 disabled:opacity-60">
                            Eliminar
                        </button>
                    @endif
                @endif
            </x-slot>
        </x-administration-compact-form-modal>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL INFORMATIVO DE PERMISOS VIGENTES --}}
    {{-- ============================================================ --}}
    @if ($showPermissionsModal)
        @php
            $permissionProfiles = $basePermissionProfiles;
        @endphp

        <div
            x-data
            wire:click.self="closePermissionsModal"
            @keydown.escape.window="$wire.closePermissionsModal()"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="permissions-modal-title"
            data-administration-modal="permissions"
        >
            <div
                class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-gray-300 bg-[#F3F3F3] shadow-2xl"
                style="height: min(86vh, 760px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;"
            >
                <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-gray-300 bg-[#F3F3F3] p-5">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white shadow-sm">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex min-w-0 flex-col gap-[10px]">
                            <h2 id="permissions-modal-title" class="truncate text-[15px] font-semibold leading-none text-gray-900" style="margin: 0;">
                                Gestionar permisos
                            </h2>
                            <p class="truncate text-[15px] leading-none text-gray-500" style="margin: 0;">
                                Accesos vigentes de Administrador y Auxiliar
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closePermissionsModal"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-xl leading-none text-gray-500 transition hover:border-[#1A3A6B] hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                        aria-label="Cerrar"
                    >
                        &times;
                    </button>
                </header>

                <div class="administration-modal-scrollbar min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
                    <div class="m-5 flex flex-col gap-5 rounded-xl border border-dashed border-gray-300 bg-white p-5 text-[15px] shadow-sm">
                        <div class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5">
                            <div class="flex items-start gap-[15px]">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="min-w-0">
                                    <p class="text-[15px] font-semibold text-gray-800">Configuración de acceso actual</p>
                                    <p class="mt-2 text-[15px] leading-6 text-gray-500">
                                        Esta vista refleja las reglas vigentes del sistema. La autorización continúa protegida por sus políticas y middleware actuales.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-[15px] md:grid-cols-2">
                            @foreach ($permissionProfiles as $roleName => $profile)
                                @if ($roles->contains('role', $roleName))
                                    <article class="flex min-w-0 flex-col rounded-xl border border-gray-200 bg-[#F3F3F3] p-5" data-permission-role="{{ $roleName }}">
                                        <div class="min-w-0 border-b border-gray-200 pb-[15px]">
                                            <h3 class="truncate text-[15px] font-semibold text-gray-900" title="{{ $roleName }}">{{ $roleName }}</h3>
                                            <span class="mt-[10px] inline-flex max-w-full rounded-full bg-blue-100 px-3 py-1 text-[15px] font-medium text-[#1A3A6B]">
                                                {{ $profile['label'] }}
                                            </span>
                                            <p class="mt-[10px] text-[15px] leading-6 text-gray-500">{{ $profile['description'] }}</p>
                                        </div>

                                        <ul class="mt-[15px] flex flex-col gap-[10px]" aria-label="Permisos de {{ $roleName }}">
                                            @foreach ($profile['permissions'] as $permission)
                                                <li class="flex min-w-0 items-center gap-[10px] rounded-lg border border-gray-200 bg-white px-3 py-2.5">
                                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 truncate text-[15px] text-gray-700" title="{{ $permission }}">{{ $permission }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </article>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <footer class="flex flex-shrink-0 justify-end border-t border-gray-300 bg-[#F3F3F3] p-5">
                    <button
                        type="button"
                        wire:click="closePermissionsModal"
                        class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0"
                    >
                        Cerrar
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
{{-- ============================================================ --}}
{{-- MODAL DE USUARIO --}}
{{-- ============================================================ --}}
@if ($selectedUserDetails)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="user-details-title">
        <!-- Contenedor principal: altura fija de 80vh -->
        <div x-data @click.outside="$wire.closeUserDetails()" class="org-user-modal relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-gray-300 bg-[#F3F3F3] shadow-2xl" style="height: min(86vh, 820px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;">
            
            <!-- ===== ENCABEZADO (fijo) ===== -->
            <div class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-gray-300 bg-[#F3F3F3] p-5">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white shadow-sm">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="min-w-0" style="display: flex; flex-direction: column; gap: 10px;">
                        <h2 id="user-details-title" class="truncate text-[15px] font-semibold text-gray-900 leading-none" title="{{ $selectedUserDetails['name'] ?? 'Usuario' }}" style="margin: 0;">
                            {{ $selectedUserDetails['name'] ?? 'Usuario' }}
                        </h2>
                        <p class="text-[15px] text-gray-500 leading-none" style="margin: 0;">Detalles del usuario</p>
                    </div>
                </div>
                <button type="button" wire:click="closeUserDetails" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-xl leading-none text-gray-500 transition hover:border-[#1A3A6B] hover:text-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Cerrar">&times;</button>
            </div>

            @if ($isEditingUser)
                {{-- ===== FORMULARIO DE EDICIÓN ===== --}}
                <div class="administration-modal-scrollbar min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
                    <form wire:submit="saveSelectedUser" class="m-5 flex flex-col gap-5 rounded-xl border border-dashed border-gray-300 bg-white p-5 text-[15px] shadow-sm">
                        <div class="grid grid-cols-1 gap-4">
                            <div><x-label for="edit-name" value="Nombre" class="text-[15px] mb-2.5 block" /><x-input id="edit-name" class="mt-1 block w-full text-[15px]" wire:model="userForm.name" /><x-input-error for="userForm.name" /></div>
                            <div><x-label for="edit-last-name" value="Apellido" class="text-[15px] mb-2.5 block" /><x-input id="edit-last-name" class="mt-1 block w-full text-[15px]" wire:model="userForm.last_name" /><x-input-error for="userForm.last_name" /></div>
                            <div><x-label for="edit-email" value="Email" class="text-[15px] mb-2.5 block" /><x-input id="edit-email" type="email" class="mt-1 block w-full text-[15px]" wire:model="userForm.email" /><x-input-error for="userForm.email" /></div>
                            <div>
                                <x-label for="edit-employee-id" value="ID del reloj checador" class="mb-2.5 block text-[15px]" />
                                <div
                                    class="relative"
                                    x-data="{ open: false, search: @js((string) ($userForm['employee_id'] ?? '')) }"
                                    @click.outside="open = false"
                                >
                                    <x-input
                                        id="edit-employee-id"
                                        type="text"
                                        autocomplete="off"
                                        maxlength="50"
                                        class="mt-1 block w-full pr-12 text-[15px]"
                                        wire:model="userForm.employee_id"
                                        x-model="search"
                                        @input="open = true"
                                        @focus="open = true"
                                        @click="open = true"
                                        @keydown.escape="open = false"
                                        aria-autocomplete="list"
                                        aria-controls="employee-id-suggestions"
                                        x-bind:aria-expanded="open"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-0 top-0 flex h-full w-12 items-center justify-center text-gray-500 transition hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                        @click="open = !open"
                                        aria-label="Mostrar sugerencias del reloj checador"
                                    >
                                        <svg class="h-4 w-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div
                                        id="employee-id-suggestions"
                                        x-cloak
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-1"
                                        class="administration-modal-scrollbar absolute z-[60] mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                        role="listbox"
                                    >
                                        @foreach ($employeeIdSuggestions as $employeeIdSuggestion)
                                            <button
                                                type="button"
                                                data-employee-id="{{ $employeeIdSuggestion->employeeID }}"
                                                data-person-name="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}"
                                                x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())"
                                                @click="search = $el.dataset.employeeId; $wire.set('userForm.employee_id', $el.dataset.employeeId); open = false"
                                                class="flex w-full min-w-0 items-center gap-4 rounded-lg px-4 py-3 text-left transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none focus:ring-0"
                                                role="option"
                                            >
                                                <span class="w-20 shrink-0 truncate text-[15px] font-semibold text-[#1A3A6B]" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                                <span class="min-w-0 flex-1 truncate text-[15px] text-gray-600" title="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}">{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <p class="mt-2 text-[15px] text-gray-500">Selecciona un ID registrado; la sugerencia muestra el nombre detectado por el checador.</p>
                                <x-input-error for="userForm.employee_id" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div><x-label for="edit-role" value="Rol" class="text-[15px] mb-2.5 block" /><select id="edit-role" wire:model.live="userForm.role_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un rol</option>@foreach ($roles as $availableRole)<option value="{{ $availableRole->id }}">{{ $availableRole->role }}</option>@endforeach</select><x-input-error for="userForm.role_id" /></div>
                            <div><x-label for="edit-position" value="Puesto" class="text-[15px] mb-2.5 block" /><select id="edit-position" wire:model.live="userForm.job_position_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un puesto</option>@foreach ($jobPositions as $position)<option value="{{ $position->id }}">{{ $position->name }} — {{ $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo' }}</option>@endforeach</select><x-input-error for="userForm.job_position_id" /></div>
                        </div>
                        <div><x-label for="edit-area" value="Área / departamento" class="text-[15px] mb-2.5 block" /><select id="edit-area" wire:model="userForm.physical_area_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un área</option>@foreach ($physicalAreas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select><x-input-error for="userForm.physical_area_id" /></div>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="hierarchy-selection-card">
                                <div class="mb-2.5 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <x-label for="edit-superiors" value="Jefe directo" class="block text-[15px] font-semibold text-gray-700" />
                                        <p class="mt-1 text-[15px] text-gray-500">Solo se permite uno y debe estar asignado al organigrama.</p>
                                    </div>
                                </div>
                                <div id="edit-superiors" class="hierarchy-selection-list block w-full text-[15px]" role="group" aria-label="Jefe directo">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-gray-100">
                                        <input type="radio" name="edit-superior" wire:click="clearSuperiorSelection" @checked(empty($userForm['superior_ids'])) class="h-4 w-4 shrink-0 border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0">
                                        <span class="text-[15px] font-medium text-gray-600">Sin jefe directo</span>
                                    </label>
                                    @forelse ($superiorCandidates as $superiorCandidate)
                                        <label wire:key="superior-candidate-{{ $superiorCandidate->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-gray-100">
                                            <input type="radio" name="edit-superior" value="{{ $superiorCandidate->id }}" wire:click="selectSuperior({{ $superiorCandidate->id }})" @checked(in_array($superiorCandidate->id, $userForm['superior_ids'] ?? [])) class="h-4 w-4 shrink-0 border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0">
                                            <span class="min-w-0 flex-1" title="{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }} — {{ $superiorCandidate->email }}">
                                                <span class="block truncate text-[15px] font-medium text-gray-800">{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }}</span>
                                                <span class="block truncate text-[15px] text-gray-500">{{ $superiorCandidate->email }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-3 py-2.5 text-[15px] text-gray-500">No hay colaboradores asignados al organigrama.</p>
                                    @endforelse
                                </div>
                                <x-input-error for="userForm.superior_ids" />
                            </div>
                            <div class="hierarchy-selection-card">
                                <div class="mb-2.5 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <x-label for="edit-subordinates" value="Subordinados directos" class="block text-[15px] font-semibold text-gray-700" />
                                        <p class="mt-1 text-[15px] text-gray-500">Solo aparecen personas sin jefe o que ya te reportan directamente.</p>
                                    </div>
                                </div>
                                <div id="edit-subordinates" class="hierarchy-selection-list block w-full text-[15px]" role="group" aria-label="Subordinados directos">
                                    @forelse ($subordinateCandidates as $subordinateCandidate)
                                        <label wire:key="subordinate-candidate-{{ $subordinateCandidate->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-gray-100">
                                            <input type="checkbox" value="{{ $subordinateCandidate->id }}" wire:model.live="userForm.subordinate_ids" class="h-4 w-4 shrink-0 rounded border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0">
                                            <span class="min-w-0 flex-1" title="{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }} — {{ $subordinateCandidate->email }}">
                                                <span class="block truncate text-[15px] font-medium text-gray-800">{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }}</span>
                                                <span class="block truncate text-[15px] text-gray-500">{{ $subordinateCandidate->email }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-3 py-2.5 text-[15px] text-gray-500">No hay colaboradores disponibles.</p>
                                    @endforelse
                                </div>
                                <x-input-error for="userForm.subordinate_ids" />
                            </div>
                        </div>
                        @if ($userForm['is_hourly_position'] ?? false)
                            <div class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-[#F3F3F3] p-4 sm:grid-cols-2">
                                <div><x-label for="edit-hourly-rate" value="Precio por hora" class="text-[15px] mb-2.5 block" /><x-input id="edit-hourly-rate" type="number" step="0.01" class="mt-1 block w-full text-[15px]" wire:model="userForm.hourly_rate" /><x-input-error for="userForm.hourly_rate" /></div>
                                <div><x-label for="edit-food-allowance" value="Apoyo económico por día" class="text-[15px] mb-2.5 block" /><x-input id="edit-food-allowance" type="number" step="0.01" class="mt-1 block w-full text-[15px]" wire:model="userForm.food_allowance" /><x-input-error for="userForm.food_allowance" /></div>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-[#F3F3F3] p-4 sm:grid-cols-2">
                            <div><x-label for="edit-password" value="Nueva contraseña (opcional)" class="text-[15px] mb-2.5 block" /><x-input id="edit-password" type="password" class="mt-1 block w-full text-[15px]" wire:model="userForm.password" /><x-input-error for="userForm.password" /></div>
                            <div><x-label for="edit-password-confirmation" value="Confirmar contraseña" class="text-[15px] mb-2.5 block" /><x-input id="edit-password-confirmation" type="password" class="mt-1 block w-full text-[15px]" wire:model="userForm.password_confirmation" /></div>
                        </div>
                        <div class="flex justify-end gap-3 border-t pt-4">
                            <button type="button" wire:click="cancelEditingUser" class="rounded-lg border border-[#1A3A6B] bg-white px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-gray-100 focus:outline-none focus:ring-0">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            @else
                {{-- ===== MENÚ DE PESTAÑAS (fijo) ===== --}}
                <div class="flex flex-shrink-0 gap-[10px] border-b border-gray-300 bg-[#F3F3F3] px-5 pt-3">
                    <button type="button"
                        wire:click="setActiveTab('datos')"
                        class="inline-flex items-center gap-[10px] border-b-2 px-4 py-3 text-[15px] font-medium transition-colors focus:outline-none focus:ring-0 {{ $activeTab === 'datos' ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6m2 13H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" /></svg>
                        Datos
                    </button>
                    <button type="button"
                        wire:click="setActiveTab('eliminar')"
                        class="inline-flex items-center gap-[10px] border-b-2 px-4 py-3 text-[15px] font-medium transition-colors focus:outline-none focus:ring-0 {{ $activeTab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" /></svg>
                        Eliminar usuario
                    </button>
                </div>

                {{-- ===== CONTENIDO SCROLLABLE ===== --}}
                <div class="administration-modal-scrollbar min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
                    @if ($activeTab === 'datos')
                        {{-- PESTAÑA DATOS --}}
                        <div class="p-5" style="display: flex; flex-direction: column; gap: 15px;">
                            <!-- DATOS GENERALES -->
                            <div class="overflow-hidden rounded-xl border border-dashed border-gray-300 bg-white shadow-sm" style="display: flex; flex-direction: column;">
                                <h3 class="border-b border-gray-200 bg-gray-100 text-[15px] font-semibold text-gray-800" style="padding: 15px 20px; margin: 0;">Datos generales</h3>
                                <div class="org-modal-fields p-5" style="display: flex; flex-direction: column; gap: 15px;">
                                    <!-- Nombre completo -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Nombre completo</span>
                                        <p class="text-[15px] font-semibold text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['name'] ?? 'N/A' }}</p>
                                    </div>
                                    <!-- Correo electrónico -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Correo electrónico</span>
                                        <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['email'] ?? 'N/A' }}</p>
                                    </div>
                                    <!-- Rol + ID checador (2 columnas) -->
                                    <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Rol</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['role'] ?: 'Sin rol' }}</p>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">ID del checador</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['employee_id'] ?: 'No asignado' }}</p>
                                        </div>
                                    </div>
                                    <!-- Fechas (2 columnas) -->
                                    <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Fecha de creación</span>
                                            <p class="text-[15px] font-medium text-gray-600 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['created_at'] ?? 'N/A' }}</p>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Última actualización</span>
                                            <p class="text-[15px] font-medium text-gray-600 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['updated_at'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <!-- Contraseña -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Contraseña</span>
                                        <p class="text-[15px] font-medium text-gray-500 leading-tight truncate" style="margin: 0;">•••••••• (protegida)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ORGANIZACIONAL -->
                            <div class="overflow-hidden rounded-xl border border-dashed border-gray-300 bg-white shadow-sm" style="display: flex; flex-direction: column;">
                                <h3 class="border-b border-gray-200 bg-gray-100 text-[15px] font-semibold text-gray-800" style="padding: 15px 20px; margin: 0;">Organizacional</h3>
                                <div class="org-modal-fields p-5" style="display: flex; flex-direction: column; gap: 15px;">
                                    <!-- Puesto + Área (2 columnas) -->
                                    <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Puesto</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['job_position'] ?: 'Sin asignar' }}</p>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Área / departamento</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['physical_area'] ?: 'Sin asignar' }}</p>
                                        </div>
                                    </div>
                                    <!-- Jefes directos -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Jefes directos</span>
                                        <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">
                                            @if (count($selectedUserDetails['superiors'] ?? []) > 0)
                                                {{ implode(', ', $selectedUserDetails['superiors']) }}
                                            @else
                                                <span class="text-gray-400">Sin jefe asignado</span>
                                            @endif
                                        </p>
                                    </div>
                                    <!-- Subordinados directos -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Subordinados directos</span>
                                        <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">
                                            @if (count($selectedUserDetails['subordinates'] ?? []) > 0)
                                                {{ implode(', ', $selectedUserDetails['subordinates']) }}
                                            @else
                                                <span class="text-gray-400">Sin subordinados</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos de auxiliar -->
                            @if ($selectedUserDetails['is_hourly_position'] ?? false)
                                <div style="display: flex; flex-direction: column;">
                                    <div class="overflow-hidden rounded-xl border border-dashed border-amber-300 bg-amber-50 shadow-sm">
                                        <h3 class="border-b border-amber-200 bg-amber-100/70 text-[15px] font-semibold text-amber-800" style="padding: 15px 20px; margin: 0;">Datos de auxiliar</h3>
                                        <div class="org-modal-financial grid grid-cols-1 gap-[15px] p-5 sm:grid-cols-2">
                                            <div style="display: flex; flex-direction: column;">
                                                <span class="text-[15px] text-amber-600" style="margin-bottom: 10px;">Precio por hora</span>
                                                <p class="text-[15px] font-medium text-amber-800 leading-tight truncate" style="margin: 0;">${{ number_format((float) ($selectedUserDetails['hourly_rate'] ?? 0), 2) }}</p>
                                            </div>
                                            <div style="display: flex; flex-direction: column;">
                                                <span class="text-[15px] text-amber-600" style="margin-bottom: 10px;">Apoyo económico por día</span>
                                                <p class="text-[15px] font-medium text-amber-800 leading-tight truncate" style="margin: 0;">${{ number_format((float) ($selectedUserDetails['food_allowance'] ?? 0), 2) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    @elseif ($activeTab === 'eliminar')
                        {{-- PESTAÑA ELIMINAR USUARIO --}}
                        <div class="flex min-h-full items-center p-5">
                            <div class="w-full rounded-xl border border-dashed border-red-300 bg-white p-5 shadow-sm">
                                <div class="flex items-start gap-[15px]">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1" style="display: flex; flex-direction: column; gap: 15px;">
                                        <h3 class="text-[15px] font-semibold text-red-700" style="margin: 0;">¿Estás seguro de eliminar este usuario?</h3>
                                        <p class="text-[15px] leading-relaxed text-gray-600" style="margin: 0;">Esta acción es irreversible y eliminará permanentemente al usuario del sistema.</p>
                                        <p class="text-[15px] leading-relaxed text-gray-600" style="margin: 0;">Para confirmar, escribe el nombre completo del usuario: <span class="font-semibold text-gray-800">{{ $selectedUserDetails['name'] ?? 'Usuario' }}</span></p>
                                        <div class="flex flex-col gap-[15px]">
                                            <input type="text" wire:model="deleteConfirmationName" class="w-full border-gray-300 px-4 py-3 text-[15px] focus:border-red-500 focus:ring-0" placeholder="Nombre completo del usuario">
                                            <button type="button" wire:click="deleteSelectedUser" class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition-colors hover:bg-red-700 focus:outline-none focus:ring-0">
                                                Eliminar usuario permanentemente
                                            </button>
                                        </div>
                                        @error('deleteConfirmationName')
                                            <p class="text-[15px] text-red-600" style="margin: 0;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ===== PIE CON BOTONES (fijo) ===== --}}
            <div class="flex flex-shrink-0 justify-end gap-[15px] border-t border-gray-300 bg-[#F3F3F3] p-5">
                @if (! $isEditingUser && $activeTab === 'datos')
                    <button type="button" wire:click="beginEditingUser" class="inline-flex items-center justify-center gap-[10px] rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Editar
                    </button>
                @endif
                <button type="button" wire:click="closeUserDetails" class="rounded-lg border border-[#1A3A6B] bg-white px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-gray-100 focus:outline-none focus:ring-0">Cerrar</button>
            </div>

        </div> {{-- fin contenedor principal --}}
    </div> {{-- fin fixed overlay --}}
@endif
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('org-tree-container');
        const wrapper = document.getElementById('org-tree-wrapper');

        if (!container || !wrapper) return;

        let isPanning = false;
        let didDrag = false;
        let panStartedOnNode = false;
        let startX, startY, startTranslateX, startTranslateY;
        let scale = 1;
        let translateX = 0, translateY = 0;

        function updateTransform() {
            wrapper.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
        }

        function resetView() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            updateTransform();
        }

        function centerNode(nodeElement) {
            resetView();
            
            requestAnimationFrame(() => {
                const wrapperRect = wrapper.getBoundingClientRect();
                const nodeRect = nodeElement.getBoundingClientRect();
                
                const nodeCenterX = (nodeRect.left + nodeRect.width / 2) - wrapperRect.left;
                const nodeCenterY = (nodeRect.top + nodeRect.height / 2) - wrapperRect.top;
                
                const containerRect = container.getBoundingClientRect();
                const targetX = (containerRect.width / 2) - (nodeCenterX * scale);
                const targetY = (containerRect.height / 2) - (nodeCenterY * scale);
                
                translateX = targetX;
                translateY = targetY;
                updateTransform();
            });
        }

        container.addEventListener('wheel', function(e) {
            if (e.target.closest('#search-results') || e.target.closest('.search-result-item') || e.target.closest('#physical-area-results')) {
                return;
            }
            
            e.preventDefault();
            const rect = container.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const newScale = Math.min(Math.max(scale * delta, 0.3), 3);
            const dx = (mouseX - translateX) * (1 - newScale / scale);
            const dy = (mouseY - translateY) * (1 - newScale / scale);
            scale = newScale;
            translateX += dx;
            translateY += dy;
            updateTransform();
        }, { passive: false });

        container.addEventListener('mousedown', function(e) {
            if (e.button !== 0 || e.target.closest('#node-search-input, #search-results, .search-result-item, #physical-area-filter, #physical-area-results, #fullscreen-toggle')) {
                return;
            }

            const node = e.target.closest('.org-node');
            if (!node && e.target.closest('button, a, input, select, textarea')) {
                return;
            }

            isPanning = true;
            didDrag = false;
            panStartedOnNode = Boolean(node);
            startX = e.clientX;
            startY = e.clientY;
            startTranslateX = translateX;
            startTranslateY = translateY;
            wrapper.style.cursor = 'grabbing';
        });

        window.addEventListener('mousemove', function(e) {
            if (!isPanning) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            if (!didDrag && Math.hypot(dx, dy) < 5) return;
            didDrag = true;
            translateX = startTranslateX + dx;
            translateY = startTranslateY + dy;
            updateTransform();
        });

        window.addEventListener('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                wrapper.style.cursor = 'grab';
            }
        });

        container.addEventListener('click', function(e) {
            if (didDrag && panStartedOnNode && e.target.closest('.org-node')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                didDrag = false;
                panStartedOnNode = false;
            }
        }, true);

        container.addEventListener('selectstart', function(e) {
            if (isPanning) e.preventDefault();
        });

        container.addEventListener('dblclick', function(e) {
            e.preventDefault();
            resetView();
        });

        // ============================================================
        // BUSCADOR DE NODOS
        // ============================================================
        const searchInput = document.getElementById('node-search-input');
        const searchResults = document.getElementById('search-results');
        let allNodes = [];
        let searchTimeout = null;

        if (searchResults) {
            searchResults.addEventListener('wheel', function(e) {
                e.stopPropagation();
            }, { passive: true });
            
            searchResults.addEventListener('mousedown', function(e) {
                e.stopPropagation();
            });
            
            searchResults.addEventListener('touchstart', function(e) {
                e.stopPropagation();
            });
        }

        function loadAllNodes() {
            allNodes = [];
            document.querySelectorAll('.org-node').forEach(el => {
                const nameEl = el.querySelector('p.text-sm.font-semibold');
                const emailEl = el.querySelector('p.text-xs.text-gray-500');
                const name = nameEl ? nameEl.textContent.trim() : '';
                const email = emailEl ? emailEl.textContent.trim() : '';
                const id = el.dataset.id ? parseInt(el.dataset.id) : null;
                if (id && name) {
                    allNodes.push({ id, name, email: email || '', element: el });
                }
            });
            return allNodes;
        }

        function showResults(results) {
            if (!searchResults) return;
            
            if (results.length === 0) {
                searchResults.innerHTML = '<div style="padding: 12px 16px; color: #6b7280; font-size: 14px; text-align: center;">No se encontraron resultados</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = results.map(node => `
                <div class="search-result-item" data-id="${node.id}" style="padding: 12px 16px; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s; background-color: transparent;" 
                     onmouseover="this.style.backgroundColor='#f0f4ff'"
                     onmouseout="this.style.backgroundColor='transparent'">
                    <span style="font-weight: 600; color: #111827; font-size: 15px; text-align: left; line-height: 1.2; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${node.name}</span>
                    <span style="font-size: 13px; color: #6b7280; text-align: left; line-height: 1.2; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${node.email}</span>
                </div>
            `).join('');
            
            searchResults.style.display = 'block';

            searchResults.querySelectorAll('.search-result-item').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    const nodeId = parseInt(this.dataset.id);
                    const nodeEl = document.querySelector(`.org-node[data-id="${nodeId}"]`);
                    
                    if (nodeEl) {
                        centerNode(nodeEl);
                        searchResults.style.display = 'none';
                        searchInput.value = '';
                        allNodes = [];
                    }
                });
            });
        }

        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                const nodes = loadAllNodes();
                if (nodes.length > 0) {
                    showResults(nodes);
                } else {
                    setTimeout(() => {
                        const nodes2 = loadAllNodes();
                        if (nodes2.length > 0) showResults(nodes2);
                    }, 300);
                }
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const nodes = loadAllNodes();
                    const query = this.value.toLowerCase().trim();
                    if (!query) {
                        showResults(nodes);
                        return;
                    }
                    const filtered = nodes.filter(n => 
                        n.name.toLowerCase().includes(query) || 
                        n.email.toLowerCase().includes(query)
                    );
                    showResults(filtered);
                }, 200);
            });

            searchInput.addEventListener('blur', function() {
                setTimeout(() => { 
                    if (searchResults) searchResults.style.display = 'none'; 
                }, 300);
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (searchResults) searchResults.style.display = 'none';
                    this.blur();
                }
            });
        }

        document.addEventListener('click', function(e) {
            if (searchInput && searchResults) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            }
        });

        document.addEventListener('livewire:navigated', function() {
            setTimeout(() => { allNodes = []; loadAllNodes(); }, 300);
        });

        setTimeout(() => { allNodes = []; loadAllNodes(); }, 500);

        // ============================================================
        // BOTÓN DE PANTALLA COMPLETA
        // ============================================================
        const fullscreenBtn = document.getElementById('fullscreen-toggle');
        const iconExpand = document.getElementById('fullscreen-icon-expand');
        const iconCompress = document.getElementById('fullscreen-icon-compress');

        if (fullscreenBtn && container) {
            const setFullscreenMode = function(isFullscreen) {
                document.body.classList.toggle('org-chart-fullscreen', isFullscreen);
                iconExpand.style.display = isFullscreen ? 'none' : 'block';
                iconCompress.style.display = isFullscreen ? 'block' : 'none';
                fullscreenBtn.setAttribute('aria-label', isFullscreen ? 'Salir de pantalla completa' : 'Ver en pantalla completa');
                if (isFullscreen) setTimeout(resetView, 100);
            };

            fullscreenBtn.addEventListener('click', function() {
                setFullscreenMode(!document.body.classList.contains('org-chart-fullscreen'));
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.body.classList.contains('org-chart-fullscreen')) {
                    setFullscreenMode(false);
                }
            });
        }
    });
</script>
@endpush
