@props(['node', 'depth' => 0])

@php
    // Nombre y apellido van en dos líneas; si el nodo viene de una caché vieja sin esos datos se separa el nombre completo.
    $fullName = trim((string) ($node['name'] ?? ''));
    $firstName = trim((string) ($node['first_name'] ?? \Illuminate\Support\Str::before($fullName, ' ')));
    $lastName = trim((string) ($node['last_name'] ?? \Illuminate\Support\Str::after($fullName, ' ')));
    $nameParts = explode(' ', $fullName);
    $initials = '';
    if (count($nameParts) >= 2) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
    } elseif (count($nameParts) === 1) {
        $initials = strtoupper(substr($nameParts[0], 0, 2));
    } else {
        $initials = '?';
    }
@endphp

<div class="flex flex-col items-center visual-node-wrapper" wire:key="org-node-box-{{ $node['instance'] ?? $node['id'] }}">
    <div class="relative z-10">
    <button type="button"
        wire:click="selectUser({{ $node['id'] }})"
        data-id="{{ $node['id'] }}"
        data-name="{{ $node['name'] }}"
        data-email="{{ $node['email'] }}"
        data-role="{{ $node['role'] ?? '' }}"
        data-position="{{ $node['job_position'] ?? '' }}"
        data-area="{{ $node['physical_area'] ?? '' }}"
        data-photo="{{ $node['photo'] ?? '' }}"
        data-initials="{{ $initials }}"
        @class([
            'org-node js-org-card flex w-[280px] flex-shrink-0 flex-col items-stretch rounded-xl panel-border panel-surface border-2 p-[20px] text-left home-row focus:outline-none focus:ring-0 z-10',
            'org-node-outside' => ! empty($node['out_of_area']),
        ])>

        <span class="flex flex-col items-center">
            @if (! empty($node['photo']))
                <img src="{{ $node['photo'] }}" alt="" class="h-[60px] w-[60px] shrink-0 rounded-full object-cover" loading="lazy">
            @else
                <span class="flex h-[60px] w-[60px] shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-xs font-semibold text-white">{{ $initials }}</span>
            @endif
            <span class="home-link mt-[15px] block h-[15px] w-full truncate text-center leading-none" title="{{ $node['name'] }}">{{ $firstName }}</span>
            @if ($lastName !== '')
                <span class="home-link mt-[15px] block h-[15px] w-full truncate text-center leading-none" title="{{ $node['name'] }}">{{ $lastName }}</span>
            @endif
            <span class="mt-[15px] block h-[15px] w-full truncate text-center leading-none">{{ \Illuminate\Support\Str::before((string) $node['email'], '@') }}@</span>
        </span>

        <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none" title="{{ $node['presence_label'] ?? 'Sin actividad registrada' }}">
            <span @class([
                'h-[10px] w-[10px] shrink-0 rounded-full',
                'bg-emerald-500' => $node['is_online'] ?? false,
                'bg-zinc-500' => ! ($node['is_online'] ?? false),
            ])></span>
            <span @class(['truncate', 'home-link' => $node['is_online'] ?? false])>{{ $node['presence_label'] ?? 'Sin actividad registrada' }}</span>
        </span>

        @if (! empty($node['out_of_area']))
            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none" title="Jefatura fuera del área elegida: se muestra para ubicar la rama">
                <svg class="h-[15px] w-[15px] shrink-0" style="color: #64748b;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                <span class="truncate">Fuera del área</span>
            </span>
        @endif
        @if (! empty($node['role']))
            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none" title="Rol: {{ $node['role'] }}"><svg class="h-[15px] w-[15px] shrink-0" style="color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0" /></svg><span class="truncate">{{ $node['role'] }}</span></span>
        @endif
        @if (! empty($node['job_position']))
            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none" title="Puesto: {{ $node['job_position'] }}"><svg class="h-[15px] w-[15px] shrink-0" style="color: #10b981;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.25 14.15v4.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18.4v-4.25m16.5 0a2.25 2.25 0 0 0 .75-1.7V8.7A2.25 2.25 0 0 0 18.75 6.45H15M20.25 14.15 12 16.5l-8.25-2.35m0 0A2.25 2.25 0 0 1 3 12.45V8.7a2.25 2.25 0 0 1 2.25-2.25H9m6 0V5.25A2.25 2.25 0 0 0 12.75 3h-1.5A2.25 2.25 0 0 0 9 5.25v1.2m6 0H9" /></svg><span class="truncate">{{ $node['job_position'] }}</span></span>
        @endif
        @if (! empty($node['physical_area']))
            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none" title="Área: {{ $node['physical_area'] }}"><svg class="h-[15px] w-[15px] shrink-0" style="color: #8b5cf6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg><span class="truncate">{{ $node['physical_area'] }}</span></span>
        @endif
        @if (($node['superior_count'] ?? 0) > 1)
            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none" title="Reporta a múltiples superiores"><svg class="h-[15px] w-[15px] shrink-0" style="color: #f59e0b;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg><span class="truncate">{{ $node['superior_count'] }} jefes</span></span>
        @endif

        <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none"><svg class="h-[15px] w-[15px] shrink-0" style="color: currentColor;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg><span class="truncate">{{ ($node['subordinate_count'] ?? 0) > 0 ? $node['subordinate_count'].' subordinado(s) directo(s)' : 'Sin subordinados' }}</span></span>
    </button>

    {{-- Eliminar al usuario: abre la confirmación (hay que escribir su nombre completo), no borra directamente. --}}
    <button type="button"
        wire:click="requestDeleteUser({{ $node['id'] }})"
        class="org-node-delete absolute right-[8px] top-[8px] z-20 inline-flex h-[24px] w-[24px] items-center justify-center panel-icon-button rounded-lg focus:outline-none focus:ring-0"
        title="Eliminar a {{ $node['name'] }}" aria-label="Eliminar a {{ $node['name'] }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
    </div>

    @if (!empty($node['children']) && count($node['children']) > 0)
        {{-- Conectores: un tronco desde la persona, una barra horizontal y una bajada por cada subordinado (ver estilos .org-*). --}}
        <div class="org-stem" aria-hidden="true"></div>
        <div class="org-children">
            @foreach ($node['children'] as $child)
                <div class="org-child">
                    <x-administracion.organigrama-node :node="$child" :depth="$depth + 1" />
                </div>
            @endforeach
        </div>
    @endif
</div>

