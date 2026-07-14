<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AssetImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_specific_fields_are_imported_from_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $csv = implode("\n", [
            'asset_code,name,hostname,category,sub_category,brand,model,serial_number,ip_address,cpu,ram_gb,storage_gb,os_name,anydesk_id,specs,status',
            'PC-CSV-001,CSV Workstation,PC-CSV-001,PC,Desktop,Lenovo,M80,CSV-SN-001,192.168.20.10,Intel Core i5,16,512,Windows 11 Pro,987654321,Managed device,in_use',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assets.import'), [
                'file' => UploadedFile::fake()->createWithContent('assets.csv', $csv),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assets', [
            'asset_code' => 'PC-CSV-001',
            'hostname' => 'PC-CSV-001',
            'sub_category' => 'Desktop',
            'ip_address' => '192.168.20.10',
            'cpu' => 'Intel Core i5',
            'ram_gb' => 16,
            'storage_gb' => 512,
            'os_name' => 'Windows 11 Pro',
            'anydesk_id' => '987654321',
            'specs' => 'Managed device',
        ]);
    }

    public function test_category_specific_fields_are_exported_to_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        Asset::create([
            'asset_code' => 'NET-CSV-001',
            'name' => 'CSV Core Switch',
            'category' => 'Network Device',
            'sub_category' => 'Switch',
            'ip_address' => '192.168.20.2',
            'specs' => 'Ports: 24 | Speed: 1 Gbps',
            'status' => Asset::STATUS_IN_USE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.assets.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Sub Category', $content);
        $this->assertStringContainsString('AnyDesk ID', $content);
        $this->assertStringContainsString('"AnyDesk ID",Specs', $content);
        $this->assertStringContainsString('IP Address', $content);
        $this->assertStringContainsString('Specs', $content);
        $this->assertStringContainsString('192.168.20.2', $content);
        $this->assertStringContainsString('Ports: 24 | Speed: 1 Gbps', $content);
    }
}
