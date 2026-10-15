<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContentTranslation\ContentTranslationResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class ShowPoemTranslationController extends Controller
{
    public function __invoke(string $slug, string $locale): JsonResponse
    {
        $poem = Poem::query()
            ->select(['id'])
            ->where('slug', $slug)
            ->visibleTo(auth('sanctum')->id())
            ->firstOrFail();

        return ApiResponse::success([
            'translation' => new ContentTranslationResource(
                $poem->translations()->where('locale', $locale)->firstOrFail(),
            ),
        ]);
    }
}
