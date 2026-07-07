param(
    [string]$DefaultBasePrefix = "10.62",
    [string]$DefaultSegments = "38,39,36",
    [int]$DefaultStartHost = 1,
    [int]$DefaultEndHost = 254,
    [string]$DefaultServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$DefaultFactory = "GCI-HWANG",
    [string]$DefaultDepartment = "IT",
    [string]$PsExecPath = ".\PsExec.exe"
)

$ErrorActionPreference = "Stop"

function Test-IsAdministrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function ConvertTo-Boolean {
    param($Value)

    if ($Value -is [bool]) {
        return $Value
    }

    return ([string]$Value).Trim() -eq "True"
}

function Read-RequiredToken {
    $secureToken = Read-Host "Masukkan Asset Sync token" -AsSecureString
    $tokenBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureToken)
    try {
        $token = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($tokenBstr)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($tokenBstr)
    }

    if ([string]::IsNullOrWhiteSpace($token)) {
        throw "Asset Sync token wajib diisi."
    }

    return $token
}

if (-not (Test-IsAdministrator)) {
    throw "Automation harus dijalankan sebagai Administrator. Gunakan RUN-AUTO-ASSET-SCAN.cmd agar elevasi dilakukan otomatis."
}

$discoverScript = Join-Path $PSScriptRoot "Discover-ZinusNetwork.ps1"
$bootstrapScript = Join-Path $PSScriptRoot "Bootstrap-ZinusWinRM.ps1"
$scanScript = Join-Path $PSScriptRoot "Scan-ZinusAssetsRemote.ps1"
$psExecFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($PsExecPath)

foreach ($requiredPath in @($discoverScript, $bootstrapScript, $scanScript, $psExecFullPath)) {
    if (-not (Test-Path $requiredPath)) {
        throw "File wajib tidak ditemukan: $requiredPath"
    }
}

$basePrefixInput = Read-Host "Masukkan base IP prefix [$DefaultBasePrefix]"
$basePrefix = if ([string]::IsNullOrWhiteSpace($basePrefixInput)) { $DefaultBasePrefix } else { $basePrefixInput.Trim().TrimEnd(".") }

$segmentInput = Read-Host "Masukkan segment, pisahkan koma [$DefaultSegments]"
if ([string]::IsNullOrWhiteSpace($segmentInput)) {
    $segmentInput = $DefaultSegments
}

$startHostInput = Read-Host "Mulai host [$DefaultStartHost]"
$startHost = if ([string]::IsNullOrWhiteSpace($startHostInput)) { $DefaultStartHost } else { [int]$startHostInput }

$endHostInput = Read-Host "Akhir host [$DefaultEndHost]"
$endHost = if ([string]::IsNullOrWhiteSpace($endHostInput)) { $DefaultEndHost } else { [int]$endHostInput }

if ($startHost -lt 1 -or $startHost -gt 254 -or $endHost -lt 1 -or $endHost -gt 254 -or $startHost -gt $endHost) {
    throw "Range host tidak valid. Gunakan nilai 1 sampai 254."
}

$serverUrlInput = Read-Host "Server API tujuan [$DefaultServerUrl]"
$serverUrl = if ([string]::IsNullOrWhiteSpace($serverUrlInput)) { $DefaultServerUrl } else { $serverUrlInput.Trim() }

$factoryInput = Read-Host "Factory/location awal untuk aset baru [$DefaultFactory]"
$factory = if ([string]::IsNullOrWhiteSpace($factoryInput)) { $DefaultFactory } else { $factoryInput.Trim() }

$departmentInput = Read-Host "Department awal untuk aset baru [$DefaultDepartment]"
$department = if ([string]::IsNullOrWhiteSpace($departmentInput)) { $DefaultDepartment } else { $departmentInput.Trim() }

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

if ($segments.Count -eq 0) {
    throw "Minimal satu segment IP harus diisi."
}

$token = Read-RequiredToken
Write-Host "Masukkan satu credential Administrator yang berlaku pada PC target." -ForegroundColor Cyan
$credential = Get-Credential -Message "Credential admin untuk bootstrap dan remote inventory"
if (-not $credential) {
    throw "Credential admin wajib diisi."
}

$discoveryPath = Join-Path $PSScriptRoot "zinus-auto-discovery.csv"
$onlinePath = Join-Path $PSScriptRoot "zinus-auto-online.csv"
$verificationPath = Join-Path $PSScriptRoot "zinus-auto-verification.csv"
$verificationOnlinePath = Join-Path $PSScriptRoot "zinus-auto-verification-online.csv"
$bootstrapPath = Join-Path $PSScriptRoot "zinus-auto-bootstrap.csv"
$scanPath = Join-Path $PSScriptRoot "zinus-auto-scan-results.csv"
$blockedPath = Join-Path $PSScriptRoot "zinus-auto-needs-policy-or-agent.csv"

