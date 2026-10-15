<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigApplicationResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListMyGigApplicationsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyGigApplicationsController extends Controller
{
    public function __construct(
        private readonly ListMyGigApplicationsAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'applications' => GigApplicationResource::collection($this->action->execute((int) $request->user()->id)),
        ]);
    }
}
