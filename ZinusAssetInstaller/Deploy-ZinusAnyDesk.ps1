param(
    [string[]]$ComputerName = @(),
    [string]$ComputerList = "",
    [Parameter(Mandatory = $true)]
    [string]$InstallerPath,
    [string]$RemoteStagePath = "C:\ProgramData\ZinusAnyDeskDeploy",
    [string]$ResultPath = ".\zinus-anydesk-deploy-results.csv",
    [ValidateRange(1, 20)]
    [int]$MaxParallel = 8,
    [Parameter(Mandatory = $true)]
    [System.Management.Automation.PSCredential]$Credential,
    [switch]$NoFailExit
)

$ErrorActionPreference = "Stop"

function Resolve-TargetComputers {
    $targets = @($ComputerName)

    if ($ComputerList) {
        if (-not (Test-Path $ComputerList)) {
            throw "Computer list tidak ditemukan: $ComputerList"
        }

        $targets += Get-Content -LiteralPath $ComputerList |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    }

    return @($targets | Where-Object { $_ } | Sort-Object -Unique)
}

function Test-ThirdPartyDownloadWrapper {
    param([string]$Path)

    $item = Get-Item -LiteralPath $Path
    $versionInfo = $item.VersionInfo
    $signature = Get-AuthenticodeSignature -LiteralPath $Path -ErrorAction SilentlyContinue
    $metadata = @(
        $versionInfo.ProductName,
        $versionInfo.CompanyName,
        $versionInfo.FileDescription,
        $versionInfo.OriginalFilename,
        $(if ($signature.SignerCertificate) { $signature.SignerCertificate.Subject } else { "" })
    ) -join " "

    return ($metadata -match '(?i)softonic|uptodown|filehorse|softpedia')
}

$installerFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($InstallerPath)
if (-not (Test-Path -LiteralPath $installerFullPath -PathType Leaf)) {
    throw "Installer AnyDesk tidak ditemukan: $installerFullPath"
}

$installerExtension = [IO.Path]::GetExtension($installerFullPath).ToLowerInvariant()
if ($installerExtension -notin @(".exe", ".msi")) {
    throw "Installer AnyDesk harus berupa file .exe atau .msi."
}

$targets = @(Resolve-TargetComputers)
if ($targets.Count -eq 0) {
    throw "Tidak ada target AnyDesk. Gunakan -ComputerName atau -ComputerList."
}

if (Test-ThirdPartyDownloadWrapper -Path $installerFullPath) {
    $message = "Installer AnyDesk terlihat seperti downloader/wrapper pihak ketiga. Download installer resmi AnyDesk lalu taruh di folder installer."

    if (-not $NoFailExit) {
        throw $message
    }

    $resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
    $resultDirectory = Split-Path -Parent $resultFullPath
    if ($resultDirectory -and -not (Test-Path $resultDirectory)) {
        New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
    }

    $results = @(
        foreach ($target in $targets) {
            [pscustomobject]@{
                computer    = $target
                status      = "failed"
                action      = "none"
                version     = ""
                anydesk_id  = ""
                message     = $message
                deployed_at = (Get-Date).ToString("s")
            }
        }
    )

    $results | Sort-Object computer | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
    Write-Host $message -ForegroundColor Yellow
    Write-Host "AnyDesk deployment dilewati. Gagal: $($results.Count)." -ForegroundColor Yellow
    Write-Host "Hasil: $resultFullPath" -ForegroundColor Cyan
    return
}

$installerHash = (Get-FileHash -LiteralPath $installerFullPath -Algorithm SHA256).Hash
Write-Host "AnyDesk installer : $installerFullPath" -ForegroundColor Cyan
Write-Host "SHA256            : $installerHash" -ForegroundColor DarkGray
Write-Host "Target            : $($targets.Count) PC (parallel: $MaxParallel)" -ForegroundColor Cyan

