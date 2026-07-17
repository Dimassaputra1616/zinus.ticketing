param(
    [string]$DefaultBasePrefix = "10.62",
    [string]$DefaultSegments = "38,39,36",
    [int]$DefaultStartHost = 1,
    [int]$DefaultEndHost = 254,
    [string]$DefaultServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$DefaultFactory = "GCI-HWANG",
    [string]$DefaultDepartment = "IT",
    [string]$PsExecPath = ".\PsExec.exe",
    [ValidateRange(0, 20)]
    [int]$MissingRetryCount = 5,
    [ValidateRange(5, 3600)]
    [int]$MissingRetryDelaySeconds = 30,
    [switch]$DisableExistingAssetSkip
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

function Get-LocalIPv4Address {
    try {
        return @(
            Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop |
                Where-Object {
                    $_.IPAddress -and
                    $_.IPAddress -ne "127.0.0.1" -and
                    $_.IPAddress -notlike "169.254.*"
                } |
                Select-Object -ExpandProperty IPAddress -Unique
        )
    } catch {
        return @(
            [System.Net.Dns]::GetHostAddresses([System.Net.Dns]::GetHostName()) |
                Where-Object { $_.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork } |
                ForEach-Object { $_.IPAddressToString } |
                Where-Object { $_ -ne "127.0.0.1" -and $_ -notlike "169.254.*" } |
                Select-Object -Unique
        )
    }
}

function Merge-DiscoveryRows {
    param(
        [object[]]$BaseRows,
        [object[]]$RetryRows
    )

    $byIp = @{}
    foreach ($row in @($BaseRows)) {
        if ($row.ip_address) {
            $byIp[[string]$row.ip_address] = $row
        }
    }

    foreach ($row in @($RetryRows)) {
        if (-not $row.ip_address) { continue }

        $ip = [string]$row.ip_address
        if (-not $byIp.ContainsKey($ip)) {
            $byIp[$ip] = $row
            continue
        }

        $current = $byIp[$ip]
        $currentReady = ConvertTo-Boolean $current.wsman_5985
        $retryReady = ConvertTo-Boolean $row.wsman_5985
        $currentOnline = ConvertTo-Boolean $current.online
        $retryOnline = ConvertTo-Boolean $row.online

        if ($retryReady -or (-not $currentOnline -and $retryOnline)) {
            $byIp[$ip] = $row
            continue
        }

        if ([string]::IsNullOrWhiteSpace([string]$current.hostname) -and -not [string]::IsNullOrWhiteSpace([string]$row.hostname)) {
            $current.hostname = $row.hostname
            $current.name_source = $row.name_source
            $current.dns_name = $row.dns_name
            $byIp[$ip] = $current
        }
    }

    return @($byIp.Values | Sort-Object ip_address)
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
$anyDeskDeployScript = Join-Path $PSScriptRoot "Deploy-ZinusAnyDesk.ps1"
$psExecFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($PsExecPath)

foreach ($requiredPath in @($discoverScript, $bootstrapScript, $scanScript, $anyDeskDeployScript, $psExecFullPath)) {
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

$anyDeskInput = Read-Host "Pasang AnyDesk massal ke PC yang belum punya? (y/N) [N]"
$deployAnyDesk = $anyDeskInput -match '^(y|yes|ya)$'
$anyDeskInstallerPath = ""

if ($deployAnyDesk) {
    $anyDeskCandidates = @(
        Get-ChildItem -LiteralPath $PSScriptRoot -File |
            Where-Object {
                $_.Name -match '(?i)^anydesk.*\.(exe|msi)$'
            } |
            Sort-Object LastWriteTime -Descending
    )

    $defaultAnyDeskInstaller = if ($anyDeskCandidates.Count -gt 0) {
        $anyDeskCandidates[0].FullName
    } else {
        Join-Path $PSScriptRoot "anydesk.exe"
    }

    $anyDeskInstallerInput = Read-Host "Path installer AnyDesk [$defaultAnyDeskInstaller]"
    $anyDeskInstallerPath = if ([string]::IsNullOrWhiteSpace($anyDeskInstallerInput)) {
        $defaultAnyDeskInstaller
    } else {
        $anyDeskInstallerInput.Trim().Trim('"')
    }

    if (-not (Test-Path -LiteralPath $anyDeskInstallerPath -PathType Leaf)) {
        throw "Installer AnyDesk tidak ditemukan: $anyDeskInstallerPath. Download installer resmi lalu taruh di folder $PSScriptRoot."
    }
}

$forceBootstrapInput = Read-Host "Paksa bootstrap policy local admin walau WinRM sudah terbuka? (Y/n) [Y]"
$forceLocalAdminBootstrap = $true
if ($forceBootstrapInput -match '^(n|no|tidak)$') {
    $forceLocalAdminBootstrap = $false
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
$anyDeskPath = Join-Path $PSScriptRoot "zinus-auto-anydesk-results.csv"
$scanPath = Join-Path $PSScriptRoot "zinus-auto-scan-results.csv"
$blockedPath = Join-Path $PSScriptRoot "zinus-auto-needs-policy-or-agent.csv"
$failureAnalysisPath = Join-Path $PSScriptRoot "zinus-auto-failure-analysis.csv"
$failureSummaryPath = Join-Path $PSScriptRoot "zinus-auto-failure-summary.csv"
$dataQualityPath = Join-Path $PSScriptRoot "zinus-auto-data-quality-issues.csv"
$dataQualityScript = Join-Path $PSScriptRoot "Export-ZinusDataQualityIssues.ps1"
$missingHostnamesScript = Join-Path $PSScriptRoot "Export-ZinusMissingHostnames.ps1"
$missingHostnamesPath = Join-Path $PSScriptRoot "zinus-auto-missing-hostnames.csv"
$remediationScript = Join-Path $PSScriptRoot "Export-ZinusRemediationLists.ps1"

foreach ($oldResult in @($discoveryPath, $onlinePath, $verificationPath, $verificationOnlinePath, $bootstrapPath, $anyDeskPath, $scanPath, $blockedPath)) {
    Remove-Item -LiteralPath $oldResult -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "TAHAP 1/5 - Mencari perangkat online..." -ForegroundColor Cyan
& $discoverScript `
    -IpSegment $segments `
    -StartHost $startHost `
    -EndHost $endHost `
    -PingTimeoutMs 1500 `
    -PortTimeoutMs 3000 `
    -NbtstatTimeoutMs 2500 `
    -ProbeWsMan `
    -ResultPath $discoveryPath `
    -OnlineResultPath $onlinePath

$discovery = @(Import-Csv -LiteralPath $discoveryPath)
$onlineRows = @($discovery | Where-Object { ConvertTo-Boolean $_.online })
$localIpAddresses = @(Get-LocalIPv4Address)
$skippedLocalTargets = @(
    $onlineRows |
        Select-Object -ExpandProperty ip_address -Unique |
        Where-Object { $_ -in $localIpAddresses }
)
$onlineTargets = @(
    $onlineRows |
        Select-Object -ExpandProperty ip_address -Unique |
        Where-Object { $_ -notin $localIpAddresses }
)

if ($skippedLocalTargets.Count -gt 0) {
    Write-Host "Lewati IP mesin ini dari target remote: $($skippedLocalTargets -join ', ')" -ForegroundColor Yellow
}

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
Write-Host "TAHAP 2/5 - Mengaktifkan WinRM pada $($needsBootstrap.Count) perangkat online..." -ForegroundColor Cyan
& $bootstrapScript `
    -ComputerName $onlineTargets `
    -ComputerList "" `
    -PsExecPath $psExecFullPath `
    -PortTimeoutMs 3000 `
    -ResultPath $bootstrapPath `
    -Credential $credential `
    -EnableLocalAccountRemoteAdmin `
    -ForceBootstrap:$forceLocalAdminBootstrap `
    -NoFailExit

Write-Host ""
Write-Host "TAHAP 3/5 - Memverifikasi ulang WinRM setelah bootstrap..." -ForegroundColor Cyan
& $discoverScript `
    -IpSegment $onlineTargets `
    -StartHost 1 `
    -EndHost 254 `
    -PingTimeoutMs 1500 `
    -PortTimeoutMs 3000 `
    -NbtstatTimeoutMs 2500 `
    -ProbeWsMan `
    -ResultPath $verificationPath `
    -OnlineResultPath $verificationOnlinePath

$verification = @(Import-Csv -LiteralPath $verificationPath)

for ($retry = 1; $retry -le 2; $retry++) {
    $retryTargets = @(
        $verification |
            Where-Object { -not (ConvertTo-Boolean $_.wsman_5985) } |
            Select-Object -ExpandProperty ip_address -Unique
    )

    if ($retryTargets.Count -eq 0) {
        break
    }

    $retryPath = Join-Path $PSScriptRoot "zinus-auto-verification-retry-$retry.csv"
    $retryOnlinePath = Join-Path $PSScriptRoot "zinus-auto-verification-retry-$retry-online.csv"

    Write-Host "Retry verifikasi WinRM $retry/2 untuk $($retryTargets.Count) target..." -ForegroundColor Yellow
    Start-Sleep -Seconds (5 * $retry)
    & $discoverScript `
        -IpSegment $retryTargets `
        -StartHost 1 `
        -EndHost 254 `
        -PingTimeoutMs 2000 `
        -PortTimeoutMs 5000 `
        -NbtstatTimeoutMs 3000 `
        -ProbeWsMan `
        -ResultPath $retryPath `
        -OnlineResultPath $retryOnlinePath

    $retryRows = @(Import-Csv -LiteralPath $retryPath)
    $verification = @(Merge-DiscoveryRows -BaseRows $verification -RetryRows $retryRows)
    $verification | Export-Csv -Path $verificationPath -NoTypeInformation -Encoding UTF8
    $verification |
        Where-Object { ConvertTo-Boolean $_.online } |
        Export-Csv -Path $verificationOnlinePath -NoTypeInformation -Encoding UTF8
}

$readyTargets = @(
    $verification |
        Where-Object { ConvertTo-Boolean $_.wsman_5985 } |
        Select-Object -ExpandProperty ip_address -Unique |
        Where-Object { $_ -notin $localIpAddresses }
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
            $status = if ($bootstrapResult -and $bootstrapResult.status -eq "already_ready") {
                "lost_wsman_after_verification"
            } elseif ($bootstrapResult) {
                $bootstrapResult.status
            } else {
                "not_ready"
            }

            $reason = if (-not (ConvertTo-Boolean $_.online)) {
                "Perangkat tidak lagi terdeteksi online saat verifikasi."
            } elseif ($bootstrapResult -and $bootstrapResult.status -eq "already_ready") {
                "WinRM sempat terbuka saat bootstrap, tetapi port 5985 tertutup atau tidak reachable saat verifikasi ulang."
            } elseif ($bootstrapResult) {
                $bootstrapResult.message
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
Write-Host "TAHAP 4/5 - Deployment AnyDesk..." -ForegroundColor Cyan
if ($deployAnyDesk -and $readyTargets.Count -gt 0) {
    & $anyDeskDeployScript `
        -ComputerName $readyTargets `
        -ComputerList "" `
        -InstallerPath $anyDeskInstallerPath `
        -Credential $credential `
        -ResultPath $anyDeskPath `
        -NoFailExit
} elseif ($deployAnyDesk) {
    Write-Host "Belum ada PC dengan WinRM siap; deployment AnyDesk dilewati." -ForegroundColor Yellow
} else {
    Write-Host "Deployment AnyDesk tidak dipilih." -ForegroundColor DarkGray
}

$anyDeskResults = if (Test-Path $anyDeskPath) { @(Import-Csv -LiteralPath $anyDeskPath) } else { @() }
$anyDeskReadyCount = @($anyDeskResults | Where-Object { $_.status -eq "success" }).Count
$anyDeskInstalledCount = @($anyDeskResults | Where-Object { $_.action -like "installed*" }).Count
$anyDeskFailedCount = @($anyDeskResults | Where-Object { $_.status -eq "failed" }).Count

Write-Host ""
Write-Host "TAHAP 5/5 - Menarik dan mengirim aset dari $($readyTargets.Count) PC siap..." -ForegroundColor Cyan
if ($readyTargets.Count -gt 0) {
    $skipExistingAssets = -not [bool]$DisableExistingAssetSkip
    $remainingScanTargets = @($readyTargets)
    $allScanResults = @()
    $maxScanAttempts = 1 + $MissingRetryCount

    for ($scanAttempt = 1; $scanAttempt -le $maxScanAttempts -and $remainingScanTargets.Count -gt 0; $scanAttempt++) {
        $attemptPath = if ($scanAttempt -eq 1) {
            $scanPath
        } else {
            Join-Path $PSScriptRoot ("zinus-auto-scan-results-retry-{0}.csv" -f ($scanAttempt - 1))
        }

        Write-Host "Scan attempt $scanAttempt/$maxScanAttempts untuk $($remainingScanTargets.Count) target..." -ForegroundColor Cyan
        & $scanScript `
            -ComputerName $remainingScanTargets `
            -ComputerList "" `
            -Token $token `
            -Factory $factory `
            -Department $department `
            -ServerUrl $serverUrl `
            -PortTimeoutMs 5000 `
            -MaxParallel 12 `
            -RetryCount 2 `
            -RetryDelaySeconds 8 `
            -SkipExisting:$skipExistingAssets `
            -ResultPath $attemptPath `
            -Credential $credential `
            -NoFailExit

        $attemptResults = if (Test-Path $attemptPath) { @(Import-Csv -LiteralPath $attemptPath) } else { @() }
        $allScanResults += $attemptResults
        $remainingScanTargets = @(
            $attemptResults |
                Where-Object { $_.status -in @("failed", "skipped") } |
                Select-Object -ExpandProperty computer -Unique
        )

        if ($remainingScanTargets.Count -gt 0 -and $scanAttempt -lt $maxScanAttempts) {
            Write-Host "$($remainingScanTargets.Count) target belum ketarik; retry dalam $MissingRetryDelaySeconds detik..." -ForegroundColor Yellow
            Start-Sleep -Seconds $MissingRetryDelaySeconds
        }
    }

    $finalScanResults = @(
        $allScanResults |
            Group-Object -Property computer |
            ForEach-Object {
                $preferred = @($_.Group | Where-Object { $_.status -in @("success", "skipped_existing") } | Select-Object -Last 1)
                if ($preferred.Count -gt 0) {
                    $preferred[0]
                } else {
                    $_.Group | Select-Object -Last 1
                }
            }
    )

    $finalScanResults | Export-Csv -Path $scanPath -NoTypeInformation -Encoding UTF8
} else {
    @() | Export-Csv -Path $scanPath -NoTypeInformation -Encoding UTF8
    Write-Host "Belum ada PC dengan WinRM siap; tahap pengambilan aset dilewati." -ForegroundColor Yellow
}

$scanResults = if (Test-Path $scanPath) { @(Import-Csv -LiteralPath $scanPath) } else { @() }
$successCount = @($scanResults | Where-Object { $_.status -eq "success" }).Count
$skippedExistingCount = @($scanResults | Where-Object { $_.status -eq "skipped_existing" }).Count
$failedCount = @($scanResults | Where-Object { $_.status -notin @("success", "skipped_existing") }).Count

$token = $null

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "AUTOMATION SELESAI" -ForegroundColor Green
Write-Host "Ditemukan online : $($onlineTargets.Count)"
Write-Host "WinRM siap       : $($readyTargets.Count)"
Write-Host "Aset sukses      : $successCount"
Write-Host "Aset sudah ada   : $skippedExistingCount"
Write-Host "Scan gagal       : $failedCount"
if ($deployAnyDesk) {
    Write-Host "AnyDesk siap     : $anyDeskReadyCount"
    Write-Host "AnyDesk baru     : $anyDeskInstalledCount"
    Write-Host "AnyDesk gagal    : $anyDeskFailedCount"
    Write-Host "Hasil AnyDesk    : $anyDeskPath"
}
Write-Host "Perlu GPO/agent  : $($blocked.Count)"
Write-Host "Hasil scan       : $scanPath"
Write-Host "Belum terjangkau : $blockedPath"

if (Test-Path -LiteralPath $dataQualityScript) {
    try {
        & $dataQualityScript `
            -ScanPath $scanPath `
            -ResultPath $dataQualityPath

        Write-Host "Quality issue    : $dataQualityPath"
    } catch {
        Write-Host "Gagal membuat data quality report: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

$failureAnalysisScript = Join-Path $PSScriptRoot "Export-ZinusFailureAnalysis.ps1"
if (Test-Path -LiteralPath $failureAnalysisScript) {
    try {
        & $failureAnalysisScript `
            -DiscoveryPath $discoveryPath `
            -VerificationPath $verificationPath `
            -BlockedPath $blockedPath `
            -ScanPath $scanPath `
            -AnyDeskPath $anyDeskPath `
            -ResultPath $failureAnalysisPath `
            -SummaryPath $failureSummaryPath

        Write-Host "Analisa gagal    : $failureAnalysisPath"
        Write-Host "Summary gagal    : $failureSummaryPath"

        if (Test-Path -LiteralPath $missingHostnamesScript) {
            & $missingHostnamesScript `
                -FailureAnalysisPath $failureAnalysisPath `
                -DiscoveryPath $discoveryPath `
                -VerificationPath $verificationPath `
                -ResultPath $missingHostnamesPath

            Write-Host "Hostname kosong  : $missingHostnamesPath"
        }

        if (Test-Path -LiteralPath $remediationScript) {
            & $remediationScript `
                -FailureAnalysisPath $failureAnalysisPath `
                -DataQualityPath $dataQualityPath `
                -OutputDirectory $PSScriptRoot

            Write-Host "Remediation CSV  : $PSScriptRoot\zinus-remediation-*.csv"
        }
    } catch {
        Write-Host "Gagal membuat analisa failure: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}
Write-Host "============================================================" -ForegroundColor Cyan
