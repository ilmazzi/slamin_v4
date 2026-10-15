<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContentTranslation\UpsertContentTranslationRequest;
use App\Http\Resources\Api\V1\ContentTranslation\ContentTranslationResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\ContentTranslation\Actions\UpsertContentTranslationAction;
use Illuminate\Http\JsonResponse;

class UpsertArticleTranslationController extends Controller
{
    public function __construct(
        private readonly UpsertContentTranslationAction $action,
    ) {}

    public function __invoke(UpsertContentTranslationRequest $request, Article $article, string $locale): JsonResponse
    {
        $this->authorize('update', $article);

        $translation = $this->action->execute($article, $locale, $request->validated(), (int) $request->user()->id);

        return ApiResponse::success([
            'translation' => new ContentTranslationResource($translation),
        ]);
    }
}
