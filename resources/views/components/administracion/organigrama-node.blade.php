@props(['node', 'depth' => 0])

<div class="flex flex-col items-center visual-node-wrapper" wire:key="org-node-box-{{ $node['id'] }}">
    <!-- Nodo actual -->
    <button type="button" 
        wire:click="selectUser({{ $node['id'] }})"
        class="org-node inline-flex min-w-[220px] max-w-[280px] flex-shrink-0 flex-col rounded-xl border border-gray-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 z-10">
        
        <div class="flex items-start justify-between gap-2 w-full">
            <div class="overflow-hidden">
                <!-- CORREGIDO: title="..." ahora es un atributo HTML válido -->
                <p class="text-sm font-semibold text-gray-800 truncate" title="{{ $node['name'] }}">{{ $node['name'] }}</p>
                <p class="text-xs text-gray-500 truncate" title="{{ $node['email'] }}">{{ $node['email'] }}</p>
            </div>
            @if (($node['superior_count'] ?? 0) > 1)
                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                    title="Reporta a múltiples superiores">
                    {{ $node['superior_count'] }} jefes
                </span>
            @endif
        </div>

        <div class="mt-2 flex flex-wrap gap-1">
            @if (!empty($node['role']))
                <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">{{ $node['role'] }}</span>
            @endif
            @if (!empty($node['job_position']))
                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">{{ $node['job_position'] }}</span>
            @endif
            @if (!empty($node['physical_area']))
                <span class="rounded-md bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-violet-700">{{ $node['physical_area'] }}</span>
            @endif
        </div>

        @if (($node['subordinate_count'] ?? 0) > 0)
            <p class="mt-2 text-[10px] font-medium text-gray-400">
                {{ $node['subordinate_count'] }} subordinado(s) directo(s)
            </p>
        @endif
    </button>

    <!-- Conectores y subnodos -->
    @if (!empty($node['children']) && count($node['children']) > 0)
        <!-- Línea vertical descendente del padre -->
        <div class="h-6 w-px bg-gray-300"></div>

        <!-- Contenedor horizontal de hijos -->
        <div class="flex items-start justify-center isolate">
            @foreach ($node['children'] as $index => $child)
                <div class="relative flex flex-col items-center px-4">
                    
                    <!-- CORREGIDO: Clases condicionales de Tailwind limpias sin colisión de left/right -->
                    <div class="absolute top-0 h-px border-t border-gray-300
                        @if(count($node['children']) === 1) left-1/2 right-1/2
                        @elseif($index === 0) left-1/2 right-0
                        @elseif($index === count($node['children']) - 1) left-0 right-1/2
                        @else left-0 right-0 @endif">
                    </div>
                    
                    <!-- Línea vertical que entra al hijo -->
                    <div class="h-6 w-px bg-gray-300 z-10"></div>

                    <!-- Llamada recursiva -->
                    <x-administracion.organigrama-node :node="$child" :depth="$depth + 1" />
                </div>
            @endforeach
        </div>
    @endif
</div>