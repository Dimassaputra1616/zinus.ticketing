param(
    [string]$Token = "qAccfWhyBO79J0GkaTVOkhGzFXbIfdiuhMmpdMiPLtZxkkNCw3qijMZF9oaGEBXQ",
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "http://10.62.38.208/api/asset-sync",
    [string]$AgentVersion = "1.0.0",
    [switch]$SkipRun
)

$ErrorActionPreference = "Stop"

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "Please run this installer as Administrator." -ForegroundColor Red
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
    server_url    = $ServerUrl
    token         = $Token
    factory       = $Factory
    department    = $Department
    agent_version = $AgentVersion
    agent_sha256  = $agentHash
}

$config | ConvertTo-Json | Set-Content -Path $configPath -Encoding UTF8

$schtasksPath = Join-Path $env:WINDIR "System32\schtasks.exe"
$taskName = "Zinus Asset Monthly Sync"
$taskCommand = "`"$installCmd`""

if (Test-Path $schtasksPath) {
    & $schtasksPath /Create `
        /SC MONTHLY `
        /MO 1 `
        /D 1 `
        /ST 09:00 `
        /TN "$taskName" `
        /TR $taskCommand `
        /RL HIGHEST `
        /F | Out-Null
} else {
    Write-Host "schtasks.exe not found. Task not created." -ForegroundColor Yellow
}

Write-Host "Install complete. Files copied to $installRoot" -ForegroundColor Green

if (-not $SkipRun) {
    Write-Host "Running agent once for verification..." -ForegroundColor Cyan
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File "$installScript"
}
