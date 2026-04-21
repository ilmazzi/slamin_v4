<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\PoemCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\ListPoemCategoriesAction;
use Illuminate\Http\JsonResponse;

class ListPoemCategoriesController extends Controller
{
    public function __construct(
        private readonly ListPoemCategoriesAction $listPoemCategoriesAction,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $categories = $this->listPoemCategoriesAction->execute();

        return ApiResponse::success([
            'categories' => PoemCategoryResource::collection($categories),
        ]);
    }
}
