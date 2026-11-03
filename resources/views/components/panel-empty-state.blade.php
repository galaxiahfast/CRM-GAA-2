@props([
    'subtitle' => null,
])

{{-- Estado vacío de los paneles (mismo patrón que "Ningún perfil seleccionado" en Reportes de soporte).
     Usa las variables de panel-styles, así que se ve bien en tema claro y oscuro. --}}
<div {{ $attributes->class(['grid min-h-[260px] place-items-center px-[20px] py-[40px] text-center']) }}>
    <div class="max-w-[380px]">
        <span class="panel-surface panel-border panel-text mx-auto grid h-14 w-14 place-items-center rounded-xl border">
            {{ $icon }}
        </span>

        <p class="panel-accent mt-[20px] leading-snug">{{ $slot }}</p>

        @if ($subtitle)
            <p class="panel-text mt-[10px] leading-[25px]">{{ $subtitle }}</p>
        @endif

        @isset($action)
            <div class="mt-[20px]">
                {{ $action }}
            </div>
        @endisset
    </div>
</div>
