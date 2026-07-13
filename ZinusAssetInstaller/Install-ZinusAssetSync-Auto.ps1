param(
    [string]$ConfigPath = (Join-Path $PSScriptRoot "install-config.json")
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ConfigPath)) {
    Write-Host "install-config.json tidak ditemukan." -ForegroundColor Red
    Write-Host "Copy install-config.example.json menjadi install-config.json, lalu isi token deployment." -ForegroundColor Yellow
    exit 1
}

try {
    $config = Get-Content -Path $ConfigPath -Raw | ConvertFrom-Json
} catch {
    Write-Host "Gagal membaca install-config.json: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

$token = if ($config.token) { [string]$config.token } else { "" }
$token = $token.Trim()

if ([string]::IsNullOrWhiteSpace($token) -or $token -in @("ISI_TOKEN_ASSET_SYNC_DI_SINI", "TOKEN_DARI_SERVER")) {
    Write-Host "Token belum diisi di install-config.json." -ForegroundColor Red
    exit 1
}

$installScript = Join-Path $PSScriptRoot "Install-ZinusAssetSync.ps1"
if (-not (Test-Path $installScript)) {
    Write-Host "Install-ZinusAssetSync.ps1 tidak ditemukan." -ForegroundColor Red
    exit 1
}

$installParams = @{
    Token               = $token
    Factory             = if ($config.factory) { [string]$config.factory } else { "GCI-HWANG" }
    Department          = if ($config.department) { [string]$config.department } else { "IT" }
    ServerUrl           = if ($config.server_url) { [string]$config.server_url } else { "https://app.it-ticketing.web.id/api/asset-sync" }
    AgentVersion        = if ($config.agent_version) { [string]$config.agent_version } else { "1.1.0" }
}

if ($config.PSObject.Properties.Name -contains "skip_run" -and [bool]$config.skip_run) {
    $installParams.SkipRun = $true
}

& $installScript @installParams
