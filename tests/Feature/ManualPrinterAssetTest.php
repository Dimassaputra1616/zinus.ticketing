<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPrinterAssetTest extends TestCase
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
}
