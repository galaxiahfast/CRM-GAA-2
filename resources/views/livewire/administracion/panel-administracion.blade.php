@php
    $role = auth()->user()->role->role;
    $roleId = (int) auth()->user()->role_id;
    $permissionAccess = app(\App\Services\Authorization\PermissionAccessService::class);
    $canManageOrganization = $permissionAccess->allows(auth()->user(), 'administration.organization.manage');
    $canManageUsers = $permissionAccess->allows(auth()->user(), 'administration.users.manage');
    $canManageRoles = $permissionAccess->allows(auth()->user(), 'administration.roles.manage');
    $canManagePermissions = $permissionAccess->allows(auth()->user(), 'administration.permissions.manage');
    $canManageAssignments = $permissionAccess->allows(auth()->user(), 'administration.assignments.manage');

    $missingLabels = [
        'superior' => 'Sin jefe',
        'job_position' => 'Sin puesto',
        'physical_area' => 'Sin área',
    ];

    // Asegurar que $orgChartStats y $orgChartTree nunca sean null
    $orgChartStats = $orgChartStats ?? ['in_tree' => 0, 'relations' => 0, 'total_users' => 0, 'cycles_detected' => 0];
    $orgChartTree = $orgChartTree ?? [];
@endphp

<div
    x-data="{
        administrationView: 'directory',
        viewScale: 100,
        scaledContentHeight: null,
        scaleObserver: null,
        init() {
            this.$nextTick(() => window.dispatchEvent(new CustomEvent('organization-chart-shown')));
            this.$nextTick(() => {
                this.scaleObserver = new ResizeObserver(() => this.syncScaledContentHeight());
                this.scaleObserver.observe(this.$refs.scaledMain);
                this.syncScaledContentHeight();
            });
        },
        destroy() {
            this.scaleObserver?.disconnect();
        },
        syncScaledContentHeight() {
            this.$nextTick(() => {
                if (this.$refs.scaledMain) {
                    this.scaledContentHeight = Math.ceil(this.$refs.scaledMain.offsetHeight * (this.viewScale / 100));
                }
            });
        },
        zoomOut() {
            this.viewScale = Math.max(70, this.viewScale - 5);
            this.syncScaledContentHeight();
        },
        zoomIn() {
            this.viewScale = Math.min(100, this.viewScale + 5);
            this.syncScaledContentHeight();
        },
        selectAdministrationView(view) {
            this.administrationView = view;
            if (view === 'organization') {
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('organization-chart-shown')));
            }
        },
    }"
    data-panel
    data-home
    class="administration-monochrome relative min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 overflow-hidden bg-white text-[15px] text-zinc-700"
