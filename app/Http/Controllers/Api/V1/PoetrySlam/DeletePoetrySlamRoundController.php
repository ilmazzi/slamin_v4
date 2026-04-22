<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\DeletePoetrySlamRoundAction;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class DeletePoetrySlamRoundController extends Controller
{
    public function __construct(
        private readonly DeletePoetrySlamRoundAction $action,
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

        $this->action->execute($round, request()->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
