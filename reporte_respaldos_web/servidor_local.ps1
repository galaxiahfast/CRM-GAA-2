param([switch]$CheckOnly, [switch]$NoBrowser, [string]$WorkerIp, [string]$OutputPath, [int]$Port=8787)
$ErrorActionPreference = 'Stop'
$scriptPath = $MyInvocation.MyCommand.Path
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$index = Join-Path $root 'dist\index.html'
if (-not (Test-Path -LiteralPath $index)) { $index = Join-Path $root 'index.html' }

# Únicamente carpetas compartidas de logs. No hay comandos de sincronización ni escritura.
$sources = @{
    '192.168.2.178' = @{
        Name = 'IFACTURE'; Share = '\\SRVIFAC\log'
        Profiles = @(
            @{ Name='Comprobantes33'; Log='Comprobantes33' },
            @{ Name='ComprobantesPagos'; Log='ComprobantesPagos' },
            @{ Name='Data'; Log='Data' }
        )
    }
    '192.168.2.203' = @{
        Name = 'AUDICTAMEN'; Share = '\\LENOVO-LA0X1877\log'
        Profiles = @(
            @{ Name='Audictamen'; Log='Audictamen' },
            @{ Name='datafiscal'; Log='datafiscal' },
            @{ Name='datahub'; Log='datagub' },
            @{ Name='MSSQL'; Log='MSSQL' }
        )
    }
    '192.168.1.2' = @{
        Name = 'Compaqi (Tulum)'; Share = '\\SRVAUDI2\log'
        Profiles = @(
            @{ Name='Compac'; Log='Compac' },
            @{ Name='Data'; Log='Data' }
        )
    }
    '192.168.2.210' = @{
        Name = 'Compaqi (Merida)'; Share = '\\SRVCONTPAQ\log'
        Profiles = @(
            @{ Name='base_contabilidad'; Log='base_contabilidad' },
            @{ Name='base_nominas'; Log='base_nominas' },
            @{ Name='Carpeta_Contpaq'; Log='Carpeta_contpaq' },
            @{ Name='Compac'; Log='Compac' }
        )
    }
    '192.168.2.252' = @{
        Name = 'Compaqi (Auditoria)'; Share = '\\SRVCONTA2\log'
        Profiles = @(
            @{ Name='Compac'; Log='compac' },
            @{ Name='Data'; Log='Data' }
        )
    }
}

function Decode-Html([string]$value) {
    return [System.Net.WebUtility]::HtmlDecode(($value -replace '<[^>]+>', '' -replace '\s+', ' ').Trim())
}

