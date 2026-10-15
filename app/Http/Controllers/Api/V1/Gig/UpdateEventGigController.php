<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateEventGigRequest;
use App\Http\Resources\Api\V1\Gig\GigResource;
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
    ) {}

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
            'gig' => new GigResource($gig),
        ]);
    }

    private function belongsToEvent(Gig $gig, Event $event): bool
    {
        return $gig->giggable_type === Event::class && (int) $gig->giggable_id === (int) $event->id;
    }
}
