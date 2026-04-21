<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\ListNotificationsRequest;
use App\Http\Resources\Api\V1\Notification\NotificationResource;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\ListNotificationsAction;
use Illuminate\Http\JsonResponse;

class ListNotificationsController extends Controller
{
    public function __construct(
        private readonly ListNotificationsAction $action,
    ) {
    }

    public function __invoke(ListNotificationsRequest $request): JsonResponse
    {
        $notifications = $this->action->execute(
            userId: $request->user()->id,
            perPage: (int) $request->validated('per_page', 15),
            unreadOnly: $request->has('unread_only')
                ? (bool) $request->validated('unread_only')
                : null,
        );

        return ApiResponse::success(
            data: NotificationResource::collection($notifications->items()),
            meta: [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        );
    }
}
