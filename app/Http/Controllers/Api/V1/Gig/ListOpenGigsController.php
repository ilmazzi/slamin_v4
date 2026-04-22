<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListOpenGigsAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class ListOpenGigsController extends Controller
{
    public function __construct(
        private readonly ListOpenGigsAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $gigs = $this->action->execute();

        return ApiResponse::success([
            'gigs' => $gigs->map(fn (Gig $gig): array => $this->mapGig($gig))->values()->all(),
        ]);
    }

    private function mapGig(Gig $gig): array
    {
        $parent = null;

        if ($gig->giggable !== null) {
            $parent = [
                'type' => class_basename($gig->giggable_type),
                'id' => $gig->giggable->id,
                'title' => $gig->giggable->title ?? null,
                'slug' => $gig->giggable->slug ?? null,
            ];
        }

        return [
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
            'parent' => $parent,
            'event' => $gig->giggable_type === \Domain\Event\Models\Event::class ? $parent : null,
            'poem' => $gig->giggable_type === \Domain\Poetry\Models\Poem::class ? $parent : null,
        ];
    }
}
