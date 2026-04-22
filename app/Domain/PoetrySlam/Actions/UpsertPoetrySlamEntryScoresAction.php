<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\DTOs\UpsertPoetrySlamEntryScoresData;
use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Domain\PoetrySlam\Models\PoetrySlamScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertPoetrySlamEntryScoresAction
{
    public function __construct(
        private readonly CalculatePoetrySlamRoundEntryResultAction $calculateResultAction,
    ) {
    }

    public function execute(PoetrySlamRound $round, PoetrySlamEntry $entry, int $userId, UpsertPoetrySlamEntryScoresData $data): array
    {
        if ($round->status === PoetrySlamRoundStatus::Draft) {
            throw ValidationException::withMessages([
                'round' => [__('poetry_slam.validation.round_scores_not_allowed_in_draft')],
            ]);
        }

        if ($entry->poetry_slam_id !== $round->poetrySlam->id) {
            throw ValidationException::withMessages([
                'entry' => [__('poetry_slam.validation.entry_not_in_round_slam')],
            ]);
        }

        $judgeNumbers = collect($data->scores)->pluck('judge_number');

        if ($judgeNumbers->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'scores' => [__('poetry_slam.validation.duplicate_judge_number')],
            ]);
        }

        if ($judgeNumbers->contains(fn (int $judgeNumber): bool => $judgeNumber < 1 || $judgeNumber > $round->judges_count)) {
            throw ValidationException::withMessages([
                'scores' => [__('poetry_slam.validation.invalid_judge_number')],
            ]);
        }

        DB::transaction(function () use ($round, $entry, $data): void {
            PoetrySlamScore::query()
                ->where('poetry_slam_round_id', $round->id)
                ->where('poetry_slam_entry_id', $entry->id)
                ->delete();

            foreach ($data->scores as $scoreData) {
                PoetrySlamScore::query()->create([
                    'poetry_slam_round_id' => $round->id,
                    'poetry_slam_entry_id' => $entry->id,
                    'judge_number' => $scoreData['judge_number'],
                    'score' => $scoreData['score'],
                ]);
            }
        });

        $round->unsetRelation('scores');
        $round->load('scores');

        $result = $this->calculateResultAction->execute($round, $entry);

        audit()->log(
            event: 'poetry_slam.scores_upserted',
            userId: $userId,
            auditable: $round,
            newValues: [
                'poetry_slam_entry_id' => $entry->id,
                'judge_scores' => $result['judge_scores'],
                'result_value' => $result['result_value'],
                'effective_scoring_type' => $result['effective_scoring_type'],
            ],
            metadata: [
                'source' => 'poetry_slam_scores_upsert',
                'event_id' => $round->poetrySlam->event_id,
            ],
        );

        return $result;
    }
}
