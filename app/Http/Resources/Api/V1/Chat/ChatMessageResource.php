<?php

namespace App\Http\Resources\Api\V1\Chat;

use Domain\Chat\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin ChatMessage */
class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;
        $groupedReactions = $this->reactions
            ->groupBy('emoji')
            ->map(fn ($reactions, $emoji) => [
                'emoji' => $emoji,
                'count' => $reactions->count(),
                'reacted_by_current_user' => $currentUserId !== null
                    ? $reactions->contains(fn ($reaction) => $reaction->user_id === $currentUserId)
                    : false,
            ])
            ->values();

        return [
            'id' => $this->id,
            'thread_id' => $this->chat_thread_id,
            'message_type' => $this->message_type?->value,
            'content' => $this->content,
            'author' => [
                'id' => $this->user?->id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
                'avatar_url' => $this->user?->profile?->avatar_path
                    ? Storage::disk('public')->url($this->user->profile->avatar_path)
                    : null,
            ],
            'reply_to' => $this->replyTo ? [
                'id' => $this->replyTo->id,
                'content' => $this->replyTo->content,
                'author' => [
                    'id' => $this->replyTo->user?->id,
                    'nickname' => $this->replyTo->user?->profile?->nickname,
                    'display_name' => $this->replyTo->user?->profile?->display_name,
                    'avatar_url' => $this->replyTo->user?->profile?->avatar_path
                        ? Storage::disk('public')->url($this->replyTo->user->profile->avatar_path)
                        : null,
                ],
            ] : null,
            'reactions' => $groupedReactions,
            'attachments' => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'url' => $attachment->url,
            ])->values(),
            'edited_at' => $this->edited_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
