param(
    [string]$Token = "",
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$DeviceListPath = ".\zinus-remediation-non-windows-devices.csv",
    [string]$RetryTargetPath = ".\zinus-retry-failed-targets.txt",
    [string]$ReadyTargetPath = ".\zinus-retry-ready-targets.txt",
    [string]$BlockedTargetPath = ".\zinus-retry-blocked-targets.txt",
    [string]$VerificationPath = ".\zinus-retry-verification.csv",
    [string]$BootstrapResultPath = ".\zinus-retry-bootstrap-results.csv",
    [string]$AnyDeskResultPath = ".\zinus-anydesk-id-results-retry.csv",
    [string]$RemoteScanResultPath = ".\zinus-auto-scan-results-retry.csv",
    [string]$NetworkDeviceRetryListPath = ".\zinus-network-device-retry-list.csv",
    [string]$NetworkDeviceResultPath = ".\zinus-network-device-sync-results-retry.csv",
    [string]$LocalPrinterResultPath = ".\zinus-local-printer-sync-results-retry.csv",
    [string]$SummaryPath = ".\zinus-retry-summary.csv",
    [ValidateRange(1, 30)]
    [int]$AnyDeskMaxParallel = 16,
    [ValidateRange(1, 50)]
    [int]$RemoteScanMaxParallel = 12,
    [ValidateRange(1, 20)]
    [int]$LocalPrinterMaxParallel = 8,
    [ValidateRange(10, 600)]
    [int]$AnyDeskTargetTimeoutSeconds = 45,
    [ValidateRange(30, 1800)]
    [int]$LocalPrinterTargetTimeoutSeconds = 120,
    [ValidateRange(250, 10000)]
    [int]$ProbeTimeoutMs = 750,
    [ValidateRange(1, 100)]
    [int]$VerifyMaxParallel = 50,
    [switch]$SkipBootstrap,
    [switch]$SkipAnyDeskCollect,
    [switch]$SkipRemoteScan,
    [switch]$SkipNetworkDevices,
    [switch]$SkipLocalPrinters,
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

Set-Location -LiteralPath $PSScriptRoot

function Write-Phase {
    param([string]$Message)

    Write-Host ""
    Write-Host $Message -ForegroundColor Cyan
}

function Write-Info {
    param([string]$Message)

    Write-Host $Message -ForegroundColor DarkGray
}

function Resolve-ZinusPath {
    param([string]$Path)

    return $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($Path)
}

function Convert-SecureStringToPlainText {
    param([Security.SecureString]$SecureString)

    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecureString)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
}

function Import-CsvSafe {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        return @()
    }

    return @(Import-Csv -LiteralPath $Path)
}

function Get-TextListSafe {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        return @()
    }

    return @(
        Get-Content -LiteralPath $Path |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    )
}

