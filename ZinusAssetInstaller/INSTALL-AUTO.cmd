@echo off
setlocal

REM Double-click installer. Reads install-config.json from this folder.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Install-ZinusAssetSync-Auto.ps1"
set "EXITCODE=%ERRORLEVEL%"

echo.
if "%EXITCODE%"=="0" (
    echo Instalasi selesai.
) else (
    echo Instalasi gagal. Cek pesan error di atas.
)
pause
exit /b %EXITCODE%
