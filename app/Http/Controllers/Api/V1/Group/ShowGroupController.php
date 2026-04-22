<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ShowGroupAction;
use Illuminate\Http\JsonResponse;

class ShowGroupController extends Controller
{
    public function __construct(
        private readonly ShowGroupAction $action,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $group = $this->action->execute($slug, request()->user()?->id);

        return ApiResponse::success([
            'group' => new GroupResource($group),
        ]);
    }
}
