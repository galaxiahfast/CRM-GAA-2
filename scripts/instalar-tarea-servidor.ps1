#Requires -RunAsAdministrator
# Crea (o actualiza) la tarea de Windows "CRM GAA servidor": arranca el CRM al encender el equipo, sin que nadie
# inicie sesion, y lo mantiene encendido. Se ejecuta una sola vez en el servidor, en PowerShell como administrador:
#   powershell -ExecutionPolicy Bypass -File scripts\instalar-tarea-servidor.ps1
# A diferencia de "schtasks /Create", quita el limite de 3 dias que Windows pone por defecto a las tareas.
$ErrorActionPreference = 'Stop'

$taskName = 'CRM GAA servidor'
$root = Split-Path -Parent $PSScriptRoot
$bat = Join-Path $PSScriptRoot 'iniciar-servidor.bat'
$port = 8001

if (-not (Test-Path $bat)) { throw "No se encontro $bat" }

# La tarea corre con la cuenta del sistema: PHP y Node deben estar donde los busca iniciar-servidor.bat o en el PATH del equipo
$machinePath = [Environment]::GetEnvironmentVariable('Path', 'Machine') -split ';'
$hasPhp = (Test-Path 'C:\xampp\php\php.exe') -or ($machinePath | Where-Object { $_ -and (Test-Path (Join-Path $_ 'php.exe')) })
$hasNode = (Test-Path 'C:\Program Files\nodejs\node.exe') -or ($machinePath | Where-Object { $_ -and (Test-Path (Join-Path $_ 'node.exe')) })
if (-not $hasPhp) { throw 'No se encontro php.exe en C:\xampp\php ni en el PATH del equipo.' }
if (-not $hasNode) { throw 'No se encontro node.exe en C:\Program Files\nodejs ni en el PATH del equipo.' }
if (-not (Test-Path (Join-Path $root 'public\build\manifest.json'))) {
    Write-Warning 'Falta public\build: ejecuta "composer run deploy -- --todo" antes, o la web se vera sin estilos.'
}

# Si la tarea ya existe, se detiene para reemplazarla
$existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existing) {
    Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
}

# El puerto debe estar libre (por ejemplo, cerrar el "php artisan serve" que se abrio a mano)
$busy = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
if ($busy) {
    $owners = ($busy | ForEach-Object { (Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue).ProcessName } | Sort-Object -Unique) -join ', '
    throw "El puerto $port ya esta en uso por: $owners. Cierra ese proceso (por ejemplo el php artisan serve abierto a mano) y vuelve a ejecutar este script."
}

$action = New-ScheduledTaskAction -Execute 'cmd.exe' -Argument "/c `"`"$bat`" --registro`"" -WorkingDirectory $root
$trigger = New-ScheduledTaskTrigger -AtStartup
$trigger.Delay = 'PT1M' # espera un minuto a que MySQL y la red esten listos
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1) `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable `
    -MultipleInstances IgnoreNew

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force | Out-Null
Start-ScheduledTask -TaskName $taskName

Write-Host "Tarea '$taskName' lista y en marcha."
Write-Host "La web queda en http://<IP del servidor>:$port y se inicia sola al encender el equipo."
Write-Host "Registro: $root\storage\logs\servidor.log"
