param(
    [string]$DiscoveryPath = ".\zinus-auto-discovery.csv",
    [string]$VerificationPath = ".\zinus-auto-verification.csv",
    [string]$BlockedPath = ".\zinus-auto-needs-policy-or-agent.csv",
    [string]$ScanPath = ".\zinus-auto-scan-results.csv",
    [string]$AnyDeskPath = ".\zinus-auto-anydesk-results.csv",
    [string]$ResultPath = ".\zinus-auto-failure-analysis.csv",
    [string]$SummaryPath = ".\zinus-auto-failure-summary.csv"
)

$ErrorActionPreference = "Stop"

function ConvertTo-Boolean {
    param($Value)

    if ($Value -is [bool]) { return $Value }
    return ([string]$Value).Trim() -eq "True"
}

function Import-ZinusCsv {
    param([string]$Path)

    if (Test-Path -LiteralPath $Path) {
        return @(Import-Csv -LiteralPath $Path)
    }

    return @()
}

function Normalize-Message {
    param([string]$Message)

    if ([string]::IsNullOrWhiteSpace($Message)) { return "" }
    return (($Message -replace '[\r\n\t]+', ' ') -replace '\s{2,}', ' ').Trim()
}

function Get-OpenPorts {
    param([string]$Detection)

    if ([string]::IsNullOrWhiteSpace($Detection)) { return "" }
    if ($Detection -match 'TCP:([0-9,]+)') { return $matches[1] }
    return ""
}

function New-RowMap {
    param(
        [object[]]$Rows,
        [string]$KeyName
    )

    $map = @{}
    foreach ($row in $Rows) {
        if ($row.PSObject.Properties.Name -contains $KeyName) {
            $key = [string]$row.$KeyName
            if (-not [string]::IsNullOrWhiteSpace($key)) {
                $map[$key] = $row
            }
        }
    }

    return $map
}

function Get-BestKnownRow {
    param(
        [string]$IpAddress,
        [hashtable]$VerificationByIp,
        [hashtable]$DiscoveryByIp
    )

    if ($VerificationByIp.ContainsKey($IpAddress)) { return $VerificationByIp[$IpAddress] }
    if ($DiscoveryByIp.ContainsKey($IpAddress)) { return $DiscoveryByIp[$IpAddress] }
    return $null
}

function Resolve-LikelyDeviceType {
    param(
        [string]$IpAddress,
        [string]$Hostname,
        [string]$Detection,
        [bool]$WsManOpen,
        [string]$Reason
    )

    $name = ([string]$Hostname).Trim()
    $text = "$name $Reason"

    if ($name -match '(?i)\b(TRUENAS|NAS|SYNOLOGY|QNAP|STORAGE)\b') {
        return [pscustomobject]@{
            type       = "NAS / storage"
            confidence = "high"
            evidence   = "Hostname mengandung NAS/storage: $name"
        }
    }

    if ($name -match '(?i)^(BRN|BROTHER)|BROTHER|MFC|DCP|HL-|PRINTER|PRINT') {
        return [pscustomobject]@{
            type       = "Printer"
            confidence = "high"
            evidence   = "Hostname terlihat seperti printer/Brother: $name"
        }
    }

    if ($name -match '(?i)^(HP[A-F0-9]{5,}|HPB|HPLASER|HP-|LASERJET|DESKJET|OFFICEJET)') {
        return [pscustomobject]@{
            type       = "Printer"
            confidence = "high"
            evidence   = "Hostname terlihat seperti printer HP: $name"
        }
    }

    if ($WsManOpen -or $name -match '(?i)^(PC|NB|LAPTOP|DESKTOP|WS|WIN|CLIENT)[-_]') {
        return [pscustomobject]@{
            type       = "Windows PC / laptop"
            confidence = $(if ($WsManOpen) { "high" } else { "medium" })
            evidence   = $(if ($WsManOpen) { "WinRM 5985 terbuka" } else { "Hostname terlihat seperti endpoint Windows: $name" })
        }
    }

    if ($Detection -match 'TCP:.*\b(135|139|445)\b') {
        return [pscustomobject]@{
            type       = "Windows/Samba endpoint"
            confidence = "medium"
            evidence   = "Port Windows/Samba terdeteksi: $(Get-OpenPorts -Detection $Detection)"
        }
    }

    if ($IpAddress -match '\.(1|254)$' -and [string]::IsNullOrWhiteSpace($name)) {
        return [pscustomobject]@{
            type       = "Network device / gateway"
            confidence = "medium"
            evidence   = "IP ujung segment ($IpAddress) dan hostname kosong"
        }
    }

    if ($text -match '(?i)SMB port 445 tidak terbuka|WinRM port 5985') {
        return [pscustomobject]@{
            type       = "Unknown online device atau Windows firewalled"
            confidence = "low"
            evidence   = "Online, tapi service remote Windows tidak terbuka"
        }
    }

    return [pscustomobject]@{
        type       = "Unknown"
        confidence = "low"
        evidence   = "Tidak cukup sinyal dari hostname/port"
    }
}

