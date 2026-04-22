<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThread;
use Illuminate\Database\Eloquent\Collection;

class ListChatMessagesAction
{
    public function execute(ChatThread $thread): Collection
    {
        return ChatMessage::query()
            ->where('chat_thread_id', $thread->id)
            ->with(['user.profile', 'replyTo.user.profile', 'reactions.user.profile', 'attachments'])
            ->orderBy('created_at')
            ->get();
    }
}
