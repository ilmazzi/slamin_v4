<?php

namespace Domain\Newsletter\Actions;

use App\Mail\NewsletterConfirmationMail;
use Domain\Newsletter\Models\NewsletterSubscriber;
use Domain\Notification\Support\NotificationMailLinks;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscribeToNewsletterAction
{
    public function execute(string $email, string $locale): void
    {
        $email = Str::lower(trim($email));
        $token = Str::random(64);

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);
        if ($subscriber->exists && $subscriber->status === 'confirmed') {
            return;
        }

        $subscriber->forceFill([
            'locale' => $locale,
            'status' => 'pending',
            'confirmation_token_hash' => hash('sha256', $token),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ])->save();

        $url = NotificationMailLinks::frontend('newsletter?confirm='.rawurlencode($token));

        Mail::to($subscriber->email)
            ->locale($subscriber->locale)
            ->send(new NewsletterConfirmationMail($url));
    }
}
