param(
    [string]$DeviceListPath = ".\zinus-remediation-non-windows-devices.csv",
    [string]$DiscoveryPath = ".\zinus-auto-discovery.csv",
    [string]$Token = "",
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$AgentVersion = "1.1.0-network-device-sync",
    [string]$ResultPath = ".\zinus-network-device-sync-results.csv",
    [string]$SnmpCommunity = "public",
    [ValidateRange(250, 10000)]
    [int]$SnmpTimeoutMs = 1500,
    [ValidateRange(100, 10000)]
    [int]$ProbeTimeoutMs = 750,
    [switch]$SkipSnmp,
    [switch]$IncludeGateways,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if (-not $DryRun -and [string]::IsNullOrWhiteSpace($Token)) {
    throw "Token wajib diisi kecuali memakai -DryRun."
}

function Add-Bytes {
    param(
        [System.Collections.Generic.List[byte]]$List,
        [byte[]]$Bytes
    )

    foreach ($byte in $Bytes) {
        $List.Add($byte)
    }
}

function Encode-BerLength {
    param([int]$Length)

    if ($Length -lt 128) {
        return [byte[]]@([byte]$Length)
    }

    $bytes = New-Object System.Collections.Generic.List[byte]
    $value = [uint32]$Length
    while ($value -gt 0) {
        $bytes.Insert(0, [byte]($value -band 0xff))
        $value = $value -shr 8
    }

    $result = New-Object System.Collections.Generic.List[byte]
    $result.Add([byte](0x80 -bor $bytes.Count))
    Add-Bytes -List $result -Bytes ([byte[]]$bytes.ToArray())
    return [byte[]]$result.ToArray()
}

function Encode-BerElement {
    param(
        [byte]$Tag,
        [byte[]]$Value
    )

    $bytes = New-Object System.Collections.Generic.List[byte]
    $bytes.Add($Tag)
    Add-Bytes -List $bytes -Bytes (Encode-BerLength -Length $Value.Length)
    Add-Bytes -List $bytes -Bytes $Value
    return [byte[]]$bytes.ToArray()
}

function Encode-BerInteger {
    param([int]$Value)

    $bytes = New-Object System.Collections.Generic.List[byte]
    $value32 = [uint32]$Value
    if ($value32 -eq 0) {
        $bytes.Add([byte]0)
    } else {
        while ($value32 -gt 0) {
            $bytes.Insert(0, [byte]($value32 -band 0xff))
            $value32 = $value32 -shr 8
        }

        if (($bytes[0] -band 0x80) -ne 0) {
            $bytes.Insert(0, [byte]0)
        }
    }

    return Encode-BerElement -Tag 0x02 -Value ([byte[]]$bytes.ToArray())
}

function Encode-BerOctetString {
    param([string]$Value)
    return Encode-BerElement -Tag 0x04 -Value ([Text.Encoding]::ASCII.GetBytes($Value))
}

function Encode-BerNull {
    return Encode-BerElement -Tag 0x05 -Value ([byte[]]@())
}

function Encode-BerOid {
    param([string]$Oid)

    $parts = @($Oid.Split(".") | Where-Object { $_ -ne "" } | ForEach-Object { [int]$_ })
    if ($parts.Count -lt 2) {
        throw "OID tidak valid: $Oid"
    }

    $valueBytes = New-Object System.Collections.Generic.List[byte]
    $valueBytes.Add([byte](($parts[0] * 40) + $parts[1]))

    foreach ($part in @($parts | Select-Object -Skip 2)) {
        $chunks = New-Object System.Collections.Generic.List[byte]
        $value = [uint32]$part
        do {
            $chunks.Insert(0, [byte]($value -band 0x7f))
            $value = $value -shr 7
        } while ($value -gt 0)

        for ($i = 0; $i -lt ($chunks.Count - 1); $i++) {
            $chunks[$i] = [byte]($chunks[$i] -bor 0x80)
        }

        Add-Bytes -List $valueBytes -Bytes ([byte[]]$chunks.ToArray())
    }

    return Encode-BerElement -Tag 0x06 -Value ([byte[]]$valueBytes.ToArray())
}

function Read-BerElement {
    param(
        [byte[]]$Data,
        [ref]$Offset
    )

    if ($Offset.Value -ge $Data.Length) {
        throw "BER offset melewati panjang data."
    }

    $start = $Offset.Value
    $tag = $Data[$Offset.Value]
    $Offset.Value++

    $lengthByte = $Data[$Offset.Value]
    $Offset.Value++

    if (($lengthByte -band 0x80) -eq 0) {
        $length = [int]$lengthByte
    } else {
        $lengthBytes = $lengthByte -band 0x7f
        $length = 0
        for ($i = 0; $i -lt $lengthBytes; $i++) {
            $length = ($length -shl 8) + $Data[$Offset.Value]
            $Offset.Value++
        }
    }

    $valueStart = $Offset.Value
    $Offset.Value += $length
    if ($Offset.Value -gt $Data.Length) {
        throw "BER length melewati panjang data."
    }

    $valueBytes = if ($length -gt 0) {
        [byte[]]$Data[$valueStart..($Offset.Value - 1)]
    } else {
        [byte[]]@()
    }

    return [pscustomobject]@{
        Tag        = $tag
        Length     = $length
        Start      = $start
        ValueStart = $valueStart
        End        = $Offset.Value
        Value      = $valueBytes
    }
}

function Get-BerChildren {
    param(
        [byte[]]$Data,
        [object]$Element
    )

    $children = @()
    $offset = $Element.ValueStart
    while ($offset -lt $Element.End) {
        $refOffset = [ref]$offset
        $children += Read-BerElement -Data $Data -Offset $refOffset
        $offset = $refOffset.Value
    }

    return $children
}

function Convert-BerValueToString {
    param([object]$Element)

    switch ($Element.Tag) {
        0x02 {
            $value = 0
            foreach ($byte in $Element.Value) {
                $value = ($value -shl 8) + $byte
            }
            return [string]$value
        }
        0x04 {
            return ([Text.Encoding]::ASCII.GetString($Element.Value)).Trim([char]0, " ")
        }
        0x05 {
            return ""
        }
        0x06 {
            if ($Element.Value.Length -eq 0) { return "" }
            $first = [int]$Element.Value[0]
            $parts = New-Object System.Collections.Generic.List[string]
            $parts.Add([string][math]::Floor($first / 40))
            $parts.Add([string]($first % 40))
            $value = 0
            for ($i = 1; $i -lt $Element.Value.Length; $i++) {
                $value = ($value -shl 7) + ($Element.Value[$i] -band 0x7f)
                if (($Element.Value[$i] -band 0x80) -eq 0) {
                    $parts.Add([string]$value)
                    $value = 0
                }
            }
            return ($parts -join ".")
        }
        0x40 {
            return ($Element.Value | ForEach-Object { [string]$_ }) -join "."
        }
        default {
            if ($Element.Value.Length -eq 0) { return "" }
            return ([BitConverter]::ToString($Element.Value))
        }
    }
}

function Invoke-SnmpGet {
    param(
        [string]$IpAddress,
        [string]$Community,
        [string]$Oid,
        [int]$TimeoutMs
    )

    $requestId = Get-Random -Minimum 1 -Maximum 2147483647
    $varbindValue = New-Object System.Collections.Generic.List[byte]
    Add-Bytes -List $varbindValue -Bytes (Encode-BerOid -Oid $Oid)
    Add-Bytes -List $varbindValue -Bytes (Encode-BerNull)
    $varbind = Encode-BerElement -Tag 0x30 -Value ([byte[]]$varbindValue.ToArray())
    $varbindList = Encode-BerElement -Tag 0x30 -Value $varbind

    $pduValue = New-Object System.Collections.Generic.List[byte]
    Add-Bytes -List $pduValue -Bytes (Encode-BerInteger -Value $requestId)
    Add-Bytes -List $pduValue -Bytes (Encode-BerInteger -Value 0)
    Add-Bytes -List $pduValue -Bytes (Encode-BerInteger -Value 0)
    Add-Bytes -List $pduValue -Bytes $varbindList
    $pdu = Encode-BerElement -Tag 0xa0 -Value ([byte[]]$pduValue.ToArray())

    $messageValue = New-Object System.Collections.Generic.List[byte]
    Add-Bytes -List $messageValue -Bytes (Encode-BerInteger -Value 0)
    Add-Bytes -List $messageValue -Bytes (Encode-BerOctetString -Value $Community)
    Add-Bytes -List $messageValue -Bytes $pdu
    $message = Encode-BerElement -Tag 0x30 -Value ([byte[]]$messageValue.ToArray())

    $client = New-Object System.Net.Sockets.UdpClient
    try {
        $client.Client.ReceiveTimeout = $TimeoutMs
        $null = $client.Send($message, $message.Length, $IpAddress, 161)
        $remote = New-Object System.Net.IPEndPoint ([System.Net.IPAddress]::Any), 0
        $responseBytes = $client.Receive([ref]$remote)

        $offset = 0
        $root = Read-BerElement -Data $responseBytes -Offset ([ref]$offset)
        $rootChildren = @(Get-BerChildren -Data $responseBytes -Element $root)
        if ($rootChildren.Count -lt 3) { return $null }

        $responsePdu = $rootChildren[2]
        $pduChildren = @(Get-BerChildren -Data $responseBytes -Element $responsePdu)
        if ($pduChildren.Count -lt 4) { return $null }

        $errorStatus = [int](Convert-BerValueToString -Element $pduChildren[1])
        if ($errorStatus -ne 0) { return $null }

        $varbindListElement = $pduChildren[3]
        $varbinds = @(Get-BerChildren -Data $responseBytes -Element $varbindListElement)
        if ($varbinds.Count -lt 1) { return $null }

        $items = @(Get-BerChildren -Data $responseBytes -Element $varbinds[0])
        if ($items.Count -lt 2) { return $null }

        return Convert-BerValueToString -Element $items[1]
    } catch {
        return $null
    } finally {
        $client.Close()
    }
}

function Get-SnmpProfile {
    param(
        [string]$IpAddress,
        [string]$Community,
        [int]$TimeoutMs
    )

    $oids = [ordered]@{
        sysDescr      = "1.3.6.1.2.1.1.1.0"
        sysObjectId   = "1.3.6.1.2.1.1.2.0"
        sysName       = "1.3.6.1.2.1.1.5.0"
        printerName   = "1.3.6.1.2.1.43.5.1.1.16.1"
        printerSerial = "1.3.6.1.2.1.43.5.1.1.17.1"
    }

    $profile = [ordered]@{}
    foreach ($name in $oids.Keys) {
        $profile[$name] = Invoke-SnmpGet -IpAddress $IpAddress -Community $Community -Oid $oids[$name] -TimeoutMs $TimeoutMs
    }

    return [pscustomobject]$profile
}

function Normalize-AssetValue {
    param([string]$Value)

    if ([string]::IsNullOrWhiteSpace($Value)) { return "" }

    $text = $Value.Trim()
    $badValues = @(
        "unknown",
        "none",
        "null",
        "not specified",
        "not available",
        "to be filled by o.e.m.",
        "system serial number",
        "serial number"
    )

    if ($badValues -contains $text.ToLowerInvariant()) {
        return ""
    }

    return $text
}

function Get-NetworkCategory {
    param(
        [string]$LikelyType,
        [string]$Hostname = "",
        [string]$Evidence = ""
    )

    $text = @($LikelyType, $Hostname, $Evidence) -join " "

    if ($text -match "(?i)\b(CCTV|CAMERA|IPCAM|IP-CAM|NVR|DVR|HIKVISION|DAHUA|UNIVIEW|ONVIF|RTSP)\b") {
        return "CCTV"
    }

    switch -Regex ($LikelyType) {
        "Printer" { return "Printer" }
        "NAS|storage" { return "NAS" }
        "Network|gateway|router|switch|access" { return "Network Device" }
        default { return "Network Device" }
    }
}

function Get-BrandFromText {
    param([string]$Text)

    if ($Text -match "(?i)\bHP\b|Hewlett") { return "HP" }
    if ($Text -match "(?i)Brother|^BRN") { return "Brother" }
    if ($Text -match "(?i)TrueNAS") { return "TrueNAS" }
    if ($Text -match "(?i)Synology") { return "Synology" }
    if ($Text -match "(?i)QNAP") { return "QNAP" }
    if ($Text -match "(?i)Canon") { return "Canon" }
    if ($Text -match "(?i)Epson") { return "Epson" }
    if ($Text -match "(?i)Hikvision|HIK") { return "Hikvision" }
    if ($Text -match "(?i)Dahua") { return "Dahua" }
    if ($Text -match "(?i)Uniview|\bUNV\b") { return "Uniview" }
    if ($Text -match "(?i)MikroTik|RouterOS") { return "MikroTik" }
    if ($Text -match "(?i)Ubiquiti|UniFi") { return "Ubiquiti" }
    if ($Text -match "(?i)TP-?Link") { return "TP-Link" }
    if ($Text -match "(?i)Ruijie") { return "Ruijie" }
    if ($Text -match "(?i)Cisco") { return "Cisco" }
    if ($Text -match "(?i)D-?Link") { return "D-Link" }
    return ""
}

function Get-ModelFromText {
    param(
        [string]$Category,
        [string]$Hostname,
        [string]$Description,
        [string]$PrinterName
    )

    if (-not [string]::IsNullOrWhiteSpace($PrinterName)) {
        return $PrinterName.Trim()
    }

    if ($Description -match "(?i)(HP\s+[A-Za-z0-9][A-Za-z0-9\-\s]+)") {
        return $matches[1].Trim()
    }

    if ($Description -match "(?i)(Brother\s+[A-Za-z0-9][A-Za-z0-9\-\s]+)") {
        return $matches[1].Trim()
    }

    if ($Category -eq "NAS" -and -not [string]::IsNullOrWhiteSpace($Description)) {
        return ($Description -replace "\s+", " ").Trim()
    }

    if ($Category -in @("CCTV", "Network Device") -and -not [string]::IsNullOrWhiteSpace($Description)) {
        return ($Description -replace "\s+", " ").Trim()
    }

    if ($Hostname -match "^(HP|BRN)(.+)$") {
        return $Hostname
    }

    return ""
}

function Test-TcpPort {
    param(
        [string]$IpAddress,
        [int]$Port,
        [int]$TimeoutMs
    )

    $client = New-Object System.Net.Sockets.TcpClient
    $async = $null
    try {
        $async = $client.BeginConnect($IpAddress, $Port, $null, $null)
        if (-not $async.AsyncWaitHandle.WaitOne($TimeoutMs, $false)) {
            return $false
        }

        $client.EndConnect($async)
        return $true
    } catch {
        return $false
    } finally {
        if ($async -and $async.AsyncWaitHandle) {
            $async.AsyncWaitHandle.Close()
        }
        $client.Close()
    }
}

function Test-DeviceReachability {
    param(
        [string]$IpAddress,
        [string]$Category,
        [int]$TimeoutMs
    )

    $watch = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $ping = New-Object System.Net.NetworkInformation.Ping
        $reply = $ping.Send($IpAddress, $TimeoutMs)
        if ($reply.Status -eq [System.Net.NetworkInformation.IPStatus]::Success) {
            return [pscustomobject]@{
                status     = "online"
                latency_ms = [int]$reply.RoundtripTime
                source     = "ping"
                error      = ""
            }
        }
    } catch {}

    $ports = switch ($Category) {
        "Printer" { @(80, 443, 515, 631, 9100) }
        "NAS" { @(80, 443, 22, 445, 5000, 5001) }
        "CCTV" { @(80, 443, 554, 8000, 8080, 8899) }
        default { @(80, 443, 22, 23, 8080, 8443) }
    }

    foreach ($port in $ports) {
        if (Test-TcpPort -IpAddress $IpAddress -Port $port -TimeoutMs $TimeoutMs) {
            $watch.Stop()
            return [pscustomobject]@{
                status     = "online"
                latency_ms = [int][math]::Max(1, $watch.ElapsedMilliseconds)
                source     = "tcp:$port"
                error      = ""
            }
        }
    }

    return [pscustomobject]@{
        status     = "offline"
        latency_ms = $null
        source     = "probe"
        error      = "No ping or management port response"
    }
}

