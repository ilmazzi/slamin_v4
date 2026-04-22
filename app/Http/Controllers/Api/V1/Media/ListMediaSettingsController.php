<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ResolveMediaSettingsAction;
use Illuminate\Http\JsonResponse;

class ListMediaSettingsController extends Controller
{
    public function __construct(
        private readonly ResolveMediaSettingsAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'settings' => $this->action->execute(),
        ]);
    }
}
