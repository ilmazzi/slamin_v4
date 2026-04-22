<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\StorePoetrySlamRoundRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\StorePoetrySlamRoundAction;
use Domain\PoetrySlam\DTOs\StorePoetrySlamRoundData;
use Illuminate\Http\JsonResponse;

class StorePoetrySlamRoundController extends Controller
{
    public function __construct(
        private readonly StorePoetrySlamRoundAction $action,
    ) {
    }

    public function __invoke(StorePoetrySlamRoundRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');

        if ($event->poetrySlam === null) {
            return ApiResponse::error(
                message: __('poetry_slam.not_found'),
                status: 404,
            );
        }

        $round = $this->action->execute(
            $event->poetrySlam,
            StorePoetrySlamRoundData::fromRequest($request),
        );

        return ApiResponse::success([
            'round' => [
                'id' => $round->id,
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
                'started_at' => $round->started_at?->toISOString(),
                'completed_at' => $round->completed_at?->toISOString(),
                'created_at' => $round->created_at?->toISOString(),
                'updated_at' => $round->updated_at?->toISOString(),
            ],
        ], status: 201);
    }
}
