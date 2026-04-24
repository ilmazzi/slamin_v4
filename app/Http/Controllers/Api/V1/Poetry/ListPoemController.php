<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\ListPoemRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\DTOs\ListPoemData;
use Domain\Poetry\Queries\ListPoemQuery;
use Illuminate\Http\JsonResponse;

class ListPoemController extends Controller
{
    public function __construct(
        private readonly ListPoemQuery $listPoemQuery,
    ) {
    }

    public function __invoke(ListPoemRequest $request): JsonResponse
    {
        $poems = $this->listPoemQuery->execute(
            data: ListPoemData::fromRequest($request),
            authUserId: $request->user()?->id,
        );

        return ApiResponse::success(
            data: PoemResource::collection($poems->items()),
            meta: [
                'current_page' => $poems->currentPage(),
                'last_page' => $poems->lastPage(),
                'per_page' => $poems->perPage(),
                'total' => $poems->total(),
            ]
        );
    }
}
