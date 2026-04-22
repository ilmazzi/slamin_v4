<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StorePoemTranslationGigRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StorePoemTranslationGigAction;
use Domain\Gig\DTOs\StorePoemTranslationGigData;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class StorePoemTranslationGigController extends Controller
{
    public function __construct(
        private readonly StorePoemTranslationGigAction $action,
    ) {
    }

    public function __invoke(StorePoemTranslationGigRequest $request, Poem $poem): JsonResponse
    {
        $this->authorize('update', $poem);

        $gig = $this->action->execute($poem, StorePoemTranslationGigData::fromRequest($request));

        return ApiResponse::success([
            'translation_gig' => [
                'id' => $gig->id,
                'poem_id' => $poem->id,
                'target_language' => $gig->language,
                'proposed_price' => $gig->proposed_price,
                'currency' => $gig->currency,
                'deadline' => $gig->deadline?->toISOString(),
                'special_requirements' => $gig->special_requirements,
                'status' => $gig->status?->value,
            ],
        ], status: 201);
    }
}
