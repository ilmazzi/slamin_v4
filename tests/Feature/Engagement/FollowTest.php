<?php

use App\Mail\Notifications\NotificationMail;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Models\Notification;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function followUser(string $nickname): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->profile()->update(['nickname' => $nickname, 'display_name' => ucfirst($nickname), 'is_public' => true]);

    return $user->fresh('profile');
}

function followEvent(User $owner, array $overrides = []): Event
{
    $category = EventCategory::query()->firstOrCreate(['slug' => 'follow'], ['is_active' => true, 'sort_order' => 1]);

    return Event::query()->create(array_merge([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Serata seguita',
        'slug' => 'serata-seguita-'.uniqid(),
        'description' => 'Evento.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

function followPoem(User $owner, string $title): Poem
{
    $poem = Poem::query()->create([
        'user_id' => $owner->id,
        'poem_category_id' => PoemCategory::query()->firstOrCreate(['slug' => 'follow'], ['is_active' => true, 'sort_order' => 1])->id,
        'poem_type_id' => PoemType::query()->firstOrCreate(['slug' => 'follow'], ['is_active' => true, 'sort_order' => 1])->id,
        'title' => $title,
        'slug' => 'poesia-'.uniqid(),
        'content' => 'Testo.',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);
    audit()->log(event: 'poem.created', userId: $owner->id, auditable: $poem);

    return $poem;
}

it('follows and unfollows a public profile but not your own or a private one', function () {
    $viewer = followUser('lettore');
    $poet = followUser('poeta');
    Sanctum::actingAs($viewer);

    $this->postJson('/api/v1/profiles/poeta/follow')
        ->assertOk()
        ->assertJsonPath('data.follow', ['is_following' => true, 'followers_count' => 1]);
    $this->postJson('/api/v1/profiles/poeta/follow')->assertJsonPath('data.follow.followers_count', 1);
    $this->getJson('/api/v1/profiles/poeta')->assertJsonPath('data.follow.is_following', true);

    $this->deleteJson('/api/v1/profiles/poeta/follow')
        ->assertOk()
        ->assertJsonPath('data.follow', ['is_following' => false, 'followers_count' => 0]);

    $this->postJson('/api/v1/profiles/lettore/follow')->assertStatus(422);

    $poet->profile()->update(['is_public' => false]);
    $this->postJson('/api/v1/profiles/poeta/follow')->assertNotFound();
});

it('notifies and emails the followed user once, unless the category is opted out', function () {
    Mail::fake();
    $viewer = followUser('lettore');
    $poet = followUser('poeta');
    $poet->profile()->update(['preferred_locale' => 'en']);
    Sanctum::actingAs($viewer);

    $this->postJson('/api/v1/profiles/poeta/follow')->assertOk();
    $this->deleteJson('/api/v1/profiles/poeta/follow')->assertOk();
    $this->postJson('/api/v1/profiles/poeta/follow')->assertOk();

    $notifications = Notification::query()->where('user_id', $poet->id)->where('type', 'user_followed')->get();
    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->message)->toBe('Lettore started following you.')
        ->and($notifications->first()->data['follower_nickname'])->toBe('lettore');
    Mail::assertSent(NotificationMail::class, 1);
    Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($poet->email)
        && $mail->category === NotificationCategory::Followers
        && str_contains($mail->render(), '/u/lettore'));

    $poet->forceFill(['notification_email_opt_outs' => ['followers']])->save();
    Mail::fake();
    Sanctum::actingAs(followUser('altro'));
    $this->postJson('/api/v1/profiles/poeta/follow')->assertOk();

    expect(Notification::query()->where('user_id', $poet->id)->count())->toBe(2);
    Mail::assertNothingSent();
});

it('does not notify the organizer when someone follows an event', function () {
    $organizer = followUser('organizzatore');
    Sanctum::actingAs(followUser('lettore'));

    $this->postJson('/api/v1/events/'.followEvent($organizer)->id.'/follow')->assertOk();

    expect(Notification::query()->where('user_id', $organizer->id)->exists())->toBeFalse();
});

it('follows a published event but not your own or a draft', function () {
    $viewer = followUser('lettore');
    $organizer = followUser('organizzatore');
    $event = followEvent($organizer);
    Sanctum::actingAs($viewer);

    $this->postJson("/api/v1/events/{$event->id}/follow")
        ->assertOk()
        ->assertJsonPath('data.follow', ['is_following' => true, 'followers_count' => 1]);
    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertJsonPath('data.event.is_following', true)
        ->assertJsonPath('data.event.followers_count', 1);
    $this->deleteJson("/api/v1/events/{$event->id}/follow")->assertJsonPath('data.follow.is_following', false);

    $this->postJson('/api/v1/events/'.followEvent($organizer, ['status' => 'draft'])->id.'/follow')->assertForbidden();

    Sanctum::actingAs($organizer);
    $this->postJson("/api/v1/events/{$event->id}/follow")->assertForbidden();
});

it('shows by default the updates of followed profiles and events in the dashboard', function () {
    $viewer = followUser('lettore');
    $poet = followUser('poeta');
    $stranger = followUser('sconosciuto');
    $event = followEvent($stranger, ['title' => 'Festival seguito']);
    Sanctum::actingAs($viewer);
    $this->postJson('/api/v1/profiles/poeta/follow')->assertOk();
    $this->postJson("/api/v1/events/{$event->id}/follow")->assertOk();

    followPoem($poet, 'Poesia del poeta');
    followPoem($stranger, 'Poesia di uno sconosciuto');
    followPoem($viewer, 'Poesia mia');
    $this->travel(1)->minute();
    audit()->log(event: 'event.updated', userId: $stranger->id, auditable: $event);

    $this->getJson('/api/v1/me/dashboard-widgets?dashboard_activities_page=1')
        ->assertOk()
        ->assertJsonPath('data.recent_activities_feed', 'following')
        ->assertJsonPath('data.following_count', 2)
        ->assertJsonPath('data.recent_activities.total', 2)
        ->assertJsonPath('data.recent_activities.items.0.title', 'Festival seguito')
        ->assertJsonPath('data.recent_activities.items.0.label', __('home.dashboard_page.following_activity_labels.event_updated'))
        ->assertJsonPath('data.recent_activities.items.0.actor', ['name' => 'Sconosciuto', 'url' => '/u/sconosciuto'])
        ->assertJsonPath('data.recent_activities.items.1.title', 'Poesia del poeta')
        ->assertJsonPath('data.recent_activities.items.1.actor.url', '/u/poeta');

    $poet->profile()->update(['visibility' => ['poems' => false]]);

    $this->getJson('/api/v1/me/dashboard-widgets')->assertJsonPath('data.recent_activities.total', 1);

    $this->getJson('/api/v1/me/dashboard-widgets?activities_feed=mine')
        ->assertJsonPath('data.recent_activities_feed', 'mine')
        ->assertJsonPath('data.recent_activities.items.0.title', 'Poesia mia');
});
