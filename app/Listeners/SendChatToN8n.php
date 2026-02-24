<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendChatToN8n
{
    /**
     * Handle the event.
     */
    public function handle(MessageCreated $event): void
    {
        // Use config() instead of env() — env() returns null after config:cache
        $webhookUrl = config('services.n8n.webhook_url');

        // Only send if webhook is configured and sender is user
        if (empty($webhookUrl) || $event->senderType !== 'user') {
            return;
        }

        // Check if bot is active for this conversation
        $conversation = \App\Models\Conversation::find($event->message->conversation_id);
        if ($conversation && !$conversation->is_bot_active) {
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
                'files' => [],
                'created_at' => $event->message->created_at->toIso8601String(),
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to send chat to n8n', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message_id' => $event->message->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending chat to n8n: ' . $e->getMessage(), [
                'message_id' => $event->message->id,
            ]);
        }
    }
}
