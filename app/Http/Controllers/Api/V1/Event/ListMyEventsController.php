<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Queries\ListMyEventQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyEventsController extends Controller
{
    public function __construct(
        private readonly ListMyEventQuery $listMyEventQuery,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $events = $this->listMyEventQuery->execute(
            $request->user()->id,
            (int) $request->query('per_page', 10),
        );

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
