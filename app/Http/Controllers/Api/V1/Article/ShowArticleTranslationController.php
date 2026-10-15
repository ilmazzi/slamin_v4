<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContentTranslation\ContentTranslationResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Illuminate\Http\JsonResponse;

class ShowArticleTranslationController extends Controller
{
    public function __invoke(string $slug, string $locale): JsonResponse
    {
        $article = Article::query()
            ->select(['id'])
            ->where('slug', $slug)
            ->visibleTo(auth('sanctum')->id())
            ->firstOrFail();

        return ApiResponse::success([
            'translation' => new ContentTranslationResource(
                $article->translations()->where('locale', $locale)->firstOrFail(),
            ),
        ]);
    }
}
