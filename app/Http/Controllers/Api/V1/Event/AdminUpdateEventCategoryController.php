<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\UpdateEventCategoryRequest;
use App\Http\Resources\Api\V1\Event\AdminEventCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\AdminUpdateEventCategoryAction;
use Domain\Event\Models\EventCategory;
use Illuminate\Http\JsonResponse;

class AdminUpdateEventCategoryController extends Controller
{
    public function __construct(
        private readonly AdminUpdateEventCategoryAction $action,
    ) {
    }

    public function __invoke(UpdateEventCategoryRequest $request, EventCategory $eventCategory): JsonResponse
    {
        $category = $this->action->execute($eventCategory, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminEventCategoryResource($category),
        ]);
    }
}
