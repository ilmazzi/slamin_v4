<?php

namespace Domain\Newsletter\Actions;

use Domain\Newsletter\Models\NewsletterSubscriber;

class ConfirmNewsletterSubscriptionAction
{
    public function execute(string $token): bool
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('confirmation_token_hash', hash('sha256', $token))
            ->first();

        if (! $subscriber || ($subscriber->status === 'pending' && $subscriber->updated_at->lt(now()->subDays(2)))) {
            return false;
        }

        if ($subscriber->status === 'confirmed') {
            return true;
        }

        if ($subscriber->status !== 'pending') {
            return false;
        }

        $subscriber->forceFill([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return true;
    }
}
