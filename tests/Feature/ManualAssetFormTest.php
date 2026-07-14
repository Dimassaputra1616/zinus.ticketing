<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualAssetFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_printer_create_form_uses_printer_specific_fields_and_copy(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.assets.manual.create', ['category' => 'Printer & Scanner']))
            ->assertOk()
            ->assertSeeText('Add Printer / Scanner')
            ->assertSeeText('Device Type')
            ->assertSeeText('Printer Details')
            ->assertSeeText('IP Address')
            ->assertSeeText('Printer Specifications')
            ->assertSeeText('Maintenance / Supply Notes')
            ->assertSee('name="ip_address"', false)
            ->assertSee('name="specs"', false);
    }

    public function test_admin_can_store_manual_printer_network_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.assets.manual.store'), [
                'asset_code' => 'PRN-FIN-001',
                'name' => 'Printer Finance',
                'category' => 'Printer & Scanner',
                'sub_category' => 'Multifunction Printer',
                'brand' => 'Epson',
                'model' => 'L5290',
                'serial_number' => 'PRINTER-SN-001',
                'ip_address' => '192.168.10.25',
                'specs' => 'Connection: LAN | Print Type: Color | Paper: A4',
                'status' => Asset::STATUS_IN_USE,
                'condition' => 'good',
                'lifecycle_status' => 'active',
                'notes' => 'Toner and print head are in good condition.',
            ])
            ->assertRedirect(route('admin.assets.printer-scanner'));

        $this->assertDatabaseHas('assets', [
            'asset_code' => 'PRN-FIN-001',
            'category' => 'Printer & Scanner',
            'sub_category' => 'Multifunction Printer',
            'ip_address' => '192.168.10.25',
            'specs' => 'Connection: LAN | Print Type: Color | Paper: A4',
            'source_type' => 'manual',
        ]);
    }

    public function test_manual_form_copy_matches_each_asset_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $cases = [
            ['PC', 'Add PC', 'Computer Details', 'Desktop, Mini PC, Workstation'],
            ['Laptop', 'Add Laptop', 'Computer Details', 'Business Laptop, Ultrabook'],
            ['Monitor', 'Add Monitor', 'Display Details', 'Display Specifications'],
            ['Network Device', 'Add Network Device', 'Network Details', 'Network Specifications'],
            ['CCTV', 'Add CCTV / Recorder', 'Surveillance Details', 'Surveillance Specifications'],
            ['Peripheral', 'Add Peripheral', 'Peripheral Details', 'Peripheral Specifications'],
            ['Software License', 'Add Software License', 'License Details', 'Product / License Key'],
        ];

        foreach ($cases as [$category, $title, $heading, $categoryCopy]) {
            $this->actingAs($admin)
                ->get(route('admin.assets.manual.create', ['category' => $category]))
                ->assertOk()
                ->assertSeeText($title)
                ->assertSeeText($heading)
                ->assertSee($categoryCopy);
        }
    }

    public function test_admin_can_store_manual_computer_hardware_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.assets.manual.store'), [
                'asset_code' => 'PC-FIN-001',
                'name' => 'Finance Workstation',
                'hostname' => 'PC-FIN-001',
                'category' => 'PC',
                'brand' => 'Lenovo',
                'model' => 'ThinkCentre M80',
                'serial_number' => 'PC-SN-001',
                'cpu' => 'Intel Core i5-12400',
                'ram_gb' => 16,
                'storage_gb' => 512,
                'os_name' => 'Windows 11 Pro',
                'ip_address' => '192.168.10.30',
                'anydesk_id' => '123456789',
                'status' => Asset::STATUS_IN_USE,
                'condition' => 'good',
                'lifecycle_status' => 'active',
            ])
            ->assertRedirect(route('admin.assets.pc'));

        $this->assertDatabaseHas('assets', [
            'asset_code' => 'PC-FIN-001',
            'hostname' => 'PC-FIN-001',
            'cpu' => 'Intel Core i5-12400',
            'ram_gb' => 16,
            'storage_gb' => 512,
            'os_name' => 'Windows 11 Pro',
            'ip_address' => '192.168.10.30',
            'anydesk_id' => '123456789',
        ]);
    }

    public function test_admin_can_store_manual_assignee_name_without_master_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.assets.manual.store'), [
                'asset_code' => 'MON-MANUAL-ASSIGNEE',
                'name' => 'Manual Assignee Monitor',
                'category' => 'Monitor',
                'status' => Asset::STATUS_IN_USE,
                'assigned_to_name' => 'Vendor Support Desk',
            ])
            ->assertRedirect(route('admin.assets.monitor'));

        $asset = Asset::where('asset_code', 'MON-MANUAL-ASSIGNEE')->firstOrFail();

        $this->assertNull($asset->user_id);
        $this->assertSame('Vendor Support Desk', $asset->assigned_to_name);
        $this->assertSame('Vendor Support Desk', $asset->assigned_to_display_name);
    }

    public function test_admin_can_store_assignee_from_master_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $assignee = User::factory()->create(['name' => 'Dimas Saputra']);

        $this->actingAs($admin)
            ->post(route('admin.assets.manual.store'), [
                'asset_code' => 'MON-MASTER-ASSIGNEE',
                'name' => 'Master Assignee Monitor',
                'category' => 'Monitor',
                'status' => Asset::STATUS_IN_USE,
                'user_id' => $assignee->id,
                'assigned_to_name' => $assignee->name,
            ])
            ->assertRedirect(route('admin.assets.monitor'));

        $asset = Asset::where('asset_code', 'MON-MASTER-ASSIGNEE')->firstOrFail();

        $this->assertSame($assignee->id, $asset->user_id);
        $this->assertSame('Dimas Saputra', $asset->assigned_to_name);
        $this->assertSame('Dimas Saputra', $asset->assigned_to_display_name);
    }

    public function test_admin_can_resolve_master_assignee_by_name_without_javascript(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $assignee = User::factory()->create(['name' => 'Dimas Saputra']);

        $this->actingAs($admin)
            ->post(route('admin.assets.manual.store'), [
                'asset_code' => 'MON-MASTER-NAME-ASSIGNEE',
                'name' => 'Master Name Assignee Monitor',
                'category' => 'Monitor',
                'status' => Asset::STATUS_IN_USE,
                'assigned_to_name' => 'Dimas Saputra',
            ])
            ->assertRedirect(route('admin.assets.monitor'));

        $asset = Asset::where('asset_code', 'MON-MASTER-NAME-ASSIGNEE')->firstOrFail();

        $this->assertSame($assignee->id, $asset->user_id);
        $this->assertSame('Dimas Saputra', $asset->assigned_to_name);
        $this->assertSame('Dimas Saputra', $asset->assigned_to_display_name);
    }

    public function test_admin_returns_to_manual_inventory_after_updating_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'PC-FIN-EDIT-001',
            'name' => 'Finance Workstation',
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);
        $returnTo = route('admin.assets.manual.index');

        $this->actingAs($admin)
            ->put(route('admin.assets.manual.update', $asset), [
                'asset_code' => $asset->asset_code,
                'name' => 'Finance Workstation Updated',
                'category' => 'PC',
                'status' => Asset::STATUS_IN_USE,
                'condition' => 'good',
                'lifecycle_status' => 'active',
                'redirect_to' => $returnTo,
            ])
            ->assertRedirect($returnTo)
            ->assertSessionHas('success', 'Manual asset updated successfully.');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Finance Workstation Updated',
            'status' => Asset::STATUS_IN_USE,
        ]);
    }

    public function test_manual_asset_edit_form_preserves_default_manual_return_target(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = Asset::create([
            'asset_code' => 'MANUAL-DETAIL-001',
            'name' => 'Manual Detail Asset',
            'category' => 'Monitor',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.manual.edit', $asset))
            ->assertOk()
            ->assertSee('name="redirect_to" value="' . route('admin.assets.manual.index') . '"', false);
    }
}
