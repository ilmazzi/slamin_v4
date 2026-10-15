<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigNegotiationResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListGigNegotiationsAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListGigNegotiationsController extends Controller
{
    public function __construct(
        private readonly ListGigNegotiationsAction $action,
    ) {}

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        $userId = (int) $request->user()->id;

        abort_unless(
            $gig->isManagedBy($userId) || $gig->negotiations()->where('translator_user_id', $userId)->exists(),
            403,
        );

        return ApiResponse::success([
            'negotiations' => GigNegotiationResource::collection($this->action->execute($gig, $userId)),
        ]);
    }
}
