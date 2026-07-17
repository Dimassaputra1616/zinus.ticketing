param(
    [string[]]$ComputerName = @(),
    [string]$ComputerList = ".\computers.txt",
    [string[]]$IpSegment = @(),
    [int]$StartHost = 1,
    [int]$EndHost = 254,
    [Parameter(Mandatory = $true)]
    [string]$Token,
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$LookupUrl = "",
    [string]$AgentVersion = "1.1.0-remote-scan",
    [string]$ResultPath = ".\zinus-asset-remote-scan-results.csv",
    [int]$WsManPort = 5985,
    [int]$PortTimeoutMs = 3000,
    [ValidateRange(1, 50)]
    [int]$MaxParallel = 20,
    [ValidateRange(0, 5)]
    [int]$RetryCount = 1,
    [ValidateRange(1, 60)]
    [int]$RetryDelaySeconds = 5,
    [switch]$SkipExisting,
    [switch]$SkipPreflight,
    [switch]$NoFailExit,
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

if ($StartHost -lt 1 -or $StartHost -gt 254 -or $EndHost -lt 1 -or $EndHost -gt 254 -or $StartHost -gt $EndHost) {
    throw "Range host tidak valid. Gunakan StartHost/EndHost antara 1 sampai 254."
}

function Expand-IpSegment {
    param(
        [string]$Segment,
        [int]$StartHost,
        [int]$EndHost
    )

    $value = $Segment.Trim()
    if ($value -eq "") {
        return @()
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})$') {
        $prefix = $matches[1]
        return $StartHost..$EndHost | ForEach-Object { "$prefix.$_" }
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})\.0/24$') {
        $prefix = $matches[1]
        return $StartHost..$EndHost | ForEach-Object { "$prefix.$_" }
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})\.(\d{1,3})-(\d{1,3})$') {
        $prefix = $matches[1]
        $rangeStart = [int]$matches[2]
        $rangeEnd = [int]$matches[3]

        if ($rangeStart -gt $rangeEnd) {
            throw "IP range tidak valid: $Segment"
        }

        return $rangeStart..$rangeEnd | ForEach-Object { "$prefix.$_" }
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$') {
        return @($value)
    }

    throw "Format IP segment tidak didukung: $Segment. Contoh: 10.62.38, 10.62.38.0/24, atau 10.62.38.1-100."
}

function Resolve-TargetComputers {
    $targets = @()

    if ($ComputerName) {
        $targets += $ComputerName
    }

    if ($ComputerList -and (Test-Path $ComputerList)) {
        $targets += Get-Content -Path $ComputerList |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    }

    foreach ($segment in $IpSegment) {
        $targets += Expand-IpSegment -Segment $segment -StartHost $StartHost -EndHost $EndHost
    }

    $targets | Where-Object { $_ } | Sort-Object -Unique
}

