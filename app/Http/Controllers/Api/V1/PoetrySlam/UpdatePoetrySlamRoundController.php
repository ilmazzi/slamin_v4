<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\UpdatePoetrySlamRoundRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\UpdatePoetrySlamRoundAction;
use Domain\PoetrySlam\DTOs\UpdatePoetrySlamRoundData;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class UpdatePoetrySlamRoundController extends Controller
{
    public function __construct(
        private readonly UpdatePoetrySlamRoundAction $action,
    ) {
    }

    public function __invoke(UpdatePoetrySlamRoundRequest $request, Event $event, PoetrySlamRound $round): JsonResponse
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

        $round = $this->action->execute(
            round: $round,
            userId: $request->user()->id,
            data: UpdatePoetrySlamRoundData::fromRequest($request),
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
        ]);
    }
}
