<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreGigNegotiationEventRequest;
use App\Http\Resources\Api\V1\Gig\GigNegotiationEventResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreGigNegotiationEventAction;
use Domain\Gig\DTOs\StoreGigNegotiationEventData;
use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\JsonResponse;

class StoreGigNegotiationEventController extends Controller
{
    public function __construct(
        private readonly StoreGigNegotiationEventAction $action,
    ) {}

    public function __invoke(StoreGigNegotiationEventRequest $request, GigNegotiation $negotiation): JsonResponse
    {
        abort_unless($negotiation->involves((int) $request->user()->id), 403);

        $event = $this->action->execute($negotiation, StoreGigNegotiationEventData::fromRequest($request));

        return ApiResponse::success([
            'event' => new GigNegotiationEventResource($event),
        ], status: 201);
    }
}
