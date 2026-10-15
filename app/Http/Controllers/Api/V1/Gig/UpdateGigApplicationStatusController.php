<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateGigApplicationStatusRequest;
use App\Http\Resources\Api\V1\Gig\GigApplicationResource;
use App\Http\Resources\Api\V1\Gig\GigResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdateGigApplicationStatusAction;
use Domain\Gig\DTOs\UpdateGigApplicationStatusData;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;

class UpdateGigApplicationStatusController extends Controller
{
    public function __construct(
        private readonly UpdateGigApplicationStatusAction $action,
    ) {}

    public function __invoke(
        UpdateGigApplicationStatusRequest $request,
        Gig $gig,
        GigApplication $application,
    ): JsonResponse {
        abort_unless($gig->isManagedBy((int) $request->user()->id), 403);

        if ((int) $application->gig_id !== (int) $gig->id) {
            return ApiResponse::error(
                message: __('gig.application_not_found'),
                status: 404,
            );
        }

        $application = $this->action->execute($gig, $application, UpdateGigApplicationStatusData::fromRequest($request));
        $application->unsetRelation('gig');
        $gig->load('confirmedApplications.user.profile');

        return ApiResponse::success([
            'application' => new GigApplicationResource($application),
            'gig' => new GigResource($gig),
        ]);
    }
}
