<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\RecentEventPlaceResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\ListRecentEventPlacesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListRecentEventPlacesController extends Controller
{
    public function __construct(
        private readonly ListRecentEventPlacesAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'places' => RecentEventPlaceResource::collection(
                $this->action->execute($request->user()->id)
            ),
        ]);
    }
}
