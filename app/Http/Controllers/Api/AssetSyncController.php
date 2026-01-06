<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\AssetSyncLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'serial_number' => ['required', 'string', 'max:191'],
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
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($request, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $serialNumber = trim((string) $data['serial_number']);
        if ($serialNumber === '') {
            return $this->validationErrorResponse($request, [
                'serial_number' => ['Serial number is required.'],
            ]);
        }
        if (! empty($data['asset_code']) && trim((string) $data['asset_code']) !== $serialNumber) {
            return $this->validationErrorResponse($request, [
                'asset_code' => ['Asset code must match serial number.'],
            ]);
        }
        $assetCode = $serialNumber;
        $ip = $request->ip();
        $hostname = $data['hostname'] ?? null;
        if ($hostname !== null) {
            $hostname = trim((string) $hostname);
        }
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

            $conflictingAssetCode = Asset::query()
                ->where('asset_code', $serialNumber)
                ->where(function ($query) use ($serialNumber) {
                    $query->whereNull('serial_number')
                        ->orWhere('serial_number', '!=', $serialNumber);
                })
                ->exists();
            if ($conflictingAssetCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Serial number already bound to a different asset.',
                ], 409);
            }

            $serialDuplicates = Asset::query()
                ->where('serial_number', $serialNumber)
                ->count();
            if ($serialDuplicates > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate serial number detected.',
                ], 409);
            }

            if ($hostname !== '') {
                $hostnameConflict = Asset::query()
                    ->where('sync_source', 'agent')
                    ->where(function ($query) use ($hostname) {
                        $query->where('hostname', $hostname)
                            ->orWhere('name', $hostname);
                    })
                    ->where('serial_number', '!=', $serialNumber)
                    ->exists();
                if ($hostnameConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hostname already used by another asset.',
                    ], 409);
                }
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
            $specString = implode(' | ', $specParts);

            $payload = [
                'name' => $hostname,
                'hostname' => $hostname,
                'factory' => $factory,
                'category' => $categoryName,
                'category_id' => $categoryId,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'cpu' => $data['cpu'] ?? null,
                'ram_gb' => $data['ram_gb'] ?? null,
                'serial_number' => $serialNumber,
                'specs' => $specString ?: null,
                'storage_gb' => $data['storage_gb'] ?? null,
                'storage_detail' => $data['storage_detail'] ?? null,
                'os_name' => $data['os_name'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'status' => $status,
                'department_id' => $departmentId,
                'location' => $factory,
                'notes' => null,
                'sync_source' => 'agent',
                'last_synced_at' => now(),
            ];

            $existingAsset = Asset::query()
                ->where('serial_number', $serialNumber)
                ->first();
            if ($existingAsset && $existingAsset->department_id) {
                unset($payload['department_id']);
            }
            $payload['asset_code'] = $assetCode;

            $asset = Asset::updateOrCreate(
                ['serial_number' => $serialNumber],
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
                'message' => 'Asset synced',
                'data' => [
                    'asset_id' => $asset->id,
                    'mode' => $mode,
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
