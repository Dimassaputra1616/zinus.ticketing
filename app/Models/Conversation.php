<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_admin_id',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin assigned to this conversation.
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    /**
     * Get all messages for the conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the latest message.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Scope to eager load unread message count.
     */
    public function scopeWithUnreadCount($query)
    {
        return $query->withCount(['messages as unread_count' => function ($query) {
            $query->where('is_read', false)
                  ->where('user_id', '!=', null); // Only count user messages, not admin
        }]);
    }

    /**
     * Scope to include latest message for preview.
     */
    public function scopeWithLatestMessage($query)
    {
        return $query->with(['latestMessage.user']);
    }

    /**
     * Scope to get conversations with activity (has messages).
     */
    public function scopeWithActivity($query)
    {
        return $query->has('messages');
    }

    /**
     * Scope to order by latest activity.
     */
    public function scopeLatestActivity($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }

    /**
     * Mark all messages in this conversation as read.
     */
    public function markAsRead()
    {
        $this->messages()->where('is_read', false)->update(['is_read' => true]);
    }

    /**
     * Get unread message count attribute.
     */
    public function getUnreadCountAttribute()
    {
        return $this->messages()
            ->where('is_read', false)
            ->where('user_id', '!=', null)
            ->count();
    }

    /**
     * Close this conversation.
     */
    public function close()
    {
        $this->update(['is_open' => false]);
    }

    /**
     * Reopen this conversation.
     */
    public function reopen()
    {
        $this->update(['is_open' => true]);
    }
}
