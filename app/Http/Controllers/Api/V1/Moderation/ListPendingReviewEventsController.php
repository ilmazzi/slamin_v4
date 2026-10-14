<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListPendingReviewEventsAction;
use Illuminate\Http\JsonResponse;

class ListPendingReviewEventsController extends Controller
{
    public function __construct(
        private readonly ListPendingReviewEventsAction $action,
    ) {}

    public function __invoke(PaginationRequest $request): JsonResponse
    {
        $events = $this->action->execute($request->perPage());

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
