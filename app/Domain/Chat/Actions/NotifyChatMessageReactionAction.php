<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatMessageReaction;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyChatMessageReactionAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(ChatMessageReaction $reaction): void
    {
        $reaction->loadMissing(['user.profile', 'message.user.profile', 'message.thread.group']);

        if ($reaction->message?->user_id === $reaction->user_id) {
            return;
        }

        $senderName = $reaction->user?->profile?->display_name
            ?? $reaction->user?->profile?->nickname
            ?? $reaction->user?->email
            ?? 'User';

        $this->createNotificationAction->execute(
            userId: $reaction->message->user_id,
            type: 'chat_message_reacted',
            title: __('chat.notifications.reaction_title'),
            message: __('chat.notifications.reaction_message', [
                'user' => $senderName,
                'emoji' => $reaction->emoji,
            ]),
            data: [
                'chat_thread_id' => $reaction->message->chat_thread_id,
                'chat_message_id' => $reaction->chat_message_id,
                'sender_user_id' => $reaction->user_id,
                'emoji' => $reaction->emoji,
            ],
        );
    }
}
