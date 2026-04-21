<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\DeleteAllNotificationsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteAllNotificationsController extends Controller
{
    public function __construct(
        private readonly DeleteAllNotificationsAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $deleted = $this->action->execute($request->user()->id);

        return ApiResponse::success([
            'deleted_count' => $deleted,
        ]);
    }
}
