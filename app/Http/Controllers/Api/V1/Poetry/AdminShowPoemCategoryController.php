<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\AdminPoemCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Models\PoemCategory;
use Illuminate\Http\JsonResponse;

class AdminShowPoemCategoryController extends Controller
{
    public function __invoke(PoemCategory $poemCategory): JsonResponse
    {
        return ApiResponse::success([
            'category' => new AdminPoemCategoryResource($poemCategory->load('translations')),
        ]);
    }
}
