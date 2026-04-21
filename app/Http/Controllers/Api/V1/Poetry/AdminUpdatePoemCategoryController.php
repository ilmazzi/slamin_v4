<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\UpdatePoemCategoryRequest;
use App\Http\Resources\Api\V1\Poetry\AdminPoemCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminUpdatePoemCategoryAction;
use Domain\Poetry\Models\PoemCategory;
use Illuminate\Http\JsonResponse;

class AdminUpdatePoemCategoryController extends Controller
{
    public function __construct(
        private readonly AdminUpdatePoemCategoryAction $action,
    ) {
    }

    public function __invoke(UpdatePoemCategoryRequest $request, PoemCategory $poemCategory): JsonResponse
    {
        $category = $this->action->execute($poemCategory, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminPoemCategoryResource($category),
        ]);
    }
}