foreach ($oldResult in @($discoveryPath, $onlinePath, $verificationPath, $verificationOnlinePath, $bootstrapPath, $scanPath, $blockedPath)) {
    Remove-Item -LiteralPath $oldResult -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "TAHAP 1/4 - Mencari perangkat online..." -ForegroundColor Cyan
& $discoverScript `
    -IpSegment $segments `
    -StartHost $startHost `
    -EndHost $endHost `
    -ProbeWsMan `
    -ResultPath $discoveryPath `
    -OnlineResultPath $onlinePath

$discovery = @(Import-Csv -LiteralPath $discoveryPath)
$onlineRows = @($discovery | Where-Object { ConvertTo-Boolean $_.online })
$onlineTargets = @($onlineRows | Select-Object -ExpandProperty ip_address -Unique)

if ($onlineTargets.Count -eq 0) {
    $token = $null
    throw "Tidak ada perangkat online yang ditemukan pada segment dan range tersebut."
}

$initialReadyTargets = @(
    $onlineRows |
        Where-Object { ConvertTo-Boolean $_.wsman_5985 } |
        Select-Object -ExpandProperty ip_address -Unique
)
$needsBootstrap = @($onlineTargets | Where-Object { $_ -notin $initialReadyTargets })

Write-Host ""
Write-Host "TAHAP 2/4 - Mengaktifkan WinRM pada $($needsBootstrap.Count) perangkat online..." -ForegroundColor Cyan
& $bootstrapScript `
    -ComputerName $onlineTargets `
    -ComputerList "" `
    -PsExecPath $psExecFullPath `
    -ResultPath $bootstrapPath `
    -Credential $credential `
    -EnableLocalAccountRemoteAdmin `
    -NoFailExit

Write-Host ""
Write-Host "TAHAP 3/4 - Memverifikasi ulang WinRM setelah bootstrap..." -ForegroundColor Cyan
& $discoverScript `
    -IpSegment $onlineTargets `
    -StartHost 1 `
    -EndHost 254 `
    -ProbeWsMan `
    -ResultPath $verificationPath `
    -OnlineResultPath $verificationOnlinePath

$verification = @(Import-Csv -LiteralPath $verificationPath)
$readyTargets = @(
    $verification |
        Where-Object { ConvertTo-Boolean $_.wsman_5985 } |
        Select-Object -ExpandProperty ip_address -Unique
)

$bootstrapByTarget = @{}
if (Test-Path $bootstrapPath) {
    foreach ($row in @(Import-Csv -LiteralPath $bootstrapPath)) {
        if ($row.computer) {
            $bootstrapByTarget[$row.computer] = $row
        }
    }
}

$blocked = @(
    $verification |
        Where-Object { -not (ConvertTo-Boolean $_.wsman_5985) } |
        ForEach-Object {
            $bootstrapResult = $bootstrapByTarget[$_.ip_address]
            $status = if ($bootstrapResult) { $bootstrapResult.status } else { "not_ready" }
            $reason = if ($bootstrapResult) {
                $bootstrapResult.message
            } elseif (-not (ConvertTo-Boolean $_.online)) {
                "Perangkat tidak lagi terdeteksi online saat verifikasi."
            } else {
                "WinRM port 5985 tetap tertutup."
            }

            [pscustomobject]@{
                ip_address = $_.ip_address
                hostname   = $_.hostname
                status     = $status
                reason     = $reason
            }
        }
)
$blocked | Export-Csv -Path $blockedPath -NoTypeInformation -Encoding UTF8

Write-Host ""
Write-Host "TAHAP 4/4 - Menarik dan mengirim aset dari $($readyTargets.Count) PC siap..." -ForegroundColor Cyan
if ($readyTargets.Count -gt 0) {
    & $scanScript `
        -ComputerName $readyTargets `
        -ComputerList "" `
        -Token $token `
        -Factory $factory `
        -Department $department `
        -ServerUrl $serverUrl `
        -ResultPath $scanPath `
        -Credential $credential `
        -NoFailExit
} else {
    @() | Export-Csv -Path $scanPath -NoTypeInformation -Encoding UTF8
    Write-Host "Belum ada PC dengan WinRM siap; tahap pengambilan aset dilewati." -ForegroundColor Yellow
}

$scanResults = if (Test-Path $scanPath) { @(Import-Csv -LiteralPath $scanPath) } else { @() }
$successCount = @($scanResults | Where-Object { $_.status -eq "success" }).Count
$failedCount = @($scanResults | Where-Object { $_.status -ne "success" }).Count

$token = $null

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "AUTOMATION SELESAI" -ForegroundColor Green
Write-Host "Ditemukan online : $($onlineTargets.Count)"
Write-Host "WinRM siap       : $($readyTargets.Count)"
Write-Host "Aset sukses      : $successCount"
Write-Host "Scan gagal       : $failedCount"
Write-Host "Perlu GPO/agent  : $($blocked.Count)"
Write-Host "Hasil scan       : $scanPath"
Write-Host "Belum terjangkau : $blockedPath"
Write-Host "============================================================" -ForegroundColor Cyan
