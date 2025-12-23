<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    /**
     * Handle secure download of a ticket attachment.
     */
    public function download(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $user = $request->user();

        if (! $user || (! $user->isAdmin() && $ticket->user_id !== $user->id)) {
            abort(403);
        }

        $disk = $attachment->disk ?? 'public';

        if (! Storage::disk($disk)->exists($attachment->stored_name)) {
            abort(404);
        }

        return Storage::disk($disk)->download($attachment->stored_name, $attachment->original_name);
    }
}
