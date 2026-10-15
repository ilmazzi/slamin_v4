<?php

namespace Domain\Notification\Jobs;

use App\Mail\Notifications\WeeklyDigestMail;
use Domain\Notification\Actions\BuildDigestNotificationsAction;
use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigestEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $userId,
        public readonly array $popular,
        public readonly string $since,
    ) {}

    public function handle(BuildDigestNotificationsAction $buildDigestNotifications): void
    {
        $recipient = User::query()->with('profile')->find($this->userId);
        if ($recipient === null || ! $recipient->wantsNotificationEmail(NotificationCategory::WeeklyDigest)) {
            return;
        }

        $notifications = $buildDigestNotifications->execute($recipient, Carbon::parse($this->since));

        if ($notifications === [] && array_filter($this->popular) === []) {
            return;
        }

        Mail::to($recipient)->send(new WeeklyDigestMail($recipient, $this->popular, $notifications));
    }
}
