<?php

namespace App\Http\Controllers\Api\V1\Home;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\ListMediaAction;
use Domain\Media\DTOs\ListMediaData;
use Domain\Media\Enums\MediaType;
use Domain\Media\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowHomeMediaController extends Controller
{
    public function __construct(
        private readonly ListMediaAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        // Lightweight "home" slice: we only need a handful of public-ready media cards.
        $media = $this->action->execute(
            auth('sanctum')->id() ?? $request->user()?->id,
            new ListMediaData(
                perPage: 6,
                search: null,
                type: null,
                sort: 'newest',
                nickname: null,
            ),
        );

        $items = collect($media->items())
            ->take(3)
            ->values()
            ->map(function ($item, int $index): array {
                /** @var MediaItem $item */
                $resolvedUrl = $item->resolvedFileUrl();

                $coverUrl = null;
                if ($item->type === MediaType::Video) {
                    $coverUrl = $item->resolvedThumbnailUrl();
                } elseif ($item->type === MediaType::Photo) {
                    $coverUrl = $resolvedUrl;
                }

                $title = trim((string) ($item->title ?? ''));
                $description = trim((string) ($item->description ?? ''));

                return [
                    'id' => $item->id,
                    'type' => $item->type?->value,
                    // The frontend gracefully ignores unknown tones; keep it stable & deterministic.
                    'tone' => ['charcoal', 'sepia', 'graphite'][$index % 3],
                    'title' => $title !== '' ? $title : __('home.community_media.no_title'),
                    'subtitle' => $description !== '' ? $description : __('home.community_media.no_description'),
                    'href' => '/media/'.$item->id,
                    'media_url' => $resolvedUrl,
                    'cover_url' => $coverUrl,
                    'is_liked' => (bool) ($item->is_liked ?? false),
                    'stats' => [
                        'views' => (int) ($item->views_count ?? 0),
                        'likes' => (int) ($item->likes_count ?? 0),
                        'comments' => (int) ($item->comments_count ?? 0),
                    ],
                ];
            })
            ->all();

        return ApiResponse::success([
            'community_media' => [
                'items' => $items,
            ],
        ]);
    }

    private function resolveLocale(Request $request): string
    {
        $raw = strtolower(trim((string) $request->query('locale', '')));
        if ($raw === '') {
            return 'it';
        }

        // Keep it strict: the API currently ships translations only for these locales.
        return in_array($raw, ['it', 'en'], true) ? $raw : 'it';
    }
}
