<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\DeletePoemAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletePoemController extends Controller
{
    public function __construct(
        private readonly DeletePoemAction $deletePoemAction,
    ) {
    }

    public function __invoke(Request $request, Poem $poem): JsonResponse
    {
        $this->authorize('delete', $poem);

        $this->deletePoemAction->execute($poem, $request->user()->id);

        return ApiResponse::success();
    }
}
