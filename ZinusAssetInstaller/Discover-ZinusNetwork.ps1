param(
    [string[]]$IpSegment = @("10.62.38", "10.62.39", "10.62.36"),
    [int]$StartHost = 1,
    [int]$EndHost = 254,
    [int]$PingTimeoutMs = 750,
    [int]$PortTimeoutMs = 750,
    [int]$NbtstatTimeoutMs = 1000,
    [switch]$ProbeWsMan,
    [string]$ResultPath = ".\zinus-network-discovery-results.csv",
    [string]$OnlineResultPath = ".\zinus-network-discovery-online.csv"
)

$ErrorActionPreference = "Stop"

if ($StartHost -lt 1 -or $StartHost -gt 254 -or $EndHost -lt 1 -or $EndHost -gt 254 -or $StartHost -gt $EndHost) {
    throw "Range host tidak valid. Gunakan StartHost/EndHost antara 1 sampai 254."
}

function Normalize-IpSegments {
    param([string[]]$Segments)

    return @(
        foreach ($segment in @($Segments)) {
            ([string]$segment).Split(",") |
                ForEach-Object { $_.Trim() } |
                Where-Object { $_ }
        }
    )
}

$IpSegment = @(Normalize-IpSegments -Segments $IpSegment)

function Expand-IpSegment {
    param(
        [string]$Segment,
        [int]$StartHost,
        [int]$EndHost
    )

    $value = $Segment.Trim()
    if ($value -eq "") {
        return @()
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})$') {
        $prefix = $matches[1]
        return $StartHost..$EndHost | ForEach-Object { "$prefix.$_" }
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})\.0/24$') {
        $prefix = $matches[1]
        return $StartHost..$EndHost | ForEach-Object { "$prefix.$_" }
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})\.(\d{1,3})-(\d{1,3})$') {
        $prefix = $matches[1]
        $rangeStart = [int]$matches[2]
        $rangeEnd = [int]$matches[3]

        if ($rangeStart -gt $rangeEnd) {
            throw "IP range tidak valid: $Segment"
        }

        return $rangeStart..$rangeEnd | ForEach-Object { "$prefix.$_" }
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$') {
        return @($value)
    }

    throw "Format IP segment tidak didukung: $Segment. Contoh: 10.62.38, 10.62.38.0/24, atau 10.62.38.1-100."
}

function Test-Ping {
    param(
        [string]$Computer,
        [int]$TimeoutMs
    )

    $ping = New-Object System.Net.NetworkInformation.Ping
    try {
        $reply = $ping.Send($Computer, $TimeoutMs)
        return $reply.Status -eq [System.Net.NetworkInformation.IPStatus]::Success
    } catch {
        return $false
    } finally {
        $ping.Dispose()
    }
}

function Test-TcpPorts {
    param(
        [string]$Computer,
        [int[]]$Ports,
        [int]$TimeoutMs
    )

    $pending = @()

    try {
        foreach ($port in $Ports) {
            $client = New-Object System.Net.Sockets.TcpClient
            try {
                $async = $client.BeginConnect($Computer, $port, $null, $null)
                $pending += [pscustomobject]@{
                    port   = $port
                    client = $client
                    async  = $async
                }
            } catch {
                $client.Close()
            }
        }

        $deadline = [DateTime]::UtcNow.AddMilliseconds($TimeoutMs)
        while ($pending.Count -gt 0 -and [DateTime]::UtcNow -lt $deadline) {
            $unfinished = @($pending | Where-Object { -not $_.async.IsCompleted })
            if ($unfinished.Count -eq 0) {
                break
            }
            Start-Sleep -Milliseconds 25
        }

        $openPorts = @()
        foreach ($probe in $pending) {
            if (-not $probe.async.IsCompleted) {
                continue
            }

            try {
                $probe.client.EndConnect($probe.async)
                $openPorts += $probe.port
            } catch {}
        }

        return @($openPorts)
    } finally {
        foreach ($probe in $pending) {
            $probe.client.Close()
        }
    }
}