function Set-TextList {
    param(
        [string]$Path,
        [string[]]$Items
    )

    $fullPath = Resolve-ZinusPath -Path $Path
    $dir = Split-Path -Parent $fullPath
    if ($dir -and -not (Test-Path -LiteralPath $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }

    $cleanItems = @($Items | Where-Object { $_ } | Sort-Object -Unique)
    if ($cleanItems.Count -gt 0) {
        $cleanItems | Set-Content -LiteralPath $fullPath -Encoding ASCII
    } else {
        "" | Set-Content -LiteralPath $fullPath -Encoding ASCII
    }

    return $fullPath
}

function Test-TrueValue {
    param([object]$Value)

    if ($Value -is [bool]) { return [bool]$Value }
    return ([string]$Value) -match '^(?i:true|1|yes|y)$'
}

function New-StringSet {
    param([string[]]$Values)

    $set = @{}
    foreach ($value in @($Values | Where-Object { $_ })) {
        $set[[string]$value] = $true
    }
    return $set
}

function Add-RetryTarget {
    param(
        [hashtable]$Set,
        [string]$Value
    )

    if ([string]::IsNullOrWhiteSpace($Value)) {
        return
    }

    $target = $Value.Trim()
    if ($target -match '^\d{1,3}(\.\d{1,3}){3}$' -or $target -match '^[A-Za-z0-9_.-]+$') {
        $Set[$target] = $true
    }
}

function Add-RetryTargetsFromTextFile {
    param(
        [hashtable]$Set,
        [string]$Path
    )

    foreach ($target in Get-TextListSafe -Path $Path) {
        Add-RetryTarget -Set $Set -Value $target
    }
}

function Add-RetryTargetsFromStatusCsv {
    param(
        [hashtable]$Set,
        [string]$Path,
        [string]$TargetColumn,
        [string[]]$FailedStatuses
    )

    foreach ($row in Import-CsvSafe -Path $Path) {
        $status = [string]$row.status
        if ($FailedStatuses -notcontains $status) {
            continue
        }

        Add-RetryTarget -Set $Set -Value ([string]$row.$TargetColumn)
    }
}

function Add-RetryTargetsFromVerification {
    param(
        [hashtable]$Set,
        [string]$Path
    )

    foreach ($row in Import-CsvSafe -Path $Path) {
        if (-not $row.ip_address) {
            continue
        }

        if ((Test-TrueValue -Value $row.online) -and -not (Test-TrueValue -Value $row.wsman_5985)) {
            Add-RetryTarget -Set $Set -Value ([string]$row.ip_address)
        }
    }
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

function Get-NonWindowsExcludeIps {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        return @()
    }

    return @(
        Import-Csv -LiteralPath $Path |
            Where-Object {
                $_.ip_address -and
                $_.likely_device_type -in @("Printer", "NAS / storage", "Network device / gateway")
            } |
            Select-Object -ExpandProperty ip_address -Unique
    )
}

function Get-RetryTargets {
    param([string]$DeviceListPath)

    $set = @{}

    foreach ($path in @(
            ".\zinus-final-blocked-targets.txt",
            ".\zinus-force-bootstrap-retry-targets.txt",
            ".\zinus-anydesk-id-retry-targets.txt",
            ".\zinus-local-printer-retry-hosts.txt"
        )) {
        Add-RetryTargetsFromTextFile -Set $set -Path $path
    }

    foreach ($path in @(
            ".\zinus-final-verification.csv",
            ".\zinus-auto-verification.csv"
        )) {
        Add-RetryTargetsFromVerification -Set $set -Path $path
    }

    foreach ($path in @(
            ".\zinus-retry-bootstrap-results.csv",
            ".\zinus-final-bootstrap-results.csv"
        )) {
        Add-RetryTargetsFromStatusCsv -Set $set -Path $path -TargetColumn "computer" -FailedStatuses @("failed", "skipped")
    }

    foreach ($path in @(
            ".\zinus-anydesk-id-results-retry.csv",
            ".\zinus-anydesk-id-results-final.csv",
            ".\zinus-anydesk-id-results.csv"
        )) {
        Add-RetryTargetsFromStatusCsv -Set $set -Path $path -TargetColumn "computer" -FailedStatuses @("failed")
    }

    foreach ($path in @(
            ".\zinus-auto-scan-results-retry.csv",
            ".\zinus-auto-scan-results-final.csv",
            ".\zinus-auto-scan-results.csv",
            ".\zinus-asset-remote-scan-results.csv"
        )) {
        Add-RetryTargetsFromStatusCsv -Set $set -Path $path -TargetColumn "computer" -FailedStatuses @("failed", "skipped")
    }

    foreach ($path in @(
            ".\zinus-local-printer-sync-results-retry.csv",
            ".\zinus-local-printer-sync-results-final.csv",
            ".\zinus-local-printer-sync-results.csv"
        )) {
        Add-RetryTargetsFromStatusCsv -Set $set -Path $path -TargetColumn "computer" -FailedStatuses @("failed")
    }

    $excludeSet = New-StringSet -Values @(Get-NonWindowsExcludeIps -Path $DeviceListPath)
    $localSet = New-StringSet -Values @(Get-LocalIPv4Address)
    return @(
        $set.Keys |
            Where-Object {
                -not $excludeSet.ContainsKey([string]$_) -and
                -not $localSet.ContainsKey([string]$_)
            } |
            Sort-Object -Unique
    )
}

function Test-Ping {
    param(
        [string]$Computer,
        [int]$TimeoutMs
    )

    $ping = New-Object System.Net.NetworkInformation.Ping
    try {
        $reply = $ping.Send($Computer, $TimeoutMs)
        return $reply.Status -eq [System.Net.NetworkInformation.IPStatus]::Success
    } catch {
        return $false
    } finally {
        $ping.Dispose()
    }
}

function Test-TcpPort {
    param(
        [string]$Computer,
        [int]$Port,
        [int]$TimeoutMs
    )

    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $async = $client.BeginConnect($Computer, $Port, $null, $null)
        if (-not $async.AsyncWaitHandle.WaitOne($TimeoutMs, $false)) {
            return $false
        }

        $client.EndConnect($async)
        return $true
    } catch {
        return $false
    } finally {
        $client.Close()
    }
}