function New-NetworkAssetPayload {
    param(
        [object]$Row,
        [object]$SnmpProfile
    )

    $ip = [string]$Row.ip_address
    $category = Get-NetworkCategory -LikelyType ([string]$Row.likely_device_type) -Hostname ([string]$Row.hostname) -Evidence ([string]$Row.evidence)
    $description = if ($SnmpProfile) { [string]$SnmpProfile.sysDescr } else { "" }
    $snmpName = if ($SnmpProfile) { [string]$SnmpProfile.sysName } else { "" }
    $printerName = if ($SnmpProfile) { [string]$SnmpProfile.printerName } else { "" }
    $hostname = Normalize-AssetValue -Value ([string]$Row.hostname)
    if (-not $hostname) { $hostname = Normalize-AssetValue -Value $snmpName }
    if (-not $hostname) { $hostname = $ip }

    $textForBrand = @($hostname, $description, $printerName, [string]$Row.evidence) -join " "
    $brand = Get-BrandFromText -Text $textForBrand
    $model = Get-ModelFromText -Category $category -Hostname $hostname -Description $description -PrinterName $printerName
    $serial = if ($SnmpProfile) { Normalize-AssetValue -Value ([string]$SnmpProfile.printerSerial) } else { "" }

    $identitySource = if ($serial) { "serial" } elseif ($hostname -and $hostname -ne $ip) { "hostname_ip" } else { "ip_address" }
    $assetPrefix = switch ($category) {
        "Printer" { "PRINTER" }
        "NAS" { "NAS" }
        "CCTV" { "CCTV" }
        default { "NET" }
    }
    $assetCode = if ($serial) { $serial } else { "$assetPrefix-$($ip -replace '\.', '-')" }

    return [ordered]@{
        factory              = $Factory
        department           = $Department
        agent_version        = $AgentVersion
        asset_code           = $assetCode
        hostname             = $hostname
        user_name            = ""
        os_name              = $description
        category             = $category
        brand                = $brand
        model                = $model
        cpu                  = ""
        ram_gb               = $null
        storage_gb           = $null
        storage_detail       = [string]$Row.evidence
        disks                = @()
        monitors             = @()
        serial_number        = $serial
        identity_source      = $identitySource
        is_identity_verified = [bool]($serial)
        ip_address           = $ip
        anydesk_id           = ""
    }
}

