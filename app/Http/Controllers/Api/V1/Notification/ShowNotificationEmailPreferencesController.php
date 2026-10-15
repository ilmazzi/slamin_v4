<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\GetNotificationEmailPreferencesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowNotificationEmailPreferencesController extends Controller
{
    public function __construct(
        private readonly GetNotificationEmailPreferencesAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'preferences' => $this->action->execute($request->user()),
        ]);
    }
}
