<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Engagement\Actions\UnfollowAction;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnfollowEventController extends Controller
{
    public function __construct(
        private readonly UnfollowAction $unfollowAction,
    ) {}

    public function __invoke(Request $request, Event $event): JsonResponse
    {
        return ApiResponse::success([
            'follow' => $this->unfollowAction->execute($event, $request->user()->id),
        ]);
    }
}
