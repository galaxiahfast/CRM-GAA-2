<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34pt 38pt 42pt; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #202124; font-family: DejaVu Sans, sans-serif; font-size: 8.6pt; line-height: 1.42; }
        h1, h2, p { margin: 0; }
        h1 { color: #173a68; font-size: 19pt; line-height: 1.15; }
        h2 { margin: 17pt 0 7pt; color: #173a68; font-size: 11pt; }
        .eyebrow { margin-bottom: 5pt; color: #667085; font-size: 7.5pt; font-weight: bold; letter-spacing: .8pt; text-transform: uppercase; }
        .subtitle { margin-top: 7pt; color: #667085; }
        .rule { margin: 13pt 0; border-top: 1.4pt solid #173a68; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 0; }
        .summary td { width: 50%; padding: 9pt 10pt; vertical-align: top; border: .6pt solid #d7dce3; }
        .summary .label { display: block; margin-bottom: 2pt; color: #667085; font-size: 7.2pt; text-transform: uppercase; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data th { padding: 6pt 7pt; color: #fff; background: #173a68; font-size: 7.5pt; text-align: left; }
        .data td { padding: 7pt; vertical-align: top; border-bottom: .6pt solid #d7dce3; word-wrap: break-word; }
        .data tr:nth-child(even) td { background: #f7f8fa; }
        .tag { display: inline-block; padding: 2pt 5pt; color: #176b52; background: #e9f7f1; font-size: 7.2pt; font-weight: bold; }
        .note { margin-top: 8pt; padding: 8pt 10pt; border-left: 3pt solid #d89a20; background: #fff9ec; }
        .ok { margin-top: 8pt; padding: 8pt 10pt; border-left: 3pt solid #21845f; background: #eef9f5; }
        ul { margin: 5pt 0 0; padding-left: 15pt; } li { margin-bottom: 3pt; }
        code, pre { font-family: DejaVu Sans Mono, monospace; }
        code { color: #173a68; font-size: 8pt; }
        pre { margin: 5pt 0 0; padding: 8pt; white-space: pre-wrap; border: .6pt solid #d7dce3; background: #f6f7f9; font-size: 6.9pt; line-height: 1.35; }
        .page-break { page-break-before: always; }
        .footer { position: fixed; right: 0; bottom: -24pt; left: 0; color: #8a9099; font-size: 7pt; text-align: center; }
    </style>
</head>
<body>
    <div class="footer">Gestión de Respaldos · Documento de continuidad técnica · No contiene contraseñas</div>

    <p class="eyebrow">DataMID · Continuidad técnica</p>
    <h1>Guía de configuración<br>Gestión de Respaldos</h1>
    <p class="subtitle">Configuración necesaria para que otro programador pueda diagnosticar o restablecer la integración.</p>
    <div class="rule"></div>

    <table class="summary">
        <tr>
            <td><span class="label">Aplicación web</span><strong>{{ $applicationHost }}</strong><br>Servidor donde se ejecutan Laravel, PHP y el proceso de copia.</td>
            <td><span class="label">Servidor CONTPAQi</span><strong>{{ $sourceHost }}</strong><br>Archivos de origen, memorias externas e instancias SQL.</td>
        </tr>
        <tr>
            <td><span class="label">Origen</span><code>{{ $sourceRoot }}</code><br><span class="tag">SOLO LECTURA</span></td>
            <td><span class="label">Cuenta SQL</span><code>{{ $sqlUsername }}</code><br>Contraseña únicamente en el archivo <code>.env</code>.</td>
        </tr>
    </table>

    <h2>1. Rutas y permisos de archivos</h2>
    <table class="data">
        <thead><tr><th style="width:24%">Uso</th><th style="width:31%">Ruta UNC estable</th><th style="width:17%">Unidad en 210</th><th>Permiso requerido desde 249</th></tr></thead>
        <tbody>
            <tr><td>Origen CONTPAQi</td><td><code>{{ $sourceRoot }}</code></td><td>Carpeta local del servidor</td><td>Lectura y listado. Nunca escritura ni eliminación.</td></tr>
            @foreach($profiles as $profile)
                <tr><td>{{ $profile['label'] }}</td><td><code>{{ $profile['destination_root'] }}</code></td><td>{{ str_ends_with(strtolower($profile['destination_root']), '\\e') ? 'E: · USB Contabilidad' : (str_ends_with(strtolower($profile['destination_root']), '\\f') ? 'F: · USB Nóminas' : 'Según configuración') }}</td><td>Lectura, creación, modificación y eliminación únicamente dentro del destino.</td></tr>
            @endforeach
        </tbody>
    </table>
    <div class="note"><strong>Importante:</strong> la aplicación usa rutas UNC (<code>\\SRVCONTPAQ\...</code>), no letras mapeadas en el servidor 249. Si Windows cambia la letra de una USB en el servidor 210, se debe corregir el recurso compartido para que las rutas UNC <code>\e</code> y <code>\f</code> continúen apuntando a la memoria correcta.</div>

    <h2>2. Acceso desde el servidor de la aplicación</h2>
    <ul>
        <li>El servidor <strong>{{ $applicationHost }}</strong> debe resolver <code>SRVCONTPAQ</code> y alcanzar a <strong>{{ $sourceHost }}</strong> por SMB y SQL Server.</li>
        <li>La identidad que ejecuta PHP/Laravel necesita lectura en <code>{{ $sourceRoot }}</code> y escritura en los destinos de las memorias.</li>
        <li>El firewall debe permitir SMB (TCP 445) y los puertos configurados para las instancias <code>CONTABILIDAD</code> y <code>NOMINAS</code>.</li>
        <li>No se deben conceder permisos de eliminación sobre la carpeta de origen.</li>
    </ul>

    <div class="page-break"></div>
    <p class="eyebrow">Configuración SQL y aplicación</p>
    <h1>Acceso de solo lectura</h1>
    <p class="subtitle">Las consultas sirven únicamente para identificar las empresas registradas en cada sistema.</p>

    <h2>3. Instancias y objetos consultados</h2>
    <table class="data">
        <thead><tr><th>Instancia</th><th>Base</th><th>Objeto</th><th>Uso</th></tr></thead>
        <tbody>
            <tr><td><code>SRVCONTPAQ\NOMINAS</code></td><td><code>nomGenerales</code></td><td><code>dbo.nom10000</code></td><td>Lee <code>RutaEmpresa</code> con <code>DatosVigente = 1</code>.</td></tr>
            <tr><td><code>SRVCONTPAQ\CONTABILIDAD</code></td><td><code>GeneralesSQL</code></td><td><code>dbo.ListaEmpresas</code></td><td>Lee <code>AliasBDD</code>.</td></tr>
        </tbody>
    </table>

    <h2>4. Comandos SQL documentados</h2>
    <p>Ejecutar el bloque de creación del login en cada instancia solamente si el login no existe. La contraseña real no debe escribirse en este PDF.</p>
    <pre>{{ $sqlCommands['login'] }}</pre>
    <p style="margin-top:8pt"><strong>Instancia NOMINAS:</strong></p><pre>{{ $sqlCommands['payroll'] }}</pre>
    <p style="margin-top:8pt"><strong>Instancia CONTABILIDAD:</strong></p><pre>{{ $sqlCommands['accounting'] }}</pre>
    <div class="ok">Permisos mínimos: <strong>CONNECT</strong> a cada base y <strong>SELECT</strong> únicamente sobre la tabla utilizada. No requiere <code>db_owner</code>, <code>INSERT</code>, <code>UPDATE</code> ni <code>DELETE</code>.</div>

    <h2>5. Variables de configuración</h2>
    <table class="data">
        <thead><tr><th>Variable</th><th>Propósito</th></tr></thead>
        <tbody>
            <tr><td><code>CONTPAQ_BACKUP_ROOT</code></td><td>Origen general <code>{{ $sourceRoot }}</code>.</td></tr>
            <tr><td><code>CONTPAQ_MERIDA_CONTABILIDAD_EXPORT_ROOT</code></td><td>Destino USB Contabilidad.</td></tr>
            <tr><td><code>CONTPAQ_MERIDA_NOMINAS_EXPORT_ROOT</code></td><td>Destino USB Nóminas.</td></tr>
            <tr><td><code>CONTPAQ_SQL_USERNAME</code> / <code>CONTPAQ_SQL_PASSWORD</code></td><td>Credenciales SQL. El secreto nunca debe guardarse en Git ni en documentos.</td></tr>
            <tr><td><code>CONTPAQ_MERIDA_NOMINAS_SQL_INSTANCE</code></td><td>Instancia de Nóminas.</td></tr>
            <tr><td><code>CONTPAQ_MERIDA_CONTABILIDAD_SQL_INSTANCE</code></td><td>Instancia de Contabilidad.</td></tr>
        </tbody>
    </table>

    <h2>6. Comprobación rápida ante cambios</h2>
    <ol>
        <li>Confirmar conectividad desde {{ $applicationHost }} hacia {{ $sourceHost }}.</li>
        <li>Abrir las tres rutas UNC y comprobar permisos según la tabla de la primera página.</li>
        <li>Probar conexión de solo lectura a ambas instancias SQL.</li>
        <li>Revisar las variables <code>CONTPAQ_*</code> del <code>.env</code> y limpiar la caché de configuración si cambiaron.</li>
        <li>En Gestión de Respaldos usar “Actualizar y comprobar”.</li>
    </ol>
    <p style="margin-top:14pt;color:#667085">Generado el {{ $generatedAt->locale('es')->translatedFormat('d \d\e F \d\e Y') }} a las {{ $generatedAt->format('H:i:s') }}.</p>
</body>
</html>
