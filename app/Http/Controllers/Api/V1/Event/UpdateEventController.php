<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\UpdateEventAction;
use Domain\Event\DTOs\UpdateEventData;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;

class UpdateEventController extends Controller
{
    public function __construct(
        private readonly UpdateEventAction $action,
    ) {
    }

    public function __invoke(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        if ($request->isStatusOnlyUpdate()) {
            $event = $this->action->updatePublicationStatus(
                $event,
                EventStatus::from($request->validated('status')),
                (int) $request->user()->id,
            );
        } else {
            $event = $this->action->execute($event, UpdateEventData::fromRequest($request));
        }

        return ApiResponse::success([
            'event' => new EventResource($event),
        ]);
    }
}
