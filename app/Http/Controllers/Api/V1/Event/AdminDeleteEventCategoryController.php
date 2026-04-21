<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\AdminDeleteEventCategoryAction;
use Domain\Event\Models\EventCategory;
use Illuminate\Http\JsonResponse;

class AdminDeleteEventCategoryController extends Controller
{
    public function __construct(
        private readonly AdminDeleteEventCategoryAction $action,
    ) {
    }

    public function __invoke(EventCategory $eventCategory): JsonResponse
    {
        $deleted = $this->action->execute($eventCategory, request()->user()->id);

        if (! $deleted) {
            return ApiResponse::error(
                'Cannot delete a category that is already used by one or more events.',
                status: 422,
            );
        }

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