function Resolve-ComputerName {
    param(
        [string]$IpAddress,
        [int]$NetBiosTimeoutMs
    )

    try {
        $name = [System.Net.Dns]::GetHostEntry($IpAddress).HostName
        if ($name -and $name -ne $IpAddress) {
            return [pscustomobject]@{
                hostname = $name
                source   = "DNS"
            }
        }
    } catch {}

    $stdoutPath = [IO.Path]::GetTempFileName()
    $stderrPath = [IO.Path]::GetTempFileName()
    $process = $null

    try {
        $process = Start-Process `
            -FilePath "$env:SystemRoot\System32\nbtstat.exe" `
            -ArgumentList @("-A", $IpAddress) `
            -NoNewWindow `
            -PassThru `
            -RedirectStandardOutput $stdoutPath `
            -RedirectStandardError $stderrPath

        $finished = $process.WaitForExit($NetBiosTimeoutMs)
        if (-not $finished) {
            return [pscustomobject]@{ hostname = $null; source = $null }
        }

        $process.WaitForExit()
        $nbtstatOutput = Get-Content -Path $stdoutPath -ErrorAction SilentlyContinue
        foreach ($line in $nbtstatOutput) {
            if ($line -match '^\s*(\S+)\s+<00>\s+UNIQUE(?:\s+Registered)?\s*$') {
                return [pscustomobject]@{
                    hostname = $matches[1]
                    source   = "NetBIOS"
                }
            }
        }
    } catch {
    } finally {
        if ($process) {
            try {
                if (-not $process.HasExited) {
                    $process.Kill()
                }
            } catch {}
            try { $process.Dispose() } catch {}
        }
        Remove-Item -Path $stdoutPath, $stderrPath -Force -ErrorAction SilentlyContinue
    }

    return [pscustomobject]@{
        hostname = $null
        source   = $null
    }
}

function Get-MacFromNeighbor {
    param([string]$IpAddress)

    try {
        $neighbor = Get-NetNeighbor -IPAddress $IpAddress -ErrorAction SilentlyContinue |
            Where-Object { $_.LinkLayerAddress -and $_.LinkLayerAddress -ne "00-00-00-00-00-00" } |
            Select-Object -First 1

        if ($neighbor) {
            return [pscustomobject]@{
                mac_address = $neighbor.LinkLayerAddress
                state       = $neighbor.State
            }
        }
    } catch {}

    return [pscustomobject]@{
        mac_address = $null
        state       = $null
    }
}

$targets = @()
foreach ($segment in $IpSegment) {
    $targets += Expand-IpSegment -Segment $segment -StartHost $StartHost -EndHost $EndHost
}
$seenTargets = @{}
$targets = @(
    $targets | Where-Object {
        if ($seenTargets.ContainsKey($_)) {
            $false
        } else {
            $seenTargets[$_] = $true
            $true
        }
    }
)

Write-Host "Starting parallel network discovery on $($targets.Count) targets..." -ForegroundColor Cyan

# Create Runspace Pool
$sessionState = [System.Management.Automation.Runspaces.InitialSessionState]::CreateDefault()
$pool = [System.Management.Automation.Runspaces.RunspaceFactory]::CreateRunspacePool(1, 50, $sessionState, $Host)
$pool.Open()

