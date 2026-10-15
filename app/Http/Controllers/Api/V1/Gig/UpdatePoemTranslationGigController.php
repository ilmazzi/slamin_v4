<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdatePoemTranslationGigRequest;
use App\Http\Resources\Api\V1\Gig\PoemTranslationGigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdatePoemTranslationGigAction;
use Domain\Gig\DTOs\UpdatePoemTranslationGigData;
use Domain\Gig\Enums\GigType;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class UpdatePoemTranslationGigController extends Controller
{
    public function __construct(
        private readonly UpdatePoemTranslationGigAction $action,
    ) {}

    public function __invoke(UpdatePoemTranslationGigRequest $request, Poem $poem, Gig $gig): JsonResponse
    {
        $this->authorize('update', $poem);

        if (! $this->belongsToPoem($gig, $poem)) {
            return ApiResponse::error(__('gig.not_found'), status: 404);
        }

        $gig = $this->action->execute($gig, UpdatePoemTranslationGigData::fromRequest($request));

        return ApiResponse::success([
            'translation_gig' => new PoemTranslationGigResource($gig),
        ]);
    }

    private function belongsToPoem(Gig $gig, Poem $poem): bool
    {
        return $gig->giggable_type === Poem::class
            && (int) $gig->giggable_id === (int) $poem->id
            && $gig->type === GigType::Translator;
    }
}
