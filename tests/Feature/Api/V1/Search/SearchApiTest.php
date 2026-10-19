<?php

use Domain\Article\Models\Article;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Group\Models\Group;
use Domain\Media\Models\MediaItem;
use Domain\Poetry\Models\Poem;
use Domain\Poetry\Models\PoemCategory;
use Domain\Poetry\Models\PoemType;
use Domain\Profile\Models\Profile;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('searches public catalog content from the home query', function () {
    $author = User::factory()->poetOrganizer()->create();
    $matchingUser = User::factory()->create();
    $privateUser = User::factory()->create();

    Profile::query()->where('user_id', $matchingUser->id)->update([
        'nickname' => 'novapoet',
        'display_name' => 'Nova Search',
        'is_public' => true,
    ]);
    Profile::query()->where('user_id', $privateUser->id)->update([
        'nickname' => 'novahidden',
        'display_name' => 'Nova Hidden',
        'is_public' => false,
    ]);

    $poemCategory = PoemCategory::query()->create([
        'slug' => 'search-cat',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $poemCategory->translations()->create(['locale' => 'it', 'name' => 'Search']);
    $poemType = PoemType::query()->create([
        'slug' => 'search-type',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $poemType->translations()->create(['locale' => 'it', 'name' => 'Search type']);

    Poem::query()->create([
        'user_id' => $author->id,
        'poem_category_id' => $poemCategory->id,
        'poem_type_id' => $poemType->id,
        'title' => 'Nova in scena',
        'slug' => 'nova-in-scena',
        'content' => 'Verso',
        'description' => 'Una poesia nova',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);
    Poem::query()->create([
        'user_id' => $author->id,
        'poem_category_id' => $poemCategory->id,
        'poem_type_id' => $poemType->id,
        'title' => 'Nova bozza',
        'slug' => 'nova-bozza',
        'content' => 'Bozza',
        'description' => 'Non visibile',
        'language' => 'it',
        'status' => 'draft',
        'is_public' => true,
    ]);

    $eventCategory = EventCategory::query()->create([
        'slug' => 'search-events',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $eventCategory->translations()->create(['locale' => 'it', 'name' => 'Search events']);

    Event::query()->create([
        'user_id' => $author->id,
        'event_category_id' => $eventCategory->id,
        'title' => 'Nova Slam Night',
        'slug' => 'nova-slam-night',
        'description' => 'Evento nova',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);
    Event::query()->create([
        'user_id' => $author->id,
        'event_category_id' => $eventCategory->id,
        'title' => 'Nova draft night',
        'slug' => 'nova-draft-night',
        'description' => 'Bozza',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'draft',
        'is_public' => true,
    ]);

    Article::query()->create([
        'user_id' => $author->id,
        'title' => 'Nova sulla scena',
        'slug' => 'nova-sulla-scena',
        'content' => 'Articolo',
        'excerpt' => 'News nova',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $media = MediaItem::query()->create([
        'user_id' => $author->id,
        'type' => 'photo',
        'title' => 'Nova live',
        'description' => 'Foto nova',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/nova.jpg',
        'published_at' => now(),
        'uploaded_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/search?q=Nova');

    $response
        ->assertOk()
        ->assertJsonPath('data.query', 'Nova')
        ->assertJsonMissing(['email' => $matchingUser->email]);

    $groups = collect($response->json('data.groups'))->keyBy('type');

    expect($groups['people']['items'])->toHaveCount(1)
        ->and($groups['people']['items'][0]['href'])->toBe('/u/novapoet')
        ->and($groups['events']['items'])->toHaveCount(1)
        ->and($groups['events']['items'][0]['href'])->toBe('/events/nova-slam-night')
        ->and($groups['poems']['items'])->toHaveCount(1)
        ->and($groups['poems']['items'][0]['href'])->toBe('/poems/nova-in-scena')
        ->and($groups['articles']['items'])->toHaveCount(1)
        ->and($groups['articles']['items'][0]['href'])->toBe('/articles/nova-sulla-scena')
        ->and($groups['media']['items'])->toHaveCount(1)
        ->and($groups['media']['items'][0]['href'])->toBe('/media/'.$media->id)
        ->and($groups->has('groups'))->toBeFalse();
});

it('includes listed groups in the catalog only for verified users', function () {
    $owner = User::factory()->poetOrganizer()->create();

    $listed = Group::query()->create([
        'name' => 'Nova Community',
        'slug' => 'nova-community',
        'description' => 'Pagina nova',
        'visibility' => 'public',
        'is_listed' => true,
        'group_category_id' => groupTestCategory()->id,
        'is_active' => true,
    ]);
    $privateListed = Group::query()->create([
        'name' => 'Nova Circolo',
        'slug' => 'nova-circolo',
        'description' => 'Privato listed',
        'visibility' => 'private',
        'is_listed' => true,
        'group_category_id' => groupTestCategory()->id,
        'is_active' => true,
    ]);
    Group::query()->create([
        'name' => 'Nova Hidden',
        'slug' => 'nova-hidden',
        'description' => 'Privato unlisted',
        'visibility' => 'private',
        'is_listed' => false,
        'group_category_id' => groupTestCategory()->id,
        'is_active' => true,
    ]);

    $guestGroups = collect($this->getJson('/api/v1/search?q=Nova')->assertOk()->json('data.groups'))->keyBy('type');
    expect($guestGroups->has('groups'))->toBeFalse();

    Sanctum::actingAs($owner);
    $groups = collect($this->getJson('/api/v1/search?q=nova')->assertOk()->json('data.groups'))->keyBy('type');
    $hrefs = collect($groups['groups']['items'])->pluck('href')->all();

    expect($groups['groups']['href'])->toBe('/groups?q=nova')
        ->and($hrefs)->toContain('/groups/'.$listed->slug)
        ->and($hrefs)->toContain('/groups/'.$privateListed->slug)
        ->and($hrefs)->not->toContain('/groups/nova-hidden');
});

it('rejects catalog searches shorter than two characters', function () {
    $this->getJson('/api/v1/search?q=N')->assertUnprocessable();
});