function Resolve-FailureCategory {
    param(
        [string]$Stage,
        [string]$Reason
    )

    if ($Reason -match '(?i)SMB port 445') { return "smb_closed" }
    if ($Reason -match '(?i)Access is denied') { return "access_denied" }
    if ($Reason -match '(?i)Timeout') { return "timeout" }
    if ($Reason -match '(?i)WinRM port 5985|port 5985') { return "winrm_closed_or_unreachable" }
    if ($Reason -match '(?i)Application Control policy|blocked this file') { return "application_control_blocked" }
    if ($Reason -match '(?i)anydesk\.exe tidak ditemukan|wrapper pihak ketiga|Softonic') { return "invalid_anydesk_installer" }
    if ($Reason -match '(?i)shell was not found|remote shell') { return "unstable_winrm_session" }
    if ($Stage -eq "anydesk_deploy") { return "anydesk_deploy_failed" }
    return "other"
}

function Resolve-RecommendedAction {
    param(
        [string]$Category,
        [string]$LikelyType
    )

    switch ($Category) {
        "smb_closed" {
            if ($LikelyType -eq "Printer" -or $LikelyType -eq "NAS / storage" -or $LikelyType -eq "Network device / gateway") {
                return "Jangan pakai WinRM/PsExec. Inventaris lewat SNMP/API atau catat sebagai network device."
            }
            return "Buka SMB 445/admin share sementara atau deploy agent lewat GPO/Intune/PDQ/manual."
        }
        "access_denied" { return "Cek credential local admin/domain admin, LocalAccountTokenFilterPolicy, dan hak start service di target." }
        "timeout" { return "Cek PC online stabil, firewall, VPN/VLAN, dan latency. Scan ulang saat device aktif." }
        "winrm_closed_or_unreachable" { return "Aktifkan WinRM 5985 dan firewall rule, atau install agent lokal." }
        "invalid_anydesk_installer" { return "Ganti anydesk.exe dengan installer resmi AnyDesk, atau jawab N saat tahap AnyDesk." }
        "application_control_blocked" { return "Whitelist installer resmi di App Control/Defender policy, lalu deploy ulang." }
        "unstable_winrm_session" { return "Scan ulang target ini dengan parallel lebih kecil atau cek service WinRM target." }
        "anydesk_deploy_failed" { return "Cek installer AnyDesk, policy security target, dan log deploy AnyDesk." }
        default { return "Cek detail reason dan verifikasi manual device type/akses remote." }
    }
}

$discovery = Import-ZinusCsv -Path $DiscoveryPath
$verification = Import-ZinusCsv -Path $VerificationPath
$blocked = Import-ZinusCsv -Path $BlockedPath
$scan = Import-ZinusCsv -Path $ScanPath
$anyDesk = Import-ZinusCsv -Path $AnyDeskPath

$discoveryByIp = New-RowMap -Rows $discovery -KeyName "ip_address"
$verificationByIp = New-RowMap -Rows $verification -KeyName "ip_address"
$scanByIp = New-RowMap -Rows $scan -KeyName "computer"

$report = @()

foreach ($row in $blocked) {
    $ip = [string]$row.ip_address
    $known = Get-BestKnownRow -IpAddress $ip -VerificationByIp $verificationByIp -DiscoveryByIp $discoveryByIp
    $hostname = if ($row.hostname) { [string]$row.hostname } elseif ($known) { [string]$known.hostname } else { "" }
    $detection = if ($known) { [string]$known.detection } else { "" }
    $wsmanOpen = if ($known) { ConvertTo-Boolean $known.wsman_5985 } else { $false }
    $reason = Normalize-Message $row.reason
    $device = Resolve-LikelyDeviceType -IpAddress $ip -Hostname $hostname -Detection $detection -WsManOpen $wsmanOpen -Reason $reason
    $category = Resolve-FailureCategory -Stage "remote_access" -Reason $reason

    $report += [pscustomobject]@{
        ip_address          = $ip
        hostname            = $hostname
        likely_device_type  = $device.type
        confidence          = $device.confidence
        evidence            = $device.evidence
        failure_stage       = "remote_access"
        failure_category    = $category
        status              = $row.status
        reason              = $reason
        detection           = $detection
        open_ports          = Get-OpenPorts -Detection $detection
        wsman_5985          = $wsmanOpen
        scan_status         = ""
        anydesk_status      = ""
        recommended_action  = Resolve-RecommendedAction -Category $category -LikelyType $device.type
    }
}

