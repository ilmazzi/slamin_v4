<?php

return [
    'not_found' => 'Poetry slam not found for this event.',
    'entry_not_found' => 'Poetry slam entry not found.',
    'round_not_found' => 'Poetry slam round not found.',
    'score_context_not_found' => 'Poetry slam score context not found.',
    'advancement_context_not_found' => 'Poetry slam advancement context not found.',
    'validation' => [
        'event_must_be_poetry_slam' => 'The selected event must belong to the poetry slam category.',
        'already_exists' => 'A poetry slam already exists for the selected event.',
        'slots_limit_reached' => 'The poetry slam has already reached its participant limit.',
        'user_already_registered' => 'This user is already registered in the poetry slam.',
        'external_participant_already_registered' => 'This participant is already registered in the poetry slam.',
        'event_participant_entry_not_manually_creatable' => 'Entries linked to event participants are created automatically.',
        'event_participant_entry_cannot_be_deleted' => 'Entries linked to event participants cannot be deleted manually.',
        'performance_order_already_assigned' => 'This performance order is already assigned to another participant.',
        'round_number_already_exists' => 'This round number already exists for the poetry slam.',
        'active_round_already_exists' => 'There is already an active round for this poetry slam.',
        'round_scores_not_allowed_in_draft' => 'Scores cannot be inserted while the round is in draft.',
        'entry_not_in_round_slam' => 'The selected entry does not belong to the same poetry slam as the round.',
        'duplicate_judge_number' => 'Each judge number can be used only once per score submission.',
        'invalid_judge_number' => 'One or more judge numbers are outside the configured judges range for the round.',
        'manual_advancement_only' => 'Manual advancement decisions are allowed only when the round uses manual selection.',
        'advancement_percentage_max' => 'Advancement percentage cannot be greater than 100.',
    ],
];
