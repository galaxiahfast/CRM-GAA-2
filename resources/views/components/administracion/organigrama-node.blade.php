@props(['node', 'depth' => 0])

@php
    $nameParts = explode(' ', trim($node['name'] ?? ''));
    $initials = '';
    if (count($nameParts) >= 2) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
    } elseif (count($nameParts) === 1) {
        $initials = strtoupper(substr($nameParts[0], 0, 2));
    } else {
        $initials = '?';
    }
@endphp

<div class="flex flex-col items-center visual-node-wrapper" wire:key="org-node-box-{{ $node['id'] }}">
    <button type="button" 
        wire:click="selectUser({{ $node['id'] }})"
        data-id="{{ $node['id'] }}"
        class="org-node js-org-card inline-flex w-[280px] flex-shrink-0 flex-col items-center rounded-xl border-2 border-gray-300 bg-transparent p-[20px] text-center shadow-sm transition hover:border-[#1e3a8a] hover:shadow-md focus:border-[#1e3a8a] focus:outline-none focus:ring-0 z-10"
        style="gap: 15px;">
        
        <div class="flex items-center justify-center rounded-full bg-[#1A3A6B] text-white font-semibold text-xs" 
             style="width: 36px; height: 36px; flex-shrink: 0;">
            {{ $initials }}
        </div>

        <div class="w-full relative h-[10px]">
            <p class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full text-sm font-semibold text-gray-800 text-center leading-none truncate" title="{{ $node['name'] }}">
                {{ $node['name'] }}
            </p>
        </div>

        <div class="w-full relative h-[10px]">
            <p class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full text-xs text-gray-500 text-center leading-none truncate" title="{{ $node['email'] }}">
                {{ $node['email'] }}
            </p>
        </div>

        <div class="w-full flex flex-wrap items-center justify-center" style="gap: 10px;">
            @if (!empty($node['role']))
                <span class="inline-flex items-center justify-center gap-[10px] rounded-md bg-[#1e3a8a] text-[10px] font-medium text-white py-[10px] px-[20px] box-border min-w-0" 
                      style="width: 110px; flex-shrink: 0; line-height: 1;" title="{{ $node['role'] }}">
                    <svg class="flex-shrink-0" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span class="truncate" style="line-height: 1;">{{ $node['role'] }}</span>
                </span>
            @endif
            @if (!empty($node['job_position']))
                <span class="inline-flex items-center justify-center gap-[10px] rounded-md bg-[#059669] text-[10px] font-medium text-white py-[10px] px-[20px] box-border min-w-0" 
                      style="width: 110px; flex-shrink: 0; line-height: 1;" title="{{ $node['job_position'] }}">
                    <svg class="flex-shrink-0" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <span class="truncate" style="line-height: 1;">{{ $node['job_position'] }}</span>
                </span>
            @endif
            @if (!empty($node['physical_area']))
                <span class="inline-flex items-center justify-center gap-[10px] rounded-md bg-[#7c3aed] text-[10px] font-medium text-white py-[10px] px-[20px] box-border min-w-0" 
                      style="width: 110px; flex-shrink: 0; line-height: 1;" title="{{ $node['physical_area'] }}">
                    <svg class="flex-shrink-0" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="4" width="16" height="16" rx="2" ry="2"/>
                        <path d="M9 4v16M15 4v16M4 9h16M4 15h16"/>
                    </svg>
                    <span class="truncate" style="line-height: 1;">{{ $node['physical_area'] }}</span>
                </span>
            @endif
            @if (($node['superior_count'] ?? 0) > 1)
                <span class="inline-flex items-center justify-center gap-[10px] rounded-md bg-[#d97706] text-[10px] font-medium text-white py-[10px] px-[20px] box-border min-w-0" 
                      style="width: 110px; flex-shrink: 0; line-height: 1;" title="Reporta a múltiples superiores">
                    <svg class="flex-shrink-0" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="truncate" style="line-height: 1;">{{ $node['superior_count'] }} jefes</span>
                </span>
            @endif
        </div>

        <div class="w-full relative h-[10px]">
            @if (($node['subordinate_count'] ?? 0) > 0)
                <p class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full text-[10px] font-medium text-gray-400 italic text-center leading-none truncate">
                    {{ $node['subordinate_count'] }} subordinado(s) directo(s)
                </p>
            @else
                <p class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full text-[10px] font-medium text-gray-400 italic text-center leading-none truncate">
                    Sin subordinados
                </p>
            @endif
        </div>
    </button>

    @if (!empty($node['children']) && count($node['children']) > 0)
        <div class="h-6 border-l-2 border-gray-300"></div>
        <div class="flex items-start justify-center isolate">
            @foreach ($node['children'] as $index => $child)
                <div class="relative flex flex-col items-center px-4 w-full">
                    <div @class([
                        'absolute top-0 h-8 border-gray-300',
                        'border-l-2' => count($node['children']) === 1,
                        'left-1/2 right-0 border-t-2 border-l-2 rounded-tl-xl' => count($node['children']) > 1 && $index === 0,
                        'left-0 right-1/2 border-t-2 border-r-2 rounded-tr-xl' => count($node['children']) > 1 && $index === count($node['children']) - 1,
                        'left-0 right-0 border-t-2' => count($node['children']) > 1 && $index > 0 && $index < count($node['children']) - 1,
                    ])></div>
                    @if(count($node['children']) > 1 && $index > 0 && $index < count($node['children']) - 1)
                        <div class="absolute top-0 left-1/2 -translate-x-[1px] h-8 border-l-2 border-gray-300"></div>
                    @endif
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
