<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\ContentTranslation\Actions\LoadContentTranslationsAction;
use Domain\Poetry\Actions\ShowPoemAction;
use Domain\Poetry\Enums\PoemStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowPoemController extends Controller
{
    public function __construct(
        private readonly ShowPoemAction $showPoemAction,
        private readonly LoadContentTranslationsAction $loadTranslations,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $authUserId = auth('sanctum')->id();

        $poem = $this->showPoemAction->execute(
            slug: $slug,
            authUserId: $authUserId,
        );

        if ($poem->status === PoemStatus::Published && $poem->is_public && $poem->user_id !== $authUserId) {
            $poem->increment('views_count');
        }

        $this->loadTranslations->execute($poem, app()->getLocale());

        return ApiResponse::success([
            'poem' => new PoemResource($poem),
        ]);
    }
}
