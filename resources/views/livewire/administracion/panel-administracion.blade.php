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
        isFullscreen: false,
        scaledContentHeight: null,
        scaleObserver: null,
        fullscreenHandler: null,
        init() {
            this.fullscreenHandler = () => { this.isFullscreen = Boolean(document.fullscreenElement); };
            document.addEventListener('fullscreenchange', this.fullscreenHandler);
            this.$nextTick(() => window.dispatchEvent(new CustomEvent('organization-chart-shown')));
            this.$nextTick(() => {
                this.scaleObserver = new ResizeObserver(() => this.syncScaledContentHeight());
                this.scaleObserver.observe(this.$refs.scaledMain);
                this.syncScaledContentHeight();
            });
        },
        destroy() {
            this.scaleObserver?.disconnect();
            document.removeEventListener('fullscreenchange', this.fullscreenHandler);
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
        async toggleFullscreen() {
            if (document.fullscreenElement) await document.exitFullscreen();
            else await this.$root.requestFullscreen();
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
    <x-panel-styles />
    <x-home-styles />
    <x-administration-modal-styles />

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
        .unassigned-users-scrollbar::-webkit-scrollbar { width: 3px; height: 3px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .unassigned-users-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B !important; border-radius: 9999px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-thumb:hover { background: #1A3A6B !important; }
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
            background: #1A3A6B;
            pointer-events: none;
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
            background: #1A3A6B !important;
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
        /* Ventana de usuario: mismas reglas del modal de Usuarios (blanco, bordes #d4d4d8). */
        .org-user-modal .hierarchy-selection-list {
            min-height: 10rem;
            max-height: 12rem;
            padding: 0.5rem;
            border: 1px solid #d4d4d8;
            border-radius: 0.75rem;
            background-color: #fff;
            color: #000;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #f4f4f5;
        }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar { width: 6px; }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar-track { background: #f4f4f5; border-radius: 9999px; }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }


        /* Conectores del organigrama: línea de 2px, centrada exactamente en cada tarjeta, con esquinas redondeadas de 12px
           en la primera y la última rama. Cada .org-child mide lo que su tarjeta más 14px a cada lado, así las barras de
           hermanos se tocan sin huecos. */
        [data-panel] { --org-line: #a8b0bd; }
        html.module-dark-theme [data-panel] { --org-line: #3f3f46; }
        .org-stem { width: 2px; height: 24px; background: var(--org-line); flex-shrink: 0; }
        .org-children { display: flex; align-items: flex-start; justify-content: center; }
        .org-child { position: relative; display: flex; flex-direction: column; align-items: center; padding: 24px 14px 0; }
        .org-child::before, .org-child::after { content: ''; position: absolute; top: 0; height: 24px; box-sizing: border-box; }
        .org-child::before { left: 0; right: calc(50% - 1px); border-top: 2px solid var(--org-line); }
        .org-child::after { left: calc(50% - 1px); right: 0; border-top: 2px solid var(--org-line); border-left: 2px solid var(--org-line); }
        .org-child:only-child::before { display: none; }
        .org-child:only-child::after { border-top: 0; }
        .org-child:first-child:not(:only-child)::before { display: none; }
        .org-child:first-child:not(:only-child)::after { border-top-left-radius: 12px; }
        .org-child:last-child:not(:only-child)::after { display: none; }
        .org-child:last-child:not(:only-child)::before { border-right: 2px solid var(--org-line); border-top-right-radius: 12px; }


        /* Jefaturas de otra área (al filtrar por área): borde punteado y tono distinto para ubicar la rama. */
        .org-node.org-node-outside { border-style: dashed; border-color: #64748b; background-color: var(--panel-subtle); }
        @keyframes org-node-flash { 0%, 100% { box-shadow: 0 0 0 0 rgba(106, 169, 255, 0); } 30% { box-shadow: 0 0 0 6px rgba(106, 169, 255, 0.55); } }
        .org-node.org-node-flash { animation: org-node-flash 1.6s ease-out 1; border-color: #6aa9ff; }

        /* Estilo para el contenedor en modo fullscreen */
        body.org-chart-fullscreen {
            overflow: hidden;
        }
        body.org-chart-fullscreen #org-tree-container {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9000 !important;
            width: 100vw !important;
            max-height: none !important;
            height: 100vh !important;
            border-radius: 10px !important;
            border: none !important;
            padding: 20px !important;
        }

        /* Botón fullscreen: cuadrado y sin contorno azul en hover/focus */
        #search-results,
        #physical-area-results { scrollbar-width: auto; scrollbar-color: auto; }
        #search-results::-webkit-scrollbar,
        #physical-area-results::-webkit-scrollbar { width: 7px; height: 7px; }
        #search-results::-webkit-scrollbar-track,
        #physical-area-results::-webkit-scrollbar-track { background: transparent; }
        #search-results::-webkit-scrollbar-thumb,
        #physical-area-results::-webkit-scrollbar-thumb { background: #1A3A6B; border: 2px solid transparent; border-radius: 9999px; background-clip: padding-box; }
        #search-results::-webkit-scrollbar-button,
        #physical-area-results::-webkit-scrollbar-button { display: none; width: 0; height: 0; }

        #fullscreen-toggle {
            outline: none !important;
            box-shadow: none !important;
            border: 1px solid var(--panel-border);
            width: 50px;
            height: 50px;
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
        html.module-dark-theme .administration-record-scroll-thumb {
            background: #6aa9ff;
        }
    </style>

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="w-full">
    <main x-ref="scaledMain" class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
    <!-- Cabecera: mismo patrón que el inicio (cuadro con ícono, título azul, subtítulo y datos a la derecha) -->
    <section class="mb-[30px] flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
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
    @include('livewire.administracion.partials.organization-directory')
    @endunless

    @if ($orgChartPage)
    <!-- Organigrama con padding de 80px en todos los lados -->
    <div id="organigrama-principal" class="flex flex-col gap-[20px]">
        <section class="panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Resumen del organigrama">
            <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                <div class="min-w-0">
                    <p class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m-6 4h12M6 12v4m12-4v4M3 16h6v4H3zm12 0h6v4h-6zM9 8h6v4H9z" /></svg><span>Resumen</span></p>
                    <p class="mt-[15px] leading-none">Estado actual de la estructura jerárquica de la organización.</p>
                </div>
                <span class="leading-none">{{ $orgChartStats['in_tree'] ?? 0 }} de {{ $totalUsers ?? 0 }} en el árbol</span>
            </header>
            <div class="-mb-px -mr-px grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5">
                <div class="panel-border block border-b border-r p-[20px]">
                    <span class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg><span class="min-w-0">Usuarios totales</span></span>
                    <span class="mt-[15px] block truncate leading-none">{{ $totalUsers ?? 0 }}</span>
                    <span class="mt-[15px] block truncate leading-none">Registrados</span>
                </div>
                <div class="panel-border block border-b border-r p-[20px]">
                    <span class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m-6 4h12M6 12v4m12-4v4M3 16h6v4H3zm12 0h6v4h-6zM9 8h6v4H9z" /></svg><span class="min-w-0">En el árbol</span></span>
                    <span class="mt-[15px] block truncate leading-none">{{ $orgChartStats['in_tree'] ?? 0 }}</span>
                    <span class="mt-[15px] block truncate leading-none">Con jefe o equipo</span>
                </div>
                <div class="panel-border block border-b border-r p-[20px]">
                    <span class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8 3 12l4 4m10-8 4 4-4 4M4 12h16" /></svg><span class="min-w-0">Relaciones</span></span>
                    <span class="mt-[15px] block truncate leading-none">{{ $orgChartStats['relations'] ?? 0 }}</span>
                    <span class="mt-[15px] block truncate leading-none">Líneas de mando</span>
                </div>
                <div class="panel-border block border-b border-r p-[20px]">
                    <span class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg><span class="min-w-0">Roles</span></span>
                    <span class="mt-[15px] block truncate leading-none">{{ $totalRoles ?? 0 }}</span>
                    <span class="mt-[15px] block truncate leading-none">Perfiles configurados</span>
                </div>
                <div class="panel-border block border-b border-r p-[20px]">
                    <span class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg><span class="min-w-0">Permisos</span></span>
                    <span class="mt-[15px] block truncate leading-none">30</span>
                    <span class="mt-[15px] block truncate leading-none">Reglas de acceso</span>
                </div>
            </div>
        </section>

        
        <!-- Grid con ancho mínimo para evitar deformación -->
        <div class="grid min-w-0 grid-cols-1 gap-6 p-0 xl:grid-cols-3" style="padding: 0;">

            <!-- Árbol jerárquico -->
            <section class="panel-surface panel-border overflow-hidden rounded-xl border xl:col-span-2" aria-label="Estructura jerárquica">
            <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                <div class="min-w-0">
                    <p class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m-6 4h12M6 12v4m12-4v4M3 16h6v4H3zm12 0h6v4h-6zM9 8h6v4H9z" /></svg><span>Estructura jerárquica</span></p>
                    <p class="mt-[15px] leading-none">Arrastra para mover, usa la rueda para acercar y haz doble clic para restablecer.</p>
                </div>
                
            </header>
            <div>
                @if (($orgChartStats['cycles_detected'] ?? 0) > 0)
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[15px] text-red-700">
                        Se detectaron {{ $orgChartStats['cycles_detected'] }} ciclo(s) en las relaciones jerárquicas.
                        El árbol se renderiza de forma segura omitiendo ramas circulares.
                    </div>
                @endif

                <!-- Contenedor con pan y zoom (siempre visible) -->
                <div id="org-tree-container" class="relative overflow-hidden" style="height: 663px; background: var(--panel-surface); touch-action: none;">

                    <!-- LEYENDA (siempre visible) -->
                    <div class="panel-surface panel-border absolute top-0 left-0 rounded-br-xl border-b border-r p-5" style="z-index: 30;">
                        <div class="text-[15px] flex flex-col items-start gap-[10px]">
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #1e3a8a;"></span> Rol</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #059669;"></span> Puesto</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #7c3aed;"></span> Área</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #d97706;"></span> Múltiples jefes</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #64748b;"></span> Fuera del área (jefatura)</div>
                        </div>
                    </div>

                    <!-- BUSCADOR + SELECT + BOTÓN FULLSCREEN (siempre visibles) -->
                    <div class="absolute top-0 right-0 p-5 flex items-center gap-5" style="z-index: 30;">
                        <!-- Buscador -->
                        <div style="position: relative; flex: 1;">
                            <input 
                                id="node-search-input"
                                type="text" 
                                placeholder="Buscar colaborador..."
                                class="panel-surface panel-border panel-text rounded-lg border text-[15px]"
                                style="height: 50px; min-width: 220px; width: 100%; padding: 0 16px; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30;"
                                autocomplete="off"
                            >
                            <div 
                                id="search-results"
                                style="position: absolute; top: calc(100% + 4px); right: 0; width: 400px; max-width: calc(100vw - 80px); background: var(--panel-surface); border: 1px solid var(--panel-border); border-radius: 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 100;"
                            ></div>
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
                                class="panel-surface panel-border panel-text h-[50px] w-full rounded-lg border px-4 pr-10 text-[15px] focus:outline-none focus:ring-0"
                                autocomplete="off" aria-label="Buscar o filtrar por área">
                            <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.areaInput.focus())"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center focus:outline-none" tabindex="-1" aria-label="Mostrar áreas">
                                <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                            </button>
                            <div x-cloak x-show="open" id="physical-area-results" class="panel-surface panel-border absolute left-0 right-0 top-[54px] max-h-[300px] overflow-y-auto rounded-lg border" style="z-index: 100;">
                                <button type="button" @mousedown.prevent="choose('')" class="home-row panel-border block w-full border-b px-4 py-3 text-left text-[15px]">Todas las áreas</button>
                                <template x-for="area in filteredAreas" :key="area.id"><button type="button" @mousedown.prevent="choose(area.id)" x-text="area.name" class="home-row panel-border block w-full border-b px-4 py-3 text-left text-[15px]"></button></template>
                                <div x-show="filteredAreas.length === 0" class="px-4 py-3 text-center text-sm">No se encontraron áreas</div>
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
                    </div>

                    <!-- INSTRUCCIONES (siempre visibles) -->
                    <div class="panel-surface panel-border absolute bottom-0 right-0 rounded-tl-xl border-l border-t p-5" style="z-index: 30;">
                        <div class="text-[15px]">
                            Arrastra para mover · Rueda para zoom · Doble clic para reset
                        </div>
                    </div>

                    <!-- CONTENIDO PRINCIPAL: árbol o mensaje sin datos (profesional) -->
                    @if (count($orgChartTree) > 0)
                        <div id="org-tree-wrapper" class="origin-top-left" style="transform: scale(1) translate(0px, 0px); cursor: grab; width: max-content; min-width: 1000px; padding: 20px;">
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
                </div>
            </div>
            </section>

            <!-- Usuarios sin asignar -->
            <section class="panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Usuarios sin asignar">
                <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                    <div class="min-w-0">
                        <p class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg><span>Usuarios sin asignar</span></p>
                        <p class="mt-[15px] leading-none">Sin jefe, puesto o área asignada.</p>
                    </div>
                    <span class="leading-none">{{ count($unassignedUsers) }}</span>
                </header>
                <div class="unassigned-users-scrollbar" style="height: 663px; overflow-y: auto; padding: 15px 20px;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    @if (count($unassignedUsers) > 0)
                        <ul style="display: flex; flex-direction: column; gap: 15px; margin: 0; padding: 0; list-style: none;"
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
                                    $createdAt = ! empty($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->locale('es') : null;
                                @endphp
                                <li style="list-style: none;" :style="isHidden({{ (int) $user['id'] }}) ? 'order: 1; opacity: 0.5' : 'order: 0'">
                                    <div wire:key="unassigned-user-{{ $user['id'] }}" class="home-row panel-border panel-surface flex w-full items-stretch gap-[20px] rounded-xl border p-[20px]">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-xs font-semibold text-white">{{ $initials !== '' ? $initials : '?' }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="home-link block h-[15px] truncate leading-none" title="{{ $user['name'] }}">{{ $user['name'] }}</span>
                                            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none"><svg class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg><span class="truncate" title="{{ mb_strtolower((string) $user['email']) }}">{{ mb_strtolower((string) $user['email']) }}</span></span>
                                            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none"><svg class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg><span class="truncate">{{ $createdAt ? 'Creado el '.$createdAt->translatedFormat('d \\d\\e F \\d\\e Y') : 'Fecha de creación no disponible' }}</span></span>
                                            <span class="mt-[15px] flex h-[15px] items-center gap-[10px] leading-none"><svg class="h-[15px] w-[15px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg><span class="truncate">{{ $createdAt ? 'Hace '.$createdAt->diffForHumans(now(), true).' en el sistema' : 'Antigüedad no disponible' }}</span></span>
                                        </span>
                                        <span class="panel-border flex shrink-0 flex-col items-center justify-between self-stretch border-l pl-[20px]">
                                            <button type="button" wire:click="openUserManagement('editar', {{ (int) $user['id'] }})" class="home-link flex h-4 w-4 items-center justify-center focus:outline-none focus:ring-0" aria-label="Editar {{ $user['name'] }}" title="Editar {{ $user['name'] }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                                            </button>
                                            <button type="button" @click="toggle({{ (int) $user['id'] }})" class="home-link flex h-4 w-4 items-center justify-center focus:outline-none focus:ring-0" :aria-label="isHidden({{ (int) $user['id'] }}) ? 'Mostrar {{ $user['name'] }}' : 'Ocultar {{ $user['name'] }}'" :title="isHidden({{ (int) $user['id'] }}) ? 'Mostrar' : 'Ocultar al final de la lista'">
                                                <svg x-show="!isHidden({{ (int) $user['id'] }})" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                                <svg x-show="isHidden({{ (int) $user['id'] }})" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </button>
                                            <button type="button" wire:click="openUserManagement('eliminar', {{ (int) $user['id'] }})" class="flex h-4 w-4 items-center justify-center text-red-600 focus:outline-none focus:ring-0" aria-label="Eliminar {{ $user['name'] }}" title="Eliminar {{ $user['name'] }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                                            </button>
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-[15px]" style="line-height: 1.3; margin: 0; padding: 0;">
                            Todos los usuarios tienen jefe, puesto y área asignados.
                        </p>
                    @endif
                </div>
                </div>
            </section>

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
                        <input id="new-job-position-name" type="text" maxlength="255" autocomplete="off" wire:model.defer="newJobPositionName" class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0" placeholder="Ej. Contador Senior" autofocus>
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
                                <input id="edit-job-position-name" type="text" maxlength="255" wire:model.defer="editJobPositionName" @disabled($jobPositionDisabled) class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
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
                            <input id="delete-job-position-confirmation" type="text" wire:model.defer="deleteJobPositionConfirmation" @disabled($jobPositionDisabled) autocomplete="off" placeholder="{{ $jobPositionDisabled ? 'Selecciona primero un puesto' : 'Nombre exacto del puesto' }}" class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
                            <x-input-error for="deleteJobPositionConfirmation" class="mt-2 text-[15px]" />
                        </section>
                    </div>
                @endif
            </x-slot>

            <x-slot name="actions">
                @if ($jobPositionModalTab === 'crear')
                <button type="button" wire:click="closeJobPositionModal"
                    class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveJobPosition"
                    class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-[#1A3A6B] transition hover:bg-[#E7F0FB] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                    <svg wire:loading wire:target="saveJobPosition" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span wire:loading.remove wire:target="saveJobPosition">Guardar</span>
                    <span wire:loading wire:target="saveJobPosition">Guardando...</span>
                </button>
                @else
                    <button type="button" wire:click="closeJobPositionModal"
                        class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0">
                        Cancelar
                    </button>
                    @if ($jobPositionModalTab === 'editar')
                        <button type="button" wire:click="updateJobPosition" wire:loading.attr="disabled" wire:target="updateJobPosition" @disabled($jobPositionDisabled)
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-[#1A3A6B] transition hover:bg-[#E7F0FB] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                            Actualizar
                        </button>
                    @else
                        <button type="button" wire:click="deleteJobPosition" wire:loading.attr="disabled" wire:target="deleteJobPosition" @disabled($jobPositionDisabled)
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
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
                        <input id="new-physical-area-name" type="text" maxlength="255" autocomplete="off" wire:model.defer="newPhysicalAreaName" class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] shadow-none outline-none focus:border-[#B7CEEA] focus:outline-none focus:ring-0" placeholder="Ej. Auditoría" autofocus>
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
                            <input id="edit-physical-area-name" type="text" maxlength="255" wire:model.defer="editPhysicalAreaName" @disabled($physicalAreaDisabled) class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
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
                            <input id="delete-physical-area-confirmation" type="text" wire:model.defer="deletePhysicalAreaConfirmation" @disabled($physicalAreaDisabled) autocomplete="off" placeholder="{{ $physicalAreaDisabled ? 'Selecciona primero un área' : 'Nombre exacto del área' }}" class="mt-[10px] block h-11 w-full rounded-lg border border-[#B7CEEA] bg-transparent px-3 text-[15px] text-[#102A52] outline-none focus:border-[#B7CEEA] focus:ring-0 disabled:cursor-not-allowed disabled:border-[#D5DDE8] disabled:bg-[#E8EBF0] disabled:text-[#98A4B3]">
                            <x-input-error for="deletePhysicalAreaConfirmation" class="mt-2 text-[15px]" />
                        </section>
                    </div>
                @endif
            </x-slot>

            <x-slot name="actions">
                @if ($physicalAreaModalTab === 'crear')
                <button type="button" wire:click="closePhysicalAreaModal"
                    class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="savePhysicalArea"
                    class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-[#1A3A6B] transition hover:bg-[#E7F0FB] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60">
                    <svg wire:loading wire:target="savePhysicalArea" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span wire:loading.remove wire:target="savePhysicalArea">Guardar</span>
                    <span wire:loading wire:target="savePhysicalArea">Guardando...</span>
                </button>
                @else
                    <button type="button" wire:click="closePhysicalAreaModal"
                        class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0">
                        Cancelar
                    </button>
                    @if ($physicalAreaModalTab === 'editar')
                        <button type="button" wire:click="updatePhysicalArea" wire:loading.attr="disabled" wire:target="updatePhysicalArea" @disabled($physicalAreaDisabled)
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-white px-5 py-3 text-[15px] font-semibold text-[#1A3A6B] transition hover:bg-[#E7F0FB] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
                            Actualizar
                        </button>
                    @else
                        <button type="button" wire:click="deletePhysicalArea" wire:loading.attr="disabled" wire:target="deletePhysicalArea" @disabled($physicalAreaDisabled)
                            class="inline-flex min-w-28 items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50">
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
                class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-[22px] border border-[#1A3A6B] bg-[#F7FAFE] shadow-2xl"
                style="height: min(86vh, 760px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;"
            >
                <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-white/10 bg-[#1A3A6B] p-5">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex min-w-0 flex-col gap-[10px]">
                            <h2 id="permissions-modal-title" class="truncate text-[15px] font-semibold leading-none text-white" style="margin: 0;">
                                Gestionar permisos
                            </h2>
                            <p class="truncate text-[15px] leading-none text-[#C9E1FF]" style="margin: 0;">
                                Accesos vigentes de Administrador y Auxiliar
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closePermissionsModal"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-xl leading-none text-white transition hover:bg-white/20 focus:outline-none focus:ring-0"
                        aria-label="Cerrar"
                    >
                        &times;
                    </button>
                </header>

                <div class="administration-modal-scrollbar organization-card-surface min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
                    <div class="flex flex-col gap-5 p-6 text-[15px]">
                        <div class="rounded-xl border border-[#CAD7E7] bg-transparent p-5">
                            <div class="flex items-start gap-[15px]">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="min-w-0">
                                    <p class="text-[15px] font-semibold text-gray-800">Configuración de acceso actual</p>
                                    <p class="mt-2 text-[15px] leading-6 text-gray-500">
                                        Esta vista refleja las reglas vigentes del sistema. La autorización continúa protegida por sus políticas y middleware actuales.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-[15px] md:grid-cols-2">
                            @foreach ($permissionProfiles as $roleName => $profile)
                                @if ($roles->contains('role', $roleName))
                                    <article class="flex min-w-0 flex-col rounded-xl border border-[#CAD7E7] bg-white/45 p-5" data-permission-role="{{ $roleName }}">
                                        <div class="min-w-0 border-b border-gray-200 pb-[15px]">
                                            <h3 class="truncate text-[15px] font-semibold text-gray-900" title="{{ $roleName }}">{{ $roleName }}</h3>
                                            <span class="mt-[10px] inline-flex max-w-full rounded-full bg-blue-100 px-3 py-1 text-[15px] font-medium text-[#1A3A6B]">
                                                {{ $profile['label'] }}
                                            </span>
                                            <p class="mt-[10px] text-[15px] leading-6 text-gray-500">{{ $profile['description'] }}</p>
                                        </div>

                                        <ul class="mt-[15px] flex flex-col gap-[10px]" aria-label="Permisos de {{ $roleName }}">
                                            @foreach ($profile['permissions'] as $permission)
                                                <li class="flex min-w-0 items-center gap-[10px] rounded-lg border border-gray-200 bg-white px-3 py-2.5">
                                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 truncate text-[15px] text-gray-700" title="{{ $permission }}">{{ $permission }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @php($permissionRole = $roles->firstWhere('role', $roleName))
                                        <button type="button" wire:click="openPermissionRoleEditor({{ $permissionRole->id }})" class="mt-[15px] inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#1A3A6B] px-4 text-[14px] font-semibold text-white hover:bg-[#15305a] focus:outline-none focus:ring-0">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 5 4 4L8 20H4v-4L15 5z" /></svg>
                                            Editar {{ $roleName }}
                                        </button>
                                    </article>
                                @endif
                            @endforeach
                        </div>

                        <section class="rounded-xl border border-[#CAD7E7] bg-transparent p-5">
                            <h3 class="text-[13px] font-bold uppercase leading-5 tracking-[.12em] text-[#1A3A6B]">Grupos de permisos</h3>
                            <div class="mt-[10px] grid grid-cols-1 gap-[10px] sm:grid-cols-2">
                                <button type="button" wire:click="openRoleManagement('crear')" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-[#B7CEEA] bg-white/50 px-4 text-[14px] font-semibold text-[#1A3A6B] hover:bg-[#EEF5FF]">
                                    <span class="text-lg">+</span> Agregar grupo
                                </button>
                                <button type="button" wire:click="openRoleManagement('eliminar')" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-[#B7CEEA] bg-white/50 px-4 text-[14px] font-semibold text-[#1A3A6B] hover:bg-[#EEF5FF]">
                                    Administrar o eliminar grupos
                                </button>
                            </div>
                        </section>
                    </div>
                </div>

                <footer class="flex flex-shrink-0 justify-end border-t border-white/10 bg-[#1A3A6B] p-5">
                    <button
                        type="button"
                        wire:click="closePermissionsModal"
                        class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white transition hover:bg-white/20 focus:outline-none focus:ring-0"
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
    @php($detailInput = 'mt-[20px] block h-11 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:border-zinc-200 disabled:bg-zinc-100 disabled:text-zinc-400')
    @php($detailLabel = 'block text-[15px] font-medium text-gray-700')
    @php($detailValue = 'mt-[20px] flex min-h-[44px] w-full cursor-default items-center break-words rounded-xl border border-zinc-200 bg-white px-[15px] py-[10px] text-[15px] text-black')
    @php($detailSecondaryButton = 'inline-flex min-w-28 items-center justify-center rounded-xl border border-zinc-200 bg-white px-[20px] py-[12px] text-[15px] font-medium text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0')
    @php($detailPrimaryButton = 'inline-flex min-w-28 items-center justify-center gap-2 rounded-xl bg-black px-[20px] py-[12px] text-[15px] font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60')
    <div x-data="{ visible: true }" x-show="visible" @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === 'closeUserDetails') visible = false" @keydown.escape.window="visible = false; $wire.closeUserDetails()" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="user-details-title" data-administration-modal="user-details">
        {{-- Mismo marco que el modal de Usuarios: blanco, bordes grises, 20px de relleno. --}}
        <div @click.outside="visible = false; $wire.closeUserDetails()" class="org-user-modal administration-form-panel relative flex w-full flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl" style="max-width: 620px; height: min(620px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;">

            {{-- ===== ENCABEZADO (fijo) ===== --}}
            <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0" />
                        </svg>
                    </div>
                    <div class="flex min-w-0 flex-col gap-[10px]">
                        <h1 id="user-details-title" class="truncate pb-[3px] text-[15px] font-semibold leading-none text-black" title="{{ $detailName }}" style="margin: 0;">{{ $detailName }}</h1>
                        <p class="truncate pb-[3px] text-[15px] leading-none text-zinc-500" style="margin: 0;">{{ $isEditingUser ? 'Actualiza los datos de acceso y el perfil organizacional.' : 'Detalles del usuario' }}</p>
                    </div>
                </div>
                <button type="button" wire:click="closeUserDetails" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xl leading-none text-zinc-500 transition hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0" aria-label="Cerrar">&times;</button>
            </header>

            @if ($isEditingUser)
                {{-- ===== FORMULARIO DE EDICIÓN ===== --}}
                <form wire:submit="saveSelectedUser" class="flex min-h-0 flex-1 flex-col">
                    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                            <div class="p-[20px] text-[15px]">
                                <div class="flex flex-col gap-[20px]">
                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="mb-[10px] font-semibold text-black">Datos personales</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0">
                                                <label for="edit-name" class="{{ $detailLabel }}">Nombre</label>
                                                <input id="edit-name" type="text" class="{{ $detailInput }}" wire:model="userForm.name">
                                                <x-input-error for="userForm.name" class="mt-2 text-[15px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-last-name" class="{{ $detailLabel }}">Apellido</label>
                                                <input id="edit-last-name" type="text" class="{{ $detailInput }}" wire:model="userForm.last_name">
                                                <x-input-error for="userForm.last_name" class="mt-2 text-[15px]" />
                                            </div>
                                            <div class="min-w-0 sm:col-span-2">
                                                <label for="edit-email" class="{{ $detailLabel }}">Email</label>
                                                <input id="edit-email" type="email" class="{{ $detailInput }}" wire:model="userForm.email">
                                                <x-input-error for="userForm.email" class="mt-2 text-[15px]" />
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="mb-[10px] font-semibold text-black">Perfil organizacional</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0 sm:col-span-2">
                                                <label for="edit-role" class="{{ $detailLabel }}">Rol</label>
                                                <x-administration-search-picker style="margin-top: 20px;" input-id="edit-role" model="userForm.role_id" :selected="$userForm['role_id'] ?? null" :items="$roles->map(fn ($availableRole) => ['id' => $availableRole->id, 'label' => $availableRole->role])" placeholder="Buscar rol..." empty-message="No se encontraron roles." />
                                                <x-input-error for="userForm.role_id" class="mt-2 text-[15px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-position" class="{{ $detailLabel }}">Puesto</label>
                                                <x-administration-search-picker style="margin-top: 20px;" input-id="edit-position" model="userForm.job_position_id" :selected="$userForm['job_position_id'] ?? null" :items="$jobPositions->map(fn ($position) => ['id' => $position->id, 'label' => $position->name, 'meta' => $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo'])" placeholder="Buscar puesto..." empty-message="No se encontraron puestos." />
                                                <x-input-error for="userForm.job_position_id" class="mt-2 text-[15px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-area" class="{{ $detailLabel }}">Área / departamento</label>
                                                <x-administration-search-picker style="margin-top: 20px;" input-id="edit-area" model="userForm.physical_area_id" :selected="$userForm['physical_area_id'] ?? null" :items="$physicalAreas->map(fn ($area) => ['id' => $area->id, 'label' => $area->name])" placeholder="Buscar área o departamento..." empty-message="No se encontraron áreas." />
                                                <x-input-error for="userForm.physical_area_id" class="mt-2 text-[15px]" />
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="mb-[10px] font-semibold text-black">Configuración del checador{{ ($userForm['is_hourly_position'] ?? false) ? ' y nómina' : '' }}</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0 sm:col-span-2">
                                                <label for="edit-employee-id" class="{{ $detailLabel }}">ID del reloj checador</label>
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
                                                        class="absolute right-0 top-0 flex h-11 w-12 items-center justify-center text-zinc-500 transition hover:text-black focus:outline-none focus:ring-0"
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
                                                                class="flex w-full min-w-0 items-center gap-4 px-4 py-3 text-left transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none focus:ring-0"
                                                                role="option"
                                                            >
                                                                <span class="w-20 shrink-0 truncate text-[15px] text-black" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                                                <span class="min-w-0 flex-1 truncate text-[15px] text-black" title="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}">{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <p class="mt-[6px] text-[15px] text-[#71717a]">Selecciona un ID registrado; la sugerencia muestra el nombre detectado por el checador.</p>
                                                <x-input-error for="userForm.employee_id" class="mt-2 text-[15px]" />
                                            </div>

                                            @if ($userForm['is_hourly_position'] ?? false)
                                                <div class="min-w-0">
                                                    <label for="edit-hourly-rate" class="{{ $detailLabel }}">Precio por hora</label>
                                                    <input id="edit-hourly-rate" type="number" step="0.01" class="{{ $detailInput }}" wire:model="userForm.hourly_rate">
                                                    <x-input-error for="userForm.hourly_rate" class="mt-2 text-[15px]" />
                                                </div>
                                                <div class="min-w-0">
                                                    <label for="edit-food-allowance" class="{{ $detailLabel }}">Apoyo económico por día</label>
                                                    <input id="edit-food-allowance" type="number" step="0.01" class="{{ $detailInput }}" wire:model="userForm.food_allowance">
                                                    <x-input-error for="userForm.food_allowance" class="mt-2 text-[15px]" />
                                                </div>
                                            @endif
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="mb-[10px] font-semibold text-black">Jerarquía</h2>
                                        <div class="flex flex-col gap-[20px]">
                                            <div class="min-w-0">
                                                <span id="edit-superiors-label" class="{{ $detailLabel }}">Jefe directo</span>
                                                <p class="mt-[6px] text-[15px] text-[#71717a]">Solo se permite uno y debe estar asignado al organigrama.</p>
                                                <div id="edit-superiors" class="hierarchy-selection-list mt-[14px] block w-full text-[15px]" role="group" aria-labelledby="edit-superiors-label">
                                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-zinc-100">
                                                        <input type="radio" name="edit-superior" wire:click="clearSuperiorSelection" @checked(empty($userForm['superior_ids'])) class="h-4 w-4 shrink-0 border-zinc-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0">
                                                        <span class="text-[15px] text-black">Sin jefe directo</span>
                                                    </label>
                                                    @forelse ($superiorCandidates as $superiorCandidate)
                                                        <div wire:key="superior-candidate-{{ $superiorCandidate->id }}">
                                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-zinc-100">
                                                                <input type="radio" name="edit-superior" value="{{ $superiorCandidate->id }}" wire:click="selectSuperior({{ $superiorCandidate->id }})" @checked(in_array($superiorCandidate->id, $userForm['superior_ids'] ?? [])) class="h-4 w-4 shrink-0 border-zinc-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0">
                                                                <span class="min-w-0 flex-1" title="{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }} — {{ mb_strtolower((string) $superiorCandidate->email) }}">
                                                                    <span class="block truncate text-[15px] text-black">{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }}</span>
                                                                    <span class="mt-[5px] block truncate text-[15px] text-[#71717a]">{{ mb_strtolower((string) $superiorCandidate->email) }}</span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    @empty
                                                        <p class="px-3 py-2.5 text-[15px] text-[#71717a]">No hay colaboradores asignados al organigrama.</p>
                                                    @endforelse
                                                </div>
                                                <x-input-error for="userForm.superior_ids" class="mt-2 text-[15px]" />
                                            </div>

                                            <div class="min-w-0">
                                                <span id="edit-subordinates-label" class="{{ $detailLabel }}">Subordinados directos</span>
                                                <p class="mt-[6px] text-[15px] text-[#71717a]">Solo aparecen personas sin jefe o que ya te reportan directamente.</p>
                                                <div id="edit-subordinates" class="hierarchy-selection-list mt-[14px] block w-full text-[15px]" role="group" aria-labelledby="edit-subordinates-label">
                                                    @forelse ($subordinateCandidates as $subordinateCandidate)
                                                        <div wire:key="subordinate-candidate-{{ $subordinateCandidate->id }}">
                                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-zinc-100">
                                                                <input type="checkbox" value="{{ $subordinateCandidate->id }}" wire:model.live="userForm.subordinate_ids" class="h-4 w-4 shrink-0 rounded border-zinc-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0">
                                                                <span class="min-w-0 flex-1" title="{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }} — {{ mb_strtolower((string) $subordinateCandidate->email) }}">
                                                                    <span class="block truncate text-[15px] text-black">{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }}</span>
                                                                    <span class="mt-[5px] block truncate text-[15px] text-[#71717a]">{{ mb_strtolower((string) $subordinateCandidate->email) }}</span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    @empty
                                                        <p class="px-3 py-2.5 text-[15px] text-[#71717a]">No hay colaboradores disponibles.</p>
                                                    @endforelse
                                                </div>
                                                <x-input-error for="userForm.subordinate_ids" class="mt-2 text-[15px]" />
                                            </div>
                                        </div>
                                    </section>

                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="mb-[10px] font-semibold text-black">Contraseña</h2>
                                        <div class="grid grid-cols-1 gap-[20px] sm:grid-cols-2">
                                            <div class="min-w-0">
                                                <label for="edit-password" class="{{ $detailLabel }}">Nueva contraseña (opcional)</label>
                                                <input id="edit-password" type="password" autocomplete="new-password" class="{{ $detailInput }}" wire:model="userForm.password">
                                                <x-input-error for="userForm.password" class="mt-2 text-[15px]" />
                                            </div>
                                            <div class="min-w-0">
                                                <label for="edit-password-confirmation" class="{{ $detailLabel }}">Confirmar contraseña</label>
                                                <input id="edit-password-confirmation" type="password" autocomplete="new-password" class="{{ $detailInput }}" wire:model="userForm.password_confirmation">
                                                <x-input-error for="userForm.password_confirmation" class="mt-2 text-[15px]" />
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>

                    <footer class="flex flex-shrink-0 justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
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
                {{-- ===== PESTAÑAS (fijas) ===== --}}
                <nav class="flex flex-shrink-0 border-b border-gray-300 bg-[#F3F3F3] px-5" aria-label="Secciones del usuario">
                    @foreach (['datos' => 'Datos', 'eliminar' => 'Eliminar usuario'] as $detailTab => $detailTabLabel)
                        <button type="button" wire:click="setActiveTab('{{ $detailTab }}')" class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $activeTab === $detailTab ? ($detailTab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">{{ $detailTabLabel }}</button>
                    @endforeach
                </nav>

                {{-- ===== CONTENIDO CON SCROLL ===== --}}
                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                        <div class="p-[20px] text-[15px]">
                            @if ($activeTab === 'datos')
                                <div class="flex flex-col gap-[20px]">
                                    <section style="--admin-form-gap: 20px;">
                                        <h2 class="mb-[10px] font-semibold text-black">Datos generales</h2>
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
                                        <h2 class="mb-[10px] font-semibold text-black">Organizacional</h2>
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
                                            <h2 class="mb-[10px] font-semibold text-black">Datos de auxiliar</h2>
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
                                <section style="--admin-form-gap: 20px;">
                                    <h2 class="mb-[10px] font-semibold text-black">¿Estás seguro de eliminar este usuario?</h2>
                                    <p class="text-[15px] leading-relaxed text-black">Esta acción es irreversible y eliminará permanentemente al usuario del sistema.</p>
                                    <p class="mt-[20px] text-[15px] leading-relaxed text-black">Para confirmar, escribe el nombre completo del usuario: <span class="text-red-700">{{ $detailName }}</span></p>
                                    <label for="delete-user-confirmation" class="mt-[20px] {{ $detailLabel }}">Nombre completo</label>
                                    <input id="delete-user-confirmation" type="text" autocomplete="off" wire:model="deleteConfirmationName" class="{{ $detailInput }}" placeholder="Nombre completo del usuario">
                                    @error('deleteConfirmationName')
                                        <p class="mt-2 text-[15px] text-red-600" style="margin-bottom: 0;">{{ $message }}</p>
                                    @enderror
                                    <button type="button" wire:click="deleteSelectedUser" class="mt-[20px] inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-[20px] py-[12px] text-[15px] font-medium text-white transition-colors hover:bg-red-700 focus:outline-none focus:ring-0">Eliminar usuario permanentemente</button>
                                </section>
                            @endif
                        </div>
                    </div>
                    <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                {{-- ===== PIE CON BOTONES (fijo) ===== --}}
                <footer class="flex flex-shrink-0 justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
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
    document.addEventListener('DOMContentLoaded', function() {
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

        function updateTransform() {
            wrapper.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
        }

        function resetView() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            updateTransform();
        }

        // Tras cada actualización de Livewire: si cambiaron las personas que se muestran (filtro por área) se parte de la vista
        // inicial; si no, se conserva el movimiento y el zoom. En ambos casos se suelta cualquier arrastre que haya quedado a medias.
        const treeSignature = () => [...document.querySelectorAll('.org-node')].map((el) => el.dataset.id).join(',');
        let lastTreeSignature = treeSignature();
        const afterLivewireUpdate = () => {
            isPanning = false;
            didDrag = false;
            panStartedOnNode = false;
            const signature = treeSignature();
            if (signature !== lastTreeSignature) {
                lastTreeSignature = signature;
                resetView();
            } else {
                updateTransform();
            }
            if (window.equalizeNodeHeights) window.equalizeNodeHeights();
            allNodes = [];
        };
        const hookLivewire = () => window.Livewire?.hook('morphed', () => afterLivewireUpdate());
        if (window.Livewire) hookLivewire(); else document.addEventListener('livewire:init', hookLivewire);
        window.addEventListener('blur', () => { isPanning = false; });

        function centerNode(nodeElement) {
            resetView();
            
            requestAnimationFrame(() => {
                const wrapperRect = wrapper.getBoundingClientRect();
                const nodeRect = nodeElement.getBoundingClientRect();
                
                const nodeCenterX = (nodeRect.left + nodeRect.width / 2) - wrapperRect.left;
                const nodeCenterY = (nodeRect.top + nodeRect.height / 2) - wrapperRect.top;
                
                const containerRect = container.getBoundingClientRect();
                const targetX = (containerRect.width / 2) - (nodeCenterX * scale);
                const targetY = (containerRect.height / 2) - (nodeCenterY * scale);
                
                translateX = targetX;
                translateY = targetY;
                updateTransform();
            });
        }

        container.addEventListener('wheel', function(e) {
            if (e.target.closest('#search-results') || e.target.closest('.search-result-item') || e.target.closest('#physical-area-results')) {
                return;
            }
            
            e.preventDefault();
            const rect = container.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const newScale = Math.min(Math.max(scale * delta, 0.3), 3);
            const dx = (mouseX - translateX) * (1 - newScale / scale);
            const dy = (mouseY - translateY) * (1 - newScale / scale);
            scale = newScale;
            translateX += dx;
            translateY += dy;
            updateTransform();
        }, { passive: false });

        container.addEventListener('mousedown', function(e) {
            if (e.button !== 0 || e.target.closest('#node-search-input, #search-results, .search-result-item, #physical-area-filter, #physical-area-results, #fullscreen-toggle')) {
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
            if (!isPanning) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            if (!didDrag && Math.hypot(dx, dy) < 5) return;
            didDrag = true;
            translateX = startTranslateX + dx;
            translateY = startTranslateY + dy;
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
                searchResults.innerHTML = '<div class="px-4 py-3 text-center text-[15px]">No se encontraron resultados</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = results.map((node) => {
                const avatar = node.photo
                    ? `<img src="${escapeHtml(node.photo)}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">`
                    : `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-xs font-semibold text-white">${escapeHtml(node.initials)}</span>`;
                const details = [node.role, node.position, node.area].filter(Boolean).join(' · ');
                return `<button type="button" class="search-result-item home-row panel-border flex w-full items-center gap-[15px] border-b p-[15px] text-left" data-id="${node.id}">
                    ${avatar}
                    <span class="min-w-0 flex-1">
                        <span class="home-link block h-[15px] truncate leading-none">${escapeHtml(node.name)}</span>
                        <span class="mt-[15px] block h-[15px] truncate leading-none">${escapeHtml(node.email.split('@')[0])}@</span>
                        ${details ? `<span class="mt-[15px] block h-[15px] truncate leading-none">${escapeHtml(details)}</span>` : ''}
                    </span>
                </button>`;
            }).join('');

            searchResults.style.display = 'block';
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
    });
</script>
@endpush
