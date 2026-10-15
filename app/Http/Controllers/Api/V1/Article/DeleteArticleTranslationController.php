<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Illuminate\Http\JsonResponse;

class DeleteArticleTranslationController extends Controller
{
    public function __invoke(Article $article, string $locale): JsonResponse
    {
        $this->authorize('update', $article);

        $article->translations()->where('locale', $locale)->firstOrFail()->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
