<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetBast;
use App\Models\AssetInspection;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_bast_for_asset(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $department = Department::create(['name' => 'IT']);
        $recipient = User::factory()->create([
            'department_id' => $department->id,
            'name' => 'Dimas Saputra',
            'email' => 'dimas@example.test',
        ]);
        $asset = $this->asset('PC-BAST-001', 'Front Desk PC', 'PC', [
            'department_id' => $department->id,
            'user_id' => $recipient->id,
            'serial_number' => 'SN-BAST-001',
            'condition' => 'good',
            'location' => 'Front Office',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.assets.bast.store'), [
                'document_number' => 'BAST/202607/9001',
                'asset_id' => $asset->id,
                'recipient_user_id' => $recipient->id,
                'department_id' => $department->id,
                'bast_type' => AssetBast::TYPE_HANDOVER,
                'status' => AssetBast::STATUS_ISSUED,
                'bast_date' => '2026-07-08',
                'recipient_name' => 'Dimas Saputra',
                'recipient_email' => 'dimas@example.test',
                'recipient_department' => 'IT',
                'handover_location' => 'IT Room',
                'condition_summary' => 'Good',
                'accessories' => ['Charger', 'Mouse'],
                'notes' => 'Ready for handover.',
                'photos' => [
                    UploadedFile::fake()->image('handover-photo.jpg', 900, 600),
                ],
            ]);

        $bast = AssetBast::firstOrFail();

        $response
            ->assertRedirect(route('admin.assets.bast.show', $bast))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('asset_basts', [
            'document_number' => 'BAST/202607/9001',
            'asset_id' => $asset->id,
            'recipient_name' => 'Dimas Saputra',
            'status' => AssetBast::STATUS_ISSUED,
        ]);
        $this->assertSame(['Charger', 'Mouse'], $bast->accessories);
        $this->assertSame('PC-BAST-001', $bast->asset_snapshot['asset_code']);
        $this->assertCount(1, $bast->photos);
        Storage::disk('public')->assertExists($bast->photos[0]['path']);

        $this->actingAs($admin)
            ->get(route('admin.assets.bast.print', $bast))
            ->assertOk()
            ->assertSee('Berita Acara Serah Terima Asset')
            ->assertSee('Lampiran Foto Asset')
            ->assertSee('Foto 1');
    }

    public function test_admin_can_create_inspection_and_view_report(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $asset = $this->asset('LAP-INSP-001', 'Loaner Laptop', 'Laptop', [
            'serial_number' => 'SN-INSP-001',
            'condition' => 'good',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.assets.inspections.store'), [
                'inspection_number' => 'INSP/202607/9001',
                'asset_id' => $asset->id,
                'inspection_type' => AssetInspection::TYPE_ROUTINE,
                'inspection_date' => '2026-07-08',
                'overall_condition' => AssetInspection::CONDITION_MINOR_ISSUE,
                'result' => AssetInspection::RESULT_NEEDS_REPAIR,
                'checklist' => [
                    'power' => 'ok',
                    'display' => 'issue',
                    'keyboard_mouse' => 'ok',
                    'network' => 'ok',
                    'storage' => 'ok',
                    'ports' => 'ok',
                    'physical' => 'issue',
                    'security' => 'ok',
                ],
                'findings' => 'Screen flicker ditemukan saat test.',
                'action_required' => 'Cek LCD cable.',
                'next_inspection_date' => '2026-08-08',
                'photos' => [
                    UploadedFile::fake()->image('inspection-photo.jpg', 900, 600),
                ],
            ]);

        $inspection = AssetInspection::firstOrFail();

        $response
            ->assertRedirect(route('admin.assets.inspections.show', $inspection))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('asset_inspections', [
            'inspection_number' => 'INSP/202607/9001',
            'asset_id' => $asset->id,
            'result' => AssetInspection::RESULT_NEEDS_REPAIR,
        ]);
        $this->assertSame('issue', $inspection->checklist['display']);
        $this->assertCount(1, $inspection->photos);
        Storage::disk('public')->assertExists($inspection->photos[0]['path']);

        $this->actingAs($admin)
            ->get(route('admin.assets.reports.index', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('INSP/202607/9001');

        $this->actingAs($admin)
            ->get(route('admin.assets.inspections.print', $inspection))
            ->assertOk()
            ->assertSee('Lampiran Foto Inspection')
            ->assertSee('Foto 1');
    }

    private function asset(string $code, string $name, string $category, array $overrides = []): Asset
    {
        return Asset::create(array_merge([
            'asset_code' => $code,
            'name' => $name,
            'category' => $category,
            'status' => Asset::STATUS_AVAILABLE,
            'source_type' => 'manual',
            'sync_source' => 'manual',
        ], $overrides));
    }
}
