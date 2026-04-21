<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\AdminPoemCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminListPoemCategoriesAction;
use Illuminate\Http\JsonResponse;

class AdminListPoemCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListPoemCategoriesAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminPoemCategoryResource::collection($this->action->execute()),
        ]);
    }
}
