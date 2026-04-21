<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\StoreEventRequest;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\StoreEventAction;
use Domain\Event\DTOs\StoreEventData;
use Illuminate\Http\JsonResponse;

class StoreEventController extends Controller
{
    public function __construct(
        private readonly StoreEventAction $action,
    ) {
    }

    public function __invoke(StoreEventRequest $request): JsonResponse
    {
        $event = $this->action->execute(StoreEventData::fromRequest($request));

        return ApiResponse::success([
            'event' => new EventResource($event),
        ]);
    }
}
