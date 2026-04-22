<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ShowGigNegotiationAction;
use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowGigNegotiationController extends Controller
{
    public function __construct(
        private readonly ShowGigNegotiationAction $action,
    ) {
    }

    public function __invoke(Request $request, GigNegotiation $negotiation): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $negotiation), 403);

        $negotiation = $this->action->execute($negotiation);

        return ApiResponse::success([
            'negotiation' => [
                'id' => $negotiation->id,
                'gig_id' => $negotiation->gig_id,
                'status' => $negotiation->status?->value,
                'agreed_price' => $negotiation->agreed_price,
                'currency' => $negotiation->currency,
                'agreed_deadline' => $negotiation->agreed_deadline?->toISOString(),
                'events' => $negotiation->events->map(fn ($event): array => [
                    'id' => $event->id,
                    'event_type' => $event->event_type?->value,
                    'price' => $event->price,
                    'currency' => $event->currency,
                    'deadline' => $event->deadline?->toISOString(),
                    'note' => $event->note,
                    'author_user_id' => $event->author_user_id,
                    'created_at' => $event->created_at?->toISOString(),
                ])->values()->all(),
            ],
        ]);
    }

    private function canAccess(int $userId, GigNegotiation $negotiation): bool
    {
        return $negotiation->owner_user_id === $userId || $negotiation->translator_user_id === $userId;
    }
}
