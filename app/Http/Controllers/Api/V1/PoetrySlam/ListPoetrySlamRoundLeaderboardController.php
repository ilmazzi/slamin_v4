<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\CalculatePoetrySlamRoundLeaderboardAction;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class ListPoetrySlamRoundLeaderboardController extends Controller
{
    public function __construct(
        private readonly CalculatePoetrySlamRoundLeaderboardAction $action,
    ) {
    }

    public function __invoke(Event $event, PoetrySlamRound $round): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');
        $round->loadMissing('poetrySlam');

        if ($event->poetrySlam === null || $round->poetry_slam_id !== $event->poetrySlam->id) {
            return ApiResponse::error(
                message: __('poetry_slam.round_not_found'),
                status: 404,
            );
        }

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
            'leaderboard' => $this->action->execute($round),
        ]);
    }
}
