param(
    [string[]]$IpSegment = @("10.62.36", "10.62.38", "10.62.39"),
    [int]$StartHost = 1,
    [int]$EndHost = 254,
    [string]$Token = "",
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$DeviceListPath = ".\zinus-remediation-non-windows-devices.csv",
    [string]$InitialDiscoveryPath = ".\zinus-final-discovery.csv",
    [string]$InitialOnlinePath = ".\zinus-final-discovery-online.csv",
    [string]$FinalVerificationPath = ".\zinus-final-verification.csv",
    [string]$FinalOnlinePath = ".\zinus-final-verification-online.csv",
    [string]$RemoteCandidatePath = ".\zinus-final-remote-candidates.txt",
    [string]$BootstrapTargetPath = ".\zinus-final-force-bootstrap-targets.txt",
    [string]$ReadyTargetPath = ".\zinus-final-ready-targets.txt",
    [string]$BlockedTargetPath = ".\zinus-final-blocked-targets.txt",
    [string]$BootstrapResultPath = ".\zinus-final-bootstrap-results.csv",
    [string]$AnyDeskResultPath = ".\zinus-anydesk-id-results-final.csv",
    [string]$RemoteScanResultPath = ".\zinus-auto-scan-results-final.csv",
    [string]$NetworkDeviceResultPath = ".\zinus-network-device-sync-results-final.csv",
    [string]$LocalPrinterResultPath = ".\zinus-local-printer-sync-results-final.csv",
    [string]$VirtualPrinterCleanupPath = ".\zinus-virtual-printer-cleanup-candidates.csv",
    [string]$PhysicalPrinterKeepPath = ".\zinus-local-printer-physical-keep.csv",
    [string]$SummaryPath = ".\zinus-final-summary.csv",
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
    [switch]$SkipDiscovery,
    [switch]$SkipBootstrap,
    [switch]$SkipAnyDeskCollect,
    [switch]$SkipRemoteScan,
    [switch]$SkipNetworkDevices,
    [switch]$SkipLocalPrinters,
    [System.Management.Automation.PSCredential]$Credential,
    [switch]$UseIntegratedAuth
)

$ErrorActionPreference = "Stop"

Set-Location -LiteralPath $PSScriptRoot

function Normalize-IpSegments {
    param([string[]]$Segments)

    return @(
        foreach ($segment in @($Segments)) {
            ([string]$segment).Split(",") |
                ForEach-Object { $_.Trim() } |
                Where-Object { $_ }
        }
    )
}

$IpSegment = @(Normalize-IpSegments -Segments $IpSegment)

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

function Test-TrueValue {
    param([object]$Value)

    if ($Value -is [bool]) { return [bool]$Value }
    return ([string]$Value) -match '^(?i:true|1|yes|y)$'
}

function Import-CsvSafe {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        return @()
    }

    return @(Import-Csv -LiteralPath $Path)
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

function New-StringSet {
    param([string[]]$Values)

    $set = @{}
    foreach ($value in @($Values | Where-Object { $_ })) {
        $set[[string]$value] = $true
    }
    return $set
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
                $_.likely_device_type -in @("Printer", "NAS / storage", "Network device / gateway", "CCTV / camera")
            } |
            Select-Object -ExpandProperty ip_address -Unique
    )
}

