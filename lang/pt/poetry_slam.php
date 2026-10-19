<?php

return [
    'not_found' => 'Poetry Slam não encontrado para este evento.',
    'entry_not_found' => 'Inscrição no Poetry Slam não encontrada.',
    'round_not_found' => 'Ronda do Poetry Slam não encontrada.',
    'score_context_not_found' => 'Contexto de pontuação do Poetry Slam não encontrado.',
    'advancement_context_not_found' => 'Contexto de passagem do Poetry Slam não encontrado.',
    'validation' => [
        'event_must_be_poetry_slam' => 'O evento selecionado deve pertencer à categoria Poetry Slam.',
        'already_exists' => 'Já existe um Poetry Slam para o evento selecionado.',
        'slots_limit_reached' => 'O Poetry Slam já atingiu o limite de participantes.',
        'user_already_registered' => 'Este utilizador já está inscrito no Poetry Slam.',
        'external_participant_already_registered' => 'Este participante já está inscrito no Poetry Slam.',
        'event_participant_entry_not_manually_creatable' => 'As inscrições ligadas a participantes do evento são criadas automaticamente.',
        'event_participant_entry_cannot_be_deleted' => 'As inscrições ligadas a participantes do evento não podem ser eliminadas manualmente.',
        'performance_order_already_assigned' => 'Esta ordem de atuação já está atribuída a outro participante.',
        'round_number_already_exists' => 'Este número de ronda já existe neste Poetry Slam.',
        'active_round_already_exists' => 'Já existe uma ronda ativa neste Poetry Slam.',
        'round_scores_not_allowed_in_draft' => 'Não é possível inserir pontuações enquanto a ronda está em rascunho.',
        'entry_not_in_round_slam' => 'A inscrição selecionada não pertence ao mesmo Poetry Slam da ronda.',
        'duplicate_judge_number' => 'Cada número de jurado só pode ser usado uma vez por submissão de pontuação.',
        'invalid_judge_number' => 'Um ou mais números de jurado estão fora do intervalo de jurados configurado para a ronda.',
        'manual_advancement_only' => 'As decisões manuais de passagem só são permitidas quando a ronda usa seleção manual.',
        'advancement_percentage_max' => 'A percentagem de passagem não pode ser superior a 100.',
    ],
];
