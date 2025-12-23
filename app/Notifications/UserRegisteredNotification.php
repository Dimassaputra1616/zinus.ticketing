<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(private User $registeredUser, private ?User $creator = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->registeredUser->id,
            'name' => $this->registeredUser->name,
            'email' => $this->registeredUser->email,
            'role' => $this->registeredUser->role,
            'created_at' => optional($this->registeredUser->created_at)?->toIso8601String(),
            'created_by' => $this->creator?->name,
            'created_by_email' => $this->creator?->email,
        ];
    }
}
