<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetService
{
    /**
     * Return paginated asset list with optional filters.
     */
    public function getCategoryBreakdown(): Collection
    {
        return Asset::query()
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => null,
                    'name' => $row->category ?? 'Tidak diketahui',
                    'count' => (int) $row->total,
                ];
            });
    }

    public function countAssets(?string $category = null): int
    {
        return Asset::query()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->count();
    }

    public function getLocationBreakdown(?string $category = null): Collection
    {
        $rows = Asset::query()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->select('location', DB::raw('count(*) as total'))
            ->groupBy('location')
            ->orderByDesc('total')
            ->get();

        return $rows->map(function ($row) {
            $label = $row->location ?: 'Belum ditetapkan';

            return [
                'value' => $row->location,
                'label' => $label,
                'count' => (int) $row->total,
            ];
        });
    }

    public function getCompanyBreakdown(?string $category = null): Collection
    {
        $locations = $this->getLocationBreakdown($category);

        return $locations
            ->groupBy(function ($item) {
                $rawLocation = (string) ($item['value'] ?? '');
                if ($rawLocation === '') {
                    return 'Belum ditetapkan';
                }

                return Str::before($rawLocation, ' ') ?: 'Company Lain';
            })
            ->map(function (Collection $group, string $company) {
                return [
                    'company' => $company,
                    'count' => $group->sum(fn ($item) => $item['count']),
                    'locations' => $group->pluck('label')->unique()->values(),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    public function getDepartmentBreakdown(?string $category = null, ?string $location = null): Collection
    {
        $query = Asset::query()
            ->join('departments', 'departments.id', '=', 'assets.department_id')
            ->select('departments.id as id', 'departments.name as name', DB::raw('count(*) as total'))
            ->whereNotNull('assets.department_id')
            ->when($category, fn ($q) => $q->where('assets.category', $category))
            ->when($location, fn ($q) => $q->where('assets.location', $location));

        return $query->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'count' => (int) $row->total,
                ];
            });
    }

    public function getAssets(?string $category = null, ?string $location = null, ?int $departmentId = null, ?string $search = null): Collection
    {
        return Asset::with(['department', 'user'])
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($location, fn ($query) => $query->where('location', $location))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Base query builder with filterable scopes.
     */
    public function filteredQuery(array $filters = [], bool $includeStatus = true): Builder
    {
        $factory = $filters['factory'] ?? null;
        $departmentId = isset($filters['department']) ? (int) $filters['department'] : null;
        $category = $filters['category'] ?? null;
        $status = $includeStatus ? ($filters['status'] ?? null) : null;
        $search = $filters['search'] ?? null;

        $statusValues = $this->mapStatusFilter($status);

        return Asset::with(['department', 'user'])
            ->when($factory, function ($query) use ($factory) {
                $query->where(function ($nested) use ($factory) {
                    $nested->where('factory', $factory)
                        ->orWhere('location', $factory);
                });
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($category, function ($query) use ($category) {
                $query->where(function ($nested) use ($category) {
                    $nested->where('category', $category)
                        ->orWhereRaw('LOWER(category) = ?', [mb_strtolower($category)]);
                });
            })
            ->when(! empty($statusValues), fn ($query) => $query->whereIn('status', $statusValues))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('asset_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('factory', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('department', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->select('*');
    }

    /**
     * Return per-status summary using the filtered (non-status) query.
     */
    public function statusSummary(array $filters = []): array
    {
        $baseQuery = $this->filteredQuery($filters);
        $total = (clone $baseQuery)->count();

        $statusMap = [
            'active' => Asset::STATUS_IN_USE,
            'in_repair' => Asset::STATUS_MAINTENANCE,
            'spare' => Asset::STATUS_AVAILABLE,
            'retired' => Asset::STATUS_BROKEN,
        ];

        $counts = [];
        foreach ($statusMap as $key => $value) {
            $counts[$key] = (clone $baseQuery)->where('status', $value)->count();
        }

        return [
            'total' => $total,
            'active' => $counts['active'] ?? 0,
            'in_repair' => $counts['in_repair'] ?? 0,
            'spare' => $counts['spare'] ?? 0,
            'retired' => $counts['retired'] ?? 0,
        ];
    }

    public function getUserAssetBreakdown(?string $category = null, ?string $location = null, ?int $departmentId = null, ?string $search = null): Collection
    {
        $assets = $this->getAssets($category, $location, $departmentId, $search);

        return $assets
            ->groupBy(fn ($asset) => $asset->user_id ?? 0)
            ->map(function (Collection $items) {
                $user = $items->first()?->user;

                return [
                    'id' => $user?->id,
                    'name' => $user?->name ?? 'Belum ditetapkan',
                    'email' => $user?->email,
                    'count' => $items->count(),
                    'assets' => $items->map(fn ($asset) => [
                        'id' => $asset->id,
                        'code' => $asset->asset_code,
                        'name' => $asset->name,
                        'status' => $asset->status,
                    ])->values(),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    public function findAsset(?int $assetId): ?Asset
    {
        if (! $assetId) {
            return null;
        }

        return Asset::with(['department', 'user'])->find($assetId);
    }

    public function getAssetLogEntries(Asset $asset): Collection
    {
        $logs = $asset->assetLogs()->with('actor')->latest()->get();

        $userIds = collect();
        foreach ($logs as $log) {
            $changes = $log->metadata['changes'] ?? [];
            $previous = $log->metadata['previous'] ?? [];
            if (array_key_exists('user_id', $changes)) {
                $userIds->push($changes['user_id']);
                $userIds->push($previous['user_id'] ?? null);
            }
        }

        $userMap = User::whereIn('id', $userIds->filter()->unique())->get()->keyBy('id');

        $entries = collect();
        foreach ($logs as $log) {
            $changes = $log->metadata['changes'] ?? [];
            $previous = $log->metadata['previous'] ?? [];

            if (array_key_exists('user_id', $changes)) {
                $entries->push([
                    'type' => 'user',
                    'from' => $this->resolveUserName($previous['user_id'] ?? null, $userMap),
                    'to' => $this->resolveUserName($changes['user_id'] ?? null, $userMap),
                    'actor' => $log->actor?->name ?? 'System',
                    'created_at' => $log->created_at,
                ]);
            }

            if (array_key_exists('status', $changes)) {
                $entries->push([
                    'type' => 'status',
                    'from' => $previous['status'] ?? 'Tidak diketahui',
                    'to' => $changes['status'] ?? 'Tidak diketahui',
                    'actor' => $log->actor?->name ?? 'System',
                    'created_at' => $log->created_at,
                ]);
            }
        }

        return $entries;
    }

    public function getAssetDetailPayload(?int $assetId): ?array
    {
        $asset = $this->findAsset($assetId);
        if (! $asset) {
            return null;
        }

        return [
            'asset' => $this->formatAsset($asset),
            'logs' => $this->getAssetLogEntries($asset)->values(),
        ];
    }

    /**
     * Normalize requested status to match persisted values while keeping the original for compatibility.
     */
    protected function mapStatusFilter(?string $status): array
    {
        if (! $status) {
            return [];
        }

        $normalized = strtolower($status);

        $map = [
            'active' => Asset::STATUS_IN_USE,
            'in_repair' => Asset::STATUS_MAINTENANCE,
            'spare' => Asset::STATUS_AVAILABLE,
            'retired' => Asset::STATUS_BROKEN,
        ];

        $values = [$status, $normalized];

        if (array_key_exists($normalized, $map)) {
            $values[] = $map[$normalized];
        }

        return array_values(array_unique(array_filter($values)));
    }

    protected function resolveUserName(?int $userId, Collection $userMap): string
    {
        if (! $userId) {
            return 'Belum ditetapkan';
        }

        return $userMap->get($userId)?->name ?? 'User #' . $userId;
    }

    /**
     * Ensure category_id stays in sync for legacy column constraints.
     */
    protected function assignCategoryId(array &$data): void
    {
        if (! array_key_exists('category', $data) || ! $data['category']) {
            return;
        }

        $name = trim($data['category']);
        $normalized = $name;

        $category = Category::firstOrCreate(['name' => $normalized]);
        $data['category'] = $normalized;
        $data['category_id'] = $category->id;
    }

    protected function formatAsset(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'factory' => $asset->factory,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'category' => $asset->category,
            'serial_number' => $asset->serial_number,
            'specs' => $asset->specs,
            'status' => $asset->status,
            'location' => $asset->location,
            'department' => $asset->department?->name,
            'user' => $asset->user?->name,
            'purchase_date' => optional($asset->purchase_date)->format('Y-m-d'),
            'warranty_expired' => optional($asset->warranty_expired)->format('Y-m-d'),
            'price' => $asset->price,
            'notes' => $asset->notes,
        ];
    }

    public function store(array $data, ?User $actor): Asset
    {
        $this->assignCategoryId($data);
        $data['sync_source'] = $data['sync_source'] ?? 'manual';

        return DB::transaction(function () use ($data, $actor) {
            $asset = Asset::create($data);
            $this->log($asset, 'created', $actor, [
                'status' => $asset->status,
                'category' => $asset->category,
            ]);

            return $asset;
        });
    }

    public function update(Asset $asset, array $data, ?User $actor): Asset
    {
        $this->assignCategoryId($data);
        $data['sync_source'] = $data['sync_source'] ?? ($asset->sync_source ?? 'manual');

        return DB::transaction(function () use ($asset, $data, $actor) {
            $original = $asset->only([
                'asset_code',
                'name',
                'factory',
                'brand',
                'model',
                'category',
                'serial_number',
                'specs',
                'status',
                'department_id',
                'user_id',
                'location',
                'purchase_date',
                'warranty_expired',
                'price',
                'notes',
            ]);

            $asset->update($data);

            $changes = Arr::except($asset->getChanges(), ['updated_at']);

            if (! empty($changes)) {
                $this->log($asset, 'updated', $actor, [
                    'changes' => $changes,
                    'previous' => Arr::only($original, array_keys($changes)),
                ]);
            }

            return $asset;
        });
    }

    public function delete(Asset $asset, ?User $actor): void
    {
        DB::transaction(function () use ($asset, $actor) {
            $this->log($asset, 'deleted', $actor, [
                'asset_code' => $asset->asset_code,
                'status' => $asset->status,
            ]);

            $asset->delete();
        });
    }

    protected function log(Asset $asset, string $action, ?User $actor, array $payload = []): AssetLog
    {
        $notes = $payload['notes'] ?? null;
        $metadata = Arr::except($payload, ['notes']);

        return $asset->assetLogs()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'notes' => $notes,
            'metadata' => $metadata ?: null,
        ]);
    }
}
