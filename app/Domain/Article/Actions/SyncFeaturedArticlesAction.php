<?php

namespace Domain\Article\Actions;

use Domain\Article\DTOs\SyncFeaturedArticlesData;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncFeaturedArticlesAction
{
    public function __construct(
        private readonly ListFeaturedArticlesForAdminAction $listFeaturedArticles,
    ) {}

    public function execute(SyncFeaturedArticlesData $data, int $userId): Collection
    {
        $articleIds = array_values(array_unique($data->articleIds));

        return DB::transaction(function () use ($articleIds, $userId) {
            $articles = Article::query()
                ->whereIn('id', $articleIds)
                ->where('status', ArticleStatus::Published)
                ->where('is_public', true)
                ->get()
                ->keyBy('id');

            if ($articles->count() !== count($articleIds)) {
                throw ValidationException::withMessages([
                    'article_ids' => [__('admin.featured_articles.invalid')],
                ]);
            }

            Article::query()
                ->where('is_featured', true)
                ->update([
                    'is_featured' => false,
                    'featured_rank' => 0,
                ]);

            foreach ($articleIds as $index => $articleId) {
                Article::query()->whereKey($articleId)->update([
                    'is_featured' => true,
                    'featured_rank' => $index + 1,
                ]);
            }

            audit()->log(
                event: 'articles.featured_synced',
                userId: $userId,
                auditable: null,
                newValues: [
                    'article_ids' => $articleIds,
                ],
                metadata: [
                    'source' => 'admin_featured_articles_sync',
                ],
            );

            return $this->listFeaturedArticles->execute();
        });
    }
}
