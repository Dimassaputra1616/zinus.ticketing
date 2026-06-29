param(
    [string[]]$ComputerName = @(),
    [string]$ComputerList,
    [Parameter(Mandatory = $true)]
    [string]$Token,
    [string]$Factory = "GCI-HWANG",
    [string]$Department = "IT",
    [string]$ServerUrl = "https://app.it-ticketing.web.id/api/asset-sync",
    [string]$AgentVersion = "1.1.0",
    [string]$RustdeskIdServer = "",
    [string]$RustdeskRelayServer = "",
    [string]$RustdeskKey = "",
    [switch]$RunNow,
    [string]$RemoteStagePath = "C:\ProgramData\ZinusAssetSyncDeploy",
    [string]$ResultPath = ".\zinus-asset-deploy-results.csv",
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

function Resolve-TargetComputers {
    $targets = @()

    if ($ComputerName) {
        $targets += $ComputerName
    }

    if ($ComputerList) {
        if (-not (Test-Path $ComputerList)) {
            throw "Computer list not found: $ComputerList"
        }

        $targets += Get-Content -Path $ComputerList |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    }

    $targets |
        Where-Object { $_ } |
        Sort-Object -Unique
}

function New-DeployResult {
    param(
        [string]$Computer,
        [string]$Status,
        [string]$Message
    )

    [pscustomobject]@{
        computer    = $Computer
        status      = $Status
        message     = $Message
        deployed_at = (Get-Date).ToString("s")
    }
}

$scriptRoot = Split-Path -Parent (Resolve-Path $MyInvocation.MyCommand.Path)
$installerPath = Join-Path $scriptRoot "Install-ZinusAssetSync.ps1"
$syncScriptPath = Join-Path $scriptRoot "tools\sync-asset.ps1"
$runCmdPath = Join-Path $scriptRoot "tools\run.cmd"

foreach ($requiredPath in @($installerPath, $syncScriptPath, $runCmdPath)) {
    if (-not (Test-Path $requiredPath)) {
        throw "Required installer file not found: $requiredPath"
    }
}

$targets = @(Resolve-TargetComputers)
if ($targets.Count -eq 0) {
    throw "No target computers provided. Use -ComputerName or -ComputerList."
}

$results = @()

foreach ($target in $targets) {
    Write-Host "Deploying Zinus Asset Sync to $target..." -ForegroundColor Cyan

    $session = $null
    try {
        $sessionParams = @{
            ComputerName = $target
        }

        if ($Credential) {
            $sessionParams.Credential = $Credential
        }

        $session = New-PSSession @sessionParams

        Invoke-Command -Session $session -ScriptBlock {
            param([string]$StagePath)

            New-Item -ItemType Directory -Path $StagePath -Force | Out-Null
            New-Item -ItemType Directory -Path (Join-Path $StagePath "tools") -Force | Out-Null
        } -ArgumentList $RemoteStagePath

        Copy-Item -ToSession $session -Path $installerPath -Destination (Join-Path $RemoteStagePath "Install-ZinusAssetSync.ps1") -Force
        Copy-Item -ToSession $session -Path $syncScriptPath -Destination (Join-Path $RemoteStagePath "tools\sync-asset.ps1") -Force
        Copy-Item -ToSession $session -Path $runCmdPath -Destination (Join-Path $RemoteStagePath "tools\run.cmd") -Force

        $installOutput = Invoke-Command -Session $session -ScriptBlock {
            param(
                [string]$StagePath,
                [string]$Token,
                [string]$Factory,
                [string]$Department,
                [string]$ServerUrl,
                [string]$AgentVersion,
                [string]$RustdeskIdServer,
                [string]$RustdeskRelayServer,
                [string]$RustdeskKey,
                [bool]$RunNow
            )

            Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force

            $installScript = Join-Path $StagePath "Install-ZinusAssetSync.ps1"
            $installParams = @{
                Token                 = $Token
                Factory               = $Factory
                Department            = $Department
                ServerUrl             = $ServerUrl
                AgentVersion          = $AgentVersion
                RustdeskIdServer      = $RustdeskIdServer
                RustdeskRelayServer   = $RustdeskRelayServer
                RustdeskKey           = $RustdeskKey
            }

            if (-not $RunNow) {
                $installParams.SkipRun = $true
            }

            & $installScript @installParams
        } -ArgumentList @(
            $RemoteStagePath,
            $Token,
            $Factory,
            $Department,
            $ServerUrl,
            $AgentVersion,
            $RustdeskIdServer,
            $RustdeskRelayServer,
            $RustdeskKey,
            [bool]$RunNow
        )

        $message = (($installOutput | Out-String).Trim())
        if (-not $message) {
            $message = "Installed"
        }

        $results += New-DeployResult -Computer $target -Status "success" -Message $message
        Write-Host "Success: $target" -ForegroundColor Green
    } catch {
        $message = $_.Exception.Message
        $results += New-DeployResult -Computer $target -Status "failed" -Message $message
        Write-Host "Failed: $target - $message" -ForegroundColor Red
    } finally {
        if ($session) {
            Remove-PSSession $session
        }
    }
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDir = Split-Path -Parent $resultFullPath
if ($resultDir -and -not (Test-Path $resultDir)) {
    New-Item -ItemType Directory -Path $resultDir -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
Write-Host "Deployment results saved to $resultFullPath" -ForegroundColor Cyan

$failedCount = @($results | Where-Object { $_.status -eq "failed" }).Count
if ($failedCount -gt 0) {
    exit 1
}
