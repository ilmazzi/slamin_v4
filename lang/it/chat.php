<?php

return [
    'validation' => [
        'self_direct_thread' => 'Non puoi aprire una chat diretta con te stesso.',
        'members_manage_only_free_groups' => 'Puoi gestire i membri solo nelle chat di gruppo libere.',
        'cannot_remove_thread_creator' => 'Non puoi rimuovere il creatore della chat di gruppo.',
        'unknown_nickname' => 'Questo utente non esiste.',
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
    'ui' => [
        'title' => 'Chat',
        'empty_threads' => 'Nessuna conversazione. Cerca un utente per iniziare.',
        'empty_messages' => 'Nessun messaggio. Scrivi il primo.',
        'placeholder' => 'Scrivi un messaggio…',
        'nickname_placeholder' => 'Nickname',
        'search_placeholder' => 'Cerca un utente…',
        'searching_users' => 'Ricerca in corso…',
        'no_users_found' => 'Nessun utente trovato.',
        'start' => 'Nuova chat',
        'start_error' => 'Impossibile aprire la conversazione.',
        'send' => 'Invia',
        'open' => 'Apri chat',
        'write' => 'Scrivi',
        'select_thread' => 'Scegli una conversazione.',
        'you' => 'Tu',
        'group_fallback' => 'Chat di gruppo',
        'load_error' => 'Impossibile caricare la chat.',
        'send_error' => 'Impossibile inviare il messaggio.',
        'realtime_unavailable' => 'Realtime non disponibile: manca la configurazione Reverb.',
    ],
];
