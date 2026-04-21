<?php

namespace Domain\Moderation\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Moderation\Models\ModerationDecision;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;

class GetModerationDashboardStatsAction
{
    public function execute(): array
    {
        $today = now()->startOfDay();

        $poemStats = [
            'pending_review_count' => Poem::query()->where('status', PoemStatus::PendingReview)->count(),
            'rejected_count' => Poem::query()->where('status', PoemStatus::Rejected)->count(),
            'published_count' => Poem::query()->where('status', PoemStatus::Published)->count(),
            'draft_count' => Poem::query()->where('status', PoemStatus::Draft)->count(),
        ];

        $articleStats = [
            'pending_review_count' => Article::query()->where('status', ArticleStatus::PendingReview)->count(),
            'rejected_count' => Article::query()->where('status', ArticleStatus::Rejected)->count(),
            'published_count' => Article::query()->where('status', ArticleStatus::Published)->count(),
            'draft_count' => Article::query()->where('status', ArticleStatus::Draft)->count(),
            'approved_today_count' => ModerationDecision::query()
                ->where('moderatable_type', (new Article())->getMorphClass())
                ->where('decision', ModerationDecisionType::Approved)
                ->where('created_at', '>=', $today)
                ->count(),
            'rejected_today_count' => ModerationDecision::query()
                ->where('moderatable_type', (new Article())->getMorphClass())
                ->where('decision', ModerationDecisionType::Rejected)
                ->where('created_at', '>=', $today)
                ->count(),
        ];

        $eventStats = [
            'pending_review_count' => Event::query()->where('status', EventStatus::PendingReview)->count(),
            'rejected_count' => Event::query()->where('status', EventStatus::Rejected)->count(),
            'published_count' => Event::query()->where('status', EventStatus::Published)->count(),
            'draft_count' => Event::query()->where('status', EventStatus::Draft)->count(),
            'approved_today_count' => ModerationDecision::query()
                ->where('moderatable_type', (new Event())->getMorphClass())
                ->where('decision', ModerationDecisionType::Approved)
                ->where('created_at', '>=', $today)
                ->count(),
            'rejected_today_count' => ModerationDecision::query()
                ->where('moderatable_type', (new Event())->getMorphClass())
                ->where('decision', ModerationDecisionType::Rejected)
                ->where('created_at', '>=', $today)
                ->count(),
        ];

        return [
            'pending_review_count' => $poemStats['pending_review_count'],
            'rejected_count' => $poemStats['rejected_count'],
            'published_count' => $poemStats['published_count'],
            'draft_count' => $poemStats['draft_count'],
            'approved_today_count' => ModerationDecision::query()
                ->where('decision', ModerationDecisionType::Approved)
                ->where('created_at', '>=', $today)
                ->count(),
            'rejected_today_count' => ModerationDecision::query()
                ->where('decision', ModerationDecisionType::Rejected)
                ->where('created_at', '>=', $today)
                ->count(),
            'poems' => array_merge($poemStats, [
                'approved_today_count' => ModerationDecision::query()
                    ->where('moderatable_type', (new Poem())->getMorphClass())
                    ->where('decision', ModerationDecisionType::Approved)
                    ->where('created_at', '>=', $today)
                    ->count(),
                'rejected_today_count' => ModerationDecision::query()
                    ->where('moderatable_type', (new Poem())->getMorphClass())
                    ->where('decision', ModerationDecisionType::Rejected)
                    ->where('created_at', '>=', $today)
                    ->count(),
            ]),
            'articles' => $articleStats,
            'events' => $eventStats,
        ];
    }
}
