<?php

namespace App\Http\Controllers\Api\V1\Home;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\I18n\Actions\ResolveDefaultLocaleAction;
use Domain\I18n\Actions\ResolveSupportedLocalesAction;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\User\Enums\AccountType;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowHomeStatsController extends Controller
{
    public function __construct(
        private readonly ResolveSupportedLocalesAction $supportedLocales,
        private readonly ResolveDefaultLocaleAction $defaultLocale,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $poetsCount = User::query()
            ->active()
            ->where('account_type', AccountType::PoetOrganizer)
            ->count();

        $eventsCount = Event::query()
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->count();

        $videosCount = MediaItem::query()
            ->where('type', MediaType::Video)
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->count();

        $viewsCount = (int) (
            (int) Poem::query()
                ->where('status', PoemStatus::Published)
                ->where('is_public', true)
                ->sum('views_count')
            + (int) Article::query()
                ->where('status', ArticleStatus::Published)
                ->where('is_public', true)
                ->sum('views_count')
            + (int) Event::query()
                ->where('status', EventStatus::Published)
                ->where('is_public', true)
                ->sum('views_count')
            + (int) MediaItem::query()
                ->where('status', MediaStatus::Ready)
                ->where('visibility', MediaVisibility::Public)
                ->sum('views_count')
        );

        return ApiResponse::success([
            'community_stats' => [
                'title' => __('home.stats.title'),
                'subtitle' => __('home.stats.subtitle'),
                'cards' => [
                    [
                        'id' => 'views',
                        'value' => $viewsCount,
                        'label' => __('home.stats.views'),
                    ],
                    [
                        'id' => 'poets',
                        'value' => (int) $poetsCount,
                        'label' => __('home.stats.poets'),
                    ],
                    [
                        'id' => 'events',
                        'value' => (int) $eventsCount,
                        'label' => __('home.stats.events'),
                    ],
                    [
                        'id' => 'videos',
                        'value' => (int) $videosCount,
                        'label' => __('home.stats.videos'),
                    ],
                ],
            ],
        ]);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = $this->supportedLocales->execute();
        $default = $this->defaultLocale->execute();
        $raw = strtolower(trim((string) $request->query('locale', '')));

        if ($raw !== '' && in_array($raw, $supported, true)) {
            return $raw;
        }

        $current = app()->getLocale();
        if (in_array($current, $supported, true)) {
            return $current;
        }

        return $default;
    }
}
