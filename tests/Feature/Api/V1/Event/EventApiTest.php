<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
uses(RefreshDatabase::class);
function eventAuthor(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function eventPoetrySlamCategory(): EventCategory
{
    $category = EventCategory::query()->create([
        'slug' => 'poetry-slam',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Poetry Slam',
    ]);

    return $category;
}

function openMicCategory(): EventCategory
{
    $category = EventCategory::query()->create([
        'slug' => 'open-mic',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $category->translations()->create([
        'locale' => 'it',
        'name' => 'Open Mic',
    ]);

    return $category;
}

function poetrySlamEvent(User $user, EventCategory $category, array $overrides = []): Event
{
    return Event::query()->create(array_merge([
        'user_id' => $user->id,
        'event_category_id' => $category->id,
        'title' => 'Poetry Slam Test',
        'slug' => 'poetry-slam-test',
        'description' => 'Descrizione evento poetry slam.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'draft',
        'is_public' => true,
    ], $overrides));
}

it('creates a poetry slam for a poetry slam event', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $category = eventPoetrySlamCategory();

    $event = poetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Lecco',
        'slug' => 'poetry-slam-lecco',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.store', $event), [
        'rules_summary' => '3 minuti a performance, testi originali.',
        'slots_limit' => 12,
        'judging_mode' => 'jury',
        'settings' => [
            'rounds' => 2,
            'max_score' => 10,
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.poetry_slam.event_id', $event->id)
        ->assertJsonPath('data.poetry_slam.status', 'draft')
        ->assertJsonPath('data.poetry_slam.slots_limit', 12)
        ->assertJsonPath('data.poetry_slam.judging_mode', 'jury')
        ->assertJsonPath('data.poetry_slam.settings.rounds', 2);

    $this->assertDatabaseHas('poetry_slams', [
        'event_id' => $event->id,
        'judging_mode' => 'jury',
        'slots_limit' => 12,
    ]);
});

it('rejects creation for a non poetry slam event', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $category = openMicCategory();

    $event = poetrySlamEvent($user, $category, [
        'title' => 'Open Mic Bergamo',
        'slug' => 'open-mic-bergamo',
        'description' => 'Descrizione evento open mic.',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.store', $event), [
        'slots_limit' => 10,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'event_id',
                ],
            ],
        ]);

    $this->assertDatabaseCount('poetry_slams', 0);
});

it('rejects duplicate poetry slam creation for the same event', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $category = eventPoetrySlamCategory();

    $event = poetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Milano',
        'slug' => 'poetry-slam-milano',
    ]);

    PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'draft',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.store', $event), [
        'slots_limit' => 8,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'event_id',
                ],
            ],
        ]);
});

it('shows a poetry slam for an event', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $category = eventPoetrySlamCategory();

    $event = poetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Torino',
        'slug' => 'poetry-slam-torino',
    ]);

    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'rules_summary' => 'Finale a 2 round.',
        'slots_limit' => 16,
        'judging_mode' => 'audience',
        'settings' => [
            'rounds' => 2,
        ],
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.show', $event));

    $response
        ->assertOk()
        ->assertJsonPath('data.poetry_slam.id', $poetrySlam->id)
        ->assertJsonPath('data.poetry_slam.status', 'open')
        ->assertJsonPath('data.poetry_slam.judging_mode', 'audience')
        ->assertJsonPath('data.poetry_slam.settings.rounds', 2);
});

it('returns not found when event has no poetry slam', function () {
    $user = eventAuthor();
    Sanctum::actingAs($user);

    $category = eventPoetrySlamCategory();

    $event = poetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Roma',
        'slug' => 'poetry-slam-roma',
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.show', $event));

    $response->assertNotFound();
});
