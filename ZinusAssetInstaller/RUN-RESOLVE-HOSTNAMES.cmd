@echo off
setlocal
pushd "%~dp0"

REM Resolve missing hostnames from discovery results using shared local Administrator credentials.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Resolve-ZinusHostnames.ps1"
set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
