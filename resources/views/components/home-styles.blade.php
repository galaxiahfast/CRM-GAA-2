{{-- Tema común de las pantallas con el diseño del inicio: en oscuro las superficies se cierran al negro del encabezado y solo hay
     dos colores de texto (blanco, o negro en claro, y azul); los enlaces y botones van en verde. --}}
<style>
    [data-home] { --home-green: #15803d; color: var(--panel-text); }
    html.module-dark-theme [data-home] {
        --home-green: #4ade80;
        --panel-page: #09090b;
        --panel-surface: #09090b;
        --panel-header: #0f0f12;
        --panel-subtle: #0c0c0f;
        --panel-hover: #131316;
        --panel-border: #27272a;
        --panel-text: #ffffff;
        background-color: var(--panel-page);
    }
    [data-home] .home-link { color: var(--home-green); }
    [data-home] .home-row,
    [data-home] .home-card { transition: background-color 150ms ease; }
    [data-home] .home-row:hover,
    [data-home] .home-card:hover { background-color: var(--panel-subtle); }
    html.module-dark-theme [data-home] .home-row:hover,
    html.module-dark-theme [data-home] .home-card:hover { background-color: var(--panel-hover, var(--panel-subtle)); }
    @media (prefers-reduced-motion: reduce) { [data-home] .home-row, [data-home] .home-card { transition: none; } }
</style>
