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

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Enable-ZinusAssetPrereqs.ps1" -EnableLocalAccountRemoteAdmin
set "EXIT_CODE=%ERRORLEVEL%"

echo.
if "%EXIT_CODE%"=="0" (
    echo Prerequisite Asset Sync sudah diaktifkan.
) else (
    echo Gagal mengaktifkan prerequisite. Cek pesan error di atas.
)
pause

popd
exit /b %EXIT_CODE%
