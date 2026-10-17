<?php

namespace Domain\Article\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Share\Support\GenericShareCardRenderer;
use Domain\Share\Support\ShareCardCache;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardPalette;
use Domain\Share\Support\ShareCardTemplate;
use Domain\Share\Support\SharePage;

class RenderArticleShareCardAction
{
    public function __construct(
        private readonly GenericShareCardRenderer $renderer,
    ) {}

    public function execute(
        string $slug,
        ShareCardFormat $format = ShareCardFormat::Square,
        ShareCardTemplate $template = ShareCardTemplate::Poster,
    ): string {
        $article = Article::query()
            ->with('user.profile')
            ->where('slug', $slug)
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        if ($template->usesImage() && ! $article->cover_image_path) {
            $template = ShareCardTemplate::Typographic;
        }

        $cacheKey = sprintf(
            'article-share-card:v3:%d:%s:%s:%s:%s',
            $article->id,
            $article->updated_at?->timestamp ?? 0,
            $article->user?->profile?->updated_at?->timestamp ?? 0,
            $format->value,
            $template->value,
        );

        return ShareCardCache::remember($cacheKey, fn () => $this->renderer->render([
            'title' => $article->title ?: __('articles.all.no_title'),
            'subtitle' => SharePage::excerpt($article->excerpt ?: $article->content),
            'author' => $article->user?->profile?->display_name,
            'image_path' => $article->cover_image_path,
            'background' => ShareCardPalette::for('article'),
        ], $format, $template));
    }
}
