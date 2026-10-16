<?php

namespace Domain\Article\Actions;

use Domain\Admin\Actions\ResolveContentPublicationFlowAction;
use Domain\Article\DTOs\UpdateArticleData;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Domain\Shared\Support\RichTextSanitizer;
use Illuminate\Support\Facades\Storage;

class UpdateArticleAction
{
    public function __construct(
        private readonly ResolveContentPublicationFlowAction $resolveContentPublicationFlowAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    public function execute(Article $article, UpdateArticleData $data): Article
    {
        $oldValues = [
            'title' => $article->title,
            'content' => $article->content,
            'excerpt' => $article->excerpt,
            'article_category_id' => $article->article_category_id,
            'cover_image_path' => $article->cover_image_path,
            'language' => $article->language,
            'status' => $article->status?->value,
            'is_public' => $article->is_public,
            'published_at' => $article->published_at?->toISOString(),
        ];

        $status = $this->resolveTargetStatus($data->status);
        $publishedAt = $status === ArticleStatus::Published
            ? ($data->publishedAt ? now()->parse($data->publishedAt) : ($article->published_at ?? now()))
            : null;

        $coverImagePath = $article->cover_image_path;

        if ($data->removeCoverImage && $coverImagePath) {
            Storage::disk('public')->delete($coverImagePath);
            $coverImagePath = null;
        }

        if ($data->coverImage) {
            if ($coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
            }

            $coverImagePath = $data->coverImage->store('articles', 'public');
        }

        $article->update([
            'title' => $data->title,
            'content' => $this->richTextSanitizer->sanitize($data->content),
            'excerpt' => $data->excerpt,
            'article_category_id' => $data->articleCategoryId,
            'cover_image_path' => $coverImagePath,
            'language' => $data->language,
            'status' => $status,
            'is_public' => $data->isPublic,
            'published_at' => $publishedAt,
        ]);

        audit()->log(
            event: 'article.updated',
            userId: $data->userId,
            auditable: $article,
            oldValues: $oldValues,
            newValues: [
                'title' => $article->title,
                'content' => $article->content,
                'excerpt' => $article->excerpt,
                'article_category_id' => $article->article_category_id,
                'cover_image_path' => $article->cover_image_path,
                'language' => $article->language,
                'status' => $article->status?->value,
                'is_public' => $article->is_public,
                'published_at' => $article->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'article_update',
                'cover_image_removed' => $data->removeCoverImage,
                'cover_image_replaced' => $data->coverImage !== null,
            ],
        );

        return $article->fresh(['user.profile', 'category.translations']);
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
}
