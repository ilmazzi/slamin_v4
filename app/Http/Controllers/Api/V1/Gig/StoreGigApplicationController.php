<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreGigApplicationRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreGigApplicationAction;
use Domain\Gig\DTOs\StoreGigApplicationData;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class StoreGigApplicationController extends Controller
{
    public function __construct(
        private readonly StoreGigApplicationAction $action,
    ) {
    }

    public function __invoke(StoreGigApplicationRequest $request, Gig $gig): JsonResponse
    {
        $application = $this->action->execute($gig, $request->user(), StoreGigApplicationData::fromRequest($request));

        return ApiResponse::success([
            'application' => [
                'id' => $application->id,
                'gig_id' => $application->gig_id,
                'user_id' => $application->user_id,
                'message' => $application->message,
                'status' => $application->status?->value,
                'created_at' => $application->created_at?->toISOString(),
            ],
        ], status: 201);
    }
}
