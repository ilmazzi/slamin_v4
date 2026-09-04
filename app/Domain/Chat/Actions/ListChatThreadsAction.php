<?php

namespace Domain\Chat\Actions;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Chat\Models\ChatThread;
use Illuminate\Database\Eloquent\Collection;

class ListChatThreadsAction
{
    public function execute(int $userId): Collection
    {
        return ChatThread::query()
            ->where(function ($query) use ($userId) {
                $query
                    ->where(function ($subQuery) use ($userId) {
                        $subQuery
                            ->whereNull('group_id')
                            ->whereHas('memberRecords', fn ($memberQuery) => $memberQuery->where('user_id', $userId));
                    })
                    ->orWhere(function ($subQuery) use ($userId) {
                        $subQuery
                            ->whereNotNull('group_id')
                            ->whereHas('group.members', fn ($memberQuery) => $memberQuery
                                ->where('user_id', $userId)
                                ->where('status', GroupMemberStatus::Active));
                    });
            })
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
