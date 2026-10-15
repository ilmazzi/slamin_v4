<?php

return [
    'validation' => [
        'self_direct_thread' => 'You cannot open a direct chat with yourself.',
        'members_manage_only_free_groups' => 'You can manage members only in free group chats.',
        'cannot_remove_thread_creator' => 'You cannot remove the creator of the group chat.',
        'unknown_nickname' => 'This user does not exist.',
    ],
    'ui' => [
        'title' => 'Chat',
        'empty_threads' => 'No conversations yet. Search a user to start one.',
        'empty_messages' => 'No messages yet. Write the first one.',
        'placeholder' => 'Write a message…',
        'nickname_placeholder' => 'Nickname',
        'search_placeholder' => 'Search a user…',
        'searching_users' => 'Searching…',
        'no_users_found' => 'No users found.',
        'start' => 'New chat',
        'start_error' => 'Unable to open the conversation.',
        'send' => 'Send',
        'open' => 'Open chat',
        'write' => 'Message',
        'select_thread' => 'Choose a conversation.',
        'open_list' => 'Conversations',
        'close_list' => 'Close list',
        'you' => 'You',
        'group_fallback' => 'Group chat',
        'load_error' => 'Unable to load chat.',
        'send_error' => 'Unable to send the message.',
        'realtime_unavailable' => 'Realtime is unavailable: Reverb is not configured.',
    ],
];
