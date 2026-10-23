<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\CastVoteRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Models\Comment;
use Domain\Engagement\Actions\CastVoteAction;
use Illuminate\Http\JsonResponse;

class VoteForumCommentController extends Controller
{
    public function __construct(
        private readonly CastVoteAction $action,
    ) {}

    public function __invoke(CastVoteRequest $request, Comment $comment): JsonResponse
    {
        $comment->load('commentable');

        $comment = $this->action->execute(
            $comment,
            $request->user()->id,
            (int) $request->validated('value'),
        );

        $comment->load([
            'user.profile',
            'votes' => fn ($votes) => $votes->where('user_id', $request->user()->id),
        ]);

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
