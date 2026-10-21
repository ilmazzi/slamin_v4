<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\RecentEventPlaceResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Queries\ListRecentEventPlacesQuery;
use Domain\Event\Queries\SearchEventPlacesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListRecentEventPlacesController extends Controller
{
    public function __construct(
        private readonly ListRecentEventPlacesQuery $listRecentEventPlacesQuery,
        private readonly SearchEventPlacesQuery $searchEventPlacesQuery,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $userId = (int) $request->user()->id;
        $places = mb_strlen($query) >= 2
            ? $this->searchEventPlacesQuery->execute($userId, $query)
            : $this->listRecentEventPlacesQuery->execute($userId);

        return ApiResponse::success([
            'places' => RecentEventPlaceResource::collection($places),
        ]);
    }
}