function Resolve-DnsNameSafe {
    param([string]$Target)

    try {
        $name = [System.Net.Dns]::GetHostEntry($Target).HostName
        if ($name -and $name -ne $Target) { return $name }
    } catch {}

    return ""
}

function Test-RetryTargets {
    param(
        [string[]]$Targets,
        [int]$TimeoutMs,
        [int]$MaxParallel
    )

    $workerScript = {
        param(
            [string]$Target,
            [int]$TimeoutMs
        )

        function Test-Ping {
            param(
                [string]$Computer,
                [int]$TimeoutMs
            )

            $ping = New-Object System.Net.NetworkInformation.Ping
            try {
                $reply = $ping.Send($Computer, $TimeoutMs)
                return $reply.Status -eq [System.Net.NetworkInformation.IPStatus]::Success
            } catch {
                return $false
            } finally {
                $ping.Dispose()
            }
        }

        function Test-TcpPort {
            param(
                [string]$Computer,
                [int]$Port,
                [int]$TimeoutMs
            )

            $client = New-Object System.Net.Sockets.TcpClient
            try {
                $async = $client.BeginConnect($Computer, $Port, $null, $null)
                if (-not $async.AsyncWaitHandle.WaitOne($TimeoutMs, $false)) {
                    return $false
                }

                $client.EndConnect($async)
                return $true
            } catch {
                return $false
            } finally {
                $client.Close()
            }
        }

        function Resolve-DnsNameSafe {
            param([string]$Target)

            try {
                $name = [System.Net.Dns]::GetHostEntry($Target).HostName
                if ($name -and $name -ne $Target) { return $name }
            } catch {}

            return ""
        }

        $pingOnline = Test-Ping -Computer $Target -TimeoutMs $TimeoutMs
        $smbOpen = Test-TcpPort -Computer $Target -Port 445 -TimeoutMs $TimeoutMs
        $wsmanOpen = Test-TcpPort -Computer $Target -Port 5985 -TimeoutMs $TimeoutMs
        $online = $pingOnline -or $smbOpen -or $wsmanOpen
        $hostname = if ($online) { Resolve-DnsNameSafe -Target $Target } else { "" }
        $detection = @()
        if ($pingOnline) { $detection += "Ping" }
        if ($smbOpen) { $detection += "SMB" }
        if ($wsmanOpen) { $detection += "WinRM" }

        [pscustomobject]@{
            ip_address     = $Target
            online         = $online
            hostname       = $hostname
            name_source    = if ($hostname) { "DNS" } else { "" }
            detection      = ($detection -join "+")
            dns_name       = $hostname
            mac_address    = ""
            neighbor_state = ""
            wsman_5985     = $wsmanOpen
            discovered_at  = (Get-Date).ToString("s")
        }
    }

    $sessionState = [System.Management.Automation.Runspaces.InitialSessionState]::CreateDefault()
    $pool = [System.Management.Automation.Runspaces.RunspaceFactory]::CreateRunspacePool(1, $MaxParallel, $sessionState, $Host)
    $pool.Open()

    $runspaces = @()
    try {
        foreach ($target in $Targets) {
            $pipeline = [System.Management.Automation.PowerShell]::Create()
            $pipeline.RunspacePool = $pool
            $pipeline.AddScript($workerScript) | Out-Null
            $pipeline.AddArgument($target) | Out-Null
            $pipeline.AddArgument($TimeoutMs) | Out-Null
            $runspaces += [pscustomobject]@{
                Target      = $target
                PowerShell  = $pipeline
                AsyncResult = $pipeline.BeginInvoke()
            }
        }

        $results = @()
        $completedCount = 0
        while ($runspaces.Count -gt 0) {
            $completed = @($runspaces | Where-Object { $_.AsyncResult.IsCompleted })
            foreach ($runspace in $completed) {
                $completedCount++
                try {
                    $result = $runspace.PowerShell.EndInvoke($runspace.AsyncResult) | Select-Object -First 1
                } catch {
                    $result = [pscustomobject]@{
                        ip_address     = $runspace.Target
                        online         = $false
                        hostname       = ""
                        name_source    = ""
                        detection      = ""
                        dns_name       = ""
                        mac_address    = ""
                        neighbor_state = ""
                        wsman_5985     = $false
                        discovered_at  = (Get-Date).ToString("s")
                    }
                } finally {
                    $runspace.PowerShell.Dispose()
                }

                $results += $result
                $color = if (Test-TrueValue -Value $result.wsman_5985) { "Green" } elseif (Test-TrueValue -Value $result.online) { "Yellow" } else { "DarkGray" }
                Write-Host "[$completedCount/$($Targets.Count)] $($result.ip_address): online=$($result.online), WinRM=$($result.wsman_5985), $($result.detection)" -ForegroundColor $color
            }

            $runspaces = @($runspaces | Where-Object { $completed -notcontains $_ })
            if ($runspaces.Count -gt 0) {
                Start-Sleep -Milliseconds 100
            }
        }

        return @($results | Sort-Object ip_address)
    } finally {
        $pool.Close()
        $pool.Dispose()
    }
}

