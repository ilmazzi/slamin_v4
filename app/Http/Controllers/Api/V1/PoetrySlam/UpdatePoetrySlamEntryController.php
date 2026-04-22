<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\UpdatePoetrySlamEntryRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\UpdatePoetrySlamEntryAction;
use Domain\PoetrySlam\DTOs\UpdatePoetrySlamEntryData;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Illuminate\Http\JsonResponse;

class UpdatePoetrySlamEntryController extends Controller
{
    public function __construct(
        private readonly UpdatePoetrySlamEntryAction $action,
    ) {
    }

    public function __invoke(UpdatePoetrySlamEntryRequest $request, Event $event, PoetrySlamEntry $entry): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');
        $entry->loadMissing('poetrySlam');

        if ($event->poetrySlam === null || $entry->poetry_slam_id !== $event->poetrySlam->id) {
            return ApiResponse::error(
                message: __('poetry_slam.entry_not_found'),
                status: 404,
            );
        }

        $entry = $this->action->execute(
            entry: $entry,
            userId: $request->user()->id,
            data: UpdatePoetrySlamEntryData::fromRequest($request),
        );

        return ApiResponse::success([
            'entry' => [
                'id' => $entry->id,
                'poetry_slam_id' => $entry->poetry_slam_id,
                'entry_source' => $entry->entry_source?->value,
                'event_participant_invite_id' => $entry->event_participant_invite_id,
                'user_id' => $entry->user_id,
                'display_name' => $entry->display_name,
                'email' => $entry->email,
                'notes' => $entry->notes,
                'status' => $entry->status?->value,
                'performance_order' => $entry->performance_order,
                'created_at' => $entry->created_at?->toISOString(),
                'updated_at' => $entry->updated_at?->toISOString(),
            ],
        ]);
    }
}
