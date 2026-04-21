<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\DeleteNotificationAction;
use Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteNotificationController extends Controller
{
    public function __construct(
        private readonly DeleteNotificationAction $action,
    ) {
    }

    public function __invoke(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== request()->user()->id) {
            throw new NotFoundHttpException();
        }

        $this->action->execute($notification, request()->user()->id);

        return ApiResponse::success();
    }
}
