@php
    $permissionAccess = app(\App\Services\Authorization\PermissionAccessService::class);
    $currentUser = auth()->user();
    $canAdministration = $permissionAccess->allows($currentUser, 'administration.organization.manage');
    $canManageUsers = $permissionAccess->allows($currentUser, 'administration.users.manage');
    $canManageRoles = $permissionAccess->allows($currentUser, 'administration.roles.manage');
    $canManagePermissions = $permissionAccess->allows($currentUser, 'administration.permissions.manage');
    $canManageAssignments = $permissionAccess->allows($currentUser, 'administration.assignments.manage');
    $canAdministrationSection = $canAdministration || $canManageUsers || $canManageRoles || $canManagePermissions || $canManageAssignments;
    $administrationRoute = $canAdministration
        ? route('administracion.index')
        : ($canManageUsers
            ? route('administracion.section')
            : ($canManageRoles
                ? route('administracion.role')
                : ($canManagePermissions
                    ? route('administracion.permissions')
                    : route('administracion.interns'))));
    $canCustomers = $permissionAccess->allows($currentUser, 'customers.view');
    $canTimeActivities = $permissionAccess->allows($currentUser, 'activities.manage');
    $canTimeClock = $permissionAccess->allows($currentUser, 'time-control.clock.use');
    $canTimeProductivity = $permissionAccess->allows($currentUser, 'time-control.productivity.view');
    $canTimeSupervision = $permissionAccess->allows($currentUser, 'time-control.supervision.view');
    $canTimeControl = $canTimeActivities || $canTimeClock || $canTimeProductivity || $canTimeSupervision;
