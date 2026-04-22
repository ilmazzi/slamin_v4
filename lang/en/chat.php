<?php

return [
    'validation' => [
        'self_direct_thread' => 'You cannot open a direct chat with yourself.',
        'members_manage_only_free_groups' => 'You can manage members only in free group chats.',
        'cannot_remove_thread_creator' => 'You cannot remove the creator of the group chat.',
    ],
    'labels' => [
        'group_chat_fallback' => 'Group chat',
    ],
    'notifications' => [
        'direct_message_title' => 'New message',
        'direct_message_message' => ':user sent you a chat message.',
        'group_message_title' => 'New group message',
        'group_message_message' => ':user sent a message in ":thread".',
        'reply_title' => 'New reply',
        'reply_message' => ':user replied to one of your messages in ":thread".',
        'reaction_title' => 'New reaction',
        'reaction_message' => ':user reacted to your message with :emoji.',
    ],
];