function Save-TargetListsFromVerification {
    param(
        [string]$VerificationPath,
        [string]$DeviceListPath,
        [string]$RemoteCandidatePath,
        [string]$BootstrapTargetPath,
        [string]$ReadyTargetPath,
        [string]$BlockedTargetPath
    )

    $rows = @(Import-CsvSafe -Path $VerificationPath)
    $localSet = New-StringSet -Values @(Get-LocalIPv4Address)
    $excludeSet = New-StringSet -Values @(Get-NonWindowsExcludeIps -Path $DeviceListPath)

    $onlineRows = @(
        $rows | Where-Object {
            $_.ip_address -and
            (Test-TrueValue -Value $_.online)
        }
    )

    $candidateRows = @(
        $onlineRows | Where-Object {
            -not $excludeSet.ContainsKey([string]$_.ip_address) -and
            -not $localSet.ContainsKey([string]$_.ip_address)
        }
    )

    $candidateIps = @($candidateRows | Select-Object -ExpandProperty ip_address -Unique | Sort-Object)
    $readyIps = @(
        $candidateRows |
            Where-Object { Test-TrueValue -Value $_.wsman_5985 } |
            Select-Object -ExpandProperty ip_address -Unique |
            Sort-Object
    )
    $blockedIps = @(
        $candidateRows |
            Where-Object { -not (Test-TrueValue -Value $_.wsman_5985) } |
            Select-Object -ExpandProperty ip_address -Unique |
            Sort-Object
    )

    Set-TextList -Path $RemoteCandidatePath -Items $candidateIps | Out-Null
    Set-TextList -Path $BootstrapTargetPath -Items $candidateIps | Out-Null
    Set-TextList -Path $ReadyTargetPath -Items $readyIps | Out-Null
    Set-TextList -Path $BlockedTargetPath -Items $blockedIps | Out-Null

    return [pscustomobject]@{
        Online            = $onlineRows.Count
        RemoteCandidates  = $candidateIps.Count
        Ready             = $readyIps.Count
        Blocked           = $blockedIps.Count
        ExcludedNonWindows = $excludeSet.Count
        ExcludedLocalIps  = $localSet.Count
        CandidateIps      = $candidateIps
        ReadyIps          = $readyIps
        BlockedIps        = $blockedIps
    }
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

    $counts = @(
        $rows |
            Group-Object -Property $StatusColumn |
            Sort-Object Name |
            ForEach-Object { "$($_.Name)=$($_.Count)" }
    )

    return ($counts -join ", ")
}

function Test-IsVirtualPrinterRow {
    param([object]$Row)

    $text = @(
        [string]$Row.printer_name,
        [string]$Row.hostname,
        [string]$Row.model,
        [string]$Row.port_name,
        [string]$Row.message
    ) -join " "

    return ($text -match "(?i)\bPDF\b|XPS|OneNote|Fax|PDFCreator|Adobe PDF|CutePDF|doPDF|novaPDF|Nitro PDF|PDFsam|PDF-XChange|Foxit|Evernote|AnyDesk Printer|RustDesk Printer|Remote Desktop|TSPrint|Redirected|Send To OneNote|Document Writer|Virtual Eps Plotter|ZWCAD|FILE:|PORTPROMPT|nul:")
}

function Export-PrinterCleanupLists {
    param(
        [string[]]$SourcePaths,
        [string]$VirtualPrinterCleanupPath,
        [string]$PhysicalPrinterKeepPath
    )

    $rows = @()
    foreach ($path in @($SourcePaths | Select-Object -Unique)) {
        if (Test-Path -LiteralPath $path) {
            $rows += Import-Csv -LiteralPath $path
        }
    }

    $printerRows = @(
        $rows | Where-Object {
            $_.category -eq "Printer" -and
            $_.asset_code -and
            $_.status -eq "success"
        }
    )

    $virtualRows = @(
        $printerRows |
            Where-Object { Test-IsVirtualPrinterRow -Row $_ } |
            Sort-Object asset_code -Unique
    )
    $physicalRows = @(
        $printerRows |
            Where-Object { -not (Test-IsVirtualPrinterRow -Row $_) } |
            Sort-Object asset_code -Unique
    )

    $virtualRows | Export-Csv -LiteralPath (Resolve-ZinusPath -Path $VirtualPrinterCleanupPath) -NoTypeInformation -Encoding UTF8
    $physicalRows | Export-Csv -LiteralPath (Resolve-ZinusPath -Path $PhysicalPrinterKeepPath) -NoTypeInformation -Encoding UTF8

    return [pscustomobject]@{
        Virtual = $virtualRows.Count
        Physical = $physicalRows.Count
    }
}

