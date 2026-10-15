<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Notification\NotificationResource;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\MarkNotificationAsReadAction;
use Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarkNotificationAsReadController extends Controller
{
    public function __construct(
        private readonly MarkNotificationAsReadAction $action,
    ) {}

    public function __invoke(Notification $notification): JsonResponse
    {
        if ((int) $notification->user_id !== (int) request()->user()->id) {
            throw new NotFoundHttpException;
        }

        $notification = $this->action->execute($notification, request()->user()->id);

        return ApiResponse::success([
            'notification' => new NotificationResource($notification),
        ]);
    }
}
