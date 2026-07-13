<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetRelation;
use App\Models\Category;
use App\Models\Department;
use App\Models\AssetSyncLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AssetSyncController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_code' => ['nullable', 'string', 'max:191'],
            'hostname' => ['required', 'string', 'max:191'],
            'user_name' => ['nullable', 'string', 'max:191'],
            'factory' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:191'],
            'identity_source' => ['nullable', 'string', 'max:50'],
            'is_identity_verified' => ['nullable', 'boolean'],
            'cpu' => ['nullable', 'string', 'max:150'],
            'ram_gb' => ['nullable', 'numeric', 'min:0'],
            'storage_gb' => ['nullable', 'integer', 'min:0'],
            'storage_detail' => ['nullable', 'string', 'max:255'],
            'os_name' => ['nullable', 'string', 'max:150'],
            'ip_address' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:50'],
            'agent_version' => ['nullable', 'string', 'max:50'],
            'agent_sha256' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string'],
            'anydesk_id' => ['nullable', 'string', 'max:100'],
            'monitors' => ['nullable', 'array', 'max:12'],
            'monitors.*.asset_code' => ['nullable', 'string', 'max:191'],
            'monitors.*.hostname' => ['nullable', 'string', 'max:191'],
            'monitors.*.name' => ['nullable', 'string', 'max:191'],
            'monitors.*.serial_number' => ['nullable', 'string', 'max:191'],
            'monitors.*.manufacturer' => ['nullable', 'string', 'max:150'],
            'monitors.*.brand' => ['nullable', 'string', 'max:150'],
            'monitors.*.model' => ['nullable', 'string', 'max:150'],
            'monitors.*.connection' => ['nullable', 'string', 'max:100'],
            'monitors.*.instance_name' => ['nullable', 'string', 'max:255'],
            'monitors.*.identity_source' => ['nullable', 'string', 'max:50'],
            'monitors.*.is_identity_verified' => ['nullable', 'boolean'],
            'monitors.*.screen_width_cm' => ['nullable', 'numeric', 'min:0'],
            'monitors.*.screen_height_cm' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($request, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $ip = $request->ip();
        $hostname = $data['hostname'] ?? null;
        if ($hostname !== null) {
            $hostname = trim((string) $hostname);
        }
        $serialNumber = $this->cleanAssetString($data['serial_number'] ?? null);
        $assetCode = $this->cleanAssetString($data['asset_code'] ?? null);
        if (! $assetCode) {
            $assetCode = $serialNumber ?: 'HOST-' . $this->assetCodeSegment($hostname ?: (string) Str::uuid());
        }
        $assetCode = Str::limit($assetCode, 191, '');
        $identitySource = $this->cleanAssetString($data['identity_source'] ?? null, 50);
        if (! in_array($identitySource, ['serial', 'uuid', 'hostname'], true)) {
            $identitySource = $serialNumber ? 'serial' : 'hostname';
        }
        $isIdentityVerified = filter_var($data['is_identity_verified'] ?? (bool) $serialNumber, FILTER_VALIDATE_BOOLEAN);
        $userName = $data['user_name'] ?? null;
        $incomingSha = isset($data['agent_sha256']) ? trim((string) $data['agent_sha256']) : '';
        if ($incomingSha === '') {
            $incomingSha = trim((string) $request->header('X-Agent-SHA256'));
        }

        $idempotencyKey = isset($data['idempotency_key']) ? trim((string) $data['idempotency_key']) : '';
        if ($idempotencyKey === '') {
            $idempotencyKey = trim((string) $request->header('X-Idempotency-Key'));
        }

        try {
            $token = trim((string) $request->bearerToken());
            $scope = $this->resolveTokenScope($token);
            if (! $scope) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
            $expectedSha = strtolower(trim((string) ($scope['agent_sha256'] ?? '')));
            $incomingSha = strtolower($incomingSha);
            if ($incomingSha !== '') {
                if ($expectedSha === '' || ! hash_equals($expectedSha, $incomingSha)) {
                    Log::warning('asset-sync invalid agent signature', [
                        'request_ip' => $request->ip(),
                        'headers' => $this->sanitizeHeaders($request->headers->all()),
                        'payload' => $this->sanitizePayload($request->all()),
                        'route' => $request->path(),
                        'agent_sha256_present' => true,
                        'agent_sha256_length' => strlen($incomingSha),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid agent signature',
                    ], 403);
                }
            }

            if ($idempotencyKey !== '') {
                $cacheKey = 'asset-sync:' . hash('sha256', $idempotencyKey);
                if (! Cache::add($cacheKey, true, now()->addMinutes(10))) {
                    return response()->json([
                        'success' => true,
                        'mode' => 'duplicate',
                    ]);
                }
            }

            $status = $this->normalizeStatus($data['status'] ?? null);

            // Guard against overwriting or violating unique constraints with manually managed assets
            $manualConflict = Asset::withTrashed()
                ->where('source_type', 'manual')
                ->where(function ($query) use ($serialNumber, $assetCode, $hostname) {
                    $query->where('asset_code', $assetCode);
                    if ($serialNumber) {
                        $query->orWhere('serial_number', $serialNumber);
                    }
                    if ($hostname !== '' && $hostname !== null) {
                        $query->orWhere('hostname', $hostname)
                              ->orWhere('name', $hostname);
                    }
                })
                ->exists();

            if ($manualConflict) {
                return response()->json([
                    'success' => true,
                    'message' => 'Asset is manually managed. Sync skipped.',
                ]);
            }

            $existingAsset = null;
            $restored = false;

            // 1. Primary lookup: by asset_code or serial_number (excluding manual assets)
            $candidates = Asset::withTrashed()
                ->where(function ($query) use ($serialNumber, $assetCode) {
                    $query->where('asset_code', $assetCode);
                    if ($serialNumber) {
                        $query->orWhere('serial_number', $serialNumber);
                    }
                })
                ->where(function ($query) {
                    $query->where('source_type', '!=', 'manual')
                        ->orWhereNull('source_type');
                })
                ->latest('updated_at')
                ->get();

            if ($candidates->isNotEmpty()) {
                // Pick the best candidate: prefer non-trashed, then most recently synced
                $existingAsset = $candidates
                    ->sortBy(function ($item) {
                        return [
                            $item->trashed() ? 1 : 0,
                            $item->last_synced_at ? 0 : 1,
                            -1 * ($item->last_synced_at?->timestamp ?? 0),
                        ];
                    })
                    ->first();
            }

            // 2. Fallback: by hostname (agent-synced assets only, excluding manual assets)
            if (! $existingAsset && $hostname !== '' && $hostname !== null) {
                $existingAsset = Asset::withTrashed()
                    ->where('sync_source', 'agent')
                    ->where(function ($query) {
                        $query->where('source_type', '!=', 'manual')
                            ->orWhereNull('source_type');
                    })
                    ->where(function ($query) use ($hostname) {
                        $query->where('hostname', $hostname)
                            ->orWhere('name', $hostname);
                    })
                    ->latest('updated_at')
                    ->first();
            }

            // 3. Clear ALL conflicting records that would violate UNIQUE constraints (excluding manual assets)
            //    The assets table has UNIQUE on: serial_number, asset_code, hostname
            //    Note: asset_code & name are NOT nullable; serial_number & hostname are nullable
            //    Soft-deleted rows still enforce UNIQUE in PostgreSQL
            $keepId = $existingAsset?->id;

            // Find all conflicting records and update them individually
            // (need unique placeholder per record for non-nullable unique columns)
            $conflictIds = \Illuminate\Support\Facades\DB::table('assets')
                ->where(function ($query) use ($serialNumber, $assetCode, $hostname) {
                    $query->where('asset_code', $assetCode);
                    if ($serialNumber) {
                        $query->orWhere('serial_number', $serialNumber);
                    }
                    if ($hostname !== '' && $hostname !== null) {
                        $query->orWhere('hostname', $hostname);
                    }
                })
                ->where(function ($query) {
                    $query->where('source_type', '!=', 'manual')
                        ->orWhereNull('source_type');
                })
                ->when($keepId, fn($q) => $q->where('id', '!=', $keepId))
                ->pluck('id');

            foreach ($conflictIds as $conflictId) {
                \Illuminate\Support\Facades\DB::table('assets')
                    ->where('id', $conflictId)
                    ->update([
                        'serial_number' => null,
                        'asset_code'    => '_MERGED_' . $conflictId,
                        'hostname'      => null,
                        'name'          => '_merged_' . $conflictId,
                    ]);
            }

            // 4. Restore if soft-deleted
            if ($existingAsset && $existingAsset->trashed()) {
                $existingAsset->restore();
                $restored = true;
            }

            $departmentId = null;
            $departmentName = $scope['department'] ?? ($data['department'] ?? null);
            if ($departmentName) {
                $departmentName = trim((string) $departmentName);
                $department = Department::firstOrCreate(['name' => $departmentName]);
                $departmentId = $department->id;
            }

            $factory = $scope['factory'] ?? ($data['factory'] ?? null);
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
            $specParts[] = 'Identity Source: ' . $identitySource;
            $specParts[] = 'Identity Verified: ' . ($isIdentityVerified ? 'Yes' : 'No');
            $specString = implode(' | ', $specParts);
            $brand = $this->normalizeMonitorBrand($this->cleanAssetString($data['brand'] ?? null, 150));
            $model = $this->cleanAssetString($data['model'] ?? null, 150);

            $payload = [
                'name' => $hostname,
                'hostname' => $hostname,
                'factory' => $factory,
                'category' => $categoryName,
                'category_id' => $categoryId,
                'brand' => $brand,
                'model' => $model,
                'cpu' => $data['cpu'] ?? null,
                'ram_gb' => isset($data['ram_gb']) ? (int) round($data['ram_gb']) : null,
                'serial_number' => $serialNumber,
                'specs' => $specString ?: null,
                'storage_gb' => $data['storage_gb'] ?? null,
                'storage_detail' => $data['storage_detail'] ?? null,
                'os_name' => $data['os_name'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'anydesk_id' => $data['anydesk_id'] ?? null,
                'status' => $status,
                'department_id' => $departmentId,
                'location' => $factory,
                'notes' => null,
                'sync_source' => 'agent',
                'source_type' => 'agent',
                'last_synced_at' => now(),
            ];

            if ($existingAsset) {
                if ($existingAsset->department_id) {
                    unset($payload['department_id']);
                }
                if (filled($existingAsset->location)) {
                    unset($payload['location']);
                }
                if (filled($existingAsset->factory)) {
                    unset($payload['factory']);
                }
            }
            $payload['asset_code'] = $assetCode;

            if ($existingAsset) {
                $existingAsset->fill($payload);
                $existingAsset->save();
                $asset = $existingAsset->fresh();
            } else {
                $asset = Asset::create($payload);
            }

            $mode = $existingAsset ? ($restored ? 'restored' : 'updated') : 'created';
            $monitorSync = $this->syncAttachedMonitors(
                $asset,
                $data['monitors'] ?? [],
                $departmentId,
                $factory
            );
            $counts = [
                'pc_created' => $mode === 'created' ? 1 : 0,
                'pc_updated' => in_array($mode, ['updated', 'restored'], true) ? 1 : 0,
                'monitors_created' => $monitorSync['created'],
                'monitors_updated' => $monitorSync['updated'],
                'monitors_attached' => $monitorSync['attached'],
                'monitor_links_closed' => $monitorSync['links_closed'],
            ];

            AssetSyncLog::create([
                'asset_id' => $asset->id,
                'asset_code' => $assetCode,
                'source_ip' => $ip,
                'hostname' => $hostname,
                'user_name' => $userName,
                'status' => 'success',
                'mode' => $mode,
                'message' => 'Sync OK ' . json_encode($counts),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asset synced',
                'counts' => $counts,
                'data' => [
                    'asset_id' => $asset->id,
                    'mode' => $mode,
                    'monitors' => $monitorSync,
                    'counts' => $counts,
                ],
            ]);
        } catch (Throwable $e) {
            $errorId = (string) Str::uuid();
            $context = [
                'error_id' => $errorId,
                'exception_class' => get_class($e),
                'exception' => $e->getMessage(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'payload' => $this->sanitizePayload($request->all()),
                'request_ip' => $request->ip(),
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'route' => $request->path(),
            ];
            if ($e instanceof QueryException) {
                $context['sql'] = $e->getSql();
                $context['bindings'] = $this->sanitizeBindings($e->getBindings());
            }

            Log::error('asset-sync failed', $context);

            try {
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
            } catch (Throwable $logException) {
                Log::warning('asset-sync audit log failed', [
                    'error_id' => $errorId,
                    'exception_class' => get_class($logException),
                    'message' => $logException->getMessage(),
                    'file' => $logException->getFile(),
                    'line' => $logException->getLine(),
                    'trace' => $logException->getTraceAsString(),
                    'request_ip' => $request->ip(),
                    'route' => $request->path(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Asset sync failed',
                'error_id' => $errorId,
            ], 500);
        }
    }

    protected function syncAttachedMonitors(Asset $parentAsset, array $monitors, ?int $departmentId, ?string $factory): array
    {
        if (empty($monitors)) {
            return [
                'received' => 0,
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'linked_manual' => 0,
                'attached' => 0,
                'skipped' => 0,
                'detached' => 0,
                'links_closed' => 0,
            ];
        }

        $summary = [
            'received' => count($monitors),
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'linked_manual' => 0,
            'attached' => 0,
            'skipped' => 0,
            'detached' => 0,
            'links_closed' => 0,
        ];
        $syncedMonitorIds = [];
        $monitorCategory = Category::firstOrCreate(['name' => 'Monitor']);

        foreach ($monitors as $index => $monitorPayload) {
            if (! is_array($monitorPayload)) {
                $summary['skipped']++;
                continue;
            }

            $monitorData = $this->normalizeMonitorPayload($monitorPayload, $parentAsset, $index + 1);
            if (! $monitorData['asset_code']) {
                $summary['skipped']++;
                continue;
            }

            $result = DB::transaction(function () use ($parentAsset, $departmentId, $factory, $monitorCategory, $monitorData) {
                $existingMonitor = $this->findExistingMonitor($monitorData);
                $restored = false;

                if ($existingMonitor && $existingMonitor->trashed()) {
                    if ($existingMonitor->source_type === 'manual') {
                        return ['mode' => 'skipped', 'asset' => null, 'attached' => false, 'links_closed' => 0];
                    }

                    $existingMonitor->restore();
                    $restored = true;
                }

                if (! $existingMonitor || $existingMonitor->source_type !== 'manual') {
                    $this->clearMonitorConflicts($monitorData, $existingMonitor?->id);
                }

                if ($existingMonitor && $existingMonitor->source_type === 'manual') {
                    $monitorAsset = $existingMonitor;
                    $mode = 'linked_manual';
                } else {
                    $payload = [
                        'asset_code' => $monitorData['asset_code'],
                        'name' => $monitorData['name'],
                        'hostname' => $monitorData['hostname'],
                        'category' => 'Monitor',
                        'category_id' => $monitorCategory->id,
                        'brand' => $monitorData['brand'],
                        'model' => $monitorData['model'],
                        'serial_number' => $monitorData['serial_number'],
                        'specs' => $this->buildMonitorSpecs($monitorData, $parentAsset),
                        'status' => Asset::STATUS_IN_USE,
                        'department_id' => $departmentId,
                        'location' => $parentAsset->location ?: $factory,
                        'sync_source' => 'agent',
                        'source_type' => 'agent',
                        'last_synced_at' => now(),
                    ];

                    if ($existingMonitor) {
                        if ($existingMonitor->department_id) {
                            unset($payload['department_id']);
                        }
                        if (filled($existingMonitor->location)) {
                            unset($payload['location']);
                        }

                        $existingMonitor->fill($payload);
                        $existingMonitor->save();
                        $monitorAsset = $existingMonitor->fresh();
                        $mode = $restored ? 'restored' : 'updated';
                    } else {
                        $monitorAsset = Asset::create($payload);
                        $mode = 'created';
                    }
                }

                $relationResult = $this->attachMonitorToParent($parentAsset, $monitorAsset);

                return [
                    'mode' => $mode,
                    'asset' => $monitorAsset,
                    'attached' => $relationResult['attached'],
                    'links_closed' => $relationResult['links_closed'],
                ];
            });

            if (! $result['asset']) {
                $summary['skipped']++;
                continue;
            }

            $syncedMonitorIds[] = $result['asset']->id;
            $summary['synced']++;

            if ($result['mode'] === 'created') {
                $summary['created']++;
            } elseif ($result['mode'] === 'linked_manual') {
                $summary['linked_manual']++;
            } else {
                $summary['updated']++;
            }

            if ($result['attached']) {
                $summary['attached']++;
            }
            $summary['links_closed'] += $result['links_closed'];
        }

        if (! empty($syncedMonitorIds)) {
            $summary['detached'] = $this->detachMissingAgentMonitors($parentAsset, $syncedMonitorIds);
            $summary['links_closed'] += $summary['detached'];
        }

        return $summary;
    }

    protected function normalizeMonitorPayload(array $monitor, Asset $parentAsset, int $index): array
    {
        $serial = $this->cleanAssetString($monitor['serial_number'] ?? null);
        $instanceName = $this->cleanAssetString($monitor['instance_name'] ?? null, 255);
        $rawBrand = $this->cleanAssetString($monitor['brand'] ?? ($monitor['manufacturer'] ?? null), 150);
        $brand = $this->normalizeMonitorBrand($rawBrand, $instanceName);
        $model = $this->cleanAssetString($monitor['model'] ?? null, 150);
        $connection = $this->cleanAssetString($monitor['connection'] ?? null, 100);
        $identitySource = $this->cleanAssetString($monitor['identity_source'] ?? null, 50);
        if (! in_array($identitySource, ['serial', 'wmi_hash'], true)) {
            $identitySource = $serial ? 'serial' : 'wmi_hash';
        }

        $assetCode = $this->cleanAssetString($monitor['asset_code'] ?? null);
        if (! $assetCode) {
            $fingerprint = $serial ?: $instanceName ?: ($parentAsset->asset_code . '-MON-' . $index);
            $assetCode = 'MON-' . $this->assetCodeSegment($fingerprint);
        }
        $assetCode = Str::limit($assetCode, 191, '');

        $hostname = $this->cleanAssetString($monitor['hostname'] ?? null);
        if (! $hostname) {
            $hostname = Str::limit($parentAsset->hostname ?: $parentAsset->asset_code, 150, '')
                . '-MON-' . $index;
        }
        $hostname = Str::limit($hostname, 191, '');

        $name = $this->cleanAssetString($monitor['name'] ?? null);
        if (! $name) {
            $nameParts = array_filter([$model, $serial ? "({$serial})" : null]);
            $name = $nameParts ? implode(' ', $nameParts) : $hostname;
        }
        if ($name && $rawBrand && $brand && strcasecmp($rawBrand, $brand) !== 0) {
            $normalizedName = preg_replace('/^' . preg_quote($rawBrand, '/') . '\b/i', $brand, $name, 1);
            if (is_string($normalizedName) && $normalizedName !== '') {
                $name = $normalizedName;
            }
        }
        $name = Str::limit($name, 191, '');

        return [
            'asset_code' => $assetCode,
            'hostname' => $hostname,
            'name' => $name,
            'serial_number' => $serial,
            'brand' => $brand,
            'model' => $model,
            'connection' => $connection,
            'instance_name' => $instanceName,
            'identity_source' => $identitySource,
            'is_identity_verified' => filter_var($monitor['is_identity_verified'] ?? (bool) $serial, FILTER_VALIDATE_BOOLEAN),
            'screen_width_cm' => isset($monitor['screen_width_cm']) ? (float) $monitor['screen_width_cm'] : null,
            'screen_height_cm' => isset($monitor['screen_height_cm']) ? (float) $monitor['screen_height_cm'] : null,
        ];
    }

    protected function findExistingMonitor(array $monitorData): ?Asset
    {
        $serial = $monitorData['serial_number'];

        return Asset::withTrashed()
            ->where(function ($query) use ($monitorData, $serial) {
                $query->where('asset_code', $monitorData['asset_code']);

                if ($serial) {
                    $query->orWhere(function ($nested) use ($serial) {
                        $nested->where('serial_number', $serial)
                            ->where(function ($categoryQuery) {
                                $categoryQuery->where('category', 'Monitor')
                                    ->orWhere('asset_code', 'like', 'MON-%');
                        });
                    });
                }
            })
            ->latest('updated_at')
            ->first();
    }

    protected function clearMonitorConflicts(array $monitorData, ?int $keepId): void
    {
        $conflictIds = DB::table('assets')
            ->where(function ($query) use ($monitorData) {
                $query->where('asset_code', $monitorData['asset_code']);

                if ($monitorData['serial_number']) {
                    $query->orWhere('serial_number', $monitorData['serial_number']);
                }

                if ($monitorData['hostname']) {
                    $query->orWhere('hostname', $monitorData['hostname']);
                }
            })
            ->where(function ($query) {
                $query->where('source_type', '!=', 'manual')
                    ->orWhereNull('source_type');
            })
            ->where(function ($query) {
                $query->where('category', 'Monitor')
                    ->orWhere('asset_code', 'like', 'MON-%');
            })
            ->when($keepId, fn ($query) => $query->where('id', '!=', $keepId))
            ->pluck('id');

        foreach ($conflictIds as $conflictId) {
            DB::table('assets')
                ->where('id', $conflictId)
                ->update([
                    'serial_number' => null,
                    'asset_code' => '_MERGED_MON_' . $conflictId,
                    'hostname' => null,
                    'name' => '_merged_monitor_' . $conflictId,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function attachMonitorToParent(Asset $parentAsset, Asset $monitorAsset): array
    {
        $closedRelations = AssetRelation::active()
            ->where('child_asset_id', $monitorAsset->id)
            ->where('parent_asset_id', '!=', $parentAsset->id)
            ->get();

        $closedRelations->each(function (AssetRelation $relation) use ($parentAsset) {
                $relation->update([
                    'ended_at' => now(),
                    'notes' => trim(($relation->notes ? $relation->notes . "\n" : '') . 'Auto-detached by agent sync before assigning to ' . $parentAsset->asset_code),
                ]);
            });

        $existingRelation = AssetRelation::active()
            ->where('parent_asset_id', $parentAsset->id)
            ->where('child_asset_id', $monitorAsset->id)
            ->first();

        if ($existingRelation) {
            return [
                'attached' => false,
                'links_closed' => $closedRelations->count(),
            ];
        }

        AssetRelation::create([
            'parent_asset_id' => $parentAsset->id,
            'child_asset_id' => $monitorAsset->id,
            'relation_type' => AssetRelation::TYPE_ATTACHED,
            'started_at' => now(),
            'notes' => 'Auto-attached by asset sync agent.',
        ]);

        return [
            'attached' => true,
            'links_closed' => $closedRelations->count(),
        ];
    }

    protected function detachMissingAgentMonitors(Asset $parentAsset, array $syncedMonitorIds): int
    {
        $staleRelations = AssetRelation::with('childAsset')
            ->active()
            ->where('parent_asset_id', $parentAsset->id)
            ->whereNotIn('child_asset_id', $syncedMonitorIds)
            ->whereHas('childAsset', function ($query) {
                $query->where('category', 'Monitor')
                    ->where('source_type', 'agent');
            })
            ->get();

        foreach ($staleRelations as $relation) {
            $relation->update([
                'ended_at' => now(),
                'notes' => trim(($relation->notes ? $relation->notes . "\n" : '') . 'Auto-detached because monitor was not reported by the latest agent sync.'),
            ]);
        }

        return $staleRelations->count();
    }

    protected function buildMonitorSpecs(array $monitorData, Asset $parentAsset): ?string
    {
        $parts = [
            'Host Asset: ' . $parentAsset->asset_code,
        ];

        foreach ([
            'brand' => 'Brand',
            'model' => 'Model',
            'serial_number' => 'Serial',
            'connection' => 'Connection',
            'instance_name' => 'Instance',
            'identity_source' => 'Identity Source',
        ] as $key => $label) {
            if (filled($monitorData[$key] ?? null)) {
                $parts[] = $label . ': ' . $monitorData[$key];
            }
        }

        $parts[] = 'Identity Verified: ' . (($monitorData['is_identity_verified'] ?? false) ? 'Yes' : 'No');

        if (! empty($monitorData['screen_width_cm']) || ! empty($monitorData['screen_height_cm'])) {
            $parts[] = 'Size: ' . ($monitorData['screen_width_cm'] ?: '?') . ' x ' . ($monitorData['screen_height_cm'] ?: '?') . ' cm';
        }

        return implode(' | ', $parts);
    }

    protected function normalizeMonitorBrand(?string $brand, ?string $instanceName = null): ?string
    {
        $candidate = $brand;
        if (! $candidate && $instanceName && preg_match('/^DISPLAY\\\\([A-Z0-9]{3})/i', $instanceName, $matches)) {
            $candidate = strtoupper($matches[1]);
        }

        if (! $candidate) {
            return null;
        }

        $code = strtoupper(trim($candidate));
        if (! preg_match('/^[A-Z0-9]{3}$/', $code)) {
            return $candidate;
        }

        $manufacturerNames = [
            'ACI' => 'ASUS',
            'ACM' => 'Acer',
            'ACR' => 'Acer',
            'AOC' => 'AOC',
            'APP' => 'Apple',
            'AUO' => 'AU Optronics',
            'BNQ' => 'BenQ',
            'BOE' => 'BOE',
            'CMO' => 'Chi Mei',
            'CMN' => 'Chimei',
            'DEL' => 'Dell',
            'EIZ' => 'EIZO',
            'FUJ' => 'Fujitsu',
            'GSM' => 'LG',
            'HSD' => 'HannStar',
            'HWP' => 'HP',
            'HPN' => 'HP',
            'IBM' => 'IBM',
            'IVM' => 'Iiyama',
            'LEN' => 'Lenovo',
            'LGD' => 'LG Display',
            'LPL' => 'LG Display',
            'MEI' => 'Panasonic',
            'MSI' => 'MSI',
            'NEC' => 'NEC',
            'PAN' => 'Panasonic',
            'PHI' => 'Philips',
            'PHL' => 'Philips',
            'SAM' => 'Samsung',
            'SEC' => 'Samsung',
            'SHP' => 'Sharp',
            'SNY' => 'Sony',
            'TOS' => 'Toshiba',
            'TPL' => 'Top Victory',
            'VIZ' => 'Vizio',
            'VSC' => 'ViewSonic',
        ];

        return $manufacturerNames[$code] ?? $candidate;
    }

    protected function cleanAssetString(mixed $value, int $limit = 191): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $cleaned === '' ? null : Str::limit($cleaned, $limit, '');
    }

    protected function assetCodeSegment(string $value): string
    {
        $segment = trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', $value), '-');

        if ($segment === '') {
            $segment = strtoupper(substr(hash('sha256', $value), 0, 16));
        }

        return Str::limit($segment, 187, '');
    }

    protected function validationErrorResponse(Request $request, array $errors): JsonResponse
    {
        Log::warning('asset-sync validation failed', [
            'errors' => $errors,
            'payload' => $this->sanitizePayload($request->all()),
            'request_ip' => $request->ip(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'route' => $request->path(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    protected function sanitizePayload(array $payload): array
    {
        $redactKeys = ['token', 'authorization', 'password', 'secret', 'agent_sha256', 'idempotency_key'];

        return $this->redactArray($payload, $redactKeys);
    }

    protected function redactArray(array $payload, array $redactKeys): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            $keyString = strtolower((string) $key);
            if (in_array($keyString, $redactKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->redactArray($value, $redactKeys);
                continue;
            }

            $sanitized[$key] = $this->redactSensitiveValue($value);
        }

        return $sanitized;
    }

    protected function sanitizeBindings(array $bindings): array
    {
        return array_map(function ($binding) {
            return $this->redactSensitiveValue($binding);
        }, $bindings);
    }

    protected function redactSensitiveValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        $expectedToken = trim((string) config('services.asset_sync.token'));
        if ($expectedToken !== '' && hash_equals($expectedToken, $trimmed)) {
            return '[redacted]';
        }

        if (stripos($trimmed, 'bearer ') === 0) {
            return $this->redactAuthorization($trimmed);
        }

        return $value;
    }

    protected function sanitizeHeaders(array $headers): array
    {
        foreach ($headers as $name => $values) {
            if (strtolower($name) !== 'authorization') {
                continue;
            }

            $headers[$name] = array_map(function ($value) {
                return $this->redactAuthorization((string) $value);
            }, (array) $values);
        }

        return $headers;
    }

    protected function redactAuthorization(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $trimmed;
        }

        if (stripos($trimmed, 'bearer ') !== 0) {
            return '[redacted]';
        }

        $token = trim(substr($trimmed, 7));
        if ($token === '') {
            return 'Bearer [redacted]';
        }

        $prefix = substr($token, 0, 6);
        $suffix = substr($token, -4);

        return 'Bearer ' . $prefix . '...' . $suffix;
    }

    protected function truncateTrace(string $trace, int $limit = 2000): string
    {
        if (strlen($trace) <= $limit) {
            return $trace;
        }

        return substr($trace, 0, $limit);
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

    protected function resolveTokenScope(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $scopedTokens = config('services.asset_sync.tokens');
        if (is_array($scopedTokens)) {
            if (array_key_exists($token, $scopedTokens) && is_array($scopedTokens[$token])) {
                return $scopedTokens[$token] + ['token' => $token];
            }

            foreach ($scopedTokens as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $entryToken = (string) ($entry['token'] ?? '');
                if ($entryToken !== '' && hash_equals($entryToken, $token)) {
                    return $entry;
                }
            }
        }

        $legacyToken = trim((string) config('services.asset_sync.token'));
        if ($legacyToken !== '' && hash_equals($legacyToken, $token)) {
            return [
                'token' => $token,
                'agent_sha256' => config('services.asset_sync.agent_sha256'),
                'department' => config('services.asset_sync.department'),
                'factory' => config('services.asset_sync.factory'),
            ];
        }

        return null;
    }
}