>
    {{-- Los estilos van a la cabecera de la página (una sola vez): dentro del componente se enviaban completos en cada actualización de Livewire. --}}
    @push('styles')
        <x-panel-styles />
        <x-home-styles />
        <x-administration-modal-styles />
    @endpush

    {{-- Aviso flotante (Usuario actualizado., Usuario eliminado., etc.): llega con el evento «notify» y se oculta solo. --}}
    <div
        x-data="{ show: false, text: '', kind: 'success', timer: null }"
        @notify.window="text = $event.detail.message; kind = $event.detail.type || 'success'; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, kind === 'error' ? 7000 : 3500)"
        x-show="show"
        x-cloak
        role="status"
        aria-live="polite"
        class="app-zoom-overlay panel-surface panel-border fixed left-1/2 top-[143px] z-[200002] flex max-w-[calc(100vw-40px)] -translate-x-1/2 items-center gap-[10px] rounded-xl border px-[20px] py-[15px] leading-none shadow-[0_8px_24px_rgba(0,0,0,0.12)]"
        style="display: none;"
    >
        <svg class="h-4 w-4 shrink-0" :class="kind === 'success' ? 'text-[#1A3A6B]' : 'text-[color:var(--panel-danger)]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 12.5l2.7 2.7L16 9.5" /><circle cx="12" cy="12" r="9.5" /></svg>
        <span x-text="text"></span>
    </div>

    @push('styles')
    <style hidden>
        .administration-monochrome,
        .administration-monochrome > section,
        .administration-monochrome > section > div {
            background-color: #ffffff !important;
        }
        .administration-monochrome {
            --administration-field-border: #e4e4e7;
            color: #3f3f46;
            font-size: 15px;
        }
        .administration-monochrome:has([data-administration-modal]) {
            z-index: 200000;
            overflow: visible !important;
        }
        .administration-monochrome main:has([data-administration-modal]) {
            width: 100% !important;
            margin-left: 0 !important;
            transform: none !important;
        }
        .administration-monochrome [data-administration-modal] :where(section, fieldset, article) {
            border-color: #e4e4e7 !important;
            background-color: #ffffff !important;
        }
        .administration-monochrome [data-administration-modal] :where(h1, h2, h3, label, strong, legend) {
            color: #18181b !important;
        }
        .administration-monochrome :where(p, span, button, input, select, option, label, th, td, h2, h3, small) {
            font-size: 15px !important;
        }
        .administration-monochrome h1 {
            font-size: 20px !important;
        }
        .administration-monochrome .bg-white,
        .administration-monochrome .bg-white\/55,
        .administration-monochrome .bg-white\/60,
        .administration-monochrome .bg-white\/70,
        .administration-monochrome .bg-white\/75,
        .administration-monochrome .bg-white\/80 {
            background-color: #ffffff !important;
        }
        .administration-monochrome button,
        .administration-monochrome input,
        .administration-monochrome select,
        .administration-monochrome textarea {
            box-shadow: none;
        }
        .administration-monochrome button:focus,
        .administration-monochrome button:focus-visible,
        .administration-monochrome input:focus,
        .administration-monochrome input:focus-visible,
        .administration-monochrome select:focus,
        .administration-monochrome select:focus-visible,
        .administration-monochrome textarea:focus,
        .administration-monochrome textarea:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
        }
        .administration-monochrome input:focus,
        .administration-monochrome input:focus-visible,
        .administration-monochrome select:focus,
        .administration-monochrome select:focus-visible,
        .administration-monochrome textarea:focus,
        .administration-monochrome textarea:focus-visible {
            border-color: var(--administration-field-border) !important;
        }
        html body div.administration-monochrome input:focus,
        html body div.administration-monochrome input:focus-visible,
        html body div.administration-monochrome select:focus,
        html body div.administration-monochrome select:focus-visible,
        html body div.administration-monochrome textarea:focus,
        html body div.administration-monochrome textarea:focus-visible {
            border-color: var(--administration-field-border) !important;
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-color: transparent !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }
        /* Siempre se ven exactamente 4 tarjetas: el desplazamiento se detiene en el borde de cada una. */
        .unassigned-users-scrollbar { scroll-snap-type: y mandatory; scroll-padding: 15px 0; }
        .unassigned-users-scrollbar li { scroll-snap-align: start; }
        .unassigned-users-scrollbar {
            overscroll-behavior: contain;
        }
        .administration-record-scroll {
            scrollbar-width: none !important;
            overscroll-behavior: contain;
        }
        .administration-record-scroll::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
        }
        .administration-record-scroll-thumb {
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
        .administration-directory .administration-catalog-button {
            color: var(--panel-text);
            background: var(--panel-surface);
            transition: background-color 150ms ease, color 150ms ease;
        }
        .administration-directory .administration-catalog-button:hover {
            background: var(--panel-hover);
        }
        .administration-directory .administration-catalog-button[aria-current="page"] {
            color: var(--panel-accent);
            background: var(--panel-subtle);
        }
        .administration-directory .administration-delete-button:hover {
            color: var(--panel-danger);
        }
        @media (prefers-reduced-motion: reduce) {
            .administration-directory .administration-catalog-button { transition: none; }
        }
        .administration-monochrome *::-webkit-scrollbar,
        html:has(.administration-monochrome)::-webkit-scrollbar,
        body:has(.administration-monochrome)::-webkit-scrollbar {
            width: 3px;
            height: 3px;
        }
        .administration-monochrome *::-webkit-scrollbar-track,
        html:has(.administration-monochrome)::-webkit-scrollbar-track,
        body:has(.administration-monochrome)::-webkit-scrollbar-track {
            border: 0;
            background: transparent !important;
            box-shadow: none !important;
        }
        .administration-monochrome *::-webkit-scrollbar-thumb,
        html:has(.administration-monochrome)::-webkit-scrollbar-thumb,
        body:has(.administration-monochrome)::-webkit-scrollbar-thumb {
            border: 0;
            border-radius: 9999px;
            background: var(--panel-accent, #1A3A6B) !important;
        }
        .administration-monochrome *::-webkit-scrollbar-button,
        html:has(.administration-monochrome)::-webkit-scrollbar-button,
        body:has(.administration-monochrome)::-webkit-scrollbar-button {
            display: none;
            width: 0;
            height: 0;
        }
        .administration-monochrome *::-webkit-scrollbar-corner,
        html:has(.administration-monochrome)::-webkit-scrollbar-corner,
        body:has(.administration-monochrome)::-webkit-scrollbar-corner {
            background: transparent;
        }

        .administration-modal-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .administration-modal-scrollbar::-webkit-scrollbar { width: 6px; }
        .administration-modal-scrollbar::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .administration-modal-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
        /* Listas de jefe y subordinados: mismo diseño que las listas del panel (filas de 20px, sin líneas ni grises). */
        .org-user-modal .hierarchy-selection-list { max-height: 300px; overflow-y: auto; overflow-x: hidden; overscroll-behavior: contain; }
        .org-pick-box { border: 1px solid #d4d4d8; border-radius: 0.75rem; background-color: transparent; }
        html.module-dark-theme .org-pick-box { border-color: #27272a; }
        .org-pick-row { transition: background-color 150ms ease; }
        .org-pick-row:hover { background-color: #f4f4f5; }
        html.module-dark-theme .org-pick-row:hover { background-color: #131316; }
        .org-pick-row input { accent-color: #15803d; }
        html.module-dark-theme .org-pick-row input { accent-color: #4ade80; }
        .org-pick-row .org-pick-name { color: inherit; }
        .org-pick-row:has(input:checked) .org-pick-name { color: #1A3A6B; }
        html.module-dark-theme .org-pick-row:has(input:checked) .org-pick-name { color: #6aa9ff; }
        /* Campos y valores del formulario: en el modo oscuro usan el fondo de la página (no el gris), con borde fino. */
        html.module-dark-theme #main-content .org-user-modal :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]),
        html.module-dark-theme #main-content .org-user-modal [class*="bg-white"]:not(.org-user-modal) {
            background-color: #09090b !important;
            border-color: #27272a !important;
            color: #ffffff !important;
        }
        html.module-dark-theme #main-content .org-user-modal :is(input, select, textarea):disabled { background-color: #0f0f12 !important; }

        /* Conectores del organigrama: línea de 2px, centrada exactamente en cada tarjeta, con esquinas redondeadas de 12px
           en la primera y la última rama. Cada .org-child mide lo que su tarjeta más 14px a cada lado, así las barras de
           hermanos se tocan sin huecos. */
        [data-panel] { --org-line: #a8b0bd; }
        html.module-dark-theme [data-panel] { --org-line: #3f3f46; }
        /* Mismas líneas que Inicio: 1px en gris #a1a1aa (#52525b en oscuro) para bordes de tarjetas, paneles, campos y para
           las líneas que unen a cada jefe con su equipo */
        #organigrama-principal { --panel-border: #a1a1aa; --org-line: #a1a1aa; }
        html.module-dark-theme #organigrama-principal { --panel-border: #52525b; --org-line: #52525b; }
        .org-stem { width: 1px; height: 24px; background: var(--org-line); flex-shrink: 0; }
        .org-children { display: flex; align-items: flex-start; justify-content: center; }
        .org-child { position: relative; display: flex; flex-direction: column; align-items: center; padding: 24px 14px 0; }
        .org-child::before, .org-child::after { content: ''; position: absolute; top: 0; height: 24px; box-sizing: border-box; }
        .org-child::before { left: 0; right: calc(50% - 1px); border-top: 1px solid var(--org-line); }
        .org-child::after { left: calc(50% - 1px); right: 0; border-top: 1px solid var(--org-line); border-left: 1px solid var(--org-line); }
        .org-child:only-child::before { display: none; }
        .org-child:only-child::after { border-top: 0; }
        .org-child:first-child:not(:only-child)::before { display: none; }
        .org-child:first-child:not(:only-child)::after { border-top-left-radius: 12px; }
        .org-child:last-child:not(:only-child)::after { display: none; }
        .org-child:last-child:not(:only-child)::before { border-right: 1px solid var(--org-line); border-top-right-radius: 12px; }
        /* Con el script, las líneas se dibujan en la capa SVG (siguen a las tarjetas mientras se deslizan) y estas quedan
           solo como espacio entre niveles */
        .org-svg-lines .org-stem { background: transparent; }
        .org-svg-lines .org-child::before, .org-svg-lines .org-child::after { display: none; }
        .org-lines { position: absolute; left: 0; top: 0; z-index: 5; overflow: visible; pointer-events: none; }
        .org-lines path { fill: none; stroke: var(--org-line); stroke-width: 1; stroke-linejoin: round; }
        .org-lines path.is-dim { opacity: .35; }
        .org-lines path.is-preview { stroke: var(--org-flow); stroke-dasharray: 6 6; }

        /* Jefaturas de otra área (al filtrar por área): borde punteado y tono distinto para ubicar la rama. */
        .org-node.org-node-outside { border-style: dashed; border-color: #64748b; background-color: var(--panel-subtle); }
        /* Al buscar a alguien o al terminar de moverlo, su tarjeta se marca un momento con un anillo verde que se desvanece */
        @keyframes org-node-flash { 0% { border-color: var(--org-flow); box-shadow: 0 0 0 6px color-mix(in srgb, var(--org-flow) 28%, transparent); } 100% { box-shadow: 0 0 0 0 transparent; } }
        .org-node.org-node-flash { animation: org-node-flash 1.4s ease-out 1; }

        /* ===== Organigrama tipo Blender =====
           El árbol ocupa todo el espacio bajo la barra superior, sobre una cuadrícula de puntos que se mueve y se acerca
           con él. Encima van paneles flotantes con el diseño de Inicio: bordes de 18px, texto de 15px, 20px entre todo y
           la misma sombra pareja alrededor. */
        [data-panel] { --org-canvas: #f4f4f5; --org-dot: #d4d4d8; --org-muted: color-mix(in srgb, var(--panel-text) 58%, transparent); }
        html.module-dark-theme [data-panel] { --org-canvas: #0b0b0e; --org-dot: #27272a; }
        #organigrama-principal { zoom: calc(1 / var(--app-zoom, 1)); }
        .org-viewport {
            height: calc((var(--app-dvh) - 123px) * var(--app-zoom, 1));
            min-height: 560px;
            background-color: var(--org-canvas);
            background-image: radial-gradient(circle, var(--org-dot) 1.2px, transparent 1.3px);
            background-size: 24px 24px;
        }
        .org-panel {
            z-index: 30;
            padding: 20px;
            border: 1px solid var(--panel-border);
            border-radius: 18px;
            background: var(--panel-surface);
            color: var(--panel-text);
            box-shadow: 0 0 30px rgba(15, 23, 42, .12);
        }
        html.module-dark-theme .org-panel { box-shadow: 0 0 30px rgba(0, 0, 0, .6); }
        .org-panel-title-box { width: 380px; max-width: calc(100% - 40px); }
        /* Títulos como los de Inicio (19px, seminegrita); !important porque la regla monocroma del módulo fuerza 15px */
        [data-panel] .org-panel :is(h1, h2).org-title, .org-title { margin-bottom: 0; font-size: 16px !important; font-weight: 600 !important; line-height: 1.3; letter-spacing: -.01em; color: var(--panel-text); }
        .org-panel .org-gap { margin-top: 20px; }
        .org-subtitle { margin: 0; font-size: 15px; font-weight: 600; line-height: 1.3; color: var(--panel-text); }
        .org-muted { color: var(--org-muted); }
        .org-gap { margin-top: 20px; }
        .org-panel :is(p, span, h1, h2) { margin-bottom: 0; }
        .org-divider { border-color: var(--panel-border); }
        .org-chip { display: inline-flex; align-items: center; border: 1px solid var(--panel-border); border-radius: 18px; padding: 15px 20px; background: var(--panel-surface); line-height: 1.3; white-space: nowrap; }
        .org-swatch { display: inline-block; height: 12px; width: 12px; flex-shrink: 0; border-radius: 4px; margin: -1px 0; vertical-align: 0; }
        /* Los textos de los paneles miden lo que sus letras: así los 20px entre ellos son exactos */
        .org-panel :is(h1, h2, p) { text-box: trim-both cap alphabetic; }
        .org-panel p { line-height: calc(.7em + 20px); }
        .org-info { width: 320px; max-width: calc(100vw - 40px); }
        /* Mismo diseño que las tarjetas de Inicio: título de 16px en seminegrita y texto gris de 15px; 20px entre todo y entre líneas */
        [data-panel] .org-panel :is(h1, h2).org-card-title { margin: 0 0 0 0; font-size: 16px !important; font-weight: 600 !important; line-height: calc(.7em + 20px); color: var(--panel-text); }
        [data-panel] .org-panel p.org-card-text { margin: 20px 0 0; font-size: 15px !important; line-height: calc(.7em + 20px); color: var(--org-muted); }
        [data-panel] .org-panel :is(h1, h2).org-card-title.org-card-gap { margin-top: 20px; }
        /* Barra de herramientas: campos y botones del mismo alto (50px) y redondeo que las etiquetas de Inicio */
        .org-field { display: block; height: 50px; border: 1px solid var(--panel-border); border-radius: 18px; background: var(--panel-surface); color: var(--panel-text); padding: 0 20px; line-height: 1; box-shadow: 0 0 30px rgba(15, 23, 42, .12); }
        .org-field::placeholder { color: var(--org-muted); opacity: 1; }
        .org-field:focus { outline: none !important; box-shadow: 0 0 30px rgba(15, 23, 42, .12) !important; border-color: color-mix(in srgb, var(--panel-text) 45%, var(--panel-border)) !important; }
        .org-tool-btn { position: relative; display: grid; height: 50px; width: 50px; flex-shrink: 0; place-items: center; border: 1px solid var(--panel-border); border-radius: 18px; background: var(--panel-surface); color: var(--panel-text); box-shadow: 0 0 30px rgba(15, 23, 42, .12); transition: background-color 150ms ease; }
        .org-tool-btn:hover { background: var(--panel-hover, var(--panel-subtle)); }
        .org-tool-badge { position: absolute; top: -8px; right: -8px; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 9999px; background: #15803d; color: #fff; font-size: 12px !important; line-height: 22px; text-align: center; }
        html.module-dark-theme .org-tool-badge { background: #4ade80; color: #052e16; }
        #fullscreen-toggle { width: 50px !important; height: 50px !important; border-radius: 18px !important; background: var(--panel-surface) !important; box-shadow: 0 0 30px rgba(15, 23, 42, .12) !important; }
        /* Panel lateral de «Sin asignar» (como el panel lateral de Blender) */
        .org-side { top: 90px; bottom: 100px; width: 400px; max-width: calc(100% - 40px); overflow: hidden; }
        /* Lo que se corta con «…» no recorta las letras que bajan o suben de la línea (g, j, p, q, y, acentos); min-width: 0
           para que en las filas flexibles el texto respete su ancho y no se encime sobre lo de al lado */
        .org-side .truncate { overflow: clip; overflow-clip-margin: 10px; min-width: 0; }

        /* Listas (resultados de búsqueda, áreas y «Sin asignar") con el formato de «En línea ahora» de Inicio: renglones con
           20px de relleno separados por una línea fina, foto de 50x50 con su borde pegado, nombre en seminegrita y datos en
           gris con 20px entre ellos */
        .org-row { display: flex; width: 100%; align-items: center; gap: 20px; padding: 20px; margin: 0; text-align: left; transition: background-color 150ms ease; }
        :is(.org-list > li + li, #search-results > .org-row + .org-row) .org-row, #search-results > .org-row + .org-row { border-top: 1px solid var(--panel-border); }
        button.org-row:hover, .org-list .org-row:hover { background: var(--panel-subtle); }
        .org-avatar { display: grid; height: 50px; width: 50px; flex-shrink: 0; place-items: center; border: 1px solid var(--panel-border); border-radius: 9999px; background: var(--panel-surface); color: var(--panel-text); font-size: 15px !important; object-fit: cover; }
        .org-row-text { min-width: 0; flex: 1 1 auto; }
        .org-row-name, .org-row-meta { display: block; height: 10.5px; line-height: 10.5px; white-space: nowrap; overflow: clip; overflow-clip-margin: 10px; text-overflow: ellipsis; }
        .org-row-name { font-weight: 600 !important; color: var(--panel-text); }
        .org-row-meta { margin-top: 20px; color: var(--org-muted); }
        .org-option { display: block; width: 100%; padding: 20px; text-align: left; line-height: 1; text-box: trim-both cap alphabetic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: background-color 150ms ease; }
        .org-option + .org-option, template + .org-option { border-top: 1px solid var(--panel-border); }
        button.org-option:hover, .org-option[aria-selected="true"] { background: var(--panel-subtle); }
        /* El indicador de scroll queda dentro de las esquinas redondeadas (5px del borde, 18px arriba y abajo) */
        .org-thumb { right: 5px !important; }

        /* Verde de Inicio para lo que se puede soltar y para la vista previa; anillo y fondo suaves de las tarjetas de Inicio */
        [data-panel] { --org-flow: #15803d; --org-ring: color-mix(in srgb, var(--panel-accent) 35%, var(--panel-border)); --org-soft: color-mix(in srgb, var(--panel-accent) 6%, var(--panel-surface)); }
        html.module-dark-theme [data-panel] { --org-flow: #4ade80; --org-soft: #111116; }

        /* ===== Tarjetas del árbol con el diseño de Inicio =====
           Foto de 50px con el nombre a un lado (como «En línea ahora»), puesto y área en gris y un pie con la conexión y las
           acciones. 20px de relleno y 20px entre cada texto: cada texto mide lo que sus letras, así la distancia es exacta. */
        .org-node { position: relative; display: flex; width: 280px; flex-shrink: 0; flex-direction: column; padding: 0; border: 1px solid var(--panel-border); border-radius: 18px; background: var(--panel-surface); color: var(--panel-text); text-align: left; cursor: pointer; transition: border-color 180ms ease, box-shadow 180ms ease; }
        .org-node:hover { border-color: var(--org-ring); box-shadow: 0 0 20px rgba(15, 23, 42, .12); }
        html.module-dark-theme .org-node:hover { box-shadow: 0 0 20px rgba(0, 0, 0, .6); }
        .org-node-head { display: flex; min-width: 0; align-items: center; gap: 20px; padding: 20px 20px 0; }
        .org-node-avatar { position: relative; flex-shrink: 0; }
        .org-node-avatar > img, .org-node-avatar > .org-node-initials { display: grid; height: 50px; width: 50px; place-items: center; border: 1px solid var(--panel-border); border-radius: 9999px; background: var(--panel-header); color: var(--panel-text); object-fit: cover; }
        .org-node-online { position: absolute; right: 2px; bottom: 2px; height: 10px; width: 10px; border: 2px solid var(--panel-surface); border-radius: 9999px; background: #10b981; }
        .org-node-names { display: block; min-width: 0; flex: 1 1 auto; }
        .org-node :is(.org-node-name, .org-node-line, .org-node-presence) { display: block; line-height: calc(.7em + 20px); text-box: trim-both cap alphabetic; white-space: nowrap; overflow: clip; overflow-clip-margin: 10px; text-overflow: ellipsis; }
        .org-node .org-node-name { font-weight: 600; color: var(--panel-text); }
        .org-node .org-node-name + .org-node-name { margin-top: 20px; }
        .org-node-body { display: block; min-width: 0; padding: 20px; }
        .org-node .org-node-line { color: var(--org-muted); }
        .org-node .org-node-line + .org-node-line { margin-top: 20px; }
        .org-node .org-node-line.is-warn { color: #b45309; }
        html.module-dark-theme .org-node .org-node-line.is-warn { color: #fbbf24; }
        .org-node-foot { display: block; margin-top: auto; padding: 20px 90px 20px 20px; border-top: 1px solid var(--panel-border); }
        .org-node .org-node-presence { color: var(--org-muted); }
        .org-node .org-node-presence.is-online { color: var(--org-flow); }
        .org-node-actions { position: absolute; right: 20px; bottom: 17px; z-index: 20; display: flex; gap: 20px; }
        .org-node-actions > * { display: grid; height: 16px; width: 16px; place-items: center; color: var(--org-muted); transition: color 150ms ease; }
        .org-node-actions > :hover { color: var(--panel-text); }
        .org-node-actions > .org-node-delete:hover { color: #dc2626; }
        .org-drag-handle { cursor: grab; }
        /* Entrada: las tarjetas aparecen por nivel solo al abrir el organigrama; después ya no, para que al mover a alguien o
           al sincronizar nada parpadee */
        @keyframes org-node-in { from { opacity: 0; translate: 0 8px; } to { opacity: 1; translate: 0 0; } }
        .org-ready:not(.org-settled) .visual-node-wrapper > .relative { animation: org-node-in 380ms cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(80ms + var(--depth, 0) * 70ms); }

        /* ===== Mover personas =====
           Se arrastra la tarjeta (de su asa o dejándola presionada) o una fila de «Sin asignar». Mientras se arrastra aparece en
           el árbol un hueco punteado justo donde quedaría: arriba de una tarjeta queda a cargo de esa persona, abajo entra a su
           equipo y a los lados queda junto a ella. Su rama original se atenúa y lo demás se acomoda deslizándose. */
        .org-is-dragging, .org-is-dragging * { cursor: grabbing !important; }
        .org-is-dragging .org-node:hover { border-color: var(--panel-border); box-shadow: none; }
        .visual-node-wrapper.org-dragging .relative { opacity: .35; transition: opacity 200ms ease; }
        .org-node.org-drop-target { border-color: var(--org-flow) !important; box-shadow: 0 0 0 4px color-mix(in srgb, var(--org-flow) 18%, transparent) !important; }
        .org-node.org-drop-bad { border-color: #dc2626 !important; box-shadow: 0 0 0 4px rgba(220, 38, 38, .16) !important; }
        /* El hueco: mismo tamaño que una tarjeta, borde punteado verde, fondo verde muy suave y quién quedará ahí */
        .org-node.org-ph-card { border: 2px dashed var(--org-flow); background: color-mix(in srgb, var(--org-flow) 7%, var(--panel-surface)); cursor: grabbing; box-shadow: none !important; }
        .org-ph-card .org-ph-text { display: block; padding: 20px; color: var(--org-flow); font-weight: 500; line-height: calc(.7em + 20px); text-box: trim-both cap alphabetic; white-space: nowrap; overflow: clip; overflow-clip-margin: 10px; text-overflow: ellipsis; }
        @keyframes org-ph-saving { 50% { background-color: color-mix(in srgb, var(--org-flow) 15%, var(--panel-surface)); } }
        .org-node.org-ph-card.is-saving { animation: org-ph-saving 1.1s ease-in-out infinite; }
        /* Tarjeta que sigue al cursor (va fuera del organigrama: el script le copia los colores del tema) */
        .org-un-row.is-dragging-row { opacity: .4; }
        .org-drag-ghost { transform-origin: 0 0; }
        .org-drag-ghost { position: fixed; left: 0; top: 0; z-index: 200005; display: flex; width: 320px; align-items: center; gap: 20px; padding: 20px; border: 1px solid var(--panel-border, #d4d4d8); border-radius: 18px; background: var(--panel-surface, #fff); color: var(--panel-text, #18181b); font-family: inherit; font-size: 15px; pointer-events: none; box-shadow: 0 22px 48px -20px rgba(15, 23, 42, .5); will-change: transform; transition: border-color 150ms ease; }
        .org-drag-ghost .org-ghost-avatar { display: grid; height: 50px; width: 50px; flex-shrink: 0; place-items: center; border: 1px solid var(--panel-border, #d4d4d8); border-radius: 9999px; background: var(--panel-header, #f4f4f5); object-fit: cover; }
        .org-drag-ghost .org-ghost-text { display: block; min-width: 0; flex: 1 1 auto; }
        .org-drag-ghost :is(.org-ghost-name, .org-ghost-hint) { display: block; line-height: calc(.7em + 20px); text-box: trim-both cap alphabetic; }
        .org-drag-ghost .org-ghost-name { font-weight: 600; white-space: nowrap; overflow: clip; overflow-clip-margin: 10px; text-overflow: ellipsis; }
        .org-drag-ghost .org-ghost-hint { margin-top: 20px; color: var(--org-muted, #71717a); transition: color 150ms ease; }
        .org-drag-ghost[data-state="ok"] { border-color: var(--org-flow, #15803d); }
        .org-drag-ghost[data-state="ok"] .org-ghost-hint { color: var(--org-flow, #15803d); }
        .org-drag-ghost[data-state="bad"] { border-color: #dc2626; }
        .org-drag-ghost[data-state="bad"] .org-ghost-hint { color: #dc2626; }

        /* ===== Panel «Sin asignar» con el diseño de Inicio =====
           Encabezado como las secciones de Inicio (título de 19px, texto gris y etiqueta con el total), un buscador y la lista
           como «En línea ahora»: filas de 20px separadas por una línea fina, foto de 50px, nombre en seminegrita y datos en
           gris. Las acciones de cada fila aparecen al pasar el puntero. */
        .org-side-head { display: block; padding: 20px; border-bottom: 1px solid var(--panel-border); }
        .org-side-head-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; }
        .org-side .org-side-search { width: 100%; margin-top: 20px; box-shadow: none !important; }
        .org-un-row { position: relative; display: flex; align-items: center; gap: 20px; padding: 20px; cursor: grab; transition: background-color 150ms ease, opacity 150ms ease; }
        .org-list > li + li .org-un-row { border-top: 1px solid var(--panel-border); }
        .org-un-row:hover { background: var(--org-soft); }
        .org-un-row .org-avatar { background: var(--panel-header); }
        .org-un-text { display: block; min-width: 0; flex: 1 1 auto; }
        .org-un-text > span { display: block; line-height: calc(.7em + 20px); text-box: trim-both cap alphabetic; white-space: nowrap; overflow: clip; overflow-clip-margin: 10px; text-overflow: ellipsis; }
        .org-un-text > .org-un-name { font-weight: 600; color: var(--panel-text); }
        .org-un-text > .org-un-meta { margin-top: 20px; color: var(--org-muted); }
        /* Las acciones flotan sobre el final de la fila (no le quitan espacio al nombre) y aparecen al pasar el puntero */
        .org-un-actions { position: absolute; right: 0; top: 0; bottom: 0; display: flex; align-items: center; gap: 6px; padding: 0 20px 0 40px; background: linear-gradient(to right, transparent, var(--org-soft) 30px); opacity: 0; pointer-events: none; transition: opacity 150ms ease; }
        .org-un-row:hover .org-un-actions, .org-un-row:focus-within .org-un-actions { pointer-events: auto; }
        .org-un-row:hover .org-un-actions, .org-un-row:focus-within .org-un-actions { opacity: 1; }
        .org-un-actions > button { display: grid; height: 30px; width: 30px; place-items: center; border-radius: 10px; color: var(--org-muted); transition: background-color 150ms ease, color 150ms ease; }
        .org-un-actions > button:hover { background: var(--panel-surface); color: var(--panel-text); }
        .org-un-actions > button.is-danger:hover { color: #dc2626; }
        .org-un-row.is-hidden { opacity: .5; }
        .org-side-empty { display: block; padding: 20px; color: var(--org-muted); line-height: calc(.7em + 20px); text-box: trim-both cap alphabetic; }
        /* Al arrastrar a alguien del árbol aparece arriba de la lista el lugar para soltarlo y sacarlo del organigrama */
        .org-side-drop { display: none; margin: 20px 20px 0; padding: 20px; border: 2px dashed var(--panel-border); border-radius: 18px; color: var(--org-muted); text-align: center; line-height: calc(.7em + 20px); text-box: trim-both cap alphabetic; transition: border-color 150ms ease, background-color 150ms ease, color 150ms ease; }
        .org-drag-from-tree .org-side-drop { display: block; }
        .org-side.org-drop-zone { border-color: var(--org-flow) !important; }
        .org-side.org-drop-zone .org-side-drop { border-color: var(--org-flow); background: color-mix(in srgb, var(--org-flow) 7%, var(--panel-surface)); color: var(--org-flow); }
        @keyframes org-row-in { from { opacity: 0; translate: 0 -8px; } to { opacity: 1; translate: 0 0; } }
        .org-list > li.org-row-new { animation: org-row-in 380ms cubic-bezier(.16, 1, .3, 1) both; }

        /* Botones de la barra: se resaltan al pasar el puntero y al presionarlos */
        .org-tool-btn, #fullscreen-toggle { transition: background-color 150ms ease, border-color 150ms ease !important; }
        .org-tool-btn:hover, #fullscreen-toggle:hover { border-color: var(--panel-text) !important; }
        .org-tool-btn[aria-expanded="true"] { border-color: var(--org-flow) !important; color: var(--org-flow); }

        @media (prefers-reduced-motion: reduce) {
            .org-ready .visual-node-wrapper > .relative { animation: none; }
            .org-node, .org-un-row, .org-un-actions, .org-drag-ghost { transition: none !important; }
            .org-node.org-ph-card.is-saving, .org-list > li.org-row-new { animation: none; }
        }

        /* Formularios abiertos desde el Organigrama con el diseño de Inicio: bordes de 18px, título de 19px en seminegrita,
           subtítulo gris, títulos de sección oscuros en seminegrita y botones redondeados como las etiquetas */
        .org-user-modal,
        body:has(#organigrama-principal) [data-administration-modal] .administration-form-panel { border-radius: 18px !important; border-color: var(--panel-border) !important; box-shadow: 0 24px 60px -20px rgba(15, 23, 42, .4) !important; }
        .org-user-modal header h1,
        body:has(#organigrama-principal) [data-administration-modal] .administration-form-panel header h1 { font-size: 19px !important; font-weight: 600 !important; line-height: 1.3 !important; color: var(--panel-text) !important; }
        .org-user-modal header p,
        body:has(#organigrama-principal) [data-administration-modal] .administration-form-panel header p { color: var(--org-muted) !important; }
        .org-user-modal .admin-title-icon,
        body:has(#organigrama-principal) [data-administration-modal] .admin-title-icon { display: none !important; }
        .org-user-modal .admin-section-title,
        body:has(#organigrama-principal) [data-administration-modal] .admin-section-title { color: var(--panel-text) !important; font-size: 16px !important; font-weight: 600 !important; }
        .org-user-modal label, .org-user-modal [id$="-label"] { color: var(--org-muted) !important; }
        .org-user-modal :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea),
        body:has(#organigrama-principal) [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea) { border-radius: 12px !important; }
        .org-user-modal .admin-btn, .org-user-modal button[class*="bg-red"],
        body:has(#organigrama-principal) [data-administration-modal] .admin-btn { border-radius: 18px !important; }
        .org-user-modal .org-choice { padding: 20px !important; border: 1px solid var(--panel-border) !important; border-radius: 18px !important; background: color-mix(in srgb, var(--panel-accent) 5%, var(--panel-surface)) !important; }
        .org-user-modal .org-choice + section { margin-top: 20px; }
        .org-user-modal .org-choice p { color: var(--org-muted); line-height: calc(.7em + 20px); }

        /* Entrada fluida: el árbol aparece ya centrado (si el script no corre, aparece igual a los 2 s) y los paneles suben
           10px mientras aparecen, en cascada y rápido */
        #org-tree-wrapper { opacity: 0; transition: opacity 260ms ease-out; animation: org-show-late 0s 2s forwards; }
        .org-ready #org-tree-wrapper { opacity: 1; animation: none; }
        @keyframes org-show-late { to { opacity: 1; } }
        @keyframes org-rise { from { opacity: 0; translate: 0 10px; } to { opacity: 1; translate: 0 0; } }
        .org-toolbar, .org-side { animation: org-rise 320ms cubic-bezier(.16, 1, .3, 1) both; }
        .org-toolbar { animation-delay: 60ms; }
        .org-side { animation-delay: 120ms; }
        @media (prefers-reduced-motion: reduce) {
            #org-tree-wrapper { transition: none; }
            .org-toolbar, .org-side { animation: none; }
        }
        /* Pantallas angostas: la barra va abajo (en dos filas: campos arriba y botones abajo), los menús se abren hacia arriba
           y el panel de «Sin asignar» queda encima del árbol sin tapar la barra */
        @media (max-width: 1023px) {
            .org-toolbar { top: auto !important; bottom: 20px; left: 20px; right: 20px !important; flex-wrap: wrap; justify-content: flex-end; }
            .org-toolbar > div:not(.relative):first-child, .org-toolbar > div.w-\[200px\] { flex: 1 1 200px; }
            .org-drop { top: auto !important; bottom: calc(100% + 20px) !important; }
            .org-side { top: 20px; bottom: 160px; }
        }

        /* Estilo para el contenedor en modo fullscreen */
        body.org-chart-fullscreen {
            overflow: hidden;
        }
        body.org-chart-fullscreen #org-tree-container {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9000 !important;
            /* El Organigrama ya va sin el zoom de la app, así que ocupa la ventana completa con 100vw x 100vh */
            width: 100vw !important;
            max-height: none !important;
            height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
            padding: 0 !important;
        }

        /* Botón fullscreen: cuadrado y sin contorno azul en hover/focus */
        #fullscreen-toggle {
            outline: none !important;
            box-shadow: none !important;
            border: 1px solid var(--panel-border);
            width: 47px;
            height: 47px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background-color: var(--panel-surface);
            color: var(--panel-text);
            transition: background-color 0.2s;
        }
        #fullscreen-toggle:hover,
        #fullscreen-toggle:focus,
        #fullscreen-toggle:active {
            outline: none !important;
            box-shadow: none !important;
            border-color: var(--panel-border);
            background-color: var(--panel-hover, var(--panel-subtle));
        }
        #fullscreen-toggle svg {
            width: 24px;
            height: 24px;
            color: var(--panel-text);
        }

        /* Contenedor del mensaje sin datos ocupa todo el espacio */
        .org-tree-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-height: 400px;
            color: #6b7280;
            font-size: 15px;
            text-align: center;
        }
        .organization-module-card {
            opacity: var(--carousel-opacity, 1);
            transform: translate3d(var(--carousel-edge-shift, 0px), 0, var(--carousel-depth, 0px)) scale(var(--carousel-scale, 1)) rotateY(var(--carousel-rotation, 0deg));
            transform-origin: center center;
            transform-style: preserve-3d;
            transition: transform 90ms linear, opacity 90ms linear;
            flex: 0 0 390px;
            width: 390px;
            min-width: 390px !important;
            max-width: 390px;
            border-radius: 10px !important;
            box-shadow: none;
            background-color: rgba(255, 255, 255, 0.48) !important;
            background-image:
                linear-gradient(135deg, rgba(255, 255, 255, 0.68), rgba(255, 255, 255, 0.24) 48%, rgba(255, 255, 255, 0.50)),
                radial-gradient(circle, rgba(26, 58, 107, 0.13) 0.7px, transparent 0.8px);
            background-size: 100% 100%, 11px 11px;
            background-position: 0 0;
            backdrop-filter: blur(14px) saturate(135%);
            -webkit-backdrop-filter: blur(14px) saturate(135%);
            scroll-snap-align: center;
            cursor: default !important;
        }
        .organization-carousel-frame {
            container-type: inline-size;
            justify-content: stretch;
            min-width: 777px;
        }
        .organization-card-carousel {
            flex: 1 1 auto;
            width: 100%;
            max-width: none;
            padding-inline: max(0px, calc((100% - 390px) / 2));
            scrollbar-width: none;
            scroll-behavior: auto;
            scroll-snap-type: none;
            perspective: 1400px;
            perspective-origin: center center;
            touch-action: pan-y;
            overscroll-behavior-x: none;
        }
        .organization-card-carousel::-webkit-scrollbar {
            display: none;
        }
        .organization-module-card footer button {
            width: 100%;
            height: 100%;
            min-height: 52px;
            cursor: pointer;
            touch-action: manipulation;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* Tema oscuro: misma paleta que Reportes de soporte. El fondo de la pantalla es el de la página
           (sin bloque blanco ni pastilla oscura en el encabezado), los textos azul oscuro pasan al azul claro
           y las líneas y campos usan el gris de las demás pantallas. */
        html.module-dark-theme #main-content div.administration-monochrome {
            --administration-field-border: #3f3f46;
            background-color: var(--panel-page) !important;
        }
        html.module-dark-theme #main-content .administration-monochrome section.m-0,
        html.module-dark-theme #main-content .administration-monochrome section.m-0 > div {
            background-color: transparent !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class~="text-[#1A3A6B]"],
        html.module-dark-theme #main-content .administration-monochrome [class~="hover:text-[#1A3A6B]"]:hover {
            color: #6aa9ff !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class~="text-[#1A3A6B]"][class~="hover:text-black"]:hover {
            color: #fafafa !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class~="hover:text-red-600"]:hover {
            color: #f87171 !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class~="text-emerald-700"] {
            color: #4ade80 !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class*="border-[#d4d4d8]"] {
            border-color: #3f3f46 !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class*="bg-zinc-100"] {
            background-color: #202023 !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class~="hover:bg-zinc-50"] {
            background-color: #18181b !important;
        }
        html.module-dark-theme #main-content .administration-monochrome [class~="hover:bg-zinc-50"]:hover {
            background-color: #26262b !important;
        }
    </style>
    @endpush

    <div class="w-full">
    <main x-ref="scaledMain" class="flex w-full min-w-0 flex-col {{ $orgChartPage ? 'gap-0 p-0' : 'gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]' }}">
    <!-- Cabecera: mismo patrón que el inicio (en el organigrama no se muestra: el título va en un panel flotante) -->
    <section data-page-header @class(['flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]', '!hidden' => $orgChartPage])>
        <div class="flex min-w-0 max-w-full items-center gap-[15px]">
            <span class="panel-surface panel-border panel-text grid h-[50px] w-[50px] shrink-0 place-items-center rounded-xl border">
                <svg class="h-[30px] w-[30px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
            </span>
            <div class="min-w-0">
                <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">{{ $orgChartPage ? 'Organigrama' : 'Administración' }}</h1>
                <p class="mt-[15px] leading-none">{{ $orgChartPage ? 'Estructura jerárquica: líneas de mando y niveles de supervisión.' : 'Gestiona colaboradores, roles, permisos y la estructura organizacional.' }}</p>
            </div>
        </div>
        <div class="w-full min-w-0 lg:w-auto lg:text-right">
            <p class="panel-accent leading-none">Organización</p>
            <p class="mt-[15px] flex items-center gap-[10px] leading-none lg:justify-end"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>Sincronizado en tiempo real</p>
        </div>
    </section>

    <section class="hidden">
            @if (false)
        <div x-show="administrationView === 'carousel'" class="organization-carousel-frame relative mt-[36px] flex w-full items-center" x-data="{ move(direction) { window.organizationCarouselMove($refs.track, direction) } }">
            <button type="button" @click="move(-1)" class="absolute left-0 top-1/2 z-30 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-[#1A3A6B] text-white focus:outline-none focus:ring-0" aria-label="Ver tarjetas anteriores">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" /></svg>
            </button>
            <div x-ref="track" data-organization-sortable role="menu" aria-label="Opciones de administración" class="organization-card-carousel flex min-w-0 flex-1 flex-nowrap gap-5 overflow-x-auto" style="align-items: flex-start;">
            @if ($canManageUsers)
                <article data-organization-module="users" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                    <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-[20px] bg-[#1A3A6B] px-[24px]">
                            <div class="flex min-w-0 items-center gap-[15px]">
                                <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><x-feathericon-users class="h-6 w-6" /></span>
                                <div class="min-w-0">
                                    <h2 class="truncate text-[21px] font-bold leading-none text-white">Usuarios</h2>
                                    <p class="mt-[8px] whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Aprovisionamiento</p>
                                </div>
                            </div>
                            <span class="whitespace-nowrap rounded-lg border border-emerald-300/40 bg-emerald-500/20 px-3 py-1.5 text-[12px] font-semibold text-emerald-100"><span class="mr-1.5 inline-block h-2 w-2 rounded-full bg-emerald-400"></span>{{ $onlineUserCount }} en línea</span>
                    </header>

                    <div class="flex min-h-0 flex-1 flex-col gap-[20px] p-[24px]">
                        <p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Crea colaboradores y administra sus accesos, cuentas y perfiles laborales en tiempo real.</p>

                        <div class="flex flex-col gap-[20px]">
                            <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-[15px]">
                                <div class="flex shrink-0 -space-x-2">
                                    @foreach ($recentOrganizationUsers as $recentUser)
                                        @php
                                            $recentNameParts = array_values(array_filter(preg_split('/\s+/', trim($recentUser->name.' '.$recentUser->last_name))));
                                            $recentInitials = collect(array_slice($recentNameParts, 0, 2))->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                                        @endphp
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-[#1A3A6B] text-[11px] font-bold text-white" title="{{ trim($recentUser->name.' '.$recentUser->last_name) }}">{{ $recentInitials ?: '?' }}</span>
                                    @endforeach
                                    @if (($totalUsers ?? 0) > $recentOrganizationUsers->count())
                                        <span class="flex h-9 min-w-9 items-center justify-center rounded-full border-2 border-white bg-[#DCEAFF] px-1 text-[11px] font-bold text-[#1A3A6B]">+{{ ($totalUsers ?? 0) - $recentOrganizationUsers->count() }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0"><strong class="block truncate text-[14px] text-[#102A52]">{{ $totalRoles ?? 0 }} roles configurados</strong><span class="mt-[5px] block truncate text-[12px] font-medium text-[#55749D]">Última alta {{ $lastOrganizationUserCreatedAt ? $lastOrganizationUserCreatedAt->locale('es')->diffForHumans() : 'sin registro' }}</span></div>
                            </div>
                            <div class="grid grid-cols-3 gap-[8px]">
                                @forelse ($organizationAreaUserCounts as $areaUserCount)
                                    <span class="truncate rounded-lg border border-[#B7CEEA] bg-[#EEF5FF] px-[10px] py-[9px] text-center text-[11px] font-medium uppercase text-[#1F4D86]" title="{{ $areaUserCount->users_count }} {{ $areaUserCount->name }}">{{ $areaUserCount->users_count }} {{ $areaUserCount->name }}</span>
                                @empty
                                    <span class="col-span-3 rounded-lg border border-[#B7CEEA] bg-[#EEF5FF] px-[10px] py-[9px] text-center text-[11px] font-medium text-[#1F4D86]">Sin áreas asignadas</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] px-[10px] py-[10px]">
                        <button type="button" wire:click="openUserManagement('crear')" class="inline-flex min-w-0 items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none focus:ring-0">
                            <svg class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14" /></svg>
                            <span>Crear</span>
                        </button>
                        <button type="button" wire:click="openUserManagement('editar')" class="inline-flex min-w-0 items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none focus:ring-0">
                            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                            <span>Editar</span>
                        </button>
                        <button type="button" wire:click="openUserManagement('eliminar')" class="inline-flex min-w-0 items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none focus:ring-0">
                            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                            <span>Eliminar</span>
                        </button>
                    </footer>
                </article>
            @endif

            @if ($canManageRoles)
                <article data-organization-module="roles" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                    <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-[20px] bg-[#1A3A6B] px-[24px]">
                        <div class="flex min-w-0 items-center gap-[15px]">
                            <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><x-feathericon-lock class="h-6 w-6" /></span>
                            <div class="min-w-0"><h2 class="truncate text-[21px] font-bold leading-none text-white">Roles</h2><p class="mt-[8px] whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Responsabilidades</p></div>
                        </div>
                        <span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">{{ $totalRoles ?? 0 }} perfiles</span>
                    </header>
                    <div class="flex min-h-0 flex-1 flex-col gap-[20px] p-[24px]">
                        <p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Define responsabilidades, alcances jerárquicos y niveles de acceso para cada perfil.</p>
                        <div class="space-y-[10px]">
                            @forelse ($organizationRoleUserCounts as $listedRole)
                                <div class="flex min-w-0 items-center gap-3" title="{{ $listedRole->role }}: {{ $listedRole->users_count }} personas">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-[11px] font-bold text-white">{{ mb_strtoupper(mb_substr($listedRole->role, 0, 2)) }}</span>
                                    <div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-2 text-[11px] font-semibold text-[#1F4D86]"><span class="truncate uppercase">{{ $listedRole->role }}</span><span>{{ $listedRole->users_count }} pers.</span></div><div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#DCE9F8]"><span class="block h-full rounded-full bg-[#4C78B2]" style="width: {{ min(100, max(14, $listedRole->users_count * 10)) }}%"></span></div></div>
                                </div>
                            @empty
                                <span class="block rounded-lg border border-dashed border-[#B7CEEA] px-[12px] py-[10px] text-center text-[11px] font-medium text-[#1F4D86]">Sin perfiles configurados</span>
                            @endforelse
                        </div>
                        @if ($roles->count() > 4)<p class="text-[12px] font-semibold text-[#55749D]">+{{ $roles->count() - 4 }} perfiles adicionales</p>@endif
                    </div>
                    <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] px-[10px] py-[10px]">
                        <button type="button" wire:click="openRoleManagement('crear')" class="inline-flex items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none"><span class="text-[18px] font-normal">+</span><span>Crear</span></button>
                        <button type="button" wire:click="openRoleManagement('editar')" class="inline-flex items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none"><svg class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg><span>Editar</span></button>
                        <button type="button" wire:click="openRoleManagement('eliminar')" class="inline-flex items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none"><svg class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg><span>Eliminar</span></button>
                    </footer>
                </article>
            @endif

            <article data-organization-module="positions" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-[20px] bg-[#1A3A6B] px-[24px]">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg></span>
                        <div class="min-w-0"><h2 class="truncate text-[21px] font-bold leading-none text-white">Puestos</h2><p class="mt-[8px] whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Estructura operativa</p></div>
                    </div>
                    <span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">{{ $jobPositions->count() }} definidos</span>
                </header>
                <div class="flex min-h-0 flex-1 flex-col gap-[20px] p-[24px]">
                    <p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Crea y organiza los puestos operativos, junto con su modalidad de compensación.</p>
                    <div class="relative space-y-[9px] pl-[18px] before:absolute before:bottom-2 before:left-[6px] before:top-2 before:w-px before:bg-[#B7CEEA]">
                        @forelse ($organizationPositionUserCounts as $listedPosition)
                            <div class="relative flex min-w-0 items-center gap-3 rounded-lg bg-[#EEF5FF]/80 px-3 py-[9px] text-[11px] text-[#1F4D86] before:absolute before:-left-[17px] before:h-[11px] before:w-[11px] before:rounded-full before:border-[3px] before:border-[#F7FAFE] before:bg-[#4C78B2]" title="{{ $listedPosition->name }}: {{ $listedPosition->users_count }} personas"><span class="flex-1 truncate font-semibold uppercase">{{ $listedPosition->name }}</span><span class="rounded-full bg-white px-2 py-0.5 font-bold text-[#102A52]">{{ $listedPosition->users_count }}</span></div>
                        @empty
                            <span class="block rounded-lg border border-dashed border-[#B7CEEA] px-[12px] py-[10px] text-center text-[11px] font-medium text-[#1F4D86]">Sin puestos configurados</span>
                        @endforelse
                    </div>
                    @if ($jobPositions->count() > 4)<p class="text-[12px] font-semibold text-[#55749D]">+{{ $jobPositions->count() - 4 }} puestos adicionales</p>@endif
                </div>
                <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] px-[10px] py-[10px]">
                    <button type="button" aria-label="Agregar Puesto Operativo" wire:click="openJobPositionModal('crear')" class="inline-flex items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none focus:ring-0"><span class="text-[18px] font-normal">+</span><span>Crear</span></button>
                    <button type="button" wire:click="openJobPositionModal('editar')" class="inline-flex items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none focus:ring-0"><svg class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg><span>Editar</span></button>
                    <button type="button" wire:click="openJobPositionModal('eliminar')" class="inline-flex items-center justify-center gap-[10px] rounded-lg px-[10px] text-[13px] font-semibold text-white focus:outline-none focus:ring-0"><svg class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg><span>Eliminar</span></button>
                </footer>
            </article>

            <article data-organization-module="areas" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-5 bg-[#1A3A6B] px-6"><div class="flex min-w-0 items-center gap-[15px]"><span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20V8l8-4 8 4v12M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" /></svg></span><div class="min-w-0"><h2 class="text-[21px] font-bold leading-none text-white">Áreas</h2><p class="mt-2 whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Divisiones organizacionales</p></div></div><span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">{{ $physicalAreas->count() }} áreas</span></header>
                <div class="flex min-h-0 flex-1 flex-col gap-5 p-6"><p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Organiza departamentos, unidades y equipos dentro de la estructura corporativa.</p><div class="relative grid grid-cols-2 gap-x-5 gap-y-4 before:absolute before:left-1/2 before:top-5 before:h-[calc(100%-40px)] before:w-px before:bg-[#CADBEF]">@forelse ($organizationAreaUserCounts as $listedArea)<div class="relative z-10 flex min-w-0 flex-col items-center text-center"><span class="flex h-10 w-10 items-center justify-center rounded-full border-4 border-[#F7FAFE] bg-[#DCE9F8] text-[12px] font-bold text-[#1A3A6B]">{{ $listedArea->users_count }}</span><span class="mt-1.5 max-w-full truncate text-[11px] font-semibold uppercase text-[#1F4D86]">{{ $listedArea->name }}</span></div>@empty<span class="col-span-2 rounded-lg border border-dashed border-[#B7CEEA] px-3 py-[10px] text-center text-[11px] font-medium text-[#1F4D86]">Sin personas asignadas</span>@endforelse</div>@if ($physicalAreas->count() > 3)<p class="text-center text-[12px] font-semibold text-[#55749D]">{{ $physicalAreas->count() }} nodos en la estructura</p>@endif</div>
                <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] px-[10px] py-[10px]"><button type="button" wire:click="openPhysicalAreaModal('crear')" class="inline-flex items-center justify-center gap-2 text-[13px] font-semibold text-white"><span class="text-lg">+</span>Crear<span class="sr-only">Agregar &Aacute;rea</span></button><button type="button" wire:click="openPhysicalAreaModal('editar')" class="inline-flex items-center justify-center gap-2 text-[13px] font-semibold text-white"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>Editar</button><button type="button" wire:click="openPhysicalAreaModal('eliminar')" class="inline-flex items-center justify-center gap-2 text-[13px] font-semibold text-white"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13" /></svg>Eliminar</button></footer>
            </article>

            @if ($canManagePermissions)
                <article data-organization-module="permissions" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                    <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-5 bg-[#1A3A6B] px-6"><div class="flex min-w-0 items-center gap-[15px]"><span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><x-feathericon-shield class="h-6 w-6" /></span><div><h2 class="text-[21px] font-bold leading-none text-white">Permisos</h2><p class="mt-2 whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Seguridad y accesos</p></div></div><span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">{{ $totalPermissions }} reglas</span></header>
                    <div class="flex min-h-0 flex-1 flex-col gap-5 p-6"><p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Consulta y administra la matriz de acceso disponible para cada perfil.</p><div class="flex items-center justify-center gap-5"><div class="relative flex h-[118px] w-[118px] shrink-0 items-center justify-center rounded-full border-[10px] border-[#DCE9F8] bg-white/60"><div class="text-center"><x-feathericon-shield class="mx-auto h-6 w-6 text-[#1A3A6B]" /><strong class="mt-1 block text-[20px] text-[#102A52]">{{ $totalPermissions }}</strong><span class="text-[9px] font-semibold uppercase tracking-wider text-[#55749D]">reglas</span></div><span class="absolute bottom-0 right-0 h-5 w-5 rounded-full border-4 border-white bg-emerald-500"></span></div><div class="space-y-3 text-[11px] text-[#55749D]"><p><strong class="block text-[19px] text-[#102A52]">{{ $totalRoles }}</strong>Roles protegidos</p><p><strong class="block text-[19px] text-[#102A52]">{{ count($basePermissionProfiles) }}</strong>Perfiles base</p></div></div><p class="text-center text-[12px] font-semibold text-[#1F4D86]">Control por rol habilitado</p></div>
                    <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] p-[10px]"><livewire:administracion.permissions.catalog-manager :card-actions="true" :key="'permission-card-actions'" /></footer>
                </article>
            @endif

            @if ($canManageAssignments)
                <article data-organization-module="assignments" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                    <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-5 bg-[#1A3A6B] px-6"><div class="flex min-w-0 items-center gap-[15px]"><span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><x-feathericon-git-merge class="h-6 w-6" /></span><div><h2 class="text-[21px] font-bold leading-none text-white">Asignaciones</h2><p class="mt-2 whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Relaciones de trabajo</p></div></div><span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">Vinculado</span></header>
                    <div class="flex min-h-0 flex-1 flex-col gap-5 p-6"><p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Relaciona responsables, auxiliares y equipos dentro de la operación.</p><div class="relative flex items-start justify-between px-2 py-5 before:absolute before:left-[58px] before:right-[58px] before:top-[52px] before:h-px before:bg-[#AFC8E6]"><div class="relative z-10 text-center"><span class="flex h-16 w-16 items-center justify-center rounded-full bg-[#1A3A6B] text-[21px] font-bold text-white">{{ $organizationAssignmentCounts['relations'] }}</span><span class="mt-2 block text-[11px] font-semibold text-[#55749D]">Relaciones</span></div><span class="absolute left-1/2 top-[52px] z-10 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-[#B7CEEA] bg-[#F7FAFE] text-[#4C78B2]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 8 3 12l4 4m10-8 4 4-4 4M4 12h16" /></svg></span><div class="relative z-10 text-center"><span class="flex h-16 w-16 items-center justify-center rounded-full bg-[#DCE9F8] text-[21px] font-bold text-[#102A52]">{{ $organizationAssignmentCounts['interns'] }}</span><span class="mt-2 block text-[11px] font-semibold text-[#55749D]">Auxiliares</span></div></div></div>
                    <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] p-[10px]"><livewire:administracion.relationship.gestion-relaciones-jerarquicas :card-actions="true" :key="'assignment-card-actions'" /><button type="button" wire:click="openAssignmentModal('relationships')" class="hidden" tabindex="-1" aria-hidden="true"></button></footer>
                </article>
            @endif

            @if (auth()->user()->isAdmin())
                <article data-organization-module="customers" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                    <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-5 bg-[#1A3A6B] px-6"><div class="flex min-w-0 items-center gap-[15px]"><span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><x-feathericon-users class="h-6 w-6" /></span><div><h2 class="text-[21px] font-bold leading-none text-white">Clientes</h2><p class="mt-2 whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Directorio corporativo</p></div></div><span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">{{ $organizationCustomerCount }} activos</span></header>
                    <div class="flex min-h-0 flex-1 flex-col gap-5 p-6">
                        <p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Administra el directorio de clientes y conserva sus relaciones operativas.</p>
                        <div class="space-y-2">
                            @forelse ($organizationCustomers->take(3) as $customer)
                                @php
                                    $customerName = trim($customer->name.' '.$customer->last_name);
                                @endphp
                                <div class="flex min-w-0 items-center gap-3 border-b border-[#DCE9F8] pb-2" title="{{ $customerName }}">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#DCE9F8] text-[11px] font-bold text-[#1A3A6B]">{{ mb_strtoupper(mb_substr($customerName, 0, 2)) }}</span>
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-semibold uppercase text-[#1F4D86]">{{ $customerName }}</span>
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
                                </div>
                            @empty
                                <span class="block rounded-lg border border-dashed border-[#B7CEEA] px-3 py-[10px] text-center text-[11px] text-[#1F4D86]">Sin clientes registrados</span>
                            @endforelse
                        </div>
                        @if ($organizationCustomerCount > 3)
                            <p class="text-[12px] font-semibold text-[#55749D]">+{{ $organizationCustomerCount - 3 }} clientes en el directorio</p>
                        @endif
                    </div>
                    <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] p-[10px]"><livewire:customer.catalog-manager :card-actions="true" :key="'customer-card-actions'" /></footer>
                </article>
                <article data-organization-module="activities" class="organization-module-card flex h-[520px] w-full min-w-[350px] cursor-grab flex-col overflow-hidden rounded-[22px] bg-white active:cursor-grabbing">
                    <header class="grid h-[108px] shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-5 bg-[#1A3A6B] px-6"><div class="flex min-w-0 items-center gap-[15px]"><span class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span><div><h2 class="text-[21px] font-bold leading-none text-white">Actividades</h2><p class="mt-2 whitespace-nowrap text-[13px] font-medium text-[#C9E1FF]">Catálogo operativo</p></div></div><span class="whitespace-nowrap rounded-lg border border-white/15 bg-white/10 px-3 py-1.5 text-[12px] font-semibold text-white">{{ $organizationActivityCount }} activas</span></header>
                    <div class="flex min-h-0 flex-1 flex-col gap-5 p-6"><p class="text-[14px] font-medium leading-[1.75] text-[#1F4677]">Configura las actividades disponibles para el registro y control de horas.</p><div class="relative space-y-3 pl-7 before:absolute before:bottom-2 before:left-[10px] before:top-2 before:w-px before:bg-[#B7CEEA]">@forelse ($organizationActivities as $activity)<div class="relative min-w-0 before:absolute before:-left-[25px] before:top-[1px] before:h-[15px] before:w-[15px] before:rounded-full before:border-4 before:border-[#F7FAFE] before:bg-[#4C78B2]" title="{{ $activity->sub_service }}"><span class="block truncate text-[11px] font-semibold uppercase text-[#1F4D86]">{{ $activity->sub_service }}</span><span class="mt-0.5 block text-[10px] text-[#7892B3]">Disponible para registro</span></div>@empty<span class="block rounded-lg border border-dashed border-[#B7CEEA] px-3 py-[10px] text-center text-[11px] text-[#1F4D86]">Sin actividades registradas</span>@endforelse</div>@if ($organizationActivityCount > 4)<p class="text-[12px] font-semibold text-[#55749D]">+{{ $organizationActivityCount - 4 }} actividades en catálogo</p>@endif</div>
                    <footer class="grid h-[72px] shrink-0 grid-cols-3 bg-[#1A3A6B] p-[10px]"><livewire:time-control.activity-catalog-manager :card-actions="true" :key="'activity-card-actions'" /></footer>
                </article>
            @endif
            </div>
            <button type="button" @click="move(1)" class="absolute right-0 top-1/2 z-30 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-[#1A3A6B] text-white focus:outline-none focus:ring-0" aria-label="Ver tarjetas siguientes">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" /></svg>
            </button>
        </div>
        <div x-show="administrationView === 'carousel'" class="mt-[50px] flex flex-wrap items-center justify-between gap-3 p-0 text-[10px] font-medium text-[#55749D]">
            <div class="flex flex-wrap items-center gap-4"><span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Centro organizacional activo</span><span class="text-[#A8BAD2]">•</span><span>Sincronización en tiempo real</span><span class="text-[#A8BAD2]">•</span><span>Última validación RBAC: {{ now()->format('H:i') }}</span></div>
            <span class="font-semibold text-[#102A52]">Protocolo de seguridad: RBAC</span>
        </div>
        @endif
    </section>

    {{-- La información jerárquica solamente se entrega a administradores. --}}
    @if ($canManageOrganization)

    @unless ($orgChartPage)
    {{-- wire:ignore: en las actualizaciones el directorio no se envía y la caja conserva su contenido; al cambiar la versión se reemplaza. --}}
    <div wire:ignore wire:key="administration-organization-directory-slot-{{ $directoryStampKey }}">
        @if ($includeDirectory)
            @include('livewire.administracion.partials.organization-directory')
        @endif
    </div>
    @endunless

    @if ($orgChartPage)
    {{-- Organigrama a pantalla completa, como el visor de Blender: el árbol ocupa todo el espacio (se mueve y se acerca) y
         la información va en paneles flotantes encima, con el diseño de Inicio (bordes de 18px, texto de 15px, 20px entre todo). --}}
    {{-- wire:poll: cada 20 s se compara el sello del organigrama; si otra persona lo cambió se vuelve a dibujar solo (si no, no se envía nada) --}}
    {{-- wire:ignore.self en el contenedor y en el árbol: sus clases y su posición las maneja el script (acercar, mover, animar)
         y Livewire no debe reiniciarlas al volver a dibujar; lo de adentro sí se actualiza. --}}
    <div id="organigrama-principal" data-area="{{ $selectedPhysicalAreaId }}" @unless ($orgChartLoaded) wire:init="loadOrgChartData" @endunless wire:poll.20s.visible="syncOrgChart">
        <div id="org-tree-container" wire:ignore.self class="org-viewport relative overflow-hidden" style="touch-action: none;">

                    <!-- CONTENIDO PRINCIPAL: árbol o mensaje sin datos (profesional) -->
                    @if (! $orgChartLoaded)
                        <div class="org-tree-empty">
                            <span>Cargando organigrama…</span>
                        </div>
                    @elseif (count($orgChartTree) > 0)
                        <div id="org-tree-wrapper" wire:ignore.self class="relative origin-top-left" style="transform: scale(1) translate(0px, 0px); cursor: grab; width: max-content; min-width: 1000px; padding: 20px;">
                            {{-- Líneas entre jefes y equipos: las dibuja el script según dónde está cada tarjeta, Livewire no las toca --}}
                            <svg class="org-lines" wire:ignore aria-hidden="true" focusable="false"></svg>
                            <div class="flex flex-col items-center gap-6" style="min-width: max-content;">
                                @foreach ($orgChartTree as $rootNode)
                                    <x-administracion.organigrama-node :node="$rootNode" :depth="0" />
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="org-tree-empty">
                            <span>No se encontraron nodos para el filtro seleccionado.</span>
                        </div>
                    @endif


            {{-- Barra de herramientas (arriba a la derecha): buscar, filtrar por área, información, pantalla completa y sin asignar --}}
            <div data-org-panel class="org-toolbar absolute right-[20px] top-[20px] flex items-start gap-[20px]" style="z-index: 40;">
                <!-- Buscador -->
                        <div style="position: relative; flex: 1;">
                            <input 
                                id="node-search-input"
                                type="text" 
                                placeholder="Buscar colaborador..."
                                class="org-field"
                                style="min-width: 240px; width: 100%; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30;"
                                autocomplete="off"
                            >
                            <div x-data="backupOverlayScroll()" class="org-drop" style="position: absolute; top: calc(100% + 20px); right: 0; width: 400px; max-width: calc(100vw - 80px); z-index: 100;">
                                <div
                                    id="search-results" x-ref="scroller" @scroll="update()" @mouseenter="update()" data-scroll-top-offset="18" data-scroll-bottom-offset="18"
                                    class="administration-form-scrollbar org-panel !p-0"
                                    style="max-height: 300px; overflow-y: auto; overflow-x: hidden; display: none;"
                                ></div>
                                <span x-show="visible" x-cloak class="administration-form-thumb org-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>
                        </div>

                        <!-- Filtro por área con búsqueda -->
                        <div class="relative w-[200px]" x-data="{
                                open: false, query: '', selected: @js((string) ($selectedPhysicalAreaId ?? '')),
                                areas: @js($physicalAreas->map(fn ($area) => ['id' => (string) $area->id, 'name' => $area->name])->values()),
                                get filteredAreas() { const term = this.query.toLocaleLowerCase().trim(); return term ? this.areas.filter(area => area.name.toLocaleLowerCase().includes(term)) : this.areas; },
                                choose(id) { this.selected = id; this.query = ''; this.open = false; $wire.set('selectedPhysicalAreaId', id); }
                            }" @click.away="open = false; query = ''" @keydown.escape.window="open = false; query = ''">
                            <input id="physical-area-filter" x-ref="areaInput" type="text" x-model="query" @focus="open = true" @input="open = true"
                                :placeholder="selected && !open ? (areas.find(area => area.id === selected)?.name || 'Todas las áreas') : 'Todas las áreas'"
                                class="org-field w-full pr-[48px]"
                                autocomplete="off" aria-label="Buscar o filtrar por área">
                            <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.areaInput.focus())"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center focus:outline-none" tabindex="-1" aria-label="Mostrar áreas">
                                <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                            </button>
                            <div x-cloak x-show="open" class="org-panel org-drop absolute left-0 right-0 top-[calc(100%+20px)] overflow-hidden !p-0" style="z-index: 100;"
                                x-data="backupOverlayScroll()" x-effect="open; $nextTick(() => update())">
                                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" id="physical-area-results" data-scroll-top-offset="18" data-scroll-bottom-offset="18" class="administration-form-scrollbar max-h-[300px] overflow-y-auto overflow-x-hidden">
                                <button type="button" @mousedown.prevent="choose('')" :aria-selected="selected === ''" class="org-option">Todas las áreas</button>
                                <template x-for="area in filteredAreas" :key="area.id"><button type="button" @mousedown.prevent="choose(area.id)" :aria-selected="selected === area.id" x-text="area.name" class="org-option"></button></template>
                                <div x-show="filteredAreas.length === 0" class="org-option org-muted">No se encontraron áreas</div>
                                </div>
                                <span x-show="visible" x-cloak class="administration-form-thumb org-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                            </div>
                        </div>

                {{-- Información: al presionarlo se despliegan la ayuda para moverse y la leyenda de colores --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button" class="org-tool-btn" @click="open = ! open" :aria-expanded="open" title="Información" aria-label="Información del organigrama">
                        <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    </button>
                    <div x-cloak x-show="open" class="org-panel org-info org-drop absolute right-0 top-[calc(100%+20px)]" style="z-index: 100;">
                        <h1 class="org-card-title">Organigrama</h1>
                        <p class="org-card-text">Estructura jerárquica: líneas de mando y niveles de supervisión.</p>
                        <p class="org-card-text">{{ $totalUsers ?? 0 }} usuarios · {{ $orgChartStats['in_tree'] ?? 0 }} en el árbol · {{ $orgChartStats['relations'] ?? 0 }} relaciones · {{ $totalRoles ?? 0 }} roles</p>
                        @if (($orgChartStats['cycles_detected'] ?? 0) > 0)
                            <p class="org-card-text !text-red-600">Se detectaron {{ $orgChartStats['cycles_detected'] }} ciclo(s) en las relaciones; se omiten las ramas circulares.</p>
                        @endif
                        <h2 class="org-card-title org-card-gap">Cómo moverte</h2>
                        <p class="org-card-text">Arrastra para mover el organigrama.</p>
                        <p class="org-card-text">Usa la rueda del ratón para acercar o alejar.</p>
                        <p class="org-card-text">Haz doble clic para volver a centrarlo.</p>
                    </div>
                </div>
                        <!-- BOTÓN DE PANTALLA COMPLETA (cuadrado, sin borde azul) -->
                        <button id="fullscreen-toggle" type="button"
                            aria-label="Alternar pantalla completa">
                            <!-- Icono expandir (visible por defecto) -->
                            <svg id="fullscreen-icon-expand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" />
                            </svg>
                            <!-- Icono comprimir (oculto por defecto) - inverso exacto del expandir -->
                            <svg id="fullscreen-icon-compress" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 9h5V4M20 9h-5V4M4 15h5v5M20 15h-5v5" />
                            </svg>
                        </button>
                <button type="button" class="org-tool-btn" @click="$dispatch('org-toggle-unassigned')" title="Usuarios sin asignar" aria-label="Mostrar u ocultar usuarios sin asignar">
                    <svg class="h-[22px] w-[22px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    <span class="org-tool-badge">{{ count($unassignedUsers) }}</span>
                </button>
            </div>

            {{-- Panel lateral (a la derecha, plegable): usuarios sin asignar --}}
            {{-- Al arrastrar a alguien del árbol el panel se abre solo (si estaba cerrado) para poder soltarlo aquí --}}
            <aside data-org-panel class="org-panel org-side absolute right-[20px] flex flex-col !p-0" aria-label="Usuarios sin asignar"
                x-data="{
                    open: (() => { try { const saved = localStorage.getItem('organigrama-side'); return saved === null ? window.innerWidth >= 1024 : saved !== '0'; } catch (e) { return window.innerWidth >= 1024; } })(),
                    openedForDrag: false,
                    q: '',
                    matches(haystack) { const term = this.q.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); return ! term || haystack.includes(term); },
                }"
                x-show="open"
                @org-toggle-unassigned.window="open = ! open; openedForDrag = false; try { localStorage.setItem('organigrama-side', open ? '1' : '0'); } catch (e) {}"
                @org-drag-start.window="if ($event.detail?.source === 'tree' && ! open) { open = true; openedForDrag = true; }"
                @org-drag-end.window="if (openedForDrag) { open = false; openedForDrag = false; }">
                {{-- Encabezado como las secciones de Inicio: título, texto gris y etiqueta con el total; debajo, el buscador --}}
                <header class="org-side-head shrink-0">
                    <div class="org-side-head-top">
                        <div class="min-w-0">
                            <h2 class="org-title">Sin asignar</h2>
                            <p class="org-muted org-gap">Arrástralas al organigrama.</p>
                        </div>
                        <span class="org-chip">{{ count($unassignedUsers) }} {{ count($unassignedUsers) === 1 ? 'persona' : 'personas' }}</span>
                    </div>
                    @if ($orgChartLoaded && count($unassignedUsers) > 3)
                        <input type="search" x-model="q" class="org-field org-side-search" placeholder="Buscar por nombre o correo" aria-label="Buscar en sin asignar" autocomplete="off">
                    @endif
                </header>
                {{-- Lugar para soltar a alguien del árbol (solo se ve mientras se arrastra desde el árbol) --}}
                <p class="org-side-drop shrink-0">Suelta aquí para sacarla del organigrama</p>
                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" data-scroll-top-offset="18" data-scroll-bottom-offset="18" class="administration-form-scrollbar h-full overflow-y-auto">
                <div>
                    @if (! $orgChartLoaded)
                        <p class="org-side-empty">Cargando usuarios…</p>
                    @elseif (count($unassignedUsers) > 0)
                        @php
                            $unassignedHaystacks = array_map(static fn (array $person): string => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($person['name'].' '.mb_strtolower((string) $person['email']))), $unassignedUsers);
                        @endphp
                        <ul class="org-list" style="display: flex; flex-direction: column; margin: 0; padding: 0; list-style: none;"
                            x-data="{
                                hidden: (() => { try { return JSON.parse(localStorage.getItem('organigrama-unassigned-hidden') || '[]'); } catch (error) { return []; } })(),
                                isHidden(id) { return this.hidden.includes(id); },
                                toggle(id) {
                                    this.hidden = this.isHidden(id) ? this.hidden.filter((item) => item !== id) : [...this.hidden, id];
                                    try { localStorage.setItem('organigrama-unassigned-hidden', JSON.stringify(this.hidden)); } catch (error) {}
                                },
                            }">
                            @foreach ($unassignedUsers as $user)
                                @php
                                    $nameParts = array_values(array_filter(preg_split('/\s+/', trim((string) $user['name']))));
                                    $initials = implode('', array_map(
                                        static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)),
                                        array_slice($nameParts, 0, 2)
                                    ));
                                    $initials = $initials !== '' ? $initials : '?';
                                    $email = mb_strtolower((string) $user['email']);
                                    $createdAt = ! empty($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->locale('es') : null;
                                    // Lo que le falta para quedar en el organigrama, en una frase corta
                                    $missingLabels = array_values(array_filter(array_map(
                                        static fn (string $key): ?string => ['superior' => 'jefe', 'job_position' => 'puesto', 'physical_area' => 'área'][$key] ?? null,
                                        (array) ($user['missing'] ?? ['superior'])
                                    )));
                                    $missingText = count($missingLabels) > 1
                                        ? 'Falta '.implode(', ', array_slice($missingLabels, 0, -1)).' y '.end($missingLabels)
                                        : 'Falta '.($missingLabels[0] ?? 'jefe');
                                    $haystack = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($user['name'].' '.$email));
                                    $userId = (int) $user['id'];
                                @endphp
                                <li wire:key="unassigned-user-{{ $userId }}" style="list-style: none;" x-show="matches(@js($haystack))" :style="{ order: isHidden({{ $userId }}) ? 1 : 0 }">
                                    <div class="org-un-row" :class="isHidden({{ $userId }}) && 'is-hidden'" data-unassigned-id="{{ $userId }}" data-name="{{ $user['name'] }}" data-initials="{{ $initials }}"
                                        title="{{ $user['name'] }} · {{ $createdAt ? 'en el sistema desde hace '.$createdAt->diffForHumans(now(), true) : 'fecha de alta no disponible' }}">
                                        <span class="org-avatar">{{ $initials }}</span>
                                        <span class="org-un-text">
                                            <span class="org-un-name">{{ $user['name'] }}</span>
                                            <span class="org-un-meta">{{ $email }}</span>
                                            <span class="org-un-meta">{{ $missingText }}</span>
                                        </span>
                                        <span class="org-un-actions">
                                            <button type="button" wire:click="openUserManagement('editar', {{ $userId }})" class="focus:outline-none focus:ring-0" aria-label="Editar {{ $user['name'] }}" title="Editar">
                                                <svg class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                                            </button>
                                            <button type="button" @click="toggle({{ $userId }})" class="focus:outline-none focus:ring-0" :aria-label="isHidden({{ $userId }}) ? 'Mostrar {{ $user['name'] }}' : 'Ocultar {{ $user['name'] }}'" :title="isHidden({{ $userId }}) ? 'Mostrar' : 'Mandar al final de la lista'">
                                                <svg x-show="!isHidden({{ $userId }})" class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                                <svg x-show="isHidden({{ $userId }})" x-cloak class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </button>
                                            <button type="button" wire:click="openUserManagement('eliminar', {{ $userId }})" class="is-danger focus:outline-none focus:ring-0" aria-label="Eliminar {{ $user['name'] }}" title="Eliminar">
                                                <svg class="h-[16px] w-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                                            </button>
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <p class="org-side-empty" x-cloak x-show="q && ! @js($unassignedHaystacks).some((haystack) => matches(haystack))">No hay nadie con ese nombre o correo.</p>
                    @else
                        <p class="org-side-empty">Todas las personas tienen jefe, puesto y área.</p>
                    @endif
                </div>
                </div>
                <span x-show="visible" x-cloak class="administration-form-thumb org-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>
            </aside>

        </div>
    </div>
    @endif
    </main>
    </div>

    @if ($showUserManagementModal)
        <livewire:administracion.users.form
            :embedded="true"
            :initial-tab="$userManagementInitialTab"
            :initial-user-id="$userManagementInitialUserId"
            :key="'organization-user-management-'.$userManagementInitialTab.'-'.($userManagementInitialUserId ?? 'list')"
        />
    @endif

    @if ($showRoleManagementModal)
        @if ($roleManagementInitialTab === 'crear')
            <livewire:administracion.roles.form
                :embedded="true"
                :key="'organization-role-create'"
            />
        @else
            <livewire:administracion.roles.gestion-roles
                :embedded="true"
                :initial-tab="$roleManagementInitialTab"
                :initial-role-id="$roleManagementInitialRoleId"
                :key="'organization-role-management-'.$roleManagementInitialTab.'-'.($roleManagementInitialRoleId ?? 'list')"
            />
        @endif
    @endif

    @if ($showAssignmentModal)
        <livewire:administracion.relationship.gestion-relaciones-jerarquicas
            :auto-open="true"
            :mode="$assignmentModalTab"
            :initial-customer-id="$assignmentModalCustomerId"
            :key="'organization-relationships-form-'.$assignmentModalInstance"
        />
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL PARA AGREGAR PUESTO DE TRABAJO                        --}}
    {{-- ============================================================ --}}
    @if ($showJobPositionModal)
        @php
            $jobPositionDisabled = ! filled($selectedJobPositionId);
            $jobPositionModalTitle = match ($jobPositionModalTab) {
                'editar' => 'Editar puesto operativo',
                'eliminar' => 'Eliminar puesto operativo',
                default => 'Crear puesto operativo',
            };
        @endphp
        <x-administration-form-modal
            wire:key="job-position-management-modal-{{ $jobPositionModalTab }}"
            submit="saveJobPosition"
            cancel-action="closeJobPositionModal"
            modal-id="job-position-form"
            :title="$jobPositionModalTitle"
            subtitle="Administra las posiciones organizacionales."
            :carousel-style="true"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7h-4V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2H4a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM10 7V5h4v2m-2 4v4" />
                </svg>
            </x-slot>

            <x-slot name="form">
                @if ($jobPositionModalTab === 'crear')
                    <section class="rounded-xl border border-[#CAD7E7] p-5">
                        <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Datos del puesto</h2>
                        <label for="new-job-position-name" class="block text-[15px] font-medium text-gray-700">Nombre del puesto</label>
                        <input id="new-job-position-name" type="text" maxlength="255" autocomplete="off" wire:model.defer="newJobPositionName" class="mt-[10px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] leading-none text-[15px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0" placeholder="Ej. Contador Senior" autofocus>
                        <x-input-error for="newJobPositionName" class="mt-2 text-[15px]" />
                        <fieldset class="mt-[15px]">
                            <legend class="block text-[15px] font-medium text-gray-700">Tipo de pago</legend>
                            <div class="mt-[10px] grid grid-cols-1 gap-[10px] sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center gap-[10px] rounded-lg border border-[#B7CEEA] bg-transparent p-3 text-[#102A52]">
                                    <input type="radio" value="full_time" wire:model="newJobPositionPaymentType" class="h-4 w-4 border-[#B7CEEA] text-[#1A3A6B] focus:ring-0"> Tiempo completo
                                </label>
                                <label class="flex cursor-pointer items-center gap-[10px] rounded-lg border border-[#B7CEEA] bg-transparent p-3 text-[#102A52]">
                                    <input type="radio" value="hourly" wire:model="newJobPositionPaymentType" class="h-4 w-4 border-[#B7CEEA] text-[#1A3A6B] focus:ring-0"> Pago por hora
                                </label>
                            </div>
                            <x-input-error for="newJobPositionPaymentType" class="mt-2 text-[15px]" />
                        </fieldset>
                    </section>
                @elseif ($jobPositionModalTab === 'editar')
                    <div class="flex flex-col gap-5">
                        <section class="rounded-xl border border-[#CAD7E7] p-5">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Seleccionar puesto</h2>
                            <label for="edit-job-position-id" class="block text-[15px] font-medium text-gray-700">Puesto operativo</label>
                            <x-administration-search-picker
                                input-id="edit-job-position-id"
                                :disabled="$lockedJobPositionId !== null"
                                model="selectedJobPositionId"
                                :selected="$selectedJobPositionId"
                                :items="$jobPositions->map(fn ($position) => ['id' => $position->id, 'label' => $position->name, 'meta' => $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo'])"
                                placeholder="Buscar puesto..."
                                empty-message="No se encontraron puestos."
                            />
                            <x-input-error for="selectedJobPositionId" class="mt-2 text-[15px]" />
                        </section>

                        <section class="rounded-xl border p-5 transition-colors {{ $jobPositionDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] {{ $jobPositionDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Datos del puesto</h2>
                            <div>
                                <label for="edit-job-position-name" class="block text-[15px] font-medium text-gray-700">Nuevo nombre</label>
                                <input id="edit-job-position-name" type="text" maxlength="255" wire:model.defer="editJobPositionName" @disabled($jobPositionDisabled) class="mt-[10px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] leading-none text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
                                <x-input-error for="editJobPositionName" class="mt-2 text-[15px]" />
                            </div>
                            <fieldset class="mt-[15px]" @disabled($jobPositionDisabled)>
                                <legend class="block text-[15px] font-medium text-gray-700">Tipo de pago</legend>
                                <div class="mt-[10px] grid grid-cols-1 gap-[10px] sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-center gap-[10px] rounded-lg border border-[#B7CEEA] bg-transparent p-3 text-[15px] text-[#102A52]">
                                        <input type="radio" value="full_time" wire:model="editJobPositionPaymentType" class="h-4 w-4 border-[#B7CEEA] text-[#1A3A6B] focus:ring-0"> Tiempo completo
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-[10px] rounded-lg border border-[#B7CEEA] bg-transparent p-3 text-[15px] text-[#102A52]">
                                        <input type="radio" value="hourly" wire:model="editJobPositionPaymentType" class="h-4 w-4 border-[#B7CEEA] text-[#1A3A6B] focus:ring-0"> Pago por hora
                                    </label>
                                </div>
                                <x-input-error for="editJobPositionPaymentType" class="mt-2 text-[15px]" />
                            </fieldset>
                        </section>
                    </div>
                @else
                    <div class="flex flex-col gap-5">
                        <section class="rounded-xl border border-[#CAD7E7] p-5">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Seleccionar puesto</h2>
                            <label for="delete-job-position-id" class="block text-[15px] font-medium text-gray-700">Puesto operativo</label>
                            <x-administration-search-picker
                                input-id="delete-job-position-id"
                                :disabled="$lockedJobPositionId !== null"
                                model="selectedJobPositionId"
                                :selected="$selectedJobPositionId"
                                :items="$jobPositions->map(fn ($position) => ['id' => $position->id, 'label' => $position->name, 'meta' => $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo'])"
                                placeholder="Buscar puesto..."
                                empty-message="No se encontraron puestos."
                            />
                            <x-input-error for="selectedJobPositionId" class="mt-2 text-[15px]" />
                        </section>

                        <section class="rounded-xl border p-5 transition-colors {{ $jobPositionDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] {{ $jobPositionDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Confirmar eliminación</h2>
                            <label for="delete-job-position-confirmation" class="block text-[15px] font-medium text-gray-700">Escribe manualmente el nombre exacto</label>
                            <input id="delete-job-position-confirmation" type="text" wire:model.defer="deleteJobPositionConfirmation" @disabled($jobPositionDisabled) autocomplete="off" placeholder="{{ $jobPositionDisabled ? 'Selecciona primero un puesto' : 'Nombre exacto del puesto' }}" class="mt-[10px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] leading-none text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
                            <x-input-error for="deleteJobPositionConfirmation" class="mt-2 text-[15px]" />
                        </section>
                    </div>
                @endif
            </x-slot>

            <x-slot name="actions">
                @if ($jobPositionModalTab === 'crear')
                <button type="button" wire:click="closeJobPositionModal"
                    class="admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveJobPosition"
                    class="admin-btn admin-btn-primary inline-flex min-w-28 items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                    <svg wire:loading wire:target="saveJobPosition" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span wire:loading.remove wire:target="saveJobPosition">Guardar</span>
                    <span wire:loading wire:target="saveJobPosition">Guardando...</span>
                </button>
                @else
                    <button type="button" wire:click="closeJobPositionModal"
                        class="admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0">
                        Cancelar
                    </button>
                    @if ($jobPositionModalTab === 'editar')
                        <button type="button" wire:click="updateJobPosition" wire:loading.attr="disabled" wire:target="updateJobPosition" @disabled($jobPositionDisabled)
                            class="admin-btn admin-btn-primary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                            Actualizar
                        </button>
                    @else
                        <button type="button" wire:click="deleteJobPosition" wire:loading.attr="disabled" wire:target="deleteJobPosition" @disabled($jobPositionDisabled)
                            class="inline-flex min-w-28 items-center justify-center rounded-xl border border-red-600 bg-red-600/10 px-[20px] py-[15px] text-[15px] text-red-600 transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                            Eliminar
                        </button>
                    @endif
                @endif
            </x-slot>
        </x-administration-form-modal>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL PARA AGREGAR ÁREA / DEPARTAMENTO                      --}}
    {{-- ============================================================ --}}
    @if ($showPhysicalAreaModal)
        @php
            $physicalAreaDisabled = ! filled($selectedPhysicalAreaManagementId);
            $physicalAreaModalTitle = match ($physicalAreaModalTab) {
                'editar' => 'Editar área o departamento',
                'eliminar' => 'Eliminar área o departamento',
                default => 'Crear área o departamento',
            };
        @endphp
        <x-administration-form-modal
            wire:key="physical-area-management-modal-{{ $physicalAreaModalTab }}"
            submit="savePhysicalArea"
            cancel-action="closePhysicalAreaModal"
            modal-id="physical-area-form"
            :title="$physicalAreaModalTitle"
            subtitle="Administra las unidades organizacionales."
            :carousel-style="true"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V5a2 2 0 012-2h6a2 2 0 012 2v16m4 0V9a2 2 0 00-2-2h-2M8 7h4m-4 4h4m-4 4h4" />
                </svg>
            </x-slot>

            <x-slot name="form">
                @if ($physicalAreaModalTab === 'crear')
                    <section class="rounded-xl border border-[#CAD7E7] p-5">
                        <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Datos del área</h2>
                        <label for="new-physical-area-name" class="block text-[15px] font-medium text-gray-700">Nombre del área o departamento</label>
                        <input id="new-physical-area-name" type="text" maxlength="255" autocomplete="off" wire:model.defer="newPhysicalAreaName" class="mt-[10px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] leading-none text-[15px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0" placeholder="Ej. Auditoría" autofocus>
                        <x-input-error for="newPhysicalAreaName" class="mt-2 text-[15px]" />
                    </section>
                @elseif ($physicalAreaModalTab === 'editar')
                    <div class="flex flex-col gap-5">
                        <section class="rounded-xl border border-[#CAD7E7] p-5">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Seleccionar área</h2>
                            <label for="edit-physical-area-id" class="block text-[15px] font-medium text-gray-700">Área o departamento</label>
                            <x-administration-search-picker
                                input-id="edit-physical-area-id"
                                :disabled="$lockedPhysicalAreaId !== null"
                                model="selectedPhysicalAreaManagementId"
                                :selected="$selectedPhysicalAreaManagementId"
                                :items="$physicalAreas->map(fn ($area) => ['id' => $area->id, 'label' => $area->name])"
                                placeholder="Buscar área o departamento..."
                                empty-message="No se encontraron áreas."
                            />
                            <x-input-error for="selectedPhysicalAreaManagementId" class="mt-2 text-[15px]" />
                        </section>
                        <section class="rounded-xl border p-5 transition-colors {{ $physicalAreaDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] {{ $physicalAreaDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Datos del área</h2>
                            <label for="edit-physical-area-name" class="block text-[15px] font-medium text-gray-700">Nuevo nombre</label>
                            <input id="edit-physical-area-name" type="text" maxlength="255" wire:model.defer="editPhysicalAreaName" @disabled($physicalAreaDisabled) class="mt-[10px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] leading-none text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
                            <x-input-error for="editPhysicalAreaName" class="mt-2 text-[15px]" />
                        </section>
                    </div>
                @else
                    <div class="flex flex-col gap-5">
                        <section class="rounded-xl border border-[#CAD7E7] p-5">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Seleccionar área</h2>
                            <label for="delete-physical-area-id" class="block text-[15px] font-medium text-gray-700">Área o departamento</label>
                            <x-administration-search-picker
                                input-id="delete-physical-area-id"
                                :disabled="$lockedPhysicalAreaId !== null"
                                model="selectedPhysicalAreaManagementId"
                                :selected="$selectedPhysicalAreaManagementId"
                                :items="$physicalAreas->map(fn ($area) => ['id' => $area->id, 'label' => $area->name])"
                                placeholder="Buscar área o departamento..."
                                empty-message="No se encontraron áreas."
                            />
                            <x-input-error for="selectedPhysicalAreaManagementId" class="mt-2 text-[15px]" />
                        </section>
                        <section class="rounded-xl border p-5 transition-colors {{ $physicalAreaDisabled ? 'border-[#D5DDE8] bg-[#F1F3F6] opacity-75' : 'border-[#CAD7E7] bg-transparent' }}">
                            <h2 class="mb-[10px] text-[13px] font-bold uppercase leading-5 tracking-[.12em] {{ $physicalAreaDisabled ? 'text-[#8290A3]' : 'text-[#1A3A6B]' }}">Confirmar eliminación</h2>
                            <label for="delete-physical-area-confirmation" class="block text-[15px] font-medium text-gray-700">Escribe manualmente el nombre exacto</label>
                            <input id="delete-physical-area-confirmation" type="text" wire:model.defer="deletePhysicalAreaConfirmation" @disabled($physicalAreaDisabled) autocomplete="off" placeholder="{{ $physicalAreaDisabled ? 'Selecciona primero un área' : 'Nombre exacto del área' }}" class="mt-[10px] block w-full rounded-lg border border-[#B7CEEA] bg-transparent px-[20px] py-[15px] leading-none text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
                            <x-input-error for="deletePhysicalAreaConfirmation" class="mt-2 text-[15px]" />
                        </section>
                    </div>
                @endif
            </x-slot>

            <x-slot name="actions">
                @if ($physicalAreaModalTab === 'crear')
                <button type="button" wire:click="closePhysicalAreaModal"
                    class="admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="savePhysicalArea"
                    class="admin-btn admin-btn-primary inline-flex min-w-28 items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                    <svg wire:loading wire:target="savePhysicalArea" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span wire:loading.remove wire:target="savePhysicalArea">Guardar</span>
                    <span wire:loading wire:target="savePhysicalArea">Guardando...</span>
                </button>
                @else
                    <button type="button" wire:click="closePhysicalAreaModal"
                        class="admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0">
                        Cancelar
                    </button>
                    @if ($physicalAreaModalTab === 'editar')
                        <button type="button" wire:click="updatePhysicalArea" wire:loading.attr="disabled" wire:target="updatePhysicalArea" @disabled($physicalAreaDisabled)
                            class="admin-btn admin-btn-primary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                            Actualizar
                        </button>
                    @else
                        <button type="button" wire:click="deletePhysicalArea" wire:loading.attr="disabled" wire:target="deletePhysicalArea" @disabled($physicalAreaDisabled)
                            class="inline-flex min-w-28 items-center justify-center rounded-xl border border-red-600 bg-red-600/10 px-[20px] py-[15px] text-[15px] text-red-600 transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                            Eliminar
                        </button>
                    @endif
                @endif
            </x-slot>
        </x-administration-form-modal>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL INFORMATIVO DE PERMISOS VIGENTES --}}
    {{-- ============================================================ --}}
    @if ($showPermissionsModal)
        <livewire:administracion.permissions.catalog-manager :auto-open="true" :initial-tab="$permissionCatalogInitialTab" :initial-record-id="$permissionCatalogInitialRecordId" :key="'permission-catalog-direct-'.$permissionCatalogModalInstance" />
        <span class="hidden" data-administration-modal="permissions" wire:click.self="closePermissionsModal" aria-hidden="true"></span>
        <span class="hidden" data-permission-role="Administrador" aria-hidden="true"></span>
        <span class="hidden" data-permission-role="Auxiliar" aria-hidden="true"></span>
    @endif

    @if ($showCustomerManagementModal)
        <livewire:customer.catalog-manager :auto-open="true" :initial-tab="$customerManagementInitialTab" :initial-record-id="$customerManagementInitialRecordId" :key="'customer-catalog-direct-'.$customerManagementModalInstance" />
    @endif

    @if ($showActivityManagementModal)
        <livewire:time-control.activity-catalog-manager :auto-open="true" :initial-tab="$activityManagementInitialTab" :initial-record-id="$activityManagementInitialRecordId" :key="'activity-catalog-direct-'.$activityManagementModalInstance" />
    @endif

    @if (false && $showPermissionsModal)
        @php
            $permissionProfiles = $basePermissionProfiles;
        @endphp

        <div
            x-data="{ visible: true }"
            x-show="visible"
            wire:click.self="closePermissionsModal"
            @click.self="visible = false"
            @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === 'closePermissionsModal') visible = false"
            @keydown.escape.window="visible = false; $wire.closePermissionsModal()"
            class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="permissions-modal-title"
            data-administration-modal="permissions"
        >
            <div
                class="administration-form-panel relative flex w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl"
                style="height: min(86vh, 760px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;"
            >
                <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <div class="admin-title-icon flex shrink-0 items-center justify-center">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex min-w-0 flex-col gap-[15px]">
                            <h1 id="permissions-modal-title" class="truncate text-[15px] leading-none text-black" style="margin: 0;">
                                Gestionar permisos
                            </h1>
                            <p class="truncate text-[15px] leading-none text-black" style="margin: 0;">
                                Accesos vigentes de Administrador y Auxiliar
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closePermissionsModal"
                        class="flex h-10 w-10 shrink-0 items-center justify-center text-xl leading-none text-zinc-500 transition hover:text-black focus:outline-none focus:ring-0"
                        aria-label="Cerrar"
                    >
                        &times;
                    </button>
                </header>

                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                    <div class="flex flex-col gap-[20px] p-[20px] text-[15px]">
                        <div class="rounded-xl border border-zinc-200 bg-transparent p-[20px]">
                            <div class="flex items-start gap-[15px]">
                                <svg class="admin-title-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="min-w-0">
                                    <p class="leading-none">Configuración de acceso actual</p>
                                    <p class="mt-[7.5px] -mb-[7.5px] text-[15px] leading-[30px]">
                                        Esta vista refleja las reglas vigentes del sistema. La autorización continúa protegida por sus políticas y middleware actuales.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-[20px] md:grid-cols-2">
                            @foreach ($permissionProfiles as $roleName => $profile)
                                @if ($roles->contains('role', $roleName))
                                    <article class="flex min-w-0 flex-col rounded-xl border border-zinc-200 bg-transparent p-[20px]" data-permission-role="{{ $roleName }}">
                                        <div class="min-w-0 border-b border-zinc-200 pb-[20px]">
                                            <h3 class="admin-section-title truncate text-[15px] leading-none" title="{{ $roleName }}">{{ $roleName }}</h3>
                                            <span class="mt-[15px] inline-flex max-w-full text-[15px] leading-none">
                                                {{ $profile['label'] }}
                                            </span>
                                            <p class="mt-[7.5px] -mb-[7.5px] text-[15px] leading-[30px]">{{ $profile['description'] }}</p>
                                        </div>

                                        <ul class="mt-[20px] flex flex-col gap-[15px]" aria-label="Permisos de {{ $roleName }}">
                                            @foreach ($profile['permissions'] as $permission)
                                                <li class="flex min-w-0 items-center gap-[10px] rounded-xl border border-zinc-200 bg-transparent px-[20px] py-[15px]">
                                                    <span class="admin-title-icon flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-current">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 truncate text-[15px] leading-none" title="{{ $permission }}">{{ $permission }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @php($permissionRole = $roles->firstWhere('role', $roleName))
                                        <button type="button" wire:click="openPermissionRoleEditor({{ $permissionRole->id }})" class="admin-btn admin-btn-primary mt-[20px] inline-flex items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] text-[15px] leading-none transition hover:opacity-90 focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                                            Editar {{ $roleName }}
                                        </button>
                                    </article>
                                @endif
                            @endforeach
                        </div>

                        <section class="rounded-xl border border-zinc-200 bg-transparent p-[20px]">
                            <h3 class="text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Grupos de permisos</h3>
                            <div class="mt-[20px] grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                <button type="button" wire:click="openRoleManagement('crear')" class="admin-btn admin-btn-secondary inline-flex items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0">
                                    <span class="text-lg">+</span> Agregar grupo
                                </button>
                                <button type="button" wire:click="openRoleManagement('eliminar')" class="admin-btn admin-btn-secondary inline-flex items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0">
                                    Administrar o eliminar grupos
                                </button>
                            </div>
                        </section>
                    </div>
                </div>
                <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                <footer class="flex flex-shrink-0 justify-end gap-[20px] border-t border-zinc-200 bg-white p-[20px]">
                    <button
                        type="button"
                        wire:click="closePermissionsModal"
                        class="admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] transition hover:opacity-90 focus:outline-none focus:ring-0"
                    >
                        Cerrar
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
{{-- ============================================================ --}}
{{-- MODAL DE USUARIO --}}
{{-- ============================================================ --}}
@if ($selectedUserDetails)
    {{-- Variables de estilo del formulario (en línea, para no mezclarse con otros bloques de PHP del panel). --}}
    @php($detailName = $selectedUserDetails['name'] ?? 'Usuario')
    @php($detailInput = 'mt-[20px] block w-full rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] leading-none text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:border-zinc-200 disabled:bg-zinc-100 disabled:text-zinc-400')
    @php($detailLabel = 'block text-[15px] font-medium text-gray-700')
    @php($detailValue = 'mt-[20px] flex w-full cursor-default items-center break-words rounded-xl border border-zinc-200 bg-white px-[20px] py-[7.5px] text-[15px] leading-[30px] text-black')
    @php($detailSecondaryButton = 'admin-btn admin-btn-secondary inline-flex min-w-28 items-center justify-center rounded-xl px-[20px] py-[15px] text-[15px] leading-none transition hover:opacity-90 focus:outline-none focus:ring-0')
    @php($detailPrimaryButton = 'admin-btn admin-btn-primary inline-flex min-w-28 items-center justify-center gap-[10px] rounded-xl px-[20px] py-[15px] text-[15px] leading-none transition hover:opacity-90 focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60')
    <div x-data="{ visible: true }" x-show="visible" @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === 'closeUserDetails') visible = false" @keydown.escape.window="visible = false; $wire.closeUserDetails()" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="user-details-title" data-administration-modal="user-details">
        {{-- Mismo marco que el modal de Usuarios: blanco, bordes grises, 20px de relleno. --}}
        <div @click.outside="visible = false; $wire.closeUserDetails()" class="org-user-modal administration-form-panel relative flex w-full flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl" style="max-width: 620px; height: min(620px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;">

            {{-- ===== ENCABEZADO (fijo) ===== --}}
            <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="admin-title-icon flex shrink-0 items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0" />
                        </svg>
                    </div>
                    <div class="flex min-w-0 flex-col gap-[15px]">
                        <h1 id="user-details-title" class="truncate pb-[3px] text-[15px] font-semibold leading-none text-black" title="{{ $detailName }}" style="margin: 0;">{{ $detailName }}</h1>
                        <p class="truncate pb-[3px] text-[15px] leading-none text-zinc-500" style="margin: 0;">{{ $isEditingUser ? 'Actualiza los datos de acceso y el perfil organizacional.' : 'Detalles del usuario' }}</p>
                    </div>
                </div>
                <button type="button" wire:click="closeUserDetails" class="flex h-10 w-10 shrink-0 items-center justify-center text-xl leading-none text-zinc-500 transition hover:text-black focus:outline-none focus:ring-0" aria-label="Cerrar">&times;</button>
            </header>

            @if ($isEditingUser)
                {{-- ===== FORMULARIO DE EDICIÓN ===== --}}
                <form wire:submit="saveSelectedUser" class="flex min-h-0 flex-1 flex-col">
                    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                            <div class="p-[20px] text-[15px]">
                                <div class="flex flex-col gap-[20px]">
                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Datos personales</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0">
                                                <label for="edit-name" class="{{ $detailLabel }}">Nombre</label>
                                                <input id="edit-name" type="text" class="{{ $detailInput }}" wire:model="userForm.name">
                                                <x-input-error for="userForm.name" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-last-name" class="{{ $detailLabel }}">Apellido</label>
                                                <input id="edit-last-name" type="text" class="{{ $detailInput }}" wire:model="userForm.last_name">
                                                <x-input-error for="userForm.last_name" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                            <div class="min-w-0 sm:col-span-2">
                                                <label for="edit-email" class="{{ $detailLabel }}">Email</label>
                                                <input id="edit-email" type="email" class="{{ $detailInput }}" wire:model="userForm.email">
                                                <x-input-error for="userForm.email" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Perfil organizacional</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0 sm:col-span-2">
                                                <label for="edit-role" class="{{ $detailLabel }}">Rol</label>
                                                <x-administration-search-picker style="margin-top: 20px;" input-id="edit-role" model="userForm.role_id" :selected="$userForm['role_id'] ?? null" :items="$roles->map(fn ($availableRole) => ['id' => $availableRole->id, 'label' => $availableRole->role])" placeholder="Buscar rol..." empty-message="No se encontraron roles." />
                                                <x-input-error for="userForm.role_id" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-position" class="{{ $detailLabel }}">Puesto</label>
                                                <x-administration-search-picker style="margin-top: 20px;" input-id="edit-position" model="userForm.job_position_id" :selected="$userForm['job_position_id'] ?? null" :items="$jobPositions->map(fn ($position) => ['id' => $position->id, 'label' => $position->name, 'meta' => $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo'])" placeholder="Buscar puesto..." empty-message="No se encontraron puestos." />
                                                <x-input-error for="userForm.job_position_id" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-area" class="{{ $detailLabel }}">Área / departamento</label>
                                                <x-administration-search-picker style="margin-top: 20px;" input-id="edit-area" model="userForm.physical_area_id" :selected="$userForm['physical_area_id'] ?? null" :items="$physicalAreas->map(fn ($area) => ['id' => $area->id, 'label' => $area->name])" placeholder="Buscar área o departamento..." empty-message="No se encontraron áreas." />
                                                <x-input-error for="userForm.physical_area_id" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Configuración del checador{{ ($userForm['is_hourly_position'] ?? false) ? ' y nómina' : '' }}</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0 sm:col-span-2">
                                                <label for="edit-employee-id" class="{{ $detailLabel }}">ID del reloj checador · opcional</label>
                                                <div
                                                    class="relative mt-[20px]"
                                                    x-data="{ open: false, search: @js((string) ($userForm['employee_id'] ?? '')) }"
                                                    @click.outside="open = false"
                                                >
                                                    <input
                                                        id="edit-employee-id"
                                                        type="text"
                                                        autocomplete="off"
                                                        maxlength="50"
                                                        class="{{ $detailInput }} !mt-0 pr-12"
                                                        wire:model="userForm.employee_id"
                                                        x-model="search"
                                                        @input="open = true"
                                                        @focus="open = true"
                                                        @click="open = true"
                                                        @keydown.escape="open = false"
                                                        aria-autocomplete="list"
                                                        aria-controls="employee-id-suggestions"
                                                        x-bind:aria-expanded="open"
                                                    >
                                                    <button
                                                        type="button"
                                                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-zinc-500 transition hover:text-black focus:outline-none focus:ring-0"
                                                        @click="open = !open"
                                                        aria-label="Mostrar sugerencias del reloj checador"
                                                    >
                                                        <svg class="h-4 w-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>

                                                    <div
                                                        id="employee-id-suggestions"
                                                        x-cloak
                                                        x-show="open"
                                                        x-transition:enter="transition ease-out duration-150"
                                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                                        x-transition:enter-end="opacity-100 translate-y-0"
                                                        x-transition:leave="transition ease-in duration-100"
                                                        x-transition:leave-start="opacity-100 translate-y-0"
                                                        x-transition:leave-end="opacity-0 -translate-y-1"
                                                        class="administration-modal-scrollbar absolute z-[60] max-h-60 w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-lg"
                                                        role="listbox"
                                                    >
                                                        @foreach ($employeeIdSuggestions as $employeeIdSuggestion)
                                                            <button
                                                                type="button"
                                                                data-employee-id="{{ $employeeIdSuggestion->employeeID }}"
                                                                data-person-name="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}"
                                                                x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())"
                                                                @click="search = $el.dataset.employeeId; $wire.set('userForm.employee_id', $el.dataset.employeeId); open = false"
                                                                class="flex w-full min-w-0 items-center gap-[15px] px-[20px] py-[15px] text-left transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none focus:ring-0"
                                                                role="option"
                                                            >
                                                                <span class="w-20 shrink-0 truncate text-[15px] text-black" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                                                <span class="min-w-0 flex-1 truncate text-[15px] text-black" title="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}">{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <p class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]">Selecciona un ID registrado; la sugerencia muestra el nombre detectado por el checador.</p>
                                                <x-input-error for="userForm.employee_id" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>

                                            @if ($userForm['is_hourly_position'] ?? false)
                                                <div class="min-w-0">
                                                    <label for="edit-hourly-rate" class="{{ $detailLabel }}">Precio por hora</label>
                                                    <input id="edit-hourly-rate" type="number" step="0.01" class="{{ $detailInput }}" wire:model="userForm.hourly_rate">
                                                    <x-input-error for="userForm.hourly_rate" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="edit-food-allowance" class="{{ $detailLabel }}">Apoyo económico por día</label>
                                                    <input id="edit-food-allowance" type="number" step="0.01" class="{{ $detailInput }}" wire:model="userForm.food_allowance">
                                                    <x-input-error for="userForm.food_allowance" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                                </div>
                                            @endif
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Jerarquía</h2>
                                        <div class="flex flex-col gap-[20px]">
                                            <div class="min-w-0">
                                                <span id="edit-superiors-label" class="{{ $detailLabel }}">Jefe directo</span>
                                                <p class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]">Marca a una sola persona; vuelve a marcarla para quitarla. Debe estar asignada al organigrama.</p>
                                                <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="org-pick-box relative mt-[20px] overflow-hidden">
                                                    <div id="edit-superiors" x-ref="scroller" @scroll="update()" @mouseenter="update()" class="hierarchy-selection-list administration-form-scrollbar block w-full text-[15px]" role="group" aria-labelledby="edit-superiors-label">
                                                        @forelse ($superiorCandidates as $superiorCandidate)
                                                            <div wire:key="superior-candidate-{{ $superiorCandidate->id }}">
                                                                <label class="org-pick-row flex cursor-pointer items-center gap-[15px] p-[20px] text-left">
                                                                    <input type="checkbox" value="{{ $superiorCandidate->id }}" wire:click="toggleSuperior({{ $superiorCandidate->id }})" @checked(in_array($superiorCandidate->id, $userForm['superior_ids'] ?? [])) class="h-4 w-4 shrink-0 focus:outline-none focus:ring-0">
                                                                    <span class="flex min-w-0 flex-1 items-center gap-[20px]" title="{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }} — {{ mb_strtolower((string) $superiorCandidate->email) }}">
                                                                        <span class="org-pick-name min-w-0 max-w-[55%] shrink-0 truncate leading-none">{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }}</span>
                                                                        <span class="min-w-0 flex-1 truncate leading-none">{{ mb_strtolower((string) $superiorCandidate->email) }}</span>
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        @empty
                                                            <p class="p-[20px] leading-none">No hay colaboradores asignados al organigrama.</p>
                                                        @endforelse
                                                    </div>
                                                    <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                                </div>
                                                <x-input-error for="userForm.superior_ids" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>

                                            <div class="min-w-0">
                                                <span id="edit-subordinates-label" class="{{ $detailLabel }}">Subordinados directos</span>
                                                <p class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]">Solo aparecen personas sin jefe o que ya te reportan directamente.</p>
                                                <div x-data="backupOverlayScroll()" x-effect="$nextTick(() => update())" class="org-pick-box relative mt-[20px] overflow-hidden">
                                                    <div id="edit-subordinates" x-ref="scroller" @scroll="update()" @mouseenter="update()" class="hierarchy-selection-list administration-form-scrollbar block w-full text-[15px]" role="group" aria-labelledby="edit-subordinates-label">
                                                        @forelse ($subordinateCandidates as $subordinateCandidate)
                                                            <div wire:key="subordinate-candidate-{{ $subordinateCandidate->id }}">
                                                                <label class="org-pick-row flex cursor-pointer items-center gap-[15px] p-[20px] text-left">
                                                                    <input type="checkbox" value="{{ $subordinateCandidate->id }}" wire:model.live="userForm.subordinate_ids" class="h-4 w-4 shrink-0 focus:outline-none focus:ring-0">
                                                                    <span class="flex min-w-0 flex-1 items-center gap-[20px]" title="{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }} — {{ mb_strtolower((string) $subordinateCandidate->email) }}">
                                                                        <span class="org-pick-name min-w-0 max-w-[55%] shrink-0 truncate leading-none">{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }}</span>
                                                                        <span class="min-w-0 flex-1 truncate leading-none">{{ mb_strtolower((string) $subordinateCandidate->email) }}</span>
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        @empty
                                                            <p class="p-[20px] leading-none">No hay colaboradores disponibles.</p>
                                                        @endforelse
                                                    </div>
                                                    <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                                                </div>
                                                <x-input-error for="userForm.subordinate_ids" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Contraseña</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0">
                                                <label for="edit-password" class="{{ $detailLabel }}">Nueva contraseña (opcional)</label>
                                                <input id="edit-password" type="password" autocomplete="new-password" class="{{ $detailInput }}" wire:model="userForm.password">
                                                <x-input-error for="userForm.password" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-password-confirmation" class="{{ $detailLabel }}">Confirmar contraseña</label>
                                                <input id="edit-password-confirmation" type="password" autocomplete="new-password" class="{{ $detailInput }}" wire:model="userForm.password_confirmation">
                                                <x-input-error for="userForm.password_confirmation" class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px]" />
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>

                    {{-- Resumen de errores: siempre a la vista junto al botón, aunque el campo con el error esté más arriba en el formulario --}}
                    @if ($errors->any())
                        <div class="flex-shrink-0 border-t border-zinc-200 bg-white px-[20px] pt-[20px]" role="alert">
                            <p class="text-red-700">No se pudo guardar. Revisa lo siguiente:</p>
                            <ul class="mt-[20px] flex flex-col gap-[15px]">
                                @foreach (array_unique($errors->all()) as $message)
                                    <li class="text-red-700">{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <footer class="flex flex-shrink-0 justify-end gap-[20px] border-t border-zinc-200 bg-white p-[20px]">
                        <button type="button" wire:click="cancelEditingUser" class="{{ $detailSecondaryButton }}">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveSelectedUser" class="{{ $detailPrimaryButton }}">
                            <svg wire:loading wire:target="saveSelectedUser" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            <span wire:loading.remove wire:target="saveSelectedUser">Guardar cambios</span>
                            <span wire:loading wire:target="saveSelectedUser">Guardando...</span>
                        </button>
                    </footer>
                </form>
            @else
                {{-- Sin menú de pestañas: los datos se ven aquí y la eliminación se pide con la X de cada nodo. --}}

                {{-- ===== CONTENIDO CON SCROLL ===== --}}
                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                        <div class="p-[20px] text-[15px]">
                            @if ($activeTab === 'datos')
                                <div class="flex flex-col gap-[20px]">
                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Datos generales</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0 sm:col-span-2">
                                                <span class="{{ $detailLabel }}">Nombre completo</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['name'] ?? 'N/A' }}</div>
                                            </div>
                                            <div class="min-w-0 sm:col-span-2">
                                                <span class="{{ $detailLabel }}">Correo electrónico</span>
                                                <div class="{{ $detailValue }}">{{ isset($selectedUserDetails['email']) ? mb_strtolower((string) $selectedUserDetails['email']) : 'N/A' }}</div>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="{{ $detailLabel }}">Rol</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['role'] ?: 'Sin rol' }}</div>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="{{ $detailLabel }}">ID del checador</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['employee_id'] ?: 'No asignado' }}</div>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="{{ $detailLabel }}">Fecha de creación</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['created_at'] ?? 'N/A' }}</div>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="{{ $detailLabel }}">Última actualización</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['updated_at'] ?? 'N/A' }}</div>
                                            </div>
                                            <div class="min-w-0 sm:col-span-2">
                                                <span class="{{ $detailLabel }}">Contraseña</span>
                                                <div class="{{ $detailValue }}">•••••••• (protegida)</div>
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="admin-section-title mb-[20px]">Organizacional</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0">
                                                <span class="{{ $detailLabel }}">Puesto</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['job_position'] ?: 'Sin asignar' }}</div>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="{{ $detailLabel }}">Área / departamento</span>
                                                <div class="{{ $detailValue }}">{{ $selectedUserDetails['physical_area'] ?: 'Sin asignar' }}</div>
                                            </div>
                                            <div class="min-w-0 sm:col-span-2">
                                                <span class="{{ $detailLabel }}">Jefes directos</span>
                                                <div class="{{ $detailValue }}">
                                                    @if (count($selectedUserDetails['superiors'] ?? []) > 0)
                                                        {{ implode(', ', $selectedUserDetails['superiors']) }}
                                                    @else
                                                        <span>Sin jefe asignado</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="min-w-0 sm:col-span-2">
                                                <span class="{{ $detailLabel }}">Subordinados directos</span>
                                                <div class="{{ $detailValue }}">
                                                    @if (count($selectedUserDetails['subordinates'] ?? []) > 0)
                                                        {{ implode(', ', $selectedUserDetails['subordinates']) }}
                                                    @else
                                                        <span>Sin subordinados</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    @if ($selectedUserDetails['is_hourly_position'] ?? false)
                                        <section style="--admin-form-gap: 20px;">
                                            <h2 class="admin-section-title mb-[20px]">Datos de auxiliar</h2>
                                            <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                                <div class="min-w-0">
                                                    <span class="{{ $detailLabel }}">Precio por hora</span>
                                                    <div class="{{ $detailValue }}">${{ number_format((float) ($selectedUserDetails['hourly_rate'] ?? 0), 2) }}</div>
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="{{ $detailLabel }}">Apoyo económico por día</span>
                                                    <div class="{{ $detailValue }}">${{ number_format((float) ($selectedUserDetails['food_allowance'] ?? 0), 2) }}</div>
                                                </div>
                                            </div>
                                        </section>
                                    @endif
                                </div>
                            @elseif ($activeTab === 'eliminar')
                                {{-- Lo más común es solo sacarlo del árbol: no se borra nada y su equipo pasa a su jefe --}}
                                <div class="org-choice">
                                    <h2 class="admin-section-title mb-[20px]">Solo quitar del organigrama</h2>
                                    <p class="-mt-[7.5px] -mb-[7.5px] text-[15px]">{{ $detailName }} sale del árbol y queda en «Sin asignar»; su equipo directo pasa a reportar a su jefe. No se borra ningún dato y después puedes volver a acomodarlo arrastrándolo.</p>
                                    <button type="button" wire:click="removeSelectedUserFromChart" wire:loading.attr="disabled" wire:target="removeSelectedUserFromChart" class="{{ $detailPrimaryButton }} mt-[20px] w-full">Solo quitar del organigrama</button>
                                </div>
                                <section style="--admin-form-gap: 20px;">
                                    <h2 class="admin-section-title mb-[20px]">¿Estás seguro de eliminar este usuario?</h2>
                                    <p class="-mt-[7.5px] -mb-[7.5px] text-[15px] leading-[30px] text-black">Esta acción es irreversible y eliminará permanentemente al usuario del sistema. Su equipo directo pasará a reportar a su jefe.</p>
                                    <p class="mt-[12.5px] -mb-[7.5px] text-[15px] leading-[30px] text-black">Para confirmar, escribe el nombre completo del usuario: <span class="text-red-700">{{ $detailName }}</span></p>
                                    <label for="delete-user-confirmation" class="mt-[20px] {{ $detailLabel }}">Nombre completo</label>
                                    <input id="delete-user-confirmation" type="text" autocomplete="off" wire:model="deleteConfirmationName" class="{{ $detailInput }}" placeholder="Nombre completo del usuario">
                                    @error('deleteConfirmationName')
                                        <p class="mt-2 text-[15px] text-red-600" style="margin-bottom: 0;">{{ $message }}</p>
                                    @enderror
                                    <button type="button" wire:click="deleteSelectedUser" class="mt-[20px] inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-[20px] py-[15px] text-[15px] leading-none text-white transition-colors hover:bg-red-700 focus:outline-none focus:ring-0">Eliminar usuario permanentemente</button>
                                </section>
                            @endif
                        </div>
                    </div>
                    <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                {{-- ===== PIE CON BOTONES (fijo) ===== --}}
                <footer class="flex flex-shrink-0 justify-end gap-[20px] border-t border-zinc-200 bg-white p-[20px]">
                    <button type="button" wire:click="closeUserDetails" class="{{ $detailSecondaryButton }}">Cerrar</button>
                    @if ($activeTab === 'datos')
                        <button type="button" wire:click="beginEditingUser" class="{{ $detailPrimaryButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Editar
                        </button>
                    @endif
                </footer>
            @endif
        </div> {{-- fin contenedor principal --}}
    </div> {{-- fin fixed overlay --}}
@endif
    @endif
</div>

@push('scripts')
<script>
    // Ajuste de altura de las tarjetas del organigrama. Va aquí (y no en cada tarjeta) porque el árbol llega después de
    // abrir la pantalla y los scripts que llegan en una actualización de Livewire no se ejecutan.
    // Iguala el alto de las tarjetas. Si el organigrama está oculto (x-show) la medición da 0 y
    // dejaba todas las tarjetas en 0px (contenido desbordado); ahora no se fija nada en ese caso
    // y se vuelve a calcular en cuanto el organigrama se muestra o cambia de tamaño.
    window.equalizeNodeHeights = window.equalizeNodeHeights || function () {
        const cards = document.querySelectorAll('.js-org-card');
        if (cards.length === 0) return;

        cards.forEach(card => { card.style.height = 'auto'; });

        let maxHeight = 0;
        cards.forEach(card => { maxHeight = Math.max(maxHeight, card.offsetHeight); });

        if (maxHeight === 0) return;

        cards.forEach(card => { card.style.height = `${maxHeight}px`; });
        // Las líneas del organigrama se vuelven a trazar con las alturas nuevas
        window.dispatchEvent(new CustomEvent('org-cards-measured'));
    };

    window.bindOrgEqualizer = window.bindOrgEqualizer || function () {
        const container = document.getElementById('organigrama-principal');
        window.orgEqualizerObserver?.disconnect();
        if (!container) return;

        let scheduled = false;
        window.orgEqualizerObserver = new ResizeObserver(() => {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(() => { scheduled = false; window.equalizeNodeHeights(); });
        });
        window.orgEqualizerObserver.observe(container);
        window.equalizeNodeHeights();
    };

    if (!window.orgEqualizerReady) {
        window.orgEqualizerReady = true;
        document.addEventListener('livewire:navigated', () => window.bindOrgEqualizer());
        document.addEventListener('livewire:initialized', () => window.bindOrgEqualizer());
        window.addEventListener('DOMContentLoaded', () => window.bindOrgEqualizer());
        if (document.readyState !== 'loading') window.bindOrgEqualizer();
    }

    // Se arranca en cuanto el árbol está en la página (este script va al final), sin esperar a que terminen de cargar todos
    // los demás scripts: así el organigrama aparece centrado al instante.
    const orgChartBoot = function() {
        const container = document.getElementById('org-tree-container');
        // El contenido del árbol lo vuelve a dibujar Livewire (al filtrar por área, por ejemplo) y el elemento cambia:
        // siempre se consulta el vigente para que mover y acercar sigan funcionando después de filtrar.
        const wrapper = {
            get style() { return (document.getElementById('org-tree-wrapper') || { style: {} }).style; },
            getBoundingClientRect() { return (document.getElementById('org-tree-wrapper') || document.createElement('div')).getBoundingClientRect(); },
        };

        if (!container) return;

        let isPanning = false;
        let didDrag = false;
        let panStartedOnNode = false;
        let startX, startY, startTranslateX, startTranslateY;
        let scale = 1;
        let translateX = 0, translateY = 0;

        // Píxeles de pantalla por píxel interno (la app aplica un zoom a todo el contenido)
        const unit = () => (container.offsetWidth ? container.getBoundingClientRect().width / container.offsetWidth : 1);

        function updateTransform() {
            wrapper.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
            const grid = 24 * scale;
            container.style.backgroundSize = `${grid}px ${grid}px`;
            container.style.backgroundPosition = `${translateX * scale}px ${translateY * scale}px`;
        }

        // Vista inicial: el nodo principal (el primero del árbol) queda centrado en horizontal y justo debajo de los controles.
        function resetView() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            updateTransform();

            const root = document.querySelector('#org-tree-wrapper .org-node');
            if (root) {
                const wrapperRect = wrapper.getBoundingClientRect();
                const nodeRect = root.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();

                const u = unit();
                translateX = (containerRect.width / 2 - ((nodeRect.left + nodeRect.width / 2) - wrapperRect.left)) / u;
                // La primera tarjeta queda 40px debajo de la barra de herramientas
                const bar = container.querySelector('.org-toolbar');
                const barBottom = bar ? (bar.getBoundingClientRect().bottom - containerRect.top) / u : 70;
                const top = barBottom < (containerRect.height / u) / 2 ? barBottom + 40 : 60;
                translateY = top - (nodeRect.top - wrapperRect.top) / u;
                updateTransform();
            }
        }

        // Tras cada actualización de Livewire: si cambió el filtro de área (o el árbol acaba de llegar) se parte de la vista
        // inicial; si no (alguien movió a una persona, o el organigrama se sincronizó), se conserva el movimiento y el zoom y
        // las tarjetas se deslizan a su nuevo lugar junto con sus líneas.
        const currentArea = () => document.getElementById('organigrama-principal')?.dataset.area || '';
        let lastArea = currentArea();
        let hadTree = Boolean(document.querySelector('#org-tree-wrapper .org-node'));
        let settleTimer = null;
        // Las tarjetas aparecen por nivel al abrir el organigrama (o al cambiar de área); después ya no
        const replayEntry = () => {
            container.classList.remove('org-settled');
            clearTimeout(settleTimer);
            settleTimer = setTimeout(() => container.classList.add('org-settled'), 1400);
        };
        const afterLivewireUpdate = () => {
            isPanning = false;
            didDrag = false;
            panStartedOnNode = false;
            // Primero se igualan las alturas de las tarjetas y después se centra, para medir el nodo ya con su tamaño final.
            if (window.equalizeNodeHeights) window.equalizeNodeHeights();
            const area = currentArea();
            const hasTree = Boolean(document.querySelector('#org-tree-wrapper .org-node'));
            if (area !== lastArea || (hasTree && !hadTree)) {
                lastArea = area;
                flipBefore = null;
                landing = null;
                resetView();
                replayEntry();
                drawLines();
            } else {
                updateTransform();
                playFlip();
            }
            hadTree = hasTree;
            allNodes = [];
        };
        // Al entrar: se igualan las alturas, se centra el árbol y entonces aparece con un fundido (nunca se ve el salto)
        const reveal = () => {
            if (container.classList.contains('org-ready')) return;
            container.classList.add('org-ready');
            replayEntry();
        };
        requestAnimationFrame(() => {
            if (window.equalizeNodeHeights) window.equalizeNodeHeights();
            resetView();
            drawLines();
            requestAnimationFrame(reveal);
        });
        // Solo las actualizaciones de este componente (el chat y otros que se actualizan solos no deben soltar un arrastre)
        const rootId = container.closest('[wire\\:id]')?.getAttribute('wire:id');
        const ours = (component) => !rootId || !component || component.id === rootId;
        const hookLivewire = () => {
            window.Livewire?.hook('morph', ({ component } = {}) => { if (ours(component)) beforeMorph(); });
            window.Livewire?.hook('morphed', ({ component } = {}) => {
                if (!ours(component)) return;
                afterLivewireUpdate();
                reveal();
            });
            // Quien sale del árbol entra a «Sin asignar» deslizándose
            window.Livewire?.hook('morph.added', ({ el } = {}) => {
                if (el instanceof Element && el.matches('.org-list > li')) el.classList.add('org-row-new');
            });
        };
        if (window.Livewire) hookLivewire(); else document.addEventListener('livewire:init', hookLivewire);
        window.addEventListener('blur', () => { isPanning = false; });

        function centerNode(nodeElement) {
            resetView();
            
            requestAnimationFrame(() => {
                const wrapperRect = wrapper.getBoundingClientRect();
                const nodeRect = nodeElement.getBoundingClientRect();
                
                const u = unit();
                const nodeCenterX = ((nodeRect.left + nodeRect.width / 2) - wrapperRect.left) / u;
                const nodeCenterY = ((nodeRect.top + nodeRect.height / 2) - wrapperRect.top) / u;

                const containerRect = container.getBoundingClientRect();
                const targetX = (containerRect.width / u / 2) - (nodeCenterX * scale);
                const targetY = (containerRect.height / u / 2) - (nodeCenterY * scale);
                
                translateX = targetX;
                translateY = targetY;
                updateTransform();
            });
        }

        container.addEventListener('wheel', function(e) {
            if (e.target.closest('#search-results') || e.target.closest('.search-result-item') || e.target.closest('#physical-area-results') || e.target.closest('[data-org-panel]')) {
                return;
            }
            
            e.preventDefault();
            const rect = container.getBoundingClientRect();
            const u = unit();
            const mouseX = (e.clientX - rect.left) / u;
            const mouseY = (e.clientY - rect.top) / u;
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const newScale = Math.min(Math.max(scale * delta, 0.3), 3);
            translateX += mouseX / newScale - mouseX / scale;
            translateY += mouseY / newScale - mouseY / scale;
            scale = newScale;
            updateTransform();
            // Si se acerca o aleja mientras se arrastra a alguien, se vuelve a ver qué hay bajo el puntero
            if (nodeDrag && !hitFrame) hitFrame = requestAnimationFrame(evaluateDrop);
        }, { passive: false });

        container.addEventListener('mousedown', function(e) {
            if (e.button !== 0 || nodeDrag || e.target.closest('#node-search-input, #search-results, .search-result-item, #physical-area-filter, #physical-area-results, #fullscreen-toggle, [data-org-panel], .org-node-actions')) {
                return;
            }

            const node = e.target.closest('.org-node');
            if (!node && e.target.closest('button, a, input, select, textarea')) {
                return;
            }

            isPanning = true;
            didDrag = false;
            panStartedOnNode = Boolean(node);
            startX = e.clientX;
            startY = e.clientY;
            startTranslateX = translateX;
            startTranslateY = translateY;
            wrapper.style.cursor = 'grabbing';
        });

        window.addEventListener('mousemove', function(e) {
            if (!isPanning || nodeDrag) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            if (!didDrag && Math.hypot(dx, dy) < 5) return;
            didDrag = true;
            const k = unit() * scale;
            translateX = startTranslateX + dx / k;
            translateY = startTranslateY + dy / k;
            updateTransform();
        });

        window.addEventListener('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                wrapper.style.cursor = 'grab';
            }
        });

        container.addEventListener('click', function(e) {
            if (didDrag && panStartedOnNode && e.target.closest('.org-node')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                didDrag = false;
                panStartedOnNode = false;
            }
        }, true);

        container.addEventListener('selectstart', function(e) {
            if (isPanning) e.preventDefault();
        });

        container.addEventListener('dblclick', function(e) {
            if (e.target.closest('[data-org-panel]')) return;
            e.preventDefault();
            resetView();
        });

        // ============================================================
        // BUSCADOR DE NODOS
        // ============================================================
        const searchInput = document.getElementById('node-search-input');
        const searchResults = document.getElementById('search-results');
        let allNodes = [];
        let searchTimeout = null;

        if (searchResults) {
            searchResults.addEventListener('wheel', function(e) {
                e.stopPropagation();
            }, { passive: true });
            
            searchResults.addEventListener('mousedown', function(e) {
                e.stopPropagation();
            });
            
            searchResults.addEventListener('touchstart', function(e) {
                e.stopPropagation();
            });
        }

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
        const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        // Lee a las personas del árbol (cada una una sola vez, aunque aparezca bajo varios jefes).
        function loadAllNodes() {
            const seen = new Set();
            allNodes = [];
            document.querySelectorAll('.org-node').forEach((el) => {
                const id = el.dataset.id ? parseInt(el.dataset.id) : null;
                if (!id || seen.has(id)) return;
                seen.add(id);
                const d = el.dataset;
                allNodes.push({
                    id,
                    name: d.name || '',
                    email: d.email || '',
                    role: d.role || '',
                    position: d.position || '',
                    area: d.area || '',
                    photo: d.photo || '',
                    initials: d.initials || '?',
                    haystack: normalize([d.name, d.email, d.role, d.position, d.area].join(' ')),
                });
            });
            allNodes.sort((a, b) => a.name.localeCompare(b.name, 'es'));
            return allNodes;
        }

        // Lista de resultados con el mismo formato de las demás listas: foto o iniciales, nombre, correo y datos laborales.
        function showResults(results) {
            if (!searchResults) return;

            if (results.length === 0) {
                searchResults.innerHTML = '<div class="org-row org-muted">No se encontraron resultados</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = results.map((node) => {
                const avatar = node.photo
                    ? `<img src="${escapeHtml(node.photo)}" alt="" class="org-avatar">`
                    : `<span class="org-avatar">${escapeHtml(node.initials)}</span>`;
                const details = [node.role, node.position, node.area].filter(Boolean).join(' · ');
                return `<button type="button" class="search-result-item org-row" data-id="${node.id}">
                    ${avatar}
                    <span class="org-row-text">
                        <span class="org-row-name">${escapeHtml(node.name)}</span>
                        <span class="org-row-meta" title="${escapeHtml(node.email)}">${escapeHtml(node.email)}</span>
                        ${details ? `<span class="org-row-meta" title="${escapeHtml(details)}">${escapeHtml(details)}</span>` : ''}
                    </span>
                </button>`;
            }).join('');

            searchResults.style.display = 'block';
            searchResults.dispatchEvent(new Event('scroll'));
        }

        // Lleva la vista al nodo elegido y lo resalta un momento.
        function goToNode(nodeId) {
            const nodeEl = document.querySelector(`.org-node[data-id="${nodeId}"]`);
            if (!nodeEl) return;

            centerNode(nodeEl);
            nodeEl.classList.remove('org-node-flash');
            void nodeEl.offsetWidth;
            nodeEl.classList.add('org-node-flash');
            setTimeout(() => nodeEl.classList.remove('org-node-flash'), 1800);
        }

        if (searchResults) {
            // Delegación: los resultados se vuelven a pintar al escribir, así que el clic se atiende en el contenedor.
            searchResults.addEventListener('click', function (e) {
                const item = e.target.closest('.search-result-item');
                if (!item) return;

                e.stopPropagation();
                e.preventDefault();
                goToNode(parseInt(item.dataset.id));
                searchResults.style.display = 'none';
                if (searchInput) searchInput.value = '';
            });
        }

        if (searchInput) {
            // Al hacer clic por primera vez aparece la lista completa; al escribir se va filtrando.
            searchInput.addEventListener('focus', function() {
                loadAllNodes();
                const query = normalize(this.value.trim());
                showResults(query ? allNodes.filter((n) => n.haystack.includes(query)) : allNodes);
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (allNodes.length === 0) loadAllNodes();
                    const query = normalize(this.value.trim());
                    showResults(query ? allNodes.filter((n) => n.haystack.includes(query)) : allNodes);
                }, 80);
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const first = searchResults.querySelector('.search-result-item');
                    if (first) {
                        e.preventDefault();
                        goToNode(parseInt(first.dataset.id));
                        searchResults.style.display = 'none';
                        this.value = '';
                        this.blur();
                    }
                }
            });

            searchInput.addEventListener('blur', function() {
                setTimeout(() => { 
                    if (searchResults) searchResults.style.display = 'none'; 
                }, 300);
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (searchResults) searchResults.style.display = 'none';
                    this.blur();
                }
            });
        }

        document.addEventListener('click', function(e) {
            if (searchInput && searchResults) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            }
        });

        document.addEventListener('livewire:navigated', function() {
            setTimeout(() => { allNodes = []; loadAllNodes(); }, 300);
        });

        window.addEventListener('organization-chart-shown', function() {
            requestAnimationFrame(() => {
                allNodes = [];
                loadAllNodes();
                updateTransform();
            });
        });

        setTimeout(() => { allNodes = []; loadAllNodes(); }, 500);

        // ============================================================
        // LÍNEAS: se dibujan en una capa SVG según dónde está cada tarjeta, así siguen a las tarjetas mientras se deslizan
        // ============================================================
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const SVG_NS = 'http://www.w3.org/2000/svg';
        const EASE = 'cubic-bezier(.22, .61, .36, 1)';
        const SLIDE_MS = 300;   // el árbol abre (o cierra) el hueco de la vista previa
        const SETTLE_MS = 460;  // al terminar de mover a alguien, todo se desliza a su lugar
        let linesFrame = null;
        let linesUntil = 0;
        let linesTimer = null;

        // Posición de un elemento dentro del árbol, en píxeles del propio árbol (sin el zoom ni el movimiento aplicados)
        function treeBox(treeEl) {
            const box = treeEl.getBoundingClientRect();
            const k = treeEl.offsetWidth ? box.width / treeEl.offsetWidth : 1;
            return (el) => {
                const r = el.getBoundingClientRect();
                return { x: (r.left - box.left) / k, y: (r.top - box.top) / k, w: r.width / k, h: r.height / k };
            };
        }

        const slotOf = (wrapperEl) => (wrapperEl ? wrapperEl.querySelector(':scope > .relative') : null);
        const cardOf = (wrapperEl) => (wrapperEl ? wrapperEl.querySelector(':scope > .relative > .org-node') : null);

        // Cada línea baja del jefe, cruza por la barra (con esquinas de 12px en los extremos) y baja a la persona
        function drawLines() {
            const treeEl = document.getElementById('org-tree-wrapper');
            const layer = treeEl ? treeEl.querySelector(':scope > .org-lines') : null;
            if (!treeEl || !layer || !treeEl.offsetWidth) return;
            container.classList.add('org-svg-lines');
            const pos = treeBox(treeEl);
            layer.setAttribute('width', treeEl.offsetWidth);
            layer.setAttribute('height', treeEl.offsetHeight);

            const groups = { preview: [], dim: [], line: [] };
            const walk = (wrapperEl) => {
                const kids = [...wrapperEl.querySelectorAll(':scope > .org-children > .org-child > .visual-node-wrapper')];
                const card = cardOf(wrapperEl);
                if (card && kids.length) {
                    const p = pos(card);
                    const px = p.x + p.w / 2;
                    const pb = p.y + p.h;
                    const fromPreview = wrapperEl.classList.contains('org-ph-wrapper');
                    const items = kids.map((kid) => {
                        const kidCard = cardOf(kid);
                        if (!kidCard) return null;
                        const r = pos(kidCard);
                        const kind = fromPreview || kid.classList.contains('org-ph-wrapper') ? 'preview' : (kid.closest('.org-dragging') ? 'dim' : 'line');
                        return { cx: r.x + r.w / 2, top: r.y, kind };
                    }).filter(Boolean);
                    if (items.length) {
                        const barY = pb + (Math.min(...items.map((item) => item.top)) - pb) / 2;
                        const minX = Math.min(...items.map((item) => item.cx));
                        const maxX = Math.max(...items.map((item) => item.cx));
                        items.forEach((item) => {
                            let d;
                            if (Math.abs(item.cx - px) < 1) {
                                d = `M${px} ${pb}V${item.top}`;
                            } else {
                                const dir = Math.sign(item.cx - px);
                                const outer = dir < 0 ? item.cx <= minX + 0.5 : item.cx >= maxX - 0.5;
                                const r = outer ? Math.max(0, Math.min(12, Math.abs(item.cx - px), item.top - barY)) : 0;
                                d = `M${px} ${pb}V${barY}H${item.cx - dir * r}` + (r ? `Q${item.cx} ${barY} ${item.cx} ${barY + r}` : '') + `V${item.top}`;
                            }
                            groups[item.kind].push(d);
                        });
                    }
                }
                kids.forEach(walk);
            };
            treeEl.querySelectorAll(':scope > div > .visual-node-wrapper').forEach(walk);

            // Primero la vista previa y lo atenuado; encima las líneas normales (donde se enciman se ve la normal)
            const ordered = [...groups.preview.map((d) => [d, 'is-preview']), ...groups.dim.map((d) => [d, 'is-dim']), ...groups.line.map((d) => [d, ''])];
            while (layer.childElementCount > ordered.length) layer.lastElementChild.remove();
            ordered.forEach(([d, cls], index) => {
                let path = layer.children[index];
                if (!path) {
                    path = document.createElementNS(SVG_NS, 'path');
                    layer.append(path);
                }
                if (path.getAttribute('d') !== d) path.setAttribute('d', d);
                if ((path.getAttribute('class') || '') !== cls) path.setAttribute('class', cls);
            });
        }

        // Mientras las tarjetas se deslizan, las líneas se vuelven a trazar en cada cuadro
        function animateLines(ms) {
            linesUntil = Math.max(linesUntil, performance.now() + ms);
            if (linesFrame) return;
            const tick = () => {
                drawLines();
                linesFrame = performance.now() < linesUntil ? requestAnimationFrame(tick) : null;
            };
            linesFrame = requestAnimationFrame(tick);
        }
        function scheduleLines(delay = 60) {
            clearTimeout(linesTimer);
            linesTimer = setTimeout(drawLines, delay);
        }
        window.addEventListener('resize', () => scheduleLines(150));
        window.addEventListener('org-cards-measured', () => scheduleLines(0));
        if (document.fonts?.ready) document.fonts.ready.then(() => { if (window.equalizeNodeHeights) window.equalizeNodeHeights(); scheduleLines(0); });

        // ============================================================
        // DESLIZAR: cada tarjeta va de donde estaba a su nuevo lugar (el árbol se acomoda solo, sin saltos)
        // ============================================================
        let flipBefore = null;  // dónde estaba cada tarjeta antes de volver a dibujar el árbol
        let landing = null;     // persona que se acaba de mover: dónde quedó su hueco y qué se esperaba

        const slots = () => [...document.querySelectorAll('#org-tree-wrapper .visual-node-wrapper > .relative')];
        const slotKey = (slot) => {
            const card = slot.querySelector(':scope > .org-node');
            if (!card) return null;
            return card.classList.contains('org-ph-card') ? 'ph' : card.dataset.id;
        };
        function measureSlots() {
            const map = new Map();
            slots().forEach((slot) => {
                const key = slotKey(slot);
                if (key && !map.has(key)) map.set(key, slot.getBoundingClientRect());
            });
            return map;
        }
        function rememberPositions() {
            // También sin animaciones: sirve para que la vista no brinque al volver a dibujar
            flipBefore = measureSlots();
        }
        const stopSlides = () => slots().forEach((slot) => slot.getAnimations().forEach((animation) => {
            if (animation.id === 'org-slide' || animation.id === 'org-ph-in') animation.cancel();
        }));

        function slideFrom(first, ms) {
            stopSlides();
            if (!first || reduceMotion) {
                drawLines();
                return;
            }
            const k = unit() * scale;
            slots().forEach((slot) => {
                const key = slotKey(slot);
                const was = key ? first.get(key) : null;
                if (!was) return;
                const now = slot.getBoundingClientRect();
                const dx = (was.left - now.left) / k;
                const dy = (was.top - now.top) / k;
                if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) return;
                slot.animate([{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'translate(0, 0)' }], { duration: ms, easing: EASE }).id = 'org-slide';
            });
            animateLines(ms + 40);
        }

        // Deja una tarjeta en el mismo punto de la pantalla después de reacomodar el árbol (se mueve el lienzo, no ella):
        // así lo que está bajo el puntero no se escapa mientras se arrastra
        function anchorTo(slot, rect) {
            if (!slot || !rect) return;
            const now = slot.getBoundingClientRect();
            const k = unit() * scale;
            translateX += (rect.left - now.left) / k;
            translateY += (rect.top - now.top) / k;
            updateTransform();
        }

        function playFlip() {
            const first = flipBefore;
            const land = landing;
            flipBefore = null;
            landing = null;
            if (land) {
                const card = document.querySelector(`#org-tree-wrapper .org-node[data-id="${land.id}"]`);
                let ok = false;
                if (land.placement === 'remove') {
                    ok = !card;
                } else if (card) {
                    ok = superiorIdOf(card) === land.expectSuperior;
                    if (ok && land.placement === 'above') {
                        const target = document.querySelector(`#org-tree-wrapper .org-node[data-id="${land.targetId}"]`);
                        ok = Boolean(target) && superiorIdOf(target) === land.id;
                    }
                }
                // Si quedó donde se soltó, su tarjeta se queda justo en el hueco y el resto se acomoda a su alrededor; si no se
                // pudo (alguien más cambió el organigrama), regresa deslizándose a su lugar
                if (ok && card && land.rect) anchorTo(card.parentElement, land.rect);
                else if (land.anchorId && first) anchorTo(slotOfId(land.anchorId), first.get(String(land.anchorId)));
            } else if (first) {
                // Cambio que llegó de otra pantalla (o de un formulario): la tarjeta más cercana al centro de la vista se queda
                // en su lugar y lo demás se acomoda a su alrededor, así la vista no brinca
                const box = container.getBoundingClientRect();
                const centerX = box.left + box.width / 2;
                const centerY = box.top + box.height / 2;
                const nearest = [...first.entries()]
                    .filter(([key]) => key !== 'ph')
                    .map(([key, rect]) => [key, rect, Math.hypot(rect.left + rect.width / 2 - centerX, rect.top + rect.height / 2 - centerY)])
                    .sort((a, b) => a[2] - b[2])
                    .find(([key]) => slotOfId(key));
                if (nearest) anchorTo(slotOfId(nearest[0]), nearest[1]);
            }
            slideFrom(first, SETTLE_MS);
        }

        function flashNode(id) {
            const card = document.querySelector(`#org-tree-wrapper .org-node[data-id="${id}"]`);
            if (!card) return;
            card.classList.remove('org-node-flash');
            void card.offsetWidth;
            card.classList.add('org-node-flash');
            setTimeout(() => card.classList.remove('org-node-flash'), 1500);
        }
        window.addEventListener('org-node-moved', (event) => {
            const id = event.detail?.id ?? (Array.isArray(event.detail) ? event.detail[0]?.id : null);
            if (id) setTimeout(() => flashNode(id), 260);
        });

        // ============================================================
        // MOVER PERSONAS: se arrastra una tarjeta (de su asa, o dejándola presionada un momento) o una fila de «Sin
        // asignar». En el árbol aparece un hueco punteado donde quedaría: arriba de una tarjeta queda a cargo de esa
        // persona, abajo entra a su equipo y a los lados queda junto a ella. Al soltar en «Sin asignar» sale del árbol.
        // ============================================================
        let nodePress = null;   // presión del botón que todavía no se convierte en arrastre
        let nodeDrag = null;    // arrastre en curso
        let preview = null;     // hueco de la vista previa: { targetId, placement, wrapper, restore }
        let pending = null;     // movimiento enviado que espera la respuesta
        let suppressNodeClick = false;
        let edgeFrame = null;
        let hitFrame = null;
        let marked = null;
        let lastPointer = { x: 0, y: 0 };

        const wireComponent = () => {
            const root = container.closest('[wire\\:id]');
            return root && window.Livewire ? window.Livewire.find(root.getAttribute('wire:id')) : null;
        };
        // Tarjeta «de verdad» más cercana hacia arriba (los huecos de la vista previa no cuentan)
        const realWrapperFrom = (el) => {
            let wrapperEl = el ? el.closest('.visual-node-wrapper') : null;
            while (wrapperEl && wrapperEl.classList.contains('org-ph-wrapper')) wrapperEl = wrapperEl.parentElement ? wrapperEl.parentElement.closest('.visual-node-wrapper') : null;
            return wrapperEl;
        };
        // Jefe que la pantalla muestra para una tarjeta (null si es la cabeza de su rama)
        const superiorIdOf = (card) => {
            const own = card.closest('.visual-node-wrapper');
            const parentCard = cardOf(realWrapperFrom(own ? own.parentElement : null));
            return parentCard && parentCard.dataset.id ? parseInt(parentCard.dataset.id) : null;
        };
        const slotOfId = (id) => document.querySelector(`#org-tree-wrapper .org-node[data-id="${id}"]`)?.parentElement || null;
        // Compañera de al lado dentro del mismo equipo (sin contar el hueco de la vista previa)
        const neighbourChild = (childEl, direction) => {
            let el = childEl ? childEl[direction] : null;
            while (el && el.classList.contains('org-ph-child')) el = el[direction];
            return el;
        };

        const HINTS = {
            above: (name) => `Quedará a cargo de ${name}`,
            child: (name) => `Quedará en el equipo de ${name}`,
            before: (name) => `Quedará junto a ${name}`,
            after: (name) => `Quedará junto a ${name}`,
        };
        const DEFAULT_HINT = 'Suéltala arriba, abajo o a un lado de otra persona';

        function startPress(e, info) {
            nodePress = { ...info, x: e.clientX, y: e.clientY, timer: null };
            if (info.longPress) {
                // Dejar presionada la tarjeta un momento sin moverla convierte el gesto en «mover persona» (si se mueve
                // antes, es el gesto normal de mover el organigrama)
                nodePress.timer = setTimeout(() => {
                    if (nodePress && !nodeDrag) beginNodeDrag(nodePress, lastPointer);
                }, 320);
            }
        }

        container.addEventListener('mousedown', function(e) {
            if (e.button !== 0 || nodeDrag || pending) return;
            lastPointer = { x: e.clientX, y: e.clientY };
            const handle = e.target.closest('.org-drag-handle');
            if (handle) {
                const card = cardOf(handle.closest('.visual-node-wrapper'));
                if (!card) return;
                e.preventDefault();
                startPress(e, { source: 'tree', card, id: parseInt(card.dataset.id), from: superiorIdOf(card), longPress: false });
                return;
            }
            const row = e.target.closest('[data-unassigned-id]');
            if (row && !e.target.closest('button, a, input')) {
                e.preventDefault();
                startPress(e, { source: 'unassigned', row, id: parseInt(row.dataset.unassignedId), from: null, longPress: false });
                return;
            }
            const card = e.target.closest('#org-tree-wrapper .org-node');
            if (card && card.dataset.id) {
                startPress(e, { source: 'tree', card, id: parseInt(card.dataset.id), from: superiorIdOf(card), longPress: true });
            }
        }, true);

        function avatarElement(photo, initials, className) {
            const avatar = photo ? document.createElement('img') : document.createElement('span');
            avatar.className = className;
            if (photo) {
                avatar.src = photo;
                avatar.alt = '';
            } else {
                avatar.textContent = initials || '?';
            }
            return avatar;
        }

        // Tarjeta que sigue al cursor: va en el body (fuera del organigrama), así que se le copian los colores del tema
        function buildGhost(drag) {
            const ghost = document.createElement('div');
            ghost.className = 'org-drag-ghost';
            const styles = getComputedStyle(container);
            ['--panel-surface', '--panel-border', '--panel-text', '--panel-header', '--org-flow', '--org-muted'].forEach((name) => {
                const value = styles.getPropertyValue(name);
                if (value) ghost.style.setProperty(name, value);
            });
            const text = document.createElement('span');
            text.className = 'org-ghost-text';
            const nameEl = document.createElement('span');
            nameEl.className = 'org-ghost-name';
            nameEl.textContent = drag.name;
            const hint = document.createElement('span');
            hint.className = 'org-ghost-hint';
            text.append(nameEl, hint);
            ghost.append(avatarElement(drag.photo, drag.initials, 'org-ghost-avatar'), text);
            return { ghost, hint };
        }

        // Hueco de la vista previa: del mismo tamaño que una tarjeta, con la foto y el nombre de quien quedará ahí
        function buildPlaceholder(drag) {
            const wrapperEl = document.createElement('div');
            wrapperEl.className = 'visual-node-wrapper org-ph-wrapper flex flex-col items-center';
            const slot = document.createElement('div');
            slot.className = 'relative z-10';
            const card = document.createElement('div');
            card.className = 'org-node org-ph-card';
            card.style.height = `${drag.cardHeight}px`;
            const head = document.createElement('span');
            head.className = 'org-node-head';
            const avatarWrap = document.createElement('span');
            avatarWrap.className = 'org-node-avatar';
            avatarWrap.append(avatarElement(drag.photo, drag.initials, drag.photo ? '' : 'org-node-initials'));
            const names = document.createElement('span');
            names.className = 'org-node-names';
            const nameEl = document.createElement('span');
            nameEl.className = 'org-node-name';
            nameEl.textContent = drag.name;
            names.append(nameEl);
            head.append(avatarWrap, names);
            const text = document.createElement('span');
            text.className = 'org-ph-text';
            text.textContent = drag.teamSize > 0 ? 'Aquí quedará con su equipo' : 'Aquí quedará';
            card.append(head, text);
            slot.append(card);
            wrapperEl.append(slot);
            return wrapperEl;
        }

        // Pone el hueco en el árbol (con los mismos contenedores que una tarjeta real) y devuelve cómo quitarlo
        function applyPreview(drag, targetCard, placement) {
            const targetWrapper = targetCard.closest('.visual-node-wrapper');
            const ph = buildPlaceholder(drag);
            const make = (className) => Object.assign(document.createElement('div'), { className });
            const undo = [];
            if (placement === 'child') {
                let kids = targetWrapper.querySelector(':scope > .org-children');
                if (!kids) {
                    const stem = make('org-stem');
                    kids = make('org-children');
                    targetWrapper.append(stem, kids);
                    const created = kids;
                    undo.push(() => { stem.remove(); created.remove(); });
                }
                const child = make('org-child org-ph-child');
                child.append(ph);
                kids.append(child);
                undo.push(() => child.remove());
            } else if (placement === 'before' || placement === 'after') {
                const own = targetWrapper.parentElement;
                const child = make('org-child org-ph-child');
                child.append(ph);
                own.parentElement.insertBefore(child, placement === 'before' ? own : own.nextSibling);
                undo.push(() => child.remove());
            } else {
                // Arriba: el hueco ocupa su lugar y la persona (con toda su rama) baja a su equipo
                const parent = targetWrapper.parentElement;
                const stem = make('org-stem');
                const kids = make('org-children');
                const child = make('org-child');
                parent.insertBefore(ph, targetWrapper);
                child.append(targetWrapper);
                kids.append(child);
                ph.append(stem, kids);
                undo.push(() => { parent.insertBefore(targetWrapper, ph); ph.remove(); });
            }
            return { wrapper: ph, restore: () => undo.reverse().forEach((fn) => fn()) };
        }

        function setPreview(targetCard, placement) {
            const targetId = parseInt(targetCard.dataset.id);
            if (preview && preview.targetId === targetId && preview.placement === placement) return;
            const first = reduceMotion ? null : measureSlots();
            const anchorSlot = targetCard.parentElement;
            const anchorRect = anchorSlot.getBoundingClientRect();
            const travelling = Boolean(preview);
            stopSlides();
            if (preview) preview.restore();
            preview = { targetId, placement, ...applyPreview(nodeDrag, targetCard, placement) };
            anchorTo(anchorSlot, anchorRect);
            slideFrom(first, SLIDE_MS);
            // Si ya había un hueco, se desliza al nuevo lugar; si no, aparece suave
            if (!travelling && !reduceMotion) {
                slotOf(preview.wrapper).animate([{ opacity: 0, transform: 'scale(.94)' }, { opacity: 1, transform: 'scale(1)' }], { duration: 220, easing: EASE }).id = 'org-ph-in';
            }
        }

        function clearPreview(animate = true) {
            if (!preview) return;
            const first = animate && !reduceMotion ? measureSlots() : null;
            const anchorSlot = slotOfId(preview.targetId);
            const anchorRect = anchorSlot ? anchorSlot.getBoundingClientRect() : null;
            stopSlides();
            preview.restore();
            preview = null;
            if (animate) {
                anchorTo(anchorSlot, anchorRect);
                slideFrom(first, SLIDE_MS);
            } else {
                drawLines();
            }
        }

        function mark(el, className) {
            if (marked && (marked.el !== el || marked.className !== className)) marked.el.classList.remove(marked.className);
            marked = el ? { el, className } : null;
            if (el) el.classList.add(className);
        }

        function beginNodeDrag(press, point) {
            clearTimeout(press.timer);
            isPanning = false;
            const source = press.source;
            const el = source === 'tree' ? press.card : press.row;
            const branch = source === 'tree' ? press.card.closest('.visual-node-wrapper') : null;
            // Su propia rama (ella y su equipo) no puede recibirla
            const blocked = new Set([press.id]);
            if (branch) branch.querySelectorAll('.org-node[data-id]').forEach((card) => blocked.add(parseInt(card.dataset.id)));
            const sample = document.querySelector('#org-tree-wrapper .js-org-card');

            nodeDrag = {
                ...press,
                name: el.dataset.name || '',
                initials: el.dataset.initials || '?',
                photo: source === 'tree' ? (press.card.dataset.photo || '') : '',
                origin: (source === 'tree' ? press.card.parentElement : press.row).getBoundingClientRect(),
                cardHeight: (source === 'tree' ? press.card.offsetHeight : sample?.offsetHeight) || 200,
                teamSize: blocked.size - 1,
                branch,
                blocked,
                drop: null,
                zone: false,
            };
            Object.assign(nodeDrag, buildGhost(nodeDrag));
            nodePress = null;
            document.body.append(nodeDrag.ghost);
            if (!reduceMotion) nodeDrag.ghost.animate([{ opacity: 0, scale: '.94' }, { opacity: 1, scale: '1' }], { duration: 160, easing: EASE });
            branch?.classList.add('org-dragging');
            press.row?.classList.add('is-dragging-row');
            container.classList.add('org-is-dragging');
            if (source === 'tree') container.classList.add('org-drag-from-tree');
            window.dispatchEvent(new CustomEvent('org-drag-start', { detail: { source } }));
            placeGhost(point);
            showHint('', DEFAULT_HINT);
            drawLines();
            evaluateDrop();
            edgeFrame = requestAnimationFrame(edgePan);
        }

        function placeGhost(point) {
            if (nodeDrag) nodeDrag.ghost.style.transform = `translate(${point.x + 18}px, ${point.y + 18}px)`;
        }

        function showHint(state, message) {
            if (!nodeDrag) return;
            nodeDrag.ghost.dataset.state = state;
            if (nodeDrag.hint.textContent !== message) nodeDrag.hint.textContent = message;
        }

        function nearPreview(point) {
            if (!preview) return false;
            return [slotOf(preview.wrapper), slotOfId(preview.targetId)].some((el) => {
                if (!el) return false;
                const r = el.getBoundingClientRect();
                return point.x > r.left - 40 && point.x < r.right + 40 && point.y > r.top - 40 && point.y < r.bottom + 40;
            });
        }

        // Qué pasaría al soltar aquí: se calcula una vez por cuadro con lo que está bajo el puntero
        function evaluateDrop() {
            hitFrame = null;
            const drag = nodeDrag;
            if (!drag) return;
            const point = lastPointer;
            const under = document.elementFromPoint(point.x, point.y);
            const side = under ? under.closest('.org-side') : null;
            container.querySelector('.org-side.org-drop-zone')?.classList.remove('org-drop-zone');
            drag.zone = false;

            if (side) {
                clearPreview();
                drag.drop = null;
                mark(null);
                if (drag.source === 'tree') {
                    side.classList.add('org-drop-zone');
                    drag.zone = true;
                    showHint('ok', drag.teamSize > 0 ? 'Saldrá del organigrama y su equipo pasará a su jefe' : 'Saldrá del organigrama');
                } else {
                    showHint('', 'Suéltala sobre el organigrama');
                }
                return;
            }

            // Sobre su propio hueco: todo se queda como está
            if (preview && under && under.closest('.org-ph-wrapper > .relative')) return;

            const card = under ? under.closest('#org-tree-wrapper .org-node') : null;
            if (card && card.dataset.id && !card.classList.contains('org-ph-card')) {
                const id = parseInt(card.dataset.id);
                if (id === drag.id) {
                    clearPreview();
                    drag.drop = null;
                    mark(null);
                    showHint('', DEFAULT_HINT);
                    return;
                }
                if (drag.blocked.has(id)) {
                    clearPreview();
                    drag.drop = null;
                    mark(card, 'org-drop-bad');
                    showHint('bad', 'No puede quedar dentro de su propio equipo');
                    return;
                }
                const r = card.getBoundingClientRect();
                const rx = (point.x - r.left) / r.width;
                const ry = (point.y - r.top) / r.height;
                const targetSuperior = superiorIdOf(card);
                let placement = 'child';
                if (ry < 0.3) placement = 'above';
                else if (targetSuperior !== null && rx < 0.2) placement = 'before';
                else if (targetSuperior !== null && rx > 0.8) placement = 'after';

                // Lo que no cambiaría nada no muestra hueco
                const ownChild = drag.branch && drag.branch.parentElement.classList.contains('org-child') ? drag.branch.parentElement : null;
                const targetChild = card.closest('.visual-node-wrapper').parentElement;
                const unchanged = (placement === 'child' && id === drag.from)
                    || (placement === 'before' && ownChild && neighbourChild(targetChild, 'previousElementSibling') === ownChild)
                    || (placement === 'after' && ownChild && neighbourChild(targetChild, 'nextElementSibling') === ownChild);
                if (unchanged) {
                    clearPreview();
                    drag.drop = null;
                    mark(null);
                    showHint('', placement === 'child' ? `Ya reporta a ${card.dataset.name}` : 'Ya está en ese lugar');
                    return;
                }

                mark(card, 'org-drop-target');
                drag.drop = { targetId: id, placement, targetSuperior };
                setPreview(card, placement);
                showHint('ok', HINTS[placement](card.dataset.name));
                return;
            }

            // Fuera de las tarjetas: el hueco se queda mientras el puntero siga cerca de él o de su tarjeta
            if (nearPreview(point)) return;
            clearPreview();
            drag.drop = null;
            mark(null);
            showHint('', DEFAULT_HINT);
        }

        // Cerca de las orillas el organigrama se recorre solo, para llevar a la persona a una rama que no se ve
        function edgePan() {
            if (!nodeDrag) return;
            const rect = container.getBoundingClientRect();
            const margin = 60;
            const speed = 12;
            let vx = 0;
            let vy = 0;
            if (lastPointer.x < rect.left + margin) vx = speed;
            else if (lastPointer.x > rect.right - margin) vx = -speed;
            if (lastPointer.y < rect.top + margin) vy = speed;
            else if (lastPointer.y > rect.bottom - margin) vy = -speed;
            const overPanel = document.elementFromPoint(lastPointer.x, lastPointer.y)?.closest('[data-org-panel]');
            if ((vx || vy) && !overPanel) {
                const k = unit() * scale;
                translateX += vx / k;
                translateY += vy / k;
                updateTransform();
                if (!hitFrame) hitFrame = requestAnimationFrame(evaluateDrop);
            }
            edgeFrame = requestAnimationFrame(edgePan);
        }

        // La tarjeta que seguía al cursor vuela a donde va (su hueco, «Sin asignar» o de regreso a su lugar) y se desvanece
        function flyGhost(ghost, rect, instant) {
            if (instant || reduceMotion || !rect) {
                ghost.remove();
                return;
            }
            const from = ghost.getBoundingClientRect();
            const shrink = Math.max(0.6, Math.min(1, rect.width / from.width));
            const flight = ghost.animate([
                { transform: `translate(${from.left}px, ${from.top}px)`, opacity: 1 },
                { transform: `translate(${rect.left}px, ${rect.top}px) scale(${shrink})`, opacity: 0 },
            ], { duration: 260, easing: EASE, fill: 'forwards' });
            flight.onfinish = () => ghost.remove();
            setTimeout(() => ghost.remove(), 500);
        }

        function endNodeDrag(drop, instant = false) {
            const drag = nodeDrag;
            if (!drag) return;
            nodeDrag = null;
            cancelAnimationFrame(edgeFrame);
            if (hitFrame) cancelAnimationFrame(hitFrame);
            hitFrame = null;
            mark(null);
            container.querySelector('.org-side.org-drop-zone')?.classList.remove('org-drop-zone');
            container.classList.remove('org-is-dragging', 'org-drag-from-tree');
            suppressNodeClick = true;
            setTimeout(() => { suppressNodeClick = false; }, 0);

            const wire = wireComponent();
            const action = drop && wire ? (drag.zone ? 'remove' : (drag.drop && preview ? 'place' : null)) : null;
            const release = () => {
                drag.branch?.classList.remove('org-dragging');
                drag.row?.classList.remove('is-dragging-row');
                window.dispatchEvent(new CustomEvent('org-drag-end'));
            };

            if (!action) {
                // Se cancela: la tarjeta regresa a su lugar y el hueco se cierra
                flyGhost(drag.ghost, drag.origin, instant);
                release();
                clearPreview(!instant);
                return;
            }

            let request;
            if (action === 'place') {
                const phSlot = slotOf(preview.wrapper);
                phSlot.querySelector('.org-ph-card')?.classList.add('is-saving');
                flyGhost(drag.ghost, phSlot.getBoundingClientRect(), false);
                const { targetId, placement, targetSuperior } = drag.drop;
                pending = { id: drag.id, placement, targetId, expectSuperior: placement === 'child' ? targetId : targetSuperior, anchorId: targetId };
                request = wire.call('placeUserInChart', drag.id, targetId, placement, drag.from, targetSuperior);
            } else {
                flyGhost(drag.ghost, container.querySelector('.org-side-drop')?.getBoundingClientRect() || null, false);
                pending = { id: drag.id, placement: 'remove', anchorId: drag.from };
                request = wire.call('removeUserFromChart', drag.id, drag.from);
            }
            Promise.resolve(request).finally(() => {
                // Si no llegó la respuesta (sin conexión), se deshace la vista previa
                if (pending) {
                    pending = null;
                    clearPreview();
                }
                release();
                drawLines();
            });
        }

        function cancelNodeDrag() {
            if (nodePress) { clearTimeout(nodePress.timer); nodePress = null; }
            if (nodeDrag) endNodeDrag(false);
        }

        // Justo antes de que Livewire vuelva a dibujar: se guarda dónde está cada tarjeta (la persona movida, en su hueco) y
        // se quita la vista previa, para que Livewire compare contra el árbol original
        function beforeMorph() {
            if (nodeDrag) endNodeDrag(false, true);
            if (nodePress) { clearTimeout(nodePress.timer); nodePress = null; }
            rememberPositions();
            if (pending) {
                const phSlot = preview ? slotOf(preview.wrapper) : null;
                landing = { ...pending, rect: phSlot ? phSlot.getBoundingClientRect() : null };
                if (flipBefore && landing.rect) flipBefore.set(String(pending.id), landing.rect);
                pending = null;
            }
            stopSlides();
            if (preview) {
                preview.restore();
                preview = null;
            }
        }

        window.addEventListener('mousemove', (e) => {
            lastPointer = { x: e.clientX, y: e.clientY };
            if (nodeDrag) {
                placeGhost(lastPointer);
                if (!hitFrame) hitFrame = requestAnimationFrame(evaluateDrop);
                return;
            }
            if (!nodePress) return;
            const distance = Math.hypot(e.clientX - nodePress.x, e.clientY - nodePress.y);
            if (nodePress.longPress) {
                // Se movió antes de cumplir el tiempo: es el gesto de mover el organigrama
                if (distance > 6) { clearTimeout(nodePress.timer); nodePress = null; }
            } else if (distance > 4) {
                beginNodeDrag(nodePress, lastPointer);
            }
        });
        window.addEventListener('mouseup', () => {
            if (nodePress) { clearTimeout(nodePress.timer); nodePress = null; }
            if (nodeDrag) endNodeDrag(true);
        });
        window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && nodeDrag) endNodeDrag(false); });
        window.addEventListener('blur', () => cancelNodeDrag());
        // Al soltar no se abre la ficha de la persona (el clic que dispara el navegador se descarta)
        container.addEventListener('click', (e) => {
            if (suppressNodeClick) { e.preventDefault(); e.stopImmediatePropagation(); suppressNodeClick = false; }
        }, true);

        // ============================================================
        // BOTÓN DE PANTALLA COMPLETA
        // ============================================================
        const fullscreenBtn = document.getElementById('fullscreen-toggle');
        const iconExpand = document.getElementById('fullscreen-icon-expand');
        const iconCompress = document.getElementById('fullscreen-icon-compress');

        if (fullscreenBtn && container) {
            const setFullscreenMode = function(isFullscreen) {
                document.body.classList.toggle('org-chart-fullscreen', isFullscreen);
                iconExpand.style.display = isFullscreen ? 'none' : 'block';
                iconCompress.style.display = isFullscreen ? 'block' : 'none';
                fullscreenBtn.setAttribute('aria-label', isFullscreen ? 'Salir de pantalla completa' : 'Ver en pantalla completa');
                if (isFullscreen) setTimeout(resetView, 100);
            };

            fullscreenBtn.addEventListener('click', function() {
                setFullscreenMode(!document.body.classList.contains('org-chart-fullscreen'));
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.body.classList.contains('org-chart-fullscreen')) {
                    setFullscreenMode(false);
                }
            });
        }
    };
    if (document.getElementById('org-tree-container')) orgChartBoot(); else document.addEventListener('DOMContentLoaded', orgChartBoot);
</script>
@endpush
