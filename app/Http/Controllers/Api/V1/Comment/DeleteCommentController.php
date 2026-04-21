<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\DeleteCommentAction;
use Domain\Comment\Models\Comment;
use Illuminate\Http\JsonResponse;

class DeleteCommentController extends Controller
{
    public function __construct(
        private readonly DeleteCommentAction $action,
    ) {
    }

    public function __invoke(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $this->action->execute($comment, request()->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
