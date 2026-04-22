<?php

return [
    'validation' => [
        'photo_limit_reached' => 'Hai raggiunto il limite di upload foto.',
        'video_limit_reached' => 'Hai raggiunto il limite di upload video.',
        'photo_too_large' => 'La foto supera la dimensione massima consentita.',
        'video_too_large' => 'Il video supera la dimensione massima consentita.',
        'photo_mime_not_allowed' => 'Il formato della foto non e consentito.',
        'video_mime_not_allowed' => 'Il formato del video non e consentito.',
        'peertube_not_configured' => 'PeerTube non e configurato correttamente.',
        'peertube_upload_failed' => 'Upload video su PeerTube fallito.',
        'video_file_missing' => 'Il file video selezionato non e disponibile.',
        'invalid_snap_timestamp' => 'Il timestamp dello snap non e valido.',
        'snap_timestamp_exceeds_duration' => 'Il timestamp dello snap supera la durata del video.',
        'video_status_only_for_videos' => 'Lo stato video e disponibile solo per i media video.',
        'feature_pack_not_available' => 'Il feature pack selezionato non e disponibile.',
        'stripe_not_configured' => 'Stripe non e configurato correttamente.',
        'stripe_checkout_session_failed' => 'Creazione della sessione Stripe fallita.',
        'checkout_order_not_pending' => 'L ordine di checkout non e piu in stato pending.',
    ],
    'notifications' => [
        'snap_created_title' => 'Nuovo snap sul tuo video',
        'snap_created_message' => ':user ha aggiunto uno snap ":kind" al secondo :second del video ":title".',
        'video_ready_title' => 'Video pronto',
        'video_ready_message' => 'Il video ":title" e pronto per essere visto.',
        'video_failed_title' => 'Video non disponibile',
        'video_failed_message' => 'C e stato un problema con il video ":title".',
    ],
];
