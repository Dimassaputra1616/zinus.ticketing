<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetRelation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reassign_child_asset_to_one_active_parent(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $firstParent = $this->asset('PC-001', 'Office PC', 'PC');
        $secondParent = $this->asset('LAP-001', 'Loaner Laptop', 'Laptop');
        $child = $this->asset('MON-001', 'Desk Monitor', 'Monitor');

        $this->actingAs($admin)
            ->post(route('admin.assets.relations.attach', $firstParent), [
                'child_asset_id' => $child->id,
                'notes' => 'Initial setup',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('asset_relations', [
            'parent_asset_id' => $firstParent->id,
            'child_asset_id' => $child->id,
            'ended_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assets.relations.attach', $secondParent), [
                'child_asset_id' => $child->id,
                'notes' => 'Moved to laptop',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, AssetRelation::active()->where('child_asset_id', $child->id)->count());
        $this->assertDatabaseHas('asset_relations', [
            'parent_asset_id' => $secondParent->id,
            'child_asset_id' => $child->id,
            'ended_at' => null,
        ]);
        $this->assertNotNull(
            AssetRelation::where('parent_asset_id', $firstParent->id)
                ->where('child_asset_id', $child->id)
                ->first()
                ?->ended_at
        );
    }

    public function test_pc_or_laptop_cannot_be_attached_as_child_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $parent = $this->asset('PC-001', 'Office PC', 'PC');
        $childLaptop = $this->asset('LAP-001', 'Loaner Laptop', 'Laptop');

        $this->actingAs($admin)
            ->post(route('admin.assets.relations.attach', $parent), [
                'child_asset_id' => $childLaptop->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'PC or Laptop assets cannot be attached as child assets.');

        $this->assertDatabaseCount('asset_relations', 0);
    }

    public function test_asset_detail_page_renders_operational_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = $this->asset('PC-001', 'Office PC', 'PC');
        $monitor = $this->asset('MON-001', 'Desk Monitor', 'Monitor');
        $monitor->update(['hostname' => 'monitor-frontdesk-01']);
        AssetRelation::create([
            'parent_asset_id' => $asset->id,
            'child_asset_id' => $monitor->id,
            'relation_type' => AssetRelation::TYPE_ATTACHED,
            'started_at' => now(),
            'created_by' => $admin->id,
        ]);

        $asset->update([
            'hostname' => 'office-pc-01',
            'serial_number' => 'SN-001',
            'condition' => 'good',
            'lifecycle_status' => 'active',
            'anydesk_id' => '123456789',
            'specs' => 'CPU: Intel(R) Core(TM) i5-8250U CPU @ 1.60GHz | RAM: 15.9 GB | Storage: 1034.66 GB | OS: Microsoft Windows 10 Home Single Language | IP: 10.62.36.74 | User: Administrator',
            'warranty_until' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('Technical Inventory')
            ->assertSee('Lifecycle Control')
            ->assertSee('Relationship Workspace')
            ->assertSee('Asset Mutation Timeline')
            ->assertSee('Original Agent Payload')
            ->assertSee('QR Label')
            ->assertSee('href="' . route('admin.assets.qr-label', $asset) . '"', false)
            ->assertDontSee('Raw Specs');

        $technicalInventoryHtml = \Illuminate\Support\Str::between(
            $response->getContent(),
            'Technical Inventory',
            'Ownership & Commercials'
        );

        $this->assertStringContainsString('Intel(R) Core(TM) i5-8250U CPU @ 1.60GHz', $technicalInventoryHtml);
        $this->assertStringContainsString('15.9 GB', $technicalInventoryHtml);
        $this->assertStringContainsString('1034.66 GB', $technicalInventoryHtml);
        $this->assertStringContainsString('Microsoft Windows 10 Home Single Language', $technicalInventoryHtml);
        $this->assertStringContainsString('10.62.36.74', $technicalInventoryHtml);
        $this->assertStringContainsString('monitor-frontdesk-01', $technicalInventoryHtml);
        $this->assertStringContainsString(e(route('assets.show', $monitor)), $technicalInventoryHtml);
        $this->assertStringNotContainsString('Desk Monitor', $technicalInventoryHtml);
    }

    public function test_asset_qr_label_page_renders_branded_print_label(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $assignee = User::factory()->create(['name' => 'Dimas Saputra']);
        $department = Department::create(['name' => 'IT']);
        $asset = $this->asset('PC-QR-001', 'QR Workstation', 'PC');
        $asset->update([
            'department_id' => $department->id,
            'user_id' => $assignee->id,
            'hostname' => 'pc-qr-001',
            'serial_number' => 'SN-QR-001',
            'location' => 'Zinus F3 Tangerang',
            'lifecycle_status' => 'assigned',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assets.qr-label', $asset))
            ->assertOk()
            ->assertSee('Zinus Asset Passport')
            ->assertSee('QR Workstation')
            ->assertSee('SN-QR-001')
            ->assertSeeInOrder(['QR Workstation', 'SN-QR-001'], false)
            ->assertSee('Dimas Saputra')
            ->assertSee('Zinus F3 Tangerang')
            ->assertSee('Scan To Verify')
            ->assertSee(route('assets.show', $asset), false)
            ->assertSee('<svg', false);
    }

    public function test_asset_mutation_timeline_formats_status_values_for_display(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = $this->asset('PC-STATUS-001', 'Status Workstation', 'PC');

        $this->actingAs($admin)
            ->put(route('assets.update', $asset), [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->category,
                'status' => Asset::STATUS_IN_USE,
            ])
            ->assertRedirect(route('admin.assets.pc'));

        $response = $this->actingAs($admin)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSeeText('Asset Mutation Timeline')
            ->assertSeeText('Before: Spare')
            ->assertSeeText('After: Active');

        $visibleText = preg_replace('/\s+/', ' ', strip_tags($response->getContent()));

        $this->assertStringNotContainsString('Before: available', $visibleText);
        $this->assertStringNotContainsString('After: in_use', $visibleText);
    }

    public function test_child_asset_detail_renders_current_host_relation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $parent = $this->asset('PC-001', 'Office PC', 'PC');
        $monitor = $this->asset('MON-001', 'Desk Monitor', 'Monitor');

        $relation = AssetRelation::create([
            'parent_asset_id' => $parent->id,
            'child_asset_id' => $monitor->id,
            'relation_type' => AssetRelation::TYPE_ATTACHED,
            'started_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('assets.show', $monitor))
            ->assertOk()
            ->assertSee('Current Host')
            ->assertSee('Office PC')
            ->assertSee(route('admin.assets.relations.detach', $relation), false);
    }

    public function test_monitor_detail_renders_monitor_specific_technical_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $monitor = $this->asset('MON-001', 'Desk Monitor', 'Monitor');
        $monitor->update([
            'hostname' => 'office-pc-P2419H',
            'serial_number' => 'DISPLAY-001',
            'brand' => 'Dell',
            'model' => 'P2419H',
            'specs' => 'Connection: HDMI | Instance: DISPLAY\\DEL40A9\\1&UID4352 | Identity Source: serial | Identity Verified: Yes | Size: 52 x 29 cm',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('assets.show', $monitor))
            ->assertOk();

        $technicalInventoryHtml = \Illuminate\Support\Str::between(
            $response->getContent(),
            'Technical Inventory',
            'Ownership & Commercials'
        );

        $this->assertStringContainsString('Connection', $technicalInventoryHtml);
        $this->assertStringContainsString('HDMI', $technicalInventoryHtml);
        $this->assertStringContainsString('Screen Size', $technicalInventoryHtml);
        $this->assertStringContainsString('52 x 29 cm', $technicalInventoryHtml);
        $this->assertStringContainsString('Display Instance', $technicalInventoryHtml);
        $this->assertStringContainsString('DISPLAY\\DEL40A9\\1&amp;UID4352', $technicalInventoryHtml);
        $this->assertStringContainsString('Identity Source', $technicalInventoryHtml);
        $this->assertStringContainsString('Identity Verified', $technicalInventoryHtml);
        $this->assertStringNotContainsString('>CPU<', $technicalInventoryHtml);
        $this->assertStringNotContainsString('>RAM<', $technicalInventoryHtml);
        $this->assertStringNotContainsString('>Storage<', $technicalInventoryHtml);
        $this->assertStringNotContainsString('Operating System', $technicalInventoryHtml);
        $this->assertStringNotContainsString('IP Address', $technicalInventoryHtml);
        $this->assertStringNotContainsString('AnyDesk ID', $technicalInventoryHtml);
        $this->assertStringNotContainsString('Sub Category', $technicalInventoryHtml);
    }

    public function test_non_computer_details_render_category_specific_technical_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $cases = [
            [
                'category' => 'Printer & Scanner',
                'sub_category' => 'Multifunction Printer',
                'specs' => 'Connection: LAN | Print Type: Color | Paper: A4 | Firmware: 1.2.3',
                'expected' => ['IP Address', 'Connection', 'Print Type', 'Paper Size', 'Firmware'],
                'ip_address' => '192.168.10.20',
            ],
            [
                'category' => 'Network Device',
                'sub_category' => 'Switch',
                'specs' => 'Management: Web | Ports: 24 | Speed: 1 Gbps | Firmware: 2.0 | MAC: 00:11:22:33:44:55',
                'expected' => ['IP Address', 'Management / Connection', 'Ports', 'Speed', 'Firmware', 'MAC Address'],
                'ip_address' => '192.168.10.21',
            ],
            [
                'category' => 'CCTV',
                'sub_category' => 'NVR',
                'specs' => 'Resolution: 4 MP | Channels: 8 | Storage: 2 TB | Connection: PoE',
                'expected' => ['IP Address', 'Resolution', 'Channels', 'Storage', 'Connection'],
                'ip_address' => '192.168.10.22',
            ],
            [
                'category' => 'Peripheral',
                'sub_category' => 'UPS',
                'specs' => 'Connection: USB | Interface: HID | Compatibility: Windows | Capacity: 1100 VA',
                'expected' => ['Peripheral Type', 'Connection', 'Interface', 'Compatibility', 'Capacity / Power'],
            ],
            [
                'category' => 'Software License',
                'sub_category' => 'Subscription',
                'specs' => 'Seats: 25 | Version: 2024 | Platform: Windows',
                'expected' => ['Product / License Key', 'Vendor / Product', 'License Type', 'Seats', 'Version', 'Platform', 'License Expiry'],
            ],
        ];

        foreach ($cases as $index => $case) {
            $asset = $this->asset(
                'CATEGORY-'.$index,
                $case['category'].' Asset',
                $case['category']
            );
            $asset->update([
                'sub_category' => $case['sub_category'],
                'brand' => 'Test Brand',
                'model' => 'Test Model',
                'serial_number' => 'CATEGORY-SN-'.$index,
                'ip_address' => $case['ip_address'] ?? null,
                'specs' => $case['specs'],
                'warranty_until' => now()->addYear()->toDateString(),
            ]);

            $response = $this->actingAs($admin)
                ->get(route('assets.show', $asset))
                ->assertOk();

            $technicalInventoryHtml = \Illuminate\Support\Str::between(
                $response->getContent(),
                'Technical Inventory',
                'Ownership & Commercials'
            );

            foreach ($case['expected'] as $expectedLabel) {
                $this->assertStringContainsString($expectedLabel, $technicalInventoryHtml);
            }

            $this->assertStringNotContainsString('>CPU<', $technicalInventoryHtml);
            $this->assertStringNotContainsString('>RAM<', $technicalInventoryHtml);
            $this->assertStringNotContainsString('Operating System', $technicalInventoryHtml);
            $this->assertStringNotContainsString('AnyDesk ID', $technicalInventoryHtml);
        }
    }

    private function asset(string $code, string $name, string $category): Asset
    {
        return Asset::create([
            'asset_code' => $code,
            'name' => $name,
            'category' => $category,
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);
    }
}
