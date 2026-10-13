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
    $supportsDarkTheme = true;
@endphp
<script>
    document.documentElement.classList.toggle('module-dark-theme', localStorage.getItem('activities-support-theme') === 'dark');
</script>
<!-- ============================================================ -->
<!-- CONTENEDOR PRINCIPAL CON x-data COMPARTIDO                    -->
<!-- ============================================================ -->
<div x-data="{ 
    isTransitioning: false,
    darkMode: localStorage.getItem('activities-support-theme') === 'dark',
    supportsDarkTheme: @js($supportsDarkTheme),
    sidebarSearch: '',
    sidebarSearchOpen: false,
    sidebarListening: false,
    sidebarVoiceMessage: '',
    sidebarMatches: [],
    sidebarSearchThumbTop: 0,
    sidebarSearchThumbHeight: 0,
    sidebarSearchThumbVisible: false,
    profileMenuOpen: false,
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        document.documentElement.classList.toggle('module-dark-theme', this.darkMode);
        localStorage.setItem('activities-support-theme', this.darkMode ? 'dark' : 'light');
        window.dispatchEvent(new CustomEvent('module-theme-changed', { detail: { dark: this.darkMode } }));
    },
    normalizeSidebarText(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    },
    updateSidebarMatches() {
        const query = this.normalizeSidebarText(this.sidebarSearch);

        if (!query) {
            this.sidebarMatches = [];
            this.sidebarSearchOpen = false;
            return;
        }

        const links = Array.from(document.querySelectorAll('#logo-sidebar a[href]'));
        const seen = new Set();

        this.sidebarMatches = links.map((link) => ({
            label: link.textContent.replace(/\s+/g, ' ').trim(),
            url: link.href,
        })).filter((item) => {
            if (!item.label || seen.has(item.url) || !this.normalizeSidebarText(item.label).includes(query)) {
                return false;
            }

            seen.add(item.url);
            return true;
        }).slice(0, 8);
        this.sidebarSearchOpen = true;
        this.$nextTick(() => this.updateSidebarSearchScrollbar());
    },
    updateSidebarSearchScrollbar() {
        const scroller = this.$refs.sidebarSearchResults;

        if (!scroller || scroller.scrollHeight <= scroller.clientHeight) {
            this.sidebarSearchThumbVisible = false;
            return;
        }

        const viewportHeight = scroller.clientHeight;
        const thumbHeight = Math.max(32, viewportHeight * viewportHeight / scroller.scrollHeight);
        const availableTravel = Math.max(0, viewportHeight - thumbHeight);
        const scrollRange = Math.max(1, scroller.scrollHeight - viewportHeight);

        this.sidebarSearchThumbHeight = thumbHeight;
        this.sidebarSearchThumbTop = (scroller.scrollTop / scrollRange) * availableTravel;
        this.sidebarSearchThumbVisible = true;
    },
    openCompactSidebarSearch() {
        collapsed = false;
        this.$nextTick(() => this.$refs.sidebarSearchInput?.focus());
    },
    startSidebarVoiceSearch() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            this.sidebarVoiceMessage = 'La búsqueda por voz no está disponible en este navegador.';
            return;
        }

        const recognition = new SpeechRecognition();
        recognition.lang = 'es-MX';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        this.sidebarListening = true;
        this.sidebarVoiceMessage = 'Escuchando…';

        recognition.onresult = (event) => {
            this.sidebarSearch = event.results[0][0].transcript;
            this.sidebarVoiceMessage = '';
            this.updateSidebarMatches();
        };
        recognition.onerror = () => {
            this.sidebarVoiceMessage = 'No fue posible reconocer la voz. Inténtalo nuevamente.';
        };
        recognition.onend = () => {
            this.sidebarListening = false;
        };
        recognition.start();
    }
}">

    <!-- ===================== BARRA SUPERIOR ===================== -->
    <nav id="main-nav" class="fixed right-0 top-0 z-[90] flex h-[70px] items-center bg-[#1a3a6b] px-[20px]">
        <div class="flex w-full items-center justify-end">
            <div class="hidden items-center justify-start rtl:justify-end">
                <div class="hidden items-center">
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
                @if ($supportsDarkTheme)
                    <button type="button" @click="toggleDarkMode()"
                        class="hidden"
                        :aria-label="darkMode ? 'Activar tema claro' : 'Activar tema oscuro'"
                        :title="darkMode ? 'Tema claro' : 'Tema oscuro'">
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                        <svg x-show="darkMode" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    </button>
                @endif
                <!-- Notificaciones en tiempo real -->
                <livewire:notification-center />

                <!-- AVATAR -->
                <div class="hidden items-center">
                    <x-dropdown
                        align="right"
                        width="72"
                        contentClasses="overflow-hidden rounded-2xl bg-white p-0 ring-0"
                        dropdownClasses="rounded-2xl border border-zinc-200 shadow-[0_18px_45px_rgba(0,0,0,0.14)]"
                    >
                        <x-slot name="content">
                            <div class="border-b border-zinc-200 p-[20px] text-black">
                                <p class="text-[20px] font-semibold">Mi cuenta</p>
                                <div class="mt-[15px] flex min-w-0 items-center gap-[15px]">
                                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-full border border-zinc-200 bg-zinc-100">
                                        @if(Auth::user()->profile_photo_path)
                                            <img class="h-full w-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="Foto de {{ Auth::user()->name }}">
                                        @else
                                            <span class="text-[15px] font-semibold text-black">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1).mb_substr(Auth::user()->last_name ?? '', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-[15px] font-semibold" title="{{ trim(Auth::user()->name.' '.Auth::user()->last_name) }}">{{ trim(Auth::user()->name.' '.Auth::user()->last_name) }}</p>
                                        <p class="mt-1 truncate text-[15px] text-zinc-500" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-[10px]">
                                <a href="{{ route('profile.show') }}" class="flex h-10 w-full items-center gap-[15px] rounded-xl px-[15px] text-[15px] font-semibold text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0">
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                                    Mi perfil
                                </a>
                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <a href="{{ route('api-tokens.index') }}" class="mt-[10px] flex h-10 w-full items-center gap-[15px] rounded-xl px-[15px] text-[15px] font-semibold text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0">
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a4 4 0 11-7.75 1.36L3 12.61V16h3v3h3v2h4l2.64-2.64A4 4 0 0015 7z" /></svg>
                                    Tokens de API
                                </a>
                            @endif
                            </div>

                            <div class="border-t border-zinc-200 p-[10px]">
                              <form method="POST" action="{{ route('logout') }}" x-data data-session-logout>
                                @csrf
                                <button type="button" @click.prevent="$root.requestSubmit();" class="flex h-10 w-full items-center gap-[15px] rounded-xl px-[15px] text-left text-[15px] font-semibold text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0">
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 17l5-5-5-5m5 5H3m10-9h6a2 2 0 012 2v14a2 2 0 01-2 2h-6" /></svg>
                                    Cerrar sesión
                                </button>
                              </form>
                            </div>
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
        class="fixed left-0 top-0 z-[100] h-screen
            flex flex-col bg-zinc-100 shadow-sm
            overflow-hidden"
    >

        <div class="flex h-[85px] shrink-0 items-start gap-[10px] px-[15px] pt-[30px]">
            <img x-show="!collapsed" x-cloak src="{{ asset('img/static/small-logo-gaa.png') }}" alt="Logotipo de González Alonzo y Asociados" class="h-10 w-10 shrink-0 object-contain">
            <div x-show="!collapsed" class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-[14px] font-semibold text-black">González Alonzo y</p>
                <p class="mt-[4px] truncate text-[14px] font-semibold text-black">Asociados S.C.P.</p>
            </div>
            <button @click="collapsed = !collapsed" type="button" class="flex h-[50px] w-[50px] shrink-0 items-center justify-center text-zinc-600 transition-colors hover:text-black focus:outline-none focus:ring-0" :aria-label="collapsed ? 'Expandir Menú' : 'Comprimir Menú'" :title="collapsed ? 'Expandir Menú' : 'Comprimir Menú'">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path stroke-linecap="round" d="M9 3v18" />
                </svg>
            </button>
        </div>

        <div class="relative shrink-0 bg-zinc-100 p-[15px]" @click.outside="sidebarSearchOpen = false">
            <div x-show="!collapsed" class="relative">
                <svg class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
                <input
                    id="sidebar-section-search"
                    x-ref="sidebarSearchInput"
                    x-model="sidebarSearch"
                    @input.debounce.120ms="updateSidebarMatches()"
                    @focus="sidebarSearch && updateSidebarMatches()"
                    @keydown.escape="sidebarSearchOpen = false"
                    type="search"
                    placeholder="Buscar Apartados..."
                    aria-label="Buscar Apartados"
                    class="h-[50px] w-full rounded-xl border border-zinc-300 bg-white py-[12px] pl-[45px] pr-[48px] text-[15px] text-black outline-none transition placeholder:text-zinc-500 focus:!border-zinc-300 focus:!outline-none focus:!ring-0 focus:!shadow-none focus-visible:!outline-none"
                >
                <button
                    type="button"
                    @click="startSidebarVoiceSearch()"
                    :class="sidebarListening ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-black'"
                    class="absolute right-[7px] top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg transition focus:outline-none focus:ring-0"
                    aria-label="Buscar Apartados por Voz"
                    title="Buscar por Voz"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Zm-7 9a7 7 0 0 0 14 0M12 19v3m-4 0h8" /></svg>
                </button>
            </div>

            <button
                x-show="collapsed"
                x-cloak
                type="button"
                @click="openCompactSidebarSearch()"
                class="flex h-[50px] w-[50px] items-center justify-center rounded-xl border border-zinc-300 bg-white text-zinc-600 transition hover:border-zinc-400 hover:text-black focus:outline-none focus:ring-0"
                aria-label="Abrir Buscador de Apartados"
                title="Buscar Apartados"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
            </button>

            <div
                x-show="!collapsed && sidebarSearchOpen"
                x-cloak
                class="absolute left-[15px] right-[15px] top-[80px] z-[100] rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_16px_35px_rgba(0,0,0,0.14)]"
            >
                <template x-if="sidebarMatches.length > 0">
                    <div class="relative">
                        <div x-ref="sidebarSearchResults" x-init="$nextTick(() => updateSidebarSearchScrollbar())" @scroll="updateSidebarSearchScrollbar()" class="sidebar-search-results-scrollbar h-[210px] snap-y snap-mandatory scroll-smooth overflow-y-auto">
                            <template x-for="result in sidebarMatches" :key="result.url">
                                <a :href="result.url" class="flex h-[42px] snap-start items-center gap-[12px] rounded-lg px-[12px] text-[15px] font-medium text-black transition-colors duration-150 hover:bg-zinc-200">
                                    <svg class="h-4 w-4 shrink-0 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6" /></svg>
                                    <span class="truncate" x-text="result.label"></span>
                                </a>
                            </template>
                        </div>
                        <span
                            x-show="sidebarSearchThumbVisible"
                            class="sidebar-search-overlay-scrollbar"
                            :style="`height: ${sidebarSearchThumbHeight}px; transform: translateY(${sidebarSearchThumbTop}px);`"
                            aria-hidden="true"
                        ></span>
                    </div>
                </template>
                <p x-show="sidebarMatches.length === 0" class="px-[12px] py-[10px] text-[15px] text-zinc-500">No se encontraron apartados.</p>
            </div>

            <p x-show="!collapsed && sidebarVoiceMessage" x-text="sidebarVoiceMessage" class="mt-[8px] text-[13px] text-zinc-600"></p>

        </div>

        <!-- Contenedor del menú -->
        <div class="sidebar-menu-scrollbar flex-1 px-0 pb-4 overflow-y-auto h-full overscroll-contain"
            :style="collapsed ? 'opacity: 1;' : 'opacity: 1; transition: opacity 300ms ease-in-out;'">
            <div x-show="!collapsed" class="shrink-0 px-[15px] pt-[15px]">
                <p class="text-[15px] font-medium leading-none text-black">General</p>
            </div>

            <ul class="font-medium inline-block min-w-full m-0 p-0">

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
                            <rect x="4" y="4" width="6" height="6" rx="1" />
                            <rect x="14" y="4" width="6" height="6" rx="1" />
                            <rect x="4" y="14" width="6" height="6" rx="1" />
                            <rect x="14" y="14" width="6" height="6" rx="1" />
                        </svg>
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Panel General</span>
                    </a>
                </li>

                <li class="px-[15px] pt-[15px] m-0" :class="collapsed ? 'hidden' : 'block'" aria-hidden="true">
                    <div class="border-t border-zinc-200"></div>
                </li>

                <!-- ========================================================== -->
                <!-- SECCIÓN: INFORMACIÓN (oculta en modo compacto)             -->
                <!-- ========================================================== -->
                <li class="pt-[30px] px-[15px] pb-0 m-0" :class="collapsed ? 'hidden' : 'block'">
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
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Supervisión de Horas</span>
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

                @can('manage-system-backups')
                    <li class="relative pt-[15px] px-[15px] pb-0 m-0">
                        <a href="{{ route('activity-backups.index') }}"
                            :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                            class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 font-medium {{ request()->routeIs('activity-backups.*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <svg class="w-5 h-5 shrink-0 transition-colors {{ request()->routeIs('activity-backups.*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <ellipse cx="12" cy="5" rx="8" ry="3" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5v7c0 1.66 3.58 3 8 3s8-1.34 8-3V5M4 12v7c0 1.66 3.58 3 8 3s8-1.34 8-3v-7" />
                            </svg>
                            <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Gestión de Respaldos</span>
                        </a>
                    </li>
                @endcan

                @canany(['manage-delivery-notes', 'manage-backup-reports'])
                    <li x-data="{ openDocuments: {{ request()->routeIs('soporte.hoja-entrega*', 'soporte.reportes-respaldos') ? 'true' : 'false' }} }" class="relative pt-[15px] px-[15px] pb-0 m-0">
                        <button type="button" @click="openDocuments = !openDocuments"
                            :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-between p-[15px]'"
                            class="group flex w-full items-center rounded-xl text-[15px] font-medium text-black transition-all duration-200 {{ request()->routeIs('soporte.hoja-entrega*', 'soporte.reportes-respaldos') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <span class="flex items-center">
                                <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.hoja-entrega*', 'soporte.reportes-respaldos') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 3h7l4 4v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 3v5h5M10 12h6M10 16h6" />
                                </svg>
                                <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Documentos</span>
                            </span>
                            <svg class="ml-[15px] h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" :class="openDocuments ? 'rotate-180' : 'rotate-0'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openDocuments" x-transition class="relative" style="display: none;">
                            <div class="absolute left-[22px] top-[15px] bottom-0 w-[2px] bg-gray-200"></div>
                            <ul class="w-full">
                                @can('manage-delivery-notes')
                                    <li class="pt-[15px] m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                        <a href="{{ route('soporte.hoja-entrega') }}" class="group flex w-full items-center rounded-xl p-[15px] text-[15px] text-black transition-all duration-200 {{ request()->routeIs('soporte.hoja-entrega*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                            <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.hoja-entrega*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M8 3h8l4 4v14H4V3h4Zm7 0v5h5" /></svg>
                                            <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Hoja de Entrega</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('manage-backup-reports')
                                    <li class="pt-[15px] m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                        <a href="{{ route('soporte.reportes-respaldos') }}" class="group flex w-full items-center rounded-xl p-[15px] text-[15px] text-black transition-all duration-200 {{ request()->routeIs('soporte.reportes-respaldos') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                            <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.reportes-respaldos') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m5 10V5m5 14v-7m5 7V7M3 21h18" /></svg>
                                            <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Reporte de Respaldos</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endcanany

                <!-- ========================================================== -->
                <!-- SECCIÓN: SOPORTE (oculta en modo compacto)                 -->
                <!-- ========================================================== -->
                <li class="hidden pt-[15px] px-[15px] pb-0 m-0">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Soporte</span>
                </li>

                <!-- ========================================================== -->
                <!-- SUBMENÚ: Centro de ayuda (con flecha siempre visible)      -->
                <!-- ========================================================== -->
                <li x-data="{ openSoporte: {{ request()->routeIs('soporte.ticket', 'soporte.preguntas') ? 'true' : 'false' }} }" class="hidden relative pt-[15px] px-[15px] pb-0 m-0">
                    <button @click="openSoporte = !openSoporte" 
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-between p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 cursor-pointer font-medium {{ request()->routeIs('soporte.ticket', 'soporte.preguntas') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 transition-colors {{ request()->routeIs('soporte.ticket', 'soporte.preguntas') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Centro de Ayuda</span>
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
                                    <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Chat de Soporte</span>
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

        <footer @click.outside="profileMenuOpen = false" class="relative shrink-0 bg-zinc-100 px-[15px] py-[30px]">
            <div class="absolute left-[15px] right-[15px] top-0 border-t border-zinc-200" aria-hidden="true"></div>
            <div x-show="profileMenuOpen" x-cloak class="z-[120] overflow-hidden rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_18px_45px_rgba(0,0,0,0.16)]" :class="collapsed ? 'fixed bottom-[15px] left-[130px] w-[280px]' : 'absolute bottom-[calc(100%+15px)] left-[15px] right-[15px]'">
                <a href="{{ route('soporte.preguntas') }}" class="group flex items-center gap-[15px] rounded-xl p-[15px] text-[15px] font-medium text-black transition-all duration-200 {{ request()->routeIs('soporte.preguntas') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }}">
                    <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.preguntas') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H6.5A2.5 2.5 0 0 0 4 19.5v-14Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v16a2 2 0 0 1 2-2h2.5a2.5 2.5 0 0 1 2.5 2.5v-14Z"/></svg>
                    <span class="truncate">Centro de Ayuda</span>
                </a>
                <a href="{{ route('soporte.ticket') }}" class="group mt-[15px] flex items-center gap-[15px] rounded-xl p-[15px] text-[15px] font-medium text-black transition-all duration-200 {{ request()->routeIs('soporte.ticket') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }}">
                    <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.ticket') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/><path stroke-linecap="round" stroke-width="1.8" d="M8 9h8M8 13h5"/></svg>
                    <span class="truncate">Soporte Técnico</span>
                </a>
                <a href="{{ route('profile.show') }}" class="group mt-[15px] flex items-center gap-[15px] rounded-xl p-[15px] text-[15px] font-medium text-black transition-all duration-200 {{ request()->routeIs('profile.show') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }}">
                    <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('profile.show') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                    <span class="truncate">Mi Cuenta</span>
                </a>
                <button type="button" @click="toggleDarkMode()" class="group mt-[15px] flex w-full items-center gap-[15px] rounded-xl p-[15px] text-left text-[15px] font-medium text-black transition-all duration-200 hover:bg-gray-100 focus:outline-none focus:ring-0" :class="darkMode ? 'bg-gray-100' : 'bg-gray-50'" :title="darkMode ? 'Cambiar a Tema Claro' : 'Cambiar a Tema Oscuro'">
                    <svg x-show="!darkMode" class="h-5 w-5 shrink-0 text-gray-400 transition-colors group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    <svg x-show="darkMode" x-cloak class="h-5 w-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <span class="min-w-0 flex-1 truncate" x-text="darkMode ? 'Tema Claro' : 'Tema Oscuro'"></span>
                    <span class="relative h-[22px] w-[38px] shrink-0 rounded-full border border-zinc-300 transition-colors" :class="darkMode ? 'border-blue-600 bg-blue-600' : 'bg-zinc-200'" aria-hidden="true">
                        <span class="absolute top-[2px] h-4 w-4 rounded-full bg-white shadow-sm transition-transform" :class="darkMode ? 'translate-x-[18px]' : 'translate-x-[2px]'"></span>
                    </span>
                </button>
                <div class="my-[15px] border-t border-zinc-200"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-[15px] rounded-xl bg-gray-50 p-[15px] text-left text-[15px] font-medium text-red-600 transition-all duration-200 hover:bg-red-50 focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 17 5-5-5-5M20 12H9m4 9H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8"/></svg>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>

            <button type="button" @click="profileMenuOpen = !profileMenuOpen" :aria-expanded="profileMenuOpen" class="flex min-h-[50px] w-full items-center text-left transition focus:outline-none focus:ring-0" :class="collapsed ? 'justify-center' : 'gap-[12px]'" title="Abrir Menú de Usuario">
                <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full border border-zinc-200 bg-zinc-100">
                    @if(Auth::user()->profile_photo_path)
                        <img class="h-full w-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="Foto de {{ Auth::user()->name }}">
                    @else
                    <span class="text-[15px] font-semibold text-black">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1).mb_substr(Auth::user()->last_name ?? '', 0, 1)) }}</span>
                    @endif
                </span>
                <span x-show="!collapsed" class="min-w-0 flex-1">
                    <span class="flex min-w-0 items-center gap-[8px]">
                        <strong class="block max-w-[145px] truncate text-[15px] text-black" title="{{ trim(Auth::user()->name.' '.(Auth::user()->last_name ?? '')) }}">{{ trim(Auth::user()->name.' '.(Auth::user()->last_name ?? '')) }}</strong>
                        <span class="grid h-[18px] w-[18px] shrink-0 place-items-center rounded-full bg-blue-600 text-white" title="Cuenta Verificada" aria-label="Cuenta Verificada">
                            <svg class="h-[12px] w-[12px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="m7 12 3 3 7-7"/></svg>
                        </span>
                    </span>
                    <span class="mt-[4px] block max-w-[180px] truncate text-[15px] leading-none text-zinc-500" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</span>
                </span>
                <svg x-show="!collapsed" class="h-5 w-5 shrink-0 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6"/></svg>
            </button>
        </footer>
    </aside>
    <!-- ============================================================ -->
    <!-- FIN DEL MENÚ LATERAL                                          -->
    <!-- ============================================================ -->
    <script>
        document.addEventListener('alpine:init', () => {
            const sidebar = document.getElementById('logo-sidebar');
            const menuScroller = sidebar?.querySelector('.sidebar-menu-scrollbar');

            if (sidebar && menuScroller && !sidebar.querySelector('.sidebar-overlay-scrollbar')) {
                const overlayThumb = document.createElement('span');
                overlayThumb.className = 'sidebar-overlay-scrollbar';
                overlayThumb.setAttribute('aria-hidden', 'true');
                sidebar.appendChild(overlayThumb);

                const updateOverlayScrollbar = () => {
                    const viewportHeight = menuScroller.clientHeight;
                    const contentHeight = menuScroller.scrollHeight;

                    if (contentHeight <= viewportHeight || viewportHeight <= 0) {
                        overlayThumb.style.display = 'none';
                        return;
                    }

                    const thumbHeight = Math.max(32, viewportHeight * viewportHeight / contentHeight);
                    const availableTravel = Math.max(0, viewportHeight - thumbHeight);
                    const scrollRange = Math.max(1, contentHeight - viewportHeight);
                    const thumbTop = menuScroller.offsetTop + (menuScroller.scrollTop / scrollRange) * availableTravel;

                    overlayThumb.style.display = 'block';
                    overlayThumb.style.height = `${thumbHeight}px`;
                    overlayThumb.style.transform = `translateY(${thumbTop}px)`;
                };

                menuScroller.addEventListener('scroll', updateOverlayScrollbar, { passive: true });
                new ResizeObserver(updateOverlayScrollbar).observe(menuScroller);
                new MutationObserver(updateOverlayScrollbar).observe(menuScroller, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                });
                requestAnimationFrame(updateOverlayScrollbar);
            }

            Alpine.effect(() => {

                const sidebar = document.getElementById('logo-sidebar');
                const main = document.getElementById('main-content');
                const nav = document.getElementById('main-nav');

                if (!sidebar || !main) return;

                new ResizeObserver(() => {

                    const sidebarWidth = sidebar.getBoundingClientRect().width + 'px';
                    main.style.marginLeft = sidebarWidth;
                    if (nav) nav.style.left = sidebarWidth;

                }).observe(sidebar);

            });

        });
    </script>
</div>
<!-- ============================================================ -->
<!-- FIN DEL CONTENEDOR PRINCIPAL                                  -->
<!-- ============================================================ -->
