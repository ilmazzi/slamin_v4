<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Media\MediaFeaturePackResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ListMediaFeaturePacksAction;
use Illuminate\Http\JsonResponse;

class ListMediaFeaturePacksController extends Controller
{
    public function __construct(
        private readonly ListMediaFeaturePacksAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            data: MediaFeaturePackResource::collection($this->action->execute())
        );
    }
}
