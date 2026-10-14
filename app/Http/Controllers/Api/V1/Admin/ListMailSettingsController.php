<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Admin\Actions\ResolveMailSettingsAction;
use Illuminate\Http\JsonResponse;

class ListMailSettingsController extends Controller
{
    public function __invoke(ResolveMailSettingsAction $action): JsonResponse
    {
        return ApiResponse::success([
            'settings' => $action->execute(),
        ]);
    }
}
