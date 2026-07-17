@props(['node', 'depth' => 0])

<div class="flex w-full flex-col items-center">
    <!-- Nodo actual (superior) -->
    <button type="button" wire:key="org-node-{{ $node['id'] }}" wire:click="selectUser({{ (int) $node['id'] }})"
        class="org-node inline-flex min-w-[200px] flex-shrink-0 flex-col rounded-xl border border-gray-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-blue-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-sm font-semibold text-gray-800">{{ $node['name'] }}</p>
                <p class="text-xs text-gray-500">{{ $node['email'] }}</p>
            </div>
            @if (($node['superior_count'] ?? 0) > 1)
                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                    title="Reporta a múltiples superiores">
                    {{ $node['superior_count'] }} jefes
                </span>
            @endif
        </div>

        <div class="mt-2 flex flex-wrap gap-1">
            @if (! empty($node['role']))
                <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">{{ $node['role'] }}</span>
            @endif
            @if (! empty($node['job_position']))
                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">{{ $node['job_position'] }}</span>
            @endif
            @if (! empty($node['physical_area']))
                <span class="rounded-md bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-violet-700">{{ $node['physical_area'] }}</span>
            @endif
        </div>

        @if (($node['subordinate_count'] ?? 0) > 0)
            <p class="mt-2 text-[10px] text-gray-400">{{ $node['subordinate_count'] }} subordinado(s) directo(s)</p>
        @endif
    </button>

    <!-- Conectores y subnodos (solo si tiene hijos) -->
    @if (! empty($node['children']) && count($node['children']) > 0)
        <!-- Línea vertical que baja del nodo padre -->
        <div class="h-8 w-px flex-shrink-0 bg-gray-300"></div>

        <!-- Contenedor de los hijos (distribución horizontal) -->
        <div class="relative flex flex-wrap justify-center gap-6 pt-4">
            <!-- Línea horizontal que une a todos los hijos (solo el ancho necesario) -->
            <div class="absolute left-0 right-0 top-0 h-px bg-gray-300" style="width: calc(100% - 0px);"></div>

            @foreach ($node['children'] as $child)
                <div class="relative flex flex-col items-center">
                    <!-- Línea vertical que conecta cada hijo con la horizontal -->
                    <div class="h-4 w-px flex-shrink-0 bg-gray-300"></div>
                    <x-administracion.organigrama-node :node="$child" :depth="$depth + 1" />
                </div>
            @endforeach
        </div>
    @endif
</div>