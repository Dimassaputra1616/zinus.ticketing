@echo off
setlocal

REM Silent wrapper for GPO, Intune, SCCM, PDQ, or remote PowerShell deployment.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Install-ZinusAssetSync.ps1" %*
exit /b %ERRORLEVEL%
