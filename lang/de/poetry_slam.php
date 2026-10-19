<?php

return [
    'not_found' => 'Für dieses Event wurde kein Poetry Slam gefunden.',
    'entry_not_found' => 'Poetry-Slam-Eintrag nicht gefunden.',
    'round_not_found' => 'Poetry-Slam-Runde nicht gefunden.',
    'score_context_not_found' => 'Poetry-Slam-Punktekontext nicht gefunden.',
    'advancement_context_not_found' => 'Poetry-Slam-Weiterkommen-Kontext nicht gefunden.',
    'validation' => [
        'event_must_be_poetry_slam' => 'Das gewählte Event muss zur Kategorie Poetry Slam gehören.',
        'already_exists' => 'Für das gewählte Event existiert bereits ein Poetry Slam.',
        'slots_limit_reached' => 'Der Poetry Slam hat das Teilnehmerlimit bereits erreicht.',
        'user_already_registered' => 'Diese Person ist bereits für den Poetry Slam registriert.',
        'external_participant_already_registered' => 'Diese Person ist bereits für den Poetry Slam registriert.',
        'event_participant_entry_not_manually_creatable' => 'Einträge, die mit Event-Teilnehmer verknüpft sind, werden automatisch erstellt.',
        'event_participant_entry_cannot_be_deleted' => 'Einträge, die mit Event-Teilnehmer verknüpft sind, können nicht manuell gelöscht werden.',
        'performance_order_already_assigned' => 'Diese Auftrittsreihenfolge ist bereits einer anderen Person zugewiesen.',
        'round_number_already_exists' => 'Diese Rundennummer existiert bereits für den Poetry Slam.',
        'active_round_already_exists' => 'Für diesen Poetry Slam gibt es bereits eine aktive Runde.',
        'round_scores_not_allowed_in_draft' => 'Punkte können nicht eingegeben werden, solange die Runde ein Entwurf ist.',
        'entry_not_in_round_slam' => 'Der gewählte Eintrag gehört nicht zum selben Poetry Slam wie die Runde.',
        'duplicate_judge_number' => 'Jede Jury-Nummer kann pro Punkteabgabe nur einmal verwendet werden.',
        'invalid_judge_number' => 'Eine oder mehrere Jury-Nummern liegen außerhalb des für die Runde konfigurierten Jury-Bereichs.',
        'manual_advancement_only' => 'Manuelle Weiterkommen-Entscheidungen sind nur erlaubt, wenn die Runde manuelle Auswahl verwendet.',
        'advancement_percentage_max' => 'Der Weiterkommen-Prozentsatz darf nicht größer als 100 sein.',
    ],
];
