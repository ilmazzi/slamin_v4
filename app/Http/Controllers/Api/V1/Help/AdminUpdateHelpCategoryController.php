<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Help\UpdateHelpCategoryRequest;
use App\Http\Resources\Api\V1\Help\AdminHelpCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminUpdateHelpCategoryAction;
use Domain\Help\Models\HelpCategory;
use Illuminate\Http\JsonResponse;

class AdminUpdateHelpCategoryController extends Controller
{
    public function __construct(
        private readonly AdminUpdateHelpCategoryAction $action,
    ) {}

    public function __invoke(UpdateHelpCategoryRequest $request, HelpCategory $helpCategory): JsonResponse
    {
        $category = $this->action->execute($helpCategory, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminHelpCategoryResource($category),
        ]);
    }
}
