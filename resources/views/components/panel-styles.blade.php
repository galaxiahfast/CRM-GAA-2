{{-- Estilos compartidos de los paneles con el diseño de Administración y Reportes de soporte.
     Todo cuelga de [data-panel]: el tema claro y el oscuro solo cambian las variables (misma paleta oscura que
     Reportes de soporte), así que ningún componente necesita reglas propias para el tema oscuro. --}}
<style>
    [data-panel] {
        --panel-page: #ffffff;
        --panel-surface: #ffffff;
        --panel-header: #f4f4f5;
        --panel-subtle: #fafafa;
        --panel-hover: #f4f4f5;
        --panel-border: #d4d4d8;
        --panel-text: #000000;
        --panel-muted: #71717a;
        --panel-accent: #1A3A6B;
        --panel-button: #000000;
        --panel-button-text: #ffffff;
        --panel-button-hover: #27272a;
        --panel-track: #e4e4e7;
        --panel-danger: #b91c1c;
        --panel-danger-soft: #fef2f2;
        --panel-danger-line: #fecaca;
        background-color: var(--panel-page);
        color: var(--panel-text);
        color-scheme: light;
    }

    html.module-dark-theme [data-panel] {
        --panel-page: #09090b;
        --panel-surface: #09090b;
        --panel-header: #0f0f12;
        --panel-subtle: #0c0c0f;
        --panel-hover: #131316;
        --panel-border: #27272a;
        --panel-text: #ffffff;
        --panel-muted: #ffffff;
        --panel-accent: #6aa9ff;
        --panel-button: #fafafa;
        --panel-button-text: #09090b;
        --panel-button-hover: #e4e4e7;
        --panel-track: #27272a;
        --panel-danger: #f87171;
        --panel-danger-soft: rgba(248, 113, 113, 0.08);
        --panel-danger-line: rgba(248, 113, 113, 0.4);
        color-scheme: dark;
    }

    /* Un solo tamaño y peso de letra en toda la página; solo el título principal (20px) es más grande. */
    [data-panel] :is(h2, h3, p, span, label, button, a, input, select, textarea, strong, li) {
        font-size: 15px;
        font-weight: 400;
    }

    [data-panel] :is(button, a):focus,
    [data-panel] :is(button, a):focus-visible {
        outline: none;
        box-shadow: none;
    }

    [data-panel]:fullscreen {
        height: 100dvh;
        overflow-y: auto;
    }

    /* Superficies, líneas y textos */
    .panel-surface { background-color: var(--panel-surface); }
    .panel-header { background-color: var(--panel-header); }
    .panel-subtle { background-color: var(--panel-subtle); }
    .panel-line { background-color: var(--panel-border); }
    .panel-border { border-color: var(--panel-border); }
    .panel-text { color: var(--panel-text); }
    .panel-muted { color: var(--panel-muted); }
    .panel-accent { color: var(--panel-accent); }
    .panel-danger-box {
        border-color: var(--panel-danger-line);
        background-color: var(--panel-danger-soft);
        color: var(--panel-danger);
    }

    /* Pestañas: solo icono y nombre, sin líneas (igual que el menú de Administración).
       La activa se marca con aria-selected y toma el color de acento. */
    .panel-tab {
        color: var(--panel-text);
        transition: color 150ms ease;
    }
    .panel-tab:hover,
    .panel-tab[aria-selected="true"] { color: var(--panel-accent); }

    /* Tarjetas que son enlaces: el borde no cambia al pasar el cursor ni al seleccionarlas */
    .panel-card:is(:hover, :focus, :focus-visible, :active) {
        border-color: var(--panel-border);
        outline: none;
        box-shadow: none;
    }

    /* Botones */
    .panel-button {
        background-color: var(--panel-button);
        color: var(--panel-button-text);
        transition: background-color 150ms ease;
    }
    .panel-button:hover { background-color: var(--panel-button-hover); }
    .panel-button-secondary {
        border: 1px solid var(--panel-border);
        background-color: var(--panel-surface);
        color: var(--panel-text);
        transition: background-color 150ms ease;
    }
    .panel-button-secondary:hover { background-color: var(--panel-hover); }
    .panel-icon-button {
        color: var(--panel-muted);
        transition: color 150ms ease, background-color 150ms ease;
    }
    .panel-icon-button:hover {
        background-color: var(--panel-hover);
        color: var(--panel-text);
    }
    .panel-menu-item { color: var(--panel-text); }
    .panel-menu-item:hover { background-color: var(--panel-hover); }

    /* Campos: el borde no cambia al enfocarlos (igual que Reportes de soporte y Administración) */
    .panel-field {
        border-color: var(--panel-border);
        background-color: var(--panel-surface);
        color: var(--panel-text);
    }
    .panel-field::placeholder { color: var(--panel-muted); opacity: 1; }
    [data-panel] :is(input, select, textarea).panel-field:focus,
    [data-panel] :is(input, select, textarea).panel-field:focus-visible {
        border-color: var(--panel-border) !important;
        box-shadow: none !important;
        outline: none !important;
    }

    /* Listas desplegables (mes, año y servicios): mismo diseño que las de Reportes de soporte */
    .panel-option {
        background-color: var(--panel-surface);
        color: var(--panel-text);
    }
    .panel-option:hover, .panel-option.is-active { background-color: var(--panel-subtle); }
    html.module-dark-theme [data-panel] .panel-option:hover { background-color: var(--panel-hover); }
    .panel-option[aria-selected="true"] { color: var(--panel-accent); }
    .panel-scroll {
        scrollbar-width: none;
        overscroll-behavior: contain;
    }
    .panel-scroll::-webkit-scrollbar {
        width: 0;
        height: 0;
    }
    .panel-scroll-thumb {
        position: absolute;
        right: 0;
        top: 0;
        z-index: 20;
        width: 3px;
        min-height: 32px;
        border-radius: 9999px;
        background: var(--panel-accent);
        pointer-events: none;
    }
    /* Con data-scroll-on-hover el indicador solo se ve con el puntero encima. Va también en CSS (además del JS) para que se vea igual
       aunque el JS compilado del servidor esté desactualizado. */
    [data-scroll-on-hover] .panel-scroll-thumb { opacity: 0; transition: opacity 120ms ease; }
    [data-scroll-on-hover]:hover .panel-scroll-thumb { opacity: 1; }

    /* Anillos de avance (componente compartido): pista, texto y colores según el tema */
    /* Mismo tamaño de aro en todo el panel (el componente trae 75px fijos y es compartido con otras pantallas) */
    [data-panel] .radial-progress svg { width: 56px; height: 56px; flex-shrink: 0; }
    [data-panel] .radial-progress circle:first-child { stroke: var(--panel-track); }
    [data-panel] .radial-progress .percentage-text {
        color: var(--panel-text);
        font-size: 15px;
        font-weight: 400;
    }
    [data-panel] .radial-progress circle[stroke="red"] { stroke: #dc2626; }
    [data-panel] .radial-progress circle[stroke="orange"] { stroke: #f97316; }
    [data-panel] .radial-progress circle[stroke="yellow"] { stroke: #eab308; }
    [data-panel] .radial-progress circle[stroke="#32CD32"] { stroke: #22c55e; }
    [data-panel] .radial-progress circle[stroke="green"] { stroke: #15803d; }
    html.module-dark-theme [data-panel] .radial-progress circle[stroke="red"] { stroke: #f87171; }
    html.module-dark-theme [data-panel] .radial-progress circle[stroke="orange"] { stroke: #fb923c; }
    html.module-dark-theme [data-panel] .radial-progress circle[stroke="yellow"] { stroke: #facc15; }
    html.module-dark-theme [data-panel] .radial-progress circle[stroke="#32CD32"] { stroke: #4ade80; }
    html.module-dark-theme [data-panel] .radial-progress circle[stroke="green"] { stroke: #22c55e; }

    /* Tonos de estado (anillos, textos e iconos): rojo, ámbar, verde y gris */
    .panel-tone-red { color: #dc2626; }
    .panel-tone-amber { color: #d97706; }
    .panel-tone-green { color: #15803d; }
    .panel-tone-gray { color: var(--panel-muted); }
    html.module-dark-theme [data-panel] .panel-tone-red { color: #f87171; }
    html.module-dark-theme [data-panel] .panel-tone-amber { color: #fbbf24; }
    html.module-dark-theme [data-panel] .panel-tone-green { color: #4ade80; }
    .panel-ring-track { stroke: var(--panel-track); }

    /* Avatares de contadores (activo en verde, inactivo en azul) */
    .panel-avatar-green { background-color: #16a34a; color: #ffffff; }
    .panel-avatar-blue { background-color: #2563eb; color: #ffffff; }

    /* Acciones de texto (agregar o eliminar): solo icono y nombre, sin fondo ni borde */
    .panel-action {
        color: var(--panel-accent);
        transition: opacity 150ms ease;
    }
    .panel-action-danger {
        color: var(--panel-danger);
        transition: opacity 150ms ease;
    }
    .panel-action:hover,
    .panel-action-danger:hover { opacity: 0.7; }
    .panel-menu-danger { color: var(--panel-danger); }

    /* Zonas para subir archivos */
    .panel-upload { transition: background-color 150ms ease; }
    .panel-upload:hover { background-color: var(--panel-subtle); }

    /* Indicador de carga */
    .panel-spinner {
        border-color: transparent;
        border-top-color: var(--panel-accent);
    }
</style>
