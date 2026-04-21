<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\UpdatePoemRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\UpdatePoemAction;
use Domain\Poetry\DTOs\UpdatePoemData;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class UpdatePoemController extends Controller
{
    public function __construct(
        private readonly UpdatePoemAction $updatePoemAction,
    ) {
    }

    public function __invoke(UpdatePoemRequest $request, Poem $poem): JsonResponse
    {
        $this->authorize('update', $poem);

        $data = UpdatePoemData::fromRequest($request);

        $poem = $this->updatePoemAction->execute($poem, $data);

        return ApiResponse::success([
            'poem' => new PoemResource($poem),
        ]);
    }
}
