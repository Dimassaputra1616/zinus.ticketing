param(
    [string[]]$ComputerName = @(),
    [string]$ComputerList = "",
    [string]$ScanResultsPath = ".\zinus-auto-scan-results.csv",
    [string]$Token = "",
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$AgentVersion = "1.1.0-local-printer-sync",
    [string]$ResultPath = ".\zinus-local-printer-sync-results.csv",
    [ValidateRange(1, 20)]
    [int]$MaxParallel = 8,
    [ValidateRange(30, 1800)]
    [int]$TargetTimeoutSeconds = 180,
    [switch]$IncludeNetworkPrinters,
    [switch]$IncludeOtherLocalPorts,
    [switch]$DryRun,
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

if (-not $DryRun -and [string]::IsNullOrWhiteSpace($Token)) {
    throw "Token wajib diisi kecuali memakai -DryRun."
}

function Resolve-PrinterHostTargets {
    $targets = @()

    if ($ComputerName) {
        $targets += $ComputerName
    }

    if ($ComputerList) {
        if (-not (Test-Path -LiteralPath $ComputerList)) {
            throw "Computer list tidak ditemukan: $ComputerList"
        }

        $targets += Get-Content -LiteralPath $ComputerList |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    }

    if ($targets.Count -eq 0 -and (Test-Path -LiteralPath $ScanResultsPath)) {
        $targets += Import-Csv -LiteralPath $ScanResultsPath |
            Where-Object { $_.status -eq "success" -and $_.computer } |
            Select-Object -ExpandProperty computer
    }

    return @($targets | Where-Object { $_ } | Sort-Object -Unique)
}

function New-SyncResult {
    param(
        [string]$Computer,
        [string]$PrinterName = "",
        [string]$PortName = "",
        [string]$Status,
        [string]$Message,
        [object]$Payload = $null
    )

    [pscustomobject]@{
        computer             = $Computer
        printer_name         = $PrinterName
        port_name            = $PortName
        status               = $Status
        message              = $Message
        asset_code           = if ($Payload) { [string]$Payload.asset_code } else { "" }
        hostname             = if ($Payload) { [string]$Payload.hostname } else { "" }
        category             = if ($Payload) { [string]$Payload.category } else { "" }
        brand                = if ($Payload) { [string]$Payload.brand } else { "" }
        model                = if ($Payload) { [string]$Payload.model } else { "" }
        serial_number        = if ($Payload) { [string]$Payload.serial_number } else { "" }
        identity_source      = if ($Payload) { [string]$Payload.identity_source } else { "" }
        is_identity_verified = if ($Payload) { [string]$Payload.is_identity_verified } else { "" }
        synced_at            = (Get-Date).ToString("s")
    }
}

$collectorScript = {
    param(
        [string]$Factory,
        [string]$Department,
        [string]$AgentVersion,
        [bool]$IncludeNetworkPrinters,
        [bool]$IncludeOtherLocalPorts
    )

    $ErrorActionPreference = "Stop"

    function Get-StableHash {
        param([string]$Value)

        $sha = [System.Security.Cryptography.SHA256]::Create()
        try {
            $bytes = [Text.Encoding]::UTF8.GetBytes([string]$Value)
            return (($sha.ComputeHash($bytes) | ForEach-Object { $_.ToString("x2") }) -join "").Substring(0, 16)
        } finally {
            $sha.Dispose()
        }
    }

    function Normalize-Text {
        param([string]$Value)

        if ([string]::IsNullOrWhiteSpace($Value)) { return "" }
        return ($Value -replace "\s+", " ").Trim()
    }

    function New-Slug {
        param([string]$Value)

        $slug = (Normalize-Text -Value $Value) -replace "[^A-Za-z0-9._-]+", "-"
        $slug = $slug.Trim("-")
        if ($slug.Length -gt 60) { $slug = $slug.Substring(0, 60).Trim("-") }
        return $slug
    }

    function Limit-Text {
        param(
            [string]$Value,
            [int]$MaxLength = 255
        )

        if ([string]::IsNullOrWhiteSpace($Value)) { return "" }
        $text = (Normalize-Text -Value $Value)
        if ($text.Length -le $MaxLength) { return $text }
        return $text.Substring(0, $MaxLength)
    }

    function Test-IsVirtualPrinter {
        param(
            [string]$Name,
            [string]$DriverName,
            [string]$PortName
        )

        $text = @($Name, $DriverName, $PortName) -join " "
        return ($text -match "(?i)\bPDF\b|XPS|OneNote|Fax|PDFCreator|Adobe PDF|CutePDF|doPDF|novaPDF|Nitro PDF|PDFsam|PDF-XChange|Foxit|Evernote|AnyDesk Printer|RustDesk Printer|Remote Desktop|TSPrint|Redirected|Send To OneNote|Document Writer|Virtual Eps Plotter|ZWCAD|FILE:|PORTPROMPT|nul:")
    }

    function Get-PrinterPortKind {
        param(
            [string]$PortName,
            [bool]$Network
        )

        if ($Network -or $PortName -match "^\\\\") { return "NetworkShare" }
        if ($PortName -match "^(IP_|IP6_|WSD-|WSD|http|https|ipp|socket|lpr|EP_)" -or $PortName -match "\d{1,3}(\.\d{1,3}){3}") { return "NetworkPort" }
        if ($PortName -match "^(USB|DOT4|LPT|COM)") { return "LocalPort" }
        return "OtherLocal"
    }

    function Get-PrinterBrand {
        param([string]$Text)

        if ($Text -match "(?i)\bHP\b|Hewlett") { return "HP" }
        if ($Text -match "(?i)Brother|^BRN") { return "Brother" }
        if ($Text -match "(?i)Canon") { return "Canon" }
        if ($Text -match "(?i)Epson") { return "Epson" }
        if ($Text -match "(?i)Zebra") { return "Zebra" }
        if ($Text -match "(?i)TSC") { return "TSC" }
        if ($Text -match "(?i)Ricoh") { return "Ricoh" }
        if ($Text -match "(?i)Kyocera") { return "Kyocera" }
        if ($Text -match "(?i)Xerox|Fuji") { return "Xerox" }
        if ($Text -match "(?i)Samsung") { return "Samsung" }
        return ""
    }

    function Get-PrinterModel {
        param(
            [string]$Name,
            [string]$DriverName
        )

        $model = Normalize-Text -Value $DriverName
        if (-not $model) { $model = Normalize-Text -Value $Name }
        $model = $model -replace "(?i)\s+series$", ""
        return $model.Trim()
    }

    function Get-UsbSerialFromPnpId {
        param([string]$PnpDeviceId)

        if ([string]::IsNullOrWhiteSpace($PnpDeviceId)) { return "" }
        $lastPart = ($PnpDeviceId -split "\\")[-1]
        $lastPart = ($lastPart -replace "[^A-Za-z0-9._-]+", "").Trim()
        if ($lastPart.Length -lt 5) { return "" }
        if ($lastPart -match "^(MI_|PRINTENUM|ROOT|VID_|PID_)") { return "" }
        return $lastPart
    }

    function Find-MatchingPnpPrinter {
        param(
            [object]$Printer,
            [object[]]$PnpPrinters
        )

        $printerName = Normalize-Text -Value ([string]$Printer.Name)
        $driverName = Normalize-Text -Value ([string]$Printer.DriverName)

        $matches = @(
            $PnpPrinters | Where-Object {
                $pnpName = Normalize-Text -Value ([string]$_.Name)
                $pnpCaption = Normalize-Text -Value ([string]$_.Caption)
                $pnpId = [string]$_.PNPDeviceID
                (
                    $pnpName -and (
                        $printerName -like "*$pnpName*" -or
                        $pnpName -like "*$printerName*" -or
                        $driverName -like "*$pnpName*"
                    )
                ) -or (
                    $pnpCaption -and (
                        $printerName -like "*$pnpCaption*" -or
                        $pnpCaption -like "*$printerName*" -or
                        $driverName -like "*$pnpCaption*"
                    )
                ) -or (
                    $pnpId -and $Printer.PortName -match "^(USB|DOT4)" -and $pnpId -match "^(USBPRINT|USB\\)"
                )
            }
        )

        return @($matches | Select-Object -First 1)[0]
    }

    $hostName = $env:COMPUTERNAME
    $printers = @()
    try {
        $printers = @(Get-CimInstance Win32_Printer -ErrorAction Stop)
    } catch {
        return [pscustomobject]@{
            record_type = "host_error"
            computer    = $hostName
            message     = $_.Exception.Message
        }
    }

    $pnpPrinters = @()
    try {
        $pnpPrinters = @(
            Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue |
                Where-Object {
                    ([string]$_.PNPClass -match "Printer|PrintQueue") -or
                    ([string]$_.PNPDeviceID -match "^(USBPRINT|USB\\)")
                }
        )
    } catch {
        $pnpPrinters = @()
    }

    $payloads = @()
    foreach ($printer in $printers) {
        $name = Normalize-Text -Value ([string]$printer.Name)
        $driverName = Normalize-Text -Value ([string]$printer.DriverName)
        $portName = Normalize-Text -Value ([string]$printer.PortName)
        $network = [bool]$printer.Network

        if (-not $name -or (Test-IsVirtualPrinter -Name $name -DriverName $driverName -PortName $portName)) {
            continue
        }

        $portKind = Get-PrinterPortKind -PortName $portName -Network $network
        if (-not $IncludeNetworkPrinters -and $portKind -in @("NetworkShare", "NetworkPort")) {
            continue
        }

        if (-not $IncludeOtherLocalPorts -and $portKind -eq "OtherLocal") {
            continue
        }

        $pnp = Find-MatchingPnpPrinter -Printer $printer -PnpPrinters $pnpPrinters
        $pnpId = if ($pnp) { [string]$pnp.PNPDeviceID } else { "" }
        $serial = Get-UsbSerialFromPnpId -PnpDeviceId $pnpId
        $brand = Get-PrinterBrand -Text (@($name, $driverName, $pnp.Name, $pnpId) -join " ")
        $model = Get-PrinterModel -Name $name -DriverName $driverName

        $identitySource = if ($serial) { "usb_serial" } elseif ($pnpId) { "usb_pnp_hash" } else { "host_port_hash" }
        $assetKey = if ($serial) { $serial } elseif ($pnpId) { $pnpId } else { @($hostName, $name, $driverName, $portName) -join "|" }
        $fingerprint = if ($serial) { ($serial -replace "[^A-Za-z0-9._-]+", "-").Trim("-") } else { Get-StableHash -Value $assetKey }
        $assetCode = if ($serial) { $fingerprint } else { "PRINTER-LOCAL-$fingerprint" }
        if ($assetCode.Length -gt 191) { $assetCode = $assetCode.Substring(0, 191) }

        $printerSlug = New-Slug -Value $(if ($model) { $model } else { $name })
        if (-not $printerSlug) { $printerSlug = "Printer" }
        $assetHostname = "$hostName-$printerSlug"
        if ($assetHostname.Length -gt 191) { $assetHostname = $assetHostname.Substring(0, 191).Trim("-") }

        $details = @(
            "host=$hostName",
            "printer=$name",
            "driver=$driverName",
            "port=$portName",
            "port_kind=$portKind",
            "shared=$([bool]$printer.Shared)",
            "default=$([bool]$printer.Default)"
        )
        if ($pnpId) { $details += "pnp=$pnpId" }

        $payloads += [pscustomobject]@{
            record_type          = "printer"
            computer             = $hostName
            printer_name         = $name
            port_name            = $portName
            payload              = [ordered]@{
                factory              = $Factory
                department           = $Department
                agent_version        = $AgentVersion
                asset_code           = $assetCode
                hostname             = $assetHostname
                user_name            = ""
                os_name              = "Local printer hosted by $hostName"
                category             = "Printer"
                brand                = $brand
                model                = $model
                cpu                  = ""
                ram_gb               = $null
                storage_gb           = $null
                storage_detail       = Limit-Text -Value ($details -join "; ") -MaxLength 255
                disks                = @()
                monitors             = @()
                serial_number        = $serial
                identity_source      = $identitySource
                is_identity_verified = [bool]$serial
                ip_address           = ""
                anydesk_id           = ""
            }
        }
    }

    if ($payloads.Count -eq 0) {
        return [pscustomobject]@{
            record_type = "no_printers"
            computer    = $hostName
            message     = "Tidak ada printer local/USB yang cocok."
        }
    }

    return $payloads
}

$targets = @(Resolve-PrinterHostTargets)
if ($targets.Count -eq 0) {
    throw "Tidak ada target. Pakai -ComputerName/-ComputerList atau pastikan $ScanResultsPath ada dan punya status success."
}

Write-Host "Local printer host target: $($targets.Count) PC (parallel: $MaxParallel)" -ForegroundColor Cyan
Write-Host "Include network printers : $([bool]$IncludeNetworkPrinters)" -ForegroundColor DarkGray
Write-Host "Include other local ports: $([bool]$IncludeOtherLocalPorts)" -ForegroundColor DarkGray
Write-Host "Timeout per host         : $TargetTimeoutSeconds detik" -ForegroundColor DarkGray
if ($DryRun) {
    Write-Host "Mode                     : Dry run, tidak kirim ke server" -ForegroundColor Yellow
}

$sessionState = [System.Management.Automation.Runspaces.InitialSessionState]::CreateDefault()
$pool = [System.Management.Automation.Runspaces.RunspaceFactory]::CreateRunspacePool(1, $MaxParallel, $sessionState, $Host)
$pool.Open()

function Start-PrinterWorker {
    param([string]$Target)

    $workerScript = {
        param(
            [string]$Target,
            [System.Management.Automation.PSCredential]$Credential,
            [scriptblock]$CollectorScript,
            [string]$Factory,
            [string]$Department,
            [string]$AgentVersion,
            [bool]$IncludeNetworkPrinters,
            [bool]$IncludeOtherLocalPorts
        )

        $ErrorActionPreference = "Stop"
        $session = $null
        try {
            $sessionOption = New-PSSessionOption `
                -OpenTimeout 15000 `
                -OperationTimeout 90000 `
                -CancelTimeout 5000 `
                -MaxConnectionRetryCount 0 `
                -NoMachineProfile

            $sessionParams = @{
                ComputerName  = $Target
                SessionOption = $sessionOption
                ErrorAction   = "Stop"
            }
            if ($Credential) {
                $sessionParams.Credential = $Credential
            }

            $session = New-PSSession @sessionParams
            $items = Invoke-Command -Session $session -ScriptBlock $CollectorScript -ArgumentList $Factory, $Department, $AgentVersion, $IncludeNetworkPrinters, $IncludeOtherLocalPorts -ErrorAction Stop
            return @($items)
        } catch {
            return [pscustomobject]@{
                record_type = "host_error"
                computer    = $Target
                message     = $_.Exception.Message
            }
        } finally {
            if ($session) {
                Remove-PSSession $session -ErrorAction SilentlyContinue
            }
        }
    }

    $pipeline = [System.Management.Automation.PowerShell]::Create()
    $pipeline.RunspacePool = $pool
    $pipeline.AddScript($workerScript) | Out-Null
    $pipeline.AddArgument($Target) | Out-Null
    $pipeline.AddArgument($Credential) | Out-Null
    $pipeline.AddArgument($collectorScript) | Out-Null
    $pipeline.AddArgument($Factory) | Out-Null
    $pipeline.AddArgument($Department) | Out-Null
    $pipeline.AddArgument($AgentVersion) | Out-Null
    $pipeline.AddArgument([bool]$IncludeNetworkPrinters) | Out-Null
    $pipeline.AddArgument([bool]$IncludeOtherLocalPorts) | Out-Null

    return [pscustomobject]@{
        Target      = $Target
        PowerShell  = $pipeline
        AsyncResult = $pipeline.BeginInvoke()
        StartedAt   = Get-Date
    }
}

$pendingTargets = New-Object System.Collections.Queue
foreach ($target in $targets) {
    $pendingTargets.Enqueue($target)
}

$runspaces = @()
$results = @()
$completedHosts = 0
$printerCount = 0

try {
    while ($runspaces.Count -gt 0 -or $pendingTargets.Count -gt 0) {
        while ($runspaces.Count -lt $MaxParallel -and $pendingTargets.Count -gt 0) {
            $runspaces += Start-PrinterWorker -Target ([string]$pendingTargets.Dequeue())
        }

        $completed = @($runspaces | Where-Object { $_.AsyncResult.IsCompleted })
        foreach ($runspace in $completed) {
            $completedHosts++
            try {
                $items = @($runspace.PowerShell.EndInvoke($runspace.AsyncResult))
            } catch {
                $items = @([pscustomobject]@{
                    record_type = "host_error"
                    computer    = $runspace.Target
                    message     = $_.Exception.Message
                })
            } finally {
                $runspace.PowerShell.Dispose()
            }

            foreach ($item in $items) {
                if ($item.record_type -eq "printer") {
                    $payload = $item.payload
                    try {
                        if ($DryRun) {
                            $message = ($payload | ConvertTo-Json -Depth 8 -Compress)
                            $result = New-SyncResult -Computer $runspace.Target -PrinterName $item.printer_name -PortName $item.port_name -Status "dry_run" -Message $message -Payload $payload
                        } else {
                            $jsonBody = $payload | ConvertTo-Json -Depth 8
                            $response = Invoke-RestMethod -Uri $ServerUrl -Method Post -Headers @{ Authorization = "Bearer $Token" } -Body $jsonBody -ContentType "application/json"
                            $message = if ($response.counts) { ($response.counts | ConvertTo-Json -Compress) } else { "Synced" }
                            $result = New-SyncResult -Computer $runspace.Target -PrinterName $item.printer_name -PortName $item.port_name -Status "success" -Message $message -Payload $payload
                        }
                    } catch {
                        $message = $_.Exception.Message
                        try {
                            if ($_.Exception.Response) {
                                $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                                $body = $reader.ReadToEnd()
                                $reader.Close()
                                if ($body) { $message = "$message | $body" }
                            }
                        } catch {}

                        $result = New-SyncResult -Computer $runspace.Target -PrinterName $item.printer_name -PortName $item.port_name -Status "failed" -Message $message -Payload $payload
                    }

                    $printerCount++
                    $results += $result
                    $color = if ($result.status -eq "success") { "Green" } elseif ($result.status -eq "dry_run") { "Yellow" } else { "Red" }
                    Write-Host "[$completedHosts/$($targets.Count)] $($runspace.Target): $($result.status.ToUpper()) - $($result.printer_name) - $($result.message)" -ForegroundColor $color
                } elseif ($item.record_type -eq "no_printers") {
                    $result = New-SyncResult -Computer $runspace.Target -Status "skipped" -Message $item.message
                    $results += $result
                    Write-Host "[$completedHosts/$($targets.Count)] $($runspace.Target): SKIPPED - $($item.message)" -ForegroundColor DarkGray
                } else {
                    $result = New-SyncResult -Computer $runspace.Target -Status "failed" -Message $item.message
                    $results += $result
                    Write-Host "[$completedHosts/$($targets.Count)] $($runspace.Target): FAILED - $($item.message)" -ForegroundColor Red
                }
            }
        }

        $runspaces = @($runspaces | Where-Object { $completed -notcontains $_ })

        $now = Get-Date
        $timedOut = @($runspaces | Where-Object { ($now - $_.StartedAt).TotalSeconds -ge $TargetTimeoutSeconds })
        foreach ($runspace in $timedOut) {
            $completedHosts++
            try {
                $runspace.PowerShell.Stop()
            } catch {
            } finally {
                $runspace.PowerShell.Dispose()
            }

            $result = New-SyncResult -Computer $runspace.Target -Status "failed" -Message "Timeout setelah $TargetTimeoutSeconds detik. Host dilewati."
            $results += $result
            Write-Host "[$completedHosts/$($targets.Count)] $($runspace.Target): FAILED - $($result.message)" -ForegroundColor Red
        }

        $runspaces = @($runspaces | Where-Object { $timedOut -notcontains $_ })
        if ($runspaces.Count -gt 0 -or $pendingTargets.Count -gt 0) {
            Start-Sleep -Milliseconds 200
        }
    }
} finally {
    $pool.Close()
    $pool.Dispose()
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path -LiteralPath $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
$successCount = @($results | Where-Object { $_.status -eq "success" }).Count
$dryRunCount = @($results | Where-Object { $_.status -eq "dry_run" }).Count
$failedCount = @($results | Where-Object { $_.status -eq "failed" }).Count
Write-Host "Local printer sync selesai. Printer ditemukan: $printerCount, success: $successCount, dry-run: $dryRunCount, failed: $failedCount." -ForegroundColor Cyan
Write-Host "Hasil: $resultFullPath" -ForegroundColor Cyan
