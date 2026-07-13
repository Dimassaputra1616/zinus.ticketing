param(
    [string]$OutputDirectory = ".\dist",
    [string]$PackageName = "ZinusAssetInstaller",
    [switch]$NoZip
)

$ErrorActionPreference = "Stop"

$scriptRoot = Split-Path -Parent (Resolve-Path $MyInvocation.MyCommand.Path)
$outputRoot = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($OutputDirectory)
$packageRoot = Join-Path $outputRoot $PackageName
$toolsRoot = Join-Path $packageRoot "tools"

$requiredFiles = @(
    "Install-ZinusAssetSync.ps1",
    "Install-ZinusAssetSync.cmd",
    "Install-ZinusAssetSync-Silent.cmd",
    "Install-ZinusAssetSync-Auto.ps1",
    "INSTALL-AUTO.cmd",
    "RUN-BOOTSTRAP-WINRM-SEGMENTS.cmd",
    "RUN-DISCOVER-SEGMENTS.cmd",
    "RUN-RESOLVE-HOSTNAMES.cmd",
    "RUN-SCAN-ALL.cmd",
    "RUN-SCAN-SEGMENTS.cmd",
    "RUN-AUTO-ASSET-SCAN.cmd",
    "RUN-DEPLOY-ALL.cmd",
    "RUN-ENABLE-ASSET-PREREQS.cmd",
    "Bootstrap-ZinusWinRM.ps1",
    "Bootstrap-ZinusWinRMSegments.ps1",
    "Enable-ZinusAssetPrereqs.ps1",
    "Discover-ZinusNetwork.ps1",
    "Discover-ZinusSegments.ps1",
    "Resolve-ZinusHostnames.ps1",
    "Scan-ZinusAssetsRemote.ps1",
    "Scan-ZinusSegments.ps1",
    "Invoke-ZinusAssetAutomation.ps1",
    "Deploy-ZinusAssetSync.ps1",
    "Deploy-ZinusAnyDesk.ps1",
    "Export-ZinusDataQualityIssues.ps1",
    "Export-ZinusFailureAnalysis.ps1",
    "Export-ZinusMissingHostnames.ps1",
    "Export-ZinusRemediationLists.ps1",
    "install-config.example.json",
    "README.md",
    "tools\sync-asset.ps1",
    "tools\run.cmd"
)

foreach ($relativePath in $requiredFiles) {
    $sourcePath = Join-Path $scriptRoot $relativePath
    if (-not (Test-Path $sourcePath)) {
        throw "Required package file not found: $sourcePath"
    }
}

if (Test-Path $packageRoot) {
    Remove-Item -Path $packageRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $toolsRoot -Force | Out-Null

foreach ($relativePath in $requiredFiles) {
    $sourcePath = Join-Path $scriptRoot $relativePath
    $destinationPath = Join-Path $packageRoot $relativePath
    $destinationDirectory = Split-Path -Parent $destinationPath

    if (-not (Test-Path $destinationDirectory)) {
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
    }

    Copy-Item -Path $sourcePath -Destination $destinationPath -Force
}

@(
    "# Satu hostname atau IP per baris",
    "# PC-001",
    "# LAPTOP-002"
) | Set-Content -Path (Join-Path $packageRoot "computers.example.txt") -Encoding UTF8

Copy-Item -Path (Join-Path $packageRoot "install-config.example.json") -Destination (Join-Path $packageRoot "install-config.json") -Force

if (-not $NoZip) {
    $zipPath = Join-Path $outputRoot ($PackageName + ".zip")
    if (Test-Path $zipPath) {
        Remove-Item -Path $zipPath -Force
    }

    Compress-Archive -Path (Join-Path $packageRoot "*") -DestinationPath $zipPath -Force
    Write-Host "Package folder created: $packageRoot" -ForegroundColor Green
    Write-Host "Package zip created: $zipPath" -ForegroundColor Green
} else {
    Write-Host "Package folder created: $packageRoot" -ForegroundColor Green
}
