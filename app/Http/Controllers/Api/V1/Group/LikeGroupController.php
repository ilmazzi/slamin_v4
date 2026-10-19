<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\LikeGroupAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeGroupController extends Controller
{
    public function __construct(
        private readonly LikeGroupAction $likeGroupAction,
    ) {}

    public function __invoke(Request $request, Group $group): JsonResponse
    {
        $this->authorize('like', $group);

        $this->likeGroupAction->execute($group, $request->user()->id);

        return ApiResponse::success([
            'group' => new GroupResource($this->freshGroup($group, $request->user()->id)),
        ]);
    }

    private function freshGroup(Group $group, int $userId): Group
    {
        return $group->fresh([
            'category.translations',
            'members' => fn ($query) => $query->where('user_id', $userId),
        ])->loadEngagement($userId);
    }
}
