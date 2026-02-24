<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ConversationApiController extends Controller
{
    /**
     * List all open conversations with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = Conversation::where('is_open', true)
            ->with(['user', 'latestMessage'])
            ->withUnreadCount()
            ->latestActivity()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ConversationResource::collection($conversations)->response()->getData(),
        ]);
    }

    /**
     * Get single conversation with all messages
     */
    public function show(int $id): JsonResponse
    {
        $conversation = Conversation::with(['user', 'messages.user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation),
        ]);
    }

    /**
     * Send admin reply to conversation
     */
    public function sendMessage(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $conversation = Conversation::findOrFail((int) $id);

        // Use atomic lock to prevent race conditions (double execution from n8n)
        // Lock key based on conversation ID, wait max 3 seconds
        $lock = \Illuminate\Support\Facades\Cache::lock('conversation_message_' . $conversation->id, 3);

        if (!$lock->get()) {
            // Could not get lock, meaning another request is processing for this conversation
            // Assuming it's a duplicate/concurrent request
            return response()->json([
                'success' => true,
                'message' => 'Concurrent request ignored',
                'data' => null
            ], 200);
        }

        try {
            // Prevent duplicate messages from n8n (within 10 seconds)
            $lastMessage = Message::where('conversation_id', $conversation->id)
                ->whereNull('user_id') // From admin/system
                ->latest()
                ->first();

            if ($lastMessage && 
                $lastMessage->body === $request->body && 
                $lastMessage->created_at->diffInSeconds(now()) < 10
            ) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate message ignored',
                    'data' => new MessageResource($lastMessage->load('user')),
                ], 200);
            }

            // Create admin message (user_id = null for admin)
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => null, // Admin message
                'body' => $request->body,
                'is_read' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => new MessageResource($message->load('user')),
            ], 201);
            
        } finally {
            $lock->release();
        }
    }

    /**
     * Handoff conversation to human agent (disable bot)
     */
    public function handoff(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::findOrFail((int) $id);
        
        $conversation->disableBot();

        return response()->json([
            'success' => true,
            'message' => 'Conversation handed off to agent. Bot is now disabled.',
            'data' => new ConversationResource($conversation),
        ]);
    }
}