foreach ($row in @($scan | Where-Object { $_.status -ne "success" })) {
    $ip = [string]$row.computer
    $known = Get-BestKnownRow -IpAddress $ip -VerificationByIp $verificationByIp -DiscoveryByIp $discoveryByIp
    $hostname = if ($row.hostname) { [string]$row.hostname } elseif ($known) { [string]$known.hostname } else { "" }
    $detection = if ($known) { [string]$known.detection } else { "" }
    $wsmanOpen = if ($known) { ConvertTo-Boolean $known.wsman_5985 } else { $false }
    $reason = Normalize-Message $row.message
    $device = Resolve-LikelyDeviceType -IpAddress $ip -Hostname $hostname -Detection $detection -WsManOpen $wsmanOpen -Reason $reason
    $category = Resolve-FailureCategory -Stage "asset_scan" -Reason $reason

    $report += [pscustomobject]@{
        ip_address          = $ip
        hostname            = $hostname
        likely_device_type  = $device.type
        confidence          = $device.confidence
        evidence            = $device.evidence
        failure_stage       = "asset_scan"
        failure_category    = $category
        status              = $row.status
        reason              = $reason
        detection           = $detection
        open_ports          = Get-OpenPorts -Detection $detection
        wsman_5985          = $wsmanOpen
        scan_status         = $row.status
        anydesk_status      = ""
        recommended_action  = Resolve-RecommendedAction -Category $category -LikelyType $device.type
    }
}

foreach ($row in @($anyDesk | Where-Object { $_.status -ne "success" })) {
    $ip = [string]$row.computer
    $known = Get-BestKnownRow -IpAddress $ip -VerificationByIp $verificationByIp -DiscoveryByIp $discoveryByIp
    $scanRow = if ($scanByIp.ContainsKey($ip)) { $scanByIp[$ip] } else { $null }
    $hostname = if ($scanRow -and $scanRow.hostname) { [string]$scanRow.hostname } elseif ($known) { [string]$known.hostname } else { "" }
    $detection = if ($known) { [string]$known.detection } else { "" }
    $wsmanOpen = if ($known) { ConvertTo-Boolean $known.wsman_5985 } else { $false }
    $reason = Normalize-Message $row.message
    $device = Resolve-LikelyDeviceType -IpAddress $ip -Hostname $hostname -Detection $detection -WsManOpen $wsmanOpen -Reason $reason
    $category = Resolve-FailureCategory -Stage "anydesk_deploy" -Reason $reason

    $report += [pscustomobject]@{
        ip_address          = $ip
        hostname            = $hostname
        likely_device_type  = $device.type
        confidence          = $device.confidence
        evidence            = $device.evidence
        failure_stage       = "anydesk_deploy"
        failure_category    = $category
        status              = $row.status
        reason              = $reason
        detection           = $detection
        open_ports          = Get-OpenPorts -Detection $detection
        wsman_5985          = $wsmanOpen
        scan_status         = $(if ($scanRow) { $scanRow.status } else { "" })
        anydesk_status      = $row.status
        recommended_action  = Resolve-RecommendedAction -Category $category -LikelyType $device.type
    }
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path -LiteralPath $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$report |
    Sort-Object ip_address, failure_stage |
    Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8

$summaryFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($SummaryPath)
$summaryDirectory = Split-Path -Parent $summaryFullPath
if ($summaryDirectory -and -not (Test-Path -LiteralPath $summaryDirectory)) {
    New-Item -ItemType Directory -Path $summaryDirectory -Force | Out-Null
}

$summary = @()
$summary += $report |
    Group-Object failure_stage, failure_category |
    Sort-Object Count -Descending |
    ForEach-Object {
        [pscustomobject]@{
            group_type = "failure_category"
            name       = $_.Name
            count      = $_.Count
        }
    }

$summary += $report |
    Group-Object likely_device_type, confidence |
    Sort-Object Count -Descending |
    ForEach-Object {
        [pscustomobject]@{
            group_type = "likely_device_type"
            name       = $_.Name
            count      = $_.Count
        }
    }

$summary | Export-Csv -Path $summaryFullPath -NoTypeInformation -Encoding UTF8

Write-Host "Failure analysis saved to $resultFullPath" -ForegroundColor Cyan
Write-Host "Failure summary saved to $summaryFullPath" -ForegroundColor Cyan
