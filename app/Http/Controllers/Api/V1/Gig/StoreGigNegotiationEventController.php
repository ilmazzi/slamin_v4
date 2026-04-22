<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreGigNegotiationEventRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreGigNegotiationEventAction;
use Domain\Gig\DTOs\StoreGigNegotiationEventData;
use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreGigNegotiationEventController extends Controller
{
    public function __construct(
        private readonly StoreGigNegotiationEventAction $action,
    ) {
    }

    public function __invoke(StoreGigNegotiationEventRequest $request, GigNegotiation $negotiation): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $negotiation), 403);

        $event = $this->action->execute($negotiation, StoreGigNegotiationEventData::fromRequest($request));

        return ApiResponse::success([
            'event' => [
                'id' => $event->id,
                'event_type' => $event->event_type?->value,
                'price' => $event->price,
                'currency' => $event->currency,
                'deadline' => $event->deadline?->toISOString(),
                'note' => $event->note,
            ],
        ], status: 201);
    }

    private function canAccess(int $userId, GigNegotiation $negotiation): bool
    {
        return $negotiation->owner_user_id === $userId || $negotiation->translator_user_id === $userId;
    }
}
