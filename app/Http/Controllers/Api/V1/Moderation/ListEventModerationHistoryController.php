<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Moderation\ModerationDecisionResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Moderation\Actions\ListEventModerationHistoryAction;
use Illuminate\Http\JsonResponse;

class ListEventModerationHistoryController extends Controller
{
    public function __construct(
        private readonly ListEventModerationHistoryAction $action,
    ) {
    }

    public function __invoke(Event $event): JsonResponse
    {
        return ApiResponse::success([
            'history' => ModerationDecisionResource::collection($this->action->execute($event)),
        ]);
    }
}
