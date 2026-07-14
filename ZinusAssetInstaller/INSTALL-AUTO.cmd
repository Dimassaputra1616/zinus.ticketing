@echo off
setlocal
pushd "%~dp0"

net session >nul 2>&1
if errorlevel 1 (
    echo Meminta akses Administrator...
    powershell.exe -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    popd
    exit /b
)

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
popd
exit /b %EXITCODE%