$workerScript = {
    param(
        [string]$Target,
        [System.Management.Automation.PSCredential]$Credential,
        [string]$InstallerPath,
        [string]$InstallerExtension,
        [string]$RemoteStagePath
    )

    $ErrorActionPreference = "Stop"

    function New-AnyDeskResult {
        param(
            [string]$Computer,
            [string]$Status,
            [string]$Action,
            [string]$Message,
            [string]$Version = "",
            [string]$AnyDeskId = ""
        )

        [pscustomobject]@{
            computer    = $Computer
            status      = $Status
            action      = $Action
            version     = $Version
            anydesk_id  = $AnyDeskId
            message     = $Message
            deployed_at = (Get-Date).ToString("s")
        }
    }

    $session = $null
    try {
        $sessionOption = New-PSSessionOption `
            -OpenTimeout 20000 `
            -OperationTimeout 120000 `
            -CancelTimeout 5000
        $session = New-PSSession `
            -ComputerName $Target `
            -Credential $Credential `
            -SessionOption $sessionOption `
            -ErrorAction Stop

        $detectScript = {
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

                $paths += Get-ChildItem -LiteralPath $root -Directory -Filter "AnyDesk*" -ErrorAction SilentlyContinue |
                    ForEach-Object {
                        Get-ChildItem -LiteralPath $_.FullName -File -Filter "AnyDesk*.exe" -ErrorAction SilentlyContinue |
                            Select-Object -ExpandProperty FullName
                    }
            }

            foreach ($path in @($paths | Where-Object { $_ } | Select-Object -Unique)) {
                if (Test-Path -LiteralPath $path) {
                    return $path
                }
            }

            return $null
        }

        $anyDeskExe = Invoke-Command -Session $session -ScriptBlock $detectScript -ErrorAction Stop
        $action = "already_installed"

        if (-not $anyDeskExe) {
            Invoke-Command -Session $session -ScriptBlock {
                param([string]$StagePath)
                New-Item -ItemType Directory -Path $StagePath -Force | Out-Null
            } -ArgumentList $RemoteStagePath -ErrorAction Stop

            $remoteInstaller = Join-Path $RemoteStagePath ([IO.Path]::GetFileName($InstallerPath))
            Copy-Item -ToSession $session -LiteralPath $InstallerPath -Destination $remoteInstaller -Force -ErrorAction Stop

            Invoke-Command -Session $session -ScriptBlock {
                param(
                    [string]$RemoteInstaller,
                    [string]$Extension
                )

                if ($Extension -eq ".msi") {
                    $process = Start-Process -FilePath "$env:SystemRoot\System32\msiexec.exe" `
                        -ArgumentList @("/i", "`"$RemoteInstaller`"", "/qn", "/norestart") `
                        -Wait -PassThru
                    if ($process.ExitCode -notin @(0, 3010)) {
                        throw "msiexec gagal dengan exit code $($process.ExitCode)."
                    }
                } else {
                    $installArgs = '--install "C:\Program Files (x86)\AnyDesk" --start-with-win --silent'
                    $process = Start-Process -FilePath $RemoteInstaller `
                        -ArgumentList $installArgs `
                        -Wait -PassThru
                    if ($process.ExitCode -notin @(0, 3010)) {
                        throw "AnyDesk installer gagal dengan exit code $($process.ExitCode)."
                    }
                }

                $deadline = (Get-Date).AddSeconds(90)
                do {
                    $installedPath = @(
                        "$env:ProgramFiles\AnyDesk\AnyDesk.exe",
                        "${env:ProgramFiles(x86)}\AnyDesk\AnyDesk.exe"
                    ) | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1

                    if ($installedPath) {
                        return $installedPath
                    }

                    Start-Sleep -Seconds 2
                } while ((Get-Date) -lt $deadline)

                throw "Instalasi selesai tetapi AnyDesk.exe tidak ditemukan setelah 90 detik."
            } -ArgumentList $remoteInstaller, $InstallerExtension -ErrorAction Stop | Out-Null

            $anyDeskExe = Invoke-Command -Session $session -ScriptBlock $detectScript -ErrorAction Stop
            if (-not $anyDeskExe) {
                throw "AnyDesk.exe tidak ditemukan setelah instalasi."
            }

            $action = "installed"
        }

        $details = Invoke-Command -Session $session -ScriptBlock {
            param([string]$AnyDeskExe)

            try {
                & $AnyDeskExe --start 2>$null | Out-Null
            } catch {}

            $service = Get-Service -ErrorAction SilentlyContinue |
                Where-Object { $_.Name -like "AnyDesk*" -or $_.DisplayName -like "AnyDesk*" } |
                Select-Object -First 1
            if ($service -and $service.Status -ne "Running") {
                Start-Service -Name $service.Name -ErrorAction SilentlyContinue
            }

            Start-Sleep -Seconds 3

            $id = ""
            try {
                $output = & $AnyDeskExe --get-id 2>&1 | Out-String
                if ($output -match '([0-9]{3}\s?[0-9]{3}\s?[0-9]{3,4})') {
                    $id = ($matches[1] -replace '\s+', '')
                }
            } catch {}

            [pscustomobject]@{
                version = (Get-Item -LiteralPath $AnyDeskExe).VersionInfo.ProductVersion
                id      = $id
            }
        } -ArgumentList $anyDeskExe -ErrorAction Stop

        try {
            Invoke-Command -Session $session -ScriptBlock {
                param([string]$StagePath)
                Remove-Item -LiteralPath $StagePath -Recurse -Force -ErrorAction SilentlyContinue
            } -ArgumentList $RemoteStagePath -ErrorAction SilentlyContinue
        } catch {}

        return New-AnyDeskResult `
            -Computer $Target `
            -Status "success" `
            -Action $action `
            -Message "AnyDesk siap." `
            -Version ([string]$details.version) `
            -AnyDeskId ([string]$details.id)
    } catch {
        return New-AnyDeskResult `
            -Computer $Target `
            -Status "failed" `
            -Action "none" `
            -Message $_.Exception.Message
    } finally {
        if ($session) {
            Remove-PSSession $session -ErrorAction SilentlyContinue
        }
    }
}

