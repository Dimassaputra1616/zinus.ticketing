# =========================
# ZINUS ASSET SYNC SCRIPT
# =========================
$ErrorActionPreference = "Stop"

# ===== 1. CONFIG =====
$ServerUrl  = "http://10.62.38.225:8000/api/asset-sync"   # URL API di Laravel
$Token      = "79e0d391c28351cd8fedad6af8e3bd236cf8e6017cd509a864468a5b94eae025"  # ASSET_SYNC_TOKEN
$Factory    = "Zinus F1 Bogor"
$Department = "IT"

Write-Host "=== ZINUS ASSET SYNC ===" -ForegroundColor Cyan
Write-Host "Server : $ServerUrl"
Write-Host ""


# ===== 2. PASTIKAN SCHEDULE TASK ADA (1x/bulan tgl 1 jam 09:00) =====
$taskName      = "Zinus Asset Monthly Sync"
$schtasksPath  = Join-Path $env:WINDIR "System32\schtasks.exe"

if (Test-Path $schtasksPath) {
    try {
        Write-Host "Cek / bikin scheduled task..." -ForegroundColor Yellow

        # cek sudah ada atau belum
        $null = & $schtasksPath /Query /TN "$taskName" 2>$null
        if ($LASTEXITCODE -ne 0) {
            # belum ada -> bikin
            $scriptPath = (Resolve-Path $MyInvocation.MyCommand.Path).Path

            & $schtasksPath /Create `
                /SC MONTHLY `
                /MO 1 `
                /D 1 `
                /ST 09:00 `
                /TN "$taskName" `
                /TR "powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`"" `
                /RL HIGHEST `
                /F | Out-Null

            Write-Host "Scheduled task dibuat: jalan tiap tgl 1 jam 09:00." -ForegroundColor Green
        } else {
            Write-Host "Scheduled task OK (jalan tiap tgl 1 jam 09:00)." -ForegroundColor Green
        }
    } catch {
        Write-Host "Gagal cek/bikin scheduled task: $($_.Exception.Message)" -ForegroundColor DarkYellow
    }
} else {
    Write-Host "WARNING: schtasks.exe tidak ketemu, schedule tidak dibuat." -ForegroundColor DarkYellow
}

Write-Host ""


# ===== 3. COLLECT SYSTEM INFO =====
$hostname = $env:COMPUTERNAME

$cs   = Get-CimInstance Win32_ComputerSystem
$bios = Get-CimInstance Win32_BIOS
$cpu  = Get-CimInstance Win32_Processor | Select-Object -First 1 -ExpandProperty Name

# --- User lokal yang lagi login (BUKAN Administrator) ---
$loginUser = $cs.UserName   # biasanya format: PC-NAME\User atau DOMAIN\User
if ($loginUser -and $loginUser.Contains("\")) {
    $loginUser = $loginUser.Split("\")[-1]   # ambil bagian belakang saja
}
if (-not $loginUser) {
    # fallback kalau entah kenapa kosong
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
$diskDrives      = Get-CimInstance Win32_DiskDrive
$storageDevices  = @()

foreach ($d in $diskDrives) {
    if (-not $d.Size) { continue }

    $sizeGb = [math]::Round($d.Size / 1GB)
    $diskType = "Unknown"

    # coba tebak tipe disk
    if ($d.MediaType -match 'SSD' -or $d.Model -match 'SSD') {
        $diskType = "SSD"
    } elseif ($d.PSObject.Properties.Name -contains 'RotationRate' -and $d.RotationRate -gt 0) {
        $diskType = "HDD"
    }

    $storageDevices += [pscustomobject]@{
        Model  = ($d.Model -replace '\s+',' ').Trim()
        Type   = $diskType
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
$os     = Get-CimInstance Win32_OperatingSystem
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

# --- Preview di console biar kelihatan ---
Write-Host "Hostname : $hostname"
Write-Host "User     : $userName"
Write-Host "Category : $Category"
Write-Host "RAM      : $ramGb GB"
Write-Host "Storage  : $storageSummary (Total: $storageTotalGb GB)"
Write-Host "OS       : $osName"
Write-Host "IP       : $ip"
Write-Host ""


# ===== 4. BUILD JSON PAYLOAD =====
$body = @{
    asset_code     = $hostname
    hostname       = $hostname
    user_name      = $userName
    factory        = $Factory
    department     = $Department
    category       = $Category
    brand          = $cs.Manufacturer
    model          = $cs.Model
    serial_number  = $bios.SerialNumber
    cpu            = $cpu
    ram_gb         = $ramGb
    storage_gb     = $storageTotalGb
    storage_detail = $storageSummary
    os_name        = $osName
    ip_address     = $ip
    status         = "Active"
} | ConvertTo-Json

Write-Host "Sending request to API..." -ForegroundColor Yellow


# ===== 5. SEND REQUEST =====
try {
    $response = Invoke-RestMethod -Uri $ServerUrl -Method Post -Body $body `
        -ContentType "application/json" `
        -Headers @{ Authorization = "Bearer $Token" } `
        -TimeoutSec 20

    Write-Host "Response:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Request failed: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails) {
        Write-Host $_.ErrorDetails -ForegroundColor DarkRed
    }
}
