<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\DTOs\StorePoetrySlamRoundData;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StorePoetrySlamRoundAction
{
    public function execute(PoetrySlam $poetrySlam, StorePoetrySlamRoundData $data): PoetrySlamRound
    {
        $poetrySlam->loadMissing('rounds');

        if ($poetrySlam->rounds->contains('round_number', $data->roundNumber)) {
            throw ValidationException::withMessages([
                'round_number' => [__('poetry_slam.validation.round_number_already_exists')],
            ]);
        }

        if ($data->status === PoetrySlamRoundStatus::Active && $poetrySlam->rounds->contains('status', PoetrySlamRoundStatus::Active)) {
            throw ValidationException::withMessages([
                'status' => [__('poetry_slam.validation.active_round_already_exists')],
            ]);
        }

        $timestamps = $this->resolveTimestamps($data->status);

        $round = PoetrySlamRound::query()->create([
            'poetry_slam_id' => $poetrySlam->id,
            'round_number' => $data->roundNumber,
            'name' => $data->name ?? 'Round ' . $data->roundNumber,
            'judges_count' => $data->judgesCount,
            'scoring_type' => $data->scoringType,
            'advancement_mode' => $data->advancementMode,
            'advancement_value' => $data->advancementValue,
            'advancement_based_on' => $data->advancementBasedOn,
            'status' => $data->status,
            'started_at' => $timestamps['started_at'],
            'completed_at' => $timestamps['completed_at'],
        ]);

        audit()->log(
            event: 'poetry_slam.round_created',
            userId: Auth::id(),
            auditable: $round,
            newValues: [
                'poetry_slam_id' => $round->poetry_slam_id,
                'round_number' => $round->round_number,
                'name' => $round->name,
                'judges_count' => $round->judges_count,
                'scoring_type' => $round->scoring_type?->value,
                'effective_scoring_type' => $round->effectiveScoringType()->value,
                'advancement_mode' => $round->advancement_mode?->value,
                'advancement_value' => $round->advancement_value,
                'advancement_based_on' => $round->advancement_based_on?->value,
                'status' => $round->status?->value,
            ],
            metadata: [
                'source' => 'poetry_slam_round_store',
                'event_id' => $poetrySlam->event_id,
            ],
        );

        return $round;
    }

    private function resolveTimestamps(PoetrySlamRoundStatus $status): array
    {
        return match ($status) {
            PoetrySlamRoundStatus::Draft => [
                'started_at' => null,
                'completed_at' => null,
            ],
            PoetrySlamRoundStatus::Active => [
                'started_at' => now(),
                'completed_at' => null,
            ],
            PoetrySlamRoundStatus::Completed => [
                'started_at' => now(),
                'completed_at' => now(),
            ],
        };
    }
}
