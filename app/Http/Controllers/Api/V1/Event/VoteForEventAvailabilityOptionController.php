<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\VoteForEventAvailabilityOptionAction;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAvailabilityOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoteForEventAvailabilityOptionController extends Controller
{
    public function __construct(
        private readonly VoteForEventAvailabilityOptionAction $action,
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
