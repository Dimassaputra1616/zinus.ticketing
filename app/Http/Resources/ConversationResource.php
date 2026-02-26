<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'is_open' => $this->is_open,
            'is_bot_active' => (bool) $this->is_bot_active,
            'handler_mode' => $this->is_bot_active ? 'bot' : 'admin',
            'assigned_admin_id' => $this->assigned_admin_id,
            'unread_count' => $this->when(
                isset($this->unread_count),
                $this->unread_count
            ),
            'latest_message' => new MessageResource(
                $this->whenLoaded('latestMessage')
            ),
            'messages' => MessageResource::collection(
                $this->whenLoaded('messages')
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
