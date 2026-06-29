@echo off
setlocal
pushd "%~dp0"

REM Scan IP segment kantor tanpa install agent permanen.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Scan-ZinusSegments.ps1"
set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
