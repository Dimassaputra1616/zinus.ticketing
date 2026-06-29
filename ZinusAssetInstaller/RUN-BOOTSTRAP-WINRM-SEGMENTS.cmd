@echo off
setlocal
pushd "%~dp0"

REM Enable WinRM remotely by IP segment using a shared local Administrator credential.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Bootstrap-ZinusWinRMSegments.ps1"
set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
