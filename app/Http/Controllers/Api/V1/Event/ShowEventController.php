<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Queries\ShowEventQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowEventController extends Controller
{
    public function __construct(
        private readonly ShowEventQuery $showEventQuery,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $authUserId = auth('sanctum')->id();

        $event = $this->showEventQuery->execute($slug, $authUserId);

        $viewerId = $authUserId === null ? null : (int) $authUserId;
        if ($event->status === EventStatus::Published && $event->is_public && ! $event->isManagedBy($viewerId)) {
            $event->increment('views_count');
        }

        return ApiResponse::success([
            'event' => new EventResource($event),
        ]);
    }
}
