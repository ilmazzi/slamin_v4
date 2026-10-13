<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Help\ListHelpRequest;
use App\Http\Resources\Api\V1\Help\HelpArticleResource;
use App\Http\Resources\Api\V1\Help\HelpCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\ListHelpAction;
use Illuminate\Http\JsonResponse;

class ListHelpController extends Controller
{
    public function __construct(
        private readonly ListHelpAction $action,
    ) {}

    public function __invoke(ListHelpRequest $request): JsonResponse
    {
        $result = $this->action->execute(
            $request->validated('q'),
            $request->validated('category'),
        );

        return ApiResponse::success([
            'categories' => HelpCategoryResource::collection($result['categories'])->resolve(),
            'articles' => HelpArticleResource::collection($result['articles'])->resolve(),
        ]);
    }
}
