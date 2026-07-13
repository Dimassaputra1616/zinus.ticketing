param(
    [string]$ScanPath = ".\zinus-auto-scan-results.csv",
    [string]$ResultPath = ".\zinus-auto-data-quality-issues.csv"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $ScanPath)) {
    throw "Scan result tidak ditemukan: $ScanPath"
}

$issues = @()
$scan = @(Import-Csv -LiteralPath $ScanPath)

foreach ($row in $scan) {
    if ($row.status -ne "success") {
        $issues += [pscustomobject]@{
            ip_address      = $row.computer
            hostname        = $row.hostname
            issue_type      = "asset_scan_failed"
            severity        = "high"
            details         = $row.message
            asset_code      = $row.asset_code
            category        = $row.category
            brand           = $row.brand
            model           = $row.model
            serial_number   = $row.serial_number
            identity_source = $row.identity_source
            monitor_count   = $row.monitor_count
            anydesk_id      = $row.anydesk_id
        }
        continue
    }

    if ($row.identity_source -eq "uuid") {
        $issues += [pscustomobject]@{
            ip_address      = $row.computer
            hostname        = $row.hostname
            issue_type      = "serial_missing_uuid_fallback"
            severity        = "medium"
            details         = "Serial BIOS/baseboard tidak kebaca valid; asset_code fallback ke UUID."
            asset_code      = $row.asset_code
            category        = $row.category
            brand           = $row.brand
            model           = $row.model
            serial_number   = $row.serial_number
            identity_source = $row.identity_source
            monitor_count   = $row.monitor_count
            anydesk_id      = $row.anydesk_id
        }
    }

    $monitorCount = 0
    if ($row.monitor_count -match '^\d+$') {
        $monitorCount = [int]$row.monitor_count
    }

    if ($monitorCount -eq 0) {
        $issues += [pscustomobject]@{
            ip_address      = $row.computer
            hostname        = $row.hostname
            issue_type      = "monitor_not_detected"
            severity        = "medium"
            details         = "Monitor tidak keluar dari WMI/PNP fallback."
            asset_code      = $row.asset_code
            category        = $row.category
            brand           = $row.brand
            model           = $row.model
            serial_number   = $row.serial_number
            identity_source = $row.identity_source
            monitor_count   = $row.monitor_count
            anydesk_id      = $row.anydesk_id
        }
    }

    if (-not $row.anydesk_id) {
        $issues += [pscustomobject]@{
            ip_address      = $row.computer
            hostname        = $row.hostname
            issue_type      = "anydesk_id_missing"
            severity        = "low"
            details         = "AnyDesk terpasang/scan sukses, tapi anydesk_id tidak terbaca dari CLI."
            asset_code      = $row.asset_code
            category        = $row.category
            brand           = $row.brand
            model           = $row.model
            serial_number   = $row.serial_number
            identity_source = $row.identity_source
            monitor_count   = $row.monitor_count
            anydesk_id      = $row.anydesk_id
        }
    }
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path -LiteralPath $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$issues |
    Sort-Object severity, issue_type, ip_address |
    Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8

Write-Host "Data quality issues saved to $resultFullPath" -ForegroundColor Cyan
