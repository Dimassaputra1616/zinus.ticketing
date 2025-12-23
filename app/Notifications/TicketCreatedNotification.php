<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private Ticket $ticket, private ?User $creator = null)
    {
        $this->ticket->loadMissing('user', 'category', 'department');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $creator = $this->creator ?: $this->ticket->user;

        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'status' => $this->ticket->status,
            'category' => optional($this->ticket->category)->name,
            'department' => optional($this->ticket->department)->name,
            'created_at' => optional($this->ticket->created_at)?->toIso8601String(),
            'created_by' => $creator?->name,
            'created_by_email' => $creator?->email,
        ];
    }
}
