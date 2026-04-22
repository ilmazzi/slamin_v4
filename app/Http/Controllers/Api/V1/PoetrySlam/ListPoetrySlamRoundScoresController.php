<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\CalculatePoetrySlamRoundEntryResultAction;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class ListPoetrySlamRoundScoresController extends Controller
{
    public function __construct(
        private readonly CalculatePoetrySlamRoundEntryResultAction $calculateResultAction,
    ) {
    }

    public function __invoke(Event $event, PoetrySlamRound $round): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam.entries');
        $round->loadMissing('poetrySlam', 'scores');

        if ($event->poetrySlam === null || $round->poetry_slam_id !== $event->poetrySlam->id) {
            return ApiResponse::error(
                message: __('poetry_slam.round_not_found'),
                status: 404,
            );
        }

        $results = $event->poetrySlam->entries
            ->sortBy([
                ['performance_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(fn ($entry): array => $this->calculateResultAction->execute($round, $entry));

        return ApiResponse::success([
            'round' => [
                'id' => $round->id,
                'round_number' => $round->round_number,
                'name' => $round->name,
                'judges_count' => $round->judges_count,
                'scoring_type' => $round->scoring_type?->value,
                'effective_scoring_type' => $round->effectiveScoringType()->value,
                'status' => $round->status?->value,
            ],
            'results' => $results,
        ]);
    }
}
