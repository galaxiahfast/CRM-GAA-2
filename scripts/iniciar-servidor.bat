@echo off
rem Arranca el CRM en el servidor: la web (0.0.0.0:8001), el programador (sincroniza el reloj checador cada
rem minuto y lo revisa a las 23:00) y las colas. Si alguno se cae, se reinicia solo a los 5 segundos.
rem La tarea de Windows "CRM GAA servidor" lo ejecuta con --registro al encender el equipo: asi la salida va a
rem storage\logs\servidor.log (se rota al pasar de 10 MB). Con doble clic se ve en la ventana.
cd /d "%~dp0.."
set "PATH=C:\xampp\php;C:\Program Files\nodejs;%PATH%"
if not exist "storage\logs" mkdir "storage\logs"

if /i not "%~1"=="--registro" (
    node scripts\server.mjs
    goto :eof
)

set "CRM_LOG=storage\logs\servidor.log"
:inicio
node scripts\server.mjs 2>> "storage\logs\servidor-error.log"
rem Si Node se cerro por algun error, se vuelve a iniciar a los 10 segundos (ping sirve de pausa sin consola)
echo [%date% %time%] El servidor se cerro; reiniciando en 10 segundos.>> "storage\logs\servidor-error.log"
ping -n 11 127.0.0.1 >nul
goto inicio
