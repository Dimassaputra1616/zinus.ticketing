param(
    [string]$DefaultBasePrefix = "10.62",
    [string]$DefaultSegments = "38,39,36",
    [int]$DefaultStartHost = 1,
    [int]$DefaultEndHost = 254
)

$ErrorActionPreference = "Stop"

$basePrefix = Read-Host "Masukkan base IP prefix [$DefaultBasePrefix]"
if ([string]::IsNullOrWhiteSpace($basePrefix)) {
    $basePrefix = $DefaultBasePrefix
}
$basePrefix = $basePrefix.Trim().TrimEnd(".")

$segmentInput = Read-Host "Masukkan segment, pisahkan koma [$DefaultSegments]"
if ([string]::IsNullOrWhiteSpace($segmentInput)) {
    $segmentInput = $DefaultSegments
}

$startHostInput = Read-Host "Mulai host [$DefaultStartHost]"
$startHost = if ([string]::IsNullOrWhiteSpace($startHostInput)) { $DefaultStartHost } else { [int]$startHostInput }

$endHostInput = Read-Host "Akhir host [$DefaultEndHost]"
$endHost = if ([string]::IsNullOrWhiteSpace($endHostInput)) { $DefaultEndHost } else { [int]$endHostInput }

$psExecInput = Read-Host "Path PsExec.exe [.\PsExec.exe]"
$psExecPath = if ([string]::IsNullOrWhiteSpace($psExecInput)) { Join-Path $PSScriptRoot "PsExec.exe" } else { $psExecInput }

if (-not (Test-Path $psExecPath)) {
    Write-Host "PsExec.exe tidak ditemukan di $psExecPath." -ForegroundColor Red
    Write-Host "Taruh PsExec.exe dari Microsoft Sysinternals di folder installer, lalu jalankan lagi." -ForegroundColor Yellow
    exit 1
}

Write-Host "Masukkan credential local Administrator target. Kalau semua PC pakai akun lokal seragam, username biasanya cukup: Administrator" -ForegroundColor Cyan
$credential = Get-Credential -Message "Credential local admin target"

$trustedHostsInput = Read-Host "Set TrustedHosts admin machine untuk segment ini? (Y/n) [Y]"
$skipTrustedHosts = $false
if ($trustedHostsInput -match '^(n|no|tidak)$') {
    $skipTrustedHosts = $true
}

$localPolicyInput = Read-Host "Aktifkan LocalAccountTokenFilterPolicy untuk local admin remote? (Y/n) [Y]"
$enableLocalPolicy = $true
if ($localPolicyInput -match '^(n|no|tidak)$') {
    $enableLocalPolicy = $false
}

$forceInput = Read-Host "Paksa bootstrap walau port WinRM sudah terbuka? (y/N) [N]"
$forceBootstrap = $false
if ($forceInput -match '^(y|yes|ya)$') {
    $forceBootstrap = $true
}

$segments = @(
    $segmentInput.Split(",") |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ } |
        ForEach-Object {
            if ($_ -match '^\d{1,3}\.\d{1,3}\.\d{1,3}') {
                $_
            } else {
                "$basePrefix.$_"
            }
        }
)

$bootstrap = Join-Path $PSScriptRoot "Bootstrap-ZinusWinRM.ps1"
if (-not (Test-Path $bootstrap)) {
    Write-Host "Bootstrap-ZinusWinRM.ps1 tidak ditemukan." -ForegroundColor Red
    exit 1
}

$params = @{
    ComputerList = ""
    IpSegment    = $segments
    StartHost    = $startHost
    EndHost      = $endHost
    PsExecPath   = $psExecPath
    Credential   = $credential
}

if ($skipTrustedHosts) {
    $params.SkipTrustedHosts = $true
}

if ($enableLocalPolicy) {
    $params.EnableLocalAccountRemoteAdmin = $true
}

if ($forceBootstrap) {
    $params.ForceBootstrap = $true
}

& $bootstrap @params
