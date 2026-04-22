<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Media\MediaCheckoutOrderResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ListMyMediaCheckoutOrdersAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyMediaCheckoutOrdersController extends Controller
{
    public function __construct(
        private readonly ListMyMediaCheckoutOrdersAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: MediaCheckoutOrderResource::collection($this->action->execute($request->user()->id))
        );
    }
}
