<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ShowGigAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class ShowGigController extends Controller
{
    public function __construct(
        private readonly ShowGigAction $action,
    ) {
    }

    public function __invoke(Gig $gig): JsonResponse
    {
        $gig = $this->action->execute($gig);
        $parent = $gig->giggable !== null ? [
            'type' => class_basename($gig->giggable_type),
            'id' => $gig->giggable->id,
            'title' => $gig->giggable->title ?? null,
            'slug' => $gig->giggable->slug ?? null,
        ] : null;

        return ApiResponse::success([
            'gig' => [
                'id' => $gig->id,
                'type' => $gig->type?->value,
                'quantity' => $gig->quantity,
                'filled_quantity' => $gig->filled_quantity,
                'language' => $gig->language,
                'hospitality_required' => $gig->hospitality_required,
                'description' => $gig->description,
                'proposed_price' => $gig->proposed_price,
                'currency' => $gig->currency,
                'deadline' => $gig->deadline?->toISOString(),
                'special_requirements' => $gig->special_requirements,
                'status' => $gig->status?->value,
                'opened_at' => $gig->opened_at?->toISOString(),
                'filled_at' => $gig->filled_at?->toISOString(),
                'closed_at' => $gig->closed_at?->toISOString(),
                'parent' => $parent,
                'event' => $gig->giggable_type === \Domain\Event\Models\Event::class ? $parent : null,
                'poem' => $gig->giggable_type === \Domain\Poetry\Models\Poem::class ? $parent : null,
                'applications_count' => $gig->applications->count(),
            ],
        ]);
    }
}
