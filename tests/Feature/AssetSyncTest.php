<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSyncTest extends TestCase
{
    use RefreshDatabase;

    protected string $token = 'test-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.asset_sync.token' => $this->token,
            'services.asset_sync.agent_sha256' => '',
        ]);
    }

    protected function syncAsset(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/asset-sync', $payload);
    }

    public function test_asset_sync_creates_new_asset(): void
    {
        $payload = [
            'asset_code' => 'SN-001',
            'hostname' => 'laptop-01',
            'serial_number' => 'SN-001',
            'factory' => 'Factory A',
            'department' => 'IT',
            'user_name' => 'Jane Doe',
        ];

        $response = $this->syncAsset($payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Asset synced',
            ]);

        $this->assertDatabaseHas('assets', [
            'serial_number' => 'SN-001',
            'asset_code' => 'SN-001',
            'name' => 'laptop-01',
        ]);
    }

    public function test_sync_updates_existing_asset_with_same_serial(): void
    {
        // First sync
        $this->syncAsset([
            'hostname' => 'pc-original',
            'serial_number' => 'SN-DUP',
            'cpu' => 'Intel i5',
        ]);

        $this->assertDatabaseHas('assets', [
            'serial_number' => 'SN-DUP',
            'hostname' => 'pc-original',
        ]);

        // Second sync — same serial, updated data
        $response = $this->syncAsset([
            'hostname' => 'pc-updated',
            'serial_number' => 'SN-DUP',
            'cpu' => 'Intel i7',
            'ip_address' => '10.0.0.5',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mode', 'updated');

        // Only 1 asset should exist, with updated data
        $this->assertEquals(1, Asset::where('serial_number', 'SN-DUP')->count());
        $asset = Asset::where('serial_number', 'SN-DUP')->first();
        $this->assertEquals('pc-updated', $asset->hostname);
        $this->assertEquals('Intel i7', $asset->cpu);
        $this->assertEquals('10.0.0.5', $asset->ip_address);
    }

    public function test_sync_resolves_hostname_conflict(): void
    {
        // Create existing asset via agent sync with hostname
        Asset::create([
            'name' => 'ZDI-NAS',
            'hostname' => 'ZDI-NAS',
            'serial_number' => 'OLD-SERIAL',
            'asset_code' => 'OLD-SERIAL',
            'sync_source' => 'agent',
            'source_type' => 'agent',
            'status' => 'in_use',
        ]);

        // Sync with a NEW serial but same hostname → should match via hostname fallback
        $response = $this->syncAsset([
            'hostname' => 'ZDI-NAS',
            'serial_number' => 'NEW-SERIAL',
        ]);

        $response->assertStatus(200);

        // The existing asset should be updated with the new serial
        $asset = Asset::where('hostname', 'ZDI-NAS')->first();
        $this->assertNotNull($asset);
        $this->assertEquals('NEW-SERIAL', $asset->serial_number);
    }

    public function test_sync_cleans_up_duplicate_serials(): void
    {
        // Sync same serial number twice to create duplicate scenario
        // First sync creates
        $this->syncAsset([
            'hostname' => 'dup-1',
            'serial_number' => 'SN-MULTI',
        ]);

        // Manually create a second record with same serial via DB to simulate
        // pre-existing duplicates (bypassing model unique constraints)
        try {
            \Illuminate\Support\Facades\DB::table('assets')->insert([
                'name' => 'dup-2',
                'hostname' => 'dup-2',
                'serial_number' => 'SN-MULTI',
                'asset_code' => 'SN-MULTI-2',
                'sync_source' => 'agent',
                'source_type' => 'agent',
                'status' => 'in_use',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // SQLite enforces UNIQUE — skip this test on SQLite
            $this->markTestSkipped('Database enforces UNIQUE on serial_number; duplicate cleanup tested in production.');
            return;
        }

        $this->assertEquals(2, Asset::where('serial_number', 'SN-MULTI')->count());

        // Sync should resolve — keep 1, soft-delete the other
        $response = $this->syncAsset([
            'hostname' => 'dup-final',
            'serial_number' => 'SN-MULTI',
        ]);

        $response->assertStatus(200);

        // Only 1 non-deleted asset should remain
        $this->assertEquals(1, Asset::where('serial_number', 'SN-MULTI')->count());
    }

    public function test_sync_restores_soft_deleted_asset(): void
    {
        $asset = Asset::create([
            'name' => 'deleted-pc',
            'hostname' => 'deleted-pc',
            'serial_number' => 'SN-DELETED',
            'asset_code' => 'SN-DELETED',
            'sync_source' => 'agent',
            'source_type' => 'agent',
            'status' => 'in_use',
        ]);
        $asset->delete();

        $this->assertSoftDeleted('assets', ['serial_number' => 'SN-DELETED']);

        $response = $this->syncAsset([
            'hostname' => 'deleted-pc',
            'serial_number' => 'SN-DELETED',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mode', 'restored');

        $this->assertDatabaseHas('assets', [
            'serial_number' => 'SN-DELETED',
            'deleted_at' => null,
        ]);
    }

    public function test_sync_creates_and_attaches_reported_monitors(): void
    {
        $response = $this->syncAsset([
            'hostname' => 'pc-monitor-host',
            'serial_number' => 'PC-SN-001',
            'category' => 'PC',
            'factory' => 'Factory A',
            'department' => 'IT',
            'monitors' => [
                [
                    'asset_code' => 'MON-DISPLAY-001',
                    'hostname' => 'pc-monitor-host-P2419H',
                    'name' => 'Dell P2419H',
                    'serial_number' => 'DISPLAY-001',
                    'manufacturer' => 'Dell',
                    'model' => 'P2419H',
                    'connection' => 'HDMI',
                    'instance_name' => 'DISPLAY\\DEL40A9\\1&UID4352',
                    'screen_width_cm' => 52,
                    'screen_height_cm' => 29,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.monitors.received', 1)
            ->assertJsonPath('data.monitors.synced', 1)
            ->assertJsonPath('data.monitors.created', 1)
            ->assertJsonPath('data.monitors.attached', 1);

        $parent = Asset::where('serial_number', 'PC-SN-001')->firstOrFail();
        $monitor = Asset::where('asset_code', 'MON-DISPLAY-001')->firstOrFail();

        $this->assertEquals('Monitor', $monitor->category);
        $this->assertEquals('pc-monitor-host-P2419H', $monitor->hostname);
        $this->assertEquals('Dell', $monitor->brand);
        $this->assertEquals('P2419H', $monitor->model);
        $this->assertStringContainsString('Connection: HDMI', $monitor->specs);

        $this->assertDatabaseHas('asset_relations', [
            'parent_asset_id' => $parent->id,
            'child_asset_id' => $monitor->id,
            'relation_type' => AssetRelation::TYPE_ATTACHED,
            'ended_at' => null,
        ]);
    }

    public function test_sync_moves_monitor_relation_to_latest_reporting_pc(): void
    {
        $this->syncAsset([
            'hostname' => 'first-pc',
            'serial_number' => 'PC-SN-FIRST',
            'category' => 'PC',
            'monitors' => [
                [
                    'asset_code' => 'MON-MOVE-001',
                    'hostname' => 'first-pc-MON-1',
                    'serial_number' => 'MON-MOVE-001',
                    'model' => 'Move Display',
                ],
            ],
        ])->assertStatus(200);

        $firstParent = Asset::where('serial_number', 'PC-SN-FIRST')->firstOrFail();
        $monitor = Asset::where('asset_code', 'MON-MOVE-001')->firstOrFail();

        $this->syncAsset([
            'hostname' => 'second-pc',
            'serial_number' => 'PC-SN-SECOND',
            'category' => 'PC',
            'monitors' => [
                [
                    'asset_code' => 'MON-MOVE-001',
                    'hostname' => 'second-pc-MON-1',
                    'serial_number' => 'MON-MOVE-001',
                    'model' => 'Move Display',
                ],
            ],
        ])->assertStatus(200)
            ->assertJsonPath('data.monitors.updated', 1)
            ->assertJsonPath('data.monitors.attached', 1);

        $secondParent = Asset::where('serial_number', 'PC-SN-SECOND')->firstOrFail();
        $monitor->refresh();

        $this->assertEquals('second-pc-MON-1', $monitor->hostname);
        $this->assertEquals(1, AssetRelation::active()->where('child_asset_id', $monitor->id)->count());
        $this->assertDatabaseHas('asset_relations', [
            'parent_asset_id' => $secondParent->id,
            'child_asset_id' => $monitor->id,
            'ended_at' => null,
        ]);
        $this->assertDatabaseMissing('asset_relations', [
            'parent_asset_id' => $firstParent->id,
            'child_asset_id' => $monitor->id,
            'ended_at' => null,
        ]);
    }
}
