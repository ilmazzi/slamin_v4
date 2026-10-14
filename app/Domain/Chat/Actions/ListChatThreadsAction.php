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
                'latestMessage.reactions',
                'latestMessage.attachments',
                'latestMessage.replyTo.user.profile',
            ])
            ->withCount(['messages as unread_count' => fn ($messageQuery) => $messageQuery
                ->where('chat_messages.user_id', '!=', $userId)
                ->where(fn ($readQuery) => $readQuery
                    ->whereNotExists(fn ($memberQuery) => $memberQuery
                        ->from('chat_thread_members')
                        ->whereColumn('chat_thread_members.chat_thread_id', 'chat_messages.chat_thread_id')
                        ->where('chat_thread_members.user_id', $userId)
                        ->whereNotNull('chat_thread_members.last_read_at'))
                    ->orWhereExists(fn ($memberQuery) => $memberQuery
                        ->from('chat_thread_members')
                        ->whereColumn('chat_thread_members.chat_thread_id', 'chat_messages.chat_thread_id')
                        ->where('chat_thread_members.user_id', $userId)
                        ->whereColumn('chat_messages.created_at', '>', 'chat_thread_members.last_read_at')))])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }
}
