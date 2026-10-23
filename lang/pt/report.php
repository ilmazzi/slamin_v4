<?php

return [
    'errors' => [
        'duplicate_open' => 'Já tens uma denúncia aberta para este conteúdo.',
        'self_report' => 'Não podes denunciar o teu próprio conteúdo.',
    ],
    'notifications' => [
        'created_title' => 'Nova denúncia recebida',
        'created_message' => 'Foi submetida uma nova denúncia para um :type.',
        'updated_title' => 'Atualização da tua denúncia',
        'resolved_message' => 'A tua denúncia foi analisada e resolvida.',
        'dismissed_message' => 'A tua denúncia foi analisada e arquivada.',
    ],
    'types' => [
        'poem' => 'poesia',
        'article' => 'artigo',
        'comment' => 'comentário',
        'media' => 'media',
        'event' => 'evento',
        'group' => 'grupo',
        'forum_post' => 'publicação do fórum',
        'content' => 'conteúdo',
    ],
    'ui' => [
        'reason' => 'Motivo',
        'details' => 'Detalhes (opcional)',
        'details_placeholder' => 'Adiciona detalhes úteis',
        'submitted' => 'Denúncia enviada.',
        'submit_error' => 'Não foi possível enviar a denúncia.',
        'reasons' => [
            'spam' => 'Spam',
            'abuse' => 'Abuso',
            'harassment' => 'Assédio',
            'other' => 'Outro',
        ],
    ],
];
