<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\UpdateNotificationEmailPreferencesRequest;
use App\Support\Responses\ApiResponse;
use Domain\Notification\Actions\GetNotificationEmailPreferencesAction;
use Domain\Notification\Actions\UpdateNotificationEmailPreferencesAction;
use Illuminate\Http\JsonResponse;

class UpdateNotificationEmailPreferencesController extends Controller
{
    public function __construct(
        private readonly UpdateNotificationEmailPreferencesAction $updateAction,
        private readonly GetNotificationEmailPreferencesAction $getAction,
    ) {}

    public function __invoke(UpdateNotificationEmailPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->updateAction->execute($user, $request->preferences());

        return ApiResponse::success([
            'preferences' => $this->getAction->execute($user),
        ]);
    }
}
