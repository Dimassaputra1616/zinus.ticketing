@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "COMPUTER_LIST=%SCRIPT_DIR%computers.txt"
pushd "%SCRIPT_DIR%"

if not exist "%COMPUTER_LIST%" (
    echo File computers.txt tidak ditemukan di folder installer.
    echo Buat file computers.txt berisi satu hostname atau IP per baris.
    popd
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$token = Read-Host 'Masukkan Asset Sync token';" ^
    "$cred = Get-Credential -Message 'Masukkan credential admin untuk remote scan';" ^
    "& '%SCRIPT_DIR%Scan-ZinusAssetsRemote.ps1' -ComputerList '%COMPUTER_LIST%' -Token $token -Credential $cred"

set "EXIT_CODE=%ERRORLEVEL%"
popd
exit /b %EXIT_CODE%
