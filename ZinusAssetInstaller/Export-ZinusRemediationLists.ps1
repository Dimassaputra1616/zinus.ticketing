param(
    [string]$FailureAnalysisPath = ".\zinus-auto-failure-analysis.csv",
    [string]$DataQualityPath = ".\zinus-auto-data-quality-issues.csv",
    [string]$OutputDirectory = "."
)

$ErrorActionPreference = "Stop"

function Import-ZinusCsv {
    param([string]$Path)

    if (Test-Path -LiteralPath $Path) {
        return @(Import-Csv -LiteralPath $Path)
    }

    return @()
}

function Normalize-Text {
    param([string]$Value)

    if ([string]::IsNullOrWhiteSpace($Value)) { return "" }
    return (($Value -replace '[\r\n\t]+', ' ') -replace '\s{2,}', ' ').Trim()
}

function Export-List {
    param(
        [object[]]$Rows,
        [string]$Path
    )

    $fullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($Path)
    $directory = Split-Path -Parent $fullPath
    if ($directory -and -not (Test-Path -LiteralPath $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }

    $Rows | Export-Csv -Path $fullPath -NoTypeInformation -Encoding UTF8
    return $fullPath
}

$failure = Import-ZinusCsv -Path $FailureAnalysisPath
$quality = Import-ZinusCsv -Path $DataQualityPath

$windowsRemoteBlocked = @(
    $failure |
        Where-Object {
            $_.failure_stage -eq "remote_access" -and
            $_.likely_device_type -notin @("Printer", "NAS / storage", "Network device / gateway")
        } |
        Sort-Object ip_address |
        ForEach-Object {
            [pscustomobject]@{
                ip_address         = $_.ip_address
                hostname           = $_.hostname
                likely_device_type = $_.likely_device_type
                confidence         = $_.confidence
                issue              = $_.failure_category
                reason             = Normalize-Text $_.reason
                required_action    = if ($_.failure_category -eq "smb_closed") {
                    "Push Enable-ZinusAssetPrereqs.ps1 via GPO/Intune/PDQ/manual, or open SMB 445/admin share temporarily for PsExec bootstrap."
                } elseif ($_.failure_category -eq "access_denied") {
                    "Fix admin credential/local admin rights/UAC remote restriction, then rerun automation."
                } elseif ($_.failure_category -eq "timeout") {
                    "Verify device is online/stable in this VLAN, then rerun automation."
                } else {
                    $_.recommended_action
                }
            }
        }
)

$accessDenied = @(
    $failure |
        Where-Object { $_.failure_category -eq "access_denied" } |
        Sort-Object ip_address, failure_stage |
        ForEach-Object {
            [pscustomobject]@{
                ip_address         = $_.ip_address
                hostname           = $_.hostname
                likely_device_type = $_.likely_device_type
                failure_stage      = $_.failure_stage
                reason             = Normalize-Text $_.reason
                required_action    = "Credential/admin rights rejected. Verify local/domain admin, password, LocalAccountTokenFilterPolicy, and target security policy."
            }
        }
)

$nonWindows = @(
    $failure |
        Where-Object { $_.likely_device_type -in @("Printer", "NAS / storage", "Network device / gateway") } |
        Sort-Object likely_device_type, ip_address |
        ForEach-Object {
            [pscustomobject]@{
                ip_address         = $_.ip_address
                hostname           = $_.hostname
                likely_device_type = $_.likely_device_type
                confidence         = $_.confidence
                evidence           = $_.evidence
                suggested_inventory = if ($_.likely_device_type -eq "Printer") {
                    "Use SNMP/printer page/manual asset entry; WinRM inventory is not applicable."
                } elseif ($_.likely_device_type -eq "NAS / storage") {
                    "Use NAS API/SNMP/manual asset entry; WinRM inventory is not applicable."
                } else {
                    "Verify gateway/switch/firewall role; inventory as network device."
                }
            }
        }
)

$retryComputers = @(
    $failure |
        Where-Object {
            $_.likely_device_type -eq "Windows PC / laptop" -and
            $_.failure_category -notin @("invalid_anydesk_installer")
        } |
        Select-Object -ExpandProperty ip_address -Unique |
        Where-Object { $_ } |
        Sort-Object
)

$monitorManualCheck = @(
    $quality |
        Where-Object { $_.issue_type -eq "monitor_not_detected" } |
        Sort-Object ip_address |
        ForEach-Object {
            [pscustomobject]@{
                ip_address      = $_.ip_address
                hostname        = $_.hostname
                asset_code      = $_.asset_code
                brand           = $_.brand
                model           = $_.model
                issue           = $_.issue_type
                required_action = "Check monitor cable/dock/KVM/driver and EDID visibility on target. If WMI and PNP both empty, record monitor manually."
            }
        }
)

$serialManualCheck = @(
    $quality |
        Where-Object { $_.issue_type -eq "serial_missing_uuid_fallback" } |
        Sort-Object ip_address |
        ForEach-Object {
            [pscustomobject]@{
                ip_address      = $_.ip_address
                hostname        = $_.hostname
                asset_code      = $_.asset_code
                brand           = $_.brand
                model           = $_.model
                issue           = $_.issue_type
                required_action = "BIOS/baseboard serial is blank/generic. Keep UUID fallback or fill official asset tag manually."
            }
        }
)

$outputs = @()
$outputs += Export-List -Rows $windowsRemoteBlocked -Path (Join-Path $OutputDirectory "zinus-remediation-windows-remote-blocked.csv")
$outputs += Export-List -Rows $accessDenied -Path (Join-Path $OutputDirectory "zinus-remediation-access-denied.csv")
$outputs += Export-List -Rows $nonWindows -Path (Join-Path $OutputDirectory "zinus-remediation-non-windows-devices.csv")
$outputs += Export-List -Rows $monitorManualCheck -Path (Join-Path $OutputDirectory "zinus-remediation-monitor-manual-check.csv")
$outputs += Export-List -Rows $serialManualCheck -Path (Join-Path $OutputDirectory "zinus-remediation-serial-manual-check.csv")

$retryPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath((Join-Path $OutputDirectory "zinus-remediation-retry-computers.txt"))
$retryComputers | Set-Content -Path $retryPath -Encoding ASCII
$outputs += $retryPath

Write-Host "Remediation lists saved:" -ForegroundColor Cyan
foreach ($output in $outputs) {
    Write-Host "  $output" -ForegroundColor Cyan
}
