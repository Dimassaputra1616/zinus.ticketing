<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_device_name_and_hostname_are_not_repeated_within_responsive_views(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $deviceName = 'PC-ZDI-QC-004';

        Asset::create([
            'asset_code' => 'M80-E1028700041',
            'name' => $deviceName,
            'hostname' => $deviceName,
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk();

        $visibleText = strip_tags($response->getContent());

        // The page renders one desktop row and one mobile row; each view shows the name once.
        $this->assertSame(2, substr_count($visibleText, $deviceName));
    }

    public function test_distinct_hostname_is_rendered_below_device_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'PC-ACCOUNTING-01',
            'name' => 'Accounting Workstation',
            'hostname' => 'PC-ZDI-ACC-001',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk()
            ->assertSeeText('Accounting Workstation')
            ->assertSeeText('PC-ZDI-ACC-001');
    }

    public function test_ip_address_replaces_serial_number_in_responsive_inventory_views(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'PC-NETWORK-01',
            'name' => 'Network Workstation',
            'category' => 'PC',
            'serial_number' => 'SERIAL-SHOULD-NOT-BE-SHOWN',
            'ip_address' => '192.168.10.25',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk()
            ->assertSeeText('IP Address')
            ->assertDontSeeText('Serial Number')
            ->assertDontSeeText('SERIAL-SHOULD-NOT-BE-SHOWN');

        $visibleText = strip_tags($response->getContent());

        // The page renders one desktop row and one mobile card.
        $this->assertSame(2, substr_count($visibleText, '192.168.10.25'));
    }

    public function test_inventory_can_be_searched_by_ip_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'PC-IP-SEARCH-01',
            'name' => 'Searchable Workstation',
            'category' => 'PC',
            'ip_address' => '10.20.30.40',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.pc', ['search' => '10.20.30.40']))
            ->assertOk()
            ->assertSeeText('Searchable Workstation');
    }
}
