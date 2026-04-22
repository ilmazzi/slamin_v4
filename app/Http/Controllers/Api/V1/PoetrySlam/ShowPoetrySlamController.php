<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;

class ShowPoetrySlamController extends Controller
{
    public function __invoke(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->loadMissing('poetrySlam.entries');

        if ($event->poetrySlam === null) {
            return ApiResponse::error(
                message: __('poetry_slam.not_found'),
                status: 404,
            );
        }

        $poetrySlam = $event->poetrySlam;

        return ApiResponse::success(
            data: [
                'poetry_slam' => [
                    'id' => $poetrySlam->id,
                    'event_id' => $poetrySlam->event_id,
                    'status' => $poetrySlam->status?->value,
                    'rules_summary' => $poetrySlam->rules_summary,
                    'slots_limit' => $poetrySlam->slots_limit,
                    'judging_mode' => $poetrySlam->judging_mode,
                    'settings' => $poetrySlam->settings,
                    'entries_count' => $poetrySlam->entries->count(),
                    'created_at' => $poetrySlam->created_at?->toISOString(),
                    'updated_at' => $poetrySlam->updated_at?->toISOString(),
                ],
            ],
            meta: [],
        );
    }
}