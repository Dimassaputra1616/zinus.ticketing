<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoteSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_system_only_lists_pc_and_laptop_endpoints(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $pc = $this->asset('PC-REMOTE-001', 'Finance PC', 'PC', '10.10.1.10');
        $laptop = $this->asset('LAP-REMOTE-001', 'Sales Laptop', 'Laptop', '10.10.1.11');
        $monitor = $this->asset('MON-REMOTE-001', 'Finance Monitor', 'Monitor');
        $printer = $this->asset('PRN-REMOTE-001', 'Finance Printer', 'Printer & Scanner', '10.10.1.12');

        $monitor->update(['anydesk_id' => 'MONITOR-MUST-NOT-APPEAR']);
        $printer->update(['anydesk_id' => 'PRINTER-MUST-NOT-APPEAR']);

        $this->actingAs($admin)
            ->get(route('remote-system.index'))
            ->assertOk()
            ->assertSeeText($pc->name)
            ->assertSeeText($laptop->name)
            ->assertDontSeeText($monitor->name)
            ->assertDontSeeText($printer->name)
            ->assertDontSee('MONITOR-MUST-NOT-APPEAR')
            ->assertDontSee('PRINTER-MUST-NOT-APPEAR');
    }

    public function test_remote_system_uses_compact_paginated_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        foreach (range(1, 12) as $index) {
            $this->asset(
                sprintf('PC-PAGE-%03d', $index),
                sprintf('Remote Device %02d', $index),
                'PC',
                "10.10.2.{$index}"
            );
        }

        $this->actingAs($admin)
            ->get(route('remote-system.index'))
            ->assertOk()
            ->assertSeeText('Remote Device 01')
            ->assertSeeText('Remote Device 10')
            ->assertDontSeeText('Remote Device 11')
            ->assertSeeText('Page 1 of 2')
            ->assertSeeText('Owner / Department')
            ->assertSeeText('Live Status');

        $this->actingAs($admin)
            ->get(route('remote-system.index', ['page' => 2]))
            ->assertOk()
            ->assertSeeText('Remote Device 11')
            ->assertSeeText('Remote Device 12')
            ->assertDontSeeText('Remote Device 01');
    }

    public function test_remote_system_can_filter_anydesk_readiness_and_search_endpoints(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $ready = $this->asset('PC-READY-001', 'Ready Finance PC', 'PC', '10.10.3.10');
        $missing = $this->asset('PC-MISSING-001', 'Missing Warehouse PC', 'PC', '10.10.3.11');
        $ready->update(['anydesk_id' => '123456789']);

        $this->actingAs($admin)
            ->get(route('remote-system.index', ['connection' => 'ready']))
            ->assertOk()
            ->assertSeeText($ready->name)
            ->assertDontSeeText($missing->name);

        $this->actingAs($admin)
            ->get(route('remote-system.index', ['connection' => 'missing']))
            ->assertOk()
            ->assertSeeText($missing->name)
            ->assertDontSeeText($ready->name);

        $this->actingAs($admin)
            ->get(route('remote-system.index', ['q' => 'Warehouse']))
            ->assertOk()
            ->assertSeeText($missing->name)
            ->assertDontSeeText($ready->name);
    }

    public function test_remote_system_asset_links_preserve_the_current_page_as_return_target(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = $this->asset('PC-RETURN-001', 'Return Target PC', 'PC', '10.10.4.10');
        $remoteUrl = route('remote-system.index', ['connection' => 'missing', 'q' => 'Return']);

        $this->actingAs($admin)
            ->get($remoteUrl)
            ->assertOk()
            ->assertSee(route('assets.show', ['asset' => $asset, 'return_to' => $remoteUrl]), false)
            ->assertSee(route('assets.edit', ['asset' => $asset, 'return_to' => $remoteUrl]), false);
    }

    private function asset(string $code, string $name, string $category, ?string $ipAddress = null): Asset
    {
        return Asset::create([
            'asset_code' => $code,
            'name' => $name,
            'hostname' => $code,
            'category' => $category,
            'ip_address' => $ipAddress,
            'status' => Asset::STATUS_IN_USE,
            'source_type' => 'agent',
            'sync_source' => 'agent',
        ]);
    }
}
