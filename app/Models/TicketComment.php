<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Jobs\SendTicketToN8n;

class TicketComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'comment',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $comment) {
            SendTicketToN8n::dispatch($comment->ticket, 'comment', [
                'comment_body' => strip_tags($comment->comment),
                'commented_by' => $comment->user ? $comment->user->name : 'Unknown User',
            ]);
        });
    }
}
