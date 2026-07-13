<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_USE = 'in_use';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_BROKEN = 'broken';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_IN_USE,
        self::STATUS_MAINTENANCE,
        self::STATUS_BROKEN,
    ];

    public const REMOTE_ENDPOINT_CATEGORIES = [
        'PC',
        'Laptop',
        'PC / Laptop',
        'PC/Laptop',
        'pc-laptop',
    ];

    protected $fillable = [
        'asset_code',
        'name',
        'hostname',
        'category',
        'sub_category',
        'category_id',
        'factory',
        'brand',
        'model',
        'cpu',
        'ram_gb',
        'serial_number',
        'specs',
        'storage_gb',
        'storage_detail',
        'os_name',
        'ip_address',
        'anydesk_id',
        'sync_source',
        'source_type',
        'condition',
        'lifecycle_status',
        'last_synced_at',
        'status',
        'department_id',
        'user_id',
        'location',
        'purchase_date',
        'warranty_expired',
        'warranty_until',
        'price',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expired' => 'date',
        'warranty_until' => 'date',
        'price' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categoryRel(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function scopeRemoteEndpoints(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereIn('category', self::REMOTE_ENDPOINT_CATEGORIES)
                ->orWhereHas('categoryRel', function (Builder $categoryQuery) {
                    $categoryQuery->whereIn('name', self::REMOTE_ENDPOINT_CATEGORIES);
                });
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assetLogs(): HasMany
    {
        return $this->hasMany(AssetLog::class);
    }

    public function basts(): HasMany
    {
        return $this->hasMany(AssetBast::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(AssetInspection::class);
    }

    public function childRelations(): HasMany
    {
        return $this->hasMany(AssetRelation::class, 'parent_asset_id');
    }

    public function parentRelations(): HasMany
    {
        return $this->hasMany(AssetRelation::class, 'child_asset_id');
    }

    public function activeChildRelations(): HasMany
    {
        return $this->hasMany(AssetRelation::class, 'parent_asset_id')->whereNull('ended_at');
    }

    public function activeParentRelation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssetRelation::class, 'child_asset_id')->whereNull('ended_at');
    }

    public function attachedAssets(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_relations', 'parent_asset_id', 'child_asset_id')
            ->wherePivotNull('ended_at')
            ->withPivot(['id', 'relation_type', 'started_at', 'notes'])
            ->withTimestamps();
    }

    public function attachedTo(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_relations', 'child_asset_id', 'parent_asset_id')
            ->wherePivotNull('ended_at')
            ->withPivot(['id', 'relation_type', 'started_at', 'notes'])
            ->withTimestamps();
    }
}