function New-NetworkSyncResult {
    param(
        [object]$Payload,
        [string]$Status,
        [string]$Message,
        [string]$SnmpStatus
    )

    [pscustomobject]@{
        ip_address           = $Payload.ip_address
        hostname             = $Payload.hostname
        category             = $Payload.category
        brand                = $Payload.brand
        model                = $Payload.model
        serial_number        = $Payload.serial_number
        asset_code           = $Payload.asset_code
        identity_source      = $Payload.identity_source
        is_identity_verified = $Payload.is_identity_verified
        snmp_status          = $SnmpStatus
        monitoring_status    = $Payload.monitoring_status
        monitoring_latency_ms = $Payload.monitoring_latency_ms
        monitoring_source    = $Payload.monitoring_source
        status               = $Status
        message              = $Message
        synced_at            = (Get-Date).ToString("s")
    }
}

if (-not (Test-Path -LiteralPath $DeviceListPath)) {
    throw "Device list tidak ditemukan: $DeviceListPath"
}

$rows = @(Import-Csv -LiteralPath $DeviceListPath)
if (-not $IncludeGateways) {
    $rows = @($rows | Where-Object { $_.likely_device_type -in @("Printer", "NAS / storage", "CCTV / camera") })
}

if ($rows.Count -eq 0) {
    throw "Tidak ada printer/NAS/CCTV/network device untuk disync dari $DeviceListPath."
}

