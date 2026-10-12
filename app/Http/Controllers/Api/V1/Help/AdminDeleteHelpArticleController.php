<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminDeleteHelpArticleAction;
use Domain\Help\Models\HelpArticle;
use Illuminate\Http\JsonResponse;

class AdminDeleteHelpArticleController extends Controller
{
    public function __construct(
        private readonly AdminDeleteHelpArticleAction $action,
    ) {}

    public function __invoke(HelpArticle $helpArticle): JsonResponse
    {
        $this->action->execute($helpArticle, request()->user()->id);

        return ApiResponse::success();
    }
}
