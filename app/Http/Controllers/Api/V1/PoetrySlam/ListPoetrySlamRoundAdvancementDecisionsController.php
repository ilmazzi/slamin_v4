<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementDecisionStatus;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Illuminate\Http\JsonResponse;

class ListPoetrySlamRoundAdvancementDecisionsController extends Controller
{
    public function __invoke(Event $event, PoetrySlamRound $round): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam.entries');
        $round->loadMissing('poetrySlam', 'advancementDecisions');

        if ($event->poetrySlam === null || $round->poetry_slam_id !== $event->poetrySlam->id) {
            return ApiResponse::error(
                message: __('poetry_slam.round_not_found'),
                status: 404,
            );
        }

        $decisionsByEntryId = $round->advancementDecisions->keyBy('poetry_slam_entry_id');

        return ApiResponse::success([
            'round' => [
                'id' => $round->id,
                'round_number' => $round->round_number,
                'advancement_mode' => $round->advancement_mode?->value,
                'advancement_value' => $round->advancement_value,
                'advancement_based_on' => $round->advancement_based_on?->value,
            ],
            'decisions' => $event->poetrySlam->entries
                ->sortBy([
                    ['performance_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(function ($entry) use ($decisionsByEntryId): array {
                    $decision = $decisionsByEntryId->get($entry->id);

                    return [
                        'entry_id' => $entry->id,
                        'display_name' => $entry->display_name,
                        'entry_source' => $entry->entry_source?->value,
                        'performance_order' => $entry->performance_order,
                        'decision_status' => $decision?->decision_status?->value ?? PoetrySlamRoundAdvancementDecisionStatus::Pending->value,
                        'note' => $decision?->note,
                        'decided_at' => $decision?->decided_at?->toISOString(),
                    ];
                }),
        ]);
    }
}