function New-NetworkDeviceRetryList {
    param(
        [string]$DeviceListPath,
        [string]$OutputPath
    )

    if (-not (Test-Path -LiteralPath $DeviceListPath)) {
        return @()
    }

    $failedIps = @{}
    foreach ($path in @(
            ".\zinus-network-device-sync-results-retry.csv",
            ".\zinus-network-device-sync-results-final.csv",
            ".\zinus-network-device-sync-results.csv"
        )) {
        foreach ($row in Import-CsvSafe -Path $path) {
            if ($row.status -eq "failed" -and $row.ip_address) {
                $failedIps[[string]$row.ip_address] = $true
            }
        }
    }

    $rows = @(
        Import-Csv -LiteralPath $DeviceListPath |
            Where-Object { $_.ip_address -and $failedIps.ContainsKey([string]$_.ip_address) }
    )

    $rows | Export-Csv -LiteralPath (Resolve-ZinusPath -Path $OutputPath) -NoTypeInformation -Encoding UTF8
    return $rows
}

function Get-StatusCounts {
    param(
        [string]$Path,
        [string]$StatusColumn = "status"
    )

    $rows = @(Import-CsvSafe -Path $Path)
    if ($rows.Count -eq 0) {
        return "no_rows"
    }

    return (($rows |
        Group-Object -Property $StatusColumn |
        Sort-Object Name |
        ForEach-Object { "$($_.Name)=$($_.Count)" }) -join ", ")
}

function Assert-ScriptExists {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "Script tidak ditemukan: $Path"
    }
}