function Assert-ScriptExists {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "Script tidak ditemukan: $Path"
    }
}

$discoverScript = Join-Path $PSScriptRoot "Discover-ZinusNetwork.ps1"
$bootstrapScript = Join-Path $PSScriptRoot "Bootstrap-ZinusWinRM.ps1"
$anyDeskScript = Join-Path $PSScriptRoot "Collect-ZinusAnyDeskIds.ps1"
$remoteScanScript = Join-Path $PSScriptRoot "Scan-ZinusAssetsRemote.ps1"
$networkDeviceScript = Join-Path $PSScriptRoot "Sync-ZinusNetworkDevices.ps1"
$localPrinterScript = Join-Path $PSScriptRoot "Sync-ZinusLocalPrinters.ps1"

foreach ($scriptPath in @($discoverScript, $bootstrapScript, $anyDeskScript, $remoteScanScript, $networkDeviceScript, $localPrinterScript)) {
    Assert-ScriptExists -Path $scriptPath
}

if ([string]::IsNullOrWhiteSpace($Token)) {
    $secureToken = Read-Host "Masukkan Asset Sync token" -AsSecureString
    $Token = Convert-SecureStringToPlainText -SecureString $secureToken
}

if ([string]::IsNullOrWhiteSpace($Token)) {
    throw "Token wajib diisi."
}

if (-not $Credential -and -not $UseIntegratedAuth) {
    $Credential = Get-Credential -Message "Credential admin target PC (contoh .\Administrator atau DOMAIN\Administrator)"
}

Write-Host "Zinus final automation" -ForegroundColor Green
Write-Info "Segment: $($IpSegment -join ', ')"
Write-Info "Server : $ServerUrl"
Write-Info "Factory: $Factory"
Write-Info "Dept   : $Department"

if (-not $Credential -and $UseIntegratedAuth) {
    Write-Info "Credential prompt dilewati. Remote scan memakai current/integrated auth dari proses PowerShell."
}

if (-not $Credential -and -not $SkipBootstrap) {
    Write-Info "Bootstrap dilewati karena credential admin tidak dikirim dari web console."
    $SkipBootstrap = $true
}

if (-not $Credential -and -not $SkipAnyDeskCollect) {
    Write-Info "Collect AnyDesk ID dilewati karena credential admin tidak dikirim dari web console."
    $SkipAnyDeskCollect = $true
}

if (-not $SkipDiscovery) {
    Write-Phase "TAHAP 1/7 - Discovery awal + cek WinRM"
    & $discoverScript `
        -IpSegment $IpSegment `
        -StartHost $StartHost `
        -EndHost $EndHost `
        -ProbeWsMan `
        -ResultPath $InitialDiscoveryPath `
        -OnlineResultPath $InitialOnlinePath
} elseif (-not (Test-Path -LiteralPath $InitialDiscoveryPath)) {
    throw "SkipDiscovery dipilih, tapi file discovery awal tidak ada: $InitialDiscoveryPath"
}

$initialTargets = Save-TargetListsFromVerification `
    -VerificationPath $InitialDiscoveryPath `
    -DeviceListPath $DeviceListPath `
    -RemoteCandidatePath $RemoteCandidatePath `
    -BootstrapTargetPath $BootstrapTargetPath `
    -ReadyTargetPath $ReadyTargetPath `
    -BlockedTargetPath $BlockedTargetPath

Write-Info "Online awal          : $($initialTargets.Online)"
Write-Info "Remote candidate     : $($initialTargets.RemoteCandidates)"
Write-Info "WinRM ready awal     : $($initialTargets.Ready)"
Write-Info "WinRM belum ready    : $($initialTargets.Blocked)"
Write-Info "Target bootstrap     : $BootstrapTargetPath"

