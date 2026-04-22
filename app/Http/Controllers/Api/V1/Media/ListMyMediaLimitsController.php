<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ResolveUserMediaLimitsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyMediaLimitsController extends Controller
{
    public function __construct(
        private readonly ResolveUserMediaLimitsAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'limits' => $this->action->execute($request->user()->id),
        ]);
    }
}
