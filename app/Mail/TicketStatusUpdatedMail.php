<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Ticket $ticket;
    public string $oldStatus;
    public string $newStatus;
    public ?User $admin;
    public string $ticketUrl;

    public function __construct(Ticket $ticket, string $oldStatus, string $newStatus, ?User $admin = null)
    {
        $this->ticket = $ticket;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->admin = $admin;
        $this->ticketUrl = route('user.tickets.show', $ticket);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: "[Update Tiket] #{$this->ticket->id} - Status: {$this->formatStatus($this->newStatus)}",
        );
    }

    public function content(): Content
    {
        $updatedAt = $this->ticket->updated_at?->format('d M Y H:i') ?? now()->format('d M Y H:i');

        return new Content(
            view: 'emails.ticket-status-updated',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => $this->ticketUrl,
                'oldStatusLabel' => $this->formatStatus($this->oldStatus),
                'newStatusLabel' => $this->formatStatus($this->newStatus),
                'adminName' => $this->admin?->name ?? 'Admin',
                'updatedAt' => $updatedAt,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function formatStatus(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}
