@php
    $user = auth()->user();
    $permissions = app(\App\Services\Authorization\PermissionAccessService::class);

    $now = now(config('support.timezone', 'America/Mexico_City'))->locale('es');
    $hour = (int) $now->format('G');
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $today = ucfirst($now->translatedFormat('l, d \d\e F \d\e Y'));
    $roleName = $user->role?->role;

    // Solo quien tiene el panel de Administración completo (administradores) ve este acceso, igual que en el menú lateral
    $canAdministration = $permissions->allows($user, 'administration.organization.manage');
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

    // Mismos grupos, nombres y reglas que el menú lateral
    // Tarjetas con animación (vídeo en bucle) que acompañan a algunas cajas: [vídeo, título, texto, enlace]
    // Animación (vídeo corto en bucle, como un GIF) de los accesos que la tienen; los demás muestran su icono grande
    $illustrations = [
        'Organigrama' => 'optimizacion-seo',
        'Panel General' => 'panel-movil',
        'Reloj Checador' => 'cronometro-movil',
        'Cronómetro' => 'cronometro-movil',
        'Supervisión de Horas' => 'ecualizador',
        'Manager Control' => 'panel-movil',
        'Gráficos Generales' => 'ecualizador',
        'Gestión de Respaldos' => 'carpeta-codificacion',
        'Administración' => 'aplicaciones-moviles',
        'Conciliación Bancaria' => 'optimizacion-seo',
        'Conciliación OCR' => 'carpeta-codificacion',
        'Confirmaciones de Saldos' => 'panel-movil',
        'Hoja de Entrega' => 'aplicaciones-moviles',
        'Reportes de Soporte' => 'optimizacion-seo',
        'Reporte de Respaldos' => 'carpeta-codificacion',
        'Preguntas' => 'aplicaciones-moviles',
        'Chat de Soporte' => 'ecualizador',
        'Mi Cuenta' => 'contrasena-usuario',
    ];

    $sections = [];

    $sections['General'] = [];
    if ($permissions->allows($user, 'administration.organization.manage')) {
        $sections['General'][] = ['Organigrama', 'Estructura jerárquica y líneas de mando.', route('organigrama.index'), 'org'];
    }
    $sections['General'][] = ['Panel General', 'Consulta clientes, archivos e indicadores.', route('dashboard'), 'dashboard'];

    $canActivities = $permissions->allows($user, 'activities.manage');
    $canClock = $permissions->allows($user, 'time-control.clock.use');
    $canProductivity = $permissions->allows($user, 'time-control.productivity.view');
    $canSupervision = $permissions->allows($user, 'time-control.supervision.view');
    $isTimeAdmin = ($canActivities || $canClock || $canProductivity || $canSupervision) && $canSupervision;

    $monitoring = [];
    if ($isTimeAdmin || $canClock) {
        $monitoring[] = ['Reloj Checador', 'Registra tu entrada, salida y descansos.', $isTimeAdmin ? route('time.admin.attendance') : route('time.attendance'), 'clock'];
    }
    if ($isTimeAdmin) {
        $monitoring[] = ['Supervisión de Horas', 'Consulta y corrige la asistencia del equipo.', route('time.admin.dashboard'), 'eye'];
    }
    if (! $isTimeAdmin && $canActivities) {
        $monitoring[] = ['Cronómetro', 'Registra tus actividades y el tiempo de cada una.', route('time.index'), 'check'];
    }
    if (Gate::allows('view-legacy-own-hours')) {
        $monitoring[] = ['Manager Control', 'Consulta tus horas registradas.', route('testing.own-hours.index'), 'report'];
    }
    if ($isTimeAdmin || $canProductivity) {
        $monitoring[] = ['Gráficos Generales', 'Revisa el avance y las horas por persona y cliente.', route('time.dashboard'), 'chart'];
    }
    if (Gate::allows('access-system-backups')) {
        $monitoring[] = ['Gestión de Respaldos', 'Sube, consulta y exporta respaldos.', route('activity-backups.index'), 'database'];
    }
    if (Gate::allows('view-legacy-managercontrol')) {
        $monitoring[] = ['Manager Control', 'Administra las horas registradas del equipo.', route('testing.managercontrol.index'), 'report'];
    }
    if ($monitoring !== []) {
        $sections['Monitoreo'] = $monitoring;
    }

    $information = [];
    if ($permissions->allows($user, 'administration.organization.manage')) {
        $information[] = ['Administración', 'Colaboradores, roles, permisos y organización.', $administrationRoute, 'users'];
    }
    $information[] = ['Conciliación Bancaria', 'Concilia tus movimientos bancarios.', route('bank-reconciliation.show', 'bancaria'), 'bank'];
    $information[] = ['Conciliación OCR', 'Concilia a partir de documentos escaneados.', route('bank-reconciliation.show', 'ocr'), 'ocr'];
    $information[] = ['Confirmaciones de Saldos', 'Prepara y da seguimiento a las confirmaciones.', route('confirmaciones.show', 'confirmaciones'), 'check'];
    $sections['Información'] = $information;

    $documents = [];
    if (Gate::allows('manage-delivery-notes')) {
        $documents[] = ['Hoja de Entrega', 'Genera hojas de entrega de equipo.', route('soporte.hoja-entrega'), 'delivery'];
    }
    if (Gate::allows('view-support-reports')) {
        $documents[] = ['Reportes de Soporte', 'Genera y consulta reportes de servicios técnicos.', route('soporte.reportes-tecnicos'), 'report'];
    }
    if (Gate::allows('manage-backup-reports')) {
        $documents[] = ['Reporte de Respaldos', 'Revisa el estado de los respaldos por servidor.', route('soporte.reportes-respaldos'), 'server'];
    }
    if ($documents !== []) {
        $sections['Documentos'] = $documents;
    }

    $sections['Soporte'] = [
        ['Preguntas', 'Consulta respuestas y orientación rápida.', route('soporte.preguntas'), 'help'],
        ['Chat de Soporte', 'Habla con el equipo de soporte y da seguimiento.', route('soporte.ticket'), 'ticket'],
        ['Mi Cuenta', 'Tus datos, seguridad y preferencias.', route('profile.show'), 'account'],
    ];
