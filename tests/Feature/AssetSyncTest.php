<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_sync_allows_missing_agent_sha_and_idempotency_key(): void
    {
        $token = 'test-token';
        $ip = '10.10.10.10';
        $userAgent = 'ZinusAgent/1.0';
        $configuredSha = hash('sha256', 'different');

        config([
            'services.asset_sync.token' => $token,
            'services.asset_sync.agent_sha256' => $configuredSha,
        ]);

        $payload = [
            'hostname' => 'laptop-01',
            'serial_number' => 'SN-001',
            'category' => 'Laptop',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('User-Agent', $userAgent)
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/asset-sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('assets', [
            'serial_number' => 'SN-001',
            'asset_code' => 'SN-001',
            'name' => 'laptop-01',
        ]);
    }
}
