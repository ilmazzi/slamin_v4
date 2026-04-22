<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Models\Event;
use Domain\Gig\Actions\DeleteEventGigAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class DeleteEventGigController extends Controller
{
    public function __construct(
        private readonly DeleteEventGigAction $action,
    ) {
    }

    public function __invoke(Event $event, Gig $gig): JsonResponse
    {
        $this->authorize('update', $event);

        if (! $this->belongsToEvent($gig, $event)) {
            return ApiResponse::error(
                message: __('gig.not_found'),
                status: 404,
            );
        }

        $this->action->execute($gig);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }

    private function belongsToEvent(Gig $gig, Event $event): bool
    {
        return $gig->giggable_type === Event::class && (int) $gig->giggable_id === (int) $event->id;
    }
}
