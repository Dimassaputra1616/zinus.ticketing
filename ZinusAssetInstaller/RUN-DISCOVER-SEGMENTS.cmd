@echo off
setlocal
pushd "%~dp0"

REM GLPI-style network discovery: scan IP segment tanpa install agent.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Discover-ZinusSegments.ps1"
set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
