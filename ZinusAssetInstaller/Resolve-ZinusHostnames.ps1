param(
    [string]$InputPath = ".\zinus-network-discovery-online.csv",
    [string]$ResultPath = ".\zinus-network-hostnames.csv",
    [string]$PsExecPath = ".\PsExec.exe",
    [int]$SmbPort = 445,
    [int]$PortTimeoutMs = 1000,
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

function Convert-SecureStringToPlainText {
    param([securestring]$SecureString)

    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecureString)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
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

function Invoke-PsExecHostname {
    param(
        [string]$PsExec,
        [string]$Computer,
        [string]$UserName,
        [string]$Password,
        [string]$EncodedCommand
    )

    $output = & $PsExec "\\$Computer" "-accepteula" "-nobanner" "-n" "10" "-h" "-u" $UserName "-p" $Password "powershell.exe" "-NoProfile" "-EncodedCommand" $EncodedCommand 2>&1
    $exitCode = $LASTEXITCODE

    [pscustomobject]@{
        ExitCode = $exitCode
        Output   = (($output | Out-String).Trim())
    }
}

function New-HostnameResult {
    param(
        [object]$DiscoveryRow,
        [string]$Hostname,
        [string]$NameSource,
        [string]$Status,
        [string]$Message
    )

    [pscustomobject]@{
        ip_address     = $DiscoveryRow.ip_address
        hostname       = $Hostname
        name_source    = $NameSource
        status         = $Status
        message        = $Message
        detection      = $DiscoveryRow.detection
        mac_address    = $DiscoveryRow.mac_address
        neighbor_state = $DiscoveryRow.neighbor_state
        wsman_5985     = $DiscoveryRow.wsman_5985
        resolved_at    = (Get-Date).ToString("s")
    }
}

$inputFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($InputPath)
if (-not (Test-Path $inputFullPath)) {
    throw "File discovery tidak ditemukan: $inputFullPath. Jalankan RUN-DISCOVER-SEGMENTS.cmd terlebih dahulu."
}

$psExecFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($PsExecPath)
if (-not (Test-Path $psExecFullPath)) {
    throw "PsExec tidak ditemukan: $psExecFullPath. Taruh PsExec.exe dari Microsoft Sysinternals di folder installer."
}

if (-not $Credential) {
    Write-Host "Masukkan credential local Administrator yang seragam di target." -ForegroundColor Cyan
    $Credential = Get-Credential -Message "Credential local Administrator target"
}

$rows = @(Import-Csv -Path $inputFullPath)
if ($rows.Count -eq 0) {
    throw "File discovery tidak berisi perangkat online."
}

$remoteCommand = 'Write-Output ("ZINUS_HOSTNAME=" + $env:COMPUTERNAME)'
$encodedCommand = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($remoteCommand))
$plainPassword = Convert-SecureStringToPlainText -SecureString $Credential.Password
$results = @()
$index = 0

try {
    foreach ($row in $rows) {
        $index++
        $target = $row.ip_address
        Write-Progress -Activity "Resolve Zinus Hostnames" -Status "$target ($index/$($rows.Count))" -PercentComplete (($index / $rows.Count) * 100)

        if (-not [string]::IsNullOrWhiteSpace($row.hostname)) {
            $results += New-HostnameResult `
                -DiscoveryRow $row `
                -Hostname $row.hostname `
                -NameSource $row.name_source `
                -Status "already_resolved" `
                -Message "Hostname sudah ditemukan saat discovery."
            continue
        }

        if (-not (Test-TcpPort -Computer $target -Port $SmbPort -TimeoutMs $PortTimeoutMs)) {
            $results += New-HostnameResult `
                -DiscoveryRow $row `
                -Hostname $null `
                -NameSource $null `
                -Status "smb_closed" `
                -Message "SMB port $SmbPort tertutup; PsExec tidak dapat mengakses target."
            continue
        }

        try {
            $remoteResult = Invoke-PsExecHostname `
                -PsExec $psExecFullPath `
                -Computer $target `
                -UserName $Credential.UserName `
                -Password $plainPassword `
                -EncodedCommand $encodedCommand

            if ($remoteResult.ExitCode -eq 0 -and $remoteResult.Output -match '(?m)^ZINUS_HOSTNAME=([^\r\n]+)\s*$') {
                $hostname = $matches[1].Trim()
                Write-Host "Resolved: $target -> $hostname" -ForegroundColor Green
                $results += New-HostnameResult `
                    -DiscoveryRow $row `
                    -Hostname $hostname `
                    -NameSource "RemoteAdmin" `
                    -Status "success" `
                    -Message "Hostname dibaca langsung dari Windows target."
                continue
            }

            $message = "PsExec exit code $($remoteResult.ExitCode). $($remoteResult.Output)"
            Write-Host "Failed: $target - $message" -ForegroundColor Yellow
            $results += New-HostnameResult `
                -DiscoveryRow $row `
                -Hostname $null `
                -NameSource $null `
                -Status "failed" `
                -Message $message
        } catch {
            $results += New-HostnameResult `
                -DiscoveryRow $row `
                -Hostname $null `
                -NameSource $null `
                -Status "failed" `
                -Message $_.Exception.Message
        }
    }
} finally {
    $plainPassword = $null
    Write-Progress -Activity "Resolve Zinus Hostnames" -Completed
}

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDir = Split-Path -Parent $resultFullPath
if ($resultDir -and -not (Test-Path $resultDir)) {
    New-Item -ItemType Directory -Path $resultDir -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8

$resolvedCount = @($results | Where-Object { $_.hostname }).Count
$failedCount = @($results | Where-Object { -not $_.hostname }).Count
Write-Host "Resolve selesai. Hostname ditemukan: $resolvedCount / $($results.Count). Belum ditemukan: $failedCount." -ForegroundColor Green
Write-Host "Hasil disimpan ke $resultFullPath" -ForegroundColor Cyan
