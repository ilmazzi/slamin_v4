<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\UnlikePoemAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnlikePoemController extends Controller
{
    public function __construct(
        private readonly UnlikePoemAction $unlikePoemAction,
    ) {
    }

    public function __invoke(Request $request, Poem $poem): JsonResponse
    {
        $this->authorize('like', $poem);

        $this->unlikePoemAction->execute($poem, $request->user()->id);

        return ApiResponse::success([
            'poem' => new PoemResource($poem->fresh(['user.profile'])),
        ]);
    }
}
