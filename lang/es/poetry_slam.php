<?php

return [
    'not_found' => 'No se ha encontrado el Poetry Slam de este evento.',
    'entry_not_found' => 'No se ha encontrado la inscripción del Poetry Slam.',
    'round_not_found' => 'No se ha encontrado la ronda del Poetry Slam.',
    'score_context_not_found' => 'No se ha encontrado el contexto de puntuación del Poetry Slam.',
    'advancement_context_not_found' => 'No se ha encontrado el contexto de clasificación del Poetry Slam.',
    'validation' => [
        'event_must_be_poetry_slam' => 'El evento seleccionado debe pertenecer a la categoría Poetry Slam.',
        'already_exists' => 'Ya existe un Poetry Slam para el evento seleccionado.',
        'slots_limit_reached' => 'El Poetry Slam ya ha alcanzado el límite de participantes.',
        'user_already_registered' => 'Este usuario ya está inscrito en el Poetry Slam.',
        'external_participant_already_registered' => 'Esta persona ya está inscrita en el Poetry Slam.',
        'event_participant_entry_not_manually_creatable' => 'Las inscripciones vinculadas a participantes del evento se crean automáticamente.',
        'event_participant_entry_cannot_be_deleted' => 'Las inscripciones vinculadas a participantes del evento no se pueden eliminar manualmente.',
        'performance_order_already_assigned' => 'Este orden de actuación ya está asignado a otra persona.',
        'round_number_already_exists' => 'Este número de ronda ya existe en el Poetry Slam.',
        'active_round_already_exists' => 'Ya hay una ronda activa en este Poetry Slam.',
        'round_scores_not_allowed_in_draft' => 'No se pueden introducir puntuaciones mientras la ronda está en borrador.',
        'entry_not_in_round_slam' => 'La inscripción seleccionada no pertenece al mismo Poetry Slam que la ronda.',
        'duplicate_judge_number' => 'Cada número de jurado solo puede usarse una vez por envío de puntuación.',
        'invalid_judge_number' => 'Uno o más números de jurado están fuera del rango configurado para la ronda.',
        'manual_advancement_only' => 'Las decisiones de clasificación manual solo se permiten cuando la ronda usa selección manual.',
        'advancement_percentage_max' => 'El porcentaje de clasificación no puede ser mayor que 100.',
    ],
];
