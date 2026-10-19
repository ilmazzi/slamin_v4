<?php

namespace Domain\Report\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Comment\Models\Comment;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Group\Models\Group;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Report\Enums\ReportStatus;
use Domain\Report\Models\Report;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreReportAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Model $reportable, int $reporterUserId, string $reason, ?string $details = null): Report
    {
        $this->ensureReportableIsVisible($reportable);
        $this->ensureNotSelfReport($reportable, $reporterUserId);
        $this->ensureNoOpenDuplicate($reportable, $reporterUserId);

        $report = Report::query()->create([
            'reporter_user_id' => $reporterUserId,
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason' => $reason,
            'details' => $details,
            'status' => ReportStatus::Open,
        ]);

        audit()->log(
            event: 'report.created',
            userId: $reporterUserId,
            auditable: $report,
            newValues: [
                'reportable_type' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
                'reason' => $report->reason,
                'details' => $report->details,
                'status' => $report->status?->value,
            ],
            metadata: [
                'source' => 'report_store',
            ],
        );

        $this->notifyStaff($report, $reportable);

        return $report->load(['reporter.profile', 'reportable']);
    }

    private function notifyStaff(Report $report, Model $reportable): void
    {
        $staffUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'moderator']))
            ->get(['id']);

        $reportableType = match (true) {
            $reportable instanceof Poem => 'poem',
            $reportable instanceof Article => 'article',
            $reportable instanceof Comment => 'comment',
            $reportable instanceof MediaItem => 'media',
            $reportable instanceof Event => 'event',
            $reportable instanceof Group => 'group',
            default => 'content',
        };

        foreach ($staffUsers as $staffUser) {
            $this->createNotificationAction->execute(
                userId: $staffUser->id,
                type: 'report_created',
                title: fn () => __('report.notifications.created_title'),
                message: fn () => __('report.notifications.created_message', [
                    'type' => __("report.types.{$reportableType}"),
                ]),
                data: [
                    'report_id' => $report->id,
                    'reportable_type' => $reportableType,
                    'reportable_id' => $reportable->getKey(),
                    'reason' => $report->reason,
                    'status' => $report->status?->value,
                ],
            );
        }
    }

    private function ensureNoOpenDuplicate(Model $reportable, int $reporterUserId): void
    {
        $exists = Report::query()
            ->where('reporter_user_id', $reporterUserId)
            ->where('reportable_type', $reportable->getMorphClass())
            ->where('reportable_id', $reportable->getKey())
            ->whereIn('status', [ReportStatus::Open, ReportStatus::InReview])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'report' => [__('report.errors.duplicate_open')],
            ]);
        }
    }

    private function ensureNotSelfReport(Model $reportable, int $reporterUserId): void
    {
        $ownerId = $reportable->user_id ?? null;

        if ($ownerId !== null && (int) $ownerId === $reporterUserId) {
            throw ValidationException::withMessages([
                'report' => [__('report.errors.self_report')],
            ]);
        }
    }

    private function ensureReportableIsVisible(Model $reportable): void
    {
        if ($reportable instanceof Poem) {
            if ($reportable->status === PoemStatus::Published && $reportable->is_public === true) {
                return;
            }
        }

        if ($reportable instanceof Article) {
            if ($reportable->status === ArticleStatus::Published && $reportable->is_public === true) {
                return;
            }
        }

        if ($reportable instanceof Comment) {
            $commentable = $reportable->commentable;

            if ($commentable instanceof Poem && $commentable->status === PoemStatus::Published && $commentable->is_public === true) {
                return;
            }

            if ($commentable instanceof Article && $commentable->status === ArticleStatus::Published && $commentable->is_public === true) {
                return;
            }

            if ($commentable instanceof Event && $commentable->status === EventStatus::Published && $commentable->is_public === true) {
                return;
            }

            if ($commentable instanceof Group && $commentable->is_active) {
                return;
            }

            if ($commentable instanceof MediaItem && $commentable->status === MediaStatus::Ready && $commentable->visibility === MediaVisibility::Public) {
                return;
            }
        }

        if ($reportable instanceof Event) {
            if ($reportable->status === EventStatus::Published && $reportable->is_public === true) {
                return;
            }
        }

        if ($reportable instanceof Group) {
            if ($reportable->is_active) {
                return;
            }
        }

        if ($reportable instanceof MediaItem) {
            if ($reportable->status === MediaStatus::Ready && $reportable->visibility === MediaVisibility::Public) {
                return;
            }
        }

        throw new NotFoundHttpException;
    }
}
