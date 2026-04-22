<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\DTOs\UpsertPoetrySlamRoundAdvancementDecisionData;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Domain\PoetrySlam\Models\PoetrySlamRoundAdvancementDecision;
use Illuminate\Validation\ValidationException;

class UpsertPoetrySlamRoundAdvancementDecisionAction
{
    public function execute(
        PoetrySlamRound $round,
        PoetrySlamEntry $entry,
        int $userId,
        UpsertPoetrySlamRoundAdvancementDecisionData $data
    ): PoetrySlamRoundAdvancementDecision {
        if ($round->advancement_mode !== PoetrySlamRoundAdvancementMode::ManualSelection) {
            throw ValidationException::withMessages([
                'decision_status' => [__('poetry_slam.validation.manual_advancement_only')],
            ]);
        }

        if ($entry->poetry_slam_id !== $round->poetry_slam_id) {
            throw ValidationException::withMessages([
                'entry' => [__('poetry_slam.validation.entry_not_in_round_slam')],
            ]);
        }

        $decision = PoetrySlamRoundAdvancementDecision::query()->updateOrCreate(
            [
                'poetry_slam_round_id' => $round->id,
                'poetry_slam_entry_id' => $entry->id,
            ],
            [
                'decision_status' => $data->decisionStatus,
                'note' => $data->note,
                'decided_at' => now(),
            ],
        );

        audit()->log(
            event: 'poetry_slam.round_advancement_decision_upserted',
            userId: $userId,
            auditable: $round,
            newValues: [
                'poetry_slam_entry_id' => $entry->id,
                'decision_status' => $decision->decision_status?->value,
                'note' => $decision->note,
            ],
            metadata: [
                'source' => 'poetry_slam_round_advancement_decision_upsert',
                'event_id' => $round->poetrySlam->event_id,
            ],
        );

        return $decision->fresh();
    }
}