# Target worker script
$workerScript = {
    param(
        [string]$target,
        [int]$PingTimeoutMs,
        [int]$PortTimeoutMs,
        [int]$NbtstatTimeoutMs,
        [bool]$ProbeWsMan
    )

    function Test-Ping {
        param(
            [string]$Computer,
            [int]$TimeoutMs
        )

        $ping = New-Object System.Net.NetworkInformation.Ping
        try {
            $reply = $ping.Send($Computer, $TimeoutMs)
            return $reply.Status -eq [System.Net.NetworkInformation.IPStatus]::Success
        } catch {
            return $false
        } finally {
            $ping.Dispose()
        }
    }

    function Test-TcpPorts {
        param(
            [string]$Computer,
            [int[]]$Ports,
            [int]$TimeoutMs
        )

        $pending = @()
        try {
            foreach ($port in $Ports) {
                $client = New-Object System.Net.Sockets.TcpClient
                try {
                    $async = $client.BeginConnect($Computer, $port, $null, $null)
                    $pending += [pscustomobject]@{
                        port   = $port
                        client = $client
                        async  = $async
                    }
                } catch {
                    $client.Close()
                }
            }

            $deadline = [DateTime]::UtcNow.AddMilliseconds($TimeoutMs)
            while ($pending.Count -gt 0 -and [DateTime]::UtcNow -lt $deadline) {
                $unfinished = @($pending | Where-Object { -not $_.async.IsCompleted })
                if ($unfinished.Count -eq 0) {
                    break
                }
                Start-Sleep -Milliseconds 25
            }

            $openPorts = @()
            foreach ($probe in $pending) {
                if (-not $probe.async.IsCompleted) {
                    continue
                }

                try {
                    $probe.client.EndConnect($probe.async)
                    $openPorts += $probe.port
                } catch {}
            }

            return @($openPorts)
        } finally {
            foreach ($probe in $pending) {
                $probe.client.Close()
            }
        }
    }

    function Resolve-ComputerName {
        param(
            [string]$IpAddress,
            [int]$NetBiosTimeoutMs
        )

        try {
            $name = [System.Net.Dns]::GetHostEntry($IpAddress).HostName
            if ($name -and $name -ne $IpAddress) {
                return [pscustomobject]@{
                    hostname = $name
                    source   = "DNS"
                }
            }
        } catch {}

        $stdoutPath = [IO.Path]::GetTempFileName()
        $stderrPath = [IO.Path]::GetTempFileName()
        $process = $null

        try {
            $process = Start-Process `
                -FilePath "$env:SystemRoot\System32\nbtstat.exe" `
                -ArgumentList @("-A", $IpAddress) `
                -NoNewWindow `
                -PassThru `
                -RedirectStandardOutput $stdoutPath `
                -RedirectStandardError $stderrPath

            $finished = $process.WaitForExit($NetBiosTimeoutMs)
            if (-not $finished) {
                return [pscustomobject]@{ hostname = $null; source = $null }
            }

            $process.WaitForExit()
            $nbtstatOutput = Get-Content -Path $stdoutPath -ErrorAction SilentlyContinue
            foreach ($line in $nbtstatOutput) {
                if ($line -match '^\s*(\S+)\s+<00>\s+UNIQUE(?:\s+Registered)?\s*$') {
                    return [pscustomobject]@{
                        hostname = $matches[1]
                        source   = "NetBIOS"
                    }
                }
            }
        } catch {
        } finally {
            if ($process) {
                try {
                    if (-not $process.HasExited) {
                        $process.Kill()
                    }
                } catch {}
                try { $process.Dispose() } catch {}
            }
            Remove-Item -Path $stdoutPath, $stderrPath -Force -ErrorAction SilentlyContinue
        }

        return [pscustomobject]@{
            hostname = $null
            source   = $null
        }
    }

    function Get-MacFromNeighbor {
        param([string]$IpAddress)

        try {
            $neighbor = Get-NetNeighbor -IPAddress $IpAddress -ErrorAction SilentlyContinue |
                Where-Object { $_.LinkLayerAddress -and $_.LinkLayerAddress -ne "00-00-00-00-00-00" } |
                Select-Object -First 1

            if ($neighbor) {
                return [pscustomobject]@{
                    mac_address = $neighbor.LinkLayerAddress
                    state       = $neighbor.State
                }
            }
        } catch {}

        return [pscustomobject]@{
            mac_address = $null
            state       = $null
        }
    }

    $pingOnline = $false
    try {
        $pingOnline = Test-Ping -Computer $target -TimeoutMs $PingTimeoutMs
    } catch {
        $pingOnline = $false
    }

    if ($pingOnline) {
        Start-Sleep -Milliseconds 25
    }

    $portsToProbe = @(135, 139, 445)
    if ($ProbeWsMan) {
        $portsToProbe += 5985
    }

    $openPorts = @(Test-TcpPorts -Computer $target -Ports $portsToProbe -TimeoutMs $PortTimeoutMs)
    $online = $pingOnline -or $openPorts.Count -gt 0

    $computerName = if ($online) {
        Resolve-ComputerName -IpAddress $target -NetBiosTimeoutMs $NbtstatTimeoutMs
    } else {
        [pscustomobject]@{ hostname = $null; source = $null }
    }

    $dnsName = if ($computerName.source -eq "DNS") { $computerName.hostname } else { $null }
    $neighbor = if ($online) { Get-MacFromNeighbor -IpAddress $target } else { [pscustomobject]@{ mac_address = $null; state = $null } }
    $wsmanOpen = if ($ProbeWsMan) { 5985 -in $openPorts } else { $null }

    $detectionMethod = if ($pingOnline) {
        "Ping"
    } elseif ($openPorts.Count -gt 0) {
        "TCP:$($openPorts -join ',')"
    } else {
        $null
    }

    return [pscustomobject]@{
        ip_address     = $target
        online         = $online
        hostname       = $computerName.hostname
        name_source    = $computerName.source
        detection      = $detectionMethod
        dns_name       = $dnsName
        mac_address    = $neighbor.mac_address
        neighbor_state = $neighbor.state
        wsman_5985     = $wsmanOpen
        discovered_at  = (Get-Date).ToString("s")
    }
}

