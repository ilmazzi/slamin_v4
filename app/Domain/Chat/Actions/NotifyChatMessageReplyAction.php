<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatMessage;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyChatMessageReplyAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(ChatMessage $message): void
    {
        $message->loadMissing(['user.profile', 'replyTo.user.profile', 'thread.group']);

        if (! $message->replyTo || $message->replyTo->user_id === $message->user_id) {
            return;
        }

        $senderName = $message->user?->profile?->display_name
            ?? $message->user?->profile?->nickname
            ?? $message->user?->email
            ?? 'User';

        $threadTitle = $message->thread?->title
            ?? $message->thread?->group?->name
            ?? __('chat.labels.group_chat_fallback');

        $this->createNotificationAction->execute(
            userId: $message->replyTo->user_id,
            type: 'chat_message_replied',
            title: __('chat.notifications.reply_title'),
            message: __('chat.notifications.reply_message', [
                'user' => $senderName,
                'thread' => $threadTitle,
            ]),
            data: [
                'chat_thread_id' => $message->chat_thread_id,
                'chat_message_id' => $message->id,
                'reply_to_message_id' => $message->reply_to_message_id,
                'sender_user_id' => $message->user_id,
            ],
        );
    }
}
