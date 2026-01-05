# =========================
# ZINUS ASSET SYNC SCRIPT
# =========================
$ErrorActionPreference = "Stop"

$scriptPath = (Resolve-Path $MyInvocation.MyCommand.Path).Path
$scriptRoot = Split-Path -Parent $scriptPath
$installRoot = Join-Path $env:ProgramData "ZinusAssetSync"
$logRoot = Join-Path $installRoot "logs"
$logFile = Join-Path $logRoot ("sync-{0}.log" -f (Get-Date -Format "yyyyMMdd"))

if (-not (Test-Path $logRoot)) {
    New-Item -ItemType Directory -Path $logRoot -Force | Out-Null
}

function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO",
        [switch]$ToConsole,
        [string]$Color = "Gray"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] [$Level] $Message"
    Add-Content -Path $logFile -Value $line

    if ($ToConsole) {
        Write-Host $Message -ForegroundColor $Color
    }
}

$configPath = Join-Path $installRoot "config.json"
if (-not (Test-Path $configPath)) {
    $configPath = Join-Path $scriptRoot "config.json"
}

if (-not (Test-Path $configPath)) {
    Write-Log "Config file not found. Expected at $installRoot or $scriptRoot." "ERROR" -ToConsole -Color "Red"
    exit 1
}

try {
    $config = Get-Content -Path $configPath -Raw | ConvertFrom-Json
} catch {
    Write-Log "Failed to read config: $($_.Exception.Message)" "ERROR" -ToConsole -Color "Red"
    exit 1
}

$ServerUrl = $config.server_url
$Token = $config.token
$Factory = $config.factory
$Department = $config.department
$AgentVersion = if ($config.agent_version) { $config.agent_version } else { "unknown" }

if (-not $ServerUrl -or -not $Token -or -not $Factory -or -not $Department) {
    Write-Log "Missing required config fields (server_url, token, factory, department)." "ERROR" -ToConsole -Color "Red"
    exit 1
}

try {
    $AgentSha256 = (Get-FileHash -Path $scriptPath -Algorithm SHA256).Hash
} catch {
    Write-Log "Failed to compute agent SHA256: $($_.Exception.Message)" "ERROR" -ToConsole -Color "Red"
    exit 1
}

Write-Log "=== ZINUS ASSET SYNC ===" "INFO" -ToConsole -Color "Cyan"
Write-Log "Server: $ServerUrl" "INFO" -ToConsole -Color "Gray"
Write-Log "Factory: $Factory | Department: $Department" "INFO"

# ===== 1. COLLECT SYSTEM INFO =====
$hostname = $env:COMPUTERNAME

$cs = Get-CimInstance Win32_ComputerSystem
$bios = Get-CimInstance Win32_BIOS
$cpu = Get-CimInstance Win32_Processor | Select-Object -First 1 -ExpandProperty Name

