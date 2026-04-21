<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\RejectPoemRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\RejectPoemAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class RejectPoemController extends Controller
{
    public function __construct(
        private readonly RejectPoemAction $action,
    ) {
    }

    public function __invoke(RejectPoemRequest $request, Poem $poem): JsonResponse
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
