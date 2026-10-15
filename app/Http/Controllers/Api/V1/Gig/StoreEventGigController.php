<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreEventGigRequest;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Gig\Actions\StoreEventGigAction;
use Domain\Gig\DTOs\StoreGigData;
use Illuminate\Http\JsonResponse;

class StoreEventGigController extends Controller
{
    public function __construct(
        private readonly StoreEventGigAction $action,
    ) {}

    public function __invoke(StoreEventGigRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $gig = $this->action->execute($event, StoreGigData::fromRequest($request));

        return ApiResponse::success([
            'gig' => new GigResource($gig),
        ], status: 201);
    }
}
