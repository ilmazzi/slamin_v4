<?php

return [
    'validation' => [
        'self_direct_thread' => 'Non puoi aprire una chat diretta con te stesso.',
        'members_manage_only_free_groups' => 'Puoi gestire i membri solo nelle chat di gruppo libere.',
        'cannot_remove_thread_creator' => 'Non puoi rimuovere il creatore della chat di gruppo.',
    ],
    'labels' => [
        'group_chat_fallback' => 'Chat di gruppo',
    ],
    'notifications' => [
        'direct_message_title' => 'Nuovo messaggio',
        'direct_message_message' => ':user ti ha scritto in chat.',
        'group_message_title' => 'Nuovo messaggio di gruppo',
        'group_message_message' => ':user ha inviato un messaggio in ":thread".',
        'reply_title' => 'Nuova risposta',
        'reply_message' => ':user ha risposto a un tuo messaggio in ":thread".',
        'reaction_title' => 'Nuova reazione',
        'reaction_message' => ':user ha reagito al tuo messaggio con :emoji.',
    ],
];
