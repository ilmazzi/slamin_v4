<?php

use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterConfirmationMail;
use Domain\Newsletter\Jobs\SendNewsletterCampaignJob;
use Domain\Newsletter\Jobs\SendNewsletterSubscriberMailJob;
use Domain\Newsletter\Models\NewsletterCampaign;
use Domain\Newsletter\Models\NewsletterSubscriber;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function newsletterAdmin(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
    $user->roles()->attach($role);

    return $user;
}

it('subscribes and confirms an email address with double opt in', function () {
    Mail::fake();

    $this->postJson('/api/v1/newsletter/subscribe', [
        'email' => 'Reader@Example.com',
        'locale' => 'it',
    ])->assertOk();

    $subscriber = NewsletterSubscriber::query()->firstOrFail();
    expect($subscriber->email)->toBe('reader@example.com')
        ->and($subscriber->status)->toBe('pending');

    $confirmationToken = null;
    Mail::assertSent(NewsletterConfirmationMail::class, function (NewsletterConfirmationMail $mail) use (&$confirmationToken): bool {
        parse_str((string) parse_url($mail->confirmationUrl, PHP_URL_QUERY), $query);
        $confirmationToken = $query['confirm'] ?? null;

        return $mail->hasTo('reader@example.com') && is_string($confirmationToken);
    });

    $this->postJson('/api/v1/newsletter/confirm', [
        'token' => $confirmationToken,
    ])->assertOk();

    expect($subscriber->fresh()->status)->toBe('confirmed')
        ->and($subscriber->fresh()->confirmed_at)->not->toBeNull();
});

it('unsubscribes a confirmed subscriber', function () {
    $subscriber = NewsletterSubscriber::query()->create([
        'email' => 'reader@example.com',
        'locale' => 'it',
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);

    $this->postJson('/api/v1/newsletter/unsubscribe', [
        'token' => Crypt::encryptString($subscriber->public_id),
    ])->assertOk();

    expect($subscriber->fresh()->status)->toBe('unsubscribed')
        ->and($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('queues a sanitized newsletter campaign for confirmed subscribers', function () {
    Queue::fake();
    $admin = newsletterAdmin();
    NewsletterSubscriber::query()->create([
        'email' => 'reader@example.com',
        'locale' => 'it',
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/admin/newsletter/send', [
        'subject' => 'Aggiornamenti Slam In',
        'content' => '<p>Ciao <strong>community</strong></p><script>alert(1)</script>',
    ])->assertStatus(202)
        ->assertJsonPath('data.campaign.status', 'queued');

    $campaign = NewsletterCampaign::query()->firstOrFail();
    expect($campaign->content)->toContain('<strong>community</strong>')
        ->and($campaign->content)->not->toContain('<script');

    Queue::assertPushed(SendNewsletterCampaignJob::class, fn ($job) => $job->campaignId === $campaign->id);
});

it('enforces the seven day newsletter cooldown', function () {
    Queue::fake();
    $admin = newsletterAdmin();
    NewsletterSubscriber::query()->create([
        'email' => 'reader@example.com',
        'locale' => 'it',
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);
    NewsletterCampaign::query()->create([
        'created_by_user_id' => $admin->id,
        'subject' => 'Invio precedente',
        'content' => '<p>Contenuto</p>',
        'status' => 'sent',
        'recipient_count' => 1,
        'sent_at' => now()->subDays(2),
    ]);

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/admin/newsletter/send', [
        'subject' => 'Troppo presto',
        'content' => '<p>Contenuto</p>',
    ])->assertUnprocessable();
});

it('dispatches one delivery job for each confirmed subscriber', function () {
    Queue::fake();
    $campaign = NewsletterCampaign::query()->create([
        'subject' => 'Aggiornamenti',
        'content' => '<p>Contenuto</p>',
        'status' => 'queued',
    ]);

    foreach (['one@example.com', 'two@example.com'] as $email) {
        NewsletterSubscriber::query()->create([
            'email' => $email,
            'locale' => 'it',
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }
    NewsletterSubscriber::query()->create([
        'email' => 'pending@example.com',
        'locale' => 'it',
        'status' => 'pending',
    ]);

    (new SendNewsletterCampaignJob($campaign->id))->handle();

    Queue::assertPushed(SendNewsletterSubscriberMailJob::class, 2);
    expect($campaign->fresh()->status)->toBe('sent')
        ->and($campaign->fresh()->recipient_count)->toBe(2);
});

it('sends a campaign without tracking and with an unsubscribe link', function () {
    Mail::fake();
    $campaign = NewsletterCampaign::query()->create([
        'subject' => 'Aggiornamenti',
        'content' => '<p>Contenuto</p>',
        'status' => 'sent',
        'sent_at' => now(),
    ]);
    $subscriber = NewsletterSubscriber::query()->create([
        'email' => 'reader@example.com',
        'locale' => 'it',
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);

    (new SendNewsletterSubscriberMailJob($campaign->id, $subscriber->id))->handle();

    Mail::assertSent(NewsletterCampaignMail::class, function (NewsletterCampaignMail $mail): bool {
        return $mail->hasTo('reader@example.com')
            && str_contains($mail->unsubscribeUrl, '/newsletter?unsubscribe=');
    });
});
