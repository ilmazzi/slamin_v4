<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class DeletePoemTranslationController extends Controller
{
    public function __invoke(Poem $poem, string $locale): JsonResponse
    {
        $this->authorize('update', $poem);

        $poem->translations()->where('locale', $locale)->firstOrFail()->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
