<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\AgreeGigNegotiationAction;
use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgreeGigNegotiationController extends Controller
{
    public function __construct(
        private readonly AgreeGigNegotiationAction $action,
    ) {
    }

    public function __invoke(Request $request, GigNegotiation $negotiation): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $negotiation), 403);

        $negotiation = $this->action->execute($negotiation);

        return ApiResponse::success([
            'negotiation' => [
                'id' => $negotiation->id,
                'status' => $negotiation->status?->value,
                'agreed_price' => $negotiation->agreed_price,
                'currency' => $negotiation->currency,
            ],
        ]);
    }

    private function canAccess(int $userId, GigNegotiation $negotiation): bool
    {
        return $negotiation->owner_user_id === $userId || $negotiation->translator_user_id === $userId;
    }
}
