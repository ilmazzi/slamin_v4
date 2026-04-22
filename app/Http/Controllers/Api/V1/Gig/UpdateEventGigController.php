<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateEventGigRequest;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Gig\Actions\UpdateEventGigAction;
use Domain\Gig\DTOs\UpdateGigData;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class UpdateEventGigController extends Controller
{
    public function __construct(
        private readonly UpdateEventGigAction $action,
    ) {
    }

    public function __invoke(UpdateEventGigRequest $request, Event $event, Gig $gig): JsonResponse
    {
        $this->authorize('update', $event);

        if (! $this->belongsToEvent($gig, $event)) {
            return ApiResponse::error(
                message: __('gig.not_found'),
                status: 404,
            );
        }

        $gig = $this->action->execute($gig, UpdateGigData::fromRequest($request));

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
        ]);
    }

    private function belongsToEvent(Gig $gig, Event $event): bool
    {
        return $gig->giggable_type === Event::class && (int) $gig->giggable_id === (int) $event->id;
    }
}
