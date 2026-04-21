<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\MarkAllNotificationsAsReadAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkAllNotificationsAsReadController extends Controller
{
    public function __construct(
        private readonly MarkAllNotificationsAsReadAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $updated = $this->action->execute($request->user()->id);

        return ApiResponse::success([
            'updated_count' => $updated,
        ]);
    }
}
