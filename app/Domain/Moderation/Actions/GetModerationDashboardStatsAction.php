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
use Domain\Report\Enums\ReportStatus;
use Domain\Report\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GetModerationDashboardStatsAction
{
    public function execute(): array
    {
        $decisions = ModerationDecision::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->toBase()
            ->selectRaw('moderatable_type, decision, count(*) as aggregate')
            ->groupBy('moderatable_type', 'decision')
            ->get();

        $decisionCount = fn (ModerationDecisionType $decision, ?string $type = null): int => (int) $decisions
            ->filter(fn ($row): bool => (string) $row->decision === $decision->value
                && ($type === null || $row->moderatable_type === $type))
            ->sum('aggregate');

        $poems = $this->statusCounts(new Poem);
        $articles = $this->statusCounts(new Article);
        $events = $this->statusCounts(new Event);
        $reports = $this->statusCounts(new Report);

        $poemStats = [
            'pending_review_count' => $this->count($poems, PoemStatus::PendingReview->value),
            'rejected_count' => $this->count($poems, PoemStatus::Rejected->value),
            'published_count' => $this->count($poems, PoemStatus::Published->value),
            'draft_count' => $this->count($poems, PoemStatus::Draft->value),
        ];

        $articleType = (new Article)->getMorphClass();
        $articleStats = [
            'pending_review_count' => $this->count($articles, ArticleStatus::PendingReview->value),
            'rejected_count' => $this->count($articles, ArticleStatus::Rejected->value),
            'published_count' => $this->count($articles, ArticleStatus::Published->value),
            'draft_count' => $this->count($articles, ArticleStatus::Draft->value),
            'approved_today_count' => $decisionCount(ModerationDecisionType::Approved, $articleType),
            'rejected_today_count' => $decisionCount(ModerationDecisionType::Rejected, $articleType),
        ];

        $eventType = (new Event)->getMorphClass();
        $eventStats = [
            'pending_review_count' => $this->count($events, EventStatus::PendingReview->value),
            'rejected_count' => $this->count($events, EventStatus::Rejected->value),
            'published_count' => $this->count($events, EventStatus::Published->value),
            'draft_count' => $this->count($events, EventStatus::Draft->value),
            'approved_today_count' => $decisionCount(ModerationDecisionType::Approved, $eventType),
            'rejected_today_count' => $decisionCount(ModerationDecisionType::Rejected, $eventType),
        ];

        $poemType = (new Poem)->getMorphClass();

        return [
            'pending_review_count' => $poemStats['pending_review_count'],
            'rejected_count' => $poemStats['rejected_count'],
            'published_count' => $poemStats['published_count'],
            'draft_count' => $poemStats['draft_count'],
            'approved_today_count' => $decisionCount(ModerationDecisionType::Approved),
            'rejected_today_count' => $decisionCount(ModerationDecisionType::Rejected),
            'poems' => array_merge($poemStats, [
                'approved_today_count' => $decisionCount(ModerationDecisionType::Approved, $poemType),
                'rejected_today_count' => $decisionCount(ModerationDecisionType::Rejected, $poemType),
            ]),
            'articles' => $articleStats,
            'events' => $eventStats,
            'reports' => [
                'open_count' => $this->count($reports, ReportStatus::Open->value),
                'in_review_count' => $this->count($reports, ReportStatus::InReview->value),
            ],
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private function statusCounts(Model $model): Collection
    {
        return $model->newQuery()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);
    }

    private function count(Collection $counts, string $status): int
    {
        return (int) $counts->get($status, 0);
    }
}
