<?php

namespace Domain\Article\Actions;

use Domain\Admin\Actions\ResolveContentPublicationFlowAction;
use Domain\Article\DTOs\StoreArticleData;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Support\Str;

class StoreArticleAction
{
    public function __construct(
        private readonly ResolveContentPublicationFlowAction $resolveContentPublicationFlowAction,
    ) {
    }

    public function execute(StoreArticleData $data): Article
    {
        $coverImagePath = $data->coverImage?->store('articles', 'public');
        $status = $this->resolveTargetStatus($data->status);
        $publishedAt = $status === ArticleStatus::Published
            ? ($data->publishedAt ? now()->parse($data->publishedAt) : now())
            : null;

        $article = Article::query()->create([
            'user_id' => $data->userId,
            'article_category_id' => $data->articleCategoryId,
            'title' => $data->title,
            'slug' => $this->generateUniqueSlug($data->title),
            'content' => $data->content,
            'excerpt' => $data->excerpt,
            'cover_image_path' => $coverImagePath,
            'language' => $data->language,
            'status' => $status,
            'is_public' => $data->isPublic,
            'published_at' => $publishedAt,
        ]);

        audit()->log(
            event: 'article.created',
            userId: $data->userId,
            auditable: $article,
            newValues: [
                'title' => $article->title,
                'slug' => $article->slug,
                'article_category_id' => $article->article_category_id,
                'language' => $article->language,
                'status' => $article->status?->value,
                'is_public' => $article->is_public,
                'published_at' => $article->published_at?->toISOString(),
                'cover_image_path' => $article->cover_image_path,
            ],
            metadata: [
                'source' => 'article_store',
            ],
        );

        return $article->load(['user.profile', 'category.translations']);
    }

    private function resolveTargetStatus(ArticleStatus $requestedStatus): ArticleStatus
    {
        if ($requestedStatus !== ArticleStatus::Published) {
            return $requestedStatus;
        }

        $flow = $this->resolveContentPublicationFlowAction->execute(ContentType::Article);

        return $flow === PublicationFlow::ManualReview
            ? ArticleStatus::PendingReview
            : ArticleStatus::Published;
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'article';
        $originalSlug = $slug;
        $counter = 2;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
