<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\RecentEventPlaceResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Queries\ListRecentEventPlacesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListRecentEventPlacesController extends Controller
{
    public function __construct(
        private readonly ListRecentEventPlacesQuery $listRecentEventPlacesQuery,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'places' => RecentEventPlaceResource::collection(
                $this->listRecentEventPlacesQuery->execute($request->user()->id)
            ),
        ]);
    }
}
