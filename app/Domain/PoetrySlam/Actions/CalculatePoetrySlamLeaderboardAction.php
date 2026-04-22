<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;

class CalculatePoetrySlamLeaderboardAction
{
    public function __construct(
        private readonly CalculatePoetrySlamRoundEntryResultAction $calculateResultAction,
    ) {
    }

    public function execute(PoetrySlam $poetrySlam): array
    {
        $poetrySlam->loadMissing('entries', 'rounds.scores');

        $rounds = $poetrySlam->rounds
            ->sortBy('round_number')
            ->values();

        $leaderboard = $poetrySlam->entries
            ->map(function (PoetrySlamEntry $entry) use ($rounds): array {
                $roundResults = $rounds->map(function (PoetrySlamRound $round) use ($entry): array {
                    $result = $this->calculateResultAction->execute($round, $entry);

                    return [
                        'round_id' => $round->id,
                        'round_number' => $round->round_number,
                        'round_name' => $round->name,
                        'result_value' => $result['result_value'],
                        'is_complete' => $result['is_complete'],
                    ];
                })->values();

                $completedRoundResults = $roundResults->filter(fn (array $result): bool => $result['result_value'] !== null);

                return [
                    'entry_id' => $entry->id,
                    'display_name' => $entry->display_name,
                    'entry_source' => $entry->entry_source?->value,
                    'performance_order' => $entry->performance_order,
                    'completed_rounds_count' => $completedRoundResults->count(),
                    'cumulative_result_value' => round($completedRoundResults->sum('result_value'), 2),
                    'round_results' => $roundResults->all(),
                    'rank' => null,
                ];
            })
            ->sort(function (array $left, array $right): int {
                if ($left['cumulative_result_value'] !== $right['cumulative_result_value']) {
                    return $right['cumulative_result_value'] <=> $left['cumulative_result_value'];
                }

                if ($left['completed_rounds_count'] !== $right['completed_rounds_count']) {
                    return $right['completed_rounds_count'] <=> $left['completed_rounds_count'];
                }

                $leftOrder = $left['performance_order'] ?? PHP_INT_MAX;
                $rightOrder = $right['performance_order'] ?? PHP_INT_MAX;

                if ($leftOrder !== $rightOrder) {
                    return $leftOrder <=> $rightOrder;
                }

                return $left['entry_id'] <=> $right['entry_id'];
            })
            ->values()
            ->all();

        return $this->assignRanks($leaderboard);
    }

    private function assignRanks(array $items): array
    {
        $lastValue = null;
        $lastCompletedRounds = null;
        $lastRank = null;

        foreach ($items as $index => &$item) {
            if (
                $lastValue !== null
                && $item['cumulative_result_value'] === $lastValue
                && $item['completed_rounds_count'] === $lastCompletedRounds
            ) {
                $item['rank'] = $lastRank;
                continue;
            }

            $item['rank'] = $index + 1;
            $lastRank = $item['rank'];
            $lastValue = $item['cumulative_result_value'];
            $lastCompletedRounds = $item['completed_rounds_count'];
        }

        return $items;
    }
}
