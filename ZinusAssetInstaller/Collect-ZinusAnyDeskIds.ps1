param(
    [string[]]$ComputerName = @(),
    [string]$ComputerList = "",
    [string]$VerificationPath = ".\zinus-auto-verification.csv",
    [string]$ExcludeDeviceListPath = ".\zinus-remediation-non-windows-devices.csv",
    [string]$ResultPath = ".\zinus-anydesk-id-results.csv",
    [ValidateRange(1, 30)]
    [int]$MaxParallel = 12,
    [ValidateRange(10, 600)]
    [int]$TargetTimeoutSeconds = 45,
    [switch]$SkipSmb,
    [switch]$SkipWinRM,
    [switch]$IncludeNonWindows,
    [switch]$IncludeLocalHost,
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

if (-not $Credential) {
    $Credential = Get-Credential -Message "Credential admin target untuk baca AnyDesk ID"
}

function Get-LocalIPv4Address {
    try {
        return @(
            Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop |
                Where-Object {
                    $_.IPAddress -and
                    $_.IPAddress -ne "127.0.0.1" -and
                    $_.IPAddress -notlike "169.254.*"
                } |
                Select-Object -ExpandProperty IPAddress -Unique
        )
    } catch {
        return @(
            [System.Net.Dns]::GetHostAddresses([System.Net.Dns]::GetHostName()) |
                Where-Object { $_.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork } |
                ForEach-Object { $_.IPAddressToString } |
                Where-Object { $_ -ne "127.0.0.1" -and $_ -notlike "169.254.*" } |
                Select-Object -Unique
        )
    }
}

function Resolve-AnyDeskTargets {
    $targets = @()

    if ($ComputerName) {
        $targets += $ComputerName
    }

    if ($ComputerList) {
        if (-not (Test-Path -LiteralPath $ComputerList)) {
            throw "Computer list tidak ditemukan: $ComputerList"
        }

        $targets += Get-Content -LiteralPath $ComputerList |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    }

    if ($targets.Count -eq 0 -and (Test-Path -LiteralPath $VerificationPath)) {
        $targets += Import-Csv -LiteralPath $VerificationPath |
            Where-Object { $_.online -eq "True" } |
            Select-Object -ExpandProperty ip_address
    }

    if (-not $IncludeNonWindows -and (Test-Path -LiteralPath $ExcludeDeviceListPath)) {
        $excludeIps = @(
            Import-Csv -LiteralPath $ExcludeDeviceListPath |
                Where-Object { $_.ip_address -and $_.likely_device_type -in @("Printer", "NAS / storage", "Network device / gateway", "CCTV / camera") } |
                Select-Object -ExpandProperty ip_address -Unique
        )

        if ($excludeIps.Count -gt 0) {
            $targets = @($targets | Where-Object { $_ -notin $excludeIps })
        }
    }

    if (-not $IncludeLocalHost) {
        $localIps = @(Get-LocalIPv4Address)
        if ($localIps.Count -gt 0) {
            $targets = @($targets | Where-Object { $_ -notin $localIps })
        }
    }

    return @($targets | Where-Object { $_ } | Sort-Object -Unique)
}

$workerScript = {
    param(
        [string]$Target,
        [System.Management.Automation.PSCredential]$Credential,
        [bool]$SkipSmb,
        [bool]$SkipWinRM
    )

    $ErrorActionPreference = "Stop"

    function New-AnyDeskIdResult {
        param(
            [string]$Computer,
            [string]$Status,
            [string]$Message,
            [string]$AnyDeskId = "",
            [string]$AnyDeskAlias = "",
            [string]$Method = "",
            [string]$Source = ""
        )

        [pscustomobject]@{
            computer      = $Computer
            status        = $Status
            anydesk_id    = $AnyDeskId
            anydesk_alias = $AnyDeskAlias
            method        = $Method
            source        = $Source
            message       = $Message
            collected_at  = (Get-Date).ToString("s")
        }
    }

    function Test-TcpPort {
        param(
            [string]$Computer,
            [int]$Port,
            [int]$TimeoutMs
        )

        $client = New-Object System.Net.Sockets.TcpClient
        try {
            $async = $client.BeginConnect($Computer, $Port, $null, $null)
            if (-not $async.AsyncWaitHandle.WaitOne($TimeoutMs, $false)) {
                return $false
            }

            $client.EndConnect($async)
            return $true
        } catch {
            return $false
        } finally {
            $client.Close()
        }
    }

    function Get-AnyDeskIdFromText {
        param([string]$Text)

        if ([string]::IsNullOrWhiteSpace($Text)) { return "" }

        $patterns = @(
            '(?im)^\s*ad\.anynet\.id\s*=\s*([0-9 ]{6,})\s*$',
            '(?im)^\s*anynet\.id\s*=\s*([0-9 ]{6,})\s*$',
            '(?im)^\s*id\s*=\s*([0-9 ]{6,})\s*$'
        )

        foreach ($pattern in $patterns) {
            if ($Text -match $pattern) {
                $id = ($matches[1] -replace '\s+', '')
                if ($id -match '^\d{6,12}$') { return $id }
            }
        }

        return ""
    }

    function Get-AnyDeskAliasFromText {
        param([string]$Text)

        if ([string]::IsNullOrWhiteSpace($Text)) { return "" }

        $patterns = @(
            '(?im)^\s*ad\.anynet\.alias\s*=\s*(.+?)\s*$',
            '(?im)^\s*anynet\.alias\s*=\s*(.+?)\s*$',
            '(?im)^\s*alias\s*=\s*(.+?)\s*$'
        )

        foreach ($pattern in $patterns) {
            if ($Text -match $pattern) {
                $alias = ([string]$matches[1]).Trim()
                if ($alias -match "=" -or $alias -match "(?i)^(ad\.|anynet\.)") { return "" }
                return $alias
            }
        }

        return ""
    }

    function Read-AnyDeskConfigFromRoot {
        param([string]$RootPath)

        $relativePaths = @(
            "ProgramData\AnyDesk\service.conf",
            "ProgramData\AnyDesk\system.conf",
            "ProgramData\AnyDesk\ad_svc.conf"
        )

        foreach ($relativePath in $relativePaths) {
            $path = Join-Path $RootPath $relativePath
            if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
                continue
            }

            try {
                $text = Get-Content -LiteralPath $path -Raw -ErrorAction Stop
                $id = Get-AnyDeskIdFromText -Text $text
                $alias = Get-AnyDeskAliasFromText -Text $text
                if ($id -or $alias) {
                    return [pscustomobject]@{
                        id     = $id
                        alias  = $alias
                        source = $path
                    }
                }
            } catch {}
        }

        return $null
    }

    function Get-AnyDeskIdViaSmb {
        if ($SkipSmb) { return $null }
        if (-not (Test-TcpPort -Computer $Target -Port 445 -TimeoutMs 1500)) {
            return $null
        }

        $driveName = "ZAD" + ([guid]::NewGuid().ToString("N").Substring(0, 8))
        try {
            $drive = New-PSDrive -Name $driveName -PSProvider FileSystem -Root "\\$Target\C$" -Credential $Credential -ErrorAction Stop
            try {
                return Read-AnyDeskConfigFromRoot -RootPath "$driveName`:\"
            } finally {
                Remove-PSDrive -Name $driveName -Force -ErrorAction SilentlyContinue
            }
        } catch {
            return $null
        }
    }

    function Invoke-AnyDeskCli {
        param([string]$Path)

        if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
            return ""
        }

        $stdoutPath = [IO.Path]::GetTempFileName()
        $stderrPath = [IO.Path]::GetTempFileName()
        $process = $null
        try {
            $process = Start-Process `
                -FilePath $Path `
                -ArgumentList "--get-id" `
                -NoNewWindow `
                -PassThru `
                -RedirectStandardOutput $stdoutPath `
                -RedirectStandardError $stderrPath

            if (-not $process.WaitForExit(10000)) {
                try { $process.Kill() } catch {}
                return ""
            }

            $process.WaitForExit()
            $output = @(
                Get-Content -LiteralPath $stdoutPath -ErrorAction SilentlyContinue
                Get-Content -LiteralPath $stderrPath -ErrorAction SilentlyContinue
            ) -join " "

            if ($output -match '([0-9]{3}\s?[0-9]{3}\s?[0-9]{3,4})') {
                return ($matches[1] -replace '\s+', '')
            }
        } catch {
            return ""
        } finally {
            if ($process) { try { $process.Dispose() } catch {} }
            Remove-Item -LiteralPath $stdoutPath, $stderrPath -Force -ErrorAction SilentlyContinue
        }

        return ""
    }

    function Get-AnyDeskIdViaWinRM {
        if ($SkipWinRM) { return $null }
        if (-not (Test-TcpPort -Computer $Target -Port 5985 -TimeoutMs 1500)) {
            return $null
        }

        $session = $null
        try {
            $sessionOption = New-PSSessionOption `
                -OpenTimeout 8000 `
                -OperationTimeout 25000 `
                -CancelTimeout 3000 `
                -MaxConnectionRetryCount 0 `
                -NoMachineProfile

            $session = New-PSSession -ComputerName $Target -Credential $Credential -SessionOption $sessionOption -ErrorAction Stop
            $remoteResult = Invoke-Command -Session $session -ScriptBlock {
                function Get-AnyDeskIdFromText {
                    param([string]$Text)
                    if ([string]::IsNullOrWhiteSpace($Text)) { return "" }
                    foreach ($pattern in @(
                        '(?im)^\s*ad\.anynet\.id\s*=\s*([0-9 ]{6,})\s*$',
                        '(?im)^\s*anynet\.id\s*=\s*([0-9 ]{6,})\s*$',
                        '(?im)^\s*id\s*=\s*([0-9 ]{6,})\s*$'
                    )) {
                        if ($Text -match $pattern) {
                            $id = ($matches[1] -replace '\s+', '')
                            if ($id -match '^\d{6,12}$') { return $id }
                        }
                    }
                    return ""
                }

                function Get-AnyDeskAliasFromText {
                    param([string]$Text)
                    if ([string]::IsNullOrWhiteSpace($Text)) { return "" }
                    foreach ($pattern in @(
                        '(?im)^\s*ad\.anynet\.alias\s*=\s*(.+?)\s*$',
                        '(?im)^\s*anynet\.alias\s*=\s*(.+?)\s*$',
                        '(?im)^\s*alias\s*=\s*(.+?)\s*$'
                    )) {
                        if ($Text -match $pattern) {
                            $alias = ([string]$matches[1]).Trim()
                            if ($alias -match "=" -or $alias -match "(?i)^(ad\.|anynet\.)") { return "" }
                            return $alias
                        }
                    }
                    return ""
                }

                function Invoke-AnyDeskCli {
                    param([string]$Path)
                    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) { return "" }
                    $stdoutPath = [IO.Path]::GetTempFileName()
                    $stderrPath = [IO.Path]::GetTempFileName()
                    $process = $null
                    try {
                        $process = Start-Process -FilePath $Path -ArgumentList "--get-id" -NoNewWindow -PassThru -RedirectStandardOutput $stdoutPath -RedirectStandardError $stderrPath
                        if (-not $process.WaitForExit(10000)) {
                            try { $process.Kill() } catch {}
                            return ""
                        }
                        $process.WaitForExit()
                        $output = @(
                            Get-Content -LiteralPath $stdoutPath -ErrorAction SilentlyContinue
                            Get-Content -LiteralPath $stderrPath -ErrorAction SilentlyContinue
                        ) -join " "
                        if ($output -match '([0-9]{3}\s?[0-9]{3}\s?[0-9]{3,4})') {
                            return ($matches[1] -replace '\s+', '')
                        }
                    } catch {
                        return ""
                    } finally {
                        if ($process) { try { $process.Dispose() } catch {} }
                        Remove-Item -LiteralPath $stdoutPath, $stderrPath -Force -ErrorAction SilentlyContinue
                    }
                    return ""
                }

                $configPaths = @(
                    "C:\ProgramData\AnyDesk\service.conf",
                    "C:\ProgramData\AnyDesk\system.conf",
                    "C:\ProgramData\AnyDesk\ad_svc.conf"
                )

                foreach ($path in $configPaths) {
                    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { continue }
                    try {
                        $text = Get-Content -LiteralPath $path -Raw -ErrorAction Stop
                        $id = Get-AnyDeskIdFromText -Text $text
                        $alias = Get-AnyDeskAliasFromText -Text $text
                        if ($id -or $alias) {
                            return [pscustomobject]@{
                                id     = $id
                                alias  = $alias
                                source = $path
                            }
                        }
                    } catch {}
                }

                $exePaths = @(
                    "$env:ProgramFiles\AnyDesk\AnyDesk.exe",
                    "${env:ProgramFiles(x86)}\AnyDesk\AnyDesk.exe",
                    "C:\Program Files\AnyDesk\AnyDesk.exe",
                    "C:\Program Files (x86)\AnyDesk\AnyDesk.exe"
                ) | Where-Object { $_ } | Select-Object -Unique

                foreach ($path in $exePaths) {
                    $id = Invoke-AnyDeskCli -Path $path
                    if ($id) {
                        return [pscustomobject]@{
                            id     = $id
                            alias  = ""
                            source = $path
                        }
                    }
                }

                return $null
            } -ErrorAction Stop

            return $remoteResult | Select-Object -First 1
        } catch {
            return $null
        } finally {
            if ($session) {
                Remove-PSSession $session -ErrorAction SilentlyContinue
            }
        }
    }

    try {
        $smbResult = Get-AnyDeskIdViaSmb
        if ($smbResult -and ($smbResult.id -or $smbResult.alias)) {
            return New-AnyDeskIdResult `
                -Computer $Target `
                -Status "success" `
                -Method "smb_config" `
                -AnyDeskId ([string]$smbResult.id) `
                -AnyDeskAlias ([string]$smbResult.alias) `
                -Source ([string]$smbResult.source) `
                -Message "AnyDesk ID dibaca dari config."
        }

        $winRmResult = Get-AnyDeskIdViaWinRM
        if ($winRmResult -and ($winRmResult.id -or $winRmResult.alias)) {
            return New-AnyDeskIdResult `
                -Computer $Target `
                -Status "success" `
                -Method "winrm" `
                -AnyDeskId ([string]$winRmResult.id) `
                -AnyDeskAlias ([string]$winRmResult.alias) `
                -Source ([string]$winRmResult.source) `
                -Message "AnyDesk ID dibaca dari target."
        }

        return New-AnyDeskIdResult -Computer $Target -Status "failed" -Message "AnyDesk ID tidak kebaca via SMB config maupun WinRM."
    } catch {
        return New-AnyDeskIdResult -Computer $Target -Status "failed" -Message $_.Exception.Message
    }
}

$targets = @(Resolve-AnyDeskTargets)
if ($targets.Count -eq 0) {
    throw "Tidak ada target. Pakai -ComputerName/-ComputerList atau pastikan $VerificationPath ada."
}

Write-Host "Collect AnyDesk ID target: $($targets.Count) PC (parallel: $MaxParallel)" -ForegroundColor Cyan
Write-Host "Timeout per target       : $TargetTimeoutSeconds detik" -ForegroundColor DarkGray
Write-Host "SMB config               : $(if ($SkipSmb) { 'skip' } else { 'enabled' })" -ForegroundColor DarkGray
Write-Host "WinRM fallback           : $(if ($SkipWinRM) { 'skip' } else { 'enabled' })" -ForegroundColor DarkGray

$sessionState = [System.Management.Automation.Runspaces.InitialSessionState]::CreateDefault()
$pool = [System.Management.Automation.Runspaces.RunspaceFactory]::CreateRunspacePool(1, $MaxParallel, $sessionState, $Host)
$pool.Open()

function Start-AnyDeskIdWorker {
    param([string]$Target)

    $pipeline = [System.Management.Automation.PowerShell]::Create()
    $pipeline.RunspacePool = $pool
    $pipeline.AddScript($workerScript) | Out-Null
    $pipeline.AddArgument($Target) | Out-Null
    $pipeline.AddArgument($Credential) | Out-Null
    $pipeline.AddArgument([bool]$SkipSmb) | Out-Null
    $pipeline.AddArgument([bool]$SkipWinRM) | Out-Null

    return [pscustomobject]@{
        Target      = $Target
        PowerShell  = $pipeline
        AsyncResult = $pipeline.BeginInvoke()
        StartedAt   = Get-Date
    }
}

$pendingTargets = New-Object System.Collections.Queue
foreach ($target in $targets) {
    $pendingTargets.Enqueue($target)
}

$runspaces = @()
$results = @()
$completedCount = 0

try {
    while ($runspaces.Count -gt 0 -or $pendingTargets.Count -gt 0) {
        while ($runspaces.Count -lt $MaxParallel -and $pendingTargets.Count -gt 0) {
            $runspaces += Start-AnyDeskIdWorker -Target ([string]$pendingTargets.Dequeue())
        }

        $completed = @($runspaces | Where-Object { $_.AsyncResult.IsCompleted })
        foreach ($runspace in $completed) {
            $completedCount++
            try {
                $output = $runspace.PowerShell.EndInvoke($runspace.AsyncResult)
                $result = if ($output) {
                    $output | Select-Object -First 1
                } else {
                    [pscustomobject]@{
                        computer      = $runspace.Target
                        status        = "failed"
                        anydesk_id    = ""
                        anydesk_alias = ""
                        method        = ""
                        source        = ""
                        message       = "Worker tidak menghasilkan data."
                        collected_at  = (Get-Date).ToString("s")
                    }
                }
            } catch {
                $result = [pscustomobject]@{
                    computer      = $runspace.Target
                    status        = "failed"
                    anydesk_id    = ""
                    anydesk_alias = ""
                    method        = ""
                    source        = ""
                    message       = $_.Exception.Message
                    collected_at  = (Get-Date).ToString("s")
                }
            } finally {
                $runspace.PowerShell.Dispose()
            }

            $results += $result
            $color = if ($result.status -eq "success") { "Green" } else { "Red" }
            $idText = if ($result.anydesk_id) { $result.anydesk_id } elseif ($result.anydesk_alias) { $result.anydesk_alias } else { "-" }
            Write-Host "[$completedCount/$($targets.Count)] $($runspace.Target): $($result.status.ToUpper()) - $idText - $($result.message)" -ForegroundColor $color
        }

        $runspaces = @($runspaces | Where-Object { $completed -notcontains $_ })

        $now = Get-Date
        $timedOut = @($runspaces | Where-Object { ($now - $_.StartedAt).TotalSeconds -ge $TargetTimeoutSeconds })
        foreach ($runspace in $timedOut) {
            $completedCount++
            try {
                $runspace.PowerShell.Stop()
            } catch {
            } finally {
                $runspace.PowerShell.Dispose()
            }

            $result = [pscustomobject]@{
                computer      = $runspace.Target
                status        = "failed"
                anydesk_id    = ""
                anydesk_alias = ""
                method        = ""
                source        = ""
                message       = "Timeout setelah $TargetTimeoutSeconds detik. Target dilewati."
                collected_at  = (Get-Date).ToString("s")
            }

            $results += $result
            Write-Host "[$completedCount/$($targets.Count)] $($runspace.Target): FAILED - - - $($result.message)" -ForegroundColor Red
        }

        $runspaces = @($runspaces | Where-Object { $timedOut -notcontains $_ })
        if ($runspaces.Count -gt 0 -or $pendingTargets.Count -gt 0) {
            Start-Sleep -Milliseconds 200
        }
    }
} finally {
    $pool.Close()
    $pool.Dispose()
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDirectory = Split-Path -Parent $resultFullPath
if ($resultDirectory -and -not (Test-Path -LiteralPath $resultDirectory)) {
    New-Item -ItemType Directory -Path $resultDirectory -Force | Out-Null
}

$results | Sort-Object computer | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
$successCount = @($results | Where-Object { $_.status -eq "success" }).Count
$failedCount = @($results | Where-Object { $_.status -eq "failed" }).Count
Write-Host "Collect AnyDesk ID selesai. Success: $successCount, failed: $failedCount." -ForegroundColor Cyan
Write-Host "Hasil: $resultFullPath" -ForegroundColor Cyan
