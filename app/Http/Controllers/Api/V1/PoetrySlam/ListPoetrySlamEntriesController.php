<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;

class ListPoetrySlamEntriesController extends Controller
{
    public function __invoke(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam.entries.user');

        if ($event->poetrySlam === null) {
            return ApiResponse::error(
                message: __('poetry_slam.not_found'),
                status: 404,
            );
        }

        return ApiResponse::success([
            'entries' => $event->poetrySlam->entries
                ->sortBy([
                    ['performance_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(fn ($entry): array => [
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
                ]),
        ]);
    }
}
