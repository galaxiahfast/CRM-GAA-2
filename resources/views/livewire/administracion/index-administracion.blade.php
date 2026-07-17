@php
    $role = auth()->user()->role->role;
    $roleId = (int) auth()->user()->role_id;

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

            @if (in_array($roleId, [1, 2], true))
                <a href="{{ route('administracion.section') }}" class="block">
                    <x-access-icon color="blue" icon="feathericon-users" text="Usuarios" />
                </a>
            @endif
            @if ($roleId === 1)
                <a href="{{ route('administracion.role') }}" class="block">
                    <x-access-icon color="red" icon="feathericon-lock" text="Roles" />
                </a>
                {{-- Marcador de posición: el módulo de permisos aún no tiene destino funcional. --}}
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
                <a href="{{ route('administracion.create.users') }}"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                    Agregar usuario
                </a>
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
                    <!-- Contenedor con pan y zoom (touch-action: none para evitar scroll táctil) -->
                    <div id="org-tree-container" class="relative overflow-hidden" style="height: 70vh; background: #f9fafb; border-radius: 0.75rem; border: 1px solid #e5e7eb; touch-action: none;">
                        <div id="org-tree-wrapper" class="origin-top-left" style="transform: scale(1) translate(0px, 0px); cursor: grab; width: max-content; min-width: 100%; padding: 2rem;">
                            <div class="flex flex-col items-center gap-6" style="min-width: max-content;">
                                @foreach ($orgChartTree as $rootNode)
                                    @if ($rootNode['subordinate_count'] > 0)
                                        <x-administracion.organigrama-node :node="$rootNode" :depth="0" />
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <!-- Instrucciones de navegación -->
                        <div class="absolute bottom-2 right-2 rounded bg-white/80 px-3 py-1 text-xs text-gray-500 shadow backdrop-blur-sm">
                            🖱 Arrastra para mover · Rueda para zoom · Doble clic para reset
                        </div>
                    </div>
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
                            <li>
                                <button type="button" wire:key="unassigned-user-{{ $user['id'] }}" wire:click="selectUser({{ (int) $user['id'] }})"
                                    class="w-full rounded-lg border border-gray-200 bg-white p-3 text-left shadow-sm transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Todos los usuarios tienen jefe, puesto y área asignados.</p>
                @endif
            </div>
        </div>
    </div>

    @if ($selectedUserDetails)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="user-details-title">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b p-6">
                    <div>
                        <h2 id="user-details-title" class="text-xl font-semibold text-gray-900">{{ $selectedUserDetails['name'] }}</h2>
                        <p class="mt-1 text-sm text-gray-500">Detalles del usuario</p>
                    </div>
                    <button type="button" wire:click="closeUserDetails" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Cerrar">&times;</button>
                </div>

                @if ($isEditingUser)
                    <form wire:submit="saveSelectedUser" class="space-y-4 p-6 text-sm">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div><x-label for="edit-name" value="Nombre" /><x-input id="edit-name" class="mt-1 block w-full" wire:model="userForm.name" /><x-input-error for="userForm.name" /></div>
                            <div><x-label for="edit-last-name" value="Apellido" /><x-input id="edit-last-name" class="mt-1 block w-full" wire:model="userForm.last_name" /><x-input-error for="userForm.last_name" /></div>
                            <div><x-label for="edit-email" value="Email" /><x-input id="edit-email" type="email" class="mt-1 block w-full" wire:model="userForm.email" /><x-input-error for="userForm.email" /></div>
                            <div><x-label for="edit-employee-id" value="ID del checador" /><x-input id="edit-employee-id" class="mt-1 block w-full" wire:model="userForm.employee_id" /><x-input-error for="userForm.employee_id" /></div>
                            <div><x-label for="edit-role" value="Rol" /><select id="edit-role" wire:model.live="userForm.role_id" class="mt-1 block w-full rounded-md border-gray-300"><option value="">Seleccione un rol</option>@foreach ($roles as $availableRole)<option value="{{ $availableRole->id }}">{{ $availableRole->role }}</option>@endforeach</select><x-input-error for="userForm.role_id" /></div>
                            <div><x-label for="edit-position" value="Puesto" /><select id="edit-position" wire:model="userForm.job_position_id" class="mt-1 block w-full rounded-md border-gray-300"><option value="">Seleccione un puesto</option>@foreach ($jobPositions as $position)<option value="{{ $position->id }}">{{ $position->name }}</option>@endforeach</select><x-input-error for="userForm.job_position_id" /></div>
                            <div><x-label for="edit-area" value="Área / departamento" /><select id="edit-area" wire:model="userForm.physical_area_id" class="mt-1 block w-full rounded-md border-gray-300"><option value="">Seleccione un área</option>@foreach ($physicalAreas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select><x-input-error for="userForm.physical_area_id" /></div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-2">
                            <div>
                                <x-label for="edit-superiors" value="Jefes directos" />
                                <select id="edit-superiors" multiple wire:model="userForm.superior_ids" class="mt-1 block w-full rounded-md border-gray-300">
                                    @foreach ($availableUsers as $availableUser)
                                        <option value="{{ $availableUser->id }}">{{ trim($availableUser->name.' '.$availableUser->last_name) }} — {{ $availableUser->email }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="userForm.superior_ids" />
                            </div>
                            <div>
                                <x-label for="edit-subordinates" value="Subordinados directos" />
                                <select id="edit-subordinates" multiple wire:model="userForm.subordinate_ids" class="mt-1 block w-full rounded-md border-gray-300">
                                    @foreach ($availableUsers as $availableUser)
                                        <option value="{{ $availableUser->id }}">{{ trim($availableUser->name.' '.$availableUser->last_name) }} — {{ $availableUser->email }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="userForm.subordinate_ids" />
                            </div>
                        </div>
                        @if ($userForm['is_auxiliar'] ?? false)
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div><x-label for="edit-hourly-rate" value="Precio por hora" /><x-input id="edit-hourly-rate" type="number" step="0.01" class="mt-1 block w-full" wire:model="userForm.hourly_rate" /><x-input-error for="userForm.hourly_rate" /></div>
                                <div><x-label for="edit-food-allowance" value="Apoyo económico por día" /><x-input id="edit-food-allowance" type="number" step="0.01" class="mt-1 block w-full" wire:model="userForm.food_allowance" /><x-input-error for="userForm.food_allowance" /></div>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-2">
                            <div><x-label for="edit-password" value="Nueva contraseña (opcional)" /><x-input id="edit-password" type="password" class="mt-1 block w-full" wire:model="userForm.password" /><x-input-error for="userForm.password" /></div>
                            <div><x-label for="edit-password-confirmation" value="Confirmar nueva contraseña" /><x-input id="edit-password-confirmation" type="password" class="mt-1 block w-full" wire:model="userForm.password_confirmation" /></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2"><button type="button" wire:click="cancelEditingUser" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Cancelar</button><button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Guardar cambios</button></div>
                    </form>
                @else
                <div class="space-y-6 p-6 text-sm">
                    <section>
                        <h3 class="mb-3 font-semibold text-gray-800">Datos generales</h3>
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div><dt class="text-xs text-gray-500">Nombre completo</dt><dd>{{ $selectedUserDetails['name'] }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Rol</dt><dd>{{ $selectedUserDetails['role'] ?: 'Sin rol' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Email</dt><dd>{{ $selectedUserDetails['email'] }}</dd></div>
                            <div><dt class="text-xs text-gray-500">ID del checador</dt><dd>{{ $selectedUserDetails['employee_id'] ?: 'No asignado' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Fecha de creación</dt><dd>{{ $selectedUserDetails['created_at'] }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Fecha de actualización</dt><dd>{{ $selectedUserDetails['updated_at'] }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Contraseña</dt><dd class="text-gray-600">Protegida: no se muestra ni se expone en esta vista.</dd></div>
                        </dl>
                    </section>

                    @if ($selectedUserDetails['is_auxiliar'])
                        <section class="rounded-xl bg-amber-50 p-4">
                            <h3 class="mb-3 font-semibold text-amber-900">Datos de auxiliar</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <p><span class="text-xs text-amber-700">Precio por hora</span><br>${{ number_format((float) $selectedUserDetails['hourly_rate'], 2) }}</p>
                                <p><span class="text-xs text-amber-700">Apoyo económico por día</span><br>${{ number_format((float) $selectedUserDetails['food_allowance'], 2) }}</p>
                            </div>
                        </section>
                    @endif

                    <section>
                        <h3 class="mb-3 font-semibold text-gray-800">Datos organizacionales</h3>
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div><dt class="text-xs text-gray-500">Puesto de trabajo</dt><dd>{{ $selectedUserDetails['job_position'] ?: 'Sin asignar' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Área / departamento</dt><dd>{{ $selectedUserDetails['physical_area'] ?: 'Sin asignar' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Jefes directos</dt><dd>{{ count($selectedUserDetails['superiors']) ? implode(', ', $selectedUserDetails['superiors']) : 'Sin jefe asignado' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Subordinados directos</dt><dd>{{ count($selectedUserDetails['subordinates']) ? implode(', ', $selectedUserDetails['subordinates']) : 'Sin subordinados' }}</dd></div>
                        </dl>
                    </section>

                    <section class="border-t pt-5">
                        <h3 class="font-semibold text-red-700">Eliminar usuario</h3>
                        <p class="mt-1 text-xs text-gray-600">Para confirmar, escribe exactamente: <span class="font-semibold">{{ $selectedUserDetails['name'] }}</span></p>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                            <input type="text" wire:model="deleteConfirmationName" class="w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500" placeholder="Nombre completo del usuario">
                            <button type="button" wire:click="deleteSelectedUser" class="shrink-0 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Eliminar usuario</button>
                        </div>
                        @error('deleteConfirmationName') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </section>
                </div>
                @endif

                <div class="flex justify-end gap-3 border-t bg-gray-50 p-4">
                    @if (! $isEditingUser)
                        <button type="button" wire:click="beginEditingUser" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Editar</button>
                    @endif
                    @if (false)
                    <a href="{{ route('administracion.edit.users', $selectedUserDetails['id']) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Editar información</a>
                    @endif
                    <button type="button" wire:click="closeUserDetails" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Cerrar</button>
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

        // --- Zoom con rueda del mouse (centrado en el puntero) ---
        container.addEventListener('wheel', function(e) {
            e.preventDefault();

            const rect = container.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const newScale = Math.min(Math.max(scale * delta, 0.3), 3);

            // Calcular desplazamiento para centrar el zoom en el puntero
            const dx = (mouseX - translateX) * (1 - newScale / scale);
            const dy = (mouseY - translateY) * (1 - newScale / scale);

            scale = newScale;
            translateX += dx;
            translateY += dy;

            updateTransform();
        }, { passive: false });

        // --- Pan con arrastre (solo si NO se hace clic en un nodo) ---
        container.addEventListener('mousedown', function(e) {
            // Si el clic fue en un elemento con clase 'org-node' o un descendiente, no iniciar pan
            let target = e.target;
            while (target && target !== container) {
                if (target.classList && target.classList.contains('org-node')) {
                    return; // Ignorar, es un nodo
                }
                target = target.parentNode;
            }

            // También ignorar si se hizo clic en botones, enlaces, etc. (opcional)
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

        // Prevenir selección de texto durante el arrastre
        container.addEventListener('selectstart', function(e) {
            if (isPanning) e.preventDefault();
        });

        // Reset al hacer doble clic
        container.addEventListener('dblclick', function(e) {
            // Evitar que el doble clic seleccione texto o dispare otros eventos
            e.preventDefault();
            scale = 1;
            translateX = 0;
            translateY = 0;
            updateTransform();
        });
    });
</script>
@endpush