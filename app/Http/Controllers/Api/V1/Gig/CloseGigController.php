<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\CloseGigAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloseGigController extends Controller
{
    public function __construct(
        private readonly CloseGigAction $action,
    ) {}

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        abort_unless($gig->isManagedBy((int) $request->user()->id), 403);

        return ApiResponse::success([
            'gig' => new GigResource($this->action->execute($gig)),
        ]);
    }
}
