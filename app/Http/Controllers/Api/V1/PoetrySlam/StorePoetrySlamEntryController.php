<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\StorePoetrySlamEntryRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\StorePoetrySlamEntryAction;
use Domain\PoetrySlam\DTOs\StorePoetrySlamEntryData;
use Illuminate\Http\JsonResponse;

class StorePoetrySlamEntryController extends Controller
{
    public function __construct(
        private readonly StorePoetrySlamEntryAction $action,
    ) {
    }

    public function __invoke(StorePoetrySlamEntryRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam');

        if ($event->poetrySlam === null) {
            return ApiResponse::error(
                message: __('poetry_slam.not_found'),
                status: 404,
            );
        }

        $entry = $this->action->execute(
            $event->poetrySlam,
            StorePoetrySlamEntryData::fromRequest($request),
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
        ], status: 201);
    }
}
