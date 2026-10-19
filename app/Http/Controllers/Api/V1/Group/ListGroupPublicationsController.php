<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupPublicationsAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ListGroupPublicationsController extends Controller
{
    public function __construct(
        private readonly ListGroupPublicationsAction $action,
    ) {}

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $publications = $this->action->execute(
            $group,
            $group->hasActiveMember((int) request()->user()?->id),
        );

        return ApiResponse::success([
            'poems' => $publications['poems']->map(fn ($poem) => [
                'id' => $poem->id,
                'slug' => $poem->slug,
                'title' => $poem->title,
                'description' => $poem->description,
                'published_at' => $poem->published_at?->toISOString(),
                'created_at' => $poem->created_at?->toISOString(),
            ])->values(),
            'articles' => $publications['articles']->map(fn ($article) => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'published_at' => $article->published_at?->toISOString(),
                'created_at' => $article->created_at?->toISOString(),
            ])->values(),
        ]);
    }
}
