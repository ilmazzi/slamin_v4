<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Support\Collection;

class CalculatePoetrySlamRoundEntryResultAction
{
    public function execute(PoetrySlamRound $round, PoetrySlamEntry $entry): array
    {
        $scores = $round->scores
            ->where('poetry_slam_entry_id', $entry->id)
            ->sortBy('judge_number')
            ->values();

        $rawScores = $scores->map(fn ($score): float => (float) $score->score)->values();
        $effectiveType = $round->effectiveScoringType();
        $countedScores = $rawScores;
        $discardedScores = collect();

        if ($effectiveType === PoetrySlamRoundScoringType::TrimmedSum && $rawScores->count() >= 3) {
            $countedScores = $this->removeSingleLowestAndHighest($rawScores);
            $discardedScores = $this->resolveDiscardedScores($rawScores, $countedScores);
        }

        $resultValue = match ($effectiveType) {
            PoetrySlamRoundScoringType::TrimmedSum,
            PoetrySlamRoundScoringType::Sum => round($countedScores->sum(), 2),
            PoetrySlamRoundScoringType::Average => $countedScores->count() > 0
                ? round($countedScores->avg(), 2)
                : null,
            PoetrySlamRoundScoringType::Best => $countedScores->count() > 0
                ? round($countedScores->max(), 2)
                : null,
        };

        return [
            'entry_id' => $entry->id,
            'display_name' => $entry->display_name,
            'entry_source' => $entry->entry_source?->value,
            'performance_order' => $entry->performance_order,
            'judges_count' => $round->judges_count,
            'scores_count' => $scores->count(),
            'is_complete' => $scores->count() === $round->judges_count,
            'scoring_type' => $round->scoring_type?->value,
            'effective_scoring_type' => $effectiveType->value,
            'raw_scores' => $rawScores->all(),
            'discarded_scores' => $discardedScores->values()->all(),
            'counted_scores' => $countedScores->values()->all(),
            'result_value' => $scores->count() === $round->judges_count ? $resultValue : null,
            'judge_scores' => $scores->map(fn ($score): array => [
                'judge_number' => $score->judge_number,
                'score' => (float) $score->score,
            ])->values()->all(),
        ];
    }

    private function removeSingleLowestAndHighest(Collection $scores): Collection
    {
        $values = $scores->values()->all();
        sort($values, SORT_NUMERIC);

        array_shift($values);
        array_pop($values);

        return collect($values);
    }

    private function resolveDiscardedScores(Collection $rawScores, Collection $countedScores): Collection
    {
        $remaining = $countedScores->values()->all();
        $discarded = [];

        foreach ($rawScores as $score) {
            $index = array_search($score, $remaining, true);

            if ($index === false) {
                $discarded[] = $score;
                continue;
            }

            unset($remaining[$index]);
        }

        return collect(array_values($discarded));
    }
}
