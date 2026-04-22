<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\UpsertPoetrySlamRoundAdvancementDecisionRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\UpsertPoetrySlamRoundAdvancementDecisionAction;
use Domain\PoetrySlam\DTOs\UpsertPoetrySlamRoundAdvancementDecisionData;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class UpsertPoetrySlamRoundAdvancementDecisionController extends Controller
{
    public function __construct(
        private readonly UpsertPoetrySlamRoundAdvancementDecisionAction $action,
    ) {
    }

    public function __invoke(
        UpsertPoetrySlamRoundAdvancementDecisionRequest $request,
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
                message: __('poetry_slam.advancement_context_not_found'),
                status: 404,
            );
        }

        $decision = $this->action->execute(
            round: $round,
            entry: $entry,
            userId: $request->user()->id,
            data: UpsertPoetrySlamRoundAdvancementDecisionData::fromRequest($request),
        );

        return ApiResponse::success([
            'decision' => [
                'entry_id' => $decision->poetry_slam_entry_id,
                'decision_status' => $decision->decision_status?->value,
                'note' => $decision->note,
                'decided_at' => $decision->decided_at?->toISOString(),
            ],
        ]);
    }
}
