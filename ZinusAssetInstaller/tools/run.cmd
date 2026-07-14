@echo off
setlocal

REM Jalankan sync-asset.ps1 di folder yang sama dengan script ini
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0sync-asset.ps1"

endlocal
