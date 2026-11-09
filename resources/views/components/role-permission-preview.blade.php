@props([
    'permissions',
    'selectedIds' => [],
])

{{-- Vista de solo lectura: qué apartados incluye el rol. Sin cajas de ícono ni controles que no hacen nada. --}}
<div class="role-access-list">
    @forelse ($permissions->groupBy(fn ($permission) => $permission->module ?: 'General') as $module => $modulePermissions)
        <fieldset class="role-access-group">
            <legend>{{ $module }}</legend>

            <ul>
                @foreach ($modulePermissions as $permission)
                    @php($included = in_array((int) $permission->id, $selectedIds, true))
                    <li wire:key="role-access-{{ $permission->id }}" @class(['role-access-row', 'is-included' => $included])>
                        <div class="min-w-0">
                            <p class="role-access-name" title="{{ $permission->name }}">{{ $permission->name }}</p>
                            @if ($permission->description)
                                <p class="role-access-description">{{ $permission->description }}</p>
                            @endif
                        </div>
                        <span class="role-access-state">{{ $included ? 'Incluido' : 'Sin acceso' }}</span>
                    </li>
                @endforeach
            </ul>
        </fieldset>
    @empty
        <p class="role-access-empty">No hay permisos activos en el catálogo.</p>
    @endforelse
</div>
