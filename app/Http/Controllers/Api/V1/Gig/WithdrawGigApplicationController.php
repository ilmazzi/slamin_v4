<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigApplicationResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\WithdrawGigApplicationAction;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawGigApplicationController extends Controller
{
    public function __construct(
        private readonly WithdrawGigApplicationAction $action,
    ) {}

    public function __invoke(Request $request, Gig $gig, GigApplication $application): JsonResponse
    {
        abort_unless((int) $application->user_id === (int) $request->user()->id, 403);

        if ((int) $application->gig_id !== (int) $gig->id) {
            return ApiResponse::error(
                message: __('gig.application_not_found'),
                status: 404,
            );
        }

        $application = $this->action->execute($gig, $application);
        $application->unsetRelation('gig');

        return ApiResponse::success([
            'application' => new GigApplicationResource($application),
        ]);
    }
}
