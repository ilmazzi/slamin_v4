<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ApprovePoemRequest;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Moderation\Actions\ApproveEventAction;
use Illuminate\Http\JsonResponse;

class ApproveEventController extends Controller
{
    public function __construct(
        private readonly ApproveEventAction $action,
    ) {
    }

    public function __invoke(ApprovePoemRequest $request, Event $event): JsonResponse
    {
        $event = $this->action->execute($event, $request->user()->id, $request->validated('note'));

        return ApiResponse::success([
            'event' => new EventResource($event),
        ]);
    }
}
