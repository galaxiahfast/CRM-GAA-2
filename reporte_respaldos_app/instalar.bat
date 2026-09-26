@echo off
setlocal
cd /d "%~dp0"
py -m pip install -r requirements.txt
if errorlevel 1 (
  echo.
  echo No se pudo completar la instalacion. Verifica que Python este instalado.
  pause
  exit /b 1
)
echo.
echo Instalacion terminada. Ya puedes usar iniciar.bat.
pause
