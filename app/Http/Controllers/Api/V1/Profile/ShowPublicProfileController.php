<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Engagement\Actions\GetFollowStateAction;
use Domain\Profile\Actions\GetPublicProfileStatsAction;
use Domain\Profile\Actions\ShowPublicProfileAction;
use Domain\Profile\Support\ProfilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowPublicProfileController extends Controller
{
    public function __construct(
        private readonly ShowPublicProfileAction $action,
        private readonly GetPublicProfileStatsAction $stats,
        private readonly GetFollowStateAction $followState,
    ) {}

    public function __invoke(Request $request, string $nickname): JsonResponse
    {
        $profile = $this->action->execute($nickname);
        $payload = ProfilePayload::public($profile, auth('sanctum')->user());

        if ($payload === null) {
            return ApiResponse::error(
                message: (string) __('profile.errors.not_found'),
                status: 404,
            );
        }

        return ApiResponse::success([
            'profile' => $payload,
            'stats' => $this->stats->execute($profile->user_id, $payload['sections']),
            'follow' => $this->followState->execute($profile->user, auth('sanctum')->id()),
        ]);
    }
}
