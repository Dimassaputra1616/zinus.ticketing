param(
    [string[]]$ComputerName = @(),
    [string]$ComputerList = ".\computers.txt",
    [string[]]$IpSegment = @(),
    [int]$StartHost = 1,
    [int]$EndHost = 254,
    [string]$PsExecPath = ".\PsExec.exe",
    [string]$ResultPath = ".\zinus-winrm-bootstrap-results.csv",
    [int]$WsManPort = 5985,
    [int]$SmbPort = 445,
    [int]$PortTimeoutMs = 1000,
    [switch]$SkipTrustedHosts,
    [switch]$EnableLocalAccountRemoteAdmin,
    [switch]$ForceBootstrap,
    [switch]$NoFailExit,
    [Parameter(Mandatory = $true)]
    [System.Management.Automation.PSCredential]$Credential
)

$ErrorActionPreference = "Stop"

if ($StartHost -lt 1 -or $StartHost -gt 254 -or $EndHost -lt 1 -or $EndHost -gt 254 -or $StartHost -gt $EndHost) {
    throw "Range host tidak valid. Gunakan StartHost/EndHost antara 1 sampai 254."
}

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

function Resolve-TargetComputers {
    $targets = @()

    if ($ComputerName) {
        $targets += $ComputerName
    }

    if ($ComputerList -and (Test-Path $ComputerList)) {
        $targets += Get-Content -Path $ComputerList |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -and -not $_.StartsWith("#") }
    }

    foreach ($segment in $IpSegment) {
        $targets += Expand-IpSegment -Segment $segment -StartHost $StartHost -EndHost $EndHost
    }

    $targets | Where-Object { $_ } | Sort-Object -Unique
}

function Get-TrustedHostEntry {
    param([string]$TargetOrSegment)

    $value = $TargetOrSegment.Trim()
    if ($value -eq "") {
        return $null
    }

    if ($value -match '^(\d{1,3}\.\d{1,3}\.\d{1,3})(?:$|\.0/24$|\.\d{1,3}-\d{1,3}$)') {
        return "$($matches[1]).*"
    }

    return $value
}

