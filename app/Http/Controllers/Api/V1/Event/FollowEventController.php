<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Engagement\Actions\FollowAction;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowEventController extends Controller
{
    public function __construct(
        private readonly FollowAction $followAction,
    ) {}

    public function __invoke(Request $request, Event $event): JsonResponse
    {
        $this->authorize('follow', $event);

        return ApiResponse::success([
            'follow' => $this->followAction->execute($event, $request->user()->id),
        ]);
    }
}
