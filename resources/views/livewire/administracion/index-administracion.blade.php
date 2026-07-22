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

<div class="space-y-4 w-full min-w-[800px]" style="font-size: 15px;">
    <!-- Accesos rápidos -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
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
    <div class="overflow-hidden rounded-2xl bg-white shadow-lg" style="padding: 80px;">

        <!-- Encabezado -->
        <div style="padding: 0 0 20px 0; background-color: transparent; display: flex; flex-wrap: nowrap; align-items: flex-start; justify-content: space-between; gap: 32px; min-width: max-content;">
            <div style="max-width: 672px; flex-shrink: 0;">
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; height: 56px; width: 56px; align-items: center; justify-content: center; border-radius: 0px; background-color: rgba(26, 58, 107, 0.1); flex-shrink: 0;">
                        <svg style="height: 28px; width: 28px; color: #1A3A6B; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 style="font-size: 24px; font-weight: 700; letter-spacing: -0.025em; color: #111827; white-space: nowrap;">
                            Organigrama
                        </h1>
                        <p style="font-size: 15px; color: #6b7280; white-space: nowrap;">
                            Estructura jerárquica dinámica
                        </p>
                    </div>
                </div>
                <p style="max-width: 672px; font-size: 15px; line-height: 28px; color: #6b7280;">
                    Visualización jerárquica de la organización,<br>con relaciones de supervisión y áreas.
                </p>
                <!-- Estadísticas completas -->
                <div style="display: flex; flex-wrap: wrap; gap: 25px; margin-top: 15px; font-size: 15px; color: #6b7280;">
                    <span><strong style="color: #1A3A6B;">{{ $totalUsers ?? 0 }}</strong> Usuarios totales</span>
                    <span><strong style="color: #1A3A6B;">{{ $orgChartStats['in_tree'] ?? 0 }}</strong> En árbol</span>
                    <span><strong style="color: #1A3A6B;">{{ $orgChartStats['relations'] ?? 0 }}</strong> Relaciones</span>
                    <span><strong style="color: #1A3A6B;">{{ $totalRoles ?? 0 }}</strong> Roles</span>
                    <span><strong style="color: #1A3A6B;">30</strong> Permisos</span>
                </div>
            </div>
        </div>

        <!-- Fila de controles (solo botones) -->
        <div style="padding: 12px 0 80px 0; display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
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
        <div class="grid grid-cols-1 gap-6 p-0 xl:grid-cols-3" style="min-width: 800px; padding: 0;">

            <!-- Árbol jerárquico -->
            <div class="xl:col-span-2" style="padding: 0;">
                @if (($orgChartStats['cycles_detected'] ?? 0) > 0)
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[15px] text-red-700">
                        Se detectaron {{ $orgChartStats['cycles_detected'] }} ciclo(s) en las relaciones jerárquicas.
                        El árbol se renderiza de forma segura omitiendo ramas circulares.
                    </div>
                @endif

                @if (count($orgChartTree) > 0)
                    <!-- Contenedor con pan y zoom: altura igual al panel de la derecha -->
                    <div id="org-tree-container" class="relative overflow-hidden border border-dashed border-gray-300 p-5" style="max-height: 520px; height: auto; background: #f9fafb; border-radius: 0.75rem; overflow-y: auto; touch-action: none;">
                        <div id="org-tree-wrapper" class="origin-top-left" style="transform: scale(1) translate(0px, 0px); cursor: grab; width: max-content; min-width: 1000px; padding: 20px;">
                            <div class="flex flex-col items-center gap-6" style="min-width: max-content;">
                                @foreach ($orgChartTree as $rootNode)
                                    @if (($rootNode['subordinate_count'] ?? 0) > 0 || count($rootNode['children'] ?? []) > 0)
                                        <x-administracion.organigrama-node :node="$rootNode" :depth="0" />
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- LEYENDA (sin la palabra "Rol") -->
                        <div class="absolute top-0 left-0 p-5 bg-white/80 backdrop-blur-sm rounded-br-xl border-r border-b border-white/30 shadow-sm" style="z-index: 30;">
                            <div class="text-[15px] text-gray-700 flex flex-col items-start gap-[10px]">
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
                    <x-no-data title="Sin datos" subTitle="No hay nodos raíz para el filtro seleccionado." />
                @endif
            </div>

            <!-- Usuarios sin asignar (con padding 20px, sin bordes entre elementos, fuente 15px) -->
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5" style="min-width: 250px;">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-[15px] font-semibold text-gray-700">Usuarios sin asignar</h3>
                    <span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-[15px] font-medium text-gray-700">
                        {{ count($unassignedUsers) }}
                    </span>
                </div>
                <p class="mb-4 text-[15px] text-gray-500">
                    Usuarios que aún no tienen jefe, puesto o área asignada.
                </p>
                @if (count($unassignedUsers) > 0)
                    <ul class="max-h-[520px] space-y-[10px] overflow-y-auto">
                        @foreach ($unassignedUsers as $user)
                            <li>
                                <button type="button" wire:key="unassigned-user-{{ $user['id'] }}" wire:click="selectUser({{ (int) $user['id'] }})"
                                    class="w-full rounded-lg bg-white p-3 text-left shadow-sm transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-[15px] font-medium text-gray-800">{{ $user['name'] }}</p>
                                    <p class="text-[15px] text-gray-500">{{ $user['email'] }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($user['missing'] as $missingKey)
                                            <span @class([
                                                'rounded-md px-2 py-0.5 text-[15px] font-medium',
                                                'bg-red-50 text-red-700' => $missingKey === 'superior',
                                                'bg-orange-50 text-orange-700' => $missingKey === 'job_position',
                                                'bg-yellow-50 text-yellow-700' => $missingKey === 'physical_area',
                                            ])>
                                                {{ $missingLabels[$missingKey] ?? $missingKey }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if (! empty($user['role']) || ! empty($user['job_position']) || ! empty($user['physical_area']))
                                        <div class="mt-2 flex flex-wrap gap-[10px] border-t border-gray-100 pt-2">
                                            @if (! empty($user['role']))
                                                <span class="text-[15px] text-gray-400">Rol: {{ $user['role'] }}</span>
                                            @endif
                                            @if (! empty($user['job_position']))
                                                <span class="text-[15px] text-gray-400">Puesto: {{ $user['job_position'] }}</span>
                                            @endif
                                            @if (! empty($user['physical_area']))
                                                <span class="text-[15px] text-gray-400">Área: {{ $user['physical_area'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-[15px] text-gray-500">Todos los usuarios tienen jefe, puesto y área asignados.</p>
                @endif
            </div>
        </div>
    </div>

    @if ($selectedUserDetails)
        <!-- MODAL (sin cambios) -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="user-details-title">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b p-6">
                    <div>
                        <h2 id="user-details-title" class="text-[15px] font-semibold text-gray-900">{{ $selectedUserDetails['name'] }}</h2>
                        <p class="mt-1 text-[15px] text-gray-500">Detalles del usuario</p>
                    </div>
                    <button type="button" wire:click="closeUserDetails" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Cerrar">&times;</button>
                </div>

                @if ($isEditingUser)
                    <form wire:submit="saveSelectedUser" class="space-y-4 p-6 text-[15px]">
                        <div class="grid grid-cols-2 gap-4">
                            <div><x-label for="edit-name" value="Nombre" /><x-input id="edit-name" class="mt-1 block w-full" wire:model="userForm.name" /><x-input-error for="userForm.name" /></div>
                            <div><x-label for="edit-last-name" value="Apellido" /><x-input id="edit-last-name" class="mt-1 block w-full" wire:model="userForm.last_name" /><x-input-error for="userForm.last_name" /></div>
                            <div><x-label for="edit-email" value="Email" /><x-input id="edit-email" type="email" class="mt-1 block w-full" wire:model="userForm.email" /><x-input-error for="userForm.email" /></div>
                            <div><x-label for="edit-employee-id" value="ID del checador" /><x-input id="edit-employee-id" class="mt-1 block w-full" wire:model="userForm.employee_id" /><x-input-error for="userForm.employee_id" /></div>
                            <div><x-label for="edit-role" value="Rol" /><select id="edit-role" wire:model.live="userForm.role_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un rol</option>@foreach ($roles as $availableRole)<option value="{{ $availableRole->id }}">{{ $availableRole->role }}</option>@endforeach</select><x-input-error for="userForm.role_id" /></div>
                            <div><x-label for="edit-position" value="Puesto" /><select id="edit-position" wire:model="userForm.job_position_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un puesto</option>@foreach ($jobPositions as $position)<option value="{{ $position->id }}">{{ $position->name }}</option>@endforeach</select><x-input-error for="userForm.job_position_id" /></div>
                            <div><x-label for="edit-area" value="Área / departamento" /><select id="edit-area" wire:model="userForm.physical_area_id" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]"><option value="">Seleccione un área</option>@foreach ($physicalAreas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select><x-input-error for="userForm.physical_area_id" /></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-t pt-4">
                            <div>
                                <x-label for="edit-superiors" value="Jefes directos" />
                                <select id="edit-superiors" multiple wire:model="userForm.superior_ids" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]">
                                    @foreach ($availableUsers as $availableUser)
                                        <option value="{{ $availableUser->id }}">{{ trim($availableUser->name.' '.$availableUser->last_name) }} — {{ $availableUser->email }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="userForm.superior_ids" />
                            </div>
                            <div>
                                <x-label for="edit-subordinates" value="Subordinados directos" />
                                <select id="edit-subordinates" multiple wire:model="userForm.subordinate_ids" class="mt-1 block w-full rounded-md border-gray-300 text-[15px]">
                                    @foreach ($availableUsers as $availableUser)
                                        <option value="{{ $availableUser->id }}">{{ trim($availableUser->name.' '.$availableUser->last_name) }} — {{ $availableUser->email }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="userForm.subordinate_ids" />
                            </div>
                        </div>
                        @if ($userForm['is_auxiliar'] ?? false)
                            <div class="grid grid-cols-2 gap-4">
                                <div><x-label for="edit-hourly-rate" value="Precio por hora" /><x-input id="edit-hourly-rate" type="number" step="0.01" class="mt-1 block w-full" wire:model="userForm.hourly_rate" /><x-input-error for="userForm.hourly_rate" /></div>
                                <div><x-label for="edit-food-allowance" value="Apoyo económico por día" /><x-input id="edit-food-allowance" type="number" step="0.01" class="mt-1 block w-full" wire:model="userForm.food_allowance" /><x-input-error for="userForm.food_allowance" /></div>
                            </div>
                        @endif
                        <div class="grid grid-cols-2 gap-4 border-t pt-4">
                            <div><x-label for="edit-password" value="Nueva contraseña (opcional)" /><x-input id="edit-password" type="password" class="mt-1 block w-full" wire:model="userForm.password" /><x-input-error for="userForm.password" /></div>
                            <div><x-label for="edit-password-confirmation" value="Confirmar nueva contraseña" /><x-input id="edit-password-confirmation" type="password" class="mt-1 block w-full" wire:model="userForm.password_confirmation" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2"><button type="button" wire:click="cancelEditingUser" class="rounded-lg border border-gray-300 px-4 py-2 text-[15px] font-medium text-gray-700 hover:bg-gray-100">Cancelar</button><button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-[15px] font-medium text-white hover:bg-blue-700">Guardar cambios</button></div>
                    </form>
                @else
                <div class="space-y-6 p-6 text-[15px]">
                    <section>
                        <h3 class="mb-3 font-semibold text-gray-800">Datos generales</h3>
                        <dl class="grid grid-cols-2 gap-3">
                            <div><dt class="text-[15px] text-gray-500">Nombre completo</dt><dd>{{ $selectedUserDetails['name'] }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Rol</dt><dd>{{ $selectedUserDetails['role'] ?: 'Sin rol' }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Email</dt><dd>{{ $selectedUserDetails['email'] }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">ID del checador</dt><dd>{{ $selectedUserDetails['employee_id'] ?: 'No asignado' }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Fecha de creación</dt><dd>{{ $selectedUserDetails['created_at'] }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Fecha de actualización</dt><dd>{{ $selectedUserDetails['updated_at'] }}</dd></div>
                            <div class="col-span-2"><dt class="text-[15px] text-gray-500">Contraseña</dt><dd class="text-gray-600">Protegida: no se muestra ni se expone en esta vista.</dd></div>
                        </dl>
                    </section>

                    @if ($selectedUserDetails['is_auxiliar'])
                        <section class="rounded-xl bg-amber-50 p-4">
                            <h3 class="mb-3 font-semibold text-amber-900">Datos de auxiliar</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <p><span class="text-[15px] text-amber-700">Precio por hora</span><br>${{ number_format((float) $selectedUserDetails['hourly_rate'], 2) }}</p>
                                <p><span class="text-[15px] text-amber-700">Apoyo económico por día</span><br>${{ number_format((float) $selectedUserDetails['food_allowance'], 2) }}</p>
                            </div>
                        </section>
                    @endif

                    <section>
                        <h3 class="mb-3 font-semibold text-gray-800">Datos organizacionales</h3>
                        <dl class="grid grid-cols-2 gap-3">
                            <div><dt class="text-[15px] text-gray-500">Puesto de trabajo</dt><dd>{{ $selectedUserDetails['job_position'] ?: 'Sin asignar' }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Área / departamento</dt><dd>{{ $selectedUserDetails['physical_area'] ?: 'Sin asignar' }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Jefes directos</dt><dd>{{ count($selectedUserDetails['superiors']) ? implode(', ', $selectedUserDetails['superiors']) : 'Sin jefe asignado' }}</dd></div>
                            <div><dt class="text-[15px] text-gray-500">Subordinados directos</dt><dd>{{ count($selectedUserDetails['subordinates']) ? implode(', ', $selectedUserDetails['subordinates']) : 'Sin subordinados' }}</dd></div>
                        </dl>
                    </section>

                    <section class="border-t pt-5">
                        <h3 class="font-semibold text-red-700">Eliminar usuario</h3>
                        <p class="mt-1 text-[15px] text-gray-600">Para confirmar, escribe exactamente: <span class="font-semibold">{{ $selectedUserDetails['name'] }}</span></p>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                            <input type="text" wire:model="deleteConfirmationName" class="w-full rounded-lg border-gray-300 text-[15px] focus:border-red-500 focus:ring-red-500" placeholder="Nombre completo del usuario">
                            <button type="button" wire:click="deleteSelectedUser" class="shrink-0 rounded-lg bg-red-600 px-4 py-2 text-[15px] font-medium text-white hover:bg-red-700">Eliminar usuario</button>
                        </div>
                        @error('deleteConfirmationName') <p class="mt-2 text-[15px] text-red-600">{{ $message }}</p> @enderror
                    </section>
                </div>
                @endif

                <div class="flex justify-end gap-3 border-t bg-gray-50 p-4">
                    @if (! $isEditingUser)
                        <button type="button" wire:click="beginEditingUser" class="rounded-lg bg-blue-600 px-4 py-2 text-[15px] font-medium text-white hover:bg-blue-700">Editar</button>
                    @endif
                    <button type="button" wire:click="closeUserDetails" class="rounded-lg border border-gray-300 px-4 py-2 text-[15px] font-medium text-gray-700 hover:bg-gray-100">Cerrar</button>
                </div>
            </div>
        </div>
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
                // Sin resaltado
            });
        }

        // Eventos del contenedor
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
                const nameEl = el.querySelector('.font-semibold');
                const emailEl = el.querySelector('.text-gray-500');
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
                <div class="search-result-item" data-id="${node.id}" style="padding: 10px 16px; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s; display: flex; align-items: center; gap: 10px;">
                    <div style="display: flex; flex-direction: column; min-width: 0; flex: 1; overflow: hidden;">
                        <span style="font-weight: 500; color: #111827; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${node.name}</span>
                        <span style="font-size: 13px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${node.email}</span>
                    </div>
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

                el.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f4ff';
                    this.style.borderLeft = '3px solid #1e3a8a';
                });
                el.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'transparent';
                    this.style.borderLeft = 'none';
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