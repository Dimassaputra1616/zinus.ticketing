<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class AssetAutomationService
{
    public function commands(): array
    {
        return [
            'network_diagnostics' => [
                'key' => 'network_diagnostics',
                'label' => 'Ping / SSH Probe',
                'group' => 'Diagnostics',
                'script' => 'Network-Diagnostics',
                'summary' => 'Ping targets and check SSH, WinRM, RDP, or SMB ports from the app server.',
                'native' => true,
                'needs_segments' => false,
                'needs_targets' => true,
                'requires_token' => false,
                'uses_asset_sync' => false,
                'supports_parallel' => false,
                'supports_dry_run' => false,
                'default_segments' => '',
                'output_files' => [],
                'options' => [
                    'probe_ping' => ['label' => 'Ping', 'default' => true],
                    'probe_ssh' => ['label' => 'SSH 22', 'default' => true],
                    'probe_winrm' => ['label' => 'WinRM 5985', 'default' => true],
                    'probe_rdp' => ['label' => 'RDP 3389', 'default' => false],
                    'probe_smb' => ['label' => 'SMB 445', 'default' => false],
                ],
            ],
            'discover_segments' => [
                'key' => 'discover_segments',
                'label' => 'Discover Segments',
                'group' => 'Discovery',
                'script' => 'Discover-ZinusNetwork.ps1',
                'summary' => 'Find online IP, hostname, MAC, and WinRM readiness.',
                'needs_segments' => true,
                'needs_targets' => false,
                'requires_token' => false,
                'uses_asset_sync' => false,
                'supports_parallel' => false,
                'supports_dry_run' => false,
                'default_segments' => '10.62.38, 10.62.39, 10.62.36',
                'output_files' => [
                    'zinus-web-network-discovery-results.csv',
                    'zinus-web-network-discovery-online.csv',
                ],
                'options' => [
                    'probe_wsman' => ['label' => 'Probe WinRM 5985', 'default' => true],
                ],
            ],
            'remote_scan_segments' => [
                'key' => 'remote_scan_segments',
                'label' => 'Remote Scan by Segment',
                'group' => 'Inventory',
                'script' => 'Scan-ZinusAssetsRemote.ps1',
                'summary' => 'Pull PC/laptop inventory from IP ranges and sync to Asset Center.',
                'needs_segments' => true,
                'needs_targets' => false,
                'requires_token' => true,
                'uses_asset_sync' => true,
                'supports_parallel' => true,
                'supports_dry_run' => false,
                'default_segments' => '10.62.38, 10.62.39, 10.62.36',
                'output_files' => [
                    'zinus-web-asset-scan-results.csv',
                ],
                'options' => [
                    'skip_existing' => ['label' => 'Skip existing assets', 'default' => true],
                    'skip_preflight' => ['label' => 'Skip WinRM preflight', 'default' => false],
                    'no_fail_exit' => ['label' => 'Keep exit code green for partial failures', 'default' => true],
                ],
            ],
            'remote_scan_targets' => [
                'key' => 'remote_scan_targets',
                'label' => 'Remote Scan Targets',
                'group' => 'Inventory',
                'script' => 'Scan-ZinusAssetsRemote.ps1',
                'summary' => 'Pull inventory from selected hostname or IP targets.',
                'needs_segments' => false,
                'needs_targets' => true,
                'requires_token' => true,
                'uses_asset_sync' => true,
                'supports_parallel' => true,
                'supports_dry_run' => false,
                'default_segments' => '',
                'output_files' => [
                    'zinus-web-asset-scan-results.csv',
                ],
                'options' => [
                    'skip_existing' => ['label' => 'Skip existing assets', 'default' => true],
                    'skip_preflight' => ['label' => 'Skip WinRM preflight', 'default' => false],
                    'no_fail_exit' => ['label' => 'Keep exit code green for partial failures', 'default' => true],
                ],
            ],
            'remote_scan_all_segments' => [
                'key' => 'remote_scan_all_segments',
                'label' => 'Remote Scan All',
                'group' => 'Inventory',
                'script' => 'Scan-ZinusAssetsRemote.ps1',
                'summary' => 'Pull every PC/laptop in selected IP ranges, including existing assets.',
                'needs_segments' => true,
                'needs_targets' => false,
                'requires_token' => true,
                'uses_asset_sync' => true,
                'supports_parallel' => true,
                'supports_dry_run' => false,
                'default_segments' => '10.62.38, 10.62.39, 10.62.36',
                'default_max_parallel' => 20,
                'result_path' => '.\zinus-web-asset-scan-all-results.csv',
                'output_files' => [
                    'zinus-web-asset-scan-all-results.csv',
                ],
                'options' => [
                    'skip_existing' => ['label' => 'Skip existing assets', 'default' => false],
                    'skip_preflight' => ['label' => 'Skip WinRM preflight', 'default' => false],
                    'no_fail_exit' => ['label' => 'Keep exit code green for partial failures', 'default' => true],
                ],
            ],
            'retry_failed_scan' => [
                'key' => 'retry_failed_scan',
                'label' => 'Retry Failed Scan',
                'group' => 'Retry',
                'script' => 'Scan-ZinusAssetsRemote.ps1',
                'summary' => 'Pull inventory again from targets that failed or were skipped before.',
                'needs_segments' => false,
                'needs_targets' => false,
                'requires_token' => true,
                'uses_asset_sync' => true,
                'supports_parallel' => true,
                'supports_dry_run' => false,
                'default_segments' => '',
                'default_max_parallel' => 12,
                'result_path' => '.\zinus-web-failed-scan-results.csv',
                'output_files' => [
                    'zinus-web-failed-scan-targets.txt',
                    'zinus-web-failed-scan-results.csv',
                ],
                'options' => [
                    'skip_existing' => ['label' => 'Skip existing assets', 'default' => false],
                    'skip_preflight' => ['label' => 'Skip WinRM preflight', 'default' => false],
                    'no_fail_exit' => ['label' => 'Keep exit code green for partial failures', 'default' => true],
                ],
            ],
            'sync_local_printers' => [
                'key' => 'sync_local_printers',
                'label' => 'Sync Local Printers',
                'group' => 'Peripherals',
                'script' => 'Sync-ZinusLocalPrinters.ps1',
                'summary' => 'Read physical local printers from scanned PCs and sync them as assets.',
                'needs_segments' => false,
                'needs_targets' => true,
                'targets_optional' => true,
                'requires_token' => true,
                'uses_asset_sync' => true,
                'supports_parallel' => true,
                'supports_dry_run' => true,
                'default_segments' => '',
                'output_files' => [
                    'zinus-web-local-printer-sync-results.csv',
                ],
                'options' => [
                    'dry_run' => ['label' => 'Dry run', 'default' => false],
                    'include_network_printers' => ['label' => 'Include shared/network printers', 'default' => false],
                    'include_other_local_ports' => ['label' => 'Include other local ports', 'default' => false],
                ],
            ],
            'sync_network_devices' => [
                'key' => 'sync_network_devices',
                'label' => 'Sync Network Devices',
                'group' => 'Network',
                'script' => 'Sync-ZinusNetworkDevices.ps1',
                'summary' => 'Create router, switch, printer, CCTV, and gateway assets from discovery files.',
                'needs_segments' => false,
                'needs_targets' => false,
                'requires_token' => true,
                'uses_asset_sync' => true,
                'supports_parallel' => false,
                'supports_dry_run' => true,
                'default_segments' => '',
                'output_files' => [
                    'zinus-web-network-device-sync-results.csv',
                ],
                'options' => [
                    'dry_run' => ['label' => 'Dry run', 'default' => true],
                    'skip_snmp' => ['label' => 'Skip SNMP probe', 'default' => false],
                    'include_gateways' => ['label' => 'Include gateways', 'default' => false],
                ],
            ],
            'export_data_quality' => [
                'key' => 'export_data_quality',
                'label' => 'Export Data Quality',
                'group' => 'Audit',
                'script' => 'Export-ZinusDataQualityIssues.ps1',
                'summary' => 'Generate issue rows from the latest web scan result.',
                'needs_segments' => false,
                'needs_targets' => false,
                'requires_token' => false,
                'uses_asset_sync' => false,
                'supports_parallel' => false,
                'supports_dry_run' => false,
                'default_segments' => '',
                'output_files' => [
                    'zinus-web-data-quality-issues.csv',
                ],
                'options' => [],
            ],
            'export_missing_hostnames' => [
                'key' => 'export_missing_hostnames',
                'label' => 'Export Missing Hostnames',
                'group' => 'Audit',
                'script' => 'Export-ZinusMissingHostnames.ps1',
                'summary' => 'Generate remediation rows for IPs that still have no hostname.',
                'needs_segments' => false,
                'needs_targets' => false,
                'requires_token' => false,
                'uses_asset_sync' => false,
                'supports_parallel' => false,
                'supports_dry_run' => false,
                'default_segments' => '',
                'output_files' => [
                    'zinus-web-missing-hostnames.csv',
                ],
                'options' => [],
            ],
            'build_package' => [
                'key' => 'build_package',
                'label' => 'Build Installer Package',
                'group' => 'Package',
                'script' => 'Build-ZinusAssetPackage.ps1',
                'summary' => 'Create a fresh deployment folder and zip package.',
                'needs_segments' => false,
                'needs_targets' => false,
                'requires_token' => false,
                'uses_asset_sync' => false,
                'supports_parallel' => false,
                'supports_dry_run' => false,
                'default_segments' => '',
                'output_files' => [
                    'dist/ZinusAssetInstaller',
                    'dist/ZinusAssetInstaller.zip',
                ],
                'options' => [],
            ],
        ];
    }

    public function environment(): array
    {
        $powershell = $this->resolvePowerShell();
        $installerPath = $this->installerPath();
        $enabled = (bool) config('asset_automation.enabled', true);
        $canRunNative = $enabled;
        $canRunPowerShell = $enabled && (bool) $powershell && is_dir($installerPath);

        return [
            'enabled' => $enabled,
            'installer_path' => $installerPath,
            'installer_exists' => is_dir($installerPath),
            'powershell' => $powershell,
            'can_run_native' => $canRunNative,
            'can_run_powershell' => $canRunPowerShell,
            'can_execute' => $canRunNative || $canRunPowerShell,
            'os' => PHP_OS_FAMILY,
            'timeout_seconds' => $this->timeoutSeconds(),
            'has_config_token' => $this->configuredToken() !== '',
            'default_factory' => config('services.asset_sync.factory') ?: 'GCI-HWANG',
            'default_department' => config('services.asset_sync.department') ?: 'IT',
            'default_server_url' => $this->defaultServerUrl(),
            'recent_outputs' => $this->recentOutputs(),
        ];
    }

    public function run(string $commandKey, array $input, ?User $actor = null): array
    {
        $commands = $this->commands();
        if (! array_key_exists($commandKey, $commands)) {
            throw ValidationException::withMessages([
                'command_key' => 'Command tidak tersedia di automation whitelist.',
            ]);
        }

        if (! (bool) config('asset_automation.enabled', true)) {
            throw ValidationException::withMessages([
                'command_key' => 'Automation console sedang dinonaktifkan.',
            ]);
        }

        $command = $commands[$commandKey];
        if ((bool) ($command['native'] ?? false)) {
            return $this->runNativeCommand($commandKey, $command, $input, $actor);
        }

        $secrets = [];
        [$scriptArguments, $secrets] = $this->scriptArguments($commandKey, $command, $input);

        $installerPath = $this->installerPath();
        if (! is_dir($installerPath)) {
            throw ValidationException::withMessages([
                'command_key' => 'Folder ZinusAssetInstaller tidak ditemukan.',
            ]);
        }

        $powershell = $this->resolvePowerShell();
        if (! $powershell) {
            throw ValidationException::withMessages([
                'command_key' => 'PowerShell runtime tidak ditemukan. Install PowerShell 7 (pwsh) atau set ASSET_AUTOMATION_POWERSHELL_PATH.',
            ]);
        }

        $scriptPath = $installerPath.DIRECTORY_SEPARATOR.$command['script'];
        if (! is_file($scriptPath)) {
            throw ValidationException::withMessages([
                'command_key' => "Script tidak ditemukan: {$command['script']}",
            ]);
        }

        $arguments = [
            $powershell,
            '-NoLogo',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $scriptPath,
        ];
        $arguments = array_merge($arguments, $scriptArguments);

        $startedAt = now();
        $stdout = '';
        $stderr = '';
        $exitCode = null;
        $timedOut = false;
        $errorMessage = null;

        $process = new Process($arguments, $installerPath, [
            'NO_COLOR' => '1',
            'TERM' => 'dumb',
        ], null, $this->timeoutSeconds());
        $process->setIdleTimeout(null);

        try {
            $process->run(function ($type, $buffer) use (&$stdout, &$stderr) {
                if ($type === Process::ERR) {
                    $stderr .= $buffer;

                    return;
                }

                $stdout .= $buffer;
            });
            $exitCode = $process->getExitCode();
        } catch (ProcessTimedOutException $exception) {
            $timedOut = true;
            $exitCode = -1;
            $errorMessage = $exception->getMessage();
            if ($process->isRunning()) {
                $process->stop(3);
            }
            $stdout .= $process->getOutput();
            $stderr .= $process->getErrorOutput();
        } catch (Throwable $exception) {
            $exitCode = -1;
            $errorMessage = $exception->getMessage();
            $stdout .= $process->getOutput();
            $stderr .= $process->getErrorOutput();
        }

        $finishedAt = now();
        $stdout = $this->maskSecrets($this->trimOutput($stdout), $secrets);
        $stderr = $this->maskSecrets($this->trimOutput($stderr), $secrets);
        if ($errorMessage) {
            $stderr = trim($stderr.PHP_EOL.$this->maskSecrets($errorMessage, $secrets));
        }

        $result = [
            'command_key' => $commandKey,
            'label' => $command['label'],
            'command' => $this->displayCommand($arguments, $secrets),
            'successful' => $exitCode === 0 && ! $timedOut,
            'exit_code' => $exitCode,
            'timed_out' => $timedOut,
            'started_at' => $startedAt->toDateTimeString(),
            'finished_at' => $finishedAt->toDateTimeString(),
            'duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
            'stdout' => $stdout,
            'stderr' => $stderr,
            'output_files' => $this->outputFileStatuses($command['output_files'] ?? []),
            'log_file' => null,
        ];

        $result['log_file'] = $this->writeRunLog($result, $actor);

        return $result;
    }

    protected function runNativeCommand(string $commandKey, array $command, array $input, ?User $actor): array
    {
        $startedAt = now();
        $stdout = '';
        $stderr = '';
        $exitCode = 0;
        $timedOut = false;

        try {
            [$stdout, $timedOut] = match ($commandKey) {
                'network_diagnostics' => $this->networkDiagnosticsOutput($command, $input),
                default => throw ValidationException::withMessages([
                    'command_key' => 'Native command tidak tersedia.',
                ]),
            };

            if ($timedOut) {
                $exitCode = -1;
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $exitCode = -1;
            $stderr = $exception->getMessage();
        }

        $finishedAt = now();
        $result = [
            'command_key' => $commandKey,
            'label' => $command['label'],
            'command' => $this->nativeDisplayCommand($commandKey, $input),
            'successful' => $exitCode === 0 && ! $timedOut,
            'exit_code' => $exitCode,
            'timed_out' => $timedOut,
            'started_at' => $startedAt->toDateTimeString(),
            'finished_at' => $finishedAt->toDateTimeString(),
            'duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
            'stdout' => $this->trimOutput($stdout),
            'stderr' => $this->trimOutput($stderr),
            'output_files' => $this->outputFileStatuses($command['output_files'] ?? []),
            'log_file' => null,
        ];

        $result['log_file'] = $this->writeRunLog($result, $actor);

        return $result;
    }

    protected function networkDiagnosticsOutput(array $command, array $input): array
    {
        $targets = $this->targets($input, false);
        if (count($targets) > 50) {
            throw ValidationException::withMessages([
                'targets' => 'Maksimal 50 target per network diagnostics.',
            ]);
        }

        $probes = $this->diagnosticProbes($command, $input);
        if ($probes === []) {
            throw ValidationException::withMessages([
                'command_key' => 'Pilih minimal satu probe diagnostic.',
            ]);
        }

        $lines = [
            'Network diagnostics dari server aplikasi',
            'Targets: '.count($targets),
            'Probes : '.implode(', ', array_column($probes, 'label')),
            '',
        ];
        $deadline = microtime(true) + $this->timeoutSeconds();
        $timedOut = false;

        foreach ($targets as $index => $target) {
            if (microtime(true) >= $deadline) {
                $timedOut = true;
                $remaining = count($targets) - $index;
                $lines[] = "timeout: {$remaining} target belum dicek karena melewati max timeout.";
                break;
            }

            $lines[] = '['.($index + 1).'/'.count($targets)."] {$target}";
            foreach ($probes as $probe) {
                if ($probe['type'] === 'ping') {
                    $result = $this->pingTarget($target);
                    $lines[] = sprintf(
                        '  ping      : %-7s %s',
                        $result['ok'] ? 'ok' : $result['status'],
                        $result['message']
                    );

                    continue;
                }

                $result = $this->probeTcpPort($target, $probe['port']);
                $lines[] = sprintf(
                    '  %-10s: %-7s %s',
                    $probe['label'],
                    $result['open'] ? 'open' : 'closed',
                    $result['message']
                );

                if ($probe['port'] === 22 && $result['open']) {
                    $lines[] = "  hint      : ssh user@{$target}";
                }
            }

            $lines[] = '';
        }

        return [implode(PHP_EOL, $lines), $timedOut];
    }

    protected function diagnosticProbes(array $command, array $input): array
    {
        $probes = [];
        if ($this->flag($input, 'probe_ping', $this->defaultOption($command, 'probe_ping', true))) {
            $probes[] = ['type' => 'ping', 'label' => 'ping'];
        }

        foreach ([
            'probe_ssh' => ['label' => 'ssh 22', 'port' => 22, 'default' => true],
            'probe_winrm' => ['label' => 'winrm 5985', 'port' => 5985, 'default' => true],
            'probe_rdp' => ['label' => 'rdp 3389', 'port' => 3389, 'default' => false],
            'probe_smb' => ['label' => 'smb 445', 'port' => 445, 'default' => false],
        ] as $key => $probe) {
            if ($this->flag($input, $key, $this->defaultOption($command, $key, $probe['default']))) {
                $probes[] = ['type' => 'tcp', 'label' => $probe['label'], 'port' => $probe['port']];
            }
        }

        return $probes;
    }

    protected function pingTarget(string $target): array
    {
        if (! $this->commandExists('ping')) {
            return [
                'ok' => false,
                'status' => 'skipped',
                'message' => 'ping command tidak ditemukan di server.',
            ];
        }

        $arguments = PHP_OS_FAMILY === 'Windows'
            ? ['ping', '-n', '1', '-w', '1500', $target]
            : ['ping', '-c', '1', '-W', '2', $target];
        $started = microtime(true);
        $process = new Process($arguments, null, [
            'NO_COLOR' => '1',
            'TERM' => 'dumb',
        ], null, 4);

        try {
            $process->run();
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $output = trim($process->getOutput().PHP_EOL.$process->getErrorOutput());

            return [
                'ok' => $process->isSuccessful(),
                'status' => $process->isSuccessful() ? 'ok' : 'failed',
                'message' => $this->summarizePingOutput($output, $durationMs),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function summarizePingOutput(string $output, int $durationMs): string
    {
        if (preg_match('/time[=<]\s*([0-9.]+\s*ms)/i', $output, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(ttl=\d+)/i', $output, $matches)) {
            return $matches[1].", {$durationMs}ms";
        }

        if (preg_match('/(\d+)\s+packets?\s+transmitted,\s+(\d+)\s+(?:packets?\s+)?received/i', $output, $matches)) {
            return "{$matches[2]}/{$matches[1]} received, {$durationMs}ms";
        }

        return $durationMs.'ms';
    }

    protected function probeTcpPort(string $target, int $port): array
    {
        $endpoint = filter_var($target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? "tcp://[{$target}]:{$port}"
            : "tcp://{$target}:{$port}";
        $started = microtime(true);
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($endpoint, $errno, $errstr, 1.5, STREAM_CLIENT_CONNECT);
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        if (is_resource($socket)) {
            fclose($socket);

            return [
                'open' => true,
                'message' => $durationMs.'ms',
            ];
        }

        $message = $errstr !== '' ? $errstr : 'connection failed';

        return [
            'open' => false,
            'message' => "{$message}, {$durationMs}ms",
        ];
    }

    protected function nativeDisplayCommand(string $commandKey, array $input): string
    {
        $targetCount = count($this->parseList($input['targets'] ?? ''));

        return match ($commandKey) {
            'network_diagnostics' => "network-diagnostics --targets={$targetCount}",
            default => $commandKey,
        };
    }

    protected function scriptArguments(string $commandKey, array $command, array $input): array
    {
        return match ($commandKey) {
            'discover_segments' => $this->discoverSegmentsArguments($command, $input),
            'remote_scan_segments' => $this->remoteScanSegmentsArguments($command, $input),
            'remote_scan_targets' => $this->remoteScanTargetsArguments($command, $input),
            'remote_scan_all_segments' => $this->remoteScanSegmentsArguments($command, $input),
            'retry_failed_scan' => $this->retryFailedScanArguments($command, $input),
            'sync_local_printers' => $this->syncLocalPrintersArguments($command, $input),
            'sync_network_devices' => $this->syncNetworkDevicesArguments($command, $input),
            'export_data_quality' => $this->exportDataQualityArguments(),
            'export_missing_hostnames' => $this->exportMissingHostnamesArguments(),
            'build_package' => $this->buildPackageArguments(),
            default => [[], []],
        };
    }

    protected function discoverSegmentsArguments(array $command, array $input): array
    {
        $segments = $this->segments($input, $command);
        [$startHost, $endHost] = $this->hostRange($input);

        return [[
            '-IpSegment', ...$segments,
            '-StartHost', (string) $startHost,
            '-EndHost', (string) $endHost,
            '-ResultPath', '.\zinus-web-network-discovery-results.csv',
            '-OnlineResultPath', '.\zinus-web-network-discovery-online.csv',
            ...($this->flag($input, 'probe_wsman', true) ? ['-ProbeWsMan'] : []),
        ], []];
    }

    protected function remoteScanSegmentsArguments(array $command, array $input): array
    {
        $segments = $this->segments($input, $command);
        [$startHost, $endHost] = $this->hostRange($input);
        [$token, $secrets] = $this->token($input, false);

        return [[
            '-ComputerList', '',
            '-IpSegment', ...$segments,
            '-StartHost', (string) $startHost,
            '-EndHost', (string) $endHost,
            '-Token', $token,
            '-Factory', $this->text($input, 'factory', config('services.asset_sync.factory') ?: 'GCI-HWANG', 150),
            '-Department', $this->text($input, 'department', config('services.asset_sync.department') ?: 'IT', 150),
            '-ServerUrl', $this->serverUrl($input),
            '-MaxParallel', (string) $this->maxParallel($input, 20),
            '-ResultPath', $this->scanResultPath($command, '.\zinus-web-asset-scan-results.csv'),
            ...($this->flag($input, 'skip_existing', $this->defaultOption($command, 'skip_existing', true)) ? ['-SkipExisting'] : []),
            ...($this->flag($input, 'skip_preflight', false) ? ['-SkipPreflight'] : []),
            ...($this->flag($input, 'no_fail_exit', true) ? ['-NoFailExit'] : []),
        ], $secrets];
    }

    protected function remoteScanTargetsArguments(array $command, array $input): array
    {
        $targets = $this->targets($input, (bool) ($command['targets_optional'] ?? false));
        [$token, $secrets] = $this->token($input, false);

        return [[
            '-ComputerList', '',
            '-ComputerName', ...$targets,
            '-Token', $token,
            '-Factory', $this->text($input, 'factory', config('services.asset_sync.factory') ?: 'GCI-HWANG', 150),
            '-Department', $this->text($input, 'department', config('services.asset_sync.department') ?: 'IT', 150),
            '-ServerUrl', $this->serverUrl($input),
            '-MaxParallel', (string) $this->maxParallel($input, 20),
            '-ResultPath', $this->scanResultPath($command, '.\zinus-web-asset-scan-results.csv'),
            ...($this->flag($input, 'skip_existing', $this->defaultOption($command, 'skip_existing', true)) ? ['-SkipExisting'] : []),
            ...($this->flag($input, 'skip_preflight', false) ? ['-SkipPreflight'] : []),
            ...($this->flag($input, 'no_fail_exit', true) ? ['-NoFailExit'] : []),
        ], $secrets];
    }

    protected function retryFailedScanArguments(array $command, array $input): array
    {
        $targets = $this->failedScanTargets();
        if ($targets === []) {
            throw ValidationException::withMessages([
                'targets' => 'Belum ada target gagal dari hasil scan sebelumnya.',
            ]);
        }

        if (count($targets) > 1000) {
            throw ValidationException::withMessages([
                'targets' => 'Target gagal terlalu banyak. Rapikan file hasil gagal atau jalankan per segment.',
            ]);
        }

        $this->writeInstallerTextList('zinus-web-failed-scan-targets.txt', $targets);
        [$token, $secrets] = $this->token($input, false);

        return [[
            '-ComputerList', '.\zinus-web-failed-scan-targets.txt',
            '-Token', $token,
            '-Factory', $this->text($input, 'factory', config('services.asset_sync.factory') ?: 'GCI-HWANG', 150),
            '-Department', $this->text($input, 'department', config('services.asset_sync.department') ?: 'IT', 150),
            '-ServerUrl', $this->serverUrl($input),
            '-MaxParallel', (string) $this->maxParallel($input, 12),
            '-ResultPath', $this->scanResultPath($command, '.\zinus-web-failed-scan-results.csv'),
            ...($this->flag($input, 'skip_existing', $this->defaultOption($command, 'skip_existing', false)) ? ['-SkipExisting'] : []),
            ...($this->flag($input, 'skip_preflight', false) ? ['-SkipPreflight'] : []),
            ...($this->flag($input, 'no_fail_exit', true) ? ['-NoFailExit'] : []),
        ], $secrets];
    }

    protected function syncLocalPrintersArguments(array $command, array $input): array
    {
        $dryRun = $this->flag($input, 'dry_run', false);
        [$token, $secrets] = $this->token($input, $dryRun);
        $targets = $this->targets($input, true);

        $arguments = [
            '-ScanResultsPath', '.\zinus-web-asset-scan-results.csv',
            '-Factory', $this->text($input, 'factory', config('services.asset_sync.factory') ?: 'GCI-HWANG', 150),
            '-Department', $this->text($input, 'department', config('services.asset_sync.department') ?: 'IT', 150),
            '-ServerUrl', $this->serverUrl($input),
            '-MaxParallel', (string) $this->maxParallel($input, 8),
            '-ResultPath', '.\zinus-web-local-printer-sync-results.csv',
            ...($dryRun ? ['-DryRun'] : []),
            ...($this->flag($input, 'include_network_printers', false) ? ['-IncludeNetworkPrinters'] : []),
            ...($this->flag($input, 'include_other_local_ports', false) ? ['-IncludeOtherLocalPorts'] : []),
        ];

        if ($token !== '') {
            array_splice($arguments, 0, 0, ['-Token', $token]);
        }

        if ($targets !== []) {
            array_splice($arguments, 0, 0, ['-ComputerName', ...$targets]);
        }

        return [$arguments, $secrets];
    }

    protected function syncNetworkDevicesArguments(array $command, array $input): array
    {
        $dryRun = $this->flag($input, 'dry_run', true);
        [$token, $secrets] = $this->token($input, $dryRun);

        $arguments = [
            '-DeviceListPath', '.\zinus-remediation-non-windows-devices.csv',
            '-DiscoveryPath', '.\zinus-web-network-discovery-results.csv',
            '-Factory', $this->text($input, 'factory', config('services.asset_sync.factory') ?: 'GCI-HWANG', 150),
            '-Department', $this->text($input, 'department', config('services.asset_sync.department') ?: 'IT', 150),
            '-ServerUrl', $this->serverUrl($input),
            '-ResultPath', '.\zinus-web-network-device-sync-results.csv',
            ...($dryRun ? ['-DryRun'] : []),
            ...($this->flag($input, 'skip_snmp', false) ? ['-SkipSnmp'] : []),
            ...($this->flag($input, 'include_gateways', false) ? ['-IncludeGateways'] : []),
        ];

        if ($token !== '') {
            array_splice($arguments, 0, 0, ['-Token', $token]);
        }

        return [$arguments, $secrets];
    }

    protected function exportDataQualityArguments(): array
    {
        return [[
            '-ScanPath', '.\zinus-web-asset-scan-results.csv',
            '-ResultPath', '.\zinus-web-data-quality-issues.csv',
        ], []];
    }

    protected function exportMissingHostnamesArguments(): array
    {
        return [[
            '-FailureAnalysisPath', '.\zinus-auto-failure-analysis.csv',
            '-DiscoveryPath', '.\zinus-web-network-discovery-results.csv',
            '-VerificationPath', '.\zinus-auto-verification.csv',
            '-ResultPath', '.\zinus-web-missing-hostnames.csv',
        ], []];
    }

    protected function buildPackageArguments(): array
    {
        return [[
            '-OutputDirectory', '.\dist',
            '-PackageName', 'ZinusAssetInstaller',
        ], []];
    }

    protected function scanResultPath(array $command, string $default): string
    {
        $path = trim((string) ($command['result_path'] ?? $default));

        return $path !== '' ? $path : $default;
    }

    protected function defaultOption(array $command, string $key, bool $default): bool
    {
        return (bool) ($command['options'][$key]['default'] ?? $default);
    }

    protected function failedScanTargets(): array
    {
        $targets = [];
        $csvSources = [
            ['path' => 'zinus-web-asset-scan-results.csv', 'target_columns' => ['computer', 'ip_address', 'hostname']],
            ['path' => 'zinus-web-asset-scan-all-results.csv', 'target_columns' => ['computer', 'ip_address', 'hostname']],
            ['path' => 'zinus-web-failed-scan-results.csv', 'target_columns' => ['computer', 'ip_address', 'hostname']],
            ['path' => 'zinus-auto-scan-results.csv', 'target_columns' => ['computer', 'ip_address', 'hostname']],
            ['path' => 'zinus-auto-scan-results-retry.csv', 'target_columns' => ['computer', 'ip_address', 'hostname']],
            ['path' => 'zinus-asset-remote-scan-results.csv', 'target_columns' => ['computer', 'ip_address', 'hostname']],
            ['path' => 'zinus-auto-failure-analysis.csv', 'target_columns' => ['ip_address', 'hostname', 'computer']],
        ];

        foreach ($csvSources as $source) {
            foreach ($this->readInstallerCsv($source['path']) as $row) {
                if (! $this->isFailedStatusRow($row) || $this->isNonWindowsFailureRow($row)) {
                    continue;
                }

                $this->addFailedTarget($targets, $this->firstRowValue($row, $source['target_columns']));
            }
        }

        foreach ([
            'zinus-retry-failed-targets.txt',
            'zinus-remediation-retry-computers.txt',
        ] as $textPath) {
            foreach ($this->readInstallerTextList($textPath) as $target) {
                $this->addFailedTarget($targets, $target);
            }
        }

        ksort($targets, SORT_NATURAL);

        return array_keys($targets);
    }

    protected function readInstallerCsv(string $relativePath): array
    {
        $path = $this->installerFilePath($relativePath);
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [];
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                return [];
            }

            $keys = array_map(
                fn ($key) => strtolower(trim((string) $key)),
                $header
            );

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if (! is_array($values)) {
                    continue;
                }

                $row = [];
                foreach ($keys as $index => $key) {
                    if ($key === '') {
                        continue;
                    }

                    $row[$key] = isset($values[$index]) ? trim((string) $values[$index]) : '';
                }

                if ($row !== []) {
                    $rows[] = $row;
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    protected function readInstallerTextList(string $relativePath): array
    {
        $path = $this->installerFilePath($relativePath);
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        return collect(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, '#'))
            ->values()
            ->all();
    }

    protected function writeInstallerTextList(string $relativePath, array $items): void
    {
        $path = $this->installerFilePath($relativePath);
        File::put($path, implode(PHP_EOL, $items).PHP_EOL);
    }

    protected function installerFilePath(string $relativePath): string
    {
        return $this->installerPath().DIRECTORY_SEPARATOR.ltrim(
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath),
            DIRECTORY_SEPARATOR
        );
    }

    protected function isFailedStatusRow(array $row): bool
    {
        $status = strtolower(trim((string) ($row['status'] ?? '')));
        if ($status === '') {
            return false;
        }

        return ! in_array($status, [
            'success',
            'ok',
            'ready',
            'online',
            'already_ready',
            'already_synced',
            'skipped_existing',
        ], true);
    }

    protected function isNonWindowsFailureRow(array $row): bool
    {
        $type = strtolower(trim((string) ($row['likely_device_type'] ?? $row['category'] ?? '')));
        if ($type === '') {
            return false;
        }

        foreach (['printer', 'network device', 'gateway', 'cctv', 'camera', 'nas', 'storage'] as $needle) {
            if (str_contains($type, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function firstRowValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function addFailedTarget(array &$targets, string $target): void
    {
        $target = trim($target);
        if ($target !== '' && $this->validTarget($target)) {
            $targets[$target] = true;
        }
    }

    protected function segments(array $input, array $command): array
    {
        $segments = $this->parseList($input['segments'] ?? ($command['default_segments'] ?? ''));
        if ($segments === []) {
            throw ValidationException::withMessages([
                'segments' => 'Isi minimal satu IP segment.',
            ]);
        }

        if (count($segments) > 50) {
            throw ValidationException::withMessages([
                'segments' => 'Maksimal 50 segment per run.',
            ]);
        }

        foreach ($segments as $segment) {
            if (! $this->validSegment($segment)) {
                throw ValidationException::withMessages([
                    'segments' => "Format segment tidak valid: {$segment}",
                ]);
            }
        }

        return $segments;
    }

    protected function targets(array $input, bool $optional): array
    {
        $targets = $this->parseList($input['targets'] ?? '');
        if ($targets === [] && ! $optional) {
            throw ValidationException::withMessages([
                'targets' => 'Isi minimal satu hostname atau IP target.',
            ]);
        }

        if (count($targets) > 250) {
            throw ValidationException::withMessages([
                'targets' => 'Maksimal 250 target per run.',
            ]);
        }

        foreach ($targets as $target) {
            if (! $this->validTarget($target)) {
                throw ValidationException::withMessages([
                    'targets' => "Target tidak valid: {$target}",
                ]);
            }
        }

        return $targets;
    }

    protected function hostRange(array $input): array
    {
        $start = (int) ($input['start_host'] ?? 1);
        $end = (int) ($input['end_host'] ?? 254);

        if ($start < 1 || $start > 254 || $end < 1 || $end > 254 || $start > $end) {
            throw ValidationException::withMessages([
                'start_host' => 'Range host harus 1-254 dan start tidak boleh lebih besar dari end.',
            ]);
        }

        return [$start, $end];
    }

    protected function maxParallel(array $input, int $default): int
    {
        $value = (int) ($input['max_parallel'] ?? $default);

        return max(1, min($value, 50));
    }

    protected function token(array $input, bool $optional): array
    {
        $useConfigToken = $this->flag($input, 'use_config_token', true);
        $token = $useConfigToken ? $this->configuredToken() : trim((string) ($input['token'] ?? ''));

        if ($token === '' && ! $optional) {
            throw ValidationException::withMessages([
                'token' => 'Token asset sync wajib diisi, atau aktifkan token dari server config.',
            ]);
        }

        return [$token, $token === '' ? [] : [$token]];
    }

    protected function serverUrl(array $input): string
    {
        $serverUrl = trim((string) ($input['server_url'] ?? $this->defaultServerUrl()));
        if (! filter_var($serverUrl, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'server_url' => 'Server URL harus berupa URL valid.',
            ]);
        }

        return $serverUrl;
    }

    protected function text(array $input, string $key, string $default, int $max): string
    {
        $value = trim((string) ($input[$key] ?? $default));
        if ($value === '') {
            $value = $default;
        }

        return Str::limit($value, $max, '');
    }

    protected function flag(array $input, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $input)) {
            return $default;
        }

        return filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);
    }

    protected function parseList(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\r\n,]+/', (string) $value) ?: [];
        }

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function validTarget(string $target): bool
    {
        if (filter_var($target, FILTER_VALIDATE_IP)) {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,190}$/', $target);
    }

    protected function validSegment(string $segment): bool
    {
        if (filter_var($segment, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3})$/', $segment, $matches)) {
            return filter_var($matches[1].'.1', FILTER_VALIDATE_IP) !== false;
        }

        if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3})\.0\/24$/', $segment, $matches)) {
            return filter_var($matches[1].'.1', FILTER_VALIDATE_IP) !== false;
        }

        if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3})\.(\d{1,3})-(\d{1,3})$/', $segment, $matches)) {
            $start = (int) $matches[2];
            $end = (int) $matches[3];

            return $start >= 1
                && $end <= 254
                && $start <= $end
                && filter_var($matches[1].'.'.$start, FILTER_VALIDATE_IP) !== false
                && filter_var($matches[1].'.'.$end, FILTER_VALIDATE_IP) !== false;
        }

        return false;
    }

    protected function outputFileStatuses(array $relativePaths): array
    {
        $installerPath = $this->installerPath();

        return collect($relativePaths)
            ->map(function (string $relativePath) use ($installerPath) {
                $path = $installerPath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

                return [
                    'name' => $relativePath,
                    'exists' => file_exists($path),
                    'size' => file_exists($path) && is_file($path) ? filesize($path) : null,
                    'modified_at' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function recentOutputs(): array
    {
        $paths = collect($this->commands())
            ->flatMap(fn (array $command) => $command['output_files'] ?? [])
            ->unique()
            ->values()
            ->all();

        return $this->outputFileStatuses($paths);
    }

    protected function writeRunLog(array $result, ?User $actor): ?string
    {
        try {
            $logDirectory = storage_path('logs/asset-automation');
            File::ensureDirectoryExists($logDirectory);

            $fileName = now()->format('Ymd_His').'_'.$result['command_key'].'_'.Str::random(6).'.log';
            $path = $logDirectory.DIRECTORY_SEPARATOR.$fileName;

            File::put($path, implode(PHP_EOL, [
                'Actor: '.($actor?->email ?: 'system'),
                'Command: '.$result['command'],
                'Exit code: '.$result['exit_code'],
                'Started: '.$result['started_at'],
                'Finished: '.$result['finished_at'],
                '',
                '[stdout]',
                $result['stdout'] ?: '',
                '',
                '[stderr]',
                $result['stderr'] ?: '',
            ]));

            return $fileName;
        } catch (Throwable) {
            return null;
        }
    }

    protected function displayCommand(array $arguments, array $secrets): string
    {
        $command = collect($arguments)
            ->map(fn (string $argument) => $this->quoteForDisplay($argument))
            ->implode(' ');

        return $this->maskSecrets($command, $secrets);
    }

    protected function quoteForDisplay(string $argument): string
    {
        if ($argument === '') {
            return '""';
        }

        if (preg_match('/^[A-Za-z0-9_@%+=:,\.\/\\\\-]+$/', $argument)) {
            return $argument;
        }

        return '"'.str_replace('"', '\"', $argument).'"';
    }

    protected function trimOutput(string $output): string
    {
        return Str::limit(trim($output), 60000, "\n...[output truncated]");
    }

    protected function maskSecrets(string $text, array $secrets): string
    {
        foreach ($secrets as $secret) {
            $secret = (string) $secret;
            if ($secret !== '') {
                $text = str_replace($secret, '[token hidden]', $text);
            }
        }

        return $text;
    }

    protected function installerPath(): string
    {
        return rtrim((string) config('asset_automation.installer_path', base_path('ZinusAssetInstaller')), DIRECTORY_SEPARATOR);
    }

    protected function configuredToken(): string
    {
        return trim((string) config('services.asset_sync.token'));
    }

    protected function defaultServerUrl(): string
    {
        $appUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

        return $appUrl.'/api/asset-sync';
    }

    protected function timeoutSeconds(): int
    {
        return max(30, min((int) config('asset_automation.timeout_seconds', 300), 1800));
    }

    protected function resolvePowerShell(): ?string
    {
        $configured = trim((string) config('asset_automation.powershell_path'));
        if ($configured !== '') {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['pwsh.exe', 'powershell.exe'] as $candidate) {
                if ($this->commandExists($candidate)) {
                    return $candidate;
                }
            }

            return 'powershell.exe';
        }

        foreach (['pwsh', 'powershell'] as $candidate) {
            if ($this->commandExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function commandExists(string $command): bool
    {
        if (str_contains($command, DIRECTORY_SEPARATOR) || str_contains($command, '/')) {
            return is_file($command) && is_executable($command);
        }

        $probe = PHP_OS_FAMILY === 'Windows'
            ? 'where '.escapeshellarg($command)
            : 'command -v '.escapeshellarg($command);

        exec($probe.' 2>/dev/null', $output, $exitCode);

        return $exitCode === 0 && ! empty($output);
    }
}
