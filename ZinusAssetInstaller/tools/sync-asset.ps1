# =========================
# ZINUS ASSET SYNC SCRIPT
# =========================
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
        $ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
            Where-Object { $_.IPAddress -notmatch '^169\.254\.' } |
            Select-Object -First 1 -ExpandProperty IPAddress
        return $ip
    } catch {
        return $null
    }
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
    } catch {
        return $null
    }

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
    } catch {
        return $null
    }

    try {
        $serial = Get-CimInstance Win32_ComputerSystemProduct -ErrorAction SilentlyContinue |
            Select-Object -First 1 -ExpandProperty UUID
        $serial = Normalize-AssetValue -Value $serial -Placeholders $SerialPlaceholders
        if ($serial) {
            return $serial
        }
    } catch {
        return $null
    }

    return $null
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
$username = $env:USERNAME
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
$brand = Normalize-AssetValue -Value $csInfo.Manufacturer -Placeholders $commonPlaceholders
$model = Normalize-AssetValue -Value $csInfo.Model -Placeholders $commonPlaceholders
$category = Get-CategoryFromChassis

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

if (-not $serialNumber) {
    Write-Log "Serial number not found. Sync requires serial number." "ERROR"
    exit 1
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
    token              = $token
    factory            = $factory
    department         = $department
    agent_version      = $agentVersion
    asset_code         = $serialNumber
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
    installed_software = $installedSoftware
    serial_number      = $serialNumber
}

if ($ipAddress) {
    $payload.ip_address = $ipAddress
}

$jsonBody = $payload | ConvertTo-Json -Depth 6

try {
    $headers = @{
        Authorization = "Bearer $token"
    }
    $response = Invoke-RestMethod -Uri $serverUrl -Method Post -Headers $headers -Body $jsonBody -ContentType "application/json"
    Write-Log "Sync success."
} catch {
    Write-Log "Sync failed: $($_.Exception.Message)" "ERROR"
    exit 1
}
