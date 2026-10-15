<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Dashboard\Actions\ListRecentActivitiesAction;
use Domain\Profile\Actions\ShowPublicProfileAction;
use Domain\Profile\Support\ProfilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListPublicProfileActivitiesController extends Controller
{
    public function __construct(
        private readonly ShowPublicProfileAction $profiles,
        private readonly ListRecentActivitiesAction $activities,
    ) {}

    public function __invoke(Request $request, string $nickname): JsonResponse
    {
        $profile = $this->profiles->execute($nickname);

        $payload = ProfilePayload::public($profile, auth('sanctum')->user());

        if ($payload === null) {
            return ApiResponse::error(
                message: (string) __('profile.errors.not_found'),
                status: 404,
            );
        }

        $page = max(1, $request->integer('page', 1));

        return ApiResponse::success([
            'recent_activities' => $this->activities->executePublic($profile->user_id, $payload['sections'], $page),
        ]);
    }
}
