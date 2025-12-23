<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Ticket $ticket;
    public ?User $actor;
    public string $ticketUrl;

    public function __construct(Ticket $ticket, ?User $actor = null)
    {
        $this->ticket = $ticket;
        $this->actor = $actor;
        $this->ticketUrl = route('user.tickets.show', $ticket);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Ticket Baru',
        );
    }

    public function content(): Content
    {
        $categoryName = $this->ticket->category?->name ?? 'Tidak ada kategori';
        $departmentName = $this->ticket->department?->name ?? 'Tidak ada departemen';
        $priorityLabel = $this->ticket->priority ? ucfirst(str_replace('_', ' ', $this->ticket->priority)) : 'Tidak ditentukan';
        $descriptionPreview = Str::limit(strip_tags((string) $this->ticket->description), 200);

        return new Content(
            view: 'emails.ticket-created',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => $this->ticketUrl,
                'actorName' => $this->actor?->name ?? 'User',
                'categoryName' => $categoryName,
                'departmentName' => $departmentName,
                'priorityLabel' => $priorityLabel,
                'descriptionPreview' => $descriptionPreview,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
