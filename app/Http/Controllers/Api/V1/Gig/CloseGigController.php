<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\CloseGigAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloseGigController extends Controller
{
    public function __construct(
        private readonly CloseGigAction $action,
    ) {
    }

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        abort_unless($this->canManage($request->user()->id, $gig), 403);

        $gig = $this->action->execute($gig);

        return ApiResponse::success([
            'gig' => [
                'id' => $gig->id,
                'status' => $gig->status?->value,
                'closed_at' => $gig->closed_at?->toISOString(),
                'filled_quantity' => $gig->filled_quantity,
            ],
        ]);
    }

    private function canManage(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        return $parent !== null && (int) ($parent->user_id ?? 0) === $userId;
    }
}