@endphp
<!-- ============================================================ -->
<!-- CONTENEDOR PRINCIPAL CON x-data COMPARTIDO                    -->
<!-- ============================================================ -->
<div x-data="{ 
    isTransitioning: false
}">

    <!-- ===================== BARRA SUPERIOR ===================== -->
    <nav id="main-nav" class="fixed top-0 z-[90] w-full bg-[#1a3a6b] border-b border-gray-200 py-[15px] px-[15px] flex items-center h-[90px]">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center justify-start rtl:justify-end">
                <div class="flex items-center">
                    <div class="flex flex-col leading-[2]">
                        <span class="text-[15px] font-medium text-white">González Alonzo y</span>
                        <span class="text-[15px] font-medium text-white pl-[42px]">Asociados S.C.P.</span>
                    </div>
                    <!-- Icono Hamburguesa encerrado en cuadro con bordes y margen izquierdo -->
                    <button @click="collapsed = !collapsed" 
                        type="button"
                        class="flex items-center justify-center text-white transition-colors duration-200 focus:outline-none relative p-[10px] rounded-lg border border-white/60 hover:border-white/80 ml-[22px]">
                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd"
                                d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="flex items-center gap-[15px]">
                <!-- Notificaciones en tiempo real -->
                <livewire:notification-center />

                <!-- AVATAR -->
                <div class="flex items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="content">
                            <div class="px-4 py-3 text-sm text-gray-900">
                                <div class="font-bold truncate">{{ Auth::user()->name }}</div>
                                <div class="font-normal truncate">{{ Auth::user()->email }}</div>
                            </div>
                            <x-dropdown-link href="{{ route('profile.show') }}">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                    {{ __('API Tokens') }}
                                </x-dropdown-link>
                            @endif
                            <div class="border-t border-gray-200"></div>
                            <form method="POST" action="{{ route('logout') }}" x-data data-session-logout>
                                @csrf
                                <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.requestSubmit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                        <x-slot name="trigger">
                            <button class="flex items-center justify-center overflow-hidden p-[10px] rounded-lg border border-white/60 transition-colors duration-200">
                                <div class="w-[20px] h-[20px] rounded-full flex items-center justify-center overflow-hidden">
                                    @if(Auth::user()->profile_photo_url)
                                        <img class="w-full h-full object-cover"
                                            src="{{ Auth::user()->profile_photo_url }}" alt="Avatar">
                                    @else
                                        <span class="text-[10px] font-semibold text-white">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </span>
                                    @endif
                                </div>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </nav>

    <!-- ============================================================ -->
    <!-- MENÚ LATERAL - top: 90px (altura de la barra superior)      -->
    <!-- ============================================================ -->
    <aside
        id="logo-sidebar"
        class="fixed top-[90px] left-0 z-[80] h-[calc(100vh-90px)]
            flex flex-col bg-white shadow-sm
            overflow-hidden"
    >
        
        <!-- Contenedor del menú -->
        <div class="flex-1 px-0 pb-4 overflow-y-auto h-full overscroll-contain" 
            :style="collapsed ? 'opacity: 1;' : 'opacity: 1; transition: opacity 300ms ease-in-out;'">
            <ul class="font-medium inline-block min-w-full m-0 p-0">

                <!-- ========================================================== -->
                <!-- SECCIÓN: GENERAL (oculta en modo compacto)                 -->
                <!-- ========================================================== -->
                <li class="pt-[15px] px-[15px] pb-0 m-0" :class="collapsed ? 'hidden' : 'block'">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">General</span>
                </li>

                <!-- ========================================================== -->
                <!-- BOTÓN: Inicio (siempre visible)                            -->
                <!-- ========================================================== -->
                <li class="pt-[15px] px-[15px] pb-0 m-0">
                    <a href="{{ route('inicio') }}"
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('inicio') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} group whitespace-nowrap">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('inicio') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" />
                        </svg>
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Inicio</span>
                    </a>
                </li>

                <!-- ========================================================== -->
                <!-- BOTÓN: Dashboard (vista completa anterior)                -->
                <!-- ========================================================== -->
                <li class="pt-[15px] px-[15px] pb-0 m-0">
                    <a href="{{ route('dashboard') }}"
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} group whitespace-nowrap">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('dashboard') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v8H4V4Zm10 0h6v4h-6V4ZM4 16h6v4H4v-4Zm10-4h6v8h-6v-8Z" />
                        </svg>
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Dashboard</span>
                    </a>
                </li>

                <!-- ========================================================== -->
                <!-- SECCIÓN: INFORMACIÓN (oculta en modo compacto)             -->
                <!-- ========================================================== -->
                <li class="pt-[15px] px-[15px] pb-0 m-0" :class="collapsed ? 'hidden' : 'block'">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Información</span>
                </li>

                <!-- ========================================================== -->
                <!-- BOTÓN: Administración (siempre visible)                    -->
                <!-- ========================================================== -->
                @if ($canAdministrationSection)
                <li class="pt-[15px] px-[15px] pb-0 m-0">
                    <a href="{{ $administrationRoute }}"
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('administracion.index') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} group whitespace-nowrap">
                        <x-hugeicons-user-add-01 class="w-5 h-5 transition-colors {{ request()->routeIs('administracion.index') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Administración</span>
                    </a>
                </li>
                @endif

                <!-- ========================================================== -->
                <!-- BOTÓN: Clientes (siempre visible)                          -->
                <!-- ========================================================== -->
                @if ($canCustomers)
                <li class="pt-[15px] px-[15px] pb-0 m-0">
                    <a href="{{ route('customers.index') }}"
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('customers.index') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} group whitespace-nowrap">
                        <x-hugeicons-user-group class="w-5 h-5 transition-colors {{ request()->routeIs('customers.index') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Clientes</span>
                    </a>
                </li>
                @endif

                <!-- ========================================================== -->
                <!-- SECCIÓN: ACTIVIDADES (oculta en modo compacto)            -->
                <!-- ========================================================== -->
                <li class="pt-[15px] px-[15px] pb-0 m-0" :class="collapsed ? 'hidden' : 'block'">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Actividades</span>
                </li>

                <!-- ========================================================== -->
                <!-- SUBMENÚ: Control de Horas (para TODOS los usuarios)       -->
                <!-- ========================================================== -->
                @if ($canTimeControl && $canTimeSupervision)
                    <!-- ADMINISTRADOR: Submenú con Supervisión, Panel de Control y Reloj Checador -->
                    <li x-data="{ openTimeControl: {{ request()->routeIs('time.*') ? 'true' : 'false' }} }" class="relative pt-[15px] px-[15px] pb-0 m-0">
                        <button @click="openTimeControl = !openTimeControl" 
                            :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-between p-[15px]'"
                            class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 cursor-pointer font-medium {{ request()->routeIs('time') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <div class="flex items-center">
                                <x-hugeicons-clock-01 class="w-5 h-5 transition-colors {{ request()->routeIs('time') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                                <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Control de Horas</span>
                            </div>
                            <div class="ml-[15px] shrink-0">
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': openTimeControl, 'rotate-0': !openTimeControl }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        <!-- Sub-elementos del submenú -->
                        <div x-show="openTimeControl" 
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            :class="collapsed ? 'pl-[15px]' : 'block'"
                            class="relative mt-0 dynamic-sub-menu" 
                            style="display: none;">
                            <!-- Línea vertical siempre visible -->
                            <div class="absolute left-[22px] top-[15px] bottom-0 w-[2px] bg-gray-200"></div>
                            <ul class="space-y-0 w-full">
                                <!-- Sub-elemento: Supervisión de horas -->
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.admin.dashboard') }}" 
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.admin.dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.admin.dashboard') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Supervisión de horas</span>
                                    </a>
                                </li>
                                <!-- Sub-elemento: Panel de Control -->
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.dashboard') }}"
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.dashboard') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Panel de Control</span>
                                    </a>
                                </li>
                                <!-- Sub-elemento: Reloj Checador -->
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.admin.attendance') }}"
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.admin.attendance') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <x-hugeicons-clock-01 class="w-5 h-5 transition-colors {{ request()->routeIs('time.admin.attendance') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Reloj Checador</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @elseif ($canTimeControl)
                    <!-- USUARIO NORMAL: Submenú con Cronómetro, Reloj Checador, Productividad, Panel de Control -->
                    <li x-data="{ openTimeControl: {{ request()->routeIs('time.*') ? 'true' : 'false' }} }" class="relative pt-[15px] px-[15px] pb-0 m-0">
                        <button @click="openTimeControl = !openTimeControl" 
                            :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-between p-[15px]'"
                            class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 cursor-pointer font-medium {{ request()->routeIs('time') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <div class="flex items-center">
                                <x-hugeicons-clock-01 class="w-5 h-5 transition-colors {{ request()->routeIs('time') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                                <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Control de Horas</span>
                            </div>
                            <div class="ml-[15px] shrink-0">
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': openTimeControl, 'rotate-0': !openTimeControl }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        <!-- Sub-elementos del submenú -->
                        <div x-show="openTimeControl" 
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            :class="collapsed ? 'pl-[15px]' : 'block'"
                            class="relative mt-0 dynamic-sub-menu" 
                            style="display: none;">
                            <!-- Línea vertical siempre visible -->
                            <div class="absolute left-[22px] top-[15px] bottom-0 w-[2px] bg-gray-200"></div>
                            <ul class="space-y-0 w-full">
                                <!-- Sub-elemento: Cronómetro -->
                                @if ($canTimeActivities)
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.index') }}" 
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.index') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.index') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Cronómetro</span>
                                    </a>
                                </li>
                                @endif
                                <!-- Sub-elemento: Reloj Checador -->
                                @if ($canTimeClock)
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.attendance') }}" 
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.attendance') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.attendance') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A11.916 11.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Reloj Checador</span>
                                    </a>
                                </li>
                                @endif
                                <!-- Sub-elemento: Mi Productividad -->
                                @if ($canTimeProductivity)
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.reports') }}" 
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.reports') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.reports') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Productividad</span>
                                    </a>
                                </li>
                                <!-- Sub-elemento: Panel de Control -->
                                <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.dashboard') }}"
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.dashboard') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Panel de Control</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                @endif

                <!-- ========================================================== -->
                <!-- SECCIÓN: SOPORTE (oculta en modo compacto)                 -->
                <!-- ========================================================== -->
                <li class="pt-[15px] px-[15px] pb-0 m-0" :class="collapsed ? 'hidden' : 'block'">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Soporte</span>
                </li>

                <!-- ========================================================== -->
                <!-- SUBMENÚ: Soporte (con flecha siempre visible)              -->
                <!-- ========================================================== -->
                <li x-data="{ openSoporte: {{ request()->routeIs('soporte.*') ? 'true' : 'false' }} }" class="relative pt-[15px] px-[15px] pb-0 m-0">
                    <button @click="openSoporte = !openSoporte" 
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-between p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 cursor-pointer font-medium {{ request()->routeIs('soporte.*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 transition-colors {{ request()->routeIs('soporte.*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Soporte</span>
                        </div>
                        <div class="ml-[15px] shrink-0">
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': openSoporte, 'rotate-0': !openSoporte }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>
                    <!-- Sub-elementos de Soporte -->
                    <div x-show="openSoporte" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        :class="collapsed ? 'pl-[15px]' : 'block'"
                        class="relative mt-0 dynamic-sub-menu" 
                        style="display: none;">
                        <!-- Línea vertical siempre visible -->
                        <div class="absolute left-[22px] top-[15px] bottom-0 w-[2px] bg-gray-200"></div>
                        <ul class="space-y-0 w-full">
                            <!-- Ticket -->
                            <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                <a href="{{ route('soporte.ticket') }}" 
                                    :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                    class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('soporte.ticket') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                    <svg class="w-5 h-5 transition-colors {{ request()->routeIs('soporte.ticket') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Ticket</span>
                                </a>
                            </li>
                            <!-- Preguntas -->
                            <li class="pt-[15px] pl-[30px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                <a href="{{ route('soporte.preguntas') }}" 
                                    :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                    class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('soporte.preguntas') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                    <svg class="w-5 h-5 transition-colors {{ request()->routeIs('soporte.preguntas') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Preguntas</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </aside>
    <!-- ============================================================ -->
    <!-- FIN DEL MENÚ LATERAL                                          -->
    <!-- ============================================================ -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {

                const sidebar = document.getElementById('logo-sidebar');
                const main = document.getElementById('main-content');

                if (!sidebar || !main) return;

                new ResizeObserver(() => {

                    main.style.marginLeft =
                        sidebar.getBoundingClientRect().width + 'px';

                }).observe(sidebar);

            });

        });
    </script>
</div>
<!-- ============================================================ -->
<!-- FIN DEL CONTENEDOR PRINCIPAL                                  -->
<!-- ============================================================ -->
