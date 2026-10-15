<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\PoemTranslationGigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListPoemTranslationGigsAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class ListPoemTranslationGigsController extends Controller
{
    public function __construct(
        private readonly ListPoemTranslationGigsAction $action,
    ) {}

    public function __invoke(Poem $poem): JsonResponse
    {
        $this->authorize('update', $poem);

        return ApiResponse::success([
            'translation_gigs' => PoemTranslationGigResource::collection($this->action->execute($poem)),
        ]);
    }
}
