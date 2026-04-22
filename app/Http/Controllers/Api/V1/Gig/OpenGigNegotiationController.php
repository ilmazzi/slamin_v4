<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\OpenGigNegotiationRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\OpenGigNegotiationAction;
use Domain\Gig\DTOs\OpenGigNegotiationData;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenGigNegotiationController extends Controller
{
    public function __construct(
        private readonly OpenGigNegotiationAction $action,
    ) {
    }

    public function __invoke(OpenGigNegotiationRequest $request, Gig $gig, GigApplication $application): JsonResponse
    {
        abort_unless($this->canManage($request->user()->id, $gig), 403);

        $negotiation = $this->action->execute($gig, $application, OpenGigNegotiationData::fromRequest($request));

        return ApiResponse::success([
            'negotiation' => [
                'id' => $negotiation->id,
                'gig_id' => $negotiation->gig_id,
                'gig_application_id' => $negotiation->gig_application_id,
                'status' => $negotiation->status?->value,
                'translator_user_id' => $negotiation->translator_user_id,
                'events_count' => $negotiation->events->count(),
            ],
        ], status: 201);
    }

    private function canManage(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        return $parent !== null && (int) ($parent->user_id ?? 0) === $userId;
    }
}
