<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\DTOs\UpdatePoetrySlamRoundData;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Validation\ValidationException;

class UpdatePoetrySlamRoundAction
{
    public function execute(PoetrySlamRound $round, int $userId, UpdatePoetrySlamRoundData $data): PoetrySlamRound
    {
        $round->loadMissing('poetrySlam.rounds');

        $newRoundNumber = $data->hasRoundNumber ? $data->roundNumber : $round->round_number;
        $newStatus = $data->hasStatus ? $data->status : $round->status;

        $duplicateRoundNumber = $round->poetrySlam->rounds
            ->where('id', '!=', $round->id)
            ->contains('round_number', $newRoundNumber);

        if ($duplicateRoundNumber) {
            throw ValidationException::withMessages([
                'round_number' => [__('poetry_slam.validation.round_number_already_exists')],
            ]);
        }

        if ($newStatus === PoetrySlamRoundStatus::Active) {
            $anotherActive = $round->poetrySlam->rounds
                ->where('id', '!=', $round->id)
                ->contains('status', PoetrySlamRoundStatus::Active);

            if ($anotherActive) {
                throw ValidationException::withMessages([
                    'status' => [__('poetry_slam.validation.active_round_already_exists')],
                ]);
            }
        }

        $oldValues = [
            'round_number' => $round->round_number,
            'name' => $round->name,
            'judges_count' => $round->judges_count,
            'scoring_type' => $round->scoring_type?->value,
            'advancement_mode' => $round->advancement_mode?->value,
            'advancement_value' => $round->advancement_value,
            'advancement_based_on' => $round->advancement_based_on?->value,
            'status' => $round->status?->value,
            'started_at' => $round->started_at?->toISOString(),
            'completed_at' => $round->completed_at?->toISOString(),
        ];

        $updates = [];

        if ($data->hasRoundNumber) {
            $updates['round_number'] = $data->roundNumber;
        }

        if ($data->hasName) {
            $updates['name'] = $data->name ?? 'Round ' . $newRoundNumber;
        }

        if ($data->hasJudgesCount) {
            $updates['judges_count'] = $data->judgesCount;
        }

        if ($data->hasScoringType) {
            $updates['scoring_type'] = $data->scoringType;
        }

        if ($data->hasAdvancementMode) {
            $updates['advancement_mode'] = $data->advancementMode;
        }

        if ($data->hasAdvancementValue) {
            $updates['advancement_value'] = $data->advancementValue;
        }

        if ($data->hasAdvancementBasedOn) {
            $updates['advancement_based_on'] = $data->advancementBasedOn;
        }

        if ($data->hasStatus) {
            $updates['status'] = $data->status;

            if ($data->status === PoetrySlamRoundStatus::Draft) {
                $updates['started_at'] = null;
                $updates['completed_at'] = null;
            }

            if ($data->status === PoetrySlamRoundStatus::Active) {
                $updates['started_at'] = $round->started_at ?? now();
                $updates['completed_at'] = null;
            }

            if ($data->status === PoetrySlamRoundStatus::Completed) {
                $updates['started_at'] = $round->started_at ?? now();
                $updates['completed_at'] = now();
            }
        }

        if ($updates !== []) {
            $round->update($updates);
        }

        audit()->log(
            event: 'poetry_slam.round_updated',
            userId: $userId,
            auditable: $round,
            oldValues: $oldValues,
            newValues: [
                'round_number' => $round->round_number,
                'name' => $round->name,
                'judges_count' => $round->judges_count,
                'scoring_type' => $round->scoring_type?->value,
                'effective_scoring_type' => $round->effectiveScoringType()->value,
                'advancement_mode' => $round->advancement_mode?->value,
                'advancement_value' => $round->advancement_value,
                'advancement_based_on' => $round->advancement_based_on?->value,
                'status' => $round->status?->value,
                'started_at' => $round->started_at?->toISOString(),
                'completed_at' => $round->completed_at?->toISOString(),
            ],
            metadata: [
                'source' => 'poetry_slam_round_update',
                'event_id' => $round->poetrySlam?->event_id,
            ],
        );

        return $round->fresh();
    }
}