# --- User lokal yang lagi login (BUKAN Administrator) ---
$loginUser = $cs.UserName
if ($loginUser -and $loginUser.Contains("\")) {
    $loginUser = $loginUser.Split("\")[-1]
}
if (-not $loginUser) {
    $loginUser = $env:USERNAME
}
$userName = $loginUser

# --- Deteksi kategori: Laptop / PC ---
try {
    if ($cs.PCSystemType -eq 2) {
        $Category = "Laptop"
    } else {
        $Category = "PC"
    }
} catch {
    $Category = "PC"
}

# --- RAM: bulatkan ke GB utuh ---
$ramGb = [math]::Round($cs.TotalPhysicalMemory / 1GB)

# --- Storage: detail per perangkat (SSD / HDD) ---
$diskDrives = Get-CimInstance Win32_DiskDrive
$storageDevices = @()

foreach ($d in $diskDrives) {
    if (-not $d.Size) { continue }

    $sizeGb = [math]::Round($d.Size / 1GB)
    $diskType = "Unknown"

    if ($d.MediaType -match 'SSD' -or $d.Model -match 'SSD') {
        $diskType = "SSD"
    } elseif ($d.PSObject.Properties.Name -contains 'RotationRate' -and $d.RotationRate -gt 0) {
        $diskType = "HDD"
    }

    $storageDevices += [pscustomobject]@{
        Model = ($d.Model -replace '\s+',' ').Trim()
        Type = $diskType
        SizeGB = $sizeGb
    }
}

if ($storageDevices.Count -gt 0) {
    $storageSummary = $storageDevices |
        ForEach-Object { "$($_.Type) $($_.SizeGB) GB" } |
        ForEach-Object { $_ } -join " + "

    $storageTotalGb = ($storageDevices | Measure-Object -Property SizeGB -Sum).Sum
} else {
    $storageSummary = "-"
    $storageTotalGb = 0
}

# --- OS ---
$os = Get-CimInstance Win32_OperatingSystem
$osName = $os.Caption

# --- IP Address (skip 169.254.xxx) ---
$ip = (
    Get-NetIPAddress -AddressFamily IPv4 -PrefixOrigin Dhcp -ErrorAction SilentlyContinue |
    Where-Object { $_.IPAddress -notmatch '^169\.254\.' } |
    Select-Object -First 1 -ExpandProperty IPAddress
)
if (-not $ip) {
    $ip = (Get-NetIPAddress -AddressFamily IPv4 -AddressState Preferred -ErrorAction SilentlyContinue |
        Where-Object { $_.IPAddress -notmatch '^169\.254\.' } |
        Select-Object -First 1 -ExpandProperty IPAddress)
}

Write-Log "Collected: Host=$hostname User=$userName Category=$Category RAM=${ramGb}GB OS=$osName IP=$ip" "INFO"

# ===== 2. BUILD JSON PAYLOAD =====
$serialNumber = $bios.SerialNumber
if ($serialNumber) {
    $serialNumber = $serialNumber.Trim()
}
if (-not $serialNumber) {
    Write-Log "Missing BIOS serial number. Sync aborted." "ERROR" -ToConsole -Color "Red"
    exit 1
}
$idempotencyKey = [guid]::NewGuid().ToString()

$payload = @{
    asset_code     = $serialNumber
    hostname       = $hostname
    user_name      = $userName
    factory        = $Factory
    department     = $Department
    category       = $Category
    brand          = $cs.Manufacturer
    model          = $cs.Model
    serial_number  = $serialNumber
    cpu            = $cpu
    ram_gb         = $ramGb
    storage_gb     = $storageTotalGb
    storage_detail = $storageSummary
    os_name        = $osName
    ip_address     = $ip
    status         = "Active"
    agent_version  = $AgentVersion
    agent_sha256   = $AgentSha256
    idempotency_key = $idempotencyKey
}

$body = $payload | ConvertTo-Json

# ===== 3. SEND REQUEST (RETRY) =====
$maxAttempts = 5
$backoffSeconds = 2

for ($attempt = 1; $attempt -le $maxAttempts; $attempt++) {
    try {
        Write-Log "Sending request (attempt $attempt/$maxAttempts)" "INFO"
        $response = Invoke-RestMethod -Uri $ServerUrl -Method Post -Body $body `
            -ContentType "application/json" `
            -Headers @{ Authorization = "Bearer $Token" } `
            -TimeoutSec 20

        Write-Log "Sync success." "INFO" -ToConsole -Color "Green"
        Write-Log ("Response: {0}" -f ($response | ConvertTo-Json -Depth 5)) "INFO"
        break
    } catch {
        $statusCode = $null
        $responseBody = $null
        $exception = $_.Exception

        if ($exception.Response) {
            try {
                $statusCode = [int]$exception.Response.StatusCode.value__
            } catch {
                $statusCode = $null
            }

            try {
                $reader = New-Object System.IO.StreamReader($exception.Response.GetResponseStream())
                $responseBody = $reader.ReadToEnd()
                $reader.Dispose()
            } catch {
                $responseBody = $null
            }
        }

        if ($statusCode -ge 400 -and $statusCode -lt 500) {
            Write-Log "Request failed with status $statusCode. Response: $responseBody" "ERROR" -ToConsole -Color "Red"
            break
        }

        Write-Log "Request failed (attempt $attempt/$maxAttempts): $($exception.Message)" "WARN"

        if ($attempt -ge $maxAttempts) {
            Write-Log "Max retries reached. Sync failed." "ERROR" -ToConsole -Color "Red"
            break
        }

        Start-Sleep -Seconds $backoffSeconds
        $backoffSeconds = [Math]::Min($backoffSeconds * 2, 32)
    }
}
