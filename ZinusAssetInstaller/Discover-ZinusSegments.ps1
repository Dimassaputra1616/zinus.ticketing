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

$probeInput = Read-Host "Cek WinRM port 5985 juga? (Y/n) [Y]"
$probeWsMan = $true
if ($probeInput -match '^(n|no|tidak)$') {
    $probeWsMan = $false
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

$discover = Join-Path $PSScriptRoot "Discover-ZinusNetwork.ps1"
if (-not (Test-Path $discover)) {
    Write-Host "Discover-ZinusNetwork.ps1 tidak ditemukan." -ForegroundColor Red
    exit 1
}

$params = @{
    IpSegment = $segments
    StartHost = $startHost
    EndHost = $endHost
}

if ($probeWsMan) {
    $params.ProbeWsMan = $true
}

& $discover @params
