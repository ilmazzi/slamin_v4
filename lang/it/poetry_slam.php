<?php

return [
    'not_found' => 'Poetry slam non trovato per questo evento.',
    'entry_not_found' => 'Partecipante poetry slam non trovato.',
    'round_not_found' => 'Round poetry slam non trovato.',
    'score_context_not_found' => 'Contesto punteggi poetry slam non trovato.',
    'advancement_context_not_found' => 'Contesto avanzamento poetry slam non trovato.',
    'validation' => [
        'event_must_be_poetry_slam' => 'L\'evento selezionato deve appartenere alla categoria poetry slam.',
        'already_exists' => 'Esiste già un poetry slam per l\'evento selezionato.',
        'slots_limit_reached' => 'Il poetry slam ha già raggiunto il limite massimo di partecipanti.',
        'user_already_registered' => 'Questo utente è già registrato nel poetry slam.',
        'external_participant_already_registered' => 'Questo partecipante è già registrato nel poetry slam.',
        'event_participant_entry_not_manually_creatable' => 'Le entry collegate ai partecipanti evento vengono create automaticamente.',
        'event_participant_entry_cannot_be_deleted' => 'Le entry collegate ai partecipanti evento non possono essere eliminate manualmente.',
        'performance_order_already_assigned' => 'Questo ordine di esibizione è già assegnato a un altro partecipante.',
        'round_number_already_exists' => 'Questo numero round esiste già per il poetry slam.',
        'active_round_already_exists' => 'Esiste già un round attivo per questo poetry slam.',
        'round_scores_not_allowed_in_draft' => 'Non è possibile inserire punteggi mentre il round è in bozza.',
        'entry_not_in_round_slam' => 'La entry selezionata non appartiene allo stesso poetry slam del round.',
        'duplicate_judge_number' => 'Ogni numero giudice può essere usato una sola volta per invio punteggi.',
        'invalid_judge_number' => 'Uno o più numeri giudice sono fuori dall\'intervallo configurato per il round.',
        'manual_advancement_only' => 'Le decisioni manuali di avanzamento sono consentite solo se il round usa selezione manuale.',
        'advancement_percentage_max' => 'La percentuale di avanzamento non può essere maggiore di 100.',
    ],
];