if (-not $SkipBootstrap -and $initialTargets.RemoteCandidates -gt 0) {
    Write-Phase "TAHAP 2/7 - Bootstrap policy WinRM local admin"
    $bootstrapParams = @{
        ComputerList = $BootstrapTargetPath
        Credential = $Credential
        EnableLocalAccountRemoteAdmin = $true
        ForceBootstrap = $true
        NoFailExit = $true
        ResultPath = $BootstrapResultPath
    }
    & $bootstrapScript @bootstrapParams
} elseif ($SkipBootstrap) {
    Write-Info "Bootstrap dilewati sesuai parameter."
} else {
    Write-Info "Tidak ada remote candidate untuk bootstrap."
}

if (-not $SkipDiscovery) {
    Write-Phase "TAHAP 3/7 - Verifikasi ulang setelah bootstrap"
    & $discoverScript `
        -IpSegment $IpSegment `
        -StartHost $StartHost `
        -EndHost $EndHost `
        -ProbeWsMan `
        -ResultPath $FinalVerificationPath `
        -OnlineResultPath $FinalOnlinePath
} else {
    Copy-Item -LiteralPath (Resolve-ZinusPath -Path $InitialDiscoveryPath) -Destination (Resolve-ZinusPath -Path $FinalVerificationPath) -Force
    if (Test-Path -LiteralPath $InitialOnlinePath) {
        Copy-Item -LiteralPath (Resolve-ZinusPath -Path $InitialOnlinePath) -Destination (Resolve-ZinusPath -Path $FinalOnlinePath) -Force
    }
}

$finalTargets = Save-TargetListsFromVerification `
    -VerificationPath $FinalVerificationPath `
    -DeviceListPath $DeviceListPath `
    -RemoteCandidatePath $RemoteCandidatePath `
    -BootstrapTargetPath $BootstrapTargetPath `
    -ReadyTargetPath $ReadyTargetPath `
    -BlockedTargetPath $BlockedTargetPath

Write-Info "Online final         : $($finalTargets.Online)"
Write-Info "Remote candidate     : $($finalTargets.RemoteCandidates)"
Write-Info "WinRM ready final    : $($finalTargets.Ready)"
Write-Info "Masih blocked        : $($finalTargets.Blocked)"

if (-not $SkipAnyDeskCollect -and $finalTargets.RemoteCandidates -gt 0) {
    Write-Phase "TAHAP 4/7 - Collect AnyDesk ID dari config"
    $anyDeskParams = @{
        ComputerList = $RemoteCandidatePath
        Credential = $Credential
        MaxParallel = $AnyDeskMaxParallel
        TargetTimeoutSeconds = $AnyDeskTargetTimeoutSeconds
        ResultPath = $AnyDeskResultPath
    }
    & $anyDeskScript @anyDeskParams
} elseif ($SkipAnyDeskCollect) {
    Write-Info "Collect AnyDesk ID dilewati sesuai parameter."
} else {
    Write-Info "Tidak ada remote candidate untuk collect AnyDesk."
}

if (-not $SkipRemoteScan -and $finalTargets.Ready -gt 0) {
    Write-Phase "TAHAP 5/7 - Sync PC/laptop + monitor"
    $remoteScanParams = @{
        ComputerList = $ReadyTargetPath
        Token = $Token
        Factory = $Factory
        Department = $Department
        ServerUrl = $ServerUrl
        MaxParallel = $RemoteScanMaxParallel
        NoFailExit = $true
        ResultPath = $RemoteScanResultPath
    }
    if ($Credential) {
        $remoteScanParams.Credential = $Credential
    }
    & $remoteScanScript @remoteScanParams
} elseif ($SkipRemoteScan) {
    Write-Info "Remote scan PC/laptop dilewati sesuai parameter."
} else {
    Write-Info "Tidak ada target WinRM ready untuk remote scan."
}

