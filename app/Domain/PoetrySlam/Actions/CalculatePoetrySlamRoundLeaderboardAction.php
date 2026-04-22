<?php

namespace Domain\PoetrySlam\Actions;

use Domain\PoetrySlam\Models\PoetrySlamRound;

class CalculatePoetrySlamRoundLeaderboardAction
{
    public function __construct(
        private readonly CalculatePoetrySlamRoundEntryResultAction $calculateResultAction,
    ) {
    }

    public function execute(PoetrySlamRound $round): array
    {
        $round->loadMissing('poetrySlam.entries', 'scores');

        $leaderboard = $round->poetrySlam->entries
            ->map(function ($entry) use ($round): array {
                $result = $this->calculateResultAction->execute($round, $entry);

                return [
                    ...$result,
                    'rank' => null,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $leftValue = $left['result_value'];
                $rightValue = $right['result_value'];

                if ($leftValue === null && $rightValue !== null) {
                    return 1;
                }

                if ($leftValue !== null && $rightValue === null) {
                    return -1;
                }

                if ($leftValue !== null && $rightValue !== null && $leftValue !== $rightValue) {
                    return $rightValue <=> $leftValue;
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

        return $this->assignRanks($leaderboard, 'result_value');
    }

    private function assignRanks(array $items, string $valueKey): array
    {
        $lastValue = null;
        $lastRank = null;

        foreach ($items as $index => &$item) {
            $value = $item[$valueKey];

            if ($value === null) {
                $item['rank'] = null;
                continue;
            }

            if ($lastValue !== null && $value === $lastValue) {
                $item['rank'] = $lastRank;
                continue;
            }

            $item['rank'] = $index + 1;
            $lastRank = $item['rank'];
            $lastValue = $value;
        }

        return $items;
    }
}
