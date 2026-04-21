<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\ListEventRequest;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\ListEventAction;
use Domain\Event\DTOs\ListEventData;
use Illuminate\Http\JsonResponse;

class ListEventController extends Controller
{
    public function __construct(
        private readonly ListEventAction $action,
    ) {
    }

    public function __invoke(ListEventRequest $request): JsonResponse
    {
        $events = $this->action->execute(ListEventData::fromRequest($request));

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
