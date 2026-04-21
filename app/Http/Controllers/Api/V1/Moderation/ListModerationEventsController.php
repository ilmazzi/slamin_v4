<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ListModerationEventsRequest;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListModerationEventsAction;
use Domain\Moderation\DTOs\ListModerationEventsData;
use Illuminate\Http\JsonResponse;

class ListModerationEventsController extends Controller
{
    public function __construct(
        private readonly ListModerationEventsAction $action,
    ) {
    }

    public function __invoke(ListModerationEventsRequest $request): JsonResponse
    {
        $events = $this->action->execute(ListModerationEventsData::fromRequest($request));

        return ApiResponse::success(
            data: EventResource::collection($events->items()),
            meta: [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        );
    }
}
