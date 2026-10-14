<?php

namespace App\Http\Resources\Api\V1\Chat;

use Domain\Chat\Models\ChatThread;
use Domain\Chat\Models\ChatThreadMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin ChatThread */
class ChatThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;
        $currentMember = $this->memberRecords->firstWhere('user_id', $currentUserId);
        $otherMember = $this->isDirect()
            ? $this->members->firstWhere('id', '!=', $currentUserId)
            : null;
        $lastReadAt = $currentMember instanceof ChatThreadMember ? $currentMember->last_read_at : null;
        $unreadCount = array_key_exists('unread_count', $this->resource->getAttributes())
            ? (int) $this->unread_count
            : ($this->relationLoaded('messages')
            ? $this->messages
                ->where('user_id', '!=', $currentUserId)
                ->filter(fn ($message) => $lastReadAt === null || $message->created_at?->gt($lastReadAt))
                ->count()
            : $this->messages()
                ->where('user_id', '!=', $currentUserId)
                ->when($lastReadAt, fn ($query) => $query->where('created_at', '>', $lastReadAt))
                ->count());

        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'title' => $this->title ?? $this->group?->name,
            'is_group_attached' => $this->group !== null,
            'group' => $this->group ? [
                'id' => $this->group->id,
                'slug' => $this->group->slug,
                'name' => $this->group->name,
            ] : null,
            'other_user' => $otherMember ? [
                'id' => $otherMember->id,
                'nickname' => $otherMember->profile?->nickname,
                'display_name' => $otherMember->profile?->display_name,
                'avatar_url' => $otherMember->profile?->avatar_path
                    ? Storage::disk('public')->url($otherMember->profile->avatar_path)
                    : null,
            ] : null,
            'members' => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'nickname' => $member->profile?->nickname,
                'display_name' => $member->profile?->display_name,
                'avatar_url' => $member->profile?->avatar_path
                    ? Storage::disk('public')->url($member->profile->avatar_path)
                    : null,
            ])->values(),
            'last_message' => $this->latestMessage ? new ChatMessageResource($this->latestMessage) : null,
            'unread_count' => $unreadCount,
            'last_read_at' => $lastReadAt?->toISOString(),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
