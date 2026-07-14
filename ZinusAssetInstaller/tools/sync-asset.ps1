# =========================
# ZINUS ASSET SYNC SCRIPT
# =========================
param(
    [switch]$NoDelay
)
$ErrorActionPreference = "Stop"


$installRoot = Join-Path $env:ProgramData "ZinusAssetSync"
$logRoot = Join-Path $installRoot "logs"
$configPath = Join-Path $installRoot "config.json"
$logFile = Join-Path $logRoot ("sync-{0}.log" -f (Get-Date -Format "yyyyMMdd"))

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

if (-not (Test-Path $logRoot)) {
    New-Item -ItemType Directory -Path $logRoot -Force | Out-Null
}

function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Level $Message"
    Add-Content -Path $logFile -Value $line
}

function Enter-SyncMutex {
    $mutexName = "Global\ZinusAssetSyncAgent"
    try {
        $mutex = New-Object System.Threading.Mutex($false, $mutexName)
        if (-not $mutex.WaitOne(0)) {
            Write-Log "Another Zinus Asset Sync process is already running. Exiting." "WARN"
            exit 0
        }

        return $mutex
    } catch {
        Write-Log "Failed to acquire sync mutex: $($_.Exception.Message)" "WARN"
        return $null
    }
}

function Normalize-AssetValue {
    param(
        [string]$Value,
        [string[]]$Placeholders
    )

    if ($null -eq $Value) {
        return $null
    }

    $trimmed = $Value.Trim()
    if ($trimmed -eq "") {
        return $null
    }

    foreach ($pattern in $Placeholders) {
        if ($trimmed -match $pattern) {
            return $null
        }
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
            if ($adapter -and $adapter.Status -ne "Up") {
                continue
            }

            $ip = Get-NetIPAddress -InterfaceIndex $route.InterfaceIndex -AddressFamily IPv4 -ErrorAction SilentlyContinue |
                Where-Object {
                    $_.IPAddress -and
                    $_.IPAddress -notmatch '^169\.254\.' -and
                    $_.IPAddress -notmatch '^127\.'
                } |
                Sort-Object PrefixLength -Descending |
                Select-Object -First 1 -ExpandProperty IPAddress

            if ($ip) {
                return $ip
            }
        }

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

        return $null
    } catch {}
}

function Get-LoggedOnUser {
    try {
        $csUser = Get-CimInstance Win32_ComputerSystem -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty UserName

        $normalized = Normalize-AssetValue -Value $csUser -Placeholders $commonPlaceholders
        if ($normalized) {
            return $normalized
        }
    } catch {}

    try {
        $explorerProcesses = @(Get-CimInstance Win32_Process -Filter "Name = 'explorer.exe'" -ErrorAction SilentlyContinue)
        foreach ($process in $explorerProcesses) {
            $owner = Invoke-CimMethod -InputObject $process -MethodName GetOwner -ErrorAction SilentlyContinue
            if ($owner -and $owner.User) {
                if ($owner.Domain) {
                    return "$($owner.Domain)\$($owner.User)"
                }

                return $owner.User
            }
        }
    } catch {}

    return $null
}

