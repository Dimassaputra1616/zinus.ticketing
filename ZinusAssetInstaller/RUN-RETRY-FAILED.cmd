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

if not exist "%~dp0Invoke-ZinusRetryFailedAutomation.ps1" (
    echo Invoke-ZinusRetryFailedAutomation.ps1 tidak ditemukan di folder installer.
    popd
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Invoke-ZinusRetryFailedAutomation.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

echo.
echo ==============================================
echo Retry failed automation selesai atau terhenti.
echo Tekan tombol apa saja untuk keluar.
echo ==============================================
pause

popd
exit /b %EXIT_CODE%
