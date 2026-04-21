<?php

use Domain\Article\Models\Article;
use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Moderation\Models\ModerationDecision;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function articleModerationPanelUser(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'moderator'],
        ['label' => 'Moderator']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function articleModerationAuthor(): User
{
    return User::factory()->poetOrganizer()->create();
}

function makeArticleForPanel(string $title, string $slug, string $status, int $userId): Article
{
    return Article::query()->create([
        'user_id' => $userId,
        'title' => $title,
        'slug' => $slug,
        'content' => 'Body',
        'language' => 'it',
        'status' => $status,
        'is_public' => true,
        'published_at' => $status === 'published' ? now() : null,
    ]);
}

it('returns moderation dashboard stats for articles too', function () {
    Sanctum::actingAs(articleModerationPanelUser());

    $author = articleModerationAuthor();
    makeArticleForPanel('Pending article', 'pending-article-panel', 'pending_review', $author->id);
    makeArticleForPanel('Rejected article', 'rejected-article-panel', 'rejected', $author->id);
    makeArticleForPanel('Published article', 'published-article-panel', 'published', $author->id);
    makeArticleForPanel('Draft article', 'draft-article-panel', 'draft', $author->id);

    $article = makeArticleForPanel('Approved source article', 'approved-source-article', 'published', $author->id);
    ModerationDecision::query()->create([
        'moderator_user_id' => articleModerationPanelUser()->id,
        'moderatable_type' => $article->getMorphClass(),
        'moderatable_id' => $article->id,
        'decision' => ModerationDecisionType::Approved,
        'note' => 'Approved today',
    ]);

    $response = $this->getJson('/api/v1/moderation/dashboard');

    $response
        ->assertOk()
        ->assertJsonPath('data.stats.articles.pending_review_count', 1)
        ->assertJsonPath('data.stats.articles.rejected_count', 1)
        ->assertJsonPath('data.stats.articles.published_count', 2)
        ->assertJsonPath('data.stats.articles.draft_count', 1)
        ->assertJsonPath('data.stats.articles.approved_today_count', 1);
});

it('lists moderation articles with filters', function () {
    Sanctum::actingAs(articleModerationPanelUser());

    $author = articleModerationAuthor();
    makeArticleForPanel('Alpha moderation article', 'alpha-moderation-article', 'pending_review', $author->id);
    makeArticleForPanel('Beta moderation article', 'beta-moderation-article', 'rejected', $author->id);

    $response = $this->getJson('/api/v1/moderation/articles?status=pending_review&q=Alpha');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'alpha-moderation-article')
        ->assertJsonPath('data.0.status', 'pending_review');
});

it('shows a moderation article with its moderation history', function () {
    Sanctum::actingAs(articleModerationPanelUser());

    $author = articleModerationAuthor();
    $moderator = articleModerationPanelUser();
    $article = makeArticleForPanel('History article', 'history-article', 'rejected', $author->id);

    ModerationDecision::query()->create([
        'moderator_user_id' => $moderator->id,
        'moderatable_type' => $article->getMorphClass(),
        'moderatable_id' => $article->id,
        'decision' => ModerationDecisionType::Rejected,
        'note' => 'Needs revision',
    ]);

    $response = $this->getJson("/api/v1/moderation/articles/{$article->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.article.slug', 'history-article')
        ->assertJsonPath('data.article.status', 'rejected')
        ->assertJsonPath('data.moderation_history.0.decision', 'rejected')
        ->assertJsonPath('data.moderation_history.0.note', 'Needs revision');
});
