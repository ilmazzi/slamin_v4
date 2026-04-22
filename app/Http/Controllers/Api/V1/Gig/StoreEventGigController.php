<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreEventGigRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Gig\Actions\StoreEventGigAction;
use Domain\Gig\DTOs\StoreGigData;
use Illuminate\Http\JsonResponse;

class StoreEventGigController extends Controller
{
    public function __construct(
        private readonly StoreEventGigAction $action,
    ) {
    }

    public function __invoke(StoreEventGigRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $gig = $this->action->execute($event, StoreGigData::fromRequest($request));

        return ApiResponse::success([
            'gig' => [
                'id' => $gig->id,
                'giggable_type' => $gig->giggable_type,
                'giggable_id' => $gig->giggable_id,
                'type' => $gig->type?->value,
                'quantity' => $gig->quantity,
                'filled_quantity' => $gig->filled_quantity,
                'language' => $gig->language,
                'hospitality_required' => $gig->hospitality_required,
                'description' => $gig->description,
                'status' => $gig->status?->value,
                'opened_at' => $gig->opened_at?->toISOString(),
                'filled_at' => $gig->filled_at?->toISOString(),
                'closed_at' => $gig->closed_at?->toISOString(),
                'created_at' => $gig->created_at?->toISOString(),
                'updated_at' => $gig->updated_at?->toISOString(),
            ],
        ], status: 201);
    }
}
