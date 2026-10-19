<?php

return [
    'errors' => [
        'duplicate_open' => 'Hai già una segnalazione aperta per questo contenuto.',
        'self_report' => 'Non puoi segnalare un tuo contenuto.',
    ],
    'notifications' => [
        'created_title' => 'Nuova segnalazione ricevuta',
        'created_message' => 'È stata inviata una nuova segnalazione su un contenuto di tipo :type.',
        'updated_title' => 'Aggiornamento sulla tua segnalazione',
        'resolved_message' => 'La tua segnalazione è stata presa in carico e risolta.',
        'dismissed_message' => 'La tua segnalazione è stata valutata e archiviata.',
    ],
    'types' => [
        'poem' => 'poesia',
        'article' => 'articolo',
        'comment' => 'commento',
        'media' => 'media',
        'event' => 'evento',
        'group' => 'gruppo',
        'content' => 'contenuto',
    ],
    'ui' => [
        'reason' => 'Motivo',
        'details' => 'Dettagli (opzionale)',
        'details_placeholder' => 'Aggiungi dettagli utili',
        'submitted' => 'Segnalazione inviata.',
        'submit_error' => 'Impossibile inviare la segnalazione.',
        'reasons' => [
            'spam' => 'Spam',
            'abuse' => 'Abuso',
            'harassment' => 'Molestie',
            'other' => 'Altro',
        ],
    ],
];
