@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
pushd "%SCRIPT_DIR%"

if not exist "%SCRIPT_DIR%Sync-ZinusNetworkDevices.ps1" (
    echo Sync-ZinusNetworkDevices.ps1 tidak ditemukan di folder installer.
    popd
    exit /b 1
)

if not exist "%SCRIPT_DIR%zinus-remediation-non-windows-devices.csv" (
    echo zinus-remediation-non-windows-devices.csv tidak ditemukan.
    echo Jalankan automation/discovery dulu supaya daftar printer dan NAS tersedia.
    popd
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$token = Read-Host 'Masukkan Asset Sync token';" ^
    "& '%SCRIPT_DIR%Sync-ZinusNetworkDevices.ps1' -Token $token -ResultPath '%SCRIPT_DIR%zinus-network-device-sync-results.csv'"

set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
