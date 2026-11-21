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

    // Conexión en corto para el pie de la tarjeta («Hace 3 horas»); el texto completo va en el título.
    $online = (bool) ($node['is_online'] ?? false);
    $presence = (string) ($node['presence_label'] ?? 'Sin actividad registrada');
    $presenceShort = match (true) {
        $online => 'En línea',
        str_starts_with($presence, 'Última conexión hace ') => 'Hace '.\Illuminate\Support\Str::after($presence, 'Última conexión hace '),
        str_starts_with($presence, 'Sin conectarse') => 'Sin conectarse',
        default => $presence,
    };
    $details = implode(' · ', array_filter([$node['physical_area'] ?? null, $node['role'] ?? null]));
    $warnings = array_filter([
        ! empty($node['out_of_area']) ? 'Jefatura de otra área' : null,
        ($node['superior_count'] ?? 0) > 1 ? $node['superior_count'].' jefes directos' : null,
    ]);
@endphp

<div class="flex flex-col items-center visual-node-wrapper" style="--depth: {{ min((int) $depth, 6) }}" wire:key="org-node-box-{{ $node['instance'] ?? $node['id'] }}">
    {{-- Tarjeta con el diseño de Inicio: foto de 50px con el nombre a un lado, puesto y área en gris y un pie con la conexión. --}}
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
            'org-node js-org-card focus:outline-none focus:ring-0',
            'org-node-outside' => ! empty($node['out_of_area']),
        ])>
        <span class="org-node-head">
            <span class="org-node-avatar">
                @if (! empty($node['photo']))
                    <img src="{{ $node['photo'] }}" alt="" loading="lazy">
                @else
                    <span class="org-node-initials">{{ $initials }}</span>
                @endif
                @if ($online)
                    <span class="org-node-online" aria-hidden="true"></span>
                @endif
            </span>
            <span class="org-node-names" title="{{ $node['name'] }}">
                <span class="org-node-name">{{ $firstName }}</span>
                @if ($lastName !== '')
                    <span class="org-node-name">{{ $lastName }}</span>
                @endif
            </span>
        </span>

        <span class="org-node-body">
            <span class="org-node-line" title="Puesto: {{ $node['job_position'] ?: 'sin puesto' }}">{{ $node['job_position'] ?: 'Sin puesto' }}</span>
            <span class="org-node-line" title="{{ $details }}">{{ $details !== '' ? $details : 'Sin área' }}</span>
            @foreach ($warnings as $warning)
                <span class="org-node-line is-warn">{{ $warning }}</span>
            @endforeach
        </span>

        <span class="org-node-foot">
            <span @class(['org-node-presence', 'is-online' => $online]) title="{{ $presence }}">{{ $presenceShort }}</span>
        </span>
    </button>

    {{-- Acciones en el pie: mover (con todo su equipo, arrastrando) y eliminar (abre la confirmación, no borra directamente). --}}
    <span class="org-node-actions">
        <span class="org-drag-handle" title="Arrastra para mover a {{ $node['name'] }} con su equipo" aria-hidden="true">
            <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.6" /><circle cx="15" cy="6" r="1.6" /><circle cx="9" cy="12" r="1.6" /><circle cx="15" cy="12" r="1.6" /><circle cx="9" cy="18" r="1.6" /><circle cx="15" cy="18" r="1.6" /></svg>
        </span>
        <button type="button"
            wire:click="requestDeleteUser({{ $node['id'] }})"
            class="org-node-delete focus:outline-none focus:ring-0"
            title="Eliminar a {{ $node['name'] }}" aria-label="Eliminar a {{ $node['name'] }}">
            <svg class="h-[15px] w-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </span>
    </div>

    @if (!empty($node['children']) && count($node['children']) > 0)
        {{-- Las líneas las dibuja el script en una capa aparte (siguen a las tarjetas cuando se deslizan); estos contenedores
             dan el espacio entre niveles y, si el script no corre, dibujan las líneas con CSS. --}}
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
