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
    <div data-panel data-home class="relative isolate min-h-[calc((var(--app-dvh)-123px)*var(--app-zoom,1))] w-full min-w-0 text-[15px]">
        {{-- Los videos de las tarjetas se piden desde que el navegador lee la página (antes de que arranquen los scripts) --}}
        @foreach (array_unique(array_values($illustrations)) as $videoFile)
            <link rel="preload" as="fetch" crossorigin="anonymous" href="{{ asset('img/home/'.$videoFile.'.mp4') }}">
        @endforeach
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
            /* Inicio siempre a tamaño real: la app reduce las vistas (al 90 % en escritorio y hasta un 85 % más en ventanas
               pequeñas); aquí se compensa ese zoom para que 50px se vean como 50px en cualquier pantalla. */
            [data-home] { zoom: calc(1 / var(--app-zoom, 1)); }
            /* El chat «Mensajes» va al mismo tamaño que Inicio (sin el zoom de la app) mientras se está en Inicio */
            body:has([data-home]) [data-direct-chat] { right: 50px !important; }
            body:has([data-home]) [data-direct-chat] .app-zoom-overlay { zoom: 1; }
            body:has([data-home]) [data-direct-chat] section[aria-label="Mensajes"] { max-height: calc(100dvh - 123px * var(--app-zoom, 1)); }
            @media (max-width: 639px) { body:has([data-home]) [data-direct-chat] { right: 10px !important; } }
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
            /* Entrada al abrir o recargar Inicio: cada bloque sube 16px mientras aparece, en cascada y rápido (menos de 1 s en
               total). Se usa la propiedad translate (no transform) para no chocar con el arrastre de tarjetas. */
            @keyframes home-rise { from { opacity: 0; translate: 0 16px; } to { opacity: 1; translate: 0 0; } }
            @keyframes home-fade { from { opacity: 0; } to { opacity: 1; } }
            /* La entrada espera a que la página termine de cargar (scripts, letra Poppins): si arranca mientras el navegador
               ejecuta los scripts, se ve a tirones. Mientras tanto las animaciones quedan detenidas en su primer cuadro (ocultas). */
            [data-home]:not(.home-go) :is(.home-rise, .home-fade, .home-card, .home-online-row) { animation-play-state: paused; }
            @keyframes home-drop { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: none; } }
            [data-home] .home-rise { animation: home-rise 450ms cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--i, 0) * 80ms); }
            [data-home] .home-fade { animation: home-fade 300ms ease-out both; animation-delay: calc(var(--i, 0) * 80ms); }
            [data-home] .home-card { animation: home-rise 450ms cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 1ms); position: relative; }
            [data-home] .home-card .home-card-title { display: inline-block; transition: transform 200ms ease; }
            
            [data-home] .home-card::after { content: ''; position: absolute; left: 0; bottom: -1px; height: 2px; width: 100%; background: var(--home-green); transform: scaleX(0); transform-origin: left; transition: transform 250ms ease; }
            
            [data-home] .home-online-row { animation: home-drop 450ms cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--i, 0) * 60ms); }
            [data-home] .home-online-row .home-row { transition: background-color 150ms ease, padding-left 200ms ease; }
            [data-home] .home-online-row .home-row:hover { padding-left: 25px; }
            [data-home] .home-online-dot { animation: home-pulse 2.4s ease-in-out infinite; }
            @keyframes home-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .45); } 50% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); } }
            @media (prefers-reduced-motion: reduce) {
                [data-home] .home-rise, [data-home] .home-fade, [data-home] .home-card, [data-home] .home-online-row, [data-home] .home-online-dot { animation: none; }
                [data-home] .home-card::after, [data-home] .home-card .home-card-title, [data-home] .home-online-row .home-row { transition: none; }
            }
            /* Tarjeta vertical con animación, como una pantalla de presentación: ilustración arriba, título y texto, y pie con puntos y acción */
            [data-home] .home-illus { grid-column: auto / -1; }
            [data-home] .home-tgrid { display: flex; flex-wrap: wrap; gap: 20px; }
            [data-home] .home-tgrid > .home-tcard { flex: 0 0 280px; width: 280px; }
            [data-home] .home-tcard { cursor: grab; user-select: none; -webkit-user-select: none; }
            [data-home] .home-tcard.is-dragging { z-index: 30; cursor: grabbing; }
            [data-home] .home-tcard { display: flex; min-height: 400px; flex-direction: column; border-radius: 20px; background: var(--panel-surface); border: 1px solid var(--panel-border); overflow: hidden; }
            [data-home] .home-tcard::after { display: none; }
            [data-home] .home-tcard-visual { display: flex; flex: 1 1 auto; min-height: 0; padding: 20px 20px 0; align-items: center; justify-content: center; }
            [data-home] .home-tcard-visual video { height: 150px; width: 150px; object-fit: contain; mix-blend-mode: multiply; pointer-events: none; }
            [data-home] .home-tcard-icon { height: 100px; width: 100px; color: var(--panel-text); }
                        html.module-dark-theme [data-home] .home-tcard-visual video { filter: invert(1); mix-blend-mode: screen; }
            [data-home] .home-tcard-body { display: block; flex: none; padding: 20px 20px 0; }
            [data-home] .home-sec-rule { height: 1px; flex: 1 1 auto; background: var(--panel-border); }
            [data-home] .home-sec-count { line-height: 15px; }
            [data-home] .home-tcard-foot > span { line-height: 1; }
            [data-home] .home-tcard-foot { display: flex; align-items: center; justify-content: space-between; padding: 20px; }
            [data-home] .home-tcard-dots { display: inline-flex; gap: 6px; }
            [data-home] .home-tcard-dots i { height: 6px; width: 6px; border-radius: 9999px; background: var(--panel-border); }
            [data-home] .home-tcard-dots i.on { background: var(--panel-accent); }
            @media (prefers-reduced-motion: reduce) { [data-home] .home-tcard { transition: none; } }
            /* Todas las distancias son de 20px y solo con margin: la caja de cada texto se recorta a sus letras (sin interlineado
               arriba ni abajo), así el margin es la distancia real que se ve. Nada de rellenos ni márgenes que se compensen. */
            [data-home] :is(h1, h2, p, .home-tcard-body > span, .home-online-name, .home-online-meta, .home-tcard-foot > span) { text-box: trim-both cap alphabetic; }
            [data-home] .home-sec-title, [data-home] .home-sec-text { margin: 0; padding: 0; }
            [data-home] .home-sec-text { margin-top: 20px; }

            /* ============ Diseño propio de Inicio ============
               Jerarquía clara: saludo grande, secciones con título fuerte y descripción gris, tarjetas compactas con la
               ilustración sobre un fondo suave, y chips para los datos (rol, hora, número de apartados). */
            [data-home] { --home-muted: color-mix(in srgb, var(--panel-text) 58%, transparent); --home-soft: color-mix(in srgb, var(--panel-accent) 6%, var(--panel-surface)); --home-ring: color-mix(in srgb, var(--panel-accent) 35%, var(--panel-border)); }
            html.module-dark-theme [data-home] { --home-soft: #111116; }
            [data-home] .home-hero { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; }
            [data-home] .home-hero-title { margin-top: 0; font-size: 30px; font-weight: 600; line-height: calc(.7em + 20px); letter-spacing: -.02em; color: var(--panel-text); }
            [data-home] p.home-hero-sub { margin-top: 20px; font-size: 15px; line-height: calc(.7em + 20px); color: var(--home-muted); }
            [data-home] a.home-inline-link { color: var(--home-green); font-weight: 500; text-decoration: none; transition: opacity 150ms ease; }
            [data-home] a.home-inline-link:hover { opacity: .7; }
            [data-home] .home-hero-meta { display: flex; flex-wrap: wrap; gap: 20px; }
            [data-home] span.home-chip { display: inline-flex; align-items: center; gap: 8px; padding: 15px 20px; border: 1px solid var(--panel-border); border-radius: 18px; background: var(--panel-surface); font-size: 15px; line-height: 1.3; color: var(--panel-text); white-space: nowrap; }
            [data-home] span.home-chip-strong { display: inline-block; background: var(--panel-text); border-color: var(--panel-text); color: var(--panel-surface); }
            [data-home] span.home-chip-strong span { font-size: inherit; }
            [data-home] .home-live-dot { height: 7px; width: 7px; border-radius: 9999px; background: #10b981; animation: home-pulse 2.4s ease-in-out infinite; }
            [data-home] .home-sec-head2 { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
            [data-home] h2.home-h2 { font-size: 19px; font-weight: 600; line-height: calc(.7em + 20px); letter-spacing: -.01em; color: var(--panel-text); }
            [data-home] :is(p, span).home-muted { font-size: 15px; line-height: calc(.7em + 20px); color: var(--home-muted); }
            [data-home] p.home-muted { margin-top: 20px; }
            [data-home] .home-sec-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 20px; }

            /* Tarjetas: rejilla que llena el ancho, ilustración en un panel suave y texto compacto */
            [data-home] .home-tgrid { position: relative; display: grid !important; grid-auto-rows: 1fr; align-items: stretch; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px !important; }
            [data-home] .home-tgrid > .home-tcard { width: auto !important; flex: none !important; }
            [data-home] .home-tcard { min-height: 0 !important; padding: 20px; border-radius: 18px !important; transition: border-color 180ms ease, transform 180ms ease, box-shadow 180ms ease; }
            [data-home] .home-tcard:hover { border-color: var(--home-ring); box-shadow: 0 0 20px rgba(15, 23, 42, .12); }
            html.module-dark-theme [data-home] .home-tcard:hover { box-shadow: 0 0 20px rgba(0, 0, 0, .6); }
            /* Arrastre: la tarjeta se levanta (escala y sombra) con transición; su posición la mueve el código sin transición para que siga al cursor */
            [data-home] .home-tcard.is-dragging { transition: box-shadow 220ms ease, scale 220ms cubic-bezier(.4, 0, .2, 1); }
            /* Al mover una tarjeta en la página el navegador reinicia su animación de entrada (desde opacidad 0); se apaga al arrastrar */
            [data-home] .home-tcard.home-still { animation: none; }
            /* El video aparece suave cuando ya tiene su primer cuadro (no de golpe sobre el recuadro vacío) */
            [data-home] .home-tcard-visual video { opacity: 0; transition: opacity 350ms ease-out; }
            [data-home] .home-tcard-visual video.is-ready { opacity: 1; }
            [data-home] .home-tcard.is-lifted { scale: 1.03; box-shadow: 0 0 30px rgba(15, 23, 42, .18); }
            html.module-dark-theme [data-home] .home-tcard.is-lifted { box-shadow: 0 0 30px rgba(0, 0, 0, .7); }
            [data-home] .home-tcard-visual { flex: none !important; height: 150px; padding: 0 !important; border-radius: 12px; background: #ffffff; }
            html.module-dark-theme [data-home] .home-tcard-visual { background: var(--panel-surface); }
            [data-home] .home-tcard-visual video { height: 120px !important; width: 120px !important; }
            [data-home] .home-tcard-icon { height: 72px !important; width: 72px !important; color: var(--panel-accent) !important; }
            [data-home] .home-tcard-body { flex: 1 1 auto !important; margin-top: 20px; padding: 0 !important; }
            [data-home] .home-tcard-body > span { padding: 0 !important; margin: 0 !important; overflow: visible !important; white-space: normal !important; }
            [data-home] .home-tcard-body > span:first-child { font-size: 16px !important; font-weight: 600; line-height: calc(.7em + 20px) !important; color: var(--panel-text) !important; }
            [data-home] .home-tcard-body > .home-tcard-text { display: block !important; margin-top: 20px !important; font-size: 15px !important; line-height: calc(.7em + 20px) !important; color: var(--home-muted); }
            [data-home] .home-tcard-foot { margin-top: 20px; padding: 0 !important; }
                        [data-home] .home-tcard-dots { display: none !important; }
            [data-home] .home-tcard-foot .home-link { margin-left: auto; font-size: 15px; font-weight: 500; transition: transform 180ms ease; }

            /* En línea: lista en una sola tarjeta, con separadores finos */
            [data-home] .home-online-list { border: 1px solid var(--panel-border); border-radius: 18px; background: var(--panel-surface); }
            /* Sin líneas entre las filas */
            [data-home] .home-online-row .home-row { padding: 20px !important; }
            [data-home] .home-online-row .home-row:hover { padding-left: 20px !important; background: var(--home-soft); }
            [data-home] span.home-online-name { display: block; font-size: 15px; font-weight: 600; line-height: calc(.7em + 20px); color: var(--panel-text); }
            [data-home] span.home-online-meta { margin-top: 20px; font-size: 15px; line-height: calc(.7em + 20px); color: var(--home-muted); }
            @media (prefers-reduced-motion: reduce) { [data-home] .home-tcard, [data-home] .home-tcard-foot .home-link { transition: none; } [data-home] .home-live-dot { animation: none; } }
        </style>


        <script>
            // Arranque de la entrada: tras cargar la página y la letra, dos cuadros después (ya sin trabajo pendiente). Por si
            // algo falla, a los 4 s arranca de todos modos. window.homeEntered avisa a los videos cuando terminó la entrada.
            // Al abrir o recargar Inicio la página empieza siempre hasta arriba (el navegador, por defecto, regresa al punto donde
            // se había quedado). Solo aplica en Inicio: al salir se devuelve el comportamiento normal del navegador.
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
                // La preferencia se guarda por página del historial; al salir con la navegación interna (sin recargar) se
                // devuelve a la normal para que las demás páginas no la hereden.
                document.addEventListener('livewire:navigate', () => { history.scrollRestoration = 'auto'; }, { once: true });
            }
            window.scrollTo(0, 0);
            // Se crea de nuevo cada vez que se abre Inicio (también al llegar con la navegación interna sin recargar).
            window.homeEntered = new Promise((done) => {
                const root = () => document.querySelector('[data-home]');
                let started = false;
                const go = () => {
                    if (started) return; started = true;
                    requestAnimationFrame(() => requestAnimationFrame(() => {
                        root()?.classList.add('home-go');
                        performance.mark?.('home-entrada');
                        setTimeout(done, 450);
                    }));
                };
                const afterLoad = () => {
                    const fonts = document.fonts?.ready || Promise.resolve();
                    Promise.race([fonts, new Promise((r) => setTimeout(r, 800))]).then(go);
                };
                if (document.readyState === 'complete') afterLoad(); else window.addEventListener('load', afterLoad, { once: true });
                setTimeout(go, 4000);
            });
        </script>
        <script>
            // Tiempo sin abrir cualquier apartado de una sección (se guarda en este navegador al abrir un acceso)
            window.homeSectionOpened = window.homeSectionOpened || function (section) {
                const key = 'home-section-opened:' + section;
                const orderKey = 'home-section-order:' + section;
                return {
                    label: 'Sin abrir todavía',
                    drag: null,
                    press: null,
                    moved: false,
                    init() {
                        this.refresh();
                        this.restore();
                        this.timer = setInterval(() => this.refresh(), 60000);
                    },
                    destroy() { clearInterval(this.timer); },
                    cards() { return [...this.$refs.grid.querySelectorAll(':scope > .home-tcard')]; },
                    // Arrastre con el puntero: la tarjeta sigue al cursor (sin la imagen fantasma del navegador) y las demás se deslizan a su lugar.
                    // El lugar de cada tarjeta se calcula con su posición real en la rejilla (offsetLeft/Top, que no cambia con las
                    // animaciones), así una tarjeta que se está deslizando nunca se confunde con la de debajo del cursor ni rebota.
                    ease: 'cubic-bezier(.4, 0, .2, 1)',
                    calm() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; },
                    unit() { const g = this.$refs.grid; return g.offsetWidth ? g.getBoundingClientRect().width / g.offsetWidth : 1; },
                    down(e) {
                        if (e.button !== 0 || e.pointerType === 'touch' || this.press) return;
                        this.press = { card: e.currentTarget, x: e.clientX, y: e.clientY, active: false, id: e.pointerId, dx: 0, dy: 0 };
                    },
                    move(e) {
                        const p = this.press;
                        if (!p || p.card !== e.currentTarget) return;
                        if (!p.active) {
                            if (Math.hypot(e.clientX - p.x, e.clientY - p.y) < 6) return;
                            p.active = true;
                            p.card.getAnimations().filter((a) => a.id === 'home-settle').forEach((a) => a.cancel());
                            const g = this.$refs.grid.getBoundingClientRect(), u = this.unit();
                            p.grabX = p.x - (g.left + p.card.offsetLeft * u);
                            p.grabY = p.y - (g.top + p.card.offsetTop * u);
                            try { p.card.setPointerCapture?.(p.id); } catch (err) {}
                            this.cards().forEach((c) => c.classList.add('home-still'));
                            p.card.classList.add('is-dragging', 'is-lifted');
                            this.drag = p.card;
                            this.moved = true;
                        }
                        this.reorder(e);
                        this.place(e);
                    },
                    reorder(e) {
                        const grid = this.$refs.grid, g = grid.getBoundingClientRect(), u = this.unit();
                        const x = (e.clientX - g.left) / u, y = (e.clientY - g.top) / u;
                        const cards = this.cards();
                        const target = cards.find((c) => x >= c.offsetLeft && x < c.offsetLeft + c.offsetWidth && y >= c.offsetTop && y < c.offsetTop + c.offsetHeight);
                        if (!target || target === this.drag) return;
                        const from = cards.indexOf(this.drag), to = cards.indexOf(target);
                        // Posición en pantalla de cada tarjeta justo ahora (incluye el deslizamiento que lleve a medias)
                        const first = new Map(cards.map((c) => [c, c.getBoundingClientRect()]));
                        grid.insertBefore(this.drag, to > from ? target.nextSibling : target);
                        this.dots();
                        if (this.calm()) return;
                        cards.forEach((c) => {
                            if (c === this.drag) return;
                            c.getAnimations().filter((a) => a.id === 'home-flip').forEach((a) => a.cancel());
                            const was = first.get(c), now = c.getBoundingClientRect();
                            const dx = (was.left - now.left) / u, dy = (was.top - now.top) / u;
                            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) return;
                            c.animate([{ transform: 'translate(' + dx + 'px, ' + dy + 'px)' }, { transform: 'translate(0, 0)' }], { duration: 380, easing: this.ease }).id = 'home-flip';
                        });
                    },
                    place(e) {
                        const p = this.press, c = p.card, g = this.$refs.grid.getBoundingClientRect(), u = this.unit();
                        p.dx = (e.clientX - p.grabX - (g.left + c.offsetLeft * u)) / u;
                        p.dy = (e.clientY - p.grabY - (g.top + c.offsetTop * u)) / u;
                        c.style.transform = 'translate(' + p.dx + 'px, ' + p.dy + 'px)';
                    },
                    up(e) {
                        const p = this.press;
                        if (!p || p.card !== e.currentTarget) return;
                        this.press = null;
                        if (!p.active) return;
                        const c = p.card;
                        try { c.releasePointerCapture?.(p.id); } catch (err) {}
                        c.classList.remove('is-lifted');
                        c.style.transform = '';
                        // Se queda encima de las demás hasta terminar de asentarse en su lugar
                        if (this.calm() || (Math.abs(p.dx) < 0.5 && Math.abs(p.dy) < 0.5)) {
                            c.classList.remove('is-dragging');
                        } else {
                            const a = c.animate([{ transform: 'translate(' + p.dx + 'px, ' + p.dy + 'px)' }, { transform: 'translate(0, 0)' }], { duration: 300, easing: this.ease });
                            a.id = 'home-settle';
                            a.onfinish = () => c.classList.remove('is-dragging');
                        }
                        this.drag = null;
                        this.save();
                        setTimeout(() => { this.moved = false; }, 80);
                    },
                    blockClick(e) { if (this.moved) { e.preventDefault(); e.stopPropagation(); } },
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
                        if (!at) { this.label = 'Sin abrir todavía'; return; }
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
            // Animaciones de las tarjetas. Hay 7 videos distintos (poco más de 1 MB) repetidos en 18 tarjetas: cada archivo se
            // descarga una sola vez y todas las tarjetas que lo usan lo leen de memoria. Todos siguen un mismo reloj, así que se
            // mueven a la vez. Solo se reproducen los que están en pantalla o cerca (para no gastar procesador); como el video
            // ya está en memoria, al volver a la vista arranca al instante en el punto que le toca. Con la pestaña oculta se
            // pausan todos, y si el navegador pausa uno por su cuenta (por ejemplo al mover la tarjeta) se reanuda solo.
            window.homeVideoFiles = window.homeVideoFiles || {};
            window.homeVideoFile = window.homeVideoFile || function (url) {
                if (!window.homeVideoFiles[url]) {
                    window.homeVideoFiles[url] = fetch(url, { credentials: 'same-origin' })
                        .then((r) => { if (!r.ok) throw new Error(r.status); return r.blob(); })
                        .then((blob) => URL.createObjectURL(blob))
                        .catch(() => url);
                }
                return window.homeVideoFiles[url];
            };
            window.homeVideoClock = window.homeVideoClock || performance.now();
            // Corrección de sincronía cada medio segundo. Saltar de posición tarda (el video se detiene a decodificar) y lo vuelve a
            // dejar atrás, así que solo se salta si va más de 1 s fuera de tiempo; si la diferencia es pequeña, el video se acelera
            // o se frena un poco (hasta un 25 %) hasta alcanzar a los demás, sin tirones.
            if (!window.homeVideoSyncTimer) {
                window.homeVideoSyncTimer = setInterval(() => {
                    if (document.hidden) return;
                    const t = (performance.now() - window.homeVideoClock) / 1000;
                    document.querySelectorAll('[data-home] .home-tcard-visual video').forEach((v) => {
                        if (v.paused || !v.duration || v.seeking || v.readyState < 3) return;
                        const target = t % v.duration;
                        let diff = target - v.currentTime;
                        if (diff > v.duration / 2) diff -= v.duration;
                        if (diff < -v.duration / 2) diff += v.duration;
                        if (Math.abs(diff) > 1) { v.playbackRate = 1; v.currentTime = (target + 0.3) % v.duration; return; }
                        v.playbackRate = Math.abs(diff) < 0.04 ? 1 : 1 + Math.max(-0.25, Math.min(0.25, diff * 0.8));
                    });
                }, 500);
            }
            window.homeLazyVideo = window.homeLazyVideo || function () {
                return {
                    visible: false,
                    entered: false,
                    init() {
                        const video = this.$el;
                        const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        const wanted = () => this.visible && !document.hidden && !calm;
                        const sync = () => {
                            if (!video.getAttribute('src')) return;
                            if (!wanted() || !this.entered) { if (!video.paused) video.pause(); return; }
                            if (!video.paused) return;
                            const play = () => {
                                if (!wanted()) return;
                                if (video.duration) video.currentTime = ((performance.now() - window.homeVideoClock) / 1000) % video.duration;
                                video.play().catch(() => {});
                            };
                            if (video.readyState >= 1) play(); else video.addEventListener('loadedmetadata', play, { once: true });
                        };
                        this.sync = sync;
                        window.homeEntered.then(() => { this.entered = true; sync(); });
                        video.addEventListener('pause', () => setTimeout(sync, 0));
                        // Se descarga desde el primer momento (en paralelo a la carga de la página); se muestra y arranca al terminar la entrada
                        const ready = () => window.homeEntered.then(() => { video.classList.add('is-ready'); performance.mark?.('home-video'); });
                        if (video.readyState >= 2) ready(); else video.addEventListener('loadeddata', ready, { once: true });
                        window.homeVideoFile(video.dataset.src).then((src) => { video.src = src; window.homeEntered.then(sync); });
                        this.observer = new IntersectionObserver((entries) => { this.visible = entries[0].isIntersecting; sync(); }, { rootMargin: '300px 0px' });
                        this.observer.observe(video);
                        document.addEventListener('visibilitychange', sync);
                    },
                    destroy() {
                        this.observer?.disconnect();
                        document.removeEventListener('visibilitychange', this.sync);
                    },
                };
            };
            // Guía PDF: se empieza a preparar en cuanto el puntero se acerca al enlace (o al tocarlo), y al hacer clic se
            // descarga lo ya preparado, sin esperar a que el servidor la genere. Si algo falla, el enlace funciona como siempre.
            window.homeGuideLink = window.homeGuideLink || function () {
                return {
                    job: null,
                    at: 0,
                    prepare() {
                        if (this.job && Date.now() - this.at < 60000) return this.job;
                        this.at = Date.now();
                        this.job = fetch(this.$el.href, { credentials: 'same-origin' }).then(async (r) => {
                            if (!r.ok) throw new Error(r.status);
                            const name = /filename="?([^";]+)"?/i.exec(r.headers.get('Content-Disposition') || '')?.[1] || 'guia.pdf';
                            return { blob: await r.blob(), name };
                        });
                        this.job.catch(() => { this.job = null; });
                        return this.job;
                    },
                    async open(e) {
                        e.preventDefault();
                        try {
                            const { blob, name } = await this.prepare();
                            const url = URL.createObjectURL(blob);
                            const a = Object.assign(document.createElement('a'), { href: url, download: name });
                            document.body.appendChild(a); a.click(); a.remove();
                            setTimeout(() => URL.revokeObjectURL(url), 10000);
                            this.job = null;
                        } catch (err) {
                            window.location.href = this.$el.href;
                        }
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

        <main class="flex w-full flex-col gap-[50px] p-[20px] sm:p-[50px]">
            <!-- Cabecera: título y subtítulo a la izquierda; rol y fecha a la derecha -->
            <section style="--i: 0" class="home-rise home-hero">
                <div class="min-w-0">
                    {{-- Primero el saludo; debajo la fecha y, en la misma línea, la guía en PDF --}}
                    <h1 class="home-hero-title">{{ $greeting }}, {{ trim($user->name.' '.($user->last_name ?? '')) }}</h1>
                    <p class="home-hero-sub">{{ $today }} · <a href="{{ route('section.guide', 'inicio') }}" title="Descargar la guía de Inicio en PDF" class="home-inline-link" x-data="homeGuideLink()" @pointerenter="prepare()" @focus="prepare()" @touchstart.passive="prepare()" @click="open($event)">Descargar guía PDF</a></p>
                </div>
                <div class="home-hero-meta">
                    <span class="home-chip">{{ $roleName ?: 'Portal interno' }}</span>
                    {{-- Cada dígito va en una celda de ancho fijo para que la hora no se mueva (el 1 es más angosto que el 0). --}}
                    <span class="home-chip home-chip-strong" aria-label="Hora actual"
                        x-data="homeClock(@js(config('support.timezone', 'America/Mexico_City')))"
                        x-init="start()"
                        x-html="html">{{ $now->format('h:i:s a') }}</span>
                </div>
            </section>

            <div class="grid items-start gap-y-[20px] xl:gap-x-[50px] xl:grid-cols-[minmax(0,1fr)_400px]">
                {{-- Apartados: tarjetas agrupadas por sección (solo el título de la sección, sin cajas) --}}
                <div class="home-fade flex min-w-0 flex-col gap-[50px]" style="--i: 1">
                    @foreach ($sections as $title => $cards)
                        @php([$metaIcon, $metaNote] = $sectionMeta[$title] ?? ['dashboard', ''])
                        <section class="min-w-0" aria-label="{{ $title }}" x-data="homeSectionOpened(@js($title))" @click="if ($event.target.closest('a.home-card')) touch()" @auxclick="if ($event.target.closest('a.home-card')) touch()">
                            <div class="home-sec-head2">
                                <div class="min-w-0">
                                    <h2 class="home-h2">{{ $title }}</h2>
                                    <p class="home-muted">{{ rtrim($metaNote, '.') }}.</p>
                                </div>
                                <div class="home-sec-meta">
                                    <span class="home-chip">{{ count($cards) }} {{ count($cards) === 1 ? 'apartado' : 'apartados' }}</span>
                                    <span class="home-muted" x-text="label">Sin abrir todavía</span>
                                </div>
                            </div>
                            <div class="home-tgrid" x-ref="grid">
                                @foreach ($cards as [$cardTitle, $description, $url, $icon])
                                    <a href="{{ $url }}" style="--d: {{ 120 + $loop->parent->index * 80 + $loop->index * 50 }}" class="home-card home-tcard" data-url="{{ $url }}" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="up($event)" @lostpointercapture="up($event)" @dragstart.prevent @click.capture="blockClick($event)">
                                        <span class="home-tcard-visual">
                                            @if (isset($illustrations[$cardTitle]))
                                                <video data-src="{{ asset('img/home/'.$illustrations[$cardTitle].'.mp4') }}" loop muted playsinline preload="auto" disablepictureinpicture disableremoteplayback tabindex="-1" aria-hidden="true" x-data="homeLazyVideo()"></video>
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
                                            <span class="home-link leading-none">Abrir →</span>
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
