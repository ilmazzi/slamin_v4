<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\CalculatePoetrySlamLeaderboardAction;
use Illuminate\Http\JsonResponse;

class ListPoetrySlamLeaderboardController extends Controller
{
    public function __construct(
        private readonly CalculatePoetrySlamLeaderboardAction $action,
    ) {
    }

    public function __invoke(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');

        if ($event->poetrySlam === null) {
            return ApiResponse::error(
                message: __('poetry_slam.not_found'),
                status: 404,
            );
        }

        return ApiResponse::success([
            'poetry_slam' => [
                'id' => $event->poetrySlam->id,
                'event_id' => $event->poetrySlam->event_id,
                'status' => $event->poetrySlam->status?->value,
            ],
            'leaderboard' => $this->action->execute($event->poetrySlam),
        ]);
    }
}
