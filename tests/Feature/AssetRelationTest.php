<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetRelation;
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
            'rustdesk_id' => '123456789',
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
