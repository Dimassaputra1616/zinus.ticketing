@echo off
setlocal

REM Jalankan installer PowerShell dari folder ini
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Install-ZinusAssetSync.ps1"

echo.
echo Selesai menjalankan installer. Tekan tombol apa saja untuk menutup...
pause >nul

endlocal
