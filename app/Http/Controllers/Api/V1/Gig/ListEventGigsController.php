<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Gig\Actions\ListEventGigsAction;
use Illuminate\Http\JsonResponse;

class ListEventGigsController extends Controller
{
    public function __construct(
        private readonly ListEventGigsAction $action,
    ) {}

    public function __invoke(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        return ApiResponse::success([
            'gigs' => GigResource::collection($this->action->execute($event)),
        ]);
    }
}
