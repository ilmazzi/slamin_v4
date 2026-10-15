<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\ListOpenGigsRequest;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListOpenGigsAction;
use Domain\Gig\DTOs\ListOpenGigsData;
use Illuminate\Http\JsonResponse;

class ListOpenGigsController extends Controller
{
    public function __construct(
        private readonly ListOpenGigsAction $action,
    ) {}

    public function __invoke(ListOpenGigsRequest $request): JsonResponse
    {
        $gigs = $this->action->execute(
            ListOpenGigsData::fromRequest($request),
            (int) $request->user()->id,
        );

        return ApiResponse::success(
            data: [
                'gigs' => GigResource::collection($gigs->items()),
            ],
            meta: [
                'current_page' => $gigs->currentPage(),
                'last_page' => $gigs->lastPage(),
                'per_page' => $gigs->perPage(),
                'total' => $gigs->total(),
            ],
        );
    }
}