$runspaces = @()
foreach ($target in $targets) {
    $pipeline = [System.Management.Automation.PowerShell]::Create()
    $pipeline.RunspacePool = $pool

    $pipeline.AddScript($workerScript) | Out-Null
    $pipeline.AddArgument($target) | Out-Null
    $pipeline.AddArgument($PingTimeoutMs) | Out-Null
    $pipeline.AddArgument($PortTimeoutMs) | Out-Null
    $pipeline.AddArgument($NbtstatTimeoutMs) | Out-Null
    $pipeline.AddArgument($ProbeWsMan) | Out-Null

    $runspaces += [pscustomobject]@{
        Target      = $target
        PowerShell  = $pipeline
        AsyncResult = $pipeline.BeginInvoke()
    }
}

# Wait for completion and collect results
$results = @()
$totalCount = $targets.Count
$completedCount = 0

while ($runspaces.Count -gt 0) {
    $completed = @($runspaces | Where-Object { $_.AsyncResult.IsCompleted })
    foreach ($r in $completed) {
        $completedCount++
        Write-Progress -Activity "Zinus Network Discovery" -Status "Scanned $($r.Target) ($completedCount/$totalCount)" -PercentComplete (($completedCount / $totalCount) * 100)
        
        try {
            $output = $r.PowerShell.EndInvoke($r.AsyncResult)
            if ($output) {
                $results += $output
            }
        } catch {
            # Log error internally but keep running
        } finally {
            $r.PowerShell.Dispose()
        }
    }
    # Remove only jobs collected in this iteration. A job can finish between
    # the completed snapshot above and this filter; checking IsCompleted again
    # would drop that result without ever calling EndInvoke.
    $runspaces = @($runspaces | Where-Object { $completed -notcontains $_ })
    if ($runspaces.Count -gt 0) {
        Start-Sleep -Milliseconds 100
    }
}
$pool.Close()

Write-Progress -Activity "Zinus Network Discovery" -Completed


$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDir = Split-Path -Parent $resultFullPath
if ($resultDir -and -not (Test-Path $resultDir)) {
    New-Item -ItemType Directory -Path $resultDir -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8

$onlineResultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($OnlineResultPath)
$onlineResultDir = Split-Path -Parent $onlineResultFullPath
if ($onlineResultDir -and -not (Test-Path $onlineResultDir)) {
    New-Item -ItemType Directory -Path $onlineResultDir -Force | Out-Null
}

$results |
    Where-Object { $_.online } |
    Select-Object ip_address, hostname, name_source, detection, mac_address, neighbor_state, wsman_5985, discovered_at |
    Export-Csv -Path $onlineResultFullPath -NoTypeInformation -Encoding UTF8

$onlineCount = @($results | Where-Object { $_.online }).Count
$hostnameCount = @($results | Where-Object { $_.hostname }).Count
$wsmanCount = @($results | Where-Object { $_.wsman_5985 -eq $true }).Count

Write-Host "Discovery selesai. Online: $onlineCount / $($results.Count). Hostname ditemukan: $hostnameCount. WinRM open: $wsmanCount." -ForegroundColor Green
Write-Host "Hasil disimpan ke $resultFullPath" -ForegroundColor Cyan
Write-Host "Perangkat online disimpan ke $onlineResultFullPath" -ForegroundColor Cyan
