<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\UpsertPoetrySlamEntryScoresRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\UpsertPoetrySlamEntryScoresAction;
use Domain\PoetrySlam\DTOs\UpsertPoetrySlamEntryScoresData;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class UpsertPoetrySlamEntryScoresController extends Controller
{
    public function __construct(
        private readonly UpsertPoetrySlamEntryScoresAction $action,
    ) {
    }

    public function __invoke(
        UpsertPoetrySlamEntryScoresRequest $request,
        Event $event,
        PoetrySlamRound $round,
        PoetrySlamEntry $entry
    ): JsonResponse {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');
        $round->loadMissing('poetrySlam');
        $entry->loadMissing('poetrySlam');

        if (
            $event->poetrySlam === null
            || $round->poetry_slam_id !== $event->poetrySlam->id
            || $entry->poetry_slam_id !== $event->poetrySlam->id
        ) {
            return ApiResponse::error(
                message: __('poetry_slam.score_context_not_found'),
                status: 404,
            );
        }

        $result = $this->action->execute(
            round: $round,
            entry: $entry,
            userId: $request->user()->id,
            data: UpsertPoetrySlamEntryScoresData::fromRequest($request),
        );

        return ApiResponse::success([
            'result' => $result,
        ]);
    }
}
