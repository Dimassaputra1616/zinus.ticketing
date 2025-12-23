<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\AssetSyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class AssetSyncController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_code' => ['required', 'string', 'max:191'],
            'hostname' => ['required', 'string', 'max:191'],
            'user_name' => ['nullable', 'string', 'max:191'],
            'factory' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:191'],
            'cpu' => ['nullable', 'string', 'max:150'],
            'ram_gb' => ['nullable', 'numeric', 'min:0'],
            'storage_gb' => ['nullable', 'integer', 'min:0'],
            'storage_detail' => ['nullable', 'string', 'max:255'],
            'os_name' => ['nullable', 'string', 'max:150'],
            'ip_address' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $assetCode = $data['asset_code'];
        $ip = $request->ip();
        $hostname = $data['hostname'] ?? null;
        $userName = $data['user_name'] ?? null;

        try {
            $status = $this->normalizeStatus($data['status'] ?? null);

            $departmentId = null;
            if (! empty($data['department'])) {
                $department = Department::firstOrCreate(['name' => $data['department']]);
                $departmentId = $department->id;
            }

            $categoryName = $data['category'] ?? null;
            $categoryId = null;
            if ($categoryName) {
                $category = Category::firstOrCreate(['name' => $categoryName]);
                $categoryId = $category->id;
            }

            $specParts = [];
            foreach (['cpu' => 'CPU', 'ram_gb' => 'RAM', 'storage_gb' => 'Storage', 'os_name' => 'OS', 'ip_address' => 'IP'] as $key => $label) {
                if (! empty($data[$key])) {
                    $value = $data[$key];
                    if (in_array($key, ['ram_gb', 'storage_gb'])) {
                        $value = rtrim(rtrim((string) $value, '0'), '.') . ' GB';
                    }
                    $specParts[] = "{$label}: {$value}";
                }
            }
            if (! empty($data['user_name'])) {
                $specParts[] = 'User: ' . $data['user_name'];
            }
            $specString = implode(' | ', $specParts);

            $payload = [
                'name' => $hostname,
                'factory' => $data['factory'] ?? null,
                'category' => $categoryName,
                'category_id' => $categoryId,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'cpu' => $data['cpu'] ?? null,
                'ram_gb' => $data['ram_gb'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'specs' => $specString ?: null,
                'storage_gb' => $data['storage_gb'] ?? null,
                'storage_detail' => $data['storage_detail'] ?? null,
                'os_name' => $data['os_name'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'status' => $status,
                'department_id' => $departmentId,
                'location' => $data['factory'] ?? null,
                'notes' => null,
                'sync_source' => 'agent',
                'last_synced_at' => now(),
            ];

            $asset = Asset::updateOrCreate(
                ['asset_code' => $assetCode],
                $payload
            );

            $mode = $asset->wasRecentlyCreated ? 'created' : 'updated';

            AssetSyncLog::create([
                'asset_id' => $asset->id,
                'asset_code' => $assetCode,
                'source_ip' => $ip,
                'hostname' => $hostname,
                'user_name' => $userName,
                'status' => 'success',
                'mode' => $mode,
                'message' => 'Sync OK',
            ]);

            return response()->json([
                'success' => true,
                'asset_id' => $asset->id,
                'mode' => $mode,
            ]);
        } catch (Exception $e) {
            AssetSyncLog::create([
                'asset_id' => null,
                'asset_code' => $assetCode ?? null,
                'source_ip' => $ip,
                'hostname' => $hostname,
                'user_name' => $userName,
                'status' => 'failed',
                'mode' => null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Asset sync failed',
            ], 500);
        }
    }

    protected function normalizeStatus(?string $status): string
    {
        $map = [
            'active' => Asset::STATUS_IN_USE,
            'in_use' => Asset::STATUS_IN_USE,
            'in_repair' => Asset::STATUS_MAINTENANCE,
            'maintenance' => Asset::STATUS_MAINTENANCE,
            'spare' => Asset::STATUS_AVAILABLE,
            'available' => Asset::STATUS_AVAILABLE,
            'retired' => Asset::STATUS_BROKEN,
            'broken' => Asset::STATUS_BROKEN,
        ];

        $normalized = $status ? Str::snake(Str::lower($status)) : null;

        return $map[$normalized] ?? Asset::STATUS_AVAILABLE;
    }
}
