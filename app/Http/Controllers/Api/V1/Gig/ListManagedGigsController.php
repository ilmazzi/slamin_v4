<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\ListManagedGigsRequest;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListManagedGigsAction;
use Domain\Gig\Enums\GigStatus;
use Illuminate\Http\JsonResponse;

class ListManagedGigsController extends Controller
{
    public function __construct(
        private readonly ListManagedGigsAction $action,
    ) {}

    public function __invoke(ListManagedGigsRequest $request): JsonResponse
    {
        $status = $request->validated('status');

        $gigs = $this->action->execute(
            (int) $request->user()->id,
            $status ? GigStatus::from($status) : null,
            (int) ($request->validated('per_page') ?? 10),
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
