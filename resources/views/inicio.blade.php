@php
    $user = auth()->user();
    $permissions = app(\App\Services\Authorization\PermissionAccessService::class);

    $now = now(config('support.timezone', 'America/Mexico_City'))->locale('es');
    $hour = (int) $now->format('G');
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $today = ucfirst($now->translatedFormat('l, d \d\e F \d\e Y'));
    $roleName = $user->role?->role;

    $canAdministration = $permissions->allows($user, 'administration.organization.manage')
        || $permissions->allows($user, 'administration.users.manage')
        || $permissions->allows($user, 'administration.roles.manage')
        || $permissions->allows($user, 'administration.permissions.manage')
        || $permissions->allows($user, 'administration.assignments.manage');
    $administrationRoute = $permissions->allows($user, 'administration.organization.manage')
        ? route('administracion.index')
        : ($permissions->allows($user, 'administration.users.manage')
            ? route('administracion.section')
            : ($permissions->allows($user, 'administration.roles.manage')
                ? route('administracion.role')
                : ($permissions->allows($user, 'administration.permissions.manage')
                    ? route('administracion.permissions')
                    : route('administracion.interns'))));

    // Iconos (trazos de 24x24). Cada acceso solo aparece si la persona puede entrar a esa pantalla, con las mismas
    // reglas que el menú lateral.
    $icons = [
        'dashboard' => 'M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-14h6V3h-6v4Z',
        'bank' => 'M3 6h18M6 10h12M5 14h5m4 0h5M5 18h5m4 0h5M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z',
        'ocr' => 'M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M8 12h8M8 8.5h8M8 15.5h5',
        'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'check' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'chart' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        'eye' => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'org' => 'M12 4v4m-6 4h12M6 12v4m12-4v4M3 16h6v4H3zm12 0h6v4h-6zM9 8h6v4H9z',
        'database' => 'M20 5c0 1.66-3.58 3-8 3S4 6.66 4 5s3.58-3 8-3 8 1.34 8 3ZM4 5v7c0 1.66 3.58 3 8 3s8-1.34 8-3V5M4 12v7c0 1.66 3.58 3 8 3s8-1.34 8-3v-7',
        'delivery' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z',
        'report' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'server' => 'M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.337 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z',
        'ticket' => 'M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.142-4.03 7.5-9 7.5a10.2 10.2 0 0 1-4.15-.86L3 20.25l1.54-3.86A6.9 6.9 0 0 1 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z',
        'help' => 'M9.879 7.519c.678-.75 1.642-1.219 2.711-1.219 2.034 0 3.68 1.69 3.68 3.775 0 1.768-1.189 3.252-2.79 3.662-.555.142-.98.645-.98 1.218v.545M12.5 18.25h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'account' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    ];

    $sections = [];

    $general = [
        ['Dashboard', 'Consulta clientes, archivos e indicadores.', route('dashboard'), 'dashboard'],
    ];
    $sections['General'] = $general;

    $information = [];
    if ($permissions->allows($user, 'administration.organization.manage')) {
        $information[] = ['Organigrama', 'Estructura jerárquica y líneas de mando.', route('organigrama.index'), 'org'];
    }
    if ($canAdministration) {
        $information[] = ['Administración', 'Colaboradores, roles, permisos y organización.', $administrationRoute, 'users'];
    }
    $information[] = ['Conciliación bancaria', 'Concilia tus movimientos bancarios.', route('bank-reconciliation.show', 'bancaria'), 'bank'];
    $information[] = ['Conciliación OCR', 'Concilia a partir de documentos escaneados.', route('bank-reconciliation.show', 'ocr'), 'ocr'];
    $sections['Información'] = $information;

    $activities = [];
    if ($permissions->allows($user, 'activities.manage')) {
        $activities[] = ['Registro de actividades', 'Captura y consulta tus actividades.', route('time.index'), 'check'];
    }
    if ($permissions->allows($user, 'time-control.clock.use')) {
        $activities[] = ['Reloj Checador', 'Registra tu entrada, salida y descansos.', route('time.attendance'), 'clock'];
    }
    if ($permissions->allows($user, 'time-control.productivity.view')) {
        $activities[] = ['Mi productividad', 'Revisa tu avance y tus horas.', route('time.reports'), 'chart'];
    }
    if ($permissions->allows($user, 'time-control.supervision.view')) {
        $activities[] = ['Supervisión de horas', 'Consulta y corrige la asistencia del equipo.', route('time.admin.attendance'), 'eye'];
    }
    if (Gate::allows('access-system-backups')) {
        $activities[] = ['Gestión de respaldos', 'Sube, consulta y exporta respaldos.', route('activity-backups.index'), 'database'];
    }
    if ($activities !== []) {
        $sections['Actividades'] = $activities;
    }

    $documents = [];
    if (Gate::allows('manage-delivery-notes')) {
        $documents[] = ['Hoja de entrega', 'Genera hojas de entrega de equipo.', route('soporte.hoja-entrega'), 'delivery'];
    }
    $documents[] = ['Reportes de soporte', 'Genera y consulta reportes de servicios técnicos.', route('soporte.reportes-tecnicos'), 'report'];
    if (Gate::allows('manage-backup-reports')) {
        $documents[] = ['Reporte de respaldos', 'Revisa el estado de los respaldos por servidor.', route('soporte.reportes-respaldos'), 'server'];
    }
    $sections['Documentos'] = $documents;

    $sections['Soporte'] = [
        ['Soporte técnico', 'Solicita ayuda mediante un ticket.', route('soporte.ticket'), 'ticket'],
        ['Preguntas', 'Consulta respuestas y orientación rápida.', route('soporte.preguntas'), 'help'],
        ['Mi cuenta', 'Tus datos, seguridad y preferencias.', route('profile.show'), 'account'],
    ];
@endphp

@php
    $sectionMeta = [
        'General' => ['dashboard', 'Tus herramientas principales de trabajo.'],
        'Información' => ['users', 'Organigrama, administración y conciliaciones.'],
        'Actividades' => ['check', 'Registro de actividades, asistencia y respaldos.'],
        'Documentos' => ['report', 'Hojas de entrega y reportes.'],
        'Soporte' => ['ticket', 'Ayuda, preguntas y tu cuenta.'],
    ];
@endphp
<x-app-layout>
    <div data-panel data-home class="relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 text-[15px]">
        <x-panel-styles />

        <style>
            /* Tema oscuro del inicio: superficies más cerradas (casi negras) que las del resto de los paneles. */
            html.module-dark-theme [data-home] {
                --panel-page: #09090b;
                --panel-surface: #09090b;
                --panel-header: #0f0f12;
                --panel-subtle: #0c0c0f;
                --panel-hover: #131316;
                --panel-border: #27272a;
            }
            html.module-dark-theme [data-home] { background-color: var(--panel-page); }
            /* Solo dos colores de texto: blanco (negro en tema claro) y azul; los enlaces y botones van en verde. */
            [data-home] { --home-green: #15803d; color: var(--panel-text); }
            html.module-dark-theme [data-home] { --home-green: #4ade80; --panel-text: #ffffff; }
            [data-home] .home-link { color: var(--home-green); }
            [data-home] .home-row,
            [data-home] .home-card { transition: background-color 150ms ease; }
            [data-home] .home-row:hover,
            [data-home] .home-card:hover { background-color: var(--panel-subtle); }
            html.module-dark-theme [data-home] .home-row:hover,
            html.module-dark-theme [data-home] .home-card:hover { background-color: var(--panel-hover, var(--panel-subtle)); }
            @media (prefers-reduced-motion: reduce) { [data-home] .home-row, [data-home] .home-card { transition: none; } }
        </style>


        <script>
            window.homeClock = window.homeClock || function (timeZone) {
                return {
                    html: '',
                    timer: null,
                    render() {
                        let text;
                        try {
                            text = new Intl.DateTimeFormat('es-MX', { timeZone: timeZone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }).format(new Date());
                        } catch (error) {
                            text = new Date().toLocaleTimeString('es-MX');
                        }
                        this.html = text.split('').map(function (c) {
                            return /\d/.test(c) ? '<span class="inline-block w-[0.65em] text-center">' + c + '</span>' : c;
                        }).join('');
                    },
                    start() {
                        this.render();
                        this.timer = setInterval(() => this.render(), 1000);
                    },
                    destroy() { clearInterval(this.timer); },
                };
            };
        </script>

        <main class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
            <!-- Cabecera: título y subtítulo a la izquierda; rol y fecha a la derecha -->
            <section class="mb-[30px] flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                    <span class="panel-surface panel-border panel-text grid h-[50px] w-[50px] shrink-0 place-items-center rounded-xl border">
                        <svg class="h-[30px] w-[30px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">Hola, {{ $user->name }}</h1>
                        <p class="mt-[15px] leading-none">Sesión iniciada correctamente · {{ $greeting }}</p>
                    </div>
                </div>
                <div class="w-full min-w-0 lg:w-auto lg:text-right">
                    <p class="panel-accent leading-none">{{ $roleName ?: 'Portal interno' }}</p>
                    <p class="mt-[15px] leading-none">{{ $today }}</p>
                    {{-- Cada dígito va en una celda de ancho fijo para que la hora no se mueva (el 1 es más angosto que el 0). --}}
                    <p class="mt-[15px] leading-none" aria-label="Hora actual"
                       x-data="homeClock(@js(config('support.timezone', 'America/Mexico_City')))"
                       x-init="start()"
                       x-html="html">{{ $now->format('h:i:s a') }}</p>
                </div>
            </section>

            <div class="grid items-start gap-y-[20px] xl:gap-x-[50px] xl:grid-cols-[minmax(0,1fr)_400px]">
                {{-- Accesos: una caja por grupo, con cabecera gris y celdas separadas por líneas de 1px --}}
                <div class="flex min-w-0 flex-col gap-[50px]">
                    @foreach ($sections as $title => $cards)
                        @php([$metaIcon, $metaNote] = $sectionMeta[$title] ?? ['dashboard', ''])
                        <section class="panel-surface panel-border overflow-hidden rounded-xl border" aria-label="{{ $title }}">
                            <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                                <div class="min-w-0">
                                    <p class="panel-accent flex items-center gap-[10px] leading-none">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$metaIcon] }}" /></svg>
                                        <span>{{ $title }}</span>
                                    </p>
                                    @if ($metaNote)
                                        <p class="mt-[15px] leading-none">{{ $metaNote }}</p>
                                    @endif
                                </div>
                                <span class="leading-none">{{ count($cards) }} {{ count($cards) === 1 ? 'acceso' : 'accesos' }}</span>
                            </header>
                            <div class="-mb-px -mr-px grid [grid-template-columns:repeat(auto-fill,minmax(260px,1fr))]">
                                @foreach ($cards as [$cardTitle, $description, $url, $icon])
                                    <a href="{{ $url }}" class="home-card panel-border block border-b border-r p-[20px]">
                                        <span class="home-link flex items-center gap-[10px] leading-none">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}" /></svg>
                                            <span class="min-w-0">{{ $cardTitle }}</span>
                                        </span>
                                        <span class="mt-[7.5px] -mb-[7.5px] block leading-[30px]">{{ $description }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <livewire:home.online-users lazy />
            </div>
        </main>
    </div>
</x-app-layout>
