<?php

use Domain\Audit\Models\AuditLog;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function profileTestUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'email_verified_at' => now(),
    ], $overrides));
}

it('updates the authenticated profile including bio visibility and avatar', function () {
    Storage::fake('public');
    $user = profileTestUser();
    Sanctum::actingAs($user);

    $response = $this->patch('/api/v1/me/profile', [
        'display_name' => 'Davide Mazzi',
        'bio' => 'Poeta e organizzatore.',
        'location' => 'Trento',
        'website_url' => 'https://slamin.test',
        'is_public' => true,
        'visibility' => [
            'bio' => true,
            'location' => false,
            'website_url' => true,
            'email' => false,
        ],
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 80, 80),
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.profile.display_name', 'Davide Mazzi')
        ->assertJsonPath('data.profile.bio', 'Poeta e organizzatore.')
        ->assertJsonPath('data.profile.location', 'Trento')
        ->assertJsonPath('data.profile.visibility.location', false)
        ->assertJsonPath('data.profile.is_owner', true);

    expect($response->json('data.profile.avatar_url'))->not->toBeNull();
    expect($user->fresh()->profile->avatar_path)->not->toBeNull();
});

it('updates and removes the dashboard banner image', function () {
    Storage::fake('public');
    $user = profileTestUser();
    Sanctum::actingAs($user);

    $this->patch('/api/v1/me/profile', [
        'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
    ])
        ->assertOk();

    expect($user->fresh()->profile->banner_path)->not->toBeNull();

    $this->patchJson('/api/v1/me/profile', [
        'remove_banner' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.profile.banner_url', null);

    expect($user->fresh()->profile->banner_path)->toBeNull();
});

it('keeps the same nickname when updating other profile fields', function () {
    $user = profileTestUser();
    Sanctum::actingAs($user);
    $nickname = $user->profile->nickname;

    $this->patchJson('/api/v1/me/profile', [
        'nickname' => $nickname,
        'bio' => 'Bio aggiornata',
    ])
        ->assertOk()
        ->assertJsonPath('data.profile.nickname', $nickname)
        ->assertJsonPath('data.profile.bio', 'Bio aggiornata');
});

it('stores and publicly exposes social channels and custom links', function () {
    $owner = profileTestUser();
    $owner->profile->update(['nickname' => 'social_poet']);
    Sanctum::actingAs($owner);

    $this->patchJson('/api/v1/me/profile', [
        'social_links' => [
            ['platform' => 'instagram', 'label' => 'Ignored label', 'url' => 'https://instagram.com/social_poet'],
            ['platform' => 'bluesky', 'label' => 'Bluesky', 'url' => 'https://bsky.app/profile/social-poet.test'],
            ['platform' => 'custom', 'label' => 'Podcast', 'url' => 'https://example.com/podcast'],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.profile.social_links.0.label', 'Instagram')
        ->assertJsonPath('data.profile.social_links.2.label', 'Podcast');

    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/profiles/social_poet')
        ->assertOk()
        ->assertJsonCount(3, 'data.profile.social_links')
        ->assertJsonPath('data.profile.social_links.1.platform', 'bluesky')
        ->assertJsonPath('data.profile.social_links.2.url', 'https://example.com/podcast');
});

it('stores and publicly exposes spoken languages without duplicates', function () {
    $owner = profileTestUser();
    $owner->profile->update(['nickname' => 'international_poet']);
    Sanctum::actingAs($owner);

    $this->patchJson('/api/v1/me/profile', [
        'spoken_languages' => ['Italiano', 'English', ' italiano ', 'Español'],
    ])
        ->assertOk()
        ->assertJsonPath('data.profile.spoken_languages', ['Italiano', 'English', 'Español']);

    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/profiles/international_poet')
        ->assertOk()
        ->assertJsonPath('data.profile.spoken_languages', ['Italiano', 'English', 'Español']);
});

it('rejects unsafe and malformed profile links', function () {
    $user = profileTestUser();
    Sanctum::actingAs($user);

    $unsafeResponse = $this->patchJson('/api/v1/me/profile', [
        'social_links' => [
            ['platform' => 'custom', 'label' => 'Unsafe', 'url' => 'javascript:alert(1)'],
        ],
    ]);
    $unsafeResponse->assertUnprocessable();
    expect($unsafeResponse->json('errors.fields'))->toHaveKey('social_links.0.url');

    $missingLabelResponse = $this->patchJson('/api/v1/me/profile', [
        'social_links' => [
            ['platform' => 'custom', 'label' => '', 'url' => 'https://example.com'],
        ],
    ]);
    $missingLabelResponse->assertUnprocessable();
    expect($missingLabelResponse->json('errors.fields'))->toHaveKey('social_links.0.label');
});

it('hides private fields on the public profile and 404s private profiles', function () {
    $owner = profileTestUser();
    $viewer = profileTestUser();
    $profile = $owner->profile;
    $profile->update([
        'nickname' => 'mazzi_public',
        'display_name' => 'Davide',
        'bio' => 'Testo bio',
        'location' => 'Trento',
        'website_url' => 'https://example.com',
        'is_public' => true,
        'visibility' => [
            'bio' => true,
            'location' => false,
            'website_url' => true,
            'email' => false,
        ],
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/v1/profiles/mazzi_public')
        ->assertOk()
        ->assertJsonPath('data.profile.display_name', 'Davide')
        ->assertJsonPath('data.profile.bio', 'Testo bio')
        ->assertJsonPath('data.profile.website_url', 'https://example.com')
        ->assertJsonPath('data.profile.location', null)
        ->assertJsonPath('data.profile.email', null)
        ->assertJsonPath('data.profile.is_owner', false);

    $profile->update(['is_public' => false]);

    $this->getJson('/api/v1/profiles/mazzi_public')->assertNotFound();

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/profiles/mazzi_public')
        ->assertOk()
        ->assertJsonPath('data.profile.is_owner', true)
        ->assertJsonPath('data.profile.location', 'Trento')
        ->assertJsonPath('data.profile.email', $owner->email);
});

it('shows public stats and only publicly visible publications in profile activities', function () {
    $owner = profileTestUser(['account_type' => 'poet_organizer']);
    $owner->profile->update(['nickname' => 'mazzi_feed', 'is_public' => true]);

    $poem = fn (string $slug, string $status, bool $isPublic) => Poem::query()->create([
        'user_id' => $owner->id,
        'title' => "Titolo {$slug}",
        'slug' => $slug,
        'content' => 'Versi',
        'language' => 'it',
        'status' => $status,
        'is_public' => $isPublic,
        'published_at' => $status === 'published' ? now() : null,
    ]);

    $published = $poem('pubblica', 'published', true);
    $draft = $poem('bozza', 'draft', true);
    $private = $poem('privata', 'published', false);

    foreach ([[$published, 'poem.created'], [$draft, 'poem.created'], [$private, 'poem.created'], [$published, 'poem.liked']] as [$target, $event]) {
        AuditLog::query()->create([
            'user_id' => $owner->id,
            'event' => $event,
            'auditable_type' => Poem::class,
            'auditable_id' => $target->id,
            'created_at' => now(),
        ]);
    }

    $this->getJson('/api/v1/profiles/mazzi_feed')
        ->assertOk()
        ->assertJsonPath('data.profile.account_type', 'poet_organizer')
        ->assertJsonPath('data.profile.member_since', $owner->created_at->toIso8601String())
        ->assertJsonPath('data.stats.poems', 1)
        ->assertJsonPath('data.stats.events', 0);

    $this->getJson('/api/v1/profiles/mazzi_feed/activities')
        ->assertOk()
        ->assertJsonPath('data.recent_activities.total', 1)
        ->assertJsonPath('data.recent_activities.items.0.label', __('profile.public_activity_labels.poem_created'))
        ->assertJsonPath('data.recent_activities.items.0.title', 'Titolo pubblica')
        ->assertJsonPath('data.recent_activities.items.0.url', '/poems/pubblica');

    Sanctum::actingAs($owner);
    $this->patchJson('/api/v1/me/profile', ['visibility' => ['poems' => false]])
        ->assertOk()
        ->assertJsonPath('data.profile.visibility.poems', false)
        ->assertJsonPath('data.profile.visibility.bio', true)
        ->assertJsonPath('data.profile.sections', ['events', 'articles', 'media']);

    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/profiles/mazzi_feed')
        ->assertOk()
        ->assertJsonPath('data.profile.sections', ['events', 'articles', 'media'])
        ->assertJsonMissingPath('data.stats.poems');

    $this->getJson('/api/v1/profiles/mazzi_feed/activities')
        ->assertOk()
        ->assertJsonPath('data.recent_activities.total', 0);

    $owner->profile->update(['is_public' => false]);

    $this->getJson('/api/v1/profiles/mazzi_feed/activities')->assertNotFound();
});
