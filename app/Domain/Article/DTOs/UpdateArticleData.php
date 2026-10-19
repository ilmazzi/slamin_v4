<?php

namespace Domain\Article\DTOs;

use App\Http\Requests\Api\V1\Article\UpdateArticleRequest;
use Domain\Article\Enums\ArticleStatus;
use Illuminate\Http\UploadedFile;

class UpdateArticleData
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $articleCategoryId,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $excerpt,
        public readonly ?UploadedFile $coverImage,
        public readonly bool $removeCoverImage,
        public readonly string $language,
        public readonly ArticleStatus $status,
        public readonly bool $isPublic,
        public readonly ?string $publishedAt,
        public readonly array $groupIds,
        public readonly bool $syncGroupLinks,
    ) {
    }

    public static function fromRequest(UpdateArticleRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            articleCategoryId: $request->validated('article_category_id'),
            title: $request->validated('title'),
            content: $request->validated('content'),
            excerpt: $request->validated('excerpt'),
            coverImage: $request->file('cover_image'),
            removeCoverImage: (bool) $request->validated('remove_cover_image', false),
            language: $request->validated('language', 'it'),
            status: $request->validated('status')
                ? ArticleStatus::from($request->validated('status'))
                : ArticleStatus::Draft,
            isPublic: (bool) $request->validated('is_public', true),
            publishedAt: $request->validated('published_at'),
            groupIds: $request->validated('group_ids', []) ?? [],
            syncGroupLinks: $request->exists('group_ids'),
        );
    }
}
