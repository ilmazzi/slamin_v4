<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\RemoveBookmarkEventAction;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveBookmarkEventController extends Controller
{
    public function __construct(
        private readonly RemoveBookmarkEventAction $removeBookmarkEventAction,
    ) {}

    public function __invoke(Request $request, Event $event): JsonResponse
    {
        $this->authorize('bookmark', $event);

        $this->removeBookmarkEventAction->execute($event, $request->user()->id);

        return ApiResponse::success([
            'event' => new EventResource($event->fresh(['user.profile'])?->loadEngagement($request->user()->id)),
        ]);
    }
}
