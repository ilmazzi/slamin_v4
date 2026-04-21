<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\EventResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\ShowEventAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowEventController extends Controller
{
    public function __construct(
        private readonly ShowEventAction $action,
    ) {
    }

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $event = $this->action->execute($slug, $request->user()?->id);

        return ApiResponse::success([
            'event' => new EventResource($event),
        ]);
    }
}