@endphp

@php
    $sectionMeta = [
        'General' => ['dashboard', 'Estructura de la organización y avance de los clientes.'],
        'Monitoreo' => ['clock', 'Asistencia, actividades, horas y respaldos del equipo.'],
        'Información' => ['users', 'Colaboradores, permisos y conciliaciones de los clientes.'],
        'Documentos' => ['report', 'Hojas de entrega, reportes de soporte y respaldos.'],
        'Soporte' => ['ticket', 'Preguntas frecuentes, chat de soporte y tu cuenta.'],
    ];
@endphp
<x-app-layout>
    <div data-panel data-home class="relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 text-[15px]">
        <x-panel-styles />

        <style>
            /* Tema oscuro del inicio: superficies más cerradas (casi negras) que las del resto de los paneles. */
            html.module-dark-theme [data-panel][data-home] {
                --panel-page: #09090b;
                --panel-surface: #09090b;
                --panel-header: #0f0f12;
                --panel-subtle: #0c0c0f;
                --panel-hover: #131316;
                --panel-border: #52525b;
            }
            html.module-dark-theme [data-home] { background-color: var(--panel-page); }
            /* Solo dos colores de texto: blanco (negro en tema claro) y azul; los enlaces y botones van en verde. */
            [data-panel][data-home] { --panel-border: #a1a1aa; }
            [data-home] { --home-green: #15803d; color: var(--panel-text); }
            html.module-dark-theme [data-home] { --home-green: #4ade80; --panel-text: #ffffff; }
            [data-home] .home-link { color: var(--home-green); }
            [data-home] .home-row,
            [data-home] .home-card { transition: background-color 150ms ease; }
            [data-home] .home-row:hover,
            [data-home] .home-card:not(.home-tcard):hover { background-color: var(--panel-subtle); }
            html.module-dark-theme [data-home] .home-row:hover,
            html.module-dark-theme [data-home] .home-card:not(.home-tcard):hover { background-color: var(--panel-hover, var(--panel-subtle)); }
            @media (prefers-reduced-motion: reduce) { [data-home] .home-row, [data-home] .home-card { transition: none; } }
            /* ---- Animaciones sutiles de Inicio ----
               Al entrar: la cabecera y cada caja suben un poco y aparecen en orden; dentro de cada caja los accesos
               aparecen uno tras otro. Las personas en línea se van desplegando hacia abajo, una por una. Al pasar el
               mouse, el acceso se eleva apenas y su nombre se desliza unos píxeles. */
            @keyframes home-rise { from { opacity: 0; } to { opacity: 1; } }
            @keyframes home-drop { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: none; } }
            [data-home] .home-rise { animation: home-rise 520ms cubic-bezier(.22, .61, .36, 1) both; animation-delay: calc(var(--i, 0) * 90ms); }
            [data-home] .home-card { animation: home-rise 420ms cubic-bezier(.22, .61, .36, 1) both; animation-delay: calc(var(--d, 0) * 1ms); position: relative; }
            [data-home] .home-card .home-card-title { display: inline-block; transition: transform 200ms ease; }
            
            [data-home] .home-card::after { content: ''; position: absolute; left: 0; bottom: -1px; height: 2px; width: 100%; background: var(--home-green); transform: scaleX(0); transform-origin: left; transition: transform 250ms ease; }
            
            [data-home] .home-online-row { animation: home-drop 420ms cubic-bezier(.22, .61, .36, 1) both; animation-delay: calc(var(--i, 0) * 80ms + 150ms); }
            [data-home] .home-online-row .home-row { transition: background-color 150ms ease, padding-left 200ms ease; }
            [data-home] .home-online-row .home-row:hover { padding-left: 25px; }
            [data-home] .home-online-dot { animation: home-pulse 2.4s ease-in-out infinite; }
            @keyframes home-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .45); } 50% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); } }
            @media (prefers-reduced-motion: reduce) {
                [data-home] .home-rise, [data-home] .home-card, [data-home] .home-online-row, [data-home] .home-online-dot { animation: none; }
                [data-home] .home-card::after, [data-home] .home-card .home-card-title, [data-home] .home-online-row .home-row { transition: none; }
            }
            /* Tarjeta vertical con animación, como una pantalla de presentación: ilustración arriba, título y texto, y pie con puntos y acción */
            [data-home] .home-illus { grid-column: auto / -1; }
            [data-home] .home-tgrid { display: flex; flex-wrap: wrap; gap: 20px; }
            [data-home] .home-tgrid > .home-tcard { flex: 0 0 280px; width: 280px; }
            [data-home] .home-tcard { cursor: grab; }
            [data-home] .home-tcard.is-dragging { opacity: .4; cursor: grabbing; }
            [data-home] .home-tcard { display: flex; min-height: 400px; flex-direction: column; border-radius: 20px; background: var(--panel-surface); border: 1px solid var(--panel-border); overflow: hidden; }
            [data-home] .home-tcard::after { display: none; }
            [data-home] .home-tcard-visual { display: flex; flex: 1 1 auto; min-height: 0; padding: 20px 20px 0; align-items: center; justify-content: center; }
            [data-home] .home-tcard-visual video { height: 150px; width: 150px; object-fit: contain; mix-blend-mode: multiply; pointer-events: none; }
            [data-home] .home-tcard-icon { height: 100px; width: 100px; color: var(--panel-text); }
                        html.module-dark-theme [data-home] .home-tcard-visual video { filter: invert(1); mix-blend-mode: screen; }
            [data-home] .home-tcard-body { display: block; flex: none; padding: 20px 20px 0; }
            /* Tarjetas: 20px exactos entre todo. Las líneas de 15px van a 35px (20px entre líneas) y se quita el medio
               interlineado de arriba y abajo (10px) para que no deje espacios de más */
            [data-home] .home-tcard-text { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; margin-top: 10px; margin-bottom: -10px; font-size: 15px; line-height: 35px; }
            [data-home] .home-tcard-body > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            /* Encabezado de cada sección: mismo diseño (título de 15px y descripción de 17px con 20px exactos entre líneas) */
            [data-home] .home-sec-title { display: block; line-height: 15px; margin: 0; padding: 0; }
            [data-home] .home-sec-text { margin: 10px 0 -10px; padding: 0; font-size: 15px; line-height: 35px; }
            [data-home] .home-sec-count { line-height: 15px; }
            [data-home] .home-tcard-foot > span { line-height: 1; }
            [data-home] .home-tcard-foot { display: flex; align-items: center; justify-content: space-between; padding: 20px; }
            [data-home] .home-tcard-dots { display: inline-flex; gap: 6px; }
            [data-home] .home-tcard-dots i { height: 6px; width: 6px; border-radius: 9999px; background: var(--panel-border); }
            [data-home] .home-tcard-dots i.on { background: var(--panel-accent); }
            @media (prefers-reduced-motion: reduce) { [data-home] .home-tcard { transition: none; } }
            /* La caja de cada texto mide exactamente sus letras (sin el espacio de interlineado arriba y abajo): así los 20px
               entre título, líneas y pie son exactos. Si el navegador no recorta la caja, se compensa con márgenes negativos. */
            @supports (text-box: trim-both cap alphabetic) {
                [data-home] .home-tcard-body > span, [data-home] .home-sec-title, [data-home] .home-sec-text, [data-home] .home-sec-count, [data-home] .home-tcard-foot > span { text-box: trim-both cap alphabetic; }
                [data-home] .home-tcard-body > span:first-child { line-height: 15px; }
                /* Con la caja recortada a la altura de las letras (10.5px), 30.5px de línea dejan 20px exactos entre líneas */
                [data-home] .home-tcard-text { margin-top: 14px !important; margin-bottom: -6px !important; padding-block: 6px; line-height: 30.5px; }
                /* 6px de holgura arriba y abajo (compensados con margen) para que no se corten la g, j, y ni los acentos */
                [data-home] .home-tcard-body > span:first-child { padding-block: 6px; margin-block: -6px; }
                [data-home] .home-sec-text { margin: 20px 0 0 !important; line-height: 30.5px; }
            }
        </style>


        <script>
            // Tiempo sin abrir cualquier apartado de una sección (se guarda en este navegador al abrir un acceso)
            window.homeSectionOpened = window.homeSectionOpened || function (section) {
                const key = 'home-section-opened:' + section;
                const orderKey = 'home-section-order:' + section;
                return {
                    label: 'Todavía no has abierto ningún apartado de esta sección',
                    drag: null,
                    init() {
                        this.refresh();
                        this.restore();
                        this.timer = setInterval(() => this.refresh(), 60000);
                    },
                    destroy() { clearInterval(this.timer); },
                    cards() { return [...this.$refs.grid.querySelectorAll(':scope > .home-tcard')]; },
                    start(e) { this.drag = e.target.closest('.home-tcard'); if (this.drag) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', this.drag.dataset.url); setTimeout(() => this.drag?.classList.add('is-dragging'), 0); } },
                    over(e) {
                        if (!this.drag) return;
                        const target = e.target.closest('.home-tcard');
                        if (!target || target === this.drag) return;
                        const box = target.getBoundingClientRect();
                        const after = e.clientX > box.left + box.width / 2 && e.clientY > box.top;
                        this.$refs.grid.insertBefore(this.drag, after ? target.nextSibling : target);
                        this.dots();
                    },
                    end() { this.drag?.classList.remove('is-dragging'); this.drag = null; this.save(); },
                    save() { this.dots(); try { localStorage.setItem(orderKey, JSON.stringify(this.cards().map((c) => c.dataset.url))); } catch (e) {} },
                    dots() { this.cards().forEach((card, k) => card.querySelectorAll('.home-tcard-dots i').forEach((dot, j) => dot.classList.toggle('on', j === k))); },
                    restore() {
                        let order = [];
                        try { order = JSON.parse(localStorage.getItem(orderKey)) || []; } catch (e) {}
                        if (!order.length) return;
                        const rank = (c) => { const i = order.indexOf(c.dataset.url); return i < 0 ? 1e6 : i; };
                        this.cards().sort((a, b) => rank(a) - rank(b)).forEach((c) => this.$refs.grid.appendChild(c));
                        this.dots();
                    },
                    touch() { try { localStorage.setItem(key, String(Date.now())); } catch (e) {} },
                    refresh() {
                        let at = 0;
                        try { at = Number(localStorage.getItem(key)) || 0; } catch (e) {}
                        if (!at) { this.label = 'Todavía no has abierto ningún apartado de esta sección'; return; }
                        const min = Math.max(0, Math.floor((Date.now() - at) / 60000));
                        if (min < 1) { this.label = 'Sin abrir hace menos de 1 min'; return; }
                        if (min < 60) { this.label = 'Sin abrir hace ' + min + ' min'; return; }
                        const h = Math.floor(min / 60);
                        if (h < 24) { this.label = 'Sin abrir hace ' + h + (h === 1 ? ' hora' : ' horas'); return; }
                        const d = Math.floor(h / 24);
                        this.label = 'Sin abrir hace ' + d + (d === 1 ? ' día' : ' días');
                    },
                };
            };
        </script>
        <script>
            // Animaciones de las tarjetas con el menor consumo posible: el video se carga solo cuando la tarjeta se acerca a la
            // pantalla, se reproduce solo mientras se ve y se pausa al salir de la vista o al cambiar de pestaña.
            window.homeLazyVideo = window.homeLazyVideo || function () {
                return {
                    visible: false,
                    init() {
                        const video = this.$el;
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                            video.src = video.dataset.src; video.preload = 'metadata';
                            return;
                        }
                        const sync = () => {
                            if (this.visible && !document.hidden) {
                                if (!video.getAttribute('src')) { video.preload = 'auto'; video.src = video.dataset.src + '#t=0.1'; }
                                video.play().catch(() => {});
                            } else if (!video.paused) {
                                video.pause();
                            }
                        };
                        this.observer = new IntersectionObserver((entries) => { this.visible = entries[0].isIntersecting; sync(); }, { rootMargin: '100px' });
                        this.observer.observe(video);
                        this.onVisibility = sync;
                        document.addEventListener('visibilitychange', sync);
                    },
                    destroy() {
                        this.observer?.disconnect();
                        document.removeEventListener('visibilitychange', this.onVisibility);
                    },
                };
            };
        </script>
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
            <section style="--i: 0" class="home-rise mb-[30px] flex shrink-0 flex-wrap items-start justify-between gap-x-[20px] gap-y-[20px]">
                <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                    <div class="min-w-0">
                        <h1 class="home-sec-title panel-accent break-words font-normal">{{ $greeting }}, {{ trim($user->name.' '.($user->last_name ?? '')) }}</h1>
                        <p class="home-sec-text">Sesión iniciada correctamente · <a href="{{ route('section.guide', 'inicio') }}" title="Descargar la guía de Inicio en PDF" class="home-link hover:opacity-70">Descargar guía PDF para esta sección</a></p>
                    </div>
                </div>
                <div class="flex w-full min-w-0 flex-wrap items-start gap-[20px] lg:w-auto">
                    <div class="min-w-0 lg:text-right">
                        <p class="home-sec-title panel-accent">{{ $roleName ?: 'Portal interno' }}</p>
                        {{-- Cada dígito va en una celda de ancho fijo para que la hora no se mueva (el 1 es más angosto que el 0). --}}
                        <p class="home-sec-text">{{ $today }} · <span aria-label="Hora actual"
                            x-data="homeClock(@js(config('support.timezone', 'America/Mexico_City')))"
                            x-init="start()"
                            x-html="html">{{ $now->format('h:i:s a') }}</span></p>
                    </div>
                </div>
            </section>

            <div class="grid items-start gap-y-[20px] xl:gap-x-[50px] xl:grid-cols-[minmax(0,1fr)_400px]">
                {{-- Apartados: tarjetas agrupadas por sección (solo el título de la sección, sin cajas) --}}
                <div class="home-rise flex min-w-0 flex-col gap-[50px]" style="--i: 1">
                    @foreach ($sections as $title => $cards)
                        @php([$metaIcon, $metaNote] = $sectionMeta[$title] ?? ['dashboard', ''])
                        <section class="min-w-0" aria-label="{{ $title }}" x-data="homeSectionOpened(@js($title))" @click="if ($event.target.closest('a.home-card')) touch()" @auxclick="if ($event.target.closest('a.home-card')) touch()">
                            <div class="mb-[20px] flex flex-wrap items-start justify-between gap-x-[20px] gap-y-[15px]">
                                <div class="min-w-0">
                                    <h2 class="home-sec-title panel-accent">{{ $title }}</h2>
                                    <p class="home-sec-text">{{ rtrim($metaNote, '.') }}</p>
                                    <p class="home-sec-text">{{ count($cards) }} {{ count($cards) === 1 ? 'apartado' : 'apartados' }} · <span x-text="label">Todavía no has abierto ningún apartado de esta sección</span></p>
                                </div>
                            </div>
                            <div class="home-tgrid" x-ref="grid" @dragover.prevent="over($event)" @drop.prevent="save()">
                                @foreach ($cards as [$cardTitle, $description, $url, $icon])
                                    <a href="{{ $url }}" style="--d: {{ ($loop->parent->index + 1) * 90 + 200 + $loop->index * 60 }}" class="home-card home-tcard" data-url="{{ $url }}" draggable="true" @dragstart="start($event)" @dragend="end()">
                                        <span class="home-tcard-visual">
                                            @if (isset($illustrations[$cardTitle]))
                                                <video data-src="{{ asset('img/home/'.$illustrations[$cardTitle].'.mp4') }}" loop muted playsinline preload="none" disablepictureinpicture disableremoteplayback tabindex="-1" aria-hidden="true" x-data="homeLazyVideo()"></video>
                                            @else
                                                <svg class="home-tcard-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.1" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] ?? $icons['dashboard'] }}" /></svg>
                                            @endif
                                        </span>
                                        <span class="home-tcard-body">
                                            <span class="panel-accent block leading-none">{{ $cardTitle }}</span>
                                            <span class="home-tcard-text block">{{ $description }}</span>
                                        </span>
                                        <span class="home-tcard-foot">
                                            <span class="home-tcard-dots" aria-hidden="true">
                                                @foreach ($cards as $dot)<i class="{{ $loop->index === $loop->parent->index ? 'on' : '' }}"></i>@endforeach
                                            </span>
                                            <span class="home-link leading-none">Abrir ▸</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                {{-- En línea ahora: ocupa a lo sumo el alto de las cajas de accesos (columna izquierda) y crece conforme hay más personas --}}
                <div class="relative min-w-0 xl:self-stretch">
                    <livewire:home.online-users lazy />
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
