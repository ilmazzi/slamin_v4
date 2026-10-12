<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\UpdateMediaSettingsRequest;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ResolveMediaSettingsAction;
use Domain\Media\Actions\UpdateMediaSettingsAction;
use Illuminate\Http\JsonResponse;

class UpdateMediaSettingsController extends Controller
{
    public function __construct(
        private readonly UpdateMediaSettingsAction $updateAction,
        private readonly ResolveMediaSettingsAction $resolveAction,
    ) {}

    public function __invoke(UpdateMediaSettingsRequest $request): JsonResponse
    {
        $this->updateAction->execute($request->validated());

        return ApiResponse::success([
            'settings' => $this->resolveAction->presentForAdmin(),
        ]);
    }
}
