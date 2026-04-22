<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\DeletePoemTranslationGigAction;
use Domain\Gig\Enums\GigType;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletePoemTranslationGigController extends Controller
{
    public function __construct(
        private readonly DeletePoemTranslationGigAction $action,
    ) {
    }

    public function __invoke(Request $request, Poem $poem, Gig $gig): JsonResponse
    {
        $this->authorize('update', $poem);

        if (! $this->belongsToPoem($gig, $poem)) {
            return ApiResponse::error(__('gig.not_found'), status: 404);
        }

        $this->action->execute($gig);

        return ApiResponse::success(['deleted' => true]);
    }

    private function belongsToPoem(Gig $gig, Poem $poem): bool
    {
        return $gig->giggable_type === Poem::class
            && (int) $gig->giggable_id === (int) $poem->id
            && $gig->type === GigType::Translator;
    }
}
