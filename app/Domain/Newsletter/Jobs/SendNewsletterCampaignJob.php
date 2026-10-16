<?php

namespace Domain\Newsletter\Jobs;

use Domain\Newsletter\Models\NewsletterCampaign;
use Domain\Newsletter\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendNewsletterCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $campaignId,
    ) {}

    public function handle(): void
    {
        $campaign = NewsletterCampaign::query()->find($this->campaignId);
        if (! $campaign || $campaign->status !== 'queued') {
            return;
        }

        $campaign->update(['status' => 'sending', 'failure_message' => null]);
        $recipientCount = 0;

        try {
            NewsletterSubscriber::query()
                ->confirmed()
                ->select('id')
                ->chunkById(200, function ($subscribers) use ($campaign, &$recipientCount): void {
                    foreach ($subscribers as $subscriber) {
                        SendNewsletterSubscriberMailJob::dispatch($campaign->id, $subscriber->id);
                        $recipientCount++;
                    }
                });

            $campaign->update([
                'status' => 'sent',
                'recipient_count' => $recipientCount,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $campaign->update([
                'status' => 'failed',
                'failure_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
