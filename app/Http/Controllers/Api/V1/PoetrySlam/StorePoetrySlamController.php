<?php

namespace App\Http\Controllers\Api\V1\PoetrySlam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PoetrySlam\StorePoetrySlamRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\PoetrySlam\Actions\StorePoetrySlamAction;
use Domain\PoetrySlam\DTOs\StorePoetrySlamData;
use Illuminate\Http\JsonResponse;

class StorePoetrySlamController extends Controller
{
    public function __construct(
        private readonly StorePoetrySlamAction $storePoetrySlamAction,
    ) {
    }

    public function __invoke(StorePoetrySlamRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $poetrySlam = $this->storePoetrySlamAction->execute(
            event: $event,
            data: StorePoetrySlamData::fromRequest($request),
        );

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
            status: 201,
        );
    }
}