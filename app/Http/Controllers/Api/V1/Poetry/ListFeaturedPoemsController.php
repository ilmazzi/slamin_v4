<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\ListFeaturedPoemsRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\DTOs\ListFeaturedPoemsData;
use Domain\Poetry\Queries\ListFeaturedPoemsQuery;
use Illuminate\Http\JsonResponse;

class ListFeaturedPoemsController extends Controller
{
    public function __invoke(ListFeaturedPoemsRequest $request, ListFeaturedPoemsQuery $query): JsonResponse
    {
        $data = ListFeaturedPoemsData::fromRequest($request);
        $poems = $query->execute($data);

        return ApiResponse::success(
            data: PoemResource::collection($poems->items()),
            meta: [
                'current_page' => $poems->currentPage(),
                'last_page' => $poems->lastPage(),
                'per_page' => $poems->perPage(),
                'total' => $poems->total(),
            ],
        );
    }
}
