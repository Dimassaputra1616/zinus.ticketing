<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendChatToN8n implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageCreated $event): void
    {
        $webhookUrl = env('N8N_WEBHOOK_URL');

        // Only send if webhook is configured and sender is user
        if (empty($webhookUrl) || $event->senderType !== 'user') {
            return;
        }

        try {
            $response = Http::timeout(5)->post($webhookUrl, [
                'conversation_id' => $event->message->conversation_id,
                'message_id' => $event->message->id,
                'message' => $event->message->body,
                'sender_type' => $event->senderType,
                'user_id' => $event->message->user_id,
                'user_name' => $event->message->user->name ?? 'Unknown User',
                'user_email' => $event->message->user->email ?? null,
                'files' => [], // Placeholder if ever needed
                'created_at' => $event->message->created_at->toIso8601String(),
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to send chat to n8n', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message_id' => $event->message->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending chat to n8n: ' . $e->getMessage(), [
                'message_id' => $event->message->id
            ]);
        }
    }
}
