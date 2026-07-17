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

    public function test_admin_sidebar_renders_nested_asset_center_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk()
            ->assertSeeText('IT Service Desk')
            ->assertSeeText('Asset Center')
            ->assertSeeText('Inventory')
            ->assertSeeText('Asset Operations')
            ->assertSeeText('Asset Governance')
            ->assertSeeText('Reports & Data')
            ->assertSee('title="Asset Center"', false)
            ->assertSee('href="' . route('assets.index') . '"', false)
            ->assertSee('href="' . route('admin.assets.import-export') . '"', false);
    }

    public function test_sidebar_dropdowns_open_only_for_the_active_admin_area(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $cases = [
            ['dashboard', [], 0],
            ['tickets.index', ['IT Service Desk'], 1],
            ['admin.assets.monitor', ['Asset Center', 'Inventory'], 2],
            ['admin.assets.inspections.index', ['Asset Center', 'Asset Operations'], 2],
            ['users.index', ['Administration'], 1],
        ];

        foreach ($cases as [$route, $expectedLabels, $openCount]) {
            $response = $this->actingAs($admin)->get(route($route))->assertOk();
            $sidebar = $this->sidebarMarkup($response->getContent());

            $this->assertSame($openCount, substr_count($sidebar, 'x-data="{ open: true }"'), "Unexpected open dropdown count for {$route}.");

            foreach ($expectedLabels as $label) {
                $this->assertStringContainsString($label, $sidebar);
            }
        }
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

    public function test_category_inventory_exposes_bulk_qr_print_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'PC-BULK-ACTION-001',
            'name' => 'Bulk Action Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk()
            ->assertSeeText('Print filtered')
            ->assertSeeText('Print selected')
            ->assertSee(route('admin.assets.qr-labels'), false)
            ->assertSee('category_group=pc', false)
            ->assertSee('x-model="selectedQrIds"', false);
    }

    public function test_bulk_qr_label_page_can_print_filtered_category_assets(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'PC-BULK-PRINT-001',
            'name' => 'Bulk Print Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        Asset::create([
            'asset_code' => 'LAP-BULK-PRINT-001',
            'name' => 'Bulk Print Laptop',
            'category' => 'Laptop',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.qr-labels', [
                'category_group' => 'pc',
                'search' => 'Bulk Print',
            ]))
            ->assertOk()
            ->assertSeeText('PC QR Labels')
            ->assertSeeText('PC-BULK-PRINT-001')
            ->assertDontSeeText('LAP-BULK-PRINT-001')
            ->assertSee('<svg', false);
    }

    public function test_bulk_qr_label_page_can_print_selected_assets(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $selected = Asset::create([
            'asset_code' => 'PC-SELECTED-QR-001',
            'name' => 'Selected QR Workstation',
            'category' => 'PC',
            'serial_number' => 'SN-SELECTED-QR-001',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        Asset::create([
            'asset_code' => 'PC-NOT-SELECTED-QR-001',
            'name' => 'Not Selected QR Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.qr-labels', ['ids' => [$selected->id]]))
            ->assertOk()
            ->assertSeeText('Selected QR Labels')
            ->assertSeeText('Selected QR Workstation')
            ->assertSeeText('SN-SELECTED-QR-001')
            ->assertSeeInOrder(['Selected QR Workstation', 'SN-SELECTED-QR-001'], false)
            ->assertDontSeeText('PC-NOT-SELECTED-QR-001')
            ->assertSee('data-print-layout="packed-labels"', false)
            ->assertSee('grid-template-columns: repeat(auto-fill, minmax(62mm, 62mm));', false)
            ->assertDontSee('page-break-after: always', false)
            ->assertSee('<svg', false);
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

    public function test_category_inventory_renders_delete_action_for_synced_assets(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'PRN-SYNC-DELETE-001',
            'name' => 'Synced Finance Printer',
            'category' => 'Printer & Scanner',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.printer-scanner'))
            ->assertOk()
            ->assertSee('action="' . route('assets.destroy', $asset) . '"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('name="redirect_to" value="' . route('admin.assets.printer-scanner') . '"', false);
    }

    public function test_category_inventory_renders_inline_assignee_editor_under_department(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $assignee = User::factory()->create(['name' => 'Dimas Saputra']);
        $asset = Asset::create([
            'asset_code' => 'PC-ASSIGNEE-INLINE-001',
            'name' => 'Assigned Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
            'user_id' => $assignee->id,
            'assigned_to_name' => $assignee->name,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.pc'))
            ->assertOk()
            ->assertSeeText('Dimas Saputra')
            ->assertSee('action="' . route('assets.assignee.update', $asset) . '"', false)
            ->assertSee('list="inline-assignee-desktop-' . $asset->id . '-list"', false)
            ->assertSee('list="inline-assignee-mobile-' . $asset->id . '-list"', false);
    }

    public function test_admin_can_update_asset_assignee_from_category_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'PC-ASSIGNEE-UPDATE-001',
            'name' => 'Assignment Update Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);
        $returnTo = route('admin.assets.pc', ['search' => 'Assignment']);

        $this->actingAs($admin)
            ->patch(route('assets.assignee.update', $asset), [
                'assigned_to_name' => 'Vendor Support Desk',
                'redirect_to' => $returnTo,
            ])
            ->assertRedirect($returnTo)
            ->assertSessionHas('success', 'Asset assignment updated.');

        $asset->refresh();

        $this->assertNull($asset->user_id);
        $this->assertSame('Vendor Support Desk', $asset->assigned_to_name);
        $this->assertSame('Vendor Support Desk', $asset->assigned_to_display_name);
    }

    public function test_inline_assignee_update_resolves_master_user_and_can_clear_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $assignee = User::factory()->create(['name' => 'Dimas Saputra']);
        $asset = Asset::create([
            'asset_code' => 'PC-ASSIGNEE-MASTER-001',
            'name' => 'Assignment Master Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);

        $this->actingAs($admin)
            ->patch(route('assets.assignee.update', $asset), [
                'assigned_to_name' => 'Dimas Saputra',
                'redirect_to' => route('admin.assets.pc'),
            ])
            ->assertRedirect(route('admin.assets.pc'));

        $asset->refresh();

        $this->assertSame($assignee->id, $asset->user_id);
        $this->assertSame('Dimas Saputra', $asset->assigned_to_name);

        $this->actingAs($admin)
            ->patch(route('assets.assignee.update', $asset), [
                'assigned_to_name' => '',
                'redirect_to' => route('admin.assets.pc'),
            ])
            ->assertRedirect(route('admin.assets.pc'));

        $asset->refresh();

        $this->assertNull($asset->user_id);
        $this->assertNull($asset->assigned_to_name);
    }

    public function test_admin_can_delete_asset_from_category_inventory_and_return_to_same_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'PRN-CAT-DELETE-001',
            'name' => 'Finance Printer To Delete',
            'category' => 'Printer & Scanner',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);
        $returnTo = route('admin.assets.printer-scanner', ['search' => 'Finance']);

        $this->actingAs($admin)
            ->delete(route('assets.destroy', $asset), [
                'redirect_to' => $returnTo,
            ])
            ->assertRedirect($returnTo)
            ->assertSessionHas('success', 'Asset dihapus.');

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
        $this->assertDatabaseHas('asset_logs', [
            'asset_id' => $asset->id,
            'action' => 'deleted',
        ]);
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

    private function sidebarMarkup(string $content): string
    {
        $start = strpos($content, 'id="tour-sidebar"');
        $end = strpos($content, '<!-- Toggle Collapse Button -->', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($content, $start, $end - $start);
    }
}
