<?php

namespace App\Console\Commands;

use Domain\Engagement\Actions\NotifyContentLikedAction;
use Domain\Notification\Jobs\SendLikesSummaryEmailJob;
use Domain\Notification\Models\Notification;
use Illuminate\Console\Command;

class SendLikesSummaryEmailsCommand extends Command
{
    protected $signature = 'notifications:send-likes-summary';

    protected $description = 'Queue the daily email that groups the likes each user received in the last 24 hours';

    public function handle(): int
    {
        $until = now();
        $since = $until->copy()->subDay();
        $queued = 0;

        Notification::query()
            ->whereIn('type', NotifyContentLikedAction::TYPES)
            ->whereBetween('created_at', [$since, $until])
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->each(function (int $userId) use ($since, $until, &$queued) {
                SendLikesSummaryEmailJob::dispatch($userId, $since->toIso8601String(), $until->toIso8601String());
                $queued++;
            });

        $this->info("Queued {$queued} likes summaries.");

        return self::SUCCESS;
    }
}
