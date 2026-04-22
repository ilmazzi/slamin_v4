<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupEventsAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ListGroupEventsController extends Controller
{
    public function __construct(
        private readonly ListGroupEventsAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $events = $this->action->execute(
            $group,
            $group->hasActiveMember((int) request()->user()?->id),
        );

        return ApiResponse::success([
            'events' => EventResource::collection($events),
        ]);
    }
}
