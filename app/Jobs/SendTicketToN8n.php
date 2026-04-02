<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTicketToN8n implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $action;
    protected $extraData;

    /**
     * Create a new job instance.
     */
    public function __construct(Ticket $ticket, string $action = 'created', array $extraData = [])
    {
        $this->ticket = $ticket;
        $this->action = $action;
        $this->extraData = $extraData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.n8n.webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('N8N_WEBHOOK_URL is not configured. Skipping webhook dispatch.');
            return;
        }

        try {
            // Load necessary relationships
            $this->ticket->loadMissing(['user', 'category', 'department', 'assignedAdmin']);

            $payload = [
                'action' => $this->action,
                'ticket_id' => $this->ticket->id,
                'title' => $this->ticket->title,
                'description' => strip_tags($this->ticket->description),
                'status' => strtoupper($this->ticket->status),
                'priority' => strtoupper($this->ticket->priority),
                'category' => $this->ticket->category ? $this->ticket->category->name : '-',
                'department' => $this->ticket->department ? $this->ticket->department->name : '-',
                'created_by' => $this->ticket->user ? $this->ticket->user->name : 'Unknown User',
                'assigned_admin' => $this->ticket->assignedAdmin ? $this->ticket->assignedAdmin->name : 'Unassigned',
                
                // Add keys that match Google Sheets headers exactly (as seen in screenshot)
                'Ticket ID' => $this->ticket->id,
                'Ticket' => $this->ticket->title ?: strip_tags($this->ticket->description),
                'Catagory' => $this->ticket->category ? $this->ticket->category->name : '-',
                'Assigned To' => $this->ticket->assignedAdmin ? $this->ticket->assignedAdmin->name : 'Unassigned',
                'Created Ticket' => $this->ticket->created_at ? $this->ticket->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'month_name' => $this->ticket->created_at ? $this->ticket->created_at->timezone('Asia/Jakarta')->format('F') : null,
                'year' => $this->ticket->created_at ? $this->ticket->created_at->timezone('Asia/Jakarta')->format('Y') : null,
                'sheet_name' => $this->ticket->created_at ? 'Tickets - ' . $this->ticket->created_at->timezone('Asia/Jakarta')->format('F Y') : 'Tickets - Unknown',

                'created_at' => $this->ticket->created_at ? $this->ticket->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'updated_at' => $this->ticket->updated_at ? $this->ticket->updated_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'resolved_at' => $this->ticket->resolved_at ? $this->ticket->resolved_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'spreadsheet_id' => config('services.n8n.google_spreadsheet_id'),
            ];

            if (!empty($this->extraData)) {
                $payload = array_merge($payload, $this->extraData);
            }

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error("Failed to send ticket #{$this->ticket->id} to n8n. Status: {$response->status()}", [
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending ticket #{$this->ticket->id} to n8n.", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
