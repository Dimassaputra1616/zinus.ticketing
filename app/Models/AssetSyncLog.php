<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'asset_code',
        'source_ip',
        'hostname',
        'user_name',
        'status',
        'mode',
        'message',
        'created_at',
        'updated_at',
    ];
}
