param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$Token,
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$AgentVersion = "1.1.0",
    [switch]$SkipRun
)

$ErrorActionPreference = "Stop"

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "Please run this installer as Administrator." -ForegroundColor Red
    exit 1
}

$Token = $Token.Trim()
$Factory = $Factory.Trim()
$Department = $Department.Trim()
$ServerUrl = $ServerUrl.Trim()
$AgentVersion = $AgentVersion.Trim()

if ([string]::IsNullOrWhiteSpace($Token)) {
    Write-Host "Token is required. Pass -Token when running the installer." -ForegroundColor Red
    exit 1
}

$scriptPath = (Resolve-Path $MyInvocation.MyCommand.Path).Path
$repoRoot = Split-Path -Parent $scriptPath
$sourceDir = Join-Path $repoRoot "tools"

$sourceScript = Join-Path $sourceDir "sync-asset.ps1"
$sourceCmd = Join-Path $sourceDir "run.cmd"

if (-not (Test-Path $sourceScript)) {
    Write-Host "sync-asset.ps1 not found in tools folder." -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $sourceCmd)) {
    Write-Host "run.cmd not found in tools folder." -ForegroundColor Red
    exit 1
}

$installRoot = Join-Path $env:ProgramData "ZinusAssetSync"
$logRoot = Join-Path $installRoot "logs"
$configPath = Join-Path $installRoot "config.json"
$installScript = Join-Path $installRoot "sync-asset.ps1"
$installCmd = Join-Path $installRoot "run.cmd"

if (-not (Test-Path $installRoot)) {
    New-Item -ItemType Directory -Path $installRoot -Force | Out-Null
}

if (-not (Test-Path $logRoot)) {
    New-Item -ItemType Directory -Path $logRoot -Force | Out-Null
}

Copy-Item -Path $sourceScript -Destination $installScript -Force
Copy-Item -Path $sourceCmd -Destination $installCmd -Force

$agentHash = (Get-FileHash -Path $installScript -Algorithm SHA256).Hash

$config = [ordered]@{
    server_url            = $ServerUrl
    token                 = $Token
    factory               = $Factory
    department            = $Department
    agent_version         = $AgentVersion
    agent_sha256          = $agentHash
}

$config | ConvertTo-Json | Set-Content -Path $configPath -Encoding UTF8

$schtasksPath = Join-Path $env:WINDIR "System32\schtasks.exe"
$taskName = "Zinus Asset Daily Sync"
$legacyTaskName = "Zinus Asset Monthly Sync"
$startupTaskName = "Zinus Asset Startup Sync"
$logonTaskName = "Zinus Asset Logon Sync"
$powershellPath = Join-Path $env:WINDIR "System32\WindowsPowerShell\v1.0\powershell.exe"
$taskCommand = "`"$powershellPath`" -NoProfile -ExecutionPolicy Bypass -File `"$installScript`""

function Invoke-ZinusScheduledTaskCommand {
    param(
        [string[]]$Arguments,
        [string]$Operation,
        [switch]$IgnoreFailure
    )

    $previousErrorActionPreference = $ErrorActionPreference
    try {
        # schtasks writes expected conditions such as "task not found" to
        # stderr. Capture its exit code explicitly instead of allowing the
        # script-wide Stop preference to abort the complete installation.
        $ErrorActionPreference = "Continue"
        $output = @(& $schtasksPath @Arguments 2>&1)
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorActionPreference
    }

    if ($exitCode -ne 0 -and -not $IgnoreFailure) {
        $outputText = (($output | ForEach-Object {
            if ($_ -is [System.Management.Automation.ErrorRecord]) {
                $_.Exception.Message
            } else {
                [string]$_
            }
        }) -join " ").Trim()
        throw "$Operation gagal (schtasks exit code $exitCode). $outputText"
    }
}

if (Test-Path $schtasksPath) {
    Invoke-ZinusScheduledTaskCommand `
        -Arguments @("/Delete", "/TN", $legacyTaskName, "/F") `
        -Operation "Menghapus scheduled task lama" `
        -IgnoreFailure

    Invoke-ZinusScheduledTaskCommand `
        -Arguments @("/Create", "/SC", "DAILY", "/ST", "09:00", "/TN", $taskName, "/TR", $taskCommand, "/RU", "SYSTEM", "/RL", "HIGHEST", "/F") `
        -Operation "Membuat scheduled task harian"

    Invoke-ZinusScheduledTaskCommand `
        -Arguments @("/Create", "/SC", "ONSTART", "/DELAY", "0005:00", "/TN", $startupTaskName, "/TR", $taskCommand, "/RU", "SYSTEM", "/RL", "HIGHEST", "/F") `
        -Operation "Membuat scheduled task startup"

    Invoke-ZinusScheduledTaskCommand `
        -Arguments @("/Create", "/SC", "ONLOGON", "/DELAY", "0002:00", "/TN", $logonTaskName, "/TR", $taskCommand, "/RU", "SYSTEM", "/RL", "HIGHEST", "/F") `
        -Operation "Membuat scheduled task logon"
} else {
    Write-Host "schtasks.exe not found. Task not created." -ForegroundColor Yellow
}

Write-Host "Install complete. Files copied to $installRoot" -ForegroundColor Green

if (-not $SkipRun) {
    Write-Host "Running agent once for verification..." -ForegroundColor Cyan
    & $powershellPath -NoProfile -ExecutionPolicy Bypass -File "$installScript" -NoDelay
}
