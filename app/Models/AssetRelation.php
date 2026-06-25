<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRelation extends Model
{
    use HasFactory;

    public const TYPE_ATTACHED = 'attached';

    protected $fillable = [
        'parent_asset_id',
        'child_asset_id',
        'relation_type',
        'started_at',
        'ended_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function scopeAttached(Builder $query): Builder
    {
        return $query->where('relation_type', self::TYPE_ATTACHED);
    }

    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_asset_id');
    }

    public function childAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'child_asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
