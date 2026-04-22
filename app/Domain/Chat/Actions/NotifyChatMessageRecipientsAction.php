<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatMessage;
use Domain\Chat\Models\ChatThread;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyChatMessageRecipientsAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(ChatThread $thread, ChatMessage $message): void
    {
        $thread->loadMissing(['group', 'members.profile', 'memberRecords', 'latestMessage.user.profile']);
        $message->loadMissing('user.profile');

        $senderName = $message->user?->profile?->display_name
            ?? $message->user?->profile?->nickname
            ?? $message->user?->email
            ?? 'User';

        $threadTitle = $thread->title
            ?? $thread->group?->name
            ?? __('chat.labels.group_chat_fallback');

        $recipientIds = $thread->memberRecords
            ->pluck('user_id')
            ->filter(fn ($userId) => (int) $userId !== $message->user_id)
            ->values();

        foreach ($recipientIds as $recipientId) {
            if ($thread->isDirect()) {
                $this->createNotificationAction->execute(
                    userId: (int) $recipientId,
                    type: 'chat_message_received',
                    title: __('chat.notifications.direct_message_title'),
                    message: __('chat.notifications.direct_message_message', [
                        'user' => $senderName,
                    ]),
                    data: [
                        'chat_thread_id' => $thread->id,
                        'chat_message_id' => $message->id,
                        'chat_type' => $thread->type?->value,
                        'sender_user_id' => $message->user_id,
                    ],
                );

                continue;
            }

            $this->createNotificationAction->execute(
                userId: (int) $recipientId,
                type: 'chat_message_received',
                title: __('chat.notifications.group_message_title'),
                message: __('chat.notifications.group_message_message', [
                    'user' => $senderName,
                    'thread' => $threadTitle,
                ]),
                data: [
                    'chat_thread_id' => $thread->id,
                    'chat_message_id' => $message->id,
                    'chat_type' => $thread->type?->value,
                    'sender_user_id' => $message->user_id,
                    'group_id' => $thread->group_id,
                    'is_group_attached' => $thread->group_id !== null,
                ],
            );
        }
    }
}