$sessionState = [System.Management.Automation.Runspaces.InitialSessionState]::CreateDefault()
$pool = [System.Management.Automation.Runspaces.RunspaceFactory]::CreateRunspacePool(1, $MaxParallel, $sessionState, $Host)
$pool.Open()

$runspaces = @()
foreach ($target in $targets) {
    $pipeline = [System.Management.Automation.PowerShell]::Create()
    $pipeline.RunspacePool = $pool
    $pipeline.AddScript($workerScript) | Out-Null
    $pipeline.AddArgument($target) | Out-Null
    $pipeline.AddArgument($Credential) | Out-Null
    $pipeline.AddArgument($installerFullPath) | Out-Null
    $pipeline.AddArgument($installerExtension) | Out-Null
    $pipeline.AddArgument($RemoteStagePath) | Out-Null

    $runspaces += [pscustomobject]@{
        Target      = $target
        PowerShell  = $pipeline
        AsyncResult = $pipeline.BeginInvoke()
    }
}

$results = @()
$completedCount = 0
while ($runspaces.Count -gt 0) {
    $completed = @($runspaces | Where-Object { $_.AsyncResult.IsCompleted })
    foreach ($runspace in $completed) {
        $completedCount++
        try {
            $output = $runspace.PowerShell.EndInvoke($runspace.AsyncResult)
            if ($output) {
                $result = $output | Select-Object -First 1
            } else {
                $result = [pscustomobject]@{
                    computer    = $runspace.Target
                    status      = "failed"
                    action      = "none"
                    version     = ""
                    anydesk_id  = ""
                    message     = "Worker tidak menghasilkan data."
                    deployed_at = (Get-Date).ToString("s")
                }
            }
        } catch {
            $result = [pscustomobject]@{
                computer    = $runspace.Target
                status      = "failed"
                action      = "none"
                version     = ""
                anydesk_id  = ""
                message     = $_.Exception.Message
                deployed_at = (Get-Date).ToString("s")
            }
        } finally {
            $runspace.PowerShell.Dispose()
        }

        $results += $result
        $color = if ($result.status -eq "success") { "Green" } else { "Red" }
        Write-Host "[$completedCount/$($targets.Count)] $($runspace.Target): $($result.status.ToUpper()) - $($result.action) - $($result.message)" -ForegroundColor $color
    }

    $runspaces = @($runspaces | Where-Object { $completed -notcontains $_ })
    if ($runspaces.Count -gt 0) {
        Start-Sleep -Milliseconds 200
    }
}

$pool.Close()
$pool.Dispose()

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$results | Sort-Object computer | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8

$successCount = @($results | Where-Object { $_.status -eq "success" }).Count
$installedCount = @($results | Where-Object { $_.action -like "installed*" }).Count
$failedCount = @($results | Where-Object { $_.status -eq "failed" }).Count

Write-Host "AnyDesk deployment selesai. Siap: $successCount, baru diinstal: $installedCount, gagal: $failedCount." -ForegroundColor Cyan
Write-Host "Hasil: $resultFullPath" -ForegroundColor Cyan

if ($failedCount -gt 0 -and -not $NoFailExit) {
    exit 1
}
