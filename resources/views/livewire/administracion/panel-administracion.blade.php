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

<div class="w-full min-w-0 space-y-[20px] p-[50px]" style="font-size: 15px; background-color: #F3F3F3;">
    <style hidden>
        .unassigned-users-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .unassigned-users-scrollbar::-webkit-scrollbar { width: 6px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B !important; border-radius: 9999px; }
        .unassigned-users-scrollbar::-webkit-scrollbar-thumb:hover { background: #15305a !important; }

        .administration-modal-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .administration-modal-scrollbar::-webkit-scrollbar { width: 6px; }
        .administration-modal-scrollbar::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .administration-modal-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
        .org-user-modal input,
        .org-user-modal select {
            border-color: #d1d5db;
            border-radius: 0.5rem;
            background-color: #F3F3F3;
            box-shadow: none;
        }
        .org-user-modal .org-modal-fields > div:not(.grid),
        .org-user-modal .org-modal-fields > .grid > div,
        .org-user-modal .org-modal-financial > div {
            min-width: 0;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #F3F3F3;
        }
        .org-user-modal .org-modal-fields p,
        .org-user-modal .org-modal-financial p {
            overflow: visible !important;
            white-space: normal !important;
            text-overflow: clip !important;
            overflow-wrap: anywhere;
        }
        .org-user-modal .hierarchy-selection-card {
            min-width: 0;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background-color: #F3F3F3;
        }
        .org-user-modal .hierarchy-selection-list {
            min-height: 10rem;
            max-height: 12rem;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background-color: #fff;
            color: #1f2937;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar { width: 6px; }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar-track { background: #F3F3F3; border-radius: 9999px; }
        .org-user-modal .hierarchy-selection-list::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }

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
        #physical-area-results { scrollbar-width: thin; scrollbar-color: #1A3A6B #f1f1f1; }
        #search-results::-webkit-scrollbar,
        #physical-area-results::-webkit-scrollbar { width: 4px; height: 4px; }
        #search-results::-webkit-scrollbar-track,
        #physical-area-results::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 9999px; }
        #search-results::-webkit-scrollbar-thumb,
        #physical-area-results::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
        #search-results::-webkit-scrollbar-thumb:hover,
        #physical-area-results::-webkit-scrollbar-thumb:hover { background: #15305a; }

        #fullscreen-toggle {
            outline: none !important;
            box-shadow: none !important;
            border: 1px solid #d1d5db;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background-color: rgba(255,255,255,0.8);
            backdrop-filter: blur(4px);
            transition: background-color 0.2s, border-color 0.2s;
        }
        #fullscreen-toggle:hover,
        #fullscreen-toggle:focus,
        #fullscreen-toggle:active {
            outline: none !important;
            box-shadow: none !important;
            border-color: #d1d5db; /* Sin cambio a azul */
            background-color: #ffffff;
        }
        #fullscreen-toggle svg {
            width: 24px;
            height: 24px;
            color: #1A3A6B;
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
    </style>

    <!-- Centro de gestión siempre visible -->
    <section class="m-0 min-h-[calc(100vh-190px)] w-full bg-[#F3F3F3] pt-0">
        <div class="m-0 w-full bg-[#F3F3F3] pt-0">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-2 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#1A3A6B]"><span class="h-[2px] w-7 bg-[#1A3A6B]"></span>Administración organizacional</div>
                    <div class="flex flex-wrap items-center gap-[10px]">
                        <h1 class="text-[26px] font-bold tracking-tight text-[#102A52]">Centro de organización</h1>
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Sincronizado en tiempo real</span>
                    </div>
                    <p class="mt-2 max-w-3xl text-[13px] leading-6 text-[#55749D]">Administra colaboradores, roles, permisos y dependencias operativas desde un mismo espacio.</p>
                </div>
                <a href="#organigrama-principal" class="inline-flex h-11 shrink-0 items-center justify-center gap-3 rounded-lg bg-[#102A52] px-5 text-[13px] font-semibold text-white focus:outline-none focus:ring-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v12H4zM8 21l4-4 4 4M8 9h8m-4-4v12" /></svg>
                    Ver organigrama
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" /></svg>
                </a>
            </div>

            <div class="mt-[50px] grid grid-flow-col auto-cols-[minmax(240px,1fr)] gap-3 overflow-x-auto overscroll-x-contain pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div class="relative overflow-hidden rounded-[10px] border border-[#CAD7E7] bg-white/45 px-4 py-[14px] backdrop-blur-sm"><span class="absolute inset-y-0 left-0 w-[3px] bg-[#1A3A6B]"></span><div class="flex items-center justify-between gap-3"><span class="text-[10px] font-bold uppercase tracking-[.14em] text-[#55749D]">Colaboradores</span><span class="rounded-full border border-[#DCEAFF] bg-[#EEF5FF]/80 px-2 py-1 text-[9px] font-semibold text-[#1A3A6B]">En línea: {{ $onlineUserCount }}</span></div><div class="mt-3 flex items-end justify-between gap-3"><div class="flex items-baseline gap-2"><strong class="text-[24px] leading-none text-[#102A52]">{{ $totalUsers ?? 0 }}</strong><span class="text-[11px] text-[#55749D]">registrados</span></div><span class="text-[9px] font-bold uppercase tracking-[.12em] text-[#7B96B9]">Equipo</span></div><div class="mt-3 h-[2px] overflow-hidden bg-[#DCEAFF]"><span class="block h-full w-[72%] bg-[#1A3A6B]"></span></div></div>
                <div class="relative overflow-hidden rounded-[10px] border border-[#CAD7E7] bg-white/45 px-4 py-[14px] backdrop-blur-sm"><span class="absolute inset-y-0 left-0 w-[3px] bg-[#1A3A6B]"></span><div class="flex items-center justify-between gap-3"><span class="text-[10px] font-bold uppercase tracking-[.14em] text-[#55749D]">Estructura</span><span class="rounded-full border border-[#DCEAFF] bg-[#EEF5FF]/80 px-2 py-1 text-[9px] font-semibold text-[#1A3A6B]">Activa</span></div><div class="mt-3 flex items-end justify-between gap-3"><div class="flex items-baseline gap-2"><strong class="text-[24px] leading-none text-[#102A52]">{{ $physicalAreas->count() }}</strong><span class="text-[11px] text-[#55749D]">áreas activas</span></div><span class="text-[9px] font-bold uppercase tracking-[.12em] text-[#7B96B9]">Operación</span></div><div class="mt-3 h-[2px] overflow-hidden bg-[#DCEAFF]"><span class="block h-full w-full bg-[#1A3A6B]"></span></div></div>
                <div class="relative overflow-hidden rounded-[10px] border border-[#CAD7E7] bg-white/45 px-4 py-[14px] backdrop-blur-sm"><span class="absolute inset-y-0 left-0 w-[3px] bg-emerald-500"></span><div class="flex items-center justify-between gap-3"><span class="text-[10px] font-bold uppercase tracking-[.14em] text-[#55749D]">Seguridad</span><span class="rounded-full border border-emerald-200 bg-emerald-50/80 px-2 py-1 text-[9px] font-semibold text-emerald-700">RBAC activo</span></div><div class="mt-3 flex items-end justify-between gap-3"><div class="flex items-baseline gap-2"><strong class="text-[24px] leading-none text-[#102A52]">{{ $totalRoles ?? 0 }}</strong><span class="text-[11px] text-[#55749D]">roles</span></div><span class="text-[9px] font-bold uppercase tracking-[.12em] text-emerald-700">Protegido</span></div><div class="mt-3 h-[2px] overflow-hidden bg-[#DCEAFF]"><span class="block h-full w-[82%] bg-emerald-500"></span></div></div>
                <div class="relative overflow-hidden rounded-[10px] border border-[#CAD7E7] bg-white/45 px-4 py-[14px] backdrop-blur-sm"><span class="absolute inset-y-0 left-0 w-[3px] bg-[#102A52]"></span><div class="flex items-center justify-between gap-3"><span class="text-[10px] font-bold uppercase tracking-[.14em] text-[#55749D]">Jerarquía</span><span class="rounded-full border border-[#DCEAFF] bg-[#EEF5FF]/80 px-2 py-1 text-[9px] font-semibold text-[#1A3A6B]">Auditada</span></div><div class="mt-3 flex items-end justify-between gap-3"><div class="flex items-baseline gap-2"><strong class="text-[24px] leading-none text-[#102A52]">{{ $orgChartStats['relations'] ?? 0 }}</strong><span class="text-[11px] text-[#55749D]">relaciones</span></div><span class="text-[9px] font-bold uppercase tracking-[.12em] text-[#7B96B9]">Conectada</span></div><div class="mt-3 h-[2px] overflow-hidden bg-[#DCEAFF]"><span class="block h-full w-[68%] bg-[#102A52]"></span></div></div>
            </div>
        </div>

        <div class="organization-carousel-frame relative mt-[50px] flex w-full items-center" x-data="{ move(direction) { window.organizationCarouselMove($refs.track, direction) } }">
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
        <div class="mt-[50px] flex flex-wrap items-center justify-between gap-3 p-0 text-[10px] font-medium text-[#55749D]">
            <div class="flex flex-wrap items-center gap-4"><span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Centro organizacional activo</span><span class="text-[#A8BAD2]">•</span><span>Sincronización en tiempo real</span><span class="text-[#A8BAD2]">•</span><span>Última validación RBAC: {{ now()->format('H:i') }}</span></div>
            <span class="font-semibold text-[#102A52]">Protocolo de seguridad: RBAC</span>
        </div>
    </section>

    {{-- La información jerárquica solamente se entrega a administradores. --}}
    @if ($canManageOrganization)

    @if ($showUserManagementModal)
        <livewire:administracion.users.form
            :embedded="true"
            :initial-tab="$userManagementInitialTab"
            :key="'organization-user-management-'.$userManagementInitialTab"
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
        <x-administration-panel-modal
            title="{{ ucfirst($assignmentModalTab) }} asignación"
            subtitle="Relaciona clientes, responsables y auxiliares sin salir del centro de organización."
            modal-id="assignment-management"
            cancel-action="closeAssignmentModal"
            :carousel-style="true"
        >
            <x-slot name="icon"><x-feathericon-git-merge class="h-6 w-6" /></x-slot>
            <x-slot name="content">
                <livewire:administracion.relationship.gestion-relaciones-jerarquicas :embedded="true" :mode="$assignmentModalTab" :key="'organization-relationships-form-'.$assignmentModalTab" />
            </x-slot>
            <x-slot name="actions">
                <button type="button" wire:click="closeAssignmentModal" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-[15px] font-medium text-white hover:bg-white/20">Cerrar</button>
            </x-slot>
        </x-administration-panel-modal>
    @endif

    <!-- Organigrama con padding de 80px en todos los lados -->
    <div id="organigrama-principal" class="scroll-mt-5 overflow-hidden rounded-2xl border border-gray-200 shadow-[0_8px_24px_rgba(15,23,42,0.06)]" style="padding: 20px; background-color: #F3F3F3;">

        <!-- Encabezado -->
        <div style="padding: 0; background-color: transparent; display: flex; flex-wrap: nowrap; align-items: flex-start; justify-content: space-between; gap: 32px; min-width: max-content;">
            <div style="max-width: 672px; flex-shrink: 0; display: flex; flex-direction: column; gap: 21px;">
                
                <!-- Grupo 1: Icono + Título / Subtítulo -->
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; height: 56px; width: 56px; align-items: center; justify-content: center; border-radius: 0px; background-color: rgba(26, 58, 107, 0.1); flex-shrink: 0;">
                        <svg style="height: 28px; width: 28px; color: #1A3A6B; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: center; gap: 4px;">
                        <h1 style="font-size: 24px; font-weight: 700; letter-spacing: -0.025em; color: #111827; white-space: nowrap; line-height: 1.5; margin: 0;">
                            Organigrama
                        </h1>
                        <p style="font-size: 15px; color: #6b7280; white-space: nowrap; line-height: 1.5; margin: 0;">
                            Estructura jerárquica dinámica
                        </p>
                    </div>
                </div>

                <!-- Grupo 2: Descripción -->
                <p style="max-width: 672px; font-size: 15px; line-height: 2; margin: 0; color: #6b7280;">
                    Gestión integral de la estructura organizacional, líneas de mando<br>y niveles de supervisión entre colaboradores, áreas y departamentos.
                </p>

                <!-- Grupo 3: Estadísticas -->
                <div style="display: flex; flex-wrap: wrap; gap: 20px; font-size: 15px; color: #6b7280;">
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $totalUsers ?? 0 }}</strong> Usuarios totales</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $orgChartStats['in_tree'] ?? 0 }}</strong> En árbol</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $orgChartStats['relations'] ?? 0 }}</strong> Relaciones</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">{{ $totalRoles ?? 0 }}</strong> Roles</span>
                    <span style="display: inline-flex; align-items: center; line-height: 1;"><strong style="color: #1A3A6B; margin-right: 4px;">30</strong> Permisos</span>
                </div>

            </div>
        </div>

        <div class="h-[35px]" aria-hidden="true"></div>

        <!-- Grid con ancho mínimo para evitar deformación -->
        <div class="grid min-w-0 grid-cols-1 gap-6 p-0 xl:grid-cols-3" style="padding: 0;">

            <!-- Árbol jerárquico -->
            <div class="xl:col-span-2" style="padding: 0;">
                @if (($orgChartStats['cycles_detected'] ?? 0) > 0)
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[15px] text-red-700">
                        Se detectaron {{ $orgChartStats['cycles_detected'] }} ciclo(s) en las relaciones jerárquicas.
                        El árbol se renderiza de forma segura omitiendo ramas circulares.
                    </div>
                @endif

                <!-- Contenedor con pan y zoom (siempre visible) -->
                <div id="org-tree-container" class="relative overflow-hidden border border-dashed border-gray-300 p-5" style="max-height: 520px; height: auto; background: #F3F3F3; border-radius: 0.75rem; touch-action: none;">

                    <!-- LEYENDA (siempre visible) -->
                    <div class="absolute top-0 left-0 p-5 bg-white/80 backdrop-blur-sm rounded-br-xl border-r border-b border-white/30 shadow-sm" style="z-index: 30;">
                        <div class="text-[15px] text-gray-700 flex flex-col items-start gap-[10px]">
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #1e3a8a;"></span> Rol</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #059669;"></span> Puesto</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #7c3aed;"></span> Área</div>
                            <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm" style="background-color: #d97706;"></span> Múltiples jefes</div>
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
                                class="rounded-lg text-[15px] border-gray-300 bg-white/80 backdrop-blur-sm"
                                style="height: 50px; min-width: 220px; width: 100%; padding: 0 16px; border: 1px solid #d1d5db; outline: 0 !important; box-shadow: none !important; transition: none; position: relative; z-index: 30;"
                                autocomplete="off"
                            >
                            <div 
                                id="search-results"
                                style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: rgba(255,255,255,0.98); backdrop-filter: blur(8px); border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-height: 300px; overflow-y: auto; display: none; z-index: 100;"
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
                                class="h-[50px] w-full rounded-lg border border-gray-300 bg-white/80 px-4 pr-10 text-[15px] backdrop-blur-sm focus:border-gray-300 focus:outline-none focus:ring-0"
                                autocomplete="off" aria-label="Buscar o filtrar por área">
                            <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.areaInput.focus())"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-500 focus:outline-none" tabindex="-1" aria-label="Mostrar áreas">
                                <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                            </button>
                            <div x-cloak x-show="open" id="physical-area-results" class="absolute left-0 right-0 top-[54px] max-h-[300px] overflow-y-auto rounded-lg border border-gray-300 bg-white shadow-lg" style="z-index: 100;">
                                <button type="button" @mousedown.prevent="choose('')" class="block w-full border-b border-gray-100 px-4 py-3 text-left text-[15px] hover:bg-[#f0f4ff]">Todas las áreas</button>
                                <template x-for="area in filteredAreas" :key="area.id"><button type="button" @mousedown.prevent="choose(area.id)" x-text="area.name" class="block w-full border-b border-gray-100 px-4 py-3 text-left text-[15px] hover:bg-[#f0f4ff]"></button></template>
                                <div x-show="filteredAreas.length === 0" class="px-4 py-3 text-center text-sm text-gray-500">No se encontraron áreas</div>
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
                    <div class="absolute bottom-0 right-0 p-5 bg-white/80 backdrop-blur-sm rounded-tl-xl border-l border-t border-white/30 shadow-sm" style="z-index: 30;">
                        <div class="text-[15px] text-gray-500">
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

            <!-- Usuarios sin asignar -->
            <div class="unassigned-users-scrollbar rounded-xl border border-dashed border-gray-300" style="min-width: 400px; background-color: #F3F3F3; max-height: 520px; overflow-y: auto; padding: 20px;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="flex w-full items-center justify-between" style="line-height: 1; height: 10px;">
                            <h3 class="text-[15px] font-semibold text-gray-700" style="line-height: 1; margin: 0; padding: 0;">
                                Usuarios sin asignar
                            </h3>
                            <span class="rounded-full bg-gray-200 px-2 text-[15px] font-medium text-gray-700"
                                style="line-height: 1; height: 22px; display: inline-flex; align-items: center; justify-content: center;">
                                {{ count($unassignedUsers) }}
                            </span>
                        </div>
                        <p class="text-[15px] text-gray-500" style="line-height: 1; margin: 0; padding: 0;">
                            Sin jefe, puesto o área asignada.
                        </p>
                    </div>
                    @if (count($unassignedUsers) > 0)
                        <ul class="overflow-y-auto" style="display: flex; flex-direction: column; gap: 15px; margin: 0; padding: 0; list-style: none;">
                            @foreach ($unassignedUsers as $user)
                                @php
                                    $nameParts = array_values(array_filter(preg_split('/\s+/', trim((string) $user['name']))));
                                    $initials = implode('', array_map(
                                        static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)),
                                        array_slice($nameParts, 0, 2)
                                    ));
                                @endphp
                                <li style="list-style: none;">
                                    <button type="button" wire:key="unassigned-user-{{ $user['id'] }}" wire:click="selectUser({{ (int) $user['id'] }})"
                                        class="w-full rounded-xl border border-dashed border-gray-300 bg-[#F3F3F3] p-[20px] text-left shadow-sm transition hover:border-[#1e3a8a] hover:shadow-md focus:border-[#1e3a8a] focus:outline-none focus:ring-0"
                                        style="display: flex; flex-direction: column; gap: 20px;">
                                        <div class="flex min-w-0 items-start gap-[20px]">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-xs font-semibold text-white">
                                                {{ $initials !== '' ? $initials : '?' }}
                                            </div>
                                            <div class="min-w-0 flex-1" style="display: flex; flex-direction: column; gap: 13px;">
                                                <div class="flex min-w-0 items-start justify-between gap-[0px]">

                                                    {{--  --}}
                                                    <p class="min-w-0 flex-1 truncate text-[15px] font-medium text-gray-800" title="{{ $user['name'] }}" style="line-height: 1; margin: 0; padding: 0;">
                                                        {{ $user['name'] }}
                                                    </p>

                                                    {{--  --}}
                                                    @if(! empty($user['created_at']))
                                                        <span class="shrink-0 text-[13px] text-gray-500" style="line-height: 1; margin: 0; padding: 0; white-space: nowrap;">
                                                            {{ \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="shrink-0 text-[13px] text-gray-400" style="line-height: 1; margin: 0; padding: 0; white-space: nowrap;">
                                                            Fecha no disponible
                                                        </span>
                                                    @endif
                                                </div>


                                                {{--  --}}
                                                <p class="truncate text-[15px] text-gray-500" title="{{ $user['email'] }}" style="line-height: 1; margin: 0; padding: 0;">{{ $user['email'] }}</p>

                                                <div class="flex min-w-0 items-center gap-2" title="{{ $user['presence_label'] ?? 'Sin actividad registrada' }}" style="line-height: 1; margin: 0; padding: 0;">
                                                    <span @class([
                                                        'h-2 w-2 shrink-0 rounded-full',
                                                        'bg-emerald-500' => $user['is_online'] ?? false,
                                                        'bg-gray-400' => ! ($user['is_online'] ?? false),
                                                    ])></span>
                                                    <span @class([
                                                        'truncate text-[13px] font-medium',
                                                        'text-emerald-600' => $user['is_online'] ?? false,
                                                        'text-gray-500' => ! ($user['is_online'] ?? false),
                                                    ])>
                                                        {{ $user['presence_label'] ?? 'Sin actividad registrada' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-[10px]">
                                            @foreach ($user['missing'] as $missingKey)
                                                <span @class([
                                                    'inline-flex w-[130px] shrink-0 items-center justify-center gap-[10px] rounded-md px-[20px] py-[10px] text-[15px] font-medium text-white',
                                                    'bg-[#D9383A]' => $missingKey === 'superior',
                                                    'bg-[#028A58]' => $missingKey === 'job_position',
                                                    'bg-[#8B3DFF]' => $missingKey === 'physical_area',
                                                ]) style="line-height: 1;">
                                                    @if($missingKey === 'superior')
                                                        <svg class="h-[10px] w-[10px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                        <span class="min-w-0 truncate">Sin supervisor</span>
                                                    @elseif($missingKey === 'job_position')
                                                        <svg class="h-[10px] w-[10px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7l1 12h12l1-12M9 7V5a3 3 0 016 0v2" /></svg>
                                                        <span class="min-w-0 truncate">Sin puesto</span>
                                                    @elseif($missingKey === 'physical_area')
                                                        <svg class="h-[10px] w-[10px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4zM8 8h2v2H8zm4 0h2v2h-2zm-4 4h2v2H8zm4 0h2v2h-2z" /></svg>
                                                        <span class="min-w-0 truncate">Sin área</span>
                                                    @else
                                                        <span class="min-w-0 truncate">{{ $missingLabels[$missingKey] ?? $missingKey }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                        @if (! empty($user['role']) || ! empty($user['job_position']) || ! empty($user['physical_area']))
                                            <div class="flex flex-wrap gap-[20px]">
                                                @if (! empty($user['role']))
                                                    <span class="text-[15px] text-gray-400" style="line-height: 1; margin: 0; padding: 0;"><strong class="font-semibold text-gray-500">Rol:</strong> {{ $user['role'] }}</span>
                                                @endif
                                                @if (! empty($user['job_position']))
                                                    <span class="text-[15px] text-gray-400" style="line-height: 1; margin: 0; padding: 0;"><strong class="font-semibold text-gray-500">Puesto:</strong> {{ $user['job_position'] }}</span>
                                                @endif
                                                @if (! empty($user['physical_area']))
                                                    <span class="text-[15px] text-gray-400" style="line-height: 1; margin: 0; padding: 0;"><strong class="font-semibold text-gray-500">Área:</strong> {{ $user['physical_area'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-[15px] text-gray-500" style="line-height: 1.3; margin: 0; padding: 0;">
                            Todos los usuarios tienen jefe, puesto y área asignados.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>

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
        <livewire:administracion.permissions.catalog-manager :auto-open="true" :key="'permission-catalog-direct'" />
        <span class="hidden" data-administration-modal="permissions" wire:click.self="closePermissionsModal" aria-hidden="true"></span>
        <span class="hidden" data-permission-role="Administrador" aria-hidden="true"></span>
        <span class="hidden" data-permission-role="Auxiliar" aria-hidden="true"></span>
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
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]"
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
    <div x-data="{ visible: true }" x-show="visible" @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === 'closeUserDetails') visible = false" @keydown.escape.window="visible = false; $wire.closeUserDetails()" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="user-details-title">
        <!-- Contenedor principal: altura fija de 80vh -->
        <div @click.outside="visible = false; $wire.closeUserDetails()" class="org-user-modal relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-gray-300 bg-[#F3F3F3] shadow-2xl" style="height: min(86vh, 820px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;">
            
            <!-- ===== ENCABEZADO (fijo) ===== -->
            <div class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-gray-300 bg-[#F3F3F3] p-5">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white shadow-sm">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="min-w-0" style="display: flex; flex-direction: column; gap: 10px;">
                        <h2 id="user-details-title" class="truncate text-[15px] font-semibold text-gray-900 leading-none" title="{{ $selectedUserDetails['name'] ?? 'Usuario' }}" style="margin: 0;">
                            {{ $selectedUserDetails['name'] ?? 'Usuario' }}
                        </h2>
                        <p class="text-[15px] text-gray-500 leading-none" style="margin: 0;">Detalles del usuario</p>
                    </div>
                </div>
                <button type="button" wire:click="closeUserDetails" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-xl leading-none text-gray-500 transition hover:border-[#1A3A6B] hover:text-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Cerrar">&times;</button>
            </div>

            @if ($isEditingUser)
                {{-- ===== FORMULARIO DE EDICIÓN ===== --}}
                <div class="administration-modal-scrollbar min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
                    <form wire:submit="saveSelectedUser" class="m-5 flex flex-col gap-5 rounded-xl border border-dashed border-gray-300 bg-white p-5 text-[15px] shadow-sm">
                        <div class="grid grid-cols-1 gap-4">
                            <div><x-label for="edit-name" value="Nombre" class="text-[15px] mb-2.5 block" /><x-input id="edit-name" class="mt-1 block w-full text-[15px]" wire:model="userForm.name" /><x-input-error for="userForm.name" /></div>
                            <div><x-label for="edit-last-name" value="Apellido" class="text-[15px] mb-2.5 block" /><x-input id="edit-last-name" class="mt-1 block w-full text-[15px]" wire:model="userForm.last_name" /><x-input-error for="userForm.last_name" /></div>
                            <div><x-label for="edit-email" value="Email" class="text-[15px] mb-2.5 block" /><x-input id="edit-email" type="email" class="mt-1 block w-full text-[15px]" wire:model="userForm.email" /><x-input-error for="userForm.email" /></div>
                            <div>
                                <x-label for="edit-employee-id" value="ID del reloj checador" class="mb-2.5 block text-[15px]" />
                                <div
                                    class="relative"
                                    x-data="{ open: false, search: @js((string) ($userForm['employee_id'] ?? '')) }"
                                    @click.outside="open = false"
                                >
                                    <x-input
                                        id="edit-employee-id"
                                        type="text"
                                        autocomplete="off"
                                        maxlength="50"
                                        class="mt-1 block w-full pr-12 text-[15px]"
                                        wire:model="userForm.employee_id"
                                        x-model="search"
                                        @input="open = true"
                                        @focus="open = true"
                                        @click="open = true"
                                        @keydown.escape="open = false"
                                        aria-autocomplete="list"
                                        aria-controls="employee-id-suggestions"
                                        x-bind:aria-expanded="open"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-0 top-0 flex h-full w-12 items-center justify-center text-gray-500 transition hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
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
                                        class="administration-modal-scrollbar absolute z-[60] mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                        role="listbox"
                                    >
                                        @foreach ($employeeIdSuggestions as $employeeIdSuggestion)
                                            <button
                                                type="button"
                                                data-employee-id="{{ $employeeIdSuggestion->employeeID }}"
                                                data-person-name="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}"
                                                x-show="!search || $el.dataset.employeeId.toLowerCase().includes(String(search).toLowerCase()) || $el.dataset.personName.toLowerCase().includes(String(search).toLowerCase())"
                                                @click="search = $el.dataset.employeeId; $wire.set('userForm.employee_id', $el.dataset.employeeId); open = false"
                                                class="flex w-full min-w-0 items-center gap-4 rounded-lg px-4 py-3 text-left transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none focus:ring-0"
                                                role="option"
                                            >
                                                <span class="w-20 shrink-0 truncate text-[15px] font-semibold text-[#1A3A6B]" title="{{ $employeeIdSuggestion->employeeID }}">{{ $employeeIdSuggestion->employeeID }}</span>
                                                <span class="min-w-0 flex-1 truncate text-[15px] text-gray-600" title="{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}">{{ $employeeIdSuggestion->personName ?: 'Nombre no disponible' }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <p class="mt-2 text-[15px] text-gray-500">Selecciona un ID registrado; la sugerencia muestra el nombre detectado por el checador.</p>
                                <x-input-error for="userForm.employee_id" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div><x-label for="edit-role" value="Rol" class="text-[15px] mb-2.5 block" /><x-administration-search-picker input-id="edit-role" model="userForm.role_id" :selected="$userForm['role_id'] ?? null" :items="$roles->map(fn ($availableRole) => ['id' => $availableRole->id, 'label' => $availableRole->role])" placeholder="Buscar rol..." empty-message="No se encontraron roles." /><x-input-error for="userForm.role_id" /></div>
                            <div><x-label for="edit-position" value="Puesto" class="text-[15px] mb-2.5 block" /><x-administration-search-picker input-id="edit-position" model="userForm.job_position_id" :selected="$userForm['job_position_id'] ?? null" :items="$jobPositions->map(fn ($position) => ['id' => $position->id, 'label' => $position->name, 'meta' => $position->payment_type === 'hourly' ? 'Pago por hora' : 'Tiempo completo'])" placeholder="Buscar puesto..." empty-message="No se encontraron puestos." /><x-input-error for="userForm.job_position_id" /></div>
                        </div>
                        <div><x-label for="edit-area" value="Área / departamento" class="text-[15px] mb-2.5 block" /><x-administration-search-picker input-id="edit-area" model="userForm.physical_area_id" :selected="$userForm['physical_area_id'] ?? null" :items="$physicalAreas->map(fn ($area) => ['id' => $area->id, 'label' => $area->name])" placeholder="Buscar área o departamento..." empty-message="No se encontraron áreas." /><x-input-error for="userForm.physical_area_id" /></div>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="hierarchy-selection-card">
                                <div class="mb-2.5 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <x-label for="edit-superiors" value="Jefe directo" class="block text-[15px] font-semibold text-gray-700" />
                                        <p class="mt-1 text-[15px] text-gray-500">Solo se permite uno y debe estar asignado al organigrama.</p>
                                    </div>
                                </div>
                                <div id="edit-superiors" class="hierarchy-selection-list block w-full text-[15px]" role="group" aria-label="Jefe directo">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-gray-100">
                                        <input type="radio" name="edit-superior" wire:click="clearSuperiorSelection" @checked(empty($userForm['superior_ids'])) class="h-4 w-4 shrink-0 border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0">
                                        <span class="text-[15px] font-medium text-gray-600">Sin jefe directo</span>
                                    </label>
                                    @forelse ($superiorCandidates as $superiorCandidate)
                                        <label wire:key="superior-candidate-{{ $superiorCandidate->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-gray-100">
                                            <input type="radio" name="edit-superior" value="{{ $superiorCandidate->id }}" wire:click="selectSuperior({{ $superiorCandidate->id }})" @checked(in_array($superiorCandidate->id, $userForm['superior_ids'] ?? [])) class="h-4 w-4 shrink-0 border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0">
                                            <span class="min-w-0 flex-1" title="{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }} — {{ $superiorCandidate->email }}">
                                                <span class="block truncate text-[15px] font-medium text-gray-800">{{ trim($superiorCandidate->name.' '.$superiorCandidate->last_name) }}</span>
                                                <span class="block truncate text-[15px] text-gray-500">{{ $superiorCandidate->email }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-3 py-2.5 text-[15px] text-gray-500">No hay colaboradores asignados al organigrama.</p>
                                    @endforelse
                                </div>
                                <x-input-error for="userForm.superior_ids" />
                            </div>
                            <div class="hierarchy-selection-card">
                                <div class="mb-2.5 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <x-label for="edit-subordinates" value="Subordinados directos" class="block text-[15px] font-semibold text-gray-700" />
                                        <p class="mt-1 text-[15px] text-gray-500">Solo aparecen personas sin jefe o que ya te reportan directamente.</p>
                                    </div>
                                </div>
                                <div id="edit-subordinates" class="hierarchy-selection-list block w-full text-[15px]" role="group" aria-label="Subordinados directos">
                                    @forelse ($subordinateCandidates as $subordinateCandidate)
                                        <label wire:key="subordinate-candidate-{{ $subordinateCandidate->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-gray-100">
                                            <input type="checkbox" value="{{ $subordinateCandidate->id }}" wire:model.live="userForm.subordinate_ids" class="h-4 w-4 shrink-0 rounded border-gray-300 text-[#1A3A6B] focus:outline-none focus:ring-0 focus:ring-offset-0">
                                            <span class="min-w-0 flex-1" title="{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }} — {{ $subordinateCandidate->email }}">
                                                <span class="block truncate text-[15px] font-medium text-gray-800">{{ trim($subordinateCandidate->name.' '.$subordinateCandidate->last_name) }}</span>
                                                <span class="block truncate text-[15px] text-gray-500">{{ $subordinateCandidate->email }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-3 py-2.5 text-[15px] text-gray-500">No hay colaboradores disponibles.</p>
                                    @endforelse
                                </div>
                                <x-input-error for="userForm.subordinate_ids" />
                            </div>
                        </div>
                        @if ($userForm['is_hourly_position'] ?? false)
                            <div class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-[#F3F3F3] p-4 sm:grid-cols-2">
                                <div><x-label for="edit-hourly-rate" value="Precio por hora" class="text-[15px] mb-2.5 block" /><x-input id="edit-hourly-rate" type="number" step="0.01" class="mt-1 block w-full text-[15px]" wire:model="userForm.hourly_rate" /><x-input-error for="userForm.hourly_rate" /></div>
                                <div><x-label for="edit-food-allowance" value="Apoyo económico por día" class="text-[15px] mb-2.5 block" /><x-input id="edit-food-allowance" type="number" step="0.01" class="mt-1 block w-full text-[15px]" wire:model="userForm.food_allowance" /><x-input-error for="userForm.food_allowance" /></div>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-[#F3F3F3] p-4 sm:grid-cols-2">
                            <div><x-label for="edit-password" value="Nueva contraseña (opcional)" class="text-[15px] mb-2.5 block" /><x-input id="edit-password" type="password" class="mt-1 block w-full text-[15px]" wire:model="userForm.password" /><x-input-error for="userForm.password" /></div>
                            <div><x-label for="edit-password-confirmation" value="Confirmar contraseña" class="text-[15px] mb-2.5 block" /><x-input id="edit-password-confirmation" type="password" class="mt-1 block w-full text-[15px]" wire:model="userForm.password_confirmation" /></div>
                        </div>
                        <div class="flex justify-end gap-3 border-t pt-4">
                            <button type="button" wire:click="cancelEditingUser" class="rounded-lg border border-[#1A3A6B] bg-white px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-gray-100 focus:outline-none focus:ring-0">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            @else
                {{-- ===== MENÚ DE PESTAÑAS (fijo) ===== --}}
                <div class="flex flex-shrink-0 gap-[10px] border-b border-gray-300 bg-[#F3F3F3] px-5 pt-3">
                    <button type="button"
                        wire:click="setActiveTab('datos')"
                        class="inline-flex items-center gap-[10px] border-b-2 px-4 py-3 text-[15px] font-medium transition-colors focus:outline-none focus:ring-0 {{ $activeTab === 'datos' ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6m2 13H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" /></svg>
                        Datos
                    </button>
                    <button type="button"
                        wire:click="setActiveTab('eliminar')"
                        class="inline-flex items-center gap-[10px] border-b-2 px-4 py-3 text-[15px] font-medium transition-colors focus:outline-none focus:ring-0 {{ $activeTab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" /></svg>
                        Eliminar usuario
                    </button>
                </div>

                {{-- ===== CONTENIDO SCROLLABLE ===== --}}
                <div class="administration-modal-scrollbar min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
                    @if ($activeTab === 'datos')
                        {{-- PESTAÑA DATOS --}}
                        <div class="p-5" style="display: flex; flex-direction: column; gap: 15px;">
                            <!-- DATOS GENERALES -->
                            <div class="overflow-hidden rounded-xl border border-dashed border-gray-300 bg-white shadow-sm" style="display: flex; flex-direction: column;">
                                <h3 class="border-b border-gray-200 bg-gray-100 text-[15px] font-semibold text-gray-800" style="padding: 15px 20px; margin: 0;">Datos generales</h3>
                                <div class="org-modal-fields p-5" style="display: flex; flex-direction: column; gap: 15px;">
                                    <!-- Nombre completo -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Nombre completo</span>
                                        <p class="text-[15px] font-semibold text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['name'] ?? 'N/A' }}</p>
                                    </div>
                                    <!-- Correo electrónico -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Correo electrónico</span>
                                        <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['email'] ?? 'N/A' }}</p>
                                    </div>
                                    <!-- Rol + ID checador (2 columnas) -->
                                    <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Rol</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['role'] ?: 'Sin rol' }}</p>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">ID del checador</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['employee_id'] ?: 'No asignado' }}</p>
                                        </div>
                                    </div>
                                    <!-- Fechas (2 columnas) -->
                                    <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Fecha de creación</span>
                                            <p class="text-[15px] font-medium text-gray-600 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['created_at'] ?? 'N/A' }}</p>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Última actualización</span>
                                            <p class="text-[15px] font-medium text-gray-600 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['updated_at'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <!-- Contraseña -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Contraseña</span>
                                        <p class="text-[15px] font-medium text-gray-500 leading-tight truncate" style="margin: 0;">•••••••• (protegida)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ORGANIZACIONAL -->
                            <div class="overflow-hidden rounded-xl border border-dashed border-gray-300 bg-white shadow-sm" style="display: flex; flex-direction: column;">
                                <h3 class="border-b border-gray-200 bg-gray-100 text-[15px] font-semibold text-gray-800" style="padding: 15px 20px; margin: 0;">Organizacional</h3>
                                <div class="org-modal-fields p-5" style="display: flex; flex-direction: column; gap: 15px;">
                                    <!-- Puesto + Área (2 columnas) -->
                                    <div class="grid grid-cols-1 gap-[15px] sm:grid-cols-2">
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Puesto</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['job_position'] ?: 'Sin asignar' }}</p>
                                        </div>
                                        <div style="display: flex; flex-direction: column;">
                                            <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Área / departamento</span>
                                            <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">{{ $selectedUserDetails['physical_area'] ?: 'Sin asignar' }}</p>
                                        </div>
                                    </div>
                                    <!-- Jefes directos -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Jefes directos</span>
                                        <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">
                                            @if (count($selectedUserDetails['superiors'] ?? []) > 0)
                                                {{ implode(', ', $selectedUserDetails['superiors']) }}
                                            @else
                                                <span class="text-gray-400">Sin jefe asignado</span>
                                            @endif
                                        </p>
                                    </div>
                                    <!-- Subordinados directos -->
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="text-[15px] text-gray-500" style="margin-bottom: 10px;">Subordinados directos</span>
                                        <p class="text-[15px] font-medium text-gray-800 leading-tight truncate" style="margin: 0;">
                                            @if (count($selectedUserDetails['subordinates'] ?? []) > 0)
                                                {{ implode(', ', $selectedUserDetails['subordinates']) }}
                                            @else
                                                <span class="text-gray-400">Sin subordinados</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos de auxiliar -->
                            @if ($selectedUserDetails['is_hourly_position'] ?? false)
                                <div style="display: flex; flex-direction: column;">
                                    <div class="overflow-hidden rounded-xl border border-dashed border-amber-300 bg-amber-50 shadow-sm">
                                        <h3 class="border-b border-amber-200 bg-amber-100/70 text-[15px] font-semibold text-amber-800" style="padding: 15px 20px; margin: 0;">Datos de auxiliar</h3>
                                        <div class="org-modal-financial grid grid-cols-1 gap-[15px] p-5 sm:grid-cols-2">
                                            <div style="display: flex; flex-direction: column;">
                                                <span class="text-[15px] text-amber-600" style="margin-bottom: 10px;">Precio por hora</span>
                                                <p class="text-[15px] font-medium text-amber-800 leading-tight truncate" style="margin: 0;">${{ number_format((float) ($selectedUserDetails['hourly_rate'] ?? 0), 2) }}</p>
                                            </div>
                                            <div style="display: flex; flex-direction: column;">
                                                <span class="text-[15px] text-amber-600" style="margin-bottom: 10px;">Apoyo económico por día</span>
                                                <p class="text-[15px] font-medium text-amber-800 leading-tight truncate" style="margin: 0;">${{ number_format((float) ($selectedUserDetails['food_allowance'] ?? 0), 2) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    @elseif ($activeTab === 'eliminar')
                        {{-- PESTAÑA ELIMINAR USUARIO --}}
                        <div class="flex min-h-full items-center p-5">
                            <div class="w-full rounded-xl border border-dashed border-red-300 bg-white p-5 shadow-sm">
                                <div class="flex items-start gap-[15px]">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1" style="display: flex; flex-direction: column; gap: 15px;">
                                        <h3 class="text-[15px] font-semibold text-red-700" style="margin: 0;">¿Estás seguro de eliminar este usuario?</h3>
                                        <p class="text-[15px] leading-relaxed text-gray-600" style="margin: 0;">Esta acción es irreversible y eliminará permanentemente al usuario del sistema.</p>
                                        <p class="text-[15px] leading-relaxed text-gray-600" style="margin: 0;">Para confirmar, escribe el nombre completo del usuario: <span class="font-semibold text-gray-800">{{ $selectedUserDetails['name'] ?? 'Usuario' }}</span></p>
                                        <div class="flex flex-col gap-[15px]">
                                            <input type="text" wire:model="deleteConfirmationName" class="w-full border-gray-300 px-4 py-3 text-[15px] focus:border-red-500 focus:ring-0" placeholder="Nombre completo del usuario">
                                            <button type="button" wire:click="deleteSelectedUser" class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-5 py-3 text-[15px] font-medium text-white transition-colors hover:bg-red-700 focus:outline-none focus:ring-0">
                                                Eliminar usuario permanentemente
                                            </button>
                                        </div>
                                        @error('deleteConfirmationName')
                                            <p class="text-[15px] text-red-600" style="margin: 0;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ===== PIE CON BOTONES (fijo) ===== --}}
            <div class="flex flex-shrink-0 justify-end gap-[15px] border-t border-gray-300 bg-[#F3F3F3] p-5">
                @if (! $isEditingUser && $activeTab === 'datos')
                    <button type="button" wire:click="beginEditingUser" class="inline-flex items-center justify-center gap-[10px] rounded-lg bg-[#1A3A6B] px-5 py-3 text-[15px] font-medium text-white transition hover:bg-[#15305a] focus:outline-none focus:ring-0">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Editar
                    </button>
                @endif
                <button type="button" wire:click="closeUserDetails" class="rounded-lg border border-[#1A3A6B] bg-white px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-gray-100 focus:outline-none focus:ring-0">Cerrar</button>
            </div>

        </div> {{-- fin contenedor principal --}}
    </div> {{-- fin fixed overlay --}}
@endif
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('org-tree-container');
        const wrapper = document.getElementById('org-tree-wrapper');

        if (!container || !wrapper) return;

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

        function loadAllNodes() {
            allNodes = [];
            document.querySelectorAll('.org-node').forEach(el => {
                const nameEl = el.querySelector('p.text-sm.font-semibold');
                const emailEl = el.querySelector('p.text-xs.text-gray-500');
                const name = nameEl ? nameEl.textContent.trim() : '';
                const email = emailEl ? emailEl.textContent.trim() : '';
                const id = el.dataset.id ? parseInt(el.dataset.id) : null;
                if (id && name) {
                    allNodes.push({ id, name, email: email || '', element: el });
                }
            });
            return allNodes;
        }

        function showResults(results) {
            if (!searchResults) return;
            
            if (results.length === 0) {
                searchResults.innerHTML = '<div style="padding: 12px 16px; color: #6b7280; font-size: 14px; text-align: center;">No se encontraron resultados</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = results.map(node => `
                <div class="search-result-item" data-id="${node.id}" style="padding: 12px 16px; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s; background-color: transparent;" 
                     onmouseover="this.style.backgroundColor='#f0f4ff'"
                     onmouseout="this.style.backgroundColor='transparent'">
                    <span style="font-weight: 600; color: #111827; font-size: 15px; text-align: left; line-height: 1.2; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${node.name}</span>
                    <span style="font-size: 13px; color: #6b7280; text-align: left; line-height: 1.2; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${node.email}</span>
                </div>
            `).join('');
            
            searchResults.style.display = 'block';

            searchResults.querySelectorAll('.search-result-item').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    const nodeId = parseInt(this.dataset.id);
                    const nodeEl = document.querySelector(`.org-node[data-id="${nodeId}"]`);
                    
                    if (nodeEl) {
                        centerNode(nodeEl);
                        searchResults.style.display = 'none';
                        searchInput.value = '';
                        allNodes = [];
                    }
                });
            });
        }

        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                const nodes = loadAllNodes();
                if (nodes.length > 0) {
                    showResults(nodes);
                } else {
                    setTimeout(() => {
                        const nodes2 = loadAllNodes();
                        if (nodes2.length > 0) showResults(nodes2);
                    }, 300);
                }
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const nodes = loadAllNodes();
                    const query = this.value.toLowerCase().trim();
                    if (!query) {
                        showResults(nodes);
                        return;
                    }
                    const filtered = nodes.filter(n => 
                        n.name.toLowerCase().includes(query) || 
                        n.email.toLowerCase().includes(query)
                    );
                    showResults(filtered);
                }, 200);
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
