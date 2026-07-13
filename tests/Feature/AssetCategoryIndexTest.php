<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_asset_pages_use_the_shared_application_topbar(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        foreach (['assets.index', 'admin.assets.pc', 'admin.assets.manual.index'] as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee('badge-shine', false)
                ->assertSeeText(__('messages.title_manage_assets'))
                ->assertSeeText(__('messages.desc_manage_assets'));
        }
    }

    public function test_collapsed_asset_center_button_opens_the_flyout_menu(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk()
            ->assertSee('x-teleport="body"', false)
            ->assertSee('x-show="flyoutOpen && sidebarCollapsed"', false)
            ->assertSee('role="menu"', false)
            ->assertSee('href="' . route('assets.index') . '"', false)
            ->assertSee('href="' . route('admin.assets.import-export') . '"', false);
    }

    public function test_legacy_asset_overview_redirects_to_the_unified_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.assets.overview'))
            ->assertRedirect(route('assets.index'));
    }

    public function test_unified_asset_dashboard_lists_agent_and_manual_assets_from_every_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'AGENT-PC-001',
            'name' => 'Agent Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_IN_USE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        Asset::create([
            'asset_code' => 'MANUAL-CCTV-001',
            'name' => 'Manual CCTV',
            'category' => 'CCTV',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSeeText('Agent Workstation')
            ->assertSeeText('Manual CCTV')
            ->assertSee('<option value="CCTV"', false)
            ->assertSeeText('IT Asset Inventory')
            ->assertDontSeeText('TOTAL ASSETS')
            ->assertDontSeeText('Currently assigned or in use')
            ->assertDontSeeText('Available for assignment')
            ->assertDontSeeText('Offline for maintenance')
            ->assertDontSee('id="tour-chat-widget"', false);

        $content = $response->getContent();
        $inventoryPosition = strpos($content, 'IT Asset Inventory');
        $categoryPosition = strpos($content, 'CMDB Asset Categories');
        $relationsPosition = strpos($content, 'Recent Connected CMDB Relations');

        $this->assertNotFalse($inventoryPosition);
        $this->assertNotFalse($categoryPosition);
        $this->assertNotFalse($relationsPosition);
        $this->assertLessThan($inventoryPosition, $categoryPosition);
        $this->assertLessThan($inventoryPosition, $relationsPosition);
    }

    public function test_every_asset_category_index_renders_its_expected_inventory_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $cases = [
            ['admin.assets.pc', 'IP Address'],
            ['admin.assets.laptop', 'IP Address'],
            ['admin.assets.monitor', 'Connection'],
            ['admin.assets.printer-scanner', 'IP Address'],
            ['admin.assets.network-device', 'IP Address'],
            ['admin.assets.cctv', 'IP Address'],
            ['admin.assets.peripheral', 'Connection'],
            ['admin.assets.software-license', 'License / Product Key'],
        ];

        foreach ($cases as [$route, $expectedColumn]) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSeeText($expectedColumn);
        }
    }

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

    public function test_admin_returns_to_source_asset_module_after_updating_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'AGENT-PC-EDIT-001',
            'name' => 'Agent Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);
        $returnTo = route('admin.assets.pc', ['search' => 'Agent']);

        $this->actingAs($admin)
            ->put(route('assets.update', $asset), [
                'asset_code' => $asset->asset_code,
                'name' => 'Agent Workstation Updated',
                'category' => 'PC',
                'status' => Asset::STATUS_IN_USE,
                'redirect_to' => $returnTo,
            ])
            ->assertRedirect($returnTo)
            ->assertSessionHas('success', 'Asset diperbarui.');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Agent Workstation Updated',
            'status' => Asset::STATUS_IN_USE,
        ]);
    }

    public function test_agent_asset_edit_form_preserves_default_detail_return_target(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'AGENT-PC-DETAIL-001',
            'name' => 'Agent Detail Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->get(route('assets.edit', $asset))
            ->assertOk()
            ->assertSee('name="redirect_to" value="' . route('assets.show', $asset) . '"', false);
    }

    public function test_monitor_inventory_shows_connection_instead_of_ip_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'MON-DISPLAY-01',
            'name' => 'Dell P2419H',
            'category' => 'Monitor',
            'specs' => 'Connection: DisplayPort | Identity Source: serial',
            'status' => Asset::STATUS_IN_USE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.assets.monitor'))
            ->assertOk()
            ->assertSeeText('Connection')
            ->assertDontSeeText('IP Address');

        $visibleText = strip_tags($response->getContent());

        // The page renders one desktop row and one mobile card.
        $this->assertSame(2, substr_count($visibleText, 'DisplayPort'));
    }

    public function test_asset_update_ignores_external_return_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'MON-RETURN-001',
            'name' => 'Return Safety Monitor',
            'category' => 'Monitor',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->put(route('assets.update', $asset), [
                'asset_code' => $asset->asset_code,
                'name' => 'Return Safety Monitor Updated',
                'category' => 'Monitor',
                'status' => Asset::STATUS_IN_USE,
                'redirect_to' => 'https://example.invalid/admin/assets',
            ])
            ->assertRedirect(route('admin.assets.monitor'));
    }

    public function test_peripheral_inventory_shows_connection_instead_of_ip_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'PER-KEYBOARD-01',
            'name' => 'Finance Keyboard',
            'category' => 'Peripheral',
            'sub_category' => 'Keyboard',
            'specs' => 'Connection: USB | Compatibility: Windows',
            'status' => Asset::STATUS_IN_USE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.assets.peripheral'))
            ->assertOk()
            ->assertSeeText('Connection')
            ->assertDontSeeText('IP Address');

        $visibleText = strip_tags($response->getContent());

        $this->assertSame(2, substr_count($visibleText, 'USB'));
    }

    public function test_inventory_can_be_searched_by_structured_specification(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'NET-SWITCH-01',
            'name' => 'Core Switch',
            'category' => 'Network Device',
            'specs' => 'Ports: 24 | Speed: 1 Gbps',
            'status' => Asset::STATUS_IN_USE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.network-device', ['search' => '24']))
            ->assertOk()
            ->assertSeeText('Core Switch');
    }
}
