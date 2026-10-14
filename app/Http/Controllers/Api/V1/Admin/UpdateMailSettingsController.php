<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateMailSettingsRequest;
use App\Support\Responses\ApiResponse;
use Domain\Admin\Actions\ResolveMailSettingsAction;
use Domain\Admin\Actions\UpdateMailSettingsAction;
use Illuminate\Http\JsonResponse;

class UpdateMailSettingsController extends Controller
{
    public function __invoke(
        UpdateMailSettingsRequest $request,
        UpdateMailSettingsAction $updateAction,
        ResolveMailSettingsAction $resolveAction,
    ): JsonResponse {
        $updateAction->execute($request->validated());

        return ApiResponse::success([
            'settings' => $resolveAction->execute(),
        ]);
    }
}