if (-not $SkipNetworkDevices) {
    if (Test-Path -LiteralPath $DeviceListPath) {
        Write-Phase "TAHAP 6/7 - Sync printer IP + NAS + CCTV + network device"
        & $networkDeviceScript `
            -DeviceListPath $DeviceListPath `
            -Token $Token `
            -Factory $Factory `
            -Department $Department `
            -ServerUrl $ServerUrl `
            -IncludeGateways `
            -ResultPath $NetworkDeviceResultPath
    } else {
        Write-Host "Device list tidak ada, sync printer/NAS/CCTV/network dilewati: $DeviceListPath" -ForegroundColor Yellow
    }
} else {
    Write-Info "Sync printer/NAS/CCTV/network dilewati sesuai parameter."
}

if (-not $SkipLocalPrinters -and $finalTargets.Ready -gt 0) {
    Write-Phase "TAHAP 7/7 - Sync printer local/USB fisik"
    $localPrinterParams = @{
        ComputerList = $ReadyTargetPath
        Token = $Token
        Factory = $Factory
        Department = $Department
        ServerUrl = $ServerUrl
        MaxParallel = $LocalPrinterMaxParallel
        TargetTimeoutSeconds = $LocalPrinterTargetTimeoutSeconds
        ResultPath = $LocalPrinterResultPath
    }
    if ($Credential) {
        $localPrinterParams.Credential = $Credential
    }
    & $localPrinterScript @localPrinterParams
} elseif ($SkipLocalPrinters) {
    Write-Info "Sync printer local/USB dilewati sesuai parameter."
} else {
    Write-Info "Tidak ada target WinRM ready untuk sync printer local/USB."
}

Write-Phase "FINAL - Cleanup list + summary"
$cleanupCounts = Export-PrinterCleanupLists `
    -SourcePaths @($LocalPrinterResultPath, ".\zinus-local-printer-sync-results.csv") `
    -VirtualPrinterCleanupPath $VirtualPrinterCleanupPath `
    -PhysicalPrinterKeepPath $PhysicalPrinterKeepPath

$summary = @(
    [pscustomobject]@{ item = "online_final"; value = $finalTargets.Online; detail = $FinalVerificationPath },
    [pscustomobject]@{ item = "remote_candidates"; value = $finalTargets.RemoteCandidates; detail = $RemoteCandidatePath },
    [pscustomobject]@{ item = "winrm_ready"; value = $finalTargets.Ready; detail = $ReadyTargetPath },
    [pscustomobject]@{ item = "winrm_blocked"; value = $finalTargets.Blocked; detail = $BlockedTargetPath },
    [pscustomobject]@{ item = "bootstrap"; value = ""; detail = Get-StatusCounts -Path $BootstrapResultPath },
    [pscustomobject]@{ item = "anydesk_collect"; value = ""; detail = Get-StatusCounts -Path $AnyDeskResultPath },
    [pscustomobject]@{ item = "remote_scan"; value = ""; detail = Get-StatusCounts -Path $RemoteScanResultPath },
    [pscustomobject]@{ item = "network_devices"; value = ""; detail = Get-StatusCounts -Path $NetworkDeviceResultPath },
    [pscustomobject]@{ item = "local_printers"; value = ""; detail = Get-StatusCounts -Path $LocalPrinterResultPath },
    [pscustomobject]@{ item = "virtual_printer_cleanup"; value = $cleanupCounts.Virtual; detail = $VirtualPrinterCleanupPath },
    [pscustomobject]@{ item = "physical_printer_keep"; value = $cleanupCounts.Physical; detail = $PhysicalPrinterKeepPath }
)

$summary | Export-Csv -LiteralPath (Resolve-ZinusPath -Path $SummaryPath) -NoTypeInformation -Encoding UTF8

foreach ($row in $summary) {
    Write-Host ("{0,-26} {1,-8} {2}" -f $row.item, $row.value, $row.detail)
}

Write-Host ""
Write-Host "Final automation selesai. Summary: $(Resolve-ZinusPath -Path $SummaryPath)" -ForegroundColor Green
Write-Host "Catatan: asset printer virtual yang sudah terlanjur masuk tidak dihapus otomatis karena endpoint delete belum tersedia di repo. Pakai CSV cleanup untuk hapus/import manual." -ForegroundColor Yellow
