<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupContentLinkResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\RejectGroupContentLinkAction;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupContentLink;
use Illuminate\Http\JsonResponse;

class RejectGroupContentLinkController extends Controller
{
    public function __construct(
        private readonly RejectGroupContentLinkAction $action,
    ) {}

    public function __invoke(Group $group, GroupContentLink $link): JsonResponse
    {
        abort_unless($link->group_id === $group->id, 404);
        $this->authorize('manageContentLinks', $group);

        $link = $this->action->execute($group, $link, (int) request()->user()->id);

        return ApiResponse::success([
            'link' => new GroupContentLinkResource($link),
        ]);
    }
}
