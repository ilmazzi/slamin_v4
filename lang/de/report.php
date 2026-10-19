<?php

return [
    'errors' => [
        'duplicate_open' => 'Du hast bereits eine offene Meldung für diesen Inhalt.',
        'self_report' => 'Du kannst deinen eigenen Inhalt nicht melden.',
    ],
    'notifications' => [
        'created_title' => 'Neue Meldung eingegangen',
        'created_message' => 'Es wurde eine neue Meldung für :type eingereicht.',
        'updated_title' => 'Update zu deiner Meldung',
        'resolved_message' => 'Deine Meldung wurde geprüft und abgeschlossen.',
        'dismissed_message' => 'Deine Meldung wurde geprüft und archiviert.',
    ],
    'types' => [
        'poem' => 'ein Gedicht',
        'article' => 'einen Artikel',
        'comment' => 'einen Kommentar',
        'media' => 'Medien',
        'event' => 'ein Event',
        'group' => 'eine Gruppe',
        'content' => 'einen Inhalt',
    ],
    'ui' => [
        'reason' => 'Grund',
        'details' => 'Details (optional)',
        'details_placeholder' => 'Füge nützliche Details hinzu',
        'submitted' => 'Meldung gesendet.',
        'submit_error' => 'Die Meldung konnte nicht gesendet werden.',
        'reasons' => [
            'spam' => 'Spam',
            'abuse' => 'Missbrauch',
            'harassment' => 'Belästigung',
            'other' => 'Sonstiges',
        ],
    ],
];
