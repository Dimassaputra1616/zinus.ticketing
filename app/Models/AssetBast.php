<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetBast extends Model
{
    use SoftDeletes;

    public const TYPE_HANDOVER = 'handover';
    public const TYPE_RETURN = 'return';
    public const TYPE_REPLACEMENT = 'replacement';
    public const TYPE_LOAN = 'loan';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_VOID = 'void';

    public const TYPES = [
        self::TYPE_HANDOVER,
        self::TYPE_RETURN,
        self::TYPE_REPLACEMENT,
        self::TYPE_LOAN,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ISSUED,
        self::STATUS_SIGNED,
        self::STATUS_VOID,
    ];

    protected $fillable = [
        'document_number',
        'asset_id',
        'borrow_log_id',
        'recipient_user_id',
        'department_id',
        'created_by',
        'bast_type',
        'status',
        'bast_date',
        'recipient_name',
        'recipient_email',
        'recipient_department',
        'handover_location',
        'condition_summary',
        'accessories',
        'asset_snapshot',
        'notes',
        'signed_at',
    ];

    protected $casts = [
        'bast_date' => 'date',
        'accessories' => 'array',
        'asset_snapshot' => 'array',
        'signed_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function borrowLog(): BelongsTo
    {
        return $this->belongsTo(BorrowLog::class);
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
