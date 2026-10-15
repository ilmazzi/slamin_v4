<?php

namespace App\Console\Commands;

use Domain\Notification\Actions\BuildWeeklyDigestContentAction;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Jobs\SendWeeklyDigestEmailJob;
use Domain\User\Models\User;
use Illuminate\Console\Command;

class SendWeeklyDigestEmailsCommand extends Command
{
    protected $signature = 'notifications:send-weekly-digest';

    protected $description = 'Queue the weekly platform digest for every user who did not opt out';

    public function handle(BuildWeeklyDigestContentAction $buildContent): int
    {
        $since = now()->subWeek();
        $popular = $buildContent->execute($since);
        $queued = 0;

        User::query()
            ->active()
            ->whereNotNull('email_verified_at')
            ->where(fn ($query) => $query
                ->whereNull('notification_email_opt_outs')
                ->orWhereJsonDoesntContain('notification_email_opt_outs', NotificationCategory::WeeklyDigest->value))
            ->select('id')
            ->chunkById(500, function ($users) use ($popular, $since, &$queued) {
                foreach ($users as $user) {
                    SendWeeklyDigestEmailJob::dispatch($user->id, $popular, $since->toIso8601String());
                    $queued++;
                }
            });

        $this->info("Queued {$queued} weekly digests.");

        return self::SUCCESS;
    }
}
