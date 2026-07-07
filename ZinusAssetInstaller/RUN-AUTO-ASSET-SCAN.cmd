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

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Invoke-ZinusAssetAutomation.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

echo.
echo ==============================================
echo Proses automation selesai atau terhenti.
echo Tekan tombol apa saja untuk keluar.
echo ==============================================
pause

popd
exit /b %EXIT_CODE%
