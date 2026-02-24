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

        Log::info('[N8N] SendChatToN8n listener triggered', [
            'webhook_url' => $webhookUrl ? 'SET (' . strlen($webhookUrl) . ' chars)' : 'EMPTY/NULL',
            'sender_type' => $event->senderType,
            'message_id' => $event->message->id,
            'conversation_id' => $event->message->conversation_id,
        ]);

        // Only send if webhook is configured and sender is user
        if (empty($webhookUrl)) {
            Log::warning('[N8N] Webhook URL is empty! Set N8N_WEBHOOK_URL in .env and run php artisan config:cache');
            return;
        }

        if ($event->senderType !== 'user') {
            Log::info('[N8N] Skipping: sender is not user', ['sender_type' => $event->senderType]);
            return;
        }

        // Check if bot is active for this conversation
        $conversation = \App\Models\Conversation::find($event->message->conversation_id);
        if (!$conversation) {
            Log::warning('[N8N] Conversation not found', ['conversation_id' => $event->message->conversation_id]);
            return;
        }

        if (!$conversation->is_bot_active) {
            Log::info('[N8N] Skipping: bot is not active for conversation', ['conversation_id' => $conversation->id]);
            return;
        }

        try {
            Log::info('[N8N] Sending POST to webhook...', ['url' => $webhookUrl]);

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

            Log::info('[N8N] Response received', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::warning('[N8N] Webhook returned non-success status', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message_id' => $event->message->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[N8N] Error sending chat to n8n: ' . $e->getMessage(), [
                'message_id' => $event->message->id,
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }
}
