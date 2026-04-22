<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListPoemTranslationGigsAction;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListPoemTranslationGigsController extends Controller
{
    public function __construct(
        private readonly ListPoemTranslationGigsAction $action,
    ) {
    }

    public function __invoke(Request $request, Poem $poem): JsonResponse
    {
        $this->authorize('update', $poem);

        $gigs = $this->action->execute($poem);

        return ApiResponse::success([
            'translation_gigs' => $gigs->map(fn (Gig $gig): array => $this->mapGig($gig))->values()->all(),
        ]);
    }

    private function mapGig(Gig $gig): array
    {
        return [
            'id' => $gig->id,
            'target_language' => $gig->language,
            'proposed_price' => $gig->proposed_price,
            'currency' => $gig->currency,
            'deadline' => $gig->deadline?->toISOString(),
            'special_requirements' => $gig->special_requirements,
            'status' => $gig->status?->value,
        ];
    }
}
