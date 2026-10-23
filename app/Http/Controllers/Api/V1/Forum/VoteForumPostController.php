<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\CastVoteRequest;
use App\Http\Resources\Api\V1\Forum\ForumPostResource;
use App\Support\Responses\ApiResponse;
use Domain\Engagement\Actions\CastVoteAction;
use Domain\Forum\Models\ForumPost;
use Illuminate\Http\JsonResponse;

class VoteForumPostController extends Controller
{
    public function __construct(
        private readonly CastVoteAction $action,
    ) {}

    public function __invoke(CastVoteRequest $request, ForumPost $forumPost): JsonResponse
    {
        $this->authorize('vote', $forumPost);

        $post = $this->action->execute(
            $forumPost->load('category'),
            $request->user()->id,
            (int) $request->validated('value'),
        );

        $post->load(['user.profile', 'category.translations', 'votes' => fn ($votes) => $votes->where('user_id', $request->user()->id)]);

        return ApiResponse::success([
            'post' => new ForumPostResource($post),
        ]);
    }
}