function Company-From-File([string]$file) {
    $name = [IO.Path]::GetFileName($file.TrimStart('\'))
    if ($name -notmatch '(?i)\.(mdf|ldf)$') { return $null }
    if ($name -notmatch '(?i)^(cc_|gub_|ct)') { return $null }
    $name = $name -replace '(?i)_log\.ldf$', '' -replace '(?i)\.(mdf|ldf)$', ''
    $name = $name -replace '(?i)^cc_[^_]+_CFDI_', ''
    $name = $name -replace '(?i)^gub_[^_]+_\d{4}_', ''
    $name = $name -replace '(?i)^ct(?=[A-Z0-9])', ''
    $ignored = @('ADD_Catalogos','AppManager','DB_Directory','GeneralesSQL','master','mastlog','model','modellog','MSDBData','MSDBLog','tempdb','templog')
    if ($ignored -contains $name) { return $null }
    return $name
}

function Read-LatestLog([string]$path) {
    $stream = [IO.File]::Open($path,[IO.FileMode]::Open,[IO.FileAccess]::Read,([IO.FileShare]::ReadWrite -bor [IO.FileShare]::Delete))
    $readBytes = 0L
    try {
        $length = $stream.Length
        $headBuffer = New-Object byte[] ([int][math]::Min(16384,$length))
        $headCount = $stream.Read($headBuffer,0,$headBuffer.Length)
        $readBytes += $headCount
        $head = [Text.Encoding]::UTF8.GetString($headBuffer,0,$headCount)
        $target = [regex]::Match($head,'href="#(?<id>cs_\d+)">Ir al m[^<]+reciente','IgnoreCase').Groups['id'].Value
        if (-not $target) { throw 'No se encontro el enlace a la ultima ejecucion' }
        if ($length -le 131072) {
            $buffer = New-Object byte[] ([int]$length)
            [Array]::Copy($headBuffer,$buffer,$headCount)
            $offset=$headCount
            while ($offset -lt $buffer.Length) {
                $count=$stream.Read($buffer,$offset,$buffer.Length-$offset)
                if ($count -eq 0) { break }
                $offset += $count
                $readBytes += $count
            }
            $text=[Text.Encoding]::UTF8.GetString($buffer,0,$offset)
            $marker=[regex]::Match($text,"<h2\s+id=`"$target`"",'IgnoreCase')
            if (-not $marker.Success) { throw 'La ultima ejecucion no esta completa en el log' }
            return @{Html=$text.Substring($marker.Index);TotalBytes=$length;ReadBytes=$readBytes}
        }
        $window = [math]::Min(131072,$length-$headCount)
        $previousBuffer=$null
        $previousOffset=0
        while ($true) {
            [void]$stream.Seek($length-$window,[IO.SeekOrigin]::Begin)
            $buffer = New-Object byte[] ([int]$window)
            $offset=0
            $newLength=$buffer.Length-$previousOffset
            if ($window -eq $length) {
                [Array]::Copy($headBuffer,$buffer,$headCount)
                $offset=$headCount
                [void]$stream.Seek($headCount,[IO.SeekOrigin]::Begin)
            }
            while ($offset -lt $newLength) {
                $count=$stream.Read($buffer,$offset,$newLength-$offset)
                if ($count -eq 0) { break }
                $offset += $count
                $readBytes += $count
            }
            if ($previousOffset) { [Array]::Copy($previousBuffer,0,$buffer,$newLength,$previousOffset); $offset += $previousOffset }
            $tail = [Text.Encoding]::UTF8.GetString($buffer,0,$offset)
            $marker = [regex]::Match($tail,"<h2\s+id=`"$target`"",'IgnoreCase')
            if ($marker.Success) { return @{Html=$tail.Substring($marker.Index); TotalBytes=$length; ReadBytes=$readBytes} }
            if ($window -eq $length) { throw 'La ultima ejecucion no esta completa en el log' }
            $previousBuffer=$buffer
            $previousOffset=$offset
            $window = if ($window*2 -ge $length-$headCount) { $length } else { $window*2 }
        }
    } finally { $stream.Dispose() }
}

function Read-ProfileLog([string]$path, [string]$profile) {
    $timer = [Diagnostics.Stopwatch]::StartNew()
    $latest = Read-LatestLog $path
    $html = $latest.Html
    $target = [regex]::Match($html,'<h2\s+id="(?<id>cs_\d+)"','IgnoreCase').Groups['id'].Value
    $blockMatch = [regex]::Match($html, "<h2\s+id=`"$([regex]::Escape($target))`"[^>]*>(?<date>.*?)</h2>(?<body>.*?)(?=<h2\s+id=|</body>)", 'IgnoreCase,Singleline')
    if (-not $blockMatch.Success) {
        return [ordered]@{ profile=$profile; available=$false; date=''; errors=@(); message='El formato del log no pudo interpretarse' }
    }
    $date = Decode-Html $blockMatch.Groups['date'].Value
    $body = $blockMatch.Groups['body'].Value
    $tableStart=$body.IndexOf('<table',[StringComparison]::OrdinalIgnoreCase)
    $summary = if ($tableStart -ge 0) { $body.Substring(0,$tableStart) } else { $body }
    $issues = [Collections.Generic.List[string]]::new()
    foreach ($line in [regex]::Split($summary, '(?i)<br\s*/?>|</p>|</div>')) {
        $plain = Decode-Html $line
        if ($plain -match '(?i)configuraci.n inv.lida|directorio (destino|origen) inv.lido') {
            $issues.Add($plain)
        }
    }
    $failedRows = 0
    $companies = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    $failedFiles = [Collections.Generic.List[object]]::new()
    $seenFiles = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    foreach ($row in [regex]::Matches($blockMatch.Groups['body'].Value, '<tr[^>]*>(?<row>.*?)</tr>', 'IgnoreCase,Singleline')) {
        $first = [regex]::Match($row.Groups['row'].Value,'<td[^>]*>(?<cell>.*?)</td>','IgnoreCase,Singleline')
        if ((Decode-Html $first.Groups['cell'].Value) -notmatch '^(Fall|Error)') { continue }
        $cells = @([regex]::Matches($row.Groups['row'].Value, '<td[^>]*>(?<cell>.*?)</td>', 'IgnoreCase,Singleline') | ForEach-Object { Decode-Html $_.Groups['cell'].Value })
        if ($cells.Count -ge 5 -and $cells[0] -match '^(Fall|Error)') {
            $failedRows++
            $company = Company-From-File $cells[4]
            if ($company) {
                [void]$companies.Add($company)
                if ($seenFiles.Add($cells[4])) { $failedFiles.Add(@{path=$cells[4];company=$company}) }
            }
        }
    }
    if ($failedRows -gt 0 -and $companies.Count -eq 0 -and $issues.Count -eq 0) {
        $issues.Add('Se registraron errores en archivos internos del respaldo.')
    }
    $plainSummary=Decode-Html ($summary -replace '(?i)<br\s*/?>',' | ')
    $counts=[regex]::Match($plainSummary,'Hecho:\s*(?<done>\d+)/(?<total>\d+)')
    $source=[regex]::Match($plainSummary,'Izquierda:\s*(?<path>[^|]+)').Groups['path'].Value.Trim()
    $destination=[regex]::Match($plainSummary,'Derecha:\s*(?<path>[^|]+)').Groups['path'].Value.Trim()
    $duration=[regex]::Match($plainSummary,'Tiempo Transcurrido:\s*(?<time>[^|]+)').Groups['time'].Value.Trim()
    return [ordered]@{ profile=$profile; available=$true; done=$true; date=$date; errors=@($companies | Sort-Object); failedFiles=@($failedFiles); issues=@($issues | Select-Object -Unique); failed=($failedRows -gt 0 -or $issues.Count -gt 0); message=''; processed=if($counts.Success){$counts.Groups['done'].Value+'/'+$counts.Groups['total'].Value}else{''}; source=$source; destination=$destination; duration=$duration; readBytes=$latest.ReadBytes; totalBytes=$latest.TotalBytes; readSeconds=[math]::Round($timer.Elapsed.TotalSeconds,3) }
}

function Read-AllLogs([string]$SelectedIp) {
    $result = @()
    foreach ($ip in $sources.Keys) {
        if ($SelectedIp -and $ip -ne $SelectedIp) { continue }
        $server = $sources[$ip]
        $profiles = $server.Profiles | ForEach-Object {
            $profileName = $_.Name
            try { Read-ProfileLog (Join-Path $server.Share "$($_.Log).log.html") $profileName }
            catch { [ordered]@{ profile=$profileName; available=$false; date=''; errors=@(); message='No se pudo leer el log' } }
        }
        $result += [ordered]@{ ip=$ip; name=$server.Name; share=$server.Share; profiles=@($profiles) }
    }
    return [ordered]@{ readAt=(Get-Date).ToString('dd/MM/yyyy hh:mm tt'); servers=$result }
}

if ($WorkerIp) {
    $server = $sources[$WorkerIp]
    $workerProfiles = @($server.Profiles | ForEach-Object { [ordered]@{profile=$_.Name;available=$false;done=$false;date='';errors=@();issues=@();failed=$false;message='Leyendo'} })
    $ast=[Management.Automation.Language.Parser]::ParseFile($scriptPath,[ref]$null,[ref]$null)
    $definitions=@($ast.FindAll({param($node) $node -is [Management.Automation.Language.FunctionDefinitionAst] -and $node.Name -in @('Decode-Html','Company-From-File','Read-LatestLog','Read-ProfileLog')},$false)|ForEach-Object{$_.Extent.Text})
    $taskScript = 'param($logPath,$profileName)' + "`n" + ($definitions -join "`n") + "`nRead-ProfileLog `$logPath `$profileName"
    $pool = [RunspaceFactory]::CreateRunspacePool(1,$server.Profiles.Count)
    $pool.Open()
    $tasks=@()
    foreach ($item in $server.Profiles) {
        $shell=[powershell]::Create()
        $shell.RunspacePool=$pool
        [void]$shell.AddScript($taskScript).AddArgument((Join-Path $server.Share "$($item.Log).log.html")).AddArgument($item.Name)
        $tasks+=@{Name=$item.Name; Shell=$shell; Handle=$shell.BeginInvoke(); Done=$false}
    }
    try {
        while (@($tasks | Where-Object { -not $_.Done }).Count -gt 0) {
            $changed=$false
            foreach ($task in $tasks) {
                if ($task.Done -or -not $task.Handle.IsCompleted) { continue }
                try {
                    $output=@($task.Shell.EndInvoke($task.Handle))
                    if ($task.Shell.HadErrors -or $output.Count -ne 1) { throw 'No se pudo completar la lectura' }
                    $readProfile=$output[0]
                    $readProfile['done']=$true
                } catch { $readProfile=@{profile=$task.Name;available=$false;done=$true;date='';errors=@();issues=@();failed=$false;message='Log no disponible o sin permiso'} }
                for ($i=0; $i -lt $workerProfiles.Count; $i++) { if ($workerProfiles[$i].profile -eq $task.Name) { $workerProfiles[$i]=$readProfile } }
                $task.Done=$true
                $task.Shell.Dispose()
                $changed=$true
            }
            if ($changed) {
                $json=@{servers=@(@{ip=$WorkerIp;name=$server.Name;profiles=$workerProfiles})}|ConvertTo-Json -Depth 8 -Compress
                $partial="$OutputPath.partial"
                [IO.File]::WriteAllText($partial,$json,[Text.Encoding]::UTF8)
                if ([IO.File]::Exists($OutputPath)) { [IO.File]::Replace($partial,$OutputPath,"$OutputPath.backup") }
                else { [IO.File]::Move($partial,$OutputPath) }
            }
            if (@($tasks | Where-Object { -not $_.Done }).Count) { Start-Sleep -Milliseconds 100 }
        }
    } finally { $pool.Dispose() }
    return
}
if ($CheckOnly) { Read-AllLogs | ConvertTo-Json -Depth 8; return }
$readEntries = @()
$operation = ''
function Stop-ReadWorkers {
    foreach ($entry in $script:readEntries) {
        if (-not $entry.Done -and -not $entry.Process.HasExited) { $entry.Process.Kill() }
    }
}
function Cancel-ReadWorkers {
    Stop-ReadWorkers
    foreach ($entry in $script:readEntries) {
        if ($entry.Done) { continue }
        $entry.Done=$true
        $entry.Status='cancelled'
        if (-not $entry.Result) {
            $server=$sources[$entry.Ip]
            $entry.Result=@{ip=$entry.Ip;name=$server.Name;profiles=@($server.Profiles|ForEach-Object{@{profile=$_.Name;available=$false;done=$false;date='';errors=@();issues=@();failed=$false;message='Cancelado'}})}
        }
        foreach ($p in $entry.Result.profiles) { if (-not $p.done) { $p.done=$true; $p.message='Lectura cancelada'; $p.available=$false } }
    }
}
function Start-ReadWorkers {
    Stop-ReadWorkers
    $script:operation = [guid]::NewGuid().ToString('N')
    $taskFolder = Join-Path ([IO.Path]::GetTempPath()) ("ReporteRespaldos-" + $script:operation)
    [void][IO.Directory]::CreateDirectory($taskFolder)
    $script:readEntries = @()
    foreach ($ip in ($sources.Keys | Sort-Object)) {
        $file = Join-Path $taskFolder "$ip.json"
        $args = @('-NoProfile','-ExecutionPolicy','Bypass','-File',"`"$scriptPath`"",'-WorkerIp',$ip,'-OutputPath',"`"$file`"")
        $process = Start-Process -FilePath "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" -ArgumentList $args -WindowStyle Hidden -PassThru
        $script:readEntries += @{ Ip=$ip; Process=$process; File=$file; Started=[datetime]::UtcNow; Done=$false; Status='reading'; Result=$null }
    }
}
function Get-ReadProgress {
    foreach ($entry in $script:readEntries) {
        if ($entry.Done) { continue }
        $message = ''
        if ([IO.File]::Exists($entry.File)) {
            try { $payload=[IO.File]::ReadAllText($entry.File,[Text.Encoding]::UTF8)|ConvertFrom-Json; $entry.Result=$payload.servers[0] } catch { }
        }
        if ($entry.Process.HasExited) {
            try {
                $payload = [IO.File]::ReadAllText($entry.File, [Text.Encoding]::UTF8) | ConvertFrom-Json
                $entry.Result = $payload.servers[0]
                $entry.Status = 'finished'
                foreach ($p in $entry.Result.profiles) {
                    if (-not $p.done) { $p.done=$true; $p.available=$false; $p.message='No se pudo completar la lectura'; $entry.Status='unavailable' }
                }
            } catch { $message = 'No se pudieron leer los logs' }
            $entry.Done = $true
        }
        if ($message) {
            $server = $sources[$entry.Ip]
            try {
                $partialResult = [IO.File]::ReadAllText($entry.File, [Text.Encoding]::UTF8) | ConvertFrom-Json
                $entry.Result = $partialResult.servers[0]
                foreach ($p in $entry.Result.profiles) { if (-not $p.done) { $p.done=$true; $p.available=$false; $p.message=$message } }
            } catch {
                $profiles = @($server.Profiles | ForEach-Object { @{ profile=$_.Name; available=$false; done=$true; date=''; errors=@(); issues=@(); failed=$false; message=$message } })
                $entry.Result = @{ ip=$entry.Ip; name=$server.Name; profiles=$profiles }
            }
            $entry.Status = 'unavailable'
        }
    }
    $completed = @($script:readEntries | Where-Object Done)
    $snapshots=@($script:readEntries|Where-Object{$_.Result}|ForEach-Object{$_.Result})
    $profileCount=($sources.Values|ForEach-Object{$_.Profiles.Count}|Measure-Object -Sum).Sum
    $finishedProfiles=@($snapshots|ForEach-Object{$_.profiles}|Where-Object done).Count
    return @{ operation=$script:operation; version=3; total=$script:readEntries.Count; done=$completed.Count; totalProfiles=$profileCount; doneProfiles=$finishedProfiles; readAt=(Get-Date).ToString('dd/MM/yyyy hh:mm tt'); servers=$snapshots; states=@($script:readEntries | ForEach-Object { @{ip=$_.Ip; status=$_.Status; elapsed=[math]::Round(([datetime]::UtcNow-$_.Started).TotalSeconds,1)} }) }
}
$listener = [Net.HttpListener]::new()
$listener.Prefixes.Add("http://127.0.0.1:$port/")
$listener.Start()
if (-not $NoBrowser) { Start-Process "http://127.0.0.1:$port/" }
Write-Host "Lector seguro activo en http://127.0.0.1:$port/"
Write-Host 'Cierre esta ventana para detenerlo.'

try {
    while ($listener.IsListening) {
        $context = $listener.GetContext()
        $response = $context.Response
        $response.Headers['Cache-Control']='no-store'
        try {
            if ($context.Request.HttpMethod -ne 'GET') {
                $response.StatusCode = 405
            } elseif ($context.Request.Url.AbsolutePath -in @('/api/start','/api/status','/api/cancel')) {
                if ($context.Request.Url.AbsolutePath -eq '/api/start') { Start-ReadWorkers }
                if ($context.Request.Url.AbsolutePath -eq '/api/cancel') { Cancel-ReadWorkers }
                $json = Get-ReadProgress | ConvertTo-Json -Depth 10 -Compress
                $bytes = [Text.Encoding]::UTF8.GetBytes($json)
                $response.ContentType = 'application/json; charset=utf-8'
                $response.OutputStream.Write($bytes, 0, $bytes.Length)
            } elseif ($context.Request.Url.AbsolutePath -in @('/app.js','/pdf-builder.js','/pdf-lib.min.js','/styles.css')) {
                $asset = Join-Path (Split-Path -Parent $index) $context.Request.Url.AbsolutePath.TrimStart('/')
                $bytes = [IO.File]::ReadAllBytes($asset)
                $response.ContentType = if($asset.EndsWith('.css')){'text/css; charset=utf-8'}else{'application/javascript; charset=utf-8'}
                $response.OutputStream.Write($bytes, 0, $bytes.Length)
            } else {
                $bytes = [IO.File]::ReadAllBytes($index)
                $response.ContentType = 'text/html; charset=utf-8'
                $response.OutputStream.Write($bytes, 0, $bytes.Length)
            }
        } catch {
            $response.StatusCode = 500
            $bytes = [Text.Encoding]::UTF8.GetBytes((@{ error=$_.Exception.Message } | ConvertTo-Json -Compress))
            $response.ContentType = 'application/json; charset=utf-8'
            $response.OutputStream.Write($bytes, 0, $bytes.Length)
        } finally {
            $response.Close()
        }
    }
} finally {
    Stop-ReadWorkers
    $listener.Stop()
}
