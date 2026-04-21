<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\GetUnreadNotificationsCountAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetUnreadNotificationsCountController extends Controller
{
    public function __construct(
        private readonly GetUnreadNotificationsCountAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'unread_count' => $this->action->execute($request->user()->id),
        ]);
    }
}
