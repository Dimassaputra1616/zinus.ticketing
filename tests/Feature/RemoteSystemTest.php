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

        $monitor->update(['rustdesk_id' => 'MONITOR-MUST-NOT-APPEAR']);
        $printer->update(['rustdesk_id' => 'PRINTER-MUST-NOT-APPEAR']);

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
