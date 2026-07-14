@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
pushd "%SCRIPT_DIR%"

if not exist "%SCRIPT_DIR%Collect-ZinusAnyDeskIds.ps1" (
    echo Collect-ZinusAnyDeskIds.ps1 tidak ditemukan di folder installer.
    popd
    exit /b 1
)

if not exist "%SCRIPT_DIR%zinus-auto-verification.csv" (
    echo zinus-auto-verification.csv tidak ditemukan.
    echo Jalankan discovery / automation sampai tahap verifikasi WinRM dulu.
    popd
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$cred = Get-Credential -Message 'Credential admin target untuk baca AnyDesk ID';" ^
    "& '%SCRIPT_DIR%Collect-ZinusAnyDeskIds.ps1' -Credential $cred -MaxParallel 16 -TargetTimeoutSeconds 45 -ResultPath '%SCRIPT_DIR%zinus-anydesk-id-results.csv'"

set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
