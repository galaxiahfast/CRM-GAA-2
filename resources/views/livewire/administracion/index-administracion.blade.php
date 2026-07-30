@php
    $role = auth()->user()->role->role;
    $roleId = (int) auth()->user()->role_id;

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
    </style>

    <!-- Accesos rápidos -->
    <div class="overflow-hidden rounded-2xl shadow-lg" style="background-color: #F3F3F3;">
        <div class="border-b p-4">
            <h2 class="text-[15px] font-semibold text-gray-700">Secciones principales</h2>
        </div>
        <div class="grid grid-cols-6 justify-items-center gap-6 p-4">
            @if (in_array($roleId, [1, 2], true))
                <a href="{{ route('administracion.section') }}" class="block">
                    <x-access-icon color="blue" icon="feathericon-users" text="Usuarios" />
                </a>
            @endif
            @if ($roleId === 1)
                <a href="{{ route('administracion.role') }}" class="block">
                    <x-access-icon color="red" icon="feathericon-lock" text="Roles" />
                </a>
                <div aria-disabled="true">
                    <x-access-icon color="green" icon="feathericon-shield" text="Permisos" />
                </div>
            @endif
            @if (in_array($roleId, [1, 2, 3, 4], true))
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
    @if (auth()->user()->isAdmin())

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

        <!-- Grupo 4: Fila de controles / Botones -->
        <div style="margin-top: 35px; padding: 0 0 80px 0; display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
            <a href="{{ route('administracion.create.users') }}"
                class="inline-flex items-center justify-center rounded-lg text-[15px] font-medium text-white shadow-sm"
                style="width: 180px; height: 50px; background-color: rgb(26, 58, 107); transition: background-color 0.2s; text-decoration: none;"
                onmouseover="this.style.backgroundColor='rgb(20, 46, 85)'"
                onmouseout="this.style.backgroundColor='rgb(26, 58, 107)'">
                Agregar Usuario
            </a>
            <a href="{{ route('administracion.role.create') }}"
                class="inline-flex items-center justify-center rounded-lg text-[15px] font-medium text-white shadow-sm"
                style="width: 180px; height: 50px; background-color: rgb(26, 58, 107); transition: background-color 0.2s; text-decoration: none;"
                onmouseover="this.style.backgroundColor='rgb(20, 46, 85)'"
                onmouseout="this.style.backgroundColor='rgb(26, 58, 107)'">
                Agregar Rol
            </a>
            <a href="{{ route('administracion.permissions') }}"
                class="inline-flex items-center justify-center rounded-lg text-[15px] font-medium text-white shadow-sm"
                style="width: 180px; height: 50px; background-color: rgb(26, 58, 107); transition: background-color 0.2s; text-decoration: none;"
                onmouseover="this.style.backgroundColor='rgb(20, 46, 85)'"
                onmouseout="this.style.backgroundColor='rgb(26, 58, 107)'">
                Gestionar Permisos
            </a>
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

                @if (count($orgChartTree) > 0)
                    <!-- Contenedor con pan y zoom -->
                    <div id="org-tree-container" class="relative overflow-hidden border border-dashed border-gray-300 p-5" style="max-height: 520px; height: auto; background: #F3F3F3; border-radius: 0.75rem; touch-action: none;">
                        <div id="org-tree-wrapper" class="origin-top-left" style="transform: scale(1) translate(0px, 0px); cursor: grab; width: max-content; min-width: 1000px; padding: 20px;">
                            <div class="flex flex-col items-center gap-6" style="min-width: max-content;">
                                @foreach ($orgChartTree as $rootNode)
                                    <x-administracion.organigrama-node :node="$rootNode" :depth="0" />
                                @endforeach
                            </div>
                        </div>

                        <!-- LEYENDA -->
                        <div class="absolute top-0 left-0 p-5 bg-white/80 backdrop-blur-sm rounded-br-xl border-r border-b border-white/30 shadow-sm" style="z-index: 30;">
                            <div class="text-[15px] text-gray-700 flex flex-col items-start gap-[10px]">
                                <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #1e3a8a;"></span> Rol</div>
                                <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #059669;"></span> Puesto</div>
                                <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #7c3aed;"></span> Área</div>
                                <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #d97706;"></span> Múltiples jefes</div>
                            </div>
                        </div>

                        <!-- BUSCADOR + SELECT -->
                        <div class="absolute top-0 right-0 p-5 flex items-center gap-5" style="z-index: 30;">
                            <div style="position: relative; flex: 1;">
                                <input 
                                    id="node-search-input"
                                    type="text" 
                                    placeholder="Buscar colaborador..."
                                    class="rounded-lg text-[15px] border-gray-300 bg-white/80 backdrop-blur-sm shadow-sm"
                                    style="height: 50px; min-width: 220px; width: 100%; padding: 0 16px; border: 1px solid #d1d5db; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30;"
                                    autocomplete="off"
                                >
                                <div 
                                    id="search-results"
                                    style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: rgba(255,255,255,0.98); backdrop-filter: blur(8px); border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-height: 300px; overflow-y: auto; display: none; z-index: 100;"
                                >
                                    <style>
                                        #search-results::-webkit-scrollbar {
                                            width: 4px;
                                            height: 4px;
                                        }
                                        #search-results::-webkit-scrollbar-track {
                                            background: #f1f1f1;
                                            border-radius: 10px;
                                        }
                                        #search-results::-webkit-scrollbar-thumb {
                                            background: #1A3A6B;
                                            border-radius: 10px;
                                        }
                                        #search-results::-webkit-scrollbar-thumb:hover {
                                            background: #15305a;
                                        }
                                        #search-results {
                                            scrollbar-width: thin;
                                            scrollbar-color: #1A3A6B #f1f1f1;
                                        }

                                    </style>
                                </div>
                            </div>
                            <select id="physical-area-filter" wire:model.live="selectedPhysicalAreaId"
                                class="rounded-lg text-[15px] border-gray-300 bg-white/80 backdrop-blur-sm shadow-sm"
                                style="height: 50px; min-width: 180px; padding: 0 16px; border: 1px solid #d1d5db; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30; cursor: pointer; background-color: rgba(255, 255, 255, 0.8);">
                                <option value="">Todas las áreas</option>
                                @foreach ($physicalAreas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- INSTRUCCIONES -->
                        <div class="absolute bottom-0 right-0 p-5 bg-white/80 backdrop-blur-sm rounded-tl-xl border-l border-t border-white/30 shadow-sm" style="z-index: 30;">
                            <div class="text-[15px] text-gray-500">
                                Arrastra para mover · Rueda para zoom · Doble clic para reset
                            </div>
                        </div>
                    </div>
                @else
                    <x-no-data title="Sin datos" subTitle="No hay nodos para el filtro seleccionado." />
                @endif
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
{{-- ============================================================ --}}
{{-- MODAL DE USUARIO --}}
{{-- ============================================================ --}}
@if ($selectedUserDetails)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="user-details-title">
        <!-- Contenedor principal: altura fija de 80vh -->
        <div class="org-user-modal relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-gray-300 bg-[#F3F3F3] shadow-2xl" style="height: min(86vh, 820px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;">
            
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
                            <div><x-label for="edit-employee-id" value="ID del checador" class="text-[15px] mb-2.5 block" /><x-input id="edit-employee-id" class="mt-1 block w-full text-[15px]" wire:model="userForm.employee_id" /><x-input-error for="userForm.employee_id" /></div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div><x-label for="edit-role" value="Rol" class="text-[15px] mb-2.5 block" /><select id="edit-role" wire:model.live="userForm.role_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un rol</option>@foreach ($roles as $availableRole)<option value="{{ $availableRole->id }}">{{ $availableRole->role }}</option>@endforeach</select><x-input-error for="userForm.role_id" /></div>
                            <div><x-label for="edit-position" value="Puesto" class="text-[15px] mb-2.5 block" /><select id="edit-position" wire:model="userForm.job_position_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un puesto</option>@foreach ($jobPositions as $position)<option value="{{ $position->id }}">{{ $position->name }}</option>@endforeach</select><x-input-error for="userForm.job_position_id" /></div>
                        </div>
                        <div><x-label for="edit-area" value="Área / departamento" class="text-[15px] mb-2.5 block" /><select id="edit-area" wire:model="userForm.physical_area_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un área</option>@foreach ($physicalAreas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select><x-input-error for="userForm.physical_area_id" /></div>
                        <div class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-[#F3F3F3] p-4">
                            <div><x-label for="edit-superiors" value="Jefes directos" class="text-[15px] mb-2.5 block" /><select id="edit-superiors" multiple wire:model="userForm.superior_ids" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]">@foreach ($availableUsers as $availableUser)<option value="{{ $availableUser->id }}">{{ trim($availableUser->name.' '.$availableUser->last_name) }} — {{ $availableUser->email }}</option>@endforeach</select><x-input-error for="userForm.superior_ids" /></div>
                            <div><x-label for="edit-subordinates" value="Subordinados directos" class="text-[15px] mb-2.5 block" /><select id="edit-subordinates" multiple wire:model="userForm.subordinate_ids" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]">@foreach ($availableUsers as $availableUser)<option value="{{ $availableUser->id }}">{{ trim($availableUser->name.' '.$availableUser->last_name) }} — {{ $availableUser->email }}</option>@endforeach</select><x-input-error for="userForm.subordinate_ids" /></div>
                        </div>
                        @if ($userForm['is_auxiliar'] ?? false)
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
                            @if ($selectedUserDetails['is_auxiliar'] ?? false)
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
            if (e.target.closest('#search-results') || e.target.closest('.search-result-item')) {
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
            if (e.target.closest('#node-search-input') || e.target.closest('#search-results') || e.target.closest('.search-result-item')) {
                return;
            }

            let target = e.target;
            while (target && target !== container) {
                if (target.classList && target.classList.contains('org-node')) {
                    return;
                }
                target = target.parentNode;
            }

            if (e.target.closest('button, a, input, select, textarea')) {
                return;
            }

            isPanning = true;
            startX = e.clientX;
            startY = e.clientY;
            startTranslateX = translateX;
            startTranslateY = translateY;
            wrapper.style.cursor = 'grabbing';
            e.preventDefault();
        });

        window.addEventListener('mousemove', function(e) {
            if (!isPanning) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
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

        container.addEventListener('selectstart', function(e) {
            if (isPanning) e.preventDefault();
        });

        container.addEventListener('dblclick', function(e) {
            e.preventDefault();
            resetView();
        });

        // ============================================================
        // BUSCADOR DE NODOS (resultados alineados a la izquierda)
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
    });
</script>
@endpush
