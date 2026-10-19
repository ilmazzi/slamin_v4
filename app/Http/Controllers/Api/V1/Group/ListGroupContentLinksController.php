<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupContentLinkResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupContentLinksAction;
use Domain\Group\Enums\GroupContentLinkStatus;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListGroupContentLinksController extends Controller
{
    public function __construct(
        private readonly ListGroupContentLinksAction $action,
    ) {}

    public function __invoke(Request $request, Group $group): JsonResponse
    {
        $this->authorize('manageContentLinks', $group);

        $status = $request->filled('status')
            ? GroupContentLinkStatus::tryFrom((string) $request->query('status'))
            : GroupContentLinkStatus::Pending;

        $links = $this->action->execute($group, $status);

        return ApiResponse::success([
            'links' => GroupContentLinkResource::collection($links),
        ]);
    }
}