function Get-DiskInfo {
    $disks = Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3"
    $result = @()

    foreach ($disk in $disks) {
        $sizeGb = if ($disk.Size) { [math]::Round($disk.Size / 1GB, 2) } else { 0 }
        $freeGb = if ($disk.FreeSpace) { [math]::Round($disk.FreeSpace / 1GB, 2) } else { 0 }
        $usedGb = if ($disk.Size -and $disk.FreeSpace) {
            [math]::Round(($disk.Size - $disk.FreeSpace) / 1GB, 2)
        } else {
            0
        }

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
    param(
        [string[]]$SerialPlaceholders
    )

    try {
        $serial = Get-CimInstance Win32_BIOS -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty SerialNumber
        $serial = Normalize-AssetValue -Value $serial -Placeholders $SerialPlaceholders
        if ($serial) {
            return $serial
        }
    } catch {}

    try {
        $serial = Get-CimInstance Win32_ComputerSystemProduct -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty IdentifyingNumber
        $serial = Normalize-AssetValue -Value $serial -Placeholders $SerialPlaceholders
        if ($serial) {
            return $serial
        }
    } catch {
        return $null
    }

    try {
        $serial = Get-CimInstance Win32_BaseBoard -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty SerialNumber
        $serial = Normalize-AssetValue -Value $serial -Placeholders $SerialPlaceholders
        if ($serial) {
            return $serial
        }
    } catch {}

    return $null
}

function Get-SystemUuid {
    param(
        [string[]]$SerialPlaceholders
    )

    try {
        $uuid = Get-CimInstance Win32_ComputerSystemProduct -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty UUID
        return Normalize-AssetValue -Value $uuid -Placeholders $SerialPlaceholders
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

    $hostnameValue = $Hostname
    if ([string]::IsNullOrWhiteSpace($hostnameValue)) {
        $hostnameValue = $env:COMPUTERNAME
    }

    return [pscustomobject]@{
        asset_code           = "HOST-$hostnameValue"
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

    if (-not $types) {
        return $null
    }

    $laptopTypes = @(8, 9, 10, 11, 12, 14, 30, 31, 32)
    $desktopTypes = @(3, 4, 5, 6, 7, 13, 15, 16, 17, 18, 23, 24)

    foreach ($type in $types) {
        if ($laptopTypes -contains $type) {
            return "Laptop"
        }
        if ($desktopTypes -contains $type) {
            return "PC"
        }
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

    if ($null -eq $Value) {
        return $null
    }

    $chars = @()
    foreach ($code in $Value) {
        if ($null -ne $code -and [int]$code -gt 0) {
            $chars += [char][int]$code
        }
    }

    $text = (-join $chars).Trim()
    if ($text -eq "") {
        return $null
    }

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

    if ([string]::IsNullOrWhiteSpace($candidate)) {
        return $null
    }

    $code = $candidate.Trim().ToUpperInvariant()
    if ($code -notmatch '^[A-Z0-9]{3}$') {
        return $candidate
    }

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

    if ($null -eq $Technology) {
        return $null
    }

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

    $base = $ParentHostname
    if ([string]::IsNullOrWhiteSpace($base)) {
        $base = $env:COMPUTERNAME
    }

    $suffix = $Model
    if ([string]::IsNullOrWhiteSpace($suffix)) {
        $suffix = $Serial
    }
    if ([string]::IsNullOrWhiteSpace($suffix)) {
        $suffix = "MON-$Index"
    }

    $suffix = ($suffix -replace '[^A-Za-z0-9_-]+', '-').Trim('-')
    if ([string]::IsNullOrWhiteSpace($suffix)) {
        $suffix = "MON-$Index"
    }

    $hostname = "$base-$suffix"
    if ($hostname.Length -gt 191) {
        $hostname = $hostname.Substring(0, 191)
    }

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
        [string[]]$CommonPlaceholders,
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

        $name = Normalize-AssetValue -Value ([string](Get-ObjectPropertyValue -Object $monitor -Name "Name")) -Placeholders $CommonPlaceholders
        $manufacturer = Normalize-AssetValue -Value ([string](Get-ObjectPropertyValue -Object $monitor -Name "Manufacturer")) -Placeholders $CommonPlaceholders
        if (-not $manufacturer) {
            $manufacturer = Normalize-AssetValue -Value ([string](Get-ObjectPropertyValue -Object $monitor -Name "MonitorManufacturer")) -Placeholders $CommonPlaceholders
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
        [string[]]$CommonPlaceholders,
        [string[]]$SerialPlaceholders,
        [string]$Category
    )

    $results = @()

    $monitorIds = @()
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        try {
            $monitorIds = @(Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorID -ErrorAction Stop)
        } catch {
            $monitorIds = @()
            if ($attempt -eq 3) {
                Write-Log "Failed to collect monitor IDs after $attempt attempts: $($_.Exception.Message)" "WARN"
            }
        }

        if ($monitorIds.Count -gt 0) {
            break
        }

        if ($attempt -lt 3) {
            Start-Sleep -Seconds 2
        }
    }

    if (-not $monitorIds -or $monitorIds.Count -eq 0) {
        Write-Log "No monitor IDs detected after 3 attempts. Monitor payload will be empty." "WARN"
        return $results
    }

    try {
        $connections = @(Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorConnectionParams -ErrorAction SilentlyContinue)
    } catch {
        $connections = @()
        Write-Log "Failed to collect monitor connection info: $($_.Exception.Message)" "WARN"
    }

    try {
        $displayParams = @(Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorBasicDisplayParams -ErrorAction SilentlyContinue)
    } catch {
        $displayParams = @()
        Write-Log "Failed to collect monitor display params: $($_.Exception.Message)" "WARN"
    }

    $connectionByInstance = @{}
    foreach ($connection in $connections) {
        if ($connection.InstanceName) {
            $connectionByInstance[$connection.InstanceName] = $connection
        }
    }

    $displayByInstance = @{}
    foreach ($display in $displayParams) {
        if ($display.InstanceName) {
            $displayByInstance[$display.InstanceName] = $display
        }
    }

    $activeMonitorCount = @(
        $monitorIds | Where-Object {
            -not ($_.PSObject.Properties.Name -contains "Active") -or [bool]$_.Active
        }
    ).Count
    $useInactiveFallback = $Category -eq "PC" -and $activeMonitorCount -eq 0
    if ($useInactiveFallback) {
        Write-Log "No active monitor reported for desktop; using inactive WMI monitor entries as fallback." "WARN"
    }

    $index = 0
    foreach ($monitor in $monitorIds) {
        $isActive = $true
        if ($monitor.PSObject.Properties.Name -contains "Active") {
            $isActive = [bool]$monitor.Active
        }
        if (-not $isActive -and -not $useInactiveFallback) {
            continue
        }

        $connection = $null
        if ($monitor.InstanceName -and $connectionByInstance.ContainsKey($monitor.InstanceName)) {
            $connection = $connectionByInstance[$monitor.InstanceName]
        }

        $connectionLabel = $null
        if ($connection) {
            $connectionLabel = Get-MonitorConnectionLabel -Technology $connection.VideoOutputTechnology
        }

        if ($Category -ne "PC" -and (
                [string]::IsNullOrWhiteSpace([string]$connectionLabel) -or
                @("Internal", "Embedded DisplayPort", "Embedded UDI", "Unknown", "Uninitialized") -contains $connectionLabel
            )) {
            continue
        }

        $manufacturer = Normalize-AssetValue -Value (Convert-WmiMonitorString $monitor.ManufacturerName) -Placeholders $CommonPlaceholders
        $manufacturer = Resolve-MonitorManufacturerName -Manufacturer $manufacturer -PnpId $monitor.InstanceName
        $model = Normalize-AssetValue -Value (Convert-WmiMonitorString $monitor.UserFriendlyName) -Placeholders $CommonPlaceholders
        $serial = Normalize-AssetValue -Value (Convert-WmiMonitorString $monitor.SerialNumberID) -Placeholders $SerialPlaceholders

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
        if ($assetCode.Length -gt 191) {
            $assetCode = $assetCode.Substring(0, 191)
        }

        $monitorHostname = New-MonitorHostname -ParentHostname $ParentHostname -Model $model -Serial $serial -Index $index
        $display = $null
        if ($monitor.InstanceName -and $displayByInstance.ContainsKey($monitor.InstanceName)) {
            $display = $displayByInstance[$monitor.InstanceName]
        }

        $widthCm = $null
        $heightCm = $null
        if ($display) {
            if ($display.MaxHorizontalImageSize -gt 0) {
                $widthCm = [int]$display.MaxHorizontalImageSize
            }
            if ($display.MaxVerticalImageSize -gt 0) {
                $heightCm = [int]$display.MaxVerticalImageSize
            }
        }

        $displayNameParts = @()
        if ($manufacturer) { $displayNameParts += $manufacturer }
        if ($model) { $displayNameParts += $model }
        if ($serial) { $displayNameParts += "($serial)" }
        $displayName = ($displayNameParts -join " ").Trim()
        if ([string]::IsNullOrWhiteSpace($displayName)) {
            $displayName = $monitorHostname
        }

        $results += [pscustomobject]@{
            asset_code       = $assetCode
            hostname         = $monitorHostname
            name             = $displayName
            serial_number    = $serial
            manufacturer     = $manufacturer
            brand            = $manufacturer
            model            = $model
            connection       = $connectionLabel
            instance_name    = $monitor.InstanceName
            identity_source  = $identitySource
            is_identity_verified = $isIdentityVerified
            screen_width_cm  = $widthCm
            screen_height_cm = $heightCm
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
        $uniqueResults = @(
            Get-FallbackConnectedMonitors `
                -ParentHostname $ParentHostname `
                -CommonPlaceholders $CommonPlaceholders `
                -Category $Category
        )
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

function Get-AnyDeskId {
    param(
        [int]$Attempts = 6,
        [int]$DelaySeconds = 5
    )

    foreach ($exe in Get-AnyDeskExecutablePaths) {
        if (-not (Test-Path -LiteralPath $exe)) {
            continue
        }

        for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
            try {
                $output = & $exe --get-id 2>&1 | Out-String
                if ($output -match '([0-9]{3}\s?[0-9]{3}\s?[0-9]{3,4})') {
                    return ($matches[1] -replace '\s+', '')
                }
            } catch {
                Write-Log "Failed to get ID via AnyDesk.exe: $($_.Exception.Message)" "WARN"
            }

            if ($attempt -lt $Attempts) {
                Start-Sleep -Seconds $DelaySeconds
            }
        }
    }

    return $null
}

$syncMutex = Enter-SyncMutex

if (-not $NoDelay) {
    $delay = Get-Random -Minimum 0 -Maximum 1800
    Write-Log "Applying random start delay of $delay seconds to prevent server overload..." "INFO"
    Start-Sleep -Seconds $delay
}

if (-not (Test-Path $configPath)) {
    Write-Log "Config file not found at $configPath." "ERROR"
    exit 1
}

try {
    $configJson = Get-Content $configPath -Raw | ConvertFrom-Json
} catch {
    Write-Log "Failed to read config: $($_.Exception.Message)" "ERROR"
    exit 1
}

$serverUrl = $configJson.server_url
$token = $configJson.token
$factory = $configJson.factory
$department = $configJson.department
$agentVersion = $configJson.agent_version

if ($serverUrl) {
    $serverUrl = $serverUrl.Trim()
}
if ($token) {
    $token = $token.Trim()
}
if (-not $agentVersion) {
    $agentVersion = "unknown"
}

if (-not $serverUrl -or -not $token -or -not $factory -or -not $department) {
    Write-Log "Missing required config fields (server_url, token, factory, department)." "ERROR"
    exit 1
}

try {
    $osInfo = Get-CimInstance Win32_OperatingSystem
    $cpuInfo = (Get-CimInstance Win32_Processor | Select-Object -First 1).Name
    $csInfo = Get-CimInstance Win32_ComputerSystem
    $disks = Get-DiskInfo
} catch {
    Write-Log "Failed to collect system info: $($_.Exception.Message)" "ERROR"
    exit 1
}

$hostname = $env:COMPUTERNAME
$username = Get-LoggedOnUser
$osName = $osInfo.Caption
if (-not $osName) {
    $osName = $osInfo.Version
}
if ($osInfo.Version -and $osName) {
    $osName = "${osName} ($($osInfo.Version))"
}
if (-not $osName) {
    $osName = "Unknown OS"
}

$memoryGb = [math]::Round($csInfo.TotalPhysicalMemory / 1GB, 2)
$ipAddress = Get-PrimaryIpv4
$installedSoftware = @()
$serialNumber = Get-SerialNumber -SerialPlaceholders $serialPlaceholders
$systemUuid = Get-SystemUuid -SerialPlaceholders $serialPlaceholders
$identity = Get-AssetIdentity -SerialNumber $serialNumber -Uuid $systemUuid -Hostname $hostname
$brand = Normalize-AssetValue -Value $csInfo.Manufacturer -Placeholders $commonPlaceholders
$model = Normalize-AssetValue -Value $csInfo.Model -Placeholders $commonPlaceholders
$category = Resolve-AssetCategory -Hostname $hostname -Brand $brand -Model $model
$monitors = @(
    Get-ConnectedMonitors `
        -ParentHostname $hostname `
        -CommonPlaceholders $commonPlaceholders `
        -SerialPlaceholders $serialPlaceholders `
        -Category $category
)

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

$anyDeskId = Get-AnyDeskId

if (-not $serialNumber) {
    Write-Log "Serial number not found. Using $($identity.identity_source) fallback asset identity." "WARN"
}

$storageGb = $null
$storageDetail = $null
if ($disks) {
    $storageGb = [int][math]::Round(($disks | Measure-Object -Property size_gb -Sum).Sum)
    $storageDetail = ($disks | ForEach-Object {
        "$($_.drive): $($_.size_gb) GB ($($_.free_gb) GB free)"
    }) -join "; "
}

$payload = @{
    factory            = $factory
    department         = $department
    agent_version      = $agentVersion
    asset_code         = $identity.asset_code
    hostname           = $hostname
    user_name          = $username
    os_name            = $osName
    category           = $category
    brand              = $brand
    model              = $model
    cpu                = $cpuInfo
    ram_gb             = $memoryGb
    storage_gb         = $storageGb
    storage_detail     = $storageDetail
    disks              = $disks
    monitors           = $monitors
    installed_software = $installedSoftware
    serial_number      = $serialNumber
    identity_source    = $identity.identity_source
    is_identity_verified = $identity.is_identity_verified
}

if ($ipAddress) {
    $payload.ip_address = $ipAddress
}
if ($anyDeskId) {
    $payload.anydesk_id = $anyDeskId
}

$jsonBody = $payload | ConvertTo-Json -Depth 6

try {
    $headers = @{
        Authorization = "Bearer $token"
    }
    $response = Invoke-RestMethod -Uri $serverUrl -Method Post -Headers $headers -Body $jsonBody -ContentType "application/json"
    if ($response.counts) {
        $summary = $response.counts | ConvertTo-Json -Compress
        Write-Log "Sync success. Summary: $summary"
    } elseif ($response.data -and $response.data.counts) {
        $summary = $response.data.counts | ConvertTo-Json -Compress
        Write-Log "Sync success. Summary: $summary"
    } else {
        Write-Log "Sync success."
    }
} catch {
    $errMsg = $_.Exception.Message
    # Try to extract the response body for detailed error info
    $responseBody = ""
    try {
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $reader.BaseStream.Position = 0
            $responseBody = $reader.ReadToEnd()
            $reader.Close()
        }
    } catch {}
    if ($responseBody) {
        Write-Log "Sync failed: $errMsg | Server response: $responseBody" "ERROR"
    } else {
        Write-Log "Sync failed: $errMsg" "ERROR"
    }
    if ($errMsg -match "401" -or $responseBody -match "Unauthorized") {
        Write-Log "Unauthorized. Check that config.json token matches ASSET_SYNC_TOKEN/ASSET_SYNC_TOKENS on the Laravel server, then clear Laravel config cache." "ERROR"
    }
    if ($syncMutex) {
        try {
            $syncMutex.ReleaseMutex()
            $syncMutex.Dispose()
        } catch {}
    }
    exit 1
}

if ($syncMutex) {
    try {
        $syncMutex.ReleaseMutex()
        $syncMutex.Dispose()
    } catch {}
}
