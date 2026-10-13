<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Help\AdminHelpCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminListHelpCategoriesAction;
use Illuminate\Http\JsonResponse;

class AdminListHelpCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListHelpCategoriesAction $action,
    ) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminHelpCategoryResource::collection($this->action->execute())->resolve(),
        ]);
    }
}
