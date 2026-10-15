<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Gig\Actions\ListEventConfirmedGigsAction;
use Illuminate\Http\JsonResponse;

class ListEventConfirmedGigsController extends Controller
{
    public function __construct(
        private readonly ListEventConfirmedGigsAction $action,
    ) {}

    public function __invoke(Event $event): JsonResponse
    {
        $viewerId = auth('sanctum')->id();

        return ApiResponse::success([
            'gigs' => GigResource::collection($this->action->execute($event, $viewerId === null ? null : (int) $viewerId)),
        ]);
    }
}
