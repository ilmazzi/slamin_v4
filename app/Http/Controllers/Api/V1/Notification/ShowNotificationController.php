<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Notification\NotificationResource;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowNotificationController extends Controller
{
    public function __invoke(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== request()->user()->id) {
            throw new NotFoundHttpException();
        }

        return ApiResponse::success([
            'notification' => new NotificationResource($notification),
        ]);
    }
}
