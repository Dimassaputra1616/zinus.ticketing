param(
    [switch]$EnableLocalAccountRemoteAdmin,
    [switch]$SkipTrustedHosts
)

$ErrorActionPreference = "Stop"

function Test-IsAdministrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

if (-not (Test-IsAdministrator)) {
    Write-Host "Run this script as Administrator on the target PC." -ForegroundColor Red
    exit 1
}

try {
    Set-Service -Name WinRM -StartupType Automatic -ErrorAction SilentlyContinue
} catch {}

try {
    Start-Service -Name WinRM -ErrorAction SilentlyContinue
} catch {}

try {
    Enable-PSRemoting -Force -SkipNetworkProfileCheck | Out-Null
} catch {
    winrm quickconfig -quiet | Out-Null
}

try {
    Enable-NetFirewallRule -DisplayGroup "Windows Remote Management" -ErrorAction SilentlyContinue | Out-Null
} catch {
    netsh advfirewall firewall set rule group="Windows Remote Management" new enable=yes | Out-Null
}

try {
    Enable-NetFirewallRule -DisplayGroup "File and Printer Sharing" -ErrorAction SilentlyContinue | Out-Null
} catch {
    netsh advfirewall firewall set rule group="File and Printer Sharing" new enable=yes | Out-Null
}

if ($EnableLocalAccountRemoteAdmin) {
    New-ItemProperty `
        -Path "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System" `
        -Name "LocalAccountTokenFilterPolicy" `
        -Value 1 `
        -PropertyType DWord `
        -Force | Out-Null
}

if (-not $SkipTrustedHosts) {
    try {
        Set-Item -Path WSMan:\localhost\Client\TrustedHosts -Value "*" -Force | Out-Null
    } catch {}
}

Write-Host "Zinus Asset prerequisites enabled on $env:COMPUTERNAME." -ForegroundColor Green
Write-Host "WinRM service, WinRM firewall, and File/Printer Sharing firewall rules are enabled." -ForegroundColor Green
