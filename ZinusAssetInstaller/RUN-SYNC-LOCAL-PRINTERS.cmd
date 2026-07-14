@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
pushd "%SCRIPT_DIR%"

if not exist "%SCRIPT_DIR%Sync-ZinusLocalPrinters.ps1" (
    echo Sync-ZinusLocalPrinters.ps1 tidak ditemukan di folder installer.
    popd
    exit /b 1
)

if not exist "%SCRIPT_DIR%zinus-auto-scan-results.csv" (
    echo zinus-auto-scan-results.csv tidak ditemukan.
    echo Jalankan remote asset scan dulu supaya daftar PC host tersedia.
    popd
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$token = Read-Host 'Masukkan Asset Sync token';" ^
    "$cred = Get-Credential -Message 'Masukkan credential admin target PC';" ^
    "& '%SCRIPT_DIR%Sync-ZinusLocalPrinters.ps1' -Token $token -Credential $cred -MaxParallel 8 -ResultPath '%SCRIPT_DIR%zinus-local-printer-sync-results.csv'"

set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
