<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\UnlikeEventAction;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnlikeEventController extends Controller
{
    public function __construct(
        private readonly UnlikeEventAction $unlikeEventAction,
    ) {}

    public function __invoke(Request $request, Event $event): JsonResponse
    {
        $this->authorize('like', $event);

        $this->unlikeEventAction->execute($event, $request->user()->id);

        return ApiResponse::success([
            'event' => new EventResource($event->fresh(['user.profile'])?->loadEngagement($request->user()->id)),
        ]);
    }
}
