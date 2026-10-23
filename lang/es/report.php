<?php

return [
    'errors' => [
        'duplicate_open' => 'Ya tienes una denuncia abierta para este contenido.',
        'self_report' => 'No puedes denunciar tu propio contenido.',
    ],
    'notifications' => [
        'created_title' => 'Nueva denuncia recibida',
        'created_message' => 'Se ha enviado una nueva denuncia de un :type.',
        'updated_title' => 'Actualización de tu denuncia',
        'resolved_message' => 'Tu denuncia ha sido revisada y resuelta.',
        'dismissed_message' => 'Tu denuncia ha sido revisada y archivada.',
    ],
    'types' => [
        'poem' => 'poema',
        'article' => 'artículo',
        'comment' => 'comentario',
        'media' => 'media',
        'event' => 'evento',
        'group' => 'grupo',
        'forum_post' => 'publicación del foro',
        'content' => 'contenido',
    ],
    'ui' => [
        'reason' => 'Motivo',
        'details' => 'Detalles (opcional)',
        'details_placeholder' => 'Añade detalles útiles',
        'submitted' => 'Denuncia enviada.',
        'submit_error' => 'No se ha podido enviar la denuncia.',
        'reasons' => [
            'spam' => 'Spam',
            'abuse' => 'Abuso',
            'harassment' => 'Acoso',
            'other' => 'Otro',
        ],
    ],
];
