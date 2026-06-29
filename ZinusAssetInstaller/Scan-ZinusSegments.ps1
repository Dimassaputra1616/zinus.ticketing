param(
    [string]$DefaultBasePrefix = "10.62",
    [string]$DefaultSegments = "38,39,36",
    [int]$DefaultStartHost = 1,
    [int]$DefaultEndHost = 254,
    [string]$DefaultServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$DefaultFactory = "GCI-HWANG",
    [string]$DefaultDepartment = "IT"
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

$serverUrlInput = Read-Host "Server API tujuan [$DefaultServerUrl]"
$serverUrl = if ([string]::IsNullOrWhiteSpace($serverUrlInput)) { $DefaultServerUrl } else { $serverUrlInput.Trim() }

$factoryInput = Read-Host "Factory/location awal untuk aset baru [$DefaultFactory]"
$factory = if ([string]::IsNullOrWhiteSpace($factoryInput)) { $DefaultFactory } else { $factoryInput.Trim() }

$departmentInput = Read-Host "Department awal untuk aset baru [$DefaultDepartment]"
$department = if ([string]::IsNullOrWhiteSpace($departmentInput)) { $DefaultDepartment } else { $departmentInput.Trim() }

$secureToken = Read-Host "Masukkan Asset Sync token" -AsSecureString
$tokenBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureToken)
try {
    $token = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($tokenBstr)
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($tokenBstr)
}

if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Host "Token wajib diisi." -ForegroundColor Red
    exit 1
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

Write-Host ""
Write-Host "Target scan : $($segments -join ', ') host $startHost-$endHost" -ForegroundColor Cyan
Write-Host "Server API  : $serverUrl" -ForegroundColor Cyan
Write-Host "Aset baru   : $factory / $department" -ForegroundColor Cyan
$confirmation = Read-Host "Lanjutkan remote scan dan kirim ke aplikasi? (y/N) [N]"
if ($confirmation -notmatch '^(y|yes|ya)$') {
    $token = $null
    Write-Host "Scan dibatalkan." -ForegroundColor Yellow
    exit 0
}

$credential = Get-Credential -Message "Masukkan credential admin untuk remote scan"

$scanner = Join-Path $PSScriptRoot "Scan-ZinusAssetsRemote.ps1"
if (-not (Test-Path $scanner)) {
    Write-Host "Scan-ZinusAssetsRemote.ps1 tidak ditemukan." -ForegroundColor Red
    exit 1
}

& $scanner `
    -ComputerList "" `
    -IpSegment $segments `
    -StartHost $startHost `
    -EndHost $endHost `
    -Token $token `
    -Factory $factory `
    -Department $department `
    -ServerUrl $serverUrl `
    -Credential $credential

$token = $null
