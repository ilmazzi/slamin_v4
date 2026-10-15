<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\OpenGigNegotiationRequest;
use App\Http\Resources\Api\V1\Gig\GigNegotiationResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\OpenGigNegotiationAction;
use Domain\Gig\DTOs\OpenGigNegotiationData;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;

class OpenGigNegotiationController extends Controller
{
    public function __construct(
        private readonly OpenGigNegotiationAction $action,
    ) {}

    public function __invoke(OpenGigNegotiationRequest $request, Gig $gig, GigApplication $application): JsonResponse
    {
        abort_unless($gig->isManagedBy((int) $request->user()->id), 403);

        $negotiation = $this->action->execute($gig, $application, OpenGigNegotiationData::fromRequest($request));

        return ApiResponse::success([
            'negotiation' => new GigNegotiationResource($negotiation),
        ], status: 201);
    }
}
