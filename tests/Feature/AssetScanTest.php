<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_asset_scan_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.assets.scan'))
            ->assertOk()
            ->assertSeeText('Scan Asset')
            ->assertSeeText('Lookup Manual')
            ->assertSee('BarcodeDetector', false)
            ->assertSee('decodeWithCanvas', false);
    }

    public function test_scan_page_redirects_when_qr_value_is_asset_detail_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = $this->asset('PC-SCAN-URL-001', 'Scan URL Workstation');

        $this->actingAs($admin)
            ->get(route('admin.assets.scan', ['q' => route('assets.show', $asset)]))
            ->assertRedirect(route('assets.show', $asset))
            ->assertSessionHas('success', 'Asset ditemukan dari scan.');
    }

    public function test_scan_page_redirects_when_manual_value_matches_asset_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = $this->asset('PC-SCAN-CODE-001', 'Scan Code Workstation');

        $this->actingAs($admin)
            ->get(route('admin.assets.scan', ['q' => 'PC-SCAN-CODE-001']))
            ->assertRedirect(route('assets.show', $asset));
    }

    public function test_scan_page_shows_manual_search_results_for_partial_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $this->asset('PC-SCAN-PARTIAL-001', 'Partial Scan Workstation');

        $this->actingAs($admin)
            ->get(route('admin.assets.scan', ['q' => 'Partial Scan']))
            ->assertOk()
            ->assertSeeText('Hasil pencarian')
            ->assertSeeText('PC-SCAN-PARTIAL-001');
    }

    private function asset(string $code, string $name): Asset
    {
        return Asset::create([
            'asset_code' => $code,
            'name' => $name,
            'hostname' => strtolower($code),
            'category' => 'PC',
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);
    }
}
