<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Search\Actions\SearchCatalogAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowSearchController extends Controller
{
    public function __construct(
        private readonly SearchCatalogAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        return ApiResponse::success(
            $this->action->execute($validated['q'], auth('sanctum')->user()),
        );
    }
}
