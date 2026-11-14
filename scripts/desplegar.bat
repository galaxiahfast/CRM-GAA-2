@echo off
rem Despliega los cambios del CRM en el servidor (git pull, dependencias, compilacion, migraciones, caches y colas).
rem Se salta lo que no cambio. Opciones: --todo, --sin-git, --simular. Tambien: composer run deploy
cd /d "%~dp0.."
set "PATH=C:\xampp\php;C:\Program Files\nodejs;%PATH%"
node scripts\deploy.mjs %*
echo.
pause
