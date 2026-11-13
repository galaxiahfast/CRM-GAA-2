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
    $canTimeActivities = $permissionAccess->allows($currentUser, 'activities.manage');
    $canTimeClock = $permissionAccess->allows($currentUser, 'time-control.clock.use');
    $canTimeProductivity = $permissionAccess->allows($currentUser, 'time-control.productivity.view');
    $canTimeSupervision = $permissionAccess->allows($currentUser, 'time-control.supervision.view');
    $canTimeControl = $canTimeActivities || $canTimeClock || $canTimeProductivity || $canTimeSupervision;
    $canOrganizationChart = $permissionAccess->allows($currentUser, 'administration.organization.manage');
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

        const links = Array.from(document.querySelectorAll('#logo-sidebar a[href]'));
        const seen = new Set();

        this.sidebarMatches = links.map((link) => ({
            label: link.textContent.replace(/\s+/g, ' ').trim(),
            url: link.href,
        })).filter((item) => {
            if (!item.label || seen.has(item.url) || (query && !this.normalizeSidebarText(item.label).includes(query))) {
                return false;
            }

            seen.add(item.url);
            return true;
        });
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
    containSidebarWheel(event, scroller) {
        if (!scroller) return;

        const atTop = scroller.scrollTop <= 0;
        const atBottom = Math.ceil(scroller.scrollTop + scroller.clientHeight) >= scroller.scrollHeight;

        if ((event.deltaY < 0 && atTop) || (event.deltaY > 0 && atBottom)) {
            event.preventDefault();
        }

        event.stopPropagation();
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
    <nav id="main-nav" class="fixed right-0 top-0 z-[90] flex h-[123px] items-center bg-[#1a3a6b] {{ request()->routeIs('activity-backups.*', 'bank-reconciliation.*', 'profile.show') ? 'px-[50px]' : (request()->routeIs('inicio', 'time.index', 'time.reports', 'testing.managercontrol.*', 'testing.own-hours.*', 'time.dashboard', 'time.admin.attendance', 'time.attendance', 'administracion.*', 'organigrama.*', 'soporte.ticket', 'soporte.reportes-tecnicos*', 'soporte.reportes-respaldos', 'soporte.preguntas', 'profiles.show', 'dashboard', 'customers.view') ? 'px-[20px] sm:px-[30px] xl:px-[50px]' : 'px-[20px]') }}">
        <div class="flex w-full items-center justify-between gap-[30px]">
            <button type="button" @click="collapsed = !collapsed" :aria-expanded="!collapsed" class="mobile-menu-button" aria-label="Menú de navegación" title="Menú de navegación">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            @if (request()->routeIs('inicio'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>General</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Inicio</span>
                </div>
            @elseif (request()->routeIs('time.index'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span>Control de Actividades</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Cronómetro</span>
                </div>
            @elseif (request()->routeIs('time.dashboard'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span>Control de Actividades</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Gráficos Generales</span>
                </div>
            @elseif (request()->routeIs('time.reports'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span>Control de Actividades</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Productividad</span>
                </div>
            @elseif (request()->routeIs('testing.managercontrol.*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Manager Control</span>
                </div>
            @elseif (request()->routeIs('testing.own-hours.*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span>Control de Actividades</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Manager Control</span>
                </div>
            @elseif (request()->routeIs('time.admin.attendance', 'time.attendance'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Reloj Checador</span>
                </div>
            @elseif (request()->routeIs('activity-backups.*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Monitoreo</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Gestión de Respaldos</span>
                </div>
            @elseif (request()->routeIs('bank-reconciliation.*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Información</span>
                    <span class="text-white">›</span>
                    <span class="text-white">{{ request()->route('tool') === 'ocr' ? 'Conciliación OCR' : 'Conciliación Bancaria' }}</span>
                </div>
            @elseif (request()->routeIs('organigrama.*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Información</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Organigrama</span>
                </div>
            @elseif (request()->routeIs('administracion.*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Información</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Administración</span>
                </div>
            @elseif (request()->routeIs('soporte.reportes-tecnicos*'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Documentos</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Reportes de soporte</span>
                </div>
            @elseif (request()->routeIs('soporte.ticket'))
                <div class="flex min-w-0 flex-wrap items-center gap-[10px] text-[15px] font-normal text-white sm:gap-[15px]">
                    <span>Soporte</span>
                    <span aria-hidden="true">›</span>
                    <span>Centro de ayuda</span>
                    <span aria-hidden="true">›</span>
                    <a href="{{ route('soporte.ticket') }}" class="font-semibold text-white transition-opacity hover:opacity-75" aria-current="page">Chat general</a>
                </div>
            @elseif (request()->routeIs('soporte.reportes-respaldos'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Documentos</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Reporte de Respaldos</span>
                </div>
            @elseif (request()->routeIs('soporte.preguntas'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Soporte</span>
                    <span class="text-white">›</span>
                    <span>Centro de ayuda</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Preguntas</span>
                </div>
            @elseif (request()->routeIs('dashboard'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Inicio</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Panel general</span>
                </div>
            @elseif (request()->routeIs('customers.view'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Inicio</span>
                    <span class="text-white">›</span>
                    <a href="{{ route('dashboard') }}" class="transition-colors hover:text-white"
                        onclick="try { const r = JSON.parse(sessionStorage.getItem('customer-detail-return') || 'null'); if (r && r.url) { event.preventDefault(); window.location.href = r.url; } } catch (e) {}">Panel general</a>
                    <span class="text-white">›</span>
                    <span class="text-white">Detalle del cliente</span>
                </div>
            @elseif (request()->routeIs('profile.show'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Mi cuenta</span>
                    <span class="text-white">›</span>
                    <span class="text-white">Ajustes</span>
                </div>
            @elseif (request()->routeIs('profiles.show'))
                <div class="flex min-w-0 items-center gap-[15px] text-[15px] font-normal text-white">
                    <span>Información</span>
                    <span class="text-white">›</span>
                    <a href="{{ route('profiles.index') }}" class="transition-colors hover:text-white">Perfiles</a>
                    <span class="text-white">›</span>
                    <span class="text-white">Perfil de usuario</span>
                </div>
            @endif

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
            
            <div class="ml-auto flex items-center gap-[15px]">
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

                <a href="{{ route('soporte.preguntas') }}"
                    class="flex h-[42px] w-[42px] items-center justify-center rounded-lg border-0 bg-transparent text-white transition-colors duration-200 hover:bg-white/10 focus:outline-none focus:ring-0"
                    aria-label="Abrir ayuda"
                    title="Ayuda">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.7 9a2.5 2.5 0 1 1 4.05 1.96c-.92.72-1.75 1.16-1.75 2.54M12 17h.01" />
                    </svg>
                </a>

                <livewire:support.chat-badge />

                <a href="{{ route('profile.show') }}"
                    class="flex h-[42px] w-[42px] items-center justify-center rounded-lg border-0 bg-transparent text-white transition-colors duration-200 hover:bg-white/10 focus:outline-none focus:ring-0"
                    aria-label="Abrir ajustes"
                    title="Ajustes">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.86 2.86-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.55v-.1A1.7 1.7 0 0 0 8.5 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.86-2.86.06-.06A1.7 1.7 0 0 0 4.1 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2.3V9.55h.1A1.7 1.7 0 0 0 4.1 8.5a1.7 1.7 0 0 0-.34-1.88l-.06-.06L6.56 3.7l.06.06A1.7 1.7 0 0 0 8.5 4.1a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1v-.1h4.05v.1A1.7 1.7 0 0 0 15 4.1a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.86 2.86-.06.06A1.7 1.7 0 0 0 19.4 8.5a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.1v4.05h-.1A1.7 1.7 0 0 0 19.4 15Z" />
                    </svg>
                </a>

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
    {{-- Pantallas angostas: en tableta el menú sigue en el diseño (riel de 80px) y empuja el contenido; en celular se muestra como un panel bajo la barra superior, sin tapar ni difuminar nada --}}
    <style>
        .mobile-menu-button { display: none; height: 42px; width: 42px; flex-shrink: 0; align-items: center; justify-content: center; border: 1px solid rgba(255, 255, 255, 0.6); border-radius: 8px; background: transparent; color: #ffffff; cursor: pointer; transition: background-color 150ms ease; }
        .mobile-menu-button:hover { background-color: rgba(255, 255, 255, 0.12); }
        /* Sin contorno de enfoque en el menú lateral, el encabezado y el cierre de sesión: tras pulsar un botón el navegador
           a veces lo remarcaba (outline / aro de enfoque) y no se quiere ese contorno */
        #logo-sidebar :is(button, a, summary, [tabindex]):is(:focus, :focus-visible, :active),
        #main-nav :is(button, a, summary, [tabindex]):is(:focus, :focus-visible, :active),
        [data-session-logout] :is(button, a):is(:focus, :focus-visible, :active) { outline: none !important; box-shadow: none !important; }
        #logo-sidebar, #main-nav, [data-session-logout] { -webkit-tap-highlight-color: transparent; }
        @media (max-width: 639px) {
            .mobile-menu-button { display: flex; }
            #main-content { margin-left: 0 !important; }
            #main-nav { left: 0 !important; padding-left: 20px !important; padding-right: 20px !important; }
            #main-nav > div > div.min-w-0 > :not(:last-child) { display: none; }
            #logo-sidebar { top: 123px !important; height: calc(100dvh - 123px) !important; width: 100% !important; box-shadow: none; }
            #logo-sidebar[data-collapsed="true"] { display: none; }
        }
    </style>

    <aside
        id="logo-sidebar"
        :data-collapsed="collapsed ? 'true' : 'false'"
        class="fixed left-0 top-0 z-[100] h-screen
            flex flex-col bg-zinc-100 shadow-sm
            overflow-hidden"
    >

        <div class="flex shrink-0 items-center gap-[15px] px-[15px] pb-[25px] pt-[40px]">
            <img x-show="!collapsed" x-transition.opacity.duration.250ms x-cloak src="{{ asset('img/static/logo-principal_.png') }}" alt="Logotipo de González Alonzo y Asociados" class="sidebar-brand-logo h-[43px] w-[30px] shrink-0 object-contain">
            <div x-show="!collapsed" x-transition.opacity.duration.250ms class="min-w-0 flex-1 leading-none">
                <p class="truncate text-[14px] font-semibold text-black">González Alonzo y</p>
                <p class="mt-[15px] truncate text-[14px] font-semibold text-black">Asociados S.C.P.</p>
            </div>
            <button @click="collapsed = !collapsed" type="button" :class="collapsed ? 'ml-[20px]' : ''" class="flex h-[43px] w-5 shrink-0 items-center justify-center text-zinc-600 transition-colors hover:text-black focus:outline-none focus:ring-0" :aria-label="collapsed ? 'Expandir Menú' : 'Comprimir Menú'" :title="collapsed ? 'Expandir Menú' : 'Comprimir Menú'">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path stroke-linecap="round" d="M9 3v18" />
                </svg>
            </button>
        </div>

        <div class="relative shrink-0 bg-zinc-100 p-[15px]" @click.outside="sidebarSearchOpen = false">
            <div x-show="!collapsed" x-transition.opacity.duration.250ms class="relative">
                <svg class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
                <input
                    id="sidebar-section-search"
                    x-ref="sidebarSearchInput"
                    x-model="sidebarSearch"
                    @input.debounce.120ms="updateSidebarMatches()"
                    @focus="updateSidebarMatches()"
                    @click="updateSidebarMatches()"
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
                class="flex h-[50px] w-[60px] items-center justify-center rounded-xl border border-zinc-300 bg-white text-zinc-600 transition hover:border-zinc-400 hover:text-black focus:outline-none focus:ring-0"
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
                    <div class="sidebar-search-scroll-region relative">
                        <div x-ref="sidebarSearchResults" x-init="$nextTick(() => updateSidebarSearchScrollbar())" @scroll="updateSidebarSearchScrollbar()" @wheel="containSidebarWheel($event, $refs.sidebarSearchResults)" class="sidebar-search-results-scrollbar h-[210px] snap-y snap-mandatory scroll-smooth overflow-y-auto">
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
        <div x-ref="sidebarMenuScroller" @wheel="containSidebarWheel($event, $refs.sidebarMenuScroller)" class="sidebar-menu-scrollbar flex-1 px-0 pb-[20px] overflow-y-auto h-full overscroll-contain"
            :style="collapsed ? 'opacity: 1;' : 'opacity: 1; transition: opacity 300ms ease-in-out;'">
            <div x-show="!collapsed" x-transition.opacity.duration.250ms class="shrink-0 px-[15px] pb-[15px] pt-[25px]">
                <p class="text-[15px] font-medium leading-none text-black">General</p>
            </div>

            <ul class="font-medium inline-block min-w-full m-0 p-0">

                <!-- ========================================================== -->
                <!-- BOTÓN: Inicio (siempre visible)                            -->
                <!-- ========================================================== -->
                <li class="pt-[20px] px-[15px] pb-0 m-0">
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
                <li class="pt-[20px] px-[15px] pb-0 m-0">
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

                <li class="px-[15px] pt-[20px] m-0" :class="collapsed ? 'hidden' : 'block'" aria-hidden="true">
                    <div class="border-t border-zinc-200"></div>
                </li>

                <!-- ========================================================== -->
                <!-- SECCIÓN: MONITOREO (oculta en modo compacto)               -->
                <!-- ========================================================== -->
                <li class="pt-[25px] px-[15px] pb-[15px] m-0" :class="collapsed ? 'hidden' : 'block'">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Monitoreo</span>
                </li>

                <!-- ========================================================== -->
                <!-- Reloj Checador y Control de Actividades -->
                <!-- ========================================================== -->
                @php $isTimeAdmin = $canTimeControl && $canTimeSupervision; @endphp
                @if ($canTimeControl)
                    @if ($isTimeAdmin || $canTimeClock)
                    <li class="relative pt-[20px] px-[15px] pb-0 m-0">
                        <a href="{{ $isTimeAdmin ? route('time.admin.attendance') : route('time.attendance') }}"
                            :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-start p-[15px]'"
                            class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.attendance', 'time.admin.attendance') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <x-hugeicons-clock-01 class="w-5 h-5 transition-colors {{ request()->routeIs('time.attendance', 'time.admin.attendance') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                            <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Reloj Checador</span>
                        </a>
                    </li>
                    @endif
                    @if ($isTimeAdmin)
                    <li class="relative pt-[20px] px-[15px] pb-0 m-0">
                        <a href="{{ route('time.admin.dashboard') }}"
                            :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-start p-[15px]'"
                            class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.admin.dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.admin.dashboard') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-6-6h12" /></svg>
                            <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Supervisión de Horas</span>
                        </a>
                    </li>
                    @endif
                    @if ($isTimeAdmin || $canTimeActivities || $canTimeProductivity)
                    <li x-data="{ open: window.sidebarOpenGet('time-activities'), init() { this.$watch('open', (value) => window.sidebarOpenSet('time-activities', value)); } }" class="relative pt-[20px] px-[15px] pb-0 m-0">
                        <button @click="open = !open"
                            :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-between p-[15px]'"
                            class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 cursor-pointer font-medium {{ request()->routeIs('time.index', 'time.reports', 'time.dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.index', 'time.reports', 'time.dashboard') ? 'text-green-600' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4" /></svg>
                                <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Control de Actividades</span>
                            </div>
                            <div class="ml-[15px] shrink-0">
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open, 'rotate-0': !open }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        <!-- Sub-elementos del submenú -->
                        <div x-show="open"
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
                            <div class="absolute left-[29px] top-[10px] bottom-0 w-[2px] bg-gray-200"></div>
                            <ul class="space-y-0 w-full">
@if (! $isTimeAdmin && $canTimeActivities)
<li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.index') }}" 
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.index', 'time.reports') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.index', 'time.reports') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Cronómetro</span>
                                    </a>
                                </li>
@endif
@can('view-legacy-own-hours')
@unless (auth()->user()->isAdmin())
<li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('testing.own-hours.index') }}"
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('testing.own-hours.*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('testing.own-hours.*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6M10 3v6.5L5 19a1.5 1.5 0 0 0 1.3 2h11.4A1.5 1.5 0 0 0 19 19l-5-9.5V3" /></svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Manager Control</span>
                                    </a>
                                </li>
@endunless
@endcan
@if ($isTimeAdmin || $canTimeProductivity)
<li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('time.dashboard') }}"
                                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                                        class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('time.dashboard') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('time.dashboard') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                        </svg>
                                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Gráficos Generales</span>
                                    </a>
                                </li>
@endif
                            </ul>
                        </div>
                    </li>
                    @endif
                @endif

                @can('access-system-backups')
                    <li class="relative pt-[20px] px-[15px] pb-0 m-0">
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

                @can('view-legacy-managercontrol')
                    <li class="relative pt-[20px] px-[15px] pb-0 m-0">
                        <a href="{{ route('testing.managercontrol.index') }}" data-sidebar-label="Manager Control"
                            :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                            class="group flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('testing.managercontrol.*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <svg class="w-5 h-5 shrink-0 transition-colors {{ request()->routeIs('testing.managercontrol.*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6M10 3v6.5L5 19a1.5 1.5 0 0 0 1.3 2h11.4A1.5 1.5 0 0 0 19 19l-5-9.5V3" /></svg>
                            <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Manager Control</span>
                        </a>
                    </li>
                @endcan

                <!-- ========================================================== -->
                <!-- SECCIÓN: INFORMACIÓN (oculta en modo compacto)             -->
                <!-- ========================================================== -->
                <li class="pt-[25px] px-[15px] pb-[15px] m-0" :class="collapsed ? 'hidden' : 'block'">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Información</span>
                </li>

                <!-- ========================================================== -->
                <!-- BOTÓN: Organigrama (apartado propio, antes dentro de Administración) -->
                <!-- ========================================================== -->
                @if ($canOrganizationChart)
                <li class="pt-[20px] px-[15px] pb-0 m-0">
                    <a href="{{ route('organigrama.index') }}"
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('organigrama.*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} group whitespace-nowrap">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('organigrama.*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m-6 4h12M6 12v4m12-4v4M3 16h6v4H3zm12 0h6v4h-6zM9 8h6v4H9z" /></svg>
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Organigrama</span>
                    </a>
                </li>
                @endif

                <!-- ========================================================== -->
                <!-- BOTÓN: Administración (siempre visible)                    -->
                <!-- ========================================================== -->
                @if ($canAdministrationSection)
                <li class="pt-[20px] px-[15px] pb-0 m-0">
                    <a href="{{ $administrationRoute }}"
                        :class="collapsed ? 'justify-start pl-[15px] pr-[15px] py-[15px]' : 'justify-start p-[15px]'"
                        class="flex items-center w-full text-[15px] text-black rounded-xl transition-all duration-200 {{ request()->routeIs('administracion.index') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} group whitespace-nowrap">
                        <x-hugeicons-user-add-01 class="w-5 h-5 transition-colors {{ request()->routeIs('administracion.index') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" />
                        <span class="ms-[15px] h-[15px] leading-none flex items-center" :class="collapsed ? 'hidden' : 'inline'">Administración</span>
                    </a>
                </li>
                @endif

                <li x-data="{ openBankReconciliation: window.sidebarOpenGet('bank'), init() { this.$watch('openBankReconciliation', (value) => window.sidebarOpenSet('bank', value)); } }" class="relative pt-[20px] px-[15px] pb-0 m-0">
                    <button type="button" @click="openBankReconciliation = !openBankReconciliation"
                        :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-between p-[15px]'"
                        class="group flex w-full items-center rounded-xl text-[15px] font-medium text-black transition-all duration-200 {{ request()->routeIs('bank-reconciliation.*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                        <span class="flex items-center">
                            <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('bank-reconciliation.*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M6 10h12M5 14h5m4 0h5M5 18h5m4 0h5M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" />
                            </svg>
                            <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Conciliación Bancaria</span>
                        </span>
                        <svg class="ml-[15px] h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" :class="openBankReconciliation ? 'rotate-180' : 'rotate-0'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="openBankReconciliation" x-transition :class="collapsed ? 'pl-[15px]' : 'block'" class="relative" style="display: none;">
                        <div class="absolute left-[29px] top-[10px] bottom-0 w-[2px] bg-gray-200"></div>
                        <ul class="w-full">
                            <li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                <a href="{{ route('bank-reconciliation.show', 'ocr') }}" class="group flex w-full items-center rounded-xl p-[15px] text-[15px] text-black transition-all duration-200 {{ request()->routeIs('bank-reconciliation.*') && request()->route('tool') === 'ocr' ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                    <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('bank-reconciliation.*') && request()->route('tool') === 'ocr' ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7V4h3m10 0h3v3M4 17v3h3m10 0h3v-3M7 12h10M8 9h8M8 15h8" /></svg>
                                    <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Conciliación OCR</span>
                                </a>
                            </li>
                            <li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                <a href="{{ route('bank-reconciliation.show', 'bancaria') }}" class="group flex w-full items-center rounded-xl p-[15px] text-[15px] text-black transition-all duration-200 {{ request()->routeIs('bank-reconciliation.*') && request()->route('tool') === 'bancaria' ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                    <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('bank-reconciliation.*') && request()->route('tool') === 'bancaria' ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M6 10h12M6 14h5m3 0h4M6 18h5m3 0h4" /></svg>
                                    <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Conciliación Bancaria</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>


                    <li class="pt-[25px] px-[15px] pb-[15px] m-0" :class="collapsed ? 'hidden' : 'block'">
                        <span class="flex h-[15px] items-center text-[15px] font-medium leading-none text-black">Documentos</span>
                    </li>

                                @can('manage-delivery-notes')
                                    <li class="pt-[20px] px-[15px] pb-0 m-0 w-full">
                                        <a href="{{ route('soporte.hoja-entrega') }}" data-sidebar-label="Hoja de Entrega" :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-start p-[15px]'" class="group flex w-full items-center rounded-xl text-[15px] text-black transition-all duration-200 {{ request()->routeIs('soporte.hoja-entrega*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                            <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.hoja-entrega*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M8 3h8l4 4v14H4V3h4Zm7 0v5h5" /></svg>
                                            <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Hoja de Entrega</span>
                                        </a>
                                    </li>
                                @endcan
                    @canany(['view-support-reports', 'manage-backup-reports'])
                    <li x-data="{ openReports: window.sidebarOpenGet('reports'), init() { this.$watch('openReports', (value) => window.sidebarOpenSet('reports', value)); } }" class="relative pt-[20px] px-[15px] pb-0 m-0">
                        <button type="button" @click="openReports = !openReports" :aria-expanded="openReports" data-sidebar-label="Reportes"
                            :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-between p-[15px]'"
                            class="group flex w-full items-center rounded-xl text-[15px] font-medium text-black transition-all duration-200 {{ request()->routeIs('soporte.reportes-respaldos', 'soporte.reportes-tecnicos*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                            <span class="flex min-w-0 items-center">
                                <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.reportes-respaldos', 'soporte.reportes-tecnicos*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                                <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Reportes</span>
                            </span>
                            <svg class="ml-[15px] h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" :class="openReports ? 'rotate-180' : 'rotate-0'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openReports" x-transition :class="collapsed ? 'pl-[15px]' : 'block'" class="relative" style="display: none;">
                            <div class="absolute bottom-0 left-[22px] top-[15px] w-[2px] bg-gray-200"></div>
                            <ul class="w-full">
                                @can('view-support-reports')
                                <li class="m-0 w-full pb-0 pl-[27px] pr-0 pt-[20px]" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                    <a href="{{ route('soporte.reportes-tecnicos') }}" data-sidebar-label="Reportes de Soporte" :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-start p-[15px]'" class="group flex w-full items-center rounded-xl text-[15px] text-black transition-all duration-200 {{ request()->routeIs('soporte.reportes-tecnicos*') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
                                        <svg class="h-5 w-5 shrink-0 transition-colors {{ request()->routeIs('soporte.reportes-tecnicos*') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                                        <span class="ms-[15px] flex h-[15px] items-center leading-none" :class="collapsed ? 'hidden' : 'inline'">Reportes de Soporte</span>
                                    </a>
                                </li>
                                @endcan
                                @can('manage-backup-reports')
                                    <li class="m-0 w-full pb-0 pl-[27px] pr-0 pt-[20px]" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
                                        <a href="{{ route('soporte.reportes-respaldos') }}" data-sidebar-label="Reporte de Respaldos" :class="collapsed ? 'justify-start px-[15px] py-[15px]' : 'justify-start p-[15px]'" class="group flex w-full items-center rounded-xl text-[15px] text-black transition-all duration-200 {{ request()->routeIs('soporte.reportes-respaldos') ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100' }} whitespace-nowrap">
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
                <li class="hidden pt-[20px] px-[15px] pb-0 m-0">
                    <span class="block text-[15px] font-medium text-black h-[15px] leading-none flex items-center">Soporte</span>
                </li>

                <!-- ========================================================== -->
                <!-- SUBMENÚ: Centro de ayuda (con flecha siempre visible)      -->
                <!-- ========================================================== -->
                <li x-data="{ openSoporte: window.sidebarOpenGet('help'), init() { this.$watch('openSoporte', (value) => window.sidebarOpenSet('help', value)); } }" class="relative pt-[20px] px-[15px] pb-0 m-0">
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
                        <div class="absolute left-[29px] top-[10px] bottom-0 w-[2px] bg-gray-200"></div>
                        <ul class="space-y-0 w-full">
                            <!-- Ticket -->
                            <li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
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
                            <li class="pt-[20px] pl-[27px] pr-0 pb-0 m-0 w-full" :class="collapsed ? 'pl-[15px]' : 'pl-[37px]'">
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

        <footer @click.outside="profileMenuOpen = false" class="relative shrink-0 bg-zinc-100 px-[15px] py-[20px]">
            <div class="absolute left-[15px] right-[15px] top-0 border-t border-zinc-200" aria-hidden="true"></div>
            <div x-show="profileMenuOpen" x-cloak data-profile-menu class="fixed z-[120] w-[280px] overflow-hidden rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_18px_45px_rgba(0,0,0,0.16)]" :style="profileMenuOpen ? `left: ${$el.closest('#logo-sidebar').offsetWidth + 20}px; bottom: 20px;` : ''">
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

            <button type="button" @click="profileMenuOpen = !profileMenuOpen" :aria-expanded="profileMenuOpen" class="flex min-h-[50px] w-full items-center rounded-xl px-[20px] py-[15px] text-left transition focus:outline-none focus:ring-0" :class="collapsed ? 'justify-center' : 'gap-[12px]'" title="Abrir Menú de Usuario">
                <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full border border-zinc-200 bg-zinc-100">
                    @if(Auth::user()->profile_photo_path)
                        <img class="h-full w-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="Foto de {{ Auth::user()->name }}">
                    @else
                    <span class="text-[15px] font-semibold text-black">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1).mb_substr(Auth::user()->last_name ?? '', 0, 1)) }}</span>
                    @endif
                </span>
                <span x-show="!collapsed" x-transition.opacity.duration.250ms class="min-w-0 flex-1">
                    <span class="flex min-w-0 items-center gap-[8px]">
                        <strong class="block max-w-[145px] truncate text-[15px] text-black" title="{{ trim(Auth::user()->name.' '.(Auth::user()->last_name ?? '')) }}">{{ trim(Auth::user()->name.' '.(Auth::user()->last_name ?? '')) }}</strong>
                        <span class="grid h-[18px] w-[18px] shrink-0 place-items-center rounded-full bg-blue-600 text-white" title="Cuenta Verificada" aria-label="Cuenta Verificada">
                            <svg class="h-[12px] w-[12px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="m7 12 3 3 7-7"/></svg>
                        </span>
                    </span>
                    <span class="mt-[4px] block max-w-[180px] truncate text-[15px] leading-none text-zinc-500" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</span>
                </span>
                <svg x-show="!collapsed" x-transition.opacity.duration.250ms class="h-5 w-5 shrink-0 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6"/></svg>
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

            if (sidebar && !document.querySelector('.sidebar-collapsed-tooltip')) {
                const tooltip = document.createElement('div');
                tooltip.className = 'sidebar-collapsed-tooltip';
                tooltip.setAttribute('role', 'tooltip');
                document.body.appendChild(tooltip);

                let activeTrigger = null;

                const hideCollapsedTooltip = () => {
                    activeTrigger = null;
                    tooltip.style.display = 'none';
                };

                const tooltipLabel = (trigger) => {
                    const explicitLabel = trigger.dataset.sidebarLabel
                        || trigger.getAttribute('aria-label')
                        || trigger.getAttribute('title');

                    if (explicitLabel) return explicitLabel.trim();

                    return Array.from(trigger.querySelectorAll('span'))
                        .map((span) => span.textContent.replace(/\s+/g, ' ').trim())
                        .find((label) => label.length > 1) || '';
                };

                const showCollapsedTooltip = (trigger) => {
                    if (trigger.closest('[data-profile-menu]')) { hideCollapsedTooltip(); return; }

                    // Con el menú de usuario abierto, el globo del botón taparía «Cerrar Sesión».
                    if (trigger.getAttribute('aria-expanded') === 'true') { hideCollapsedTooltip(); return; }

                    if (sidebar.dataset.collapsed !== 'true') {
                        hideCollapsedTooltip();
                        return;
                    }

                    const label = tooltipLabel(trigger);
                    if (!label) return;

                    activeTrigger = trigger;
                    tooltip.textContent = label;
                    tooltip.style.display = 'block';

                    const triggerRect = trigger.getBoundingClientRect();
                    const tooltipRect = tooltip.getBoundingClientRect();
                    const top = Math.min(
                        window.innerHeight - tooltipRect.height - 10,
                        Math.max(10, triggerRect.top + ((triggerRect.height - tooltipRect.height) / 2)),
                    );

                    tooltip.style.left = `${Math.round(sidebar.getBoundingClientRect().right + 20)}px`;
                    tooltip.style.top = `${Math.round(top)}px`;
                };

                sidebar.addEventListener('pointerover', (event) => {
                    const trigger = event.target.closest('a[href], button');
                    if (!trigger || !sidebar.contains(trigger) || trigger === activeTrigger) return;
                    // Los globos no deben aparecer sobre el menú de usuario: ya muestra sus textos y los taparían.
                    if (trigger.closest('[data-profile-menu]')) { hideCollapsedTooltip(); return; }
                    showCollapsedTooltip(trigger);
                });

                sidebar.addEventListener('pointerout', (event) => {
                    if (!activeTrigger || activeTrigger.contains(event.relatedTarget)) return;
                    hideCollapsedTooltip();
                });

                sidebar.addEventListener('focusin', (event) => {
                    const trigger = event.target.closest('a[href], button');
                    if (trigger && sidebar.contains(trigger)) showCollapsedTooltip(trigger);
                });
                sidebar.addEventListener('focusout', hideCollapsedTooltip);
                sidebar.addEventListener('click', (event) => {
                    const trigger = event.target.closest('a[href], button');
                    if (trigger) setTimeout(() => { if (trigger.getAttribute('aria-expanded') === 'true') hideCollapsedTooltip(); }, 0);
                });
                menuScroller?.addEventListener('scroll', hideCollapsedTooltip, { passive: true });
                window.addEventListener('resize', hideCollapsedTooltip, { passive: true });

                new MutationObserver(() => {
                    if (sidebar.dataset.collapsed !== 'true') hideCollapsedTooltip();
                }).observe(sidebar, { attributes: true, attributeFilter: ['data-collapsed'] });
            }

            Alpine.effect(() => {

                const sidebar = document.getElementById('logo-sidebar');
                const main = document.getElementById('main-content');
                const nav = document.getElementById('main-nav');

                if (!sidebar || !main) return;

                // El zoom de cada apartado (el control de la esquina inferior derecha) escala solo su contenido con
                // `transform: scale(...)` y un ancho de 10000 / zoom %. Aquí se lee ese mismo factor y se aplica también al
                // menú lateral y al encabezado, para que todo el marco se vea al mismo tamaño en cualquier apartado.
                // Todas las pantallas se ven al mismo tamaño base (el más pequeño de los que se usan: 0.9), para que al cambiar de
                // apartado no haya que alejar ni acercar. Si el apartado ya aplica su propia escala se usa esa; si no, el contenido
                // recibe este mismo 0.9 mediante --app-zoom (ver appApplyZoom en auto-scale.blade.php).
                const BASE_SCALE = 0.9;
                let ownScale = false;
                const manualScale = () => {
                    ownScale = false;
                    if (window.innerWidth / window.appAutoScale() < 1024) return 1;

                    for (const element of main.querySelectorAll('[style*="scale("]')) {
                        const style = element.getAttribute('style') || '';
                        if (!/width:\s*[\d.]+%/.test(style) || /translate/.test(style)) continue;
                        const match = style.match(/transform:\s*scale\(([\d.]+)\)/);
                        const value = match ? parseFloat(match[1]) : NaN;
                        if (value >= 0.5 && value <= 1.5) { ownScale = true; return value; }
                    }

                    return BASE_SCALE;
                };

                // Factor final del marco: el zoom elegido en el apartado por el zoom automático según el ancho de la ventana
                // (lienzo de 1800x950: 1 en pantallas grandes y, en las más pequeñas, se reduce hasta 0.85). Ver auto-scale.blade.php.
                const pageScale = () => manualScale() * (window.appAutoScale ? window.appAutoScale() : 1);

                const applyShell = () => {
                    const scale = pageScale();
                    window.appContentBase = (ownScale || window.innerWidth / window.appAutoScale() < 1024) ? 1 : BASE_SCALE;
                    if (window.appApplyZoom) window.appApplyZoom();
                    const zoomed = Math.abs(scale - 1) > 0.001;

                    sidebar.style.zoom = zoomed ? String(scale) : '';
                    sidebar.style.height = zoomed ? (window.innerHeight / scale) + 'px' : '';
                    if (nav) nav.style.zoom = zoomed ? String(scale) : '';

                    // Ancho visual del menú ya con el zoom: de ahí salen el margen del contenido y el inicio del encabezado
                    const left = sidebar.getBoundingClientRect().width;
                    main.style.marginLeft = left + 'px';
                    main.style.paddingTop = zoomed ? (123 * scale) + 'px' : '';

                    if (nav) {
                        nav.style.left = (left / scale) + 'px';
                        nav.style.right = zoomed ? 'auto' : '';
                        nav.style.width = zoomed ? ((document.documentElement.clientWidth - left) / scale) + 'px' : '';
                    }
                };

                let queued = false;
                const queueShell = () => {
                    if (queued) return;
                    queued = true;
                    requestAnimationFrame(() => { queued = false; applyShell(); });
                };

                new ResizeObserver(queueShell).observe(sidebar);
                new MutationObserver((records) => {
                    if (records.some((record) => record.target !== main)) queueShell();
                }).observe(main, { attributes: true, attributeFilter: ['style'], subtree: true, childList: true });
                window.addEventListener('resize', queueShell, { passive: true });
                document.addEventListener('livewire:navigated', queueShell);
                applyShell();
                // La animación de comprimir/descomprimir se activa tras el primer pintado para que no se anime al cargar.
                window.addEventListener('load', () => setTimeout(() => sidebar.classList.add('sidebar-animate'), 150), { once: true });

            });

        });
    </script>
</div>
<!-- ============================================================ -->
<!-- FIN DEL CONTENEDOR PRINCIPAL                                  -->
<!-- ============================================================ -->
