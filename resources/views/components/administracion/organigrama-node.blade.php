@props(['node', 'depth' => 0])

<div class="flex flex-col items-center visual-node-wrapper" wire:key="org-node-box-{{ $node['id'] }}">
    <!-- CARD PRINCIPAL: Grosor de 2px, gris uniforme y hover/focus con el azul de la etiqueta (#1e3a8a) -->
    <button type="button" 
        wire:click="selectUser({{ $node['id'] }})"
        data-id="{{ $node['id'] }}"
        class="org-node js-org-card inline-flex w-[260px] min-h-[150px] flex-shrink-0 flex-col items-center justify-center rounded-xl border-2 border-gray-300 bg-transparent p-4 text-center shadow-sm transition hover:border-[#1e3a8a] hover:shadow-md focus:border-[#1e3a8a] focus:outline-none focus:ring-0 z-10 gap-[10px]">
        
        <!-- Contenido de la tarjeta -->
        <div class="w-full flex flex-col h-auto gap-[5px]">
            <div class="w-full flex flex-col h-auto">
                <p class="text-sm font-semibold text-gray-800 truncate w-full leading-none" title="{{ $node['name'] }}">
                    {{ $node['name'] }}
                </p>
            </div>
            <div class="w-full flex flex-col h-auto">
                <p class="text-xs text-gray-500 truncate w-full leading-tight" title="{{ $node['email'] }}">
                    {{ $node['email'] }}
                </p>
            </div>
        </div>

        <div class="w-full flex flex-wrap items-center justify-center gap-[10px] overflow-hidden">
            @if (!empty($node['role']))
                <span class="inline-block w-[105px] rounded-md bg-[#1e3a8a] px-2 py-1 text-[10px] font-medium text-white truncate" title="{{ $node['role'] }}">
                    {{ $node['role'] }}
                </span>
            @endif
            @if (!empty($node['job_position']))
                <span class="inline-block w-[105px] rounded-md bg-[#059669] px-2 py-1 text-[10px] font-medium text-white truncate" title="{{ $node['job_position'] }}">
                    {{ $node['job_position'] }}
                </span>
            @endif
            @if (!empty($node['physical_area']))
                <span class="inline-block w-[105px] rounded-md bg-[#7c3aed] px-2 py-1 text-[10px] font-medium text-white truncate" title="{{ $node['physical_area'] }}">
                    {{ $node['physical_area'] }}
                </span>
            @endif
            @if (($node['superior_count'] ?? 0) > 1)
                <span class="inline-block w-[105px] rounded-md bg-[#d97706] px-2 py-1 text-[10px] font-medium text-white truncate" title="Reporta a múltiples superiores">
                    {{ $node['superior_count'] }} jefes
                </span>
            @endif
        </div>

        <div class="w-full shrink-0 flex flex-col h-auto">
            @if (($node['subordinate_count'] ?? 0) > 0)
                <p class="text-[10px] font-medium text-gray-400 italic w-full leading-none">
                    {{ $node['subordinate_count'] }} subordinado(s) directo(s)
                </p>
            @else
                <p class="text-[10px] font-medium text-gray-400 italic w-full leading-none">
                    Sin subordinados
                </p>
            @endif
        </div>
    </button>

    <!-- Conectores y subnodos -->
    @if (!empty($node['children']) && count($node['children']) > 0)
        <!-- Línea vertical descendente principal -->
        <div class="h-6 border-l-2 border-gray-300"></div>
        
        <div class="flex items-start justify-center isolate">
            @foreach ($node['children'] as $index => $child)
                <div class="relative flex flex-col items-center px-4 w-full">
                    
                    <!-- Contenedor del Conector -->
                    <div @class([
                        'absolute top-0 h-8 border-gray-300',
                        'border-l-2' => count($node['children']) === 1,
                        'left-1/2 right-0 border-t-2 border-l-2 rounded-tl-xl' => count($node['children']) > 1 && $index === 0,
                        'left-0 right-1/2 border-t-2 border-r-2 rounded-tr-xl' => count($node['children']) > 1 && $index === count($node['children']) - 1,
                        'left-0 right-0 border-t-2' => count($node['children']) > 1 && $index > 0 && $index < count($node['children']) - 1,
                    ])></div>
                    
                    <!-- Línea vertical descendente para hijos intermedios -->
                    @if(count($node['children']) > 1 && $index > 0 && $index < count($node['children']) - 1)
                        <div class="absolute top-0 left-1/2 -translate-x-[1px] h-8 border-l-2 border-gray-300"></div>
                    @endif

                    <!-- Espaciador físico controlado -->
                    <div class="h-8 w-full"></div>
                    
                    <x-administracion.organigrama-node :node="$child" :depth="$depth + 1" />
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:navigated', () => {
        equalizeNodeHeights();
    });

    document.addEventListener('livewire:update', () => {
        equalizeNodeHeights();
    });

    function equalizeNodeHeights() {
        const cards = document.querySelectorAll('.js-org-card');
        if (cards.length === 0) return;

        cards.forEach(card => {
            card.style.height = 'auto';
        });

        let maxHeight = 0;
        cards.forEach(card => {
            const cardHeight = card.offsetHeight;
            if (cardHeight > maxHeight) {
                maxHeight = cardHeight;
            }
        });

        cards.forEach(card => {
            card.style.height = `${maxHeight}px`;
        });
    }

    window.addEventListener('DOMContentLoaded', equalizeNodeHeights);
</script>