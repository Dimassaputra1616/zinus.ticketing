<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'serial_number',
        'notes',
        'location',
        'department_id',
        'assigned_user_id',
        'status',
        'image_path',
        'user_name',
        'user_email',
        'purchased_date',
        'acc_num',
        'batch_number',
        'company',
        'sub_department',
        'mac_address',
        'device_name',
        'brand_model',
        'board',
        'inventory_check',
        'asset_no',
        'updated_custom_at',
    ];

    public function borrowLogs()
    {
        return $this->hasMany(BorrowLog::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
