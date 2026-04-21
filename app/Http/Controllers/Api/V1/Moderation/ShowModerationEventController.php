<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Http\Resources\Api\V1\Moderation\ModerationDecisionResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Moderation\Actions\ShowModerationEventAction;
use Illuminate\Http\JsonResponse;

class ShowModerationEventController extends Controller
{
    public function __construct(
        private readonly ShowModerationEventAction $action,
    ) {
    }

    public function __invoke(Event $event): JsonResponse
    {
        $event = $this->action->execute($event);

        return ApiResponse::success([
            'event' => new EventResource($event),
            'moderation_history' => ModerationDecisionResource::collection($event->moderationDecisions),
        ]);
    }
}
