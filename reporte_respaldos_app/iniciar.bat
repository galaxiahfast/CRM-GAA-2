@echo off
setlocal
cd /d "%~dp0"
pyw app.py
if errorlevel 1 py app.py
