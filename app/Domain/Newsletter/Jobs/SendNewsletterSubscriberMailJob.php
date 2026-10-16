<?php

namespace Domain\Newsletter\Jobs;

use App\Mail\NewsletterCampaignMail;
use Domain\Newsletter\Models\NewsletterCampaign;
use Domain\Newsletter\Models\NewsletterSubscriber;
use Domain\Notification\Support\NotificationMailLinks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class SendNewsletterSubscriberMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(
        public readonly int $campaignId,
        public readonly int $subscriberId,
    ) {}

    public function handle(): void
    {
        $campaign = NewsletterCampaign::query()->find($this->campaignId);
        $subscriber = NewsletterSubscriber::query()->confirmed()->find($this->subscriberId);

        if (! $campaign || ! $subscriber || ! in_array($campaign->status, ['sending', 'sent'], true)) {
            return;
        }

        $token = Crypt::encryptString($subscriber->public_id);
        $unsubscribeUrl = NotificationMailLinks::frontend('newsletter?unsubscribe='.rawurlencode($token));

        Mail::to($subscriber->email)
            ->locale($subscriber->locale)
            ->send(new NewsletterCampaignMail($campaign, $unsubscribeUrl));
    }
}
