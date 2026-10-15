<?php

namespace Domain\Notification\Jobs;

use App\Mail\Notifications\LikesSummaryMail;
use Domain\Notification\Actions\BuildLikesSummaryAction;
use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendLikesSummaryEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $since,
        public readonly string $until,
    ) {}

    public function handle(BuildLikesSummaryAction $buildLikesSummary): void
    {
        $recipient = User::query()->with('profile')->find($this->userId);
        if ($recipient === null || ! $recipient->wantsNotificationEmail(NotificationCategory::Likes)) {
            return;
        }

        $summary = $buildLikesSummary->execute($recipient, Carbon::parse($this->since), Carbon::parse($this->until));
        if ($summary['total'] === 0) {
            return;
        }

        Mail::to($recipient)->send(new LikesSummaryMail($recipient, $summary['items'], $summary['total']));
    }
}
