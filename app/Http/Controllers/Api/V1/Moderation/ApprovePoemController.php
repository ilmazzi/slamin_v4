<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ApprovePoemRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ApprovePoemAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class ApprovePoemController extends Controller
{
    public function __construct(
        private readonly ApprovePoemAction $action,
    ) {
    }

    public function __invoke(ApprovePoemRequest $request, Poem $poem): JsonResponse
    {
        $poem = $this->action->execute(
            $poem,
            $request->user()->id,
            $request->validated('note'),
        );

        return ApiResponse::success([
            'poem' => new PoemResource($poem),
        ]);
    }
}
