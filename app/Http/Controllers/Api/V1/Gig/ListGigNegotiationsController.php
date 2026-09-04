<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListGigNegotiationsAction;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListGigNegotiationsController extends Controller
{
    public function __construct(
        private readonly ListGigNegotiationsAction $action,
    ) {
    }

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $gig), 403);

        $negotiations = $this->action->execute($gig, (int) $request->user()->id);

        return ApiResponse::success([
            'negotiations' => $negotiations->map(fn (GigNegotiation $negotiation): array => [
                'id' => $negotiation->id,
                'gig_id' => $negotiation->gig_id,
                'gig_application_id' => $negotiation->gig_application_id,
                'translator_user_id' => $negotiation->translator_user_id,
                'status' => $negotiation->status?->value,
                'agreed_price' => $negotiation->agreed_price,
                'currency' => $negotiation->currency,
                'agreed_deadline' => $negotiation->agreed_deadline?->toISOString(),
                'events_count' => $negotiation->events->count(),
            ])->values()->all(),
        ]);
    }

    private function canAccess(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        if ((int) ($parent->user_id ?? 0) === $userId) {
            return true;
        }

        return $gig->negotiations()->where('translator_user_id', $userId)->exists();
    }
}
