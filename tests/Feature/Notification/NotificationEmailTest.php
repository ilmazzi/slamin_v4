<?php

use App\Mail\Notifications\ChatMessageMail;
use App\Mail\Notifications\LikesSummaryMail;
use App\Mail\Notifications\NotificationMail;
use App\Mail\Notifications\WeeklyDigestMail;
use Domain\Chat\Jobs\SendUnreadChatMessageEmailsJob;
use Domain\Chat\Models\ChatThreadMember;
use Domain\Event\Models\EventParticipantInvite;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Jobs\SendLikesSummaryEmailJob;
use Domain\Notification\Jobs\SendNotificationEmailJob;
use Domain\Notification\Jobs\SendWeeklyDigestEmailJob;
use Domain\Notification\Models\Notification;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function emailPoem(User $owner, array $overrides = []): Poem
{
    return Poem::query()->create(array_merge([
        'user_id' => $owner->id,
        'title' => 'Notte in centro',
        'slug' => 'notte-in-centro-'.fake()->unique()->numberBetween(1, 99999),
        'content' => 'Versi',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

function directThreadBetween(User $sender, User $recipient): int
{
    Sanctum::actingAs($sender);

    return test()->postJson('/api/v1/chat/direct-threads', ['user_id' => $recipient->id])->json('data.thread.id');
}

it('returns every email category enabled by default and saves opt outs', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me/notification-preferences')
        ->assertOk()
        ->assertJsonPath('data.preferences.chat_messages', true)
        ->assertJsonPath('data.preferences.weekly_digest', true)
        ->assertJsonCount(count(NotificationCategory::cases()), 'data.preferences');

    $this->putJson('/api/v1/me/notification-preferences', ['preferences' => ['likes' => false, 'comments' => false]])
        ->assertOk()
        ->assertJsonPath('data.preferences.likes', false)
        ->assertJsonPath('data.preferences.comments', false)
        ->assertJsonPath('data.preferences.snaps', true);

    $this->putJson('/api/v1/me/notification-preferences', ['preferences' => ['likes' => true]])
        ->assertOk()
        ->assertJsonPath('data.preferences.likes', true);

    expect($user->fresh()->notification_email_opt_outs)->toBe(['comments']);
});

it('rejects unknown email categories', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/v1/me/notification-preferences', ['preferences' => ['spam' => false]])
        ->assertUnprocessable();
});

it('emails a notification right away in the recipient language unless the category is opted out', function () {
    Mail::fake();
    $recipient = User::factory()->create();
    $recipient->profile()->update(['preferred_locale' => 'en']);

    app(CreateNotificationAction::class)->execute($recipient->id, 'event_participant_invited', 'Invito', 'Sei invitato', ['event_slug' => 'slam']);

    Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($recipient) {
        return $mail->hasTo($recipient->email)
            && $mail->locale === 'en'
            && $mail->category === NotificationCategory::EventParticipantInvites
            && str_contains($mail->render(), '/events/slam');
    });

    $recipient->forceFill(['notification_email_opt_outs' => ['event_participant_invites']])->save();
    Mail::fake();

    app(CreateNotificationAction::class)->execute($recipient->id, 'event_participant_invited', 'Invito', 'Sei invitato');

    Mail::assertNothingSent();
});

it('writes the notification in the recipient language, not in the language of who triggered it', function () {
    Mail::fake();
    $recipient = User::factory()->create();
    $recipient->profile()->update(['preferred_locale' => 'it']);
    app()->setLocale('en');

    $notification = app(CreateNotificationAction::class)->execute(
        userId: $recipient->id,
        type: 'gig.application_received',
        title: fn () => __('gig.notifications.application_received_title'),
        message: fn () => __('gig.notifications.application_received_message', ['name' => 'Giulia', 'title' => 'Slam']),
    );

    expect($notification->title)->toBe('Nuova candidatura')
        ->and($notification->message)->toBe('Giulia ha inviato una candidatura per «Slam».')
        ->and(app()->getLocale())->toBe('en');

    Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->locale === 'it'
        && str_contains($mail->render(), 'Nuova candidatura')
        && ! str_contains($mail->render(), 'New application'));
});

it('translates the invite role instead of showing its code', function () {
    app()->setLocale('it');

    expect((new EventParticipantInvite(['role' => 'technical_support']))->roleLabel())->toBe('supporto tecnico')
        ->and((new EventParticipantInvite(['role' => 'custom']))->roleLabel())->toBe('custom');
});

it('does not email unverified accounts', function () {
    Mail::fake();
    $recipient = User::factory()->unverified()->create();

    app(CreateNotificationAction::class)->execute($recipient->id, 'poem_commented', 'Commento', 'Nuovo commento');

    Mail::assertNothingSent();
});

it('does not send likes or chat through the immediate notification email', function () {
    Queue::fake();
    $recipient = User::factory()->create();

    app(CreateNotificationAction::class)->execute($recipient->id, 'poem_liked', 'Like', 'Like');
    app(CreateNotificationAction::class)->execute($recipient->id, 'poem_commented', 'Commento', 'Commento');

    Queue::assertPushed(SendNotificationEmailJob::class, 1);
});

it('unsubscribes from a category through the signed link only', function () {
    $user = User::factory()->create();
    $url = URL::signedRoute('notifications.email.unsubscribe', ['user' => $user->public_id, 'category' => 'weekly_digest']);

    $this->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
    expect($user->fresh()->notification_email_opt_outs)->toBeNull();

    $this->get($url)->assertOk()->assertSee(__('notifications.mail.unsubscribed_title', [], 'it'));
    expect($user->fresh()->wantsNotificationEmail(NotificationCategory::WeeklyDigest))->toBeFalse();

    $this->post(URL::signedRoute('notifications.email.unsubscribe', ['user' => $user->public_id, 'category' => 'likes']))->assertNoContent();
    expect($user->fresh()->notification_email_opt_outs)->toBe(['weekly_digest', 'likes']);
});

