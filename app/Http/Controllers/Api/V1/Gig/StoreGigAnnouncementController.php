<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreGigAnnouncementRequest;
use App\Http\Resources\Api\V1\Gig\GigAnnouncementResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreGigAnnouncementAction;
use Domain\Gig\DTOs\StoreGigAnnouncementData;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class StoreGigAnnouncementController extends Controller
{
    public function __construct(
        private readonly StoreGigAnnouncementAction $action,
    ) {}

    public function __invoke(StoreGigAnnouncementRequest $request, Gig $gig): JsonResponse
    {
        abort_unless($gig->isManagedBy((int) $request->user()->id), 403);

        $announcement = $this->action->execute($gig, StoreGigAnnouncementData::fromRequest($request));

        return ApiResponse::success([
            'announcement' => new GigAnnouncementResource($announcement),
        ], status: 201);
    }
}
