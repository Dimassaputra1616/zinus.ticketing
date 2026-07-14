@echo off
setlocal
pushd "%~dp0"

REM Scan IP segment kantor tanpa install agent permanen.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Scan-ZinusSegments.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

echo.
echo ==============================================
echo Proses scanning selesai atau terhenti.
echo Tekan tombol apa saja untuk keluar.
echo ==============================================
pause

popd
exit /b %EXIT_CODE%