function New-ScanResult {
    param(
        [string]$Computer,
        [string]$Status,
        [string]$Message,
        [string]$AssetCode = "",
        [string]$Hostname = "",
        [string]$Category = "",
        [string]$Brand = "",
        [string]$Model = "",
        [string]$SerialNumber = "",
        [string]$IdentitySource = "",
        [string]$IsIdentityVerified = "",
        [int]$MonitorCount = 0,
        [int]$MonitorVerifiedCount = 0,
        [string]$MonitorIdentitySources = "",
        [string]$IpAddress = "",
        [string]$AnyDeskId = "",
        [string]$ExistingAssetId = "",
        [string]$MatchedBy = ""
    )

    [pscustomobject]@{
        computer                 = $Computer
        hostname                 = $Hostname
        asset_code               = $AssetCode
        status                   = $Status
        message                  = $Message
        category                 = $Category
        brand                    = $Brand
        model                    = $Model
        serial_number            = $SerialNumber
        identity_source          = $IdentitySource
        is_identity_verified     = $IsIdentityVerified
        monitor_count            = $MonitorCount
        monitor_verified_count   = $MonitorVerifiedCount
        monitor_identity_sources = $MonitorIdentitySources
        ip_address               = $IpAddress
        anydesk_id               = $AnyDeskId
        existing_asset_id        = $ExistingAssetId
        matched_by               = $MatchedBy
        scanned_at               = (Get-Date).ToString("s")
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

$targets = @(Resolve-TargetComputers)
if ($targets.Count -eq 0) {
    throw "Tidak ada target. Isi computers.txt atau gunakan -ComputerName."
}

$collector = {
    param(
        [string]$Factory,
        [string]$Department,
        [string]$AgentVersion
    )

    $commonPlaceholders = @(
        '(?i)^to be filled by o\.e\.m\.?$',
        '(?i)^default string$',
        '(?i)^system manufacturer$',
        '(?i)^system product name$',
        '(?i)^not applicable$',
        '(?i)^not specified$',
        '(?i)^not available$',
        '(?i)^unknown$',
        '(?i)^none$',
        '(?i)^n/?a$',
        '(?i)^o\.e\.m\.?$',
        '(?i)^oem$'
    )

    $serialPlaceholders = $commonPlaceholders + @(
        '(?i)^system serial number$',
        '(?i)^serial number$',
        '(?i)^123456789$',
        '^(?i)0+$',
        '(?i)^default$',
        '(?i)^not present$',
        '(?i)^00000000-0000-0000-0000-000000000000$',
        '(?i)^ffffffff-ffff-ffff-ffff-ffffffffffff$'
    )

    function Normalize-AssetValue {
        param(
            [string]$Value,
            [string[]]$Placeholders
        )

        if ($null -eq $Value) { return $null }

        $trimmed = $Value.Trim()
        if ($trimmed -eq "") { return $null }

        foreach ($pattern in $Placeholders) {
            if ($trimmed -match $pattern) { return $null }
        }

        return $trimmed
    }

    function Get-PrimaryIpv4 {
        try {
            $routes = @(Get-NetRoute -AddressFamily IPv4 -DestinationPrefix "0.0.0.0/0" -ErrorAction SilentlyContinue |
                Where-Object { $_.InterfaceIndex -and $_.NextHop -and $_.NextHop -ne "0.0.0.0" } |
                Sort-Object RouteMetric, InterfaceMetric)

            foreach ($route in $routes) {
                $adapter = Get-NetAdapter -InterfaceIndex $route.InterfaceIndex -ErrorAction SilentlyContinue
                if ($adapter -and $adapter.Status -ne "Up") { continue }

                $ip = Get-NetIPAddress -InterfaceIndex $route.InterfaceIndex -AddressFamily IPv4 -ErrorAction SilentlyContinue |
                    Where-Object {
                        $_.IPAddress -and
                        $_.IPAddress -notmatch '^169\.254\.' -and
                        $_.IPAddress -notmatch '^127\.'
                    } |
                    Sort-Object PrefixLength -Descending |
                    Select-Object -First 1 -ExpandProperty IPAddress

                if ($ip) { return $ip }
            }
        } catch {}

        try {
            $adapterConfig = Get-CimInstance Win32_NetworkAdapterConfiguration -Filter "IPEnabled = true" -ErrorAction SilentlyContinue |
                Where-Object { $_.DefaultIPGateway -and $_.IPAddress } |
                Select-Object -First 1

            if ($adapterConfig) {
                return @($adapterConfig.IPAddress | Where-Object {
                    $_ -match '^\d{1,3}(\.\d{1,3}){3}$' -and
                    $_ -notmatch '^169\.254\.' -and
                    $_ -notmatch '^127\.'
                })[0]
            }
        } catch {}

        return $null
    }

    function Get-LoggedOnUser {
        try {
            $csUser = Get-CimInstance Win32_ComputerSystem -ErrorAction SilentlyContinue |
                Select-Object -First 1 -ExpandProperty UserName
            $normalized = Normalize-AssetValue -Value $csUser -Placeholders $commonPlaceholders
            if ($normalized) { return $normalized }
        } catch {}

        try {
            $explorerProcesses = @(Get-CimInstance Win32_Process -Filter "Name = 'explorer.exe'" -ErrorAction SilentlyContinue)
            foreach ($process in $explorerProcesses) {
                $owner = Invoke-CimMethod -InputObject $process -MethodName GetOwner -ErrorAction SilentlyContinue
                if ($owner -and $owner.User) {
                    if ($owner.Domain) { return "$($owner.Domain)\$($owner.User)" }
                    return $owner.User
                }
            }
        } catch {}

        return $null
    }

    function Limit-Text {
        param(
            [string]$Value,
            [int]$MaxLength = 255
        )

        if ([string]::IsNullOrWhiteSpace($Value)) { return "" }

        $clean = (($Value -replace '[\r\n\t]+', ' ') -replace '\s{2,}', ' ').Trim()
        if ($clean.Length -le $MaxLength) { return $clean }

        if ($MaxLength -le 3) { return $clean.Substring(0, $MaxLength) }
        return $clean.Substring(0, $MaxLength - 3) + "..."
    }

    function Format-HealthStatus {
        param([object]$Status)

        $text = Normalize-AssetValue -Value ([string]$Status) -Placeholders $commonPlaceholders
        if (-not $text) { return "Unknown" }

        switch -Regex ($text) {
            "(?i)healthy|ok|good|normal" { return "Good" }
            "(?i)warning|degraded|stressed" { return "Warning" }
            "(?i)unhealthy|critical|failed|lost" { return "Critical" }
            default { return $text }
        }
    }

    function Get-StorageReliabilitySummary {
        param([object]$PhysicalDisk)

        try {
            $counter = Get-StorageReliabilityCounter -PhysicalDisk $PhysicalDisk -ErrorAction Stop
        } catch {
            return @()
        }

        if (-not $counter) { return @() }

        $parts = @()
        if ($null -ne $counter.Wear) { $parts += "wear_used=$([int]$counter.Wear)%" }
        if ($null -ne $counter.Temperature) { $parts += "temp=$([int]$counter.Temperature)C" }
        if ($null -ne $counter.PowerOnHours) { $parts += "power_on=$([int]$counter.PowerOnHours)h" }
        if ($null -ne $counter.ReadErrorsTotal -and [int64]$counter.ReadErrorsTotal -gt 0) { $parts += "read_errors=$($counter.ReadErrorsTotal)" }
        if ($null -ne $counter.WriteErrorsTotal -and [int64]$counter.WriteErrorsTotal -gt 0) { $parts += "write_errors=$($counter.WriteErrorsTotal)" }

        return $parts
    }

    function Get-SmartFailurePredictionSummary {
        try {
            $statuses = @(Get-CimInstance -Namespace root\wmi -ClassName MSStorageDriver_FailurePredictStatus -ErrorAction Stop)
        } catch {
            return ""
        }

        if ($statuses.Count -eq 0) { return "" }

        $failed = @($statuses | Where-Object { $_.PredictFailure }).Count
        if ($failed -gt 0) { return "SMART pre-fail=$failed/$($statuses.Count)" }

        return "SMART pre-fail=0/$($statuses.Count)"
    }

    function Get-PhysicalStorageHealthSummary {
        $summaries = @()

        try {
            $physicalDisks = @(Get-PhysicalDisk -ErrorAction Stop)
        } catch {
            $physicalDisks = @()
        }

        foreach ($physicalDisk in $physicalDisks) {
            $name = Normalize-AssetValue -Value ([string]$physicalDisk.FriendlyName) -Placeholders $commonPlaceholders
            $mediaType = Normalize-AssetValue -Value ([string]$physicalDisk.MediaType) -Placeholders $commonPlaceholders
            if (-not $mediaType -or $mediaType -eq "Unspecified") {
                $mediaType = Normalize-AssetValue -Value ([string]$physicalDisk.BusType) -Placeholders $commonPlaceholders
            }
            if (-not $mediaType) { $mediaType = "Disk" }

            $sizeGb = if ($physicalDisk.Size) { [int][math]::Round($physicalDisk.Size / 1GB) } else { $null }
            $health = Format-HealthStatus -Status $physicalDisk.HealthStatus
            $operational = Normalize-AssetValue -Value ((@($physicalDisk.OperationalStatus) -join ",")) -Placeholders $commonPlaceholders

            $parts = @($mediaType)
            if ($name) { $parts += (Limit-Text -Value $name -MaxLength 34) }
            if ($sizeGb) { $parts += "${sizeGb}GB" }
            $parts += "health=$health"
            if ($operational -and $operational -ne "OK") { $parts += "op=$operational" }
            $parts += Get-StorageReliabilitySummary -PhysicalDisk $physicalDisk

            $summaries += (Limit-Text -Value ($parts -join " ") -MaxLength 110)
        }

        $smartSummary = Get-SmartFailurePredictionSummary
        if ($smartSummary) { $summaries += $smartSummary }

        return @($summaries | Where-Object { $_ })
    }

    function Get-DiskInfo {
        $result = @()
        $disks = @(Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3" -ErrorAction SilentlyContinue)

        foreach ($disk in $disks) {
            $sizeGb = if ($disk.Size) { [math]::Round($disk.Size / 1GB, 2) } else { 0 }
            $freeGb = if ($disk.FreeSpace) { [math]::Round($disk.FreeSpace / 1GB, 2) } else { 0 }
            $usedGb = if ($disk.Size -and $disk.FreeSpace) { [math]::Round(($disk.Size - $disk.FreeSpace) / 1GB, 2) } else { 0 }

            $result += [pscustomobject]@{
                drive   = $disk.DeviceID
                size_gb = $sizeGb
                used_gb = $usedGb
                free_gb = $freeGb
            }
        }

        return $result
    }

    function Get-SerialNumber {
        try {
            $serial = Get-CimInstance Win32_BIOS -ErrorAction SilentlyContinue |
                Select-Object -First 1 -ExpandProperty SerialNumber
            $serial = Normalize-AssetValue -Value $serial -Placeholders $serialPlaceholders
            if ($serial) { return $serial }
        } catch {}

        try {
            $serial = Get-CimInstance Win32_ComputerSystemProduct -ErrorAction SilentlyContinue |
                Select-Object -First 1 -ExpandProperty IdentifyingNumber
            $serial = Normalize-AssetValue -Value $serial -Placeholders $serialPlaceholders
            if ($serial) { return $serial }
        } catch {}

        try {
            $serial = Get-CimInstance Win32_BaseBoard -ErrorAction SilentlyContinue |
                Select-Object -First 1 -ExpandProperty SerialNumber
            $serial = Normalize-AssetValue -Value $serial -Placeholders $serialPlaceholders
            if ($serial) { return $serial }
        } catch {}

        return $null
    }

    function Get-SystemUuid {
        try {
            $uuid = Get-CimInstance Win32_ComputerSystemProduct -ErrorAction SilentlyContinue |
                Select-Object -First 1 -ExpandProperty UUID
            return Normalize-AssetValue -Value $uuid -Placeholders $serialPlaceholders
        } catch {
            return $null
        }
    }

    function Get-AssetIdentity {
        param(
            [string]$SerialNumber,
            [string]$Uuid,
            [string]$Hostname
        )

        if (-not [string]::IsNullOrWhiteSpace($SerialNumber)) {
            return [pscustomobject]@{
                asset_code           = $SerialNumber
                identity_source      = "serial"
                is_identity_verified = $true
            }
        }

        if (-not [string]::IsNullOrWhiteSpace($Uuid)) {
            return [pscustomobject]@{
                asset_code           = "UUID-$Uuid"
                identity_source      = "uuid"
                is_identity_verified = $false
            }
        }

        return [pscustomobject]@{
            asset_code           = "HOST-$Hostname"
            identity_source      = "hostname"
            is_identity_verified = $false
        }
    }

    function Get-CategoryFromChassis {
        try {
            $types = Get-CimInstance Win32_SystemEnclosure -ErrorAction SilentlyContinue |
                Select-Object -First 1 -ExpandProperty ChassisTypes
        } catch {
            return $null
        }

        if (-not $types) { return $null }

        $laptopTypes = @(8, 9, 10, 11, 12, 14, 30, 31, 32)
        $desktopTypes = @(3, 4, 5, 6, 7, 13, 15, 16, 17, 18, 23, 24)

        foreach ($type in $types) {
            if ($laptopTypes -contains $type) { return "Laptop" }
            if ($desktopTypes -contains $type) { return "PC" }
        }

        return $null
    }

    function Resolve-AssetCategory {
        param(
            [string]$Hostname,
            [string]$Brand,
            [string]$Model
        )

        if ($Hostname -match '(?i)^(NB|LAPTOP|LT|NOTEBOOK)[-_]') { return "Laptop" }
        if ($Hostname -match '(?i)^(PC|DESKTOP|WS|MP)[-_]') { return "PC" }

        $chassisCategory = Get-CategoryFromChassis
        if ($chassisCategory) { return $chassisCategory }

        $modelText = "$Brand $Model"
        if ($modelText -match '(?i)\b(thinkpad|ideapad|yoga|zenbook|vivobook|aspire|inspiron|latitude|precision|elitebook|probook|notebook|laptop)\b') {
            return "Laptop"
        }

        if ($modelText -match '(?i)\b(nuc|desktop|tower|optiplex|prodesk|elitedesk|h110|h310|h410|h510|h610|b450|b560|b660|a520|a620|prime|ms-7d20|poweredge)\b') {
            return "PC"
        }

        return $null
    }

    function Convert-WmiMonitorString {
        param($Value)

        if ($null -eq $Value) { return $null }

        $chars = @()
        foreach ($code in $Value) {
            if ($null -ne $code -and [int]$code -gt 0) {
                $chars += [char][int]$code
            }
        }

        $text = (-join $chars).Trim()
        if ($text -eq "") { return $null }

        return $text
    }

    function Resolve-MonitorManufacturerName {
        param(
            [string]$Manufacturer,
            [string]$PnpId
        )

        $candidate = $Manufacturer
        if ([string]::IsNullOrWhiteSpace($candidate) -and $PnpId -match '(?i)^DISPLAY\\([A-Z0-9]{3})') {
            $candidate = $matches[1]
        }

        if ([string]::IsNullOrWhiteSpace($candidate)) { return $null }

        $code = $candidate.Trim().ToUpperInvariant()
        if ($code -notmatch '^[A-Z0-9]{3}$') { return $candidate }

        $manufacturerNames = @{
            ACI = "ASUS"
            ACM = "Acer"
            ACR = "Acer"
            AOC = "AOC"
            APP = "Apple"
            AUO = "AU Optronics"
            BNQ = "BenQ"
            BOE = "BOE"
            CMO = "Chi Mei"
            CMN = "Chimei"
            DEL = "Dell"
            EIZ = "EIZO"
            FUJ = "Fujitsu"
            GSM = "LG"
            HSD = "HannStar"
            HWP = "HP"
            HPN = "HP"
            IBM = "IBM"
            IVM = "Iiyama"
            LEN = "Lenovo"
            LGD = "LG Display"
            LPL = "LG Display"
            MEI = "Panasonic"
            MSI = "MSI"
            NEC = "NEC"
            PAN = "Panasonic"
            PHI = "Philips"
            PHL = "Philips"
            SAM = "Samsung"
            SEC = "Samsung"
            SHP = "Sharp"
            SNY = "Sony"
            TOS = "Toshiba"
            TPL = "Top Victory"
            VIZ = "Vizio"
            VSC = "ViewSonic"
        }

        if ($manufacturerNames.ContainsKey($code)) {
            return $manufacturerNames[$code]
        }

        return $candidate
    }

    function Get-StableHash {
        param([string]$Value)

        if ([string]::IsNullOrWhiteSpace($Value)) {
            $Value = [guid]::NewGuid().ToString()
        }

        $sha = [System.Security.Cryptography.SHA256]::Create()
        try {
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($Value)
            $hashBytes = $sha.ComputeHash($bytes)
            return (($hashBytes | ForEach-Object { $_.ToString("x2") }) -join "").Substring(0, 16).ToUpper()
        } finally {
            $sha.Dispose()
        }
    }

    function Get-MonitorConnectionLabel {
        param($Technology)

        if ($null -eq $Technology) { return $null }

        switch ([int64]$Technology) {
            -2 { return "Uninitialized" }
            -1 { return "Other" }
            0 { return "Other" }
            1 { return "VGA" }
            2 { return "S-Video" }
            3 { return "Composite" }
            4 { return "Component" }
            5 { return "DVI" }
            6 { return "HDMI" }
            8 { return "D-JPN" }
            9 { return "SDI" }
            10 { return "DisplayPort" }
            11 { return "Embedded DisplayPort" }
            12 { return "UDI" }
            13 { return "Embedded UDI" }
            14 { return "SDTV Dongle" }
            15 { return "Miracast" }
            16 { return "Indirect Wired" }
            17 { return "Indirect Virtual" }
            2147483648 { return "Internal" }
            default { return "Unknown" }
        }
    }

    function New-MonitorHostname {
        param(
            [string]$ParentHostname,
            [string]$Model,
            [string]$Serial,
            [int]$Index
        )

        $suffix = $Model
        if ([string]::IsNullOrWhiteSpace($suffix)) { $suffix = $Serial }
        if ([string]::IsNullOrWhiteSpace($suffix)) { $suffix = "MON-$Index" }

        $suffix = ($suffix -replace '[^A-Za-z0-9_-]+', '-').Trim('-')
        if ([string]::IsNullOrWhiteSpace($suffix)) { $suffix = "MON-$Index" }

        $hostname = "$ParentHostname-$suffix"
        if ($hostname.Length -gt 191) { $hostname = $hostname.Substring(0, 191) }

        return $hostname
    }

    function Get-ObjectPropertyValue {
        param(
            $Object,
            [string]$Name
        )

        if ($null -eq $Object) { return $null }
        if ($Object.PSObject.Properties.Name -contains $Name) {
            return $Object.$Name
        }

        return $null
    }

    function Get-FallbackConnectedMonitors {
        param(
            [string]$ParentHostname,
            [string]$Category
        )

        if ($Category -and $Category -ne "PC") { return @() }

        $fallbackMonitors = @()
        try {
            $fallbackMonitors = @(
                Get-CimInstance Win32_PnPEntity -Filter "PNPClass = 'Monitor'" -ErrorAction SilentlyContinue |
                    Where-Object {
                        (Get-ObjectPropertyValue -Object $_ -Name "PNPDeviceID") -and
                        (Get-ObjectPropertyValue -Object $_ -Name "Name") -and
                        ((Get-ObjectPropertyValue -Object $_ -Name "Status") -ne "Error")
                    }
            )
        } catch {
            $fallbackMonitors = @()
        }

        if ($fallbackMonitors.Count -eq 0) {
            try {
                $fallbackMonitors = @(
                    Get-CimInstance Win32_DesktopMonitor -ErrorAction SilentlyContinue |
                        Where-Object {
                            (Get-ObjectPropertyValue -Object $_ -Name "PNPDeviceID") -or
                            (Get-ObjectPropertyValue -Object $_ -Name "DeviceID") -or
                            (Get-ObjectPropertyValue -Object $_ -Name "Name")
                        }
                )
            } catch {
                $fallbackMonitors = @()
            }
        }

        $results = @()
        $index = 0
        foreach ($monitor in $fallbackMonitors) {
            $index++
            $pnpId = [string](Get-ObjectPropertyValue -Object $monitor -Name "PNPDeviceID")
            if ([string]::IsNullOrWhiteSpace($pnpId)) {
                $pnpId = [string](Get-ObjectPropertyValue -Object $monitor -Name "DeviceID")
            }

            $name = Normalize-AssetValue -Value ([string](Get-ObjectPropertyValue -Object $monitor -Name "Name")) -Placeholders $commonPlaceholders
            $manufacturer = Normalize-AssetValue -Value ([string](Get-ObjectPropertyValue -Object $monitor -Name "Manufacturer")) -Placeholders $commonPlaceholders
            if (-not $manufacturer) {
                $manufacturer = Normalize-AssetValue -Value ([string](Get-ObjectPropertyValue -Object $monitor -Name "MonitorManufacturer")) -Placeholders $commonPlaceholders
            }
            $manufacturer = Resolve-MonitorManufacturerName -Manufacturer $manufacturer -PnpId $pnpId

            $model = $name
            if ($model -match '(?i)^generic.*monitor$' -and $pnpId -match '^DISPLAY\\([^\\]+)\\') {
                $model = $matches[1]
            }
            if (-not $model) { $model = "Monitor $index" }

            $fingerprint = Get-StableHash -Value (@($pnpId, $name, $manufacturer, $model, $index) -join "|")
            $assetCode = "MON-FB-$fingerprint"
            $monitorHostname = New-MonitorHostname -ParentHostname $ParentHostname -Model $model -Serial "" -Index $index

            $results += [pscustomobject]@{
                asset_code           = $assetCode
                hostname             = $monitorHostname
                name                 = $model
                serial_number        = $null
                manufacturer         = $manufacturer
                brand                = $manufacturer
                model                = $model
                connection           = "Unknown"
                instance_name        = $pnpId
                identity_source      = "pnp_fallback"
                is_identity_verified = $false
                screen_width_cm      = $null
                screen_height_cm     = $null
            }
        }

        return @(
            $results |
                Group-Object -Property asset_code |
                ForEach-Object { $_.Group | Select-Object -First 1 } |
                Select-Object -First 12
        )
    }

    function Get-ConnectedMonitors {
        param(
            [string]$ParentHostname,
            [string]$Category
        )

        $results = @()

        $monitorIds = @()
        for ($attempt = 1; $attempt -le 3; $attempt++) {
            try {
                $monitorIds = @(Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorID -ErrorAction Stop)
            } catch {
                $monitorIds = @()
            }

            if ($monitorIds.Count -gt 0) {
                break
            }

            if ($attempt -lt 3) {
                Start-Sleep -Seconds 2
            }
        }

        if (-not $monitorIds -or $monitorIds.Count -eq 0) { return $results }

        try {
            $connections = @(Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorConnectionParams -ErrorAction SilentlyContinue)
        } catch {
            $connections = @()
        }

        try {
            $displayParams = @(Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorBasicDisplayParams -ErrorAction SilentlyContinue)
        } catch {
            $displayParams = @()
        }

        $connectionByInstance = @{}
        foreach ($connection in $connections) {
            if ($connection.InstanceName) { $connectionByInstance[$connection.InstanceName] = $connection }
        }

        $displayByInstance = @{}
        foreach ($display in $displayParams) {
            if ($display.InstanceName) { $displayByInstance[$display.InstanceName] = $display }
        }

        $activeMonitorCount = @(
            $monitorIds | Where-Object {
                -not ($_.PSObject.Properties.Name -contains "Active") -or [bool]$_.Active
            }
        ).Count
        $useInactiveFallback = $Category -eq "PC" -and $activeMonitorCount -eq 0

        $index = 0
        foreach ($monitor in $monitorIds) {
            $isActive = $true
            if ($monitor.PSObject.Properties.Name -contains "Active") { $isActive = [bool]$monitor.Active }
            if (-not $isActive -and -not $useInactiveFallback) { continue }

            $connectionLabel = $null
            if ($monitor.InstanceName -and $connectionByInstance.ContainsKey($monitor.InstanceName)) {
                $connectionLabel = Get-MonitorConnectionLabel -Technology $connectionByInstance[$monitor.InstanceName].VideoOutputTechnology
            }

            if ($Category -ne "PC" -and (
                    [string]::IsNullOrWhiteSpace([string]$connectionLabel) -or
                    @("Internal", "Embedded DisplayPort", "Embedded UDI", "Unknown", "Uninitialized") -contains $connectionLabel
                )) {
                continue
            }

            $manufacturer = Normalize-AssetValue -Value (Convert-WmiMonitorString $monitor.ManufacturerName) -Placeholders $commonPlaceholders
            $manufacturer = Resolve-MonitorManufacturerName -Manufacturer $manufacturer -PnpId $monitor.InstanceName
            $model = Normalize-AssetValue -Value (Convert-WmiMonitorString $monitor.UserFriendlyName) -Placeholders $commonPlaceholders
            $serial = Normalize-AssetValue -Value (Convert-WmiMonitorString $monitor.SerialNumberID) -Placeholders $serialPlaceholders

            $index++
            $fingerprintSource = @($monitor.InstanceName, $serial, $manufacturer, $model) -join "|"
            $fingerprint = $serial
            $identitySource = "serial"
            $isIdentityVerified = $true

            if ([string]::IsNullOrWhiteSpace($fingerprint)) {
                $fingerprint = Get-StableHash -Value $fingerprintSource
                $identitySource = "wmi_hash"
                $isIdentityVerified = $false
            }

            $fingerprint = ($fingerprint -replace '[^A-Za-z0-9._-]+', '-').Trim('-')
            if ([string]::IsNullOrWhiteSpace($fingerprint)) {
                $fingerprint = Get-StableHash -Value $fingerprintSource
                $identitySource = "wmi_hash"
                $isIdentityVerified = $false
            }

            $assetCode = "MON-$fingerprint"
            if ($assetCode.Length -gt 191) { $assetCode = $assetCode.Substring(0, 191) }

            $monitorHostname = New-MonitorHostname -ParentHostname $ParentHostname -Model $model -Serial $serial -Index $index
            $display = $null
            if ($monitor.InstanceName -and $displayByInstance.ContainsKey($monitor.InstanceName)) {
                $display = $displayByInstance[$monitor.InstanceName]
            }

            $widthCm = $null
            $heightCm = $null
            if ($display) {
                if ($display.MaxHorizontalImageSize -gt 0) { $widthCm = [int]$display.MaxHorizontalImageSize }
                if ($display.MaxVerticalImageSize -gt 0) { $heightCm = [int]$display.MaxVerticalImageSize }
            }

            $displayNameParts = @()
            if ($manufacturer) { $displayNameParts += $manufacturer }
            if ($model) { $displayNameParts += $model }
            if ($serial) { $displayNameParts += "($serial)" }
            $displayName = ($displayNameParts -join " ").Trim()
            if ([string]::IsNullOrWhiteSpace($displayName)) { $displayName = $monitorHostname }

            $results += [pscustomobject]@{
                asset_code           = $assetCode
                hostname             = $monitorHostname
                name                 = $displayName
                serial_number        = $serial
                manufacturer         = $manufacturer
                brand                = $manufacturer
                model                = $model
                connection           = $connectionLabel
                instance_name        = $monitor.InstanceName
                identity_source      = $identitySource
                is_identity_verified = $isIdentityVerified
                screen_width_cm      = $widthCm
                screen_height_cm     = $heightCm
            }
        }

        # WMI can expose duplicate/stale entries for the same physical monitor.
        # The API accepts at most 12 monitors per asset, so de-duplicate by the
        # generated identity and keep the payload within that contract.
        $uniqueResults = @(
            $results |
                Group-Object -Property asset_code |
                ForEach-Object { $_.Group | Select-Object -First 1 }
        )

        if ($uniqueResults.Count -eq 0) {
            $uniqueResults = @(Get-FallbackConnectedMonitors -ParentHostname $ParentHostname -Category $Category)
        }

        return @($uniqueResults | Select-Object -First 12)
    }

    function Get-AnyDeskExecutablePaths {
        $paths = @(
            "$env:ProgramFiles\AnyDesk\AnyDesk.exe",
            "${env:ProgramFiles(x86)}\AnyDesk\AnyDesk.exe",
            "C:\Program Files\AnyDesk\AnyDesk.exe",
            "C:\Program Files (x86)\AnyDesk\AnyDesk.exe"
        )

        foreach ($root in @($env:ProgramFiles, ${env:ProgramFiles(x86)}, "C:\Program Files", "C:\Program Files (x86)")) {
            if ([string]::IsNullOrWhiteSpace($root) -or -not (Test-Path -LiteralPath $root)) {
                continue
            }

            try {
                $paths += Get-ChildItem -LiteralPath $root -Directory -Filter "AnyDesk*" -ErrorAction SilentlyContinue |
                    ForEach-Object {
                        Get-ChildItem -LiteralPath $_.FullName -File -Filter "AnyDesk*.exe" -ErrorAction SilentlyContinue |
                            Select-Object -ExpandProperty FullName
                    }
            } catch {}
        }

        return @($paths | Where-Object { $_ } | Select-Object -Unique)
    }

    function Get-AnyDeskIdFromText {
        param([string]$Text)

        if ([string]::IsNullOrWhiteSpace($Text)) { return $null }

        foreach ($pattern in @(
                '(?im)^\s*ad\.anynet\.id\s*=\s*([0-9 ]{6,})\s*$',
                '(?im)^\s*anynet\.id\s*=\s*([0-9 ]{6,})\s*$',
                '(?im)^\s*id\s*=\s*([0-9 ]{6,})\s*$'
            )) {
            if ($Text -match $pattern) {
                $id = ($matches[1] -replace '\s+', '')
                if ($id -match '^\d{6,12}$') { return $id }
            }
        }

        return $null
    }

    function Get-AnyDeskIdFromConfig {
        foreach ($path in @(
                "C:\ProgramData\AnyDesk\system.conf",
                "C:\ProgramData\AnyDesk\service.conf",
                "C:\ProgramData\AnyDesk\ad_svc.conf"
            )) {
            if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
                continue
            }

            try {
                $id = Get-AnyDeskIdFromText -Text (Get-Content -LiteralPath $path -Raw -ErrorAction Stop)
                if ($id) { return $id }
            } catch {}
        }

        return $null
    }

    function Get-AnyDeskId {
        param(
            [int]$Attempts = 6,
            [int]$DelaySeconds = 5
        )

        $configId = Get-AnyDeskIdFromConfig
        if ($configId) { return $configId }

        foreach ($path in Get-AnyDeskExecutablePaths) {
            if (-not (Test-Path -LiteralPath $path)) {
                continue
            }

            for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
                try {
                    $output = & $path --get-id 2>&1 | Out-String
                    if ($output -match '([0-9]{3}\s?[0-9]{3}\s?[0-9]{3,4})') {
                        return ($matches[1] -replace '\s+', '')
                    }
                } catch {}

                if ($attempt -lt $Attempts) {
                    Start-Sleep -Seconds $DelaySeconds
                }
            }
        }

        return $null
    }

    $hostname = $env:COMPUTERNAME
    $osInfo = Get-CimInstance Win32_OperatingSystem
    $cpuInfo = (Get-CimInstance Win32_Processor | Select-Object -First 1).Name
    $csInfo = Get-CimInstance Win32_ComputerSystem
    $disks = Get-DiskInfo
    $storageHealth = @(Get-PhysicalStorageHealthSummary)
    $serialNumber = Get-SerialNumber
    $systemUuid = Get-SystemUuid
    $identity = Get-AssetIdentity -SerialNumber $serialNumber -Uuid $systemUuid -Hostname $hostname
    $brand = Normalize-AssetValue -Value $csInfo.Manufacturer -Placeholders $commonPlaceholders
    $model = Normalize-AssetValue -Value $csInfo.Model -Placeholders $commonPlaceholders

    try {
        $baseboard = Get-CimInstance Win32_BaseBoard -ErrorAction SilentlyContinue | Select-Object -First 1
    } catch {
        $baseboard = $null
    }

    if (-not $brand -and $baseboard) {
        $brand = Normalize-AssetValue -Value $baseboard.Manufacturer -Placeholders $commonPlaceholders
    }
    if (-not $model -and $baseboard) {
        $model = Normalize-AssetValue -Value $baseboard.Product -Placeholders $commonPlaceholders
    }

    $osName = $osInfo.Caption
    if (-not $osName) { $osName = $osInfo.Version }
    if ($osInfo.Version -and $osName) { $osName = "${osName} ($($osInfo.Version))" }
    if (-not $osName) { $osName = "Unknown OS" }

    $storageGb = $null
    $storageDetail = $null
    $storageDetailParts = @()
    if ($disks) {
        $storageGb = [int][math]::Round(($disks | Measure-Object -Property size_gb -Sum).Sum)
        $storageDetailParts += @($disks | ForEach-Object {
            "$($_.drive): $($_.size_gb) GB ($($_.free_gb) GB free)"
        })
    }

    if ($storageHealth.Count -gt 0) {
        $storageDetailParts += "Health: " + ($storageHealth -join " | ")
    }

    if ($storageDetailParts.Count -gt 0) {
        $storageDetail = Limit-Text -Value ($storageDetailParts -join "; ") -MaxLength 255
    }

    $category = Resolve-AssetCategory -Hostname $hostname -Brand $brand -Model $model

    [pscustomobject]@{
        factory              = $Factory
        department           = $Department
        agent_version        = $AgentVersion
        asset_code           = $identity.asset_code
        hostname             = $hostname
        user_name            = Get-LoggedOnUser
        os_name              = $osName
        category             = $category
        brand                = $brand
        model                = $model
        cpu                  = $cpuInfo
        ram_gb               = [math]::Round($csInfo.TotalPhysicalMemory / 1GB, 2)
        storage_gb           = $storageGb
        storage_detail       = $storageDetail
        disks                = $disks
        monitors             = @(Get-ConnectedMonitors -ParentHostname $hostname -Category $category)
        serial_number        = $serialNumber
        identity_source      = $identity.identity_source
        is_identity_verified = $identity.is_identity_verified
        ip_address           = Get-PrimaryIpv4
        anydesk_id           = Get-AnyDeskId
    }
}

Write-Host "Starting parallel remote scan on $($targets.Count) targets..." -ForegroundColor Cyan

# Create Runspace Pool
$sessionState = [System.Management.Automation.Runspaces.InitialSessionState]::CreateDefault()
$pool = [System.Management.Automation.Runspaces.RunspaceFactory]::CreateRunspacePool(1, $MaxParallel, $sessionState, $Host)
$pool.Open()

# Target worker script
$workerScript = {
    param(
        [string]$target,
        [string]$Token,
        [string]$Factory,
        [string]$Department,
        [string]$ServerUrl,
        [string]$AgentVersion,
        [int]$WsManPort,
        [int]$PortTimeoutMs,
        [int]$RetryCount,
        [int]$RetryDelaySeconds,
        [string]$LookupUrl,
        [bool]$SkipExisting,
        [bool]$SkipPreflight,
        [System.Management.Automation.PSCredential]$Credential,
        [scriptblock]$collector
    )

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

    function New-ScanResult {
        param(
            [string]$Computer,
            [string]$Status,
            [string]$Message,
            [string]$AssetCode = "",
            [string]$Hostname = "",
            [string]$Category = "",
            [string]$Brand = "",
            [string]$Model = "",
            [string]$SerialNumber = "",
            [string]$IdentitySource = "",
            [string]$IsIdentityVerified = "",
            [int]$MonitorCount = 0,
            [int]$MonitorVerifiedCount = 0,
            [string]$MonitorIdentitySources = "",
            [string]$IpAddress = "",
            [string]$AnyDeskId = "",
            [string]$ExistingAssetId = "",
            [string]$MatchedBy = ""
        )

        [pscustomobject]@{
            computer                 = $Computer
            hostname                 = $Hostname
            asset_code               = $AssetCode
            status                   = $Status
            message                  = $Message
            category                 = $Category
            brand                    = $Brand
            model                    = $Model
            serial_number            = $SerialNumber
            identity_source          = $IdentitySource
            is_identity_verified     = $IsIdentityVerified
            monitor_count            = $MonitorCount
            monitor_verified_count   = $MonitorVerifiedCount
            monitor_identity_sources = $MonitorIdentitySources
            ip_address               = $IpAddress
            anydesk_id               = $AnyDeskId
            existing_asset_id        = $ExistingAssetId
            matched_by               = $MatchedBy
            scanned_at               = (Get-Date).ToString("s")
        }
    }

    function Resolve-LookupUrl {
        param(
            [string]$ServerUrl,
            [string]$LookupUrl
        )

        if (-not [string]::IsNullOrWhiteSpace($LookupUrl)) {
            return $LookupUrl
        }

        if ($ServerUrl -match '(?i)/asset-sync/?$') {
            return ($ServerUrl -replace '(?i)/asset-sync/?$', '/asset-sync/lookup')
        }

        return ($ServerUrl.TrimEnd('/') + '/lookup')
    }

    function Get-ExistingAssetLookup {
        param(
            [string]$Target,
            [string]$Token,
            [string]$ServerUrl,
            [string]$LookupUrl
        )

        $effectiveLookupUrl = Resolve-LookupUrl -ServerUrl $ServerUrl -LookupUrl $LookupUrl
        $encodedTarget = [System.Uri]::EscapeDataString($Target)
        $separator = if ($effectiveLookupUrl.Contains("?")) { "&" } else { "?" }
        $uri = "$effectiveLookupUrl${separator}target=$encodedTarget&scope=computer"

        return Invoke-RestMethod -Uri $uri -Method Get -Headers @{ Authorization = "Bearer $Token" }
    }

    $lastMessage = ""
    if ($SkipExisting) {
        try {
            $lookup = Get-ExistingAssetLookup -Target $target -Token $Token -ServerUrl $ServerUrl -LookupUrl $LookupUrl
            if ($lookup -and [bool]$lookup.exists) {
                $asset = $lookup.asset
                return New-ScanResult `
                    -Computer $target `
                    -Status "skipped_existing" `
                    -Message "Existing asset found; remote scan skipped." `
                    -AssetCode ([string]$asset.asset_code) `
                    -Hostname ([string]$asset.hostname) `
                    -Category ([string]$asset.category) `
                    -Brand ([string]$asset.brand) `
                    -Model ([string]$asset.model) `
                    -SerialNumber ([string]$asset.serial_number) `
                    -IpAddress ([string]$asset.ip_address) `
                    -ExistingAssetId ([string]$asset.id) `
                    -MatchedBy ([string]$lookup.matched_by)
            }
        } catch {
            $lookupMessage = $_.Exception.Message
            if ($lookupMessage -match '(?i)Unauthorized|401|403') {
                return New-ScanResult -Computer $target -Status "failed" -Message "Asset lookup unauthorized: $lookupMessage"
            }
        }
    }

    for ($attempt = 1; $attempt -le ($RetryCount + 1); $attempt++) {
        try {
            if (-not $SkipPreflight) {
                $portOpen = Test-TcpPort -Computer $target -Port $WsManPort -TimeoutMs $PortTimeoutMs
                if (-not $portOpen) {
                    $lastMessage = "WinRM port $WsManPort tidak terbuka atau tidak reachable. Aktifkan WinRM/firewall di target."
                    if ($attempt -le $RetryCount) {
                        Start-Sleep -Seconds $RetryDelaySeconds
                        continue
                    }

                    return New-ScanResult -Computer $target -Status "skipped" -Message $lastMessage
                }
            }

            $invokeParams = @{
                ComputerName = $target
                ScriptBlock  = $collector
                ArgumentList = @($Factory, $Department, $AgentVersion)
                ErrorAction  = "Stop"
            }

            if ($Credential) {
                $invokeParams.Credential = $Credential
            }

            $payload = Invoke-Command @invokeParams
            $payload = $payload | Select-Object -First 1

            if (-not $payload) {
                $lastMessage = "Invoke-Command returned no data."
                if ($attempt -le $RetryCount) {
                    Start-Sleep -Seconds $RetryDelaySeconds
                    continue
                }

                return New-ScanResult -Computer $target -Status "failed" -Message $lastMessage
            }

            # Defensive API boundary: never send more than the server-side maximum.
            $payload.monitors = @($payload.monitors | Select-Object -First 12)

            $jsonBody = $payload | Select-Object `
                factory,
                department,
                agent_version,
                asset_code,
                hostname,
                user_name,
                os_name,
                category,
                brand,
                model,
                cpu,
                ram_gb,
                storage_gb,
                storage_detail,
                disks,
                monitors,
                serial_number,
                identity_source,
                is_identity_verified,
                ip_address,
                anydesk_id |
                ConvertTo-Json -Depth 8

            $response = Invoke-RestMethod -Uri $ServerUrl -Method Post -Headers @{ Authorization = "Bearer $Token" } -Body $jsonBody -ContentType "application/json"
            $summary = if ($response.counts) { ($response.counts | ConvertTo-Json -Compress) } else { "Synced" }
            $monitorIdentitySources = (@($payload.monitors) |
                ForEach-Object { $_.identity_source } |
                Where-Object { $_ } |
                Sort-Object -Unique) -join ","

            return New-ScanResult `
                -Computer $target `
                -Status "success" `
                -Message $summary `
                -AssetCode $payload.asset_code `
                -Hostname $payload.hostname `
                -Category $payload.category `
                -Brand $payload.brand `
                -Model $payload.model `
                -SerialNumber $payload.serial_number `
                -IdentitySource $payload.identity_source `
                -IsIdentityVerified ([string]$payload.is_identity_verified) `
                -MonitorCount @($payload.monitors).Count `
                -MonitorVerifiedCount @(@($payload.monitors) | Where-Object { $_.is_identity_verified }).Count `
                -MonitorIdentitySources $monitorIdentitySources `
                -IpAddress $payload.ip_address `
                -AnyDeskId $payload.anydesk_id
        } catch {
            $lastMessage = $_.Exception.Message
            try {
                if ($_.Exception.Response) {
                    $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                    $body = $reader.ReadToEnd()
                    $reader.Close()
                    if ($body) { $lastMessage = "$lastMessage | $body" }
                }
            } catch {}

            if ($lastMessage -match '(?i)Access is denied|Unauthorized|401|403' -or $attempt -gt $RetryCount) {
                return New-ScanResult -Computer $target -Status "failed" -Message $lastMessage
            }

            Start-Sleep -Seconds $RetryDelaySeconds
        }
    }

    return New-ScanResult -Computer $target -Status "failed" -Message $lastMessage
}

$runspaces = @()
foreach ($target in $targets) {
    $pipeline = [System.Management.Automation.PowerShell]::Create()
    $pipeline.RunspacePool = $pool
    
    $pipeline.AddScript($workerScript) | Out-Null
    $pipeline.AddArgument($target) | Out-Null
    $pipeline.AddArgument($Token) | Out-Null
    $pipeline.AddArgument($Factory) | Out-Null
    $pipeline.AddArgument($Department) | Out-Null
    $pipeline.AddArgument($ServerUrl) | Out-Null
    $pipeline.AddArgument($AgentVersion) | Out-Null
    $pipeline.AddArgument($WsManPort) | Out-Null
    $pipeline.AddArgument($PortTimeoutMs) | Out-Null
    $pipeline.AddArgument($RetryCount) | Out-Null
    $pipeline.AddArgument($RetryDelaySeconds) | Out-Null
    $pipeline.AddArgument($LookupUrl) | Out-Null
    $pipeline.AddArgument([bool]$SkipExisting) | Out-Null
    $pipeline.AddArgument($SkipPreflight) | Out-Null
    $pipeline.AddArgument($Credential) | Out-Null
    $pipeline.AddArgument($collector) | Out-Null

    $runspaces += [pscustomobject]@{
        Target      = $target
        PowerShell  = $pipeline
        AsyncResult = $pipeline.BeginInvoke()
    }
}

# Wait for completion and collect results
$results = @()
$totalCount = $targets.Count
$completedCount = 0

while ($runspaces.Count -gt 0) {
    $completed = @($runspaces | Where-Object { $_.AsyncResult.IsCompleted })
    foreach ($r in $completed) {
        $completedCount++
        try {
            $output = $r.PowerShell.EndInvoke($r.AsyncResult)
            if ($output) {
                $results += $output
                $scanResult = $output | Select-Object -First 1
                $statusColor = "Green"
                if ($scanResult.status -eq "skipped") { $statusColor = "Yellow" }
                if ($scanResult.status -eq "failed") { $statusColor = "Red" }
                
                Write-Host "[$completedCount/$totalCount] Result for $($r.Target): $($scanResult.status.ToUpper()) - $($scanResult.message)" -ForegroundColor $statusColor
            } else {
                $errResult = New-ScanResult -Computer $r.Target -Status "failed" -Message "No output returned from runspace."
                $results += $errResult
                Write-Host "[$completedCount/$totalCount] Result for $($r.Target): FAILED - No output returned." -ForegroundColor Red
            }
        } catch {
            $message = $_.Exception.Message
            $errResult = New-ScanResult -Computer $r.Target -Status "failed" -Message $message
            $results += $errResult
            Write-Host "[$completedCount/$totalCount] Result for $($r.Target): FAILED - $message" -ForegroundColor Red
        } finally {
            $r.PowerShell.Dispose()
        }
    }
    # Remove only jobs collected in this iteration. A job can finish between
    # the completed snapshot above and this filter; checking IsCompleted again
    # would drop that result without ever calling EndInvoke.
    $runspaces = @($runspaces | Where-Object { $completed -notcontains $_ })
    if ($runspaces.Count -gt 0) {
        Start-Sleep -Milliseconds 200
    }
}
$pool.Close()


$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDir = Split-Path -Parent $resultFullPath
if ($resultDir -and -not (Test-Path $resultDir)) {
    New-Item -ItemType Directory -Path $resultDir -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
Write-Host "Remote scan results saved to $resultFullPath" -ForegroundColor Cyan

$failedCount = @($results | Where-Object { $_.status -eq "failed" }).Count
if ($failedCount -gt 0 -and -not $NoFailExit) {
    exit 1
}
