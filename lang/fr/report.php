<?php

return [
    'errors' => [
        'duplicate_open' => 'Tu as déjà un signalement ouvert pour ce contenu.',
        'self_report' => 'Tu ne peux pas signaler ton propre contenu.',
    ],
    'notifications' => [
        'created_title' => 'Nouveau signalement reçu',
        'created_message' => 'Un nouveau signalement a été envoyé pour un :type.',
        'updated_title' => 'Mise à jour de ton signalement',
        'resolved_message' => 'Ton signalement a été examiné et résolu.',
        'dismissed_message' => 'Ton signalement a été examiné et archivé.',
    ],
    'types' => [
        'poem' => 'poème',
        'article' => 'article',
        'comment' => 'commentaire',
        'media' => 'média',
        'event' => 'événement',
        'group' => 'groupe',
        'forum_post' => 'message du forum',
        'content' => 'contenu',
    ],
    'ui' => [
        'reason' => 'Motif',
        'details' => 'Détails (facultatif)',
        'details_placeholder' => 'Ajoute des précisions utiles',
        'submitted' => 'Signalement envoyé.',
        'submit_error' => 'Impossible d’envoyer le signalement.',
        'reasons' => [
            'spam' => 'Spam',
            'abuse' => 'Abus',
            'harassment' => 'Harcèlement',
            'other' => 'Autre',
        ],
    ],
];
