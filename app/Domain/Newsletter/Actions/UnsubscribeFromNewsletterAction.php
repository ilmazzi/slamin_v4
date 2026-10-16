<?php

namespace Domain\Newsletter\Actions;

use Domain\Newsletter\Models\NewsletterSubscriber;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class UnsubscribeFromNewsletterAction
{
    public function execute(string $token): bool
    {
        try {
            $publicId = Crypt::decryptString($token);
        } catch (DecryptException) {
            return false;
        }

        $subscriber = NewsletterSubscriber::query()->where('public_id', $publicId)->first();
        if (! $subscriber) {
            return false;
        }

        $subscriber->forceFill([
            'status' => 'unsubscribed',
            'confirmation_token_hash' => null,
            'unsubscribed_at' => now(),
        ])->save();

        return true;
    }
}
