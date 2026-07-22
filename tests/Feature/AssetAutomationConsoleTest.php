<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AssetAutomationConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_asset_automation_console(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.assets.automation-console'))
            ->assertOk()
            ->assertSeeText('Automation Console')
            ->assertSeeText('Ping / SSH Probe')
            ->assertSeeText('All Auto Scan')
            ->assertSeeText('Discover Segments')
            ->assertSeeText('Remote Scan by Segment')
            ->assertSeeText('Remote Scan All')
            ->assertSeeText('Retry Failed Scan')
            ->assertSeeText('Terminal Output');
    }

    public function test_automation_console_rejects_commands_outside_whitelist(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'rm_rf_everything',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('command_key');
    }

    public function test_remote_target_scan_validates_required_targets_before_execution(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'remote_scan_targets',
                'use_config_token' => false,
                'token' => 'token-for-test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('targets');
    }

    public function test_retry_failed_scan_requires_previous_failed_targets(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $installerPath = storage_path('framework/testing/empty-asset-installer');

        File::ensureDirectoryExists($installerPath);
        config(['asset_automation.installer_path' => $installerPath]);

        $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'retry_failed_scan',
                'use_config_token' => false,
                'token' => 'token-for-test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('targets');
    }

    public function test_network_diagnostics_runs_without_powershell_setup(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        config([
            'asset_automation.installer_path' => storage_path('framework/testing/missing-installer'),
            'asset_automation.powershell_path' => '',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'network_diagnostics',
                'targets' => 'localhost',
                'probe_ping' => true,
                'probe_ssh' => false,
                'probe_winrm' => false,
                'probe_rdp' => false,
                'probe_smb' => false,
            ])
            ->assertOk()
            ->assertJsonPath('command_key', 'network_diagnostics')
            ->assertJsonPath('successful', true)
            ->assertJsonFragment(['command' => 'network-diagnostics --targets=1'])
            ->assertJsonPath('stdout', fn (string $stdout) => str_contains($stdout, 'Network diagnostics'));
    }

    public function test_all_auto_scan_requires_asset_sync_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        config(['services.asset_sync.token' => '']);

        $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'all_auto_scan',
                'segments' => '10.62.38',
                'use_config_token' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_segment_commands_pass_ip_segments_as_one_powershell_argument(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $installerPath = storage_path('framework/testing/fake-asset-installer');

        File::ensureDirectoryExists($installerPath);
        File::put($installerPath.'/Invoke-ZinusFinalizeAutomation.ps1', '');
        config([
            'asset_automation.installer_path' => $installerPath,
            'asset_automation.powershell_path' => '/usr/bin/true',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'all_auto_scan',
                'segments' => '10.62.38, 10.62.39, 10.62.36',
                'use_config_token' => false,
                'token' => 'token-for-test',
                'server_url' => 'https://app.it-ticketing.web.id/api/asset-sync',
            ])
            ->assertOk()
            ->assertJsonPath('command_key', 'all_auto_scan')
            ->assertJsonPath('async', true)
            ->assertJsonPath('running', true)
            ->assertJsonPath('command', fn (string $command) => str_contains($command, '-IpSegment 10.62.38,10.62.39,10.62.36'))
            ->assertJsonPath('command', fn (string $command) => str_contains($command, '-DeviceListPath .\zinus-web-auto-non-windows-devices.csv'))
            ->assertJsonFragment(['name' => 'zinus-web-auto-non-windows-devices.csv']);
    }

    public function test_background_automation_run_can_be_polled_until_complete(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $installerPath = storage_path('framework/testing/fake-asset-installer-poll');

        File::ensureDirectoryExists($installerPath);
        File::put($installerPath.'/Invoke-ZinusFinalizeAutomation.ps1', '');
        config([
            'asset_automation.installer_path' => $installerPath,
            'asset_automation.powershell_path' => '/usr/bin/true',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.assets.automation-console.run'), [
                'command_key' => 'all_auto_scan',
                'segments' => '10.62.38',
                'use_config_token' => false,
                'token' => 'token-for-test',
                'server_url' => 'https://app.it-ticketing.web.id/api/asset-sync',
            ])
            ->assertOk()
            ->assertJsonPath('async', true);

        $runId = $response->json('run_id');
        $status = null;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            usleep(100000);
            $status = $this->actingAs($admin)
                ->getJson(route('admin.assets.automation-console.runs.show', ['runId' => $runId]))
                ->assertOk();

            if (! $status->json('running')) {
                break;
            }
        }

        $status
            ->assertJsonPath('async', true)
            ->assertJsonPath('running', false)
            ->assertJsonPath('successful', true)
            ->assertJsonPath('exit_code', 0);
    }
}
