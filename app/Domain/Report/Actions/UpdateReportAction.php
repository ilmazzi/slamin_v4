<?php

namespace Domain\Report\Actions;

use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Report\Enums\ReportStatus;
use Domain\Report\Models\Report;

class UpdateReportAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Report $report, int $moderatorUserId, string $status, ?string $resolutionNote = null): Report
    {
        $oldValues = [
            'status' => $report->status?->value,
            'reviewed_by_user_id' => $report->reviewed_by_user_id,
            'reviewed_at' => $report->reviewed_at?->toISOString(),
            'resolution_note' => $report->resolution_note,
        ];

        $resolvedStatus = ReportStatus::from($status);
        $reviewedAt = in_array($resolvedStatus, [ReportStatus::Resolved, ReportStatus::Dismissed], true)
            ? now()
            : $report->reviewed_at;

        $report->update([
            'status' => $resolvedStatus,
            'reviewed_by_user_id' => $moderatorUserId,
            'reviewed_at' => $reviewedAt,
            'resolution_note' => $resolutionNote,
        ]);

        audit()->log(
            event: 'report.updated',
            userId: $moderatorUserId,
            auditable: $report,
            oldValues: $oldValues,
            newValues: [
                'status' => $report->status?->value,
                'reviewed_by_user_id' => $report->reviewed_by_user_id,
                'reviewed_at' => $report->reviewed_at?->toISOString(),
                'resolution_note' => $report->resolution_note,
            ],
            metadata: [
                'source' => 'report_update',
            ],
        );

        if (in_array($resolvedStatus, [ReportStatus::Resolved, ReportStatus::Dismissed], true)) {
            $this->createNotificationAction->execute(
                userId: $report->reporter_user_id,
                type: 'report_updated',
                title: fn () => __('report.notifications.updated_title'),
                message: fn () => $resolvedStatus === ReportStatus::Resolved
                    ? __('report.notifications.resolved_message')
                    : __('report.notifications.dismissed_message'),
                data: [
                    'report_id' => $report->id,
                    'status' => $report->status?->value,
                    'resolution_note' => $report->resolution_note,
                ],
            );
        }

        return $report->fresh([
            'reporter.profile',
            'reviewedBy.profile',
            'reportable',
        ]);
    }
}
