<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\RemoveEventAvailabilityVoteAction;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAvailabilityOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveEventAvailabilityVoteController extends Controller
{
    public function __construct(
        private readonly RemoveEventAvailabilityVoteAction $action,
    ) {
    }

    public function __invoke(Request $request, Event $event, EventAvailabilityOption $option): JsonResponse
    {
        $this->authorize('voteAvailability', $event);

        $event = $this->action->execute($event, $option, (int) $request->user()->id);

        return ApiResponse::success([
            'event' => new EventResource($event),
        ]);
    }
}
