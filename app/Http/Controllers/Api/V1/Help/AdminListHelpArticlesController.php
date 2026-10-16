<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Help\ListHelpRequest;
use App\Http\Resources\Api\V1\Help\AdminHelpArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminListHelpArticlesAction;
use Illuminate\Http\JsonResponse;

class AdminListHelpArticlesController extends Controller
{
    public function __construct(
        private readonly AdminListHelpArticlesAction $action,
    ) {}

    public function __invoke(ListHelpRequest $request): JsonResponse
    {
        return ApiResponse::success([
            'articles' => AdminHelpArticleResource::collection(
                $this->action->execute($request->validated('section', 'help'))
            )->resolve(),
        ]);
    }
}