$bootstrapScript = Join-Path $PSScriptRoot "Bootstrap-ZinusWinRM.ps1"
$anyDeskScript = Join-Path $PSScriptRoot "Collect-ZinusAnyDeskIds.ps1"
$remoteScanScript = Join-Path $PSScriptRoot "Scan-ZinusAssetsRemote.ps1"
$networkDeviceScript = Join-Path $PSScriptRoot "Sync-ZinusNetworkDevices.ps1"
$localPrinterScript = Join-Path $PSScriptRoot "Sync-ZinusLocalPrinters.ps1"

foreach ($scriptPath in @($bootstrapScript, $anyDeskScript, $remoteScanScript, $networkDeviceScript, $localPrinterScript)) {
    Assert-ScriptExists -Path $scriptPath
}

if ([string]::IsNullOrWhiteSpace($Token)) {
    $secureToken = Read-Host "Masukkan Asset Sync token" -AsSecureString
    $Token = Convert-SecureStringToPlainText -SecureString $secureToken
}

if ([string]::IsNullOrWhiteSpace($Token)) {
    throw "Token wajib diisi."
}

if (-not $Credential) {
    $Credential = Get-Credential -Message "Credential admin target PC (contoh .\Administrator atau DOMAIN\Administrator)"
}

Write-Host "Zinus retry failed automation" -ForegroundColor Green
Write-Info "Server : $ServerUrl"
Write-Info "Factory: $Factory"
Write-Info "Dept   : $Department"

Write-Phase "TAHAP 1/6 - Kumpulkan target gagal"
$retryTargets = @(Get-RetryTargets -DeviceListPath $DeviceListPath)
Set-TextList -Path $RetryTargetPath -Items $retryTargets | Out-Null
Write-Info "Retry target PC/laptop: $($retryTargets.Count)"
Write-Info "List                  : $RetryTargetPath"

if ($retryTargets.Count -eq 0) {
    Write-Host "Tidak ada target gagal yang bisa diretry dari file hasil saat ini." -ForegroundColor Yellow
    exit 0
}

if (-not $SkipBootstrap) {
    Write-Phase "TAHAP 2/6 - Bootstrap ulang target gagal"
    & $bootstrapScript `
        -ComputerList $RetryTargetPath `
        -Credential $Credential `
        -EnableLocalAccountRemoteAdmin `
        -ForceBootstrap `
        -NoFailExit `
        -ResultPath $BootstrapResultPath
} else {
    Write-Info "Bootstrap dilewati sesuai parameter."
}

Write-Phase "TAHAP 3/6 - Verifikasi target gagal saja"
$verification = @(Test-RetryTargets -Targets $retryTargets -TimeoutMs $ProbeTimeoutMs -MaxParallel $VerifyMaxParallel)
$verification | Export-Csv -LiteralPath (Resolve-ZinusPath -Path $VerificationPath) -NoTypeInformation -Encoding UTF8

$readyTargets = @(
    $verification |
        Where-Object { (Test-TrueValue -Value $_.online) -and (Test-TrueValue -Value $_.wsman_5985) } |
        Select-Object -ExpandProperty ip_address -Unique |
        Sort-Object
)
$blockedTargets = @(
    $verification |
        Where-Object { -not ((Test-TrueValue -Value $_.online) -and (Test-TrueValue -Value $_.wsman_5985)) } |
        Select-Object -ExpandProperty ip_address -Unique |
        Sort-Object
)

Set-TextList -Path $ReadyTargetPath -Items $readyTargets | Out-Null
Set-TextList -Path $BlockedTargetPath -Items $blockedTargets | Out-Null
Write-Info "Ready retry  : $($readyTargets.Count)"
Write-Info "Blocked retry: $($blockedTargets.Count)"

