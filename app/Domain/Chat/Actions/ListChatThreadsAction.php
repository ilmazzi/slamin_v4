<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;
use Illuminate\Database\Eloquent\Collection;

class ListChatThreadsAction
{
    public function execute(int $userId): Collection
    {
        return ChatThread::query()
            ->whereHas('memberRecords', fn ($query) => $query->where('user_id', $userId))
            ->with([
                'group',
                'members.profile',
                'memberRecords',
                'latestMessage.user.profile',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }
}
