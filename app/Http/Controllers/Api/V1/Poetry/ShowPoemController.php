<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\ShowPoemAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowPoemController extends Controller
{
    public function __construct(
        private readonly ShowPoemAction $showPoemAction,
    ) {
    }

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $poem = $this->showPoemAction->execute(
            slug: $slug,
            authUserId: $request->user()?->id,
        );

        return ApiResponse::success([
            'poem' => new PoemResource($poem),
        ]);
    }
}
