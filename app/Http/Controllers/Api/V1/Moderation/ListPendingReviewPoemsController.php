<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListPendingReviewPoemsAction;
use Illuminate\Http\JsonResponse;

class ListPendingReviewPoemsController extends Controller
{
    public function __construct(
        private readonly ListPendingReviewPoemsAction $action,
    ) {}

    public function __invoke(PaginationRequest $request): JsonResponse
    {
        $poems = $this->action->execute($request->perPage());

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
