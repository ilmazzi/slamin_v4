<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreGigApplicationRequest;
use App\Http\Resources\Api\V1\Gig\GigApplicationResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreGigApplicationAction;
use Domain\Gig\DTOs\StoreGigApplicationData;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class StoreGigApplicationController extends Controller
{
    public function __construct(
        private readonly StoreGigApplicationAction $action,
    ) {}

    public function __invoke(StoreGigApplicationRequest $request, Gig $gig): JsonResponse
    {
        if (! $gig->isListed() && ! $gig->isManagedBy((int) $request->user()->id)) {
            return ApiResponse::error(__('gig.not_available'), status: 404);
        }

        $application = $this->action->execute($gig, $request->user(), StoreGigApplicationData::fromRequest($request));

        return ApiResponse::success([
            'application' => new GigApplicationResource($application),
        ], status: 201);
    }
}
