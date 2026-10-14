<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\ListEventRequest;
use App\Http\Resources\Api\V1\Event\EventSummaryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\DTOs\ListEventData;
use Domain\Event\Queries\ListEventQuery;
use Illuminate\Http\JsonResponse;

class ListEventController extends Controller
{
    public function __construct(
        private readonly ListEventQuery $listEventQuery,
    ) {}

    public function __invoke(ListEventRequest $request): JsonResponse
    {
        $events = $this->listEventQuery->execute(ListEventData::fromRequest($request));

        return ApiResponse::success(
            data: EventSummaryResource::collection($events->items()),
            meta: [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        );
    }
}