Write-Host "Network device sync target: $($rows.Count) device" -ForegroundColor Cyan
Write-Host "SNMP community          : $(if ($SkipSnmp) { 'dilewati' } else { $SnmpCommunity })" -ForegroundColor DarkGray
Write-Host "Probe timeout           : $ProbeTimeoutMs ms" -ForegroundColor DarkGray
if ($DryRun) {
    Write-Host "Mode                   : Dry run, tidak kirim ke server" -ForegroundColor Yellow
}

$results = @()
foreach ($row in $rows) {
    $snmpProfile = $null
    $snmpStatus = "skipped"
    if (-not $SkipSnmp) {
        $snmpProfile = Get-SnmpProfile -IpAddress ([string]$row.ip_address) -Community $SnmpCommunity -TimeoutMs $SnmpTimeoutMs
        if ($snmpProfile -and ($snmpProfile.sysDescr -or $snmpProfile.sysName -or $snmpProfile.printerSerial)) {
            $snmpStatus = "ok"
        } else {
            $snmpStatus = "no_response"
        }
    }

    $payload = New-NetworkAssetPayload -Row $row -SnmpProfile $snmpProfile
    $probe = Test-DeviceReachability -IpAddress ([string]$payload.ip_address) -Category ([string]$payload.category) -TimeoutMs $ProbeTimeoutMs
    $checkedAt = (Get-Date).ToString("o")
    if ($snmpStatus -eq "ok") {
        $payload["monitoring_status"] = "online"
        $payload["monitoring_checked_at"] = $checkedAt
        $payload["last_seen_at"] = $checkedAt
        $payload["monitoring_latency_ms"] = $probe.latency_ms
        $payload["monitoring_error"] = ""
        $payload["monitoring_source"] = if ($probe.status -eq "online") { $probe.source } else { "snmp" }
    } else {
        $payload["monitoring_status"] = $probe.status
        $payload["monitoring_checked_at"] = $checkedAt
        if ($probe.status -eq "online") {
            $payload["last_seen_at"] = $checkedAt
        }
        $payload["monitoring_latency_ms"] = $probe.latency_ms
        $payload["monitoring_error"] = $probe.error
        $payload["monitoring_source"] = $probe.source
    }

    try {
        if ($DryRun) {
            $message = ($payload | ConvertTo-Json -Depth 8 -Compress)
            $result = New-NetworkSyncResult -Payload $payload -Status "dry_run" -Message $message -SnmpStatus $snmpStatus
        } else {
            $jsonBody = $payload | ConvertTo-Json -Depth 8
            $response = Invoke-RestMethod -Uri $ServerUrl -Method Post -Headers @{ Authorization = "Bearer $Token" } -Body $jsonBody -ContentType "application/json"
            $message = if ($response.counts) { ($response.counts | ConvertTo-Json -Compress) } else { "Synced" }
            $result = New-NetworkSyncResult -Payload $payload -Status "success" -Message $message -SnmpStatus $snmpStatus
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

        $result = New-NetworkSyncResult -Payload $payload -Status "failed" -Message $message -SnmpStatus $snmpStatus
    }

    $results += $result
    $color = if ($result.status -eq "success") { "Green" } elseif ($result.status -eq "dry_run") { "Yellow" } else { "Red" }
    Write-Host "$($result.ip_address) [$($result.category)] $($result.status.ToUpper()) - $($result.hostname) - $($result.message)" -ForegroundColor $color
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path -LiteralPath $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
Write-Host "Hasil network device sync: $resultFullPath" -ForegroundColor Cyan
