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
}
