param(
    [string]$FailureAnalysisPath = ".\zinus-auto-failure-analysis.csv",
    [string]$DiscoveryPath = ".\zinus-auto-discovery.csv",
    [string]$VerificationPath = ".\zinus-auto-verification.csv",
    [string]$ResultPath = ".\zinus-auto-missing-hostnames.csv"
)

$ErrorActionPreference = "Stop"

function Import-ZinusCsv {
    param([string]$Path)

    if (Test-Path -LiteralPath $Path) {
        return @(Import-Csv -LiteralPath $Path)
    }

    return @()
}

function New-Map {
    param(
        [object[]]$Rows,
        [string]$KeyName
    )

    $map = @{}
    foreach ($row in $Rows) {
        if ($row.PSObject.Properties.Name -contains $KeyName) {
            $key = [string]$row.$KeyName
            if ($key) { $map[$key] = $row }
        }
    }

    return $map
}

function Get-RowValue {
    param(
        $Row,
        [string]$Name
    )

    if ($null -eq $Row) { return "" }
    if ($Row.PSObject.Properties.Name -contains $Name) {
        return [string]$Row.$Name
    }

    return ""
}

$failure = Import-ZinusCsv -Path $FailureAnalysisPath
$discovery = Import-ZinusCsv -Path $DiscoveryPath
$verification = Import-ZinusCsv -Path $VerificationPath

$discoveryByIp = New-Map -Rows $discovery -KeyName "ip_address"
$verificationByIp = New-Map -Rows $verification -KeyName "ip_address"

$rows = @(
    $failure |
        Where-Object { [string]::IsNullOrWhiteSpace([string]$_.hostname) } |
        Sort-Object ip_address, failure_stage |
        ForEach-Object {
            $ip = [string]$_.ip_address
            $discoveryRow = if ($discoveryByIp.ContainsKey($ip)) { $discoveryByIp[$ip] } else { $null }
            $verificationRow = if ($verificationByIp.ContainsKey($ip)) { $verificationByIp[$ip] } else { $null }
            $detection = Get-RowValue -Row $verificationRow -Name "detection"
            if (-not $detection) { $detection = Get-RowValue -Row $discoveryRow -Name "detection" }
            $mac = Get-RowValue -Row $verificationRow -Name "mac_address"
            if (-not $mac) { $mac = Get-RowValue -Row $discoveryRow -Name "mac_address" }
            $wsman = Get-RowValue -Row $verificationRow -Name "wsman_5985"
            if (-not $wsman) { $wsman = Get-RowValue -Row $discoveryRow -Name "wsman_5985" }

            $recommended = if ($_.failure_category -eq "access_denied") {
                "Fix credential/admin rights first; hostname can be read after WinRM/PsExec succeeds."
            } elseif ($_.failure_category -eq "smb_closed") {
                "No DNS/NetBIOS name found and SMB closed. Check DHCP lease table, switch/ARP table, printer/NAS UI, or deploy local agent."
            } elseif ($_.failure_category -eq "timeout") {
                "Device timed out. Recheck when online, then rerun discovery."
            } else {
                "Verify manually from DHCP/DNS/network inventory."
            }

            [pscustomobject]@{
                ip_address         = $ip
                likely_device_type = $_.likely_device_type
                confidence         = $_.confidence
                failure_stage      = $_.failure_stage
                failure_category   = $_.failure_category
                detection          = $detection
                mac_address        = $mac
                wsman_5985         = $wsman
                reason             = $_.reason
                recommended_action = $recommended
            }
        }
)

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path -LiteralPath $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$rows | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
Write-Host "Missing hostname report saved to $resultFullPath" -ForegroundColor Cyan
