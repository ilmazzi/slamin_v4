<?php

namespace Domain\Article\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Share\Support\GenericShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\SharePage;

class RenderArticleShareCardAction
{
    public function __construct(
        private readonly GenericShareCardRenderer $renderer,
    ) {}

    public function execute(string $slug, ShareCardFormat $format = ShareCardFormat::Square): string
    {
        $article = Article::query()
            ->with('user.profile')
            ->where('slug', $slug)
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $cacheKey = sprintf(
            'article-share-card:v2:%d:%s:%s:%s',
            $article->id,
            $article->updated_at?->timestamp ?? 0,
            $article->user?->profile?->updated_at?->timestamp ?? 0,
            $format->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => $article->title ?: __('articles.all.no_title'),
            'subtitle' => SharePage::excerpt($article->excerpt ?: $article->content),
            'author' => $article->user?->profile?->display_name,
            'image_path' => $article->cover_image_path,
            'background' => [101, 116, 222],
        ], $format));
    }
}
