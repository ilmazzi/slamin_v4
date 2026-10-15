<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContentTranslation\UpsertContentTranslationRequest;
use App\Http\Resources\Api\V1\ContentTranslation\ContentTranslationResource;
use App\Support\Responses\ApiResponse;
use Domain\ContentTranslation\Actions\UpsertContentTranslationAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class UpsertPoemTranslationController extends Controller
{
    public function __construct(
        private readonly UpsertContentTranslationAction $action,
    ) {}

    public function __invoke(UpsertContentTranslationRequest $request, Poem $poem, string $locale): JsonResponse
    {
        $this->authorize('update', $poem);

        $translation = $this->action->execute($poem, $locale, $request->validated(), (int) $request->user()->id);

        return ApiResponse::success([
            'translation' => new ContentTranslationResource($translation),
        ]);
    }
}
