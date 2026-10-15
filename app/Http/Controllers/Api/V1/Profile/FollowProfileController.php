<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Engagement\Actions\FollowAction;
use Domain\Profile\Actions\ShowPublicProfileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowProfileController extends Controller
{
    public function __construct(
        private readonly ShowPublicProfileAction $profiles,
        private readonly FollowAction $followAction,
    ) {}

    public function __invoke(Request $request, string $nickname): JsonResponse
    {
        $profile = $this->profiles->execute($nickname);

        if (! $profile?->user || ! $profile->is_public) {
            return ApiResponse::error(message: (string) __('profile.errors.not_found'), status: 404);
        }

        if ($profile->user_id === $request->user()->id) {
            return ApiResponse::error(message: (string) __('profile.errors.cannot_follow_self'), status: 422);
        }

        return ApiResponse::success([
            'follow' => $this->followAction->execute($profile->user, $request->user()->id),
        ]);
    }
}
