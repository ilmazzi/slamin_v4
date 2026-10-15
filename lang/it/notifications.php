<?php

return [
    'title' => 'Notifiche',
    'aria_unread' => 'Notifiche, :count non lette',
    'mark_all_read' => 'Segna tutte come lette',
    'loading' => 'Caricamento…',
    'load_error' => 'Impossibile caricare le notifiche.',
    'retry' => 'Riprova',
    'empty' => 'Nessuna notifica per ora.',
    'unread' => 'Non letta',
    'just_now' => 'Adesso',
    'someone' => 'Un utente',
    'types' => [
        'gig_application_received' => [
            'title' => 'Nuova candidatura',
            'message' => ':name ha inviato una candidatura per «:title».',
        ],
        'gig_application_updated' => [
            'title' => 'Risposta alla tua candidatura',
            'pending' => 'La tua candidatura per «:title» è tornata in attesa di valutazione.',
            'accepted' => 'La tua candidatura per «:title» è stata accettata.',
            'confirmed' => 'Sei stato confermato per «:title».',
            'rejected' => 'La tua candidatura per «:title» non è stata selezionata.',
            'withdrawn' => 'La tua candidatura per «:title» risulta ritirata.',
        ],
        'gig_negotiation_opened' => [
            'title' => 'Trattativa aperta',
            'message' => 'L\'organizzatore ha aperto una trattativa sulla tua candidatura.',
        ],
        'gig_negotiation_event_created' => [
            'title' => 'Trattativa aggiornata',
            'message' => 'C\'è una nuova proposta nella trattativa.',
        ],
        'gig_negotiation_agreed' => [
            'title' => 'Accordo raggiunto',
            'message' => 'La trattativa si è chiusa con un accordo.',
        ],
        'translation_job_submitted' => [
            'title' => 'Traduzione inviata',
            'message' => 'Una traduzione è pronta per la tua revisione.',
        ],
        'translation_job_changes_requested' => [
            'title' => 'Modifiche richieste',
            'message' => 'Sono state richieste modifiche alla tua traduzione.',
        ],
        'translation_job_approved' => [
            'title' => 'Traduzione approvata',
            'message' => 'La tua traduzione è stata approvata.',
        ],
        'translation_comment_created' => [
            'title' => 'Nuovo commento sulla traduzione',
            'message' => 'È stato aggiunto un commento nel lavoro di traduzione.',
        ],
    ],
];
