<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\Models\PoetrySlamRound;

class DeletePoetrySlamRoundAction
{
    public function execute(PoetrySlamRound $round, int $userId): void
    {
        $oldValues = [
            'poetry_slam_id' => $round->poetry_slam_id,
            'round_number' => $round->round_number,
            'name' => $round->name,
            'judges_count' => $round->judges_count,
            'scoring_type' => $round->scoring_type?->value,
            'advancement_mode' => $round->advancement_mode?->value,
            'advancement_value' => $round->advancement_value,
            'advancement_based_on' => $round->advancement_based_on?->value,
            'status' => $round->status?->value,
        ];

        $round->delete();

        audit()->log(
            event: 'poetry_slam.round_deleted',
            userId: $userId,
            auditable: $round,
            oldValues: $oldValues,
            metadata: [
                'source' => 'poetry_slam_round_delete',
                'event_id' => $round->poetrySlam?->event_id,
            ],
        );
    }
}
