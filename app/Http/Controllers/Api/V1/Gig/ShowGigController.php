<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ShowGigAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowGigController extends Controller
{
    public function __construct(
        private readonly ShowGigAction $action,
    ) {}

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        $gig = $this->action->execute($gig, (int) $request->user()->id);

        if ($gig === null) {
            return ApiResponse::error(__('gig.not_available'), status: 404);
        }

        return ApiResponse::success([
            'gig' => new GigResource($gig),
        ]);
    }
}
