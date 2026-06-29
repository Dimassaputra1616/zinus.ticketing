param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$Token,
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$AgentVersion = "1.1.0",
    [string]$RustdeskIdServer = "",
    [string]$RustdeskRelayServer = "",
    [string]$RustdeskKey = "",
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
$RustdeskIdServer = $RustdeskIdServer.Trim()
$RustdeskRelayServer = $RustdeskRelayServer.Trim()
$RustdeskKey = $RustdeskKey.Trim()

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
    rustdesk_id_server    = $RustdeskIdServer
    rustdesk_relay_server = $RustdeskRelayServer
    rustdesk_key          = $RustdeskKey
}

$config | ConvertTo-Json | Set-Content -Path $configPath -Encoding UTF8

$schtasksPath = Join-Path $env:WINDIR "System32\schtasks.exe"
$taskName = "Zinus Asset Daily Sync"
$legacyTaskName = "Zinus Asset Monthly Sync"
$startupTaskName = "Zinus Asset Startup Sync"
$taskCommand = "`"$installCmd`""

if (Test-Path $schtasksPath) {
    & $schtasksPath /Delete /TN "$legacyTaskName" /F 2>$null | Out-Null

    & $schtasksPath /Create `
        /SC DAILY `
        /ST 09:00 `
        /TN "$taskName" `
        /TR $taskCommand `
        /RU SYSTEM `
        /RL HIGHEST `
        /F | Out-Null

    & $schtasksPath /Create `
        /SC ONSTART `
        /TN "$startupTaskName" `
        /TR $taskCommand `
        /RU SYSTEM `
        /RL HIGHEST `
        /F | Out-Null
} else {
    Write-Host "schtasks.exe not found. Task not created." -ForegroundColor Yellow
}

Write-Host "Install complete. Files copied to $installRoot" -ForegroundColor Green

if (-not $SkipRun) {
    Write-Host "Running agent once for verification..." -ForegroundColor Cyan
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File "$installScript" -NoDelay
}