function Set-ZinusTrustedHosts {
    param(
        [string[]]$Targets,
        [string[]]$Segments
    )

    $entries = @()

    if ($Segments) {
        foreach ($segment in $Segments) {
            $entry = Get-TrustedHostEntry -TargetOrSegment $segment
            if ($entry) { $entries += $entry }
        }
    }

    if (-not $entries -or $entries.Count -eq 0) {
        foreach ($target in $Targets) {
            $entry = Get-TrustedHostEntry -TargetOrSegment $target
            if ($entry) { $entries += $entry }
        }
    }

    $entries = @($entries | Sort-Object -Unique)
    if ($entries.Count -eq 0) {
        return
    }

    try {
        $current = ""
        try {
            $current = (Get-Item -Path WSMan:\localhost\Client\TrustedHosts -ErrorAction Stop).Value
        } catch {}

        $allEntries = @()
        if (-not [string]::IsNullOrWhiteSpace($current)) {
            $allEntries += $current.Split(",") | ForEach-Object { $_.Trim() } | Where-Object { $_ }
        }
        $allEntries += $entries
        $trustedHosts = (($allEntries | Sort-Object -Unique) -join ",")

        Set-Item -Path WSMan:\localhost\Client\TrustedHosts -Value $trustedHosts -Force | Out-Null
        Write-Host "TrustedHosts admin machine diupdate: $trustedHosts" -ForegroundColor Green
    } catch {
        Write-Host "Warning: gagal update TrustedHosts. Jalankan PowerShell sebagai Administrator jika remote scan memakai IP/workgroup. $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

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

function New-BootstrapResult {
    param(
        [string]$Computer,
        [string]$Status,
        [string]$Message
    )

    [pscustomobject]@{
        computer       = $Computer
        status         = $Status
        message        = $Message
        bootstrapped_at = (Get-Date).ToString("s")
    }
}

function New-RemoteBootstrapCommand {
    param([bool]$EnableLocalAdminPolicy)

    $localAdminPolicy = if ($EnableLocalAdminPolicy) { '$true' } else { '$false' }

    $remoteScript = @"
`$ErrorActionPreference = 'Stop'
`$ProgressPreference = 'SilentlyContinue'

try {
    Set-Service -Name WinRM -StartupType Automatic -ErrorAction SilentlyContinue
} catch {}

try {
    Start-Service -Name WinRM -ErrorAction SilentlyContinue
} catch {}

try {
    Enable-PSRemoting -Force -SkipNetworkProfileCheck
} catch {
    winrm quickconfig -quiet | Out-Null
}

try {
    Set-NetFirewallRule -Name 'WINRM-HTTP-In-TCP' -Enabled True -Profile Any -ErrorAction SilentlyContinue
} catch {
    netsh advfirewall firewall set rule group='windows remote management' new enable=yes | Out-Null
}

if ($localAdminPolicy) {
    try {
        New-Item -Path 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System' -Force | Out-Null
        New-ItemProperty -Path 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System' -Name 'LocalAccountTokenFilterPolicy' -PropertyType DWord -Value 1 -Force | Out-Null
    } catch {
        Write-Warning "WinRM aktif, tetapi LocalAccountTokenFilterPolicy tidak dapat diubah: `$(`$_.Exception.Message)"
    }
}

Write-Output 'WinRM bootstrap completed'
"@

    return [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($remoteScript))
}

function Invoke-PsExecBootstrap {
    param(
        [string]$PsExec,
        [string]$Computer,
        [string]$UserName,
        [string]$Password,
        [string]$EncodedCommand
    )

    # PsExec writes normal connection progress to stderr. With the script-wide
    # ErrorActionPreference=Stop, PowerShell 5.1 can otherwise abort on a line
    # such as "Connecting to ..." before the actual exit code is collected.
    $previousErrorActionPreference = $ErrorActionPreference
    try {
        $ErrorActionPreference = "Continue"
        # Authenticate to the target with the supplied admin credential, but
        # run the bootstrap process as LocalSystem. This avoids error 1385 on
        # targets that allow remote administration but deny interactive logon
        # for the supplied account.
        $output = & $PsExec "\\$Computer" "-accepteula" "-nobanner" "-n" "10" "-s" "-u" $UserName "-p" $Password "powershell.exe" "-NoProfile" "-ExecutionPolicy" "Bypass" "-EncodedCommand" $EncodedCommand 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorActionPreference
    }

    [pscustomobject]@{
        ExitCode = $exitCode
        Output   = ((@(
            $output | ForEach-Object {
                if ($_ -is [System.Management.Automation.ErrorRecord]) {
                    $_.Exception.Message
                } else {
                    [string]$_
                }
            }
        ) -join [Environment]::NewLine).Trim())
    }
}

$targets = @(Resolve-TargetComputers)
if ($targets.Count -eq 0) {
    throw "Tidak ada target. Isi computers.txt, gunakan -ComputerName, atau gunakan -IpSegment."
}

if (-not $SkipTrustedHosts) {
    Set-ZinusTrustedHosts -Targets $targets -Segments $IpSegment
}

$psExecFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($PsExecPath)
if (-not (Test-Path $psExecFullPath)) {
    throw "PsExec tidak ditemukan: $psExecFullPath. Download PsExec dari Microsoft Sysinternals, lalu taruh PsExec.exe di folder installer atau isi -PsExecPath."
}

$plainPassword = Convert-SecureStringToPlainText -SecureString $Credential.Password
$encodedCommand = New-RemoteBootstrapCommand -EnableLocalAdminPolicy ([bool]$EnableLocalAccountRemoteAdmin)
$results = @()

foreach ($target in $targets) {
    Write-Host "Bootstrapping WinRM on $target..." -ForegroundColor Cyan

    try {
        $portOpenBefore = Test-TcpPort -Computer $target -Port $WsManPort -TimeoutMs $PortTimeoutMs
        if ($portOpenBefore -and -not $ForceBootstrap) {
            $message = "WinRM port $WsManPort sudah terbuka. Lewati bootstrap."
            Write-Host "Ready: $target - $message" -ForegroundColor Green
            $results += New-BootstrapResult -Computer $target -Status "already_ready" -Message $message
            continue
        }

        $smbOpen = Test-TcpPort -Computer $target -Port $SmbPort -TimeoutMs $PortTimeoutMs
        if (-not $smbOpen) {
            $message = "SMB port $SmbPort tidak terbuka. PsExec tidak bisa bootstrap target ini."
            Write-Host "Skipped: $target - $message" -ForegroundColor Yellow
            $results += New-BootstrapResult -Computer $target -Status "skipped" -Message $message
            continue
        }

        $psexecResult = Invoke-PsExecBootstrap `
            -PsExec $psExecFullPath `
            -Computer $target `
            -UserName $Credential.UserName `
            -Password $plainPassword `
            -EncodedCommand $encodedCommand

        Start-Sleep -Seconds 3
        $portOpenAfter = Test-TcpPort -Computer $target -Port $WsManPort -TimeoutMs $PortTimeoutMs

        if ($portOpenAfter) {
            $message = "WinRM aktif dan port $WsManPort terbuka."
            if ($psexecResult.ExitCode -ne 0) {
                $message += " PsExec exit code $($psexecResult.ExitCode), tetapi aktivasi WinRM berhasil."
            }
            Write-Host "Success: $target - $message" -ForegroundColor Green
            $results += New-BootstrapResult -Computer $target -Status "success" -Message $message
            continue
        }

        $message = "PsExec exit code $($psexecResult.ExitCode). WinRM port open after bootstrap: $portOpenAfter. $($psexecResult.Output)"
        Write-Host "Failed: $target - $message" -ForegroundColor Red
        $results += New-BootstrapResult -Computer $target -Status "failed" -Message $message
    } catch {
        $message = $_.Exception.Message
        Write-Host "Failed: $target - $message" -ForegroundColor Red
        $results += New-BootstrapResult -Computer $target -Status "failed" -Message $message
    }
}

$plainPassword = $null

$resultFullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($ResultPath)
$resultDir = Split-Path -Parent $resultFullPath
if ($resultDir -and -not (Test-Path $resultDir)) {
    New-Item -ItemType Directory -Path $resultDir -Force | Out-Null
}

$results | Export-Csv -Path $resultFullPath -NoTypeInformation -Encoding UTF8
Write-Host "Bootstrap results saved to $resultFullPath" -ForegroundColor Cyan

$failedCount = @($results | Where-Object { $_.status -eq "failed" }).Count
if ($failedCount -gt 0 -and -not $NoFailExit) {
    exit 1
}
