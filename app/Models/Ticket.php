<?php

namespace App\Models;

use App\Models\Department;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Jobs\SendTicketToN8n;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reporter_name',
        'reporter_email',
        'department_id',
        'category_id',
        'title',
        'description',
        'status',
        'priority',
        'assigned_admin_id',
        'resolution',
    ];

    protected $withCount = [
        'attachments',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function ticketLogs(): HasMany
    {
        return $this->hasMany(TicketLog::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function logs(): HasMany
    {
        return $this->ticketLogs();
    }

    protected static function booted(): void
    {
        static::created(function (self $ticket) {
            SendTicketToN8n::dispatch($ticket, 'created');
        });

        static::updated(function (self $ticket) {
            SendTicketToN8n::dispatch($ticket, 'updated');
        });
    }
}