it('emails an unread chat message once per conversation until it is read', function () {
    Queue::fake();
    Mail::fake();
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $threadId = directThreadBetween($sender, $recipient);

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", ['content' => 'Ciao, ci sei stasera?'])->assertCreated();
    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", ['content' => 'Fammi sapere'])->assertCreated();

    Queue::assertPushed(SendUnreadChatMessageEmailsJob::class, fn ($job) => $job->delay !== null);
    $jobs = Queue::pushed(SendUnreadChatMessageEmailsJob::class);
    expect($jobs)->toHaveCount(2);

    $jobs->each(fn (SendUnreadChatMessageEmailsJob $job) => $job->handle());

    Mail::assertSent(ChatMessageMail::class, 1);
    Mail::assertSent(ChatMessageMail::class, fn (ChatMessageMail $mail) => $mail->hasTo($recipient->email)
        && str_contains($mail->render(), 'Ciao, ci sei stasera?')
        && str_contains($mail->render(), '/chat/'.$threadId));

    ChatThreadMember::query()->where('chat_thread_id', $threadId)->where('user_id', $recipient->id)->update(['last_read_at' => now()->addSecond()]);
    $this->travel(5)->seconds();

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", ['content' => 'Nuovo messaggio'])->assertCreated();
    Queue::pushed(SendUnreadChatMessageEmailsJob::class)->last()->handle();

    Mail::assertSent(ChatMessageMail::class, 2);
});

it('skips the chat email when the message was read in time', function () {
    Queue::fake();
    Mail::fake();
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $threadId = directThreadBetween($sender, $recipient);

    $this->postJson("/api/v1/chat/threads/{$threadId}/messages", ['content' => 'Ciao'])->assertCreated();
    ChatThreadMember::query()->where('chat_thread_id', $threadId)->where('user_id', $recipient->id)->update(['last_read_at' => now()->addSecond()]);

    Queue::pushed(SendUnreadChatMessageEmailsJob::class)->first()->handle();

    Mail::assertNothingSent();
});

it('creates one like notification per liker and content and never for self likes', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $liker = User::factory()->create();
    $poem = emailPoem($owner);

    Sanctum::actingAs($liker);
    $this->postJson("/api/v1/poems/{$poem->id}/like")->assertSuccessful();
    $this->deleteJson("/api/v1/poems/{$poem->id}/like")->assertSuccessful();
    $this->postJson("/api/v1/poems/{$poem->id}/like")->assertSuccessful();

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/poems/{$poem->id}/like")->assertSuccessful();

    $notifications = Notification::query()->where('type', 'poem_liked')->get();
    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->user_id)->toBe($owner->id)
        ->and($notifications->first()->data['poem_slug'])->toBe($poem->slug);

    Queue::assertNotPushed(SendNotificationEmailJob::class);
});

it('groups the likes of the last day into one summary email', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $poem = emailPoem($owner);

    foreach (User::factory()->count(3)->create() as $liker) {
        Sanctum::actingAs($liker);
        $this->postJson("/api/v1/poems/{$poem->id}/like")->assertSuccessful();
    }

    Queue::fake();
    $this->artisan('notifications:send-likes-summary')->assertSuccessful();
    Queue::assertPushed(SendLikesSummaryEmailJob::class, 1);

    app()->call([Queue::pushed(SendLikesSummaryEmailJob::class)->first(), 'handle']);

    Mail::assertSent(LikesSummaryMail::class, fn (LikesSummaryMail $mail) => $mail->hasTo($owner->email)
        && $mail->total === 3
        && $mail->items[0]['count'] === 3
        && $mail->items[0]['title'] === 'Notte in centro');
});

it('sends the weekly digest with popular content and personal notifications to users who did not opt out', function () {
    Mail::fake();
    $reader = User::factory()->create();
    $optedOut = User::factory()->create(['notification_email_opt_outs' => ['weekly_digest']]);
    User::factory()->unverified()->create();
    emailPoem(User::factory()->create(), ['title' => 'Poesia della settimana']);
    Notification::query()->create(['user_id' => $reader->id, 'type' => 'poem_commented', 'title' => 'Nuovo commento', 'message' => 'Qualcuno ha commentato', 'data' => []]);

    Queue::fake();
    $this->artisan('notifications:send-weekly-digest')->assertSuccessful();

    $jobs = Queue::pushed(SendWeeklyDigestEmailJob::class);
    expect($jobs->pluck('userId')->all())->toContain($reader->id)->not->toContain($optedOut->id);

    app()->call([$jobs->firstWhere('userId', $reader->id), 'handle']);

    Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) use ($reader) {
        $html = $mail->render();

        return $mail->hasTo($reader->email)
            && str_contains($html, 'Poesia della settimana')
            && str_contains($html, 'Nuovo commento');
    });
});

it('maps every notification type used in the code to an email category', function () {
    $types = collect(File::allFiles(app_path('Domain')))
        ->flatMap(function ($file) {
            preg_match_all("/->execute\(\s*userId:[^;]*?type: '([a-z_.]+)'/s", $file->getContents(), $matches);

            return $matches[1];
        })
        ->merge(['poem_commented', 'event_commented', 'article_commented', 'media_commented'])
        ->unique();

    expect($types)->not->toBeEmpty();
    $types->each(fn (string $type) => expect(NotificationCategory::fromType($type))->not->toBeNull("{$type} has no email category"));
});
