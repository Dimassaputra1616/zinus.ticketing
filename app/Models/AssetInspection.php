<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetInspection extends Model
{
    use SoftDeletes;

    public const TYPE_ROUTINE = 'routine';
    public const TYPE_HANDOVER = 'handover';
    public const TYPE_RETURN = 'return';
    public const TYPE_REPAIR = 'repair';

    public const CONDITION_GOOD = 'good';
    public const CONDITION_MINOR_ISSUE = 'minor_issue';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_REPAIR = 'repair';

    public const RESULT_PASSED = 'passed';
    public const RESULT_NEEDS_REPAIR = 'needs_repair';
    public const RESULT_REPLACE = 'replace';
    public const RESULT_RETIRE = 'retire';

    public const TYPES = [
        self::TYPE_ROUTINE,
        self::TYPE_HANDOVER,
        self::TYPE_RETURN,
        self::TYPE_REPAIR,
    ];

    public const CONDITIONS = [
        self::CONDITION_GOOD,
        self::CONDITION_MINOR_ISSUE,
        self::CONDITION_DAMAGED,
        self::CONDITION_REPAIR,
    ];

    public const RESULTS = [
        self::RESULT_PASSED,
        self::RESULT_NEEDS_REPAIR,
        self::RESULT_REPLACE,
        self::RESULT_RETIRE,
    ];

    protected $fillable = [
        'inspection_number',
        'asset_id',
        'inspected_by',
        'inspection_type',
        'inspection_date',
        'overall_condition',
        'result',
        'checklist',
        'findings',
        'action_required',
        'next_inspection_date',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'next_inspection_date' => 'date',
        'checklist' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
