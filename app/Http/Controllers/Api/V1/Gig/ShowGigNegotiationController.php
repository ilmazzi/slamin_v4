<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigNegotiationResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ShowGigNegotiationAction;
use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowGigNegotiationController extends Controller
{
    public function __construct(
        private readonly ShowGigNegotiationAction $action,
    ) {}

    public function __invoke(Request $request, GigNegotiation $negotiation): JsonResponse
    {
        abort_unless($negotiation->involves((int) $request->user()->id), 403);

        return ApiResponse::success([
            'negotiation' => new GigNegotiationResource($this->action->execute($negotiation)),
        ]);
    }
}