if (-not $SkipAnyDeskCollect) {
    Write-Phase "TAHAP 4/6 - Collect AnyDesk ID target gagal"
    & $anyDeskScript `
        -ComputerList $RetryTargetPath `
        -Credential $Credential `
        -MaxParallel $AnyDeskMaxParallel `
        -TargetTimeoutSeconds $AnyDeskTargetTimeoutSeconds `
        -ResultPath $AnyDeskResultPath
} else {
    Write-Info "Collect AnyDesk ID dilewati sesuai parameter."
}

if (-not $SkipRemoteScan -and $readyTargets.Count -gt 0) {
    Write-Phase "TAHAP 5/6 - Retry sync PC/laptop + monitor yang sudah ready"
    & $remoteScanScript `
        -ComputerList $ReadyTargetPath `
        -Token $Token `
        -Credential $Credential `
        -Factory $Factory `
        -Department $Department `
        -ServerUrl $ServerUrl `
        -MaxParallel $RemoteScanMaxParallel `
        -NoFailExit `
        -ResultPath $RemoteScanResultPath
} elseif ($SkipRemoteScan) {
    Write-Info "Remote scan PC/laptop dilewati sesuai parameter."
} else {
    Write-Info "Tidak ada target retry yang WinRM ready untuk remote scan."
}

if (-not $SkipNetworkDevices) {
    $networkRetryRows = @(New-NetworkDeviceRetryList -DeviceListPath $DeviceListPath -OutputPath $NetworkDeviceRetryListPath)
    if ($networkRetryRows.Count -gt 0) {
        Write-Phase "TAHAP 6/6A - Retry printer IP/NAS yang gagal"
        & $networkDeviceScript `
            -DeviceListPath $NetworkDeviceRetryListPath `
            -Token $Token `
            -Factory $Factory `
            -Department $Department `
            -ServerUrl $ServerUrl `
            -ResultPath $NetworkDeviceResultPath
    } else {
        Write-Info "Tidak ada printer IP/NAS gagal untuk retry."
    }
} else {
    Write-Info "Sync printer IP/NAS dilewati sesuai parameter."
}

if (-not $SkipLocalPrinters -and $readyTargets.Count -gt 0) {
    Write-Phase "TAHAP 6/6B - Retry printer local/USB host yang sudah ready"
    & $localPrinterScript `
        -ComputerList $ReadyTargetPath `
        -Token $Token `
        -Credential $Credential `
        -Factory $Factory `
        -Department $Department `
        -ServerUrl $ServerUrl `
        -MaxParallel $LocalPrinterMaxParallel `
        -TargetTimeoutSeconds $LocalPrinterTargetTimeoutSeconds `
        -ResultPath $LocalPrinterResultPath
} elseif ($SkipLocalPrinters) {
    Write-Info "Sync printer local/USB dilewati sesuai parameter."
} else {
    Write-Info "Tidak ada target retry yang WinRM ready untuk sync printer local/USB."
}

$summary = @(
    [pscustomobject]@{ item = "retry_targets"; value = $retryTargets.Count; detail = $RetryTargetPath },
    [pscustomobject]@{ item = "retry_ready"; value = $readyTargets.Count; detail = $ReadyTargetPath },
    [pscustomobject]@{ item = "retry_blocked"; value = $blockedTargets.Count; detail = $BlockedTargetPath },
    [pscustomobject]@{ item = "bootstrap"; value = ""; detail = Get-StatusCounts -Path $BootstrapResultPath },
    [pscustomobject]@{ item = "anydesk_collect"; value = ""; detail = Get-StatusCounts -Path $AnyDeskResultPath },
    [pscustomobject]@{ item = "remote_scan"; value = ""; detail = Get-StatusCounts -Path $RemoteScanResultPath },
    [pscustomobject]@{ item = "network_devices"; value = ""; detail = Get-StatusCounts -Path $NetworkDeviceResultPath },
    [pscustomobject]@{ item = "local_printers"; value = ""; detail = Get-StatusCounts -Path $LocalPrinterResultPath }
)

$summary | Export-Csv -LiteralPath (Resolve-ZinusPath -Path $SummaryPath) -NoTypeInformation -Encoding UTF8

Write-Phase "SUMMARY"
foreach ($row in $summary) {
    Write-Host ("{0,-20} {1,-8} {2}" -f $row.item, $row.value, $row.detail)
}

Write-Host ""
Write-Host "Retry failed automation selesai. Summary: $(Resolve-ZinusPath -Path $SummaryPath)" -ForegroundColor Green
