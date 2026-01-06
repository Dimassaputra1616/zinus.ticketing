<?php

namespace App\Models;

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

    protected $fillable = [
        'asset_code',
        'name',
        'hostname',
        'category',
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
        'sync_source',
        'last_synced_at',
        'status',
        'department_id',
        'user_id',
        'location',
        'purchase_date',
        'warranty_expired',
        'price',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expired' => 'date',
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
}
