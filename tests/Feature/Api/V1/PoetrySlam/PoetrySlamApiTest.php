<?php

use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\PoetrySlam\Models\PoetrySlam;
use Domain\PoetrySlam\Models\PoetrySlamEntry;
use Domain\PoetrySlam\Models\PoetrySlamRound;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function poetrySlamTestAuthor(): User
{
    return User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
}

function poetrySlamCategory(): EventCategory
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



function nonPoetrySlamCategory(): EventCategory
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

function makePoetrySlamEvent(User $user, EventCategory $category, array $overrides = []): Event
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
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();

    $event = makePoetrySlamEvent($user, $category, [
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
        ->assertJsonPath('data.poetry_slam.entries_count', 0);

    $this->assertDatabaseHas('poetry_slams', [
        'event_id' => $event->id,
        'judging_mode' => 'jury',
        'slots_limit' => 12,
    ]);
});

it('imports event participant invites into poetry slam entries when creating the slam', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $participantUser = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);

    $category = poetrySlamCategory();

    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Milano',
        'slug' => 'poetry-slam-milano',
    ]);
    $event->participantInvites()->create([
        'user_id' => $participantUser->id,
        'email' => $participantUser->email,
        'role' => 'performer',
        'invite_type' => 'user',
        'status' => 'accepted',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.store', $event), [
        'rules_summary' => 'Qualificazioni e finale.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.poetry_slam.entries_count', 1);

    $poetrySlamId = $response->json('data.poetry_slam.id');

    $this->assertDatabaseHas('poetry_slam_entries', [
        'poetry_slam_id' => $poetrySlamId,
        'user_id' => $participantUser->id,
        'entry_source' => 'event_participant',
        'status' => 'registered',
    ]);
});

it('rejects creation for a non poetry slam event', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = nonPoetrySlamCategory();

    $event = makePoetrySlamEvent($user, $category, [
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
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();

    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Torino',
        'slug' => 'poetry-slam-torino',
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
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();

    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Roma',
        'slug' => 'poetry-slam-roma',
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
        ->assertJsonPath('data.poetry_slam.settings.rounds', 2)
        ->assertJsonPath('data.poetry_slam.entries_count', 0);
});

it('returns not found when event has no poetry slam', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();

    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Napoli',
        'slug' => 'poetry-slam-napoli',
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.show', $event));

    $response->assertNotFound();
});

it('lists poetry slam entries for an event', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $internalParticipant = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $internalParticipant->profile()->update([
        'display_name' => 'Marta Bianchi',
    ]);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Brescia',
        'slug' => 'poetry-slam-brescia',
    ]);

    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 8,
    ]);

    PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'event_participant',
        'display_name' => 'Giulia Rossi',
        'status' => 'registered',
        'performance_order' => 2,
    ]);

    PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'site_user',
        'user_id' => $internalParticipant->id,
        'display_name' => 'Marta Bianchi',
        'email' => $internalParticipant->email,
        'status' => 'confirmed',
        'performance_order' => 1,
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.entries.index', $event));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data.entries')
        ->assertJsonPath('data.entries.0.entry_source', 'site_user')
        ->assertJsonPath('data.entries.0.display_name', 'Marta Bianchi')
        ->assertJsonPath('data.entries.1.entry_source', 'event_participant');
});

it('adds a site user entry to a poetry slam', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $participantUser = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $participantUser->profile()->update([
        'display_name' => 'Partecipante Interno',
    ]);
    $participantUser->profile()->update([
        'display_name' => 'Elena Verdi',
    ]);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Como',
        'slug' => 'poetry-slam-como',
    ]);

    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'draft',
        'slots_limit' => 10,
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.entries.store', $event), [
        'entry_source' => 'site_user',
        'user_id' => $participantUser->id,
        'notes' => 'Wild card',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.entry.poetry_slam_id', $poetrySlam->id)
        ->assertJsonPath('data.entry.entry_source', 'site_user')
        ->assertJsonPath('data.entry.user_id', $participantUser->id)
        ->assertJsonPath('data.entry.display_name', 'Elena Verdi')
        ->assertJsonPath('data.entry.notes', 'Wild card');

    $this->assertDatabaseHas('poetry_slam_entries', [
        'poetry_slam_id' => $poetrySlam->id,
        'user_id' => $participantUser->id,
        'entry_source' => 'site_user',
    ]);
});

it('adds an external entry to a poetry slam', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category, [
        'title' => 'Poetry Slam Pavia',
        'slug' => 'poetry-slam-pavia',
    ]);

    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'draft',
        'slots_limit' => 10,
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.entries.store', $event), [
        'entry_source' => 'external',
        'display_name' => 'Ospite Speciale',
        'email' => 'ospite@example.test',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.entry.poetry_slam_id', $poetrySlam->id)
        ->assertJsonPath('data.entry.entry_source', 'external')
        ->assertJsonPath('data.entry.display_name', 'Ospite Speciale')
        ->assertJsonPath('data.entry.email', 'ospite@example.test');

    $this->assertDatabaseHas('poetry_slam_entries', [
        'poetry_slam_id' => $poetrySlam->id,
        'display_name' => 'Ospite Speciale',
        'entry_source' => 'external',
    ]);
});

it('rejects duplicate site user entries', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $participantUser = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'draft',
        'slots_limit' => 10,
    ]);

    PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'site_user',
        'user_id' => $participantUser->id,
        'display_name' => 'Partecipante Interno',
        'email' => $participantUser->email,
        'status' => 'registered',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.entries.store', $event), [
        'entry_source' => 'site_user',
        'user_id' => $participantUser->id,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'user_id',
                ],
            ],
        ]);
});

it('rejects duplicate site user entries already imported from the event', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $participantUser = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $participantUser->profile()->update([
        'display_name' => 'Partecipante Sync',
    ]);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    $invite = $event->participantInvites()->create([
        'user_id' => $participantUser->id,
        'email' => $participantUser->email,
        'role' => 'performer',
        'invite_type' => 'user',
        'status' => 'accepted',
    ]);

    PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'event_participant',
        'event_participant_invite_id' => $invite->id,
        'user_id' => $participantUser->id,
        'display_name' => 'Partecipante Sync',
        'email' => $participantUser->email,
        'status' => 'registered',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.entries.store', $event), [
        'entry_source' => 'site_user',
        'user_id' => $participantUser->id,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'user_id',
                ],
            ],
        ]);
});

it('rejects adding entries when slots limit is reached', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 1,
    ]);

    PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Partecipante 1',
        'status' => 'registered',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.entries.store', $event), [
        'entry_source' => 'external',
        'display_name' => 'Partecipante 2',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'entry',
                ],
            ],
        ]);
});

it('deletes a manual poetry slam entry', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Guest Poet',
        'status' => 'registered',
    ]);

    $response = $this->deleteJson(route('api.v1.poetry-slam.entries.delete', [
        'event' => $event,
        'entry' => $entry,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('poetry_slam_entries', [
        'id' => $entry->id,
    ]);
});

it('rejects deleting entries imported from the event', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $participantUser = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);
    $participantUser->profile()->update([
        'display_name' => 'Partecipante Evento',
    ]);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    $invite = $event->participantInvites()->create([
        'user_id' => $participantUser->id,
        'email' => $participantUser->email,
        'role' => 'performer',
        'invite_type' => 'user',
        'status' => 'accepted',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'event_participant',
        'event_participant_invite_id' => $invite->id,
        'user_id' => $participantUser->id,
        'display_name' => 'Partecipante Evento',
        'email' => $participantUser->email,
        'status' => 'registered',
    ]);

    $response = $this->deleteJson(route('api.v1.poetry-slam.entries.delete', [
        'event' => $event,
        'entry' => $entry,
    ]));

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'entry',
                ],
            ],
        ]);
});

it('updates status notes and performance order for a poetry slam entry', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Ospite',
        'status' => 'registered',
    ]);

    $response = $this->patchJson(route('api.v1.poetry-slam.entries.update', [
        'event' => $event,
        'entry' => $entry,
    ]), [
        'status' => 'confirmed',
        'notes' => 'Arriva da fuori citta',
        'performance_order' => 3,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.entry.status', 'confirmed')
        ->assertJsonPath('data.entry.notes', 'Arriva da fuori citta')
        ->assertJsonPath('data.entry.performance_order', 3);

    $this->assertDatabaseHas('poetry_slam_entries', [
        'id' => $entry->id,
        'status' => 'confirmed',
        'notes' => 'Arriva da fuori citta',
        'performance_order' => 3,
    ]);
});

it('can clear notes and performance order for a poetry slam entry', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Ospite',
        'notes' => 'Da confermare',
        'status' => 'registered',
        'performance_order' => 4,
    ]);

    $response = $this->patchJson(route('api.v1.poetry-slam.entries.update', [
        'event' => $event,
        'entry' => $entry,
    ]), [
        'notes' => null,
        'performance_order' => null,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.entry.notes', null)
        ->assertJsonPath('data.entry.performance_order', null);

    $this->assertDatabaseHas('poetry_slam_entries', [
        'id' => $entry->id,
        'notes' => null,
        'performance_order' => null,
    ]);
});

it('rejects duplicate performance order inside the same poetry slam', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
        'performance_order' => 2,
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Due',
        'status' => 'registered',
    ]);

    $response = $this->patchJson(route('api.v1.poetry-slam.entries.update', [
        'event' => $event,
        'entry' => $entry,
    ]), [
        'performance_order' => 2,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'performance_order',
                ],
            ],
        ]);
});

it('updates event participant entries for operational management fields', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $participantUser = User::factory()->poetOrganizer()->create([
        'email_verified_at' => now(),
    ]);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
        'slots_limit' => 10,
    ]);

    $invite = $event->participantInvites()->create([
        'user_id' => $participantUser->id,
        'email' => $participantUser->email,
        'role' => 'performer',
        'invite_type' => 'user',
        'status' => 'accepted',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'event_participant',
        'event_participant_invite_id' => $invite->id,
        'user_id' => $participantUser->id,
        'display_name' => 'Partecipante Evento',
        'email' => $participantUser->email,
        'status' => 'registered',
    ]);

    $response = $this->patchJson(route('api.v1.poetry-slam.entries.update', [
        'event' => $event,
        'entry' => $entry,
    ]), [
        'status' => 'checked_in',
        'notes' => 'Presente in venue',
        'performance_order' => 1,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.entry.entry_source', 'event_participant')
        ->assertJsonPath('data.entry.status', 'checked_in')
        ->assertJsonPath('data.entry.notes', 'Presente in venue')
        ->assertJsonPath('data.entry.performance_order', 1);
});

it('lists poetry slam rounds for an event', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 2,
        'name' => 'Finale',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'draft',
    ]);

    \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Qualificazioni',
        'judges_count' => 2,
        'scoring_type' => 'trimmed_sum',
        'status' => 'active',
        'started_at' => now(),
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.rounds.index', $event));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data.rounds')
        ->assertJsonPath('data.rounds.0.round_number', 1)
        ->assertJsonPath('data.rounds.0.name', 'Qualificazioni')
        ->assertJsonPath('data.rounds.0.judges_count', 2)
        ->assertJsonPath('data.rounds.0.scoring_type', 'trimmed_sum')
        ->assertJsonPath('data.rounds.0.effective_scoring_type', 'sum')
        ->assertJsonPath('data.rounds.0.advancement_mode', 'all_entries')
        ->assertJsonPath('data.rounds.0.advancement_value', null)
        ->assertJsonPath('data.rounds.0.advancement_based_on', 'round_result')
        ->assertJsonPath('data.rounds.1.round_number', 2);
});

it('creates a poetry slam round with default judges count and scoring type', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.rounds.store', $event), [
        'round_number' => 1,
        'name' => 'Qualificazioni',
        'status' => 'active',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.round.poetry_slam_id', $poetrySlam->id)
        ->assertJsonPath('data.round.round_number', 1)
        ->assertJsonPath('data.round.name', 'Qualificazioni')
        ->assertJsonPath('data.round.judges_count', 5)
        ->assertJsonPath('data.round.scoring_type', 'trimmed_sum')
        ->assertJsonPath('data.round.effective_scoring_type', 'trimmed_sum')
        ->assertJsonPath('data.round.advancement_mode', 'all_entries')
        ->assertJsonPath('data.round.advancement_value', null)
        ->assertJsonPath('data.round.advancement_based_on', 'round_result')
        ->assertJsonPath('data.round.status', 'active');

    $this->assertDatabaseHas('poetry_slam_rounds', [
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'active',
    ]);
});

it('falls back to sum when trimmed sum has fewer than three judges', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.rounds.store', $event), [
        'round_number' => 1,
        'judges_count' => 2,
        'scoring_type' => 'trimmed_sum',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.round.judges_count', 2)
        ->assertJsonPath('data.round.scoring_type', 'trimmed_sum')
        ->assertJsonPath('data.round.effective_scoring_type', 'sum');
});

it('creates a poetry slam round with advancement configuration', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.rounds.store', $event), [
        'round_number' => 2,
        'advancement_mode' => 'top_n',
        'advancement_value' => 8,
        'advancement_based_on' => 'cumulative_result',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.round.advancement_mode', 'top_n')
        ->assertJsonPath('data.round.advancement_value', 8)
        ->assertJsonPath('data.round.advancement_based_on', 'cumulative_result');
});

it('rejects duplicate poetry slam round numbers', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'draft',
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.rounds.store', $event), [
        'round_number' => 1,
        'name' => 'Round 1 bis',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'round_number',
                ],
            ],
        ]);
});

it('rejects creating a second active poetry slam round', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Qualificazioni',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'active',
        'started_at' => now(),
    ]);

    $response = $this->postJson(route('api.v1.poetry-slam.rounds.store', $event), [
        'round_number' => 2,
        'name' => 'Finale',
        'status' => 'active',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'status',
                ],
            ],
        ]);
});

it('updates a poetry slam round', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $round = \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'draft',
    ]);

    $response = $this->patchJson(route('api.v1.poetry-slam.rounds.update', [
        'event' => $event,
        'round' => $round,
    ]), [
        'round_number' => 2,
        'name' => 'Semifinale',
        'judges_count' => 3,
        'scoring_type' => 'best',
        'advancement_mode' => 'manual_selection',
        'advancement_based_on' => 'round_result',
        'status' => 'completed',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.round.round_number', 2)
        ->assertJsonPath('data.round.name', 'Semifinale')
        ->assertJsonPath('data.round.judges_count', 3)
        ->assertJsonPath('data.round.scoring_type', 'best')
        ->assertJsonPath('data.round.effective_scoring_type', 'best')
        ->assertJsonPath('data.round.advancement_mode', 'manual_selection')
        ->assertJsonPath('data.round.advancement_value', null)
        ->assertJsonPath('data.round.advancement_based_on', 'round_result')
        ->assertJsonPath('data.round.status', 'completed');

    $this->assertDatabaseHas('poetry_slam_rounds', [
        'id' => $round->id,
        'round_number' => 2,
        'name' => 'Semifinale',
        'judges_count' => 3,
        'scoring_type' => 'best',
        'advancement_mode' => 'manual_selection',
        'advancement_value' => null,
        'advancement_based_on' => 'round_result',
        'status' => 'completed',
    ]);
});

it('rejects updating a round to a duplicate round number', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'draft',
    ]);

    $round = \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 2,
        'name' => 'Round 2',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'draft',
    ]);

    $response = $this->patchJson(route('api.v1.poetry-slam.rounds.update', [
        'event' => $event,
        'round' => $round,
    ]), [
        'round_number' => 1,
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'round_number',
                ],
            ],
        ]);
});

it('deletes a poetry slam round', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $round = \Domain\PoetrySlam\Models\PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'draft',
    ]);

    $response = $this->deleteJson(route('api.v1.poetry-slam.rounds.delete', [
        'event' => $event,
        'round' => $round,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('poetry_slam_rounds', [
        'id' => $round->id,
    ]);
});

it('lists pending advancement decisions for a manual selection round', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
        'performance_order' => 1,
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Semifinale',
        'judges_count' => 5,
        'scoring_type' => 'sum',
        'advancement_mode' => 'manual_selection',
        'advancement_based_on' => 'round_result',
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.rounds.advancement-decisions.index', [
        'event' => $event,
        'round' => $round,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.round.advancement_mode', 'manual_selection')
        ->assertJsonCount(1, 'data.decisions')
        ->assertJsonPath('data.decisions.0.entry_id', $entry->id)
        ->assertJsonPath('data.decisions.0.decision_status', 'pending')
        ->assertJsonPath('data.decisions.0.note', null);
});

it('upserts a manual advancement decision for a round entry', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Semifinale',
        'judges_count' => 5,
        'scoring_type' => 'sum',
        'advancement_mode' => 'manual_selection',
        'advancement_based_on' => 'round_result',
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $response = $this->putJson(route('api.v1.poetry-slam.rounds.entries.advancement-decision.upsert', [
        'event' => $event,
        'round' => $round,
        'entry' => $entry,
    ]), [
        'decision_status' => 'advanced',
        'note' => 'Passa al turno successivo',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.decision.entry_id', $entry->id)
        ->assertJsonPath('data.decision.decision_status', 'advanced')
        ->assertJsonPath('data.decision.note', 'Passa al turno successivo');

    $this->assertDatabaseHas('poetry_slam_round_advancement_decisions', [
        'poetry_slam_round_id' => $round->id,
        'poetry_slam_entry_id' => $entry->id,
        'decision_status' => 'advanced',
    ]);
});

it('rejects manual advancement decisions when the round is not manual selection', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'sum',
        'advancement_mode' => 'all_entries',
        'advancement_based_on' => 'round_result',
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $response = $this->putJson(route('api.v1.poetry-slam.rounds.entries.advancement-decision.upsert', [
        'event' => $event,
        'round' => $round,
        'entry' => $entry,
    ]), [
        'decision_status' => 'advanced',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'decision_status',
                ],
            ],
        ]);
});

it('lists round leaderboard with ranks for complete and incomplete entries', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entryOne = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
        'performance_order' => 1,
    ]);

    $entryTwo = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Due',
        'status' => 'registered',
        'performance_order' => 2,
    ]);

    $entryThree = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Tre',
        'status' => 'registered',
        'performance_order' => 3,
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 3,
        'scoring_type' => 'sum',
        'status' => 'active',
        'started_at' => now(),
    ]);

    foreach ([[1, 9], [2, 9], [3, 9]] as [$judgeNumber, $score]) {
        \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
            'poetry_slam_round_id' => $round->id,
            'poetry_slam_entry_id' => $entryTwo->id,
            'judge_number' => $judgeNumber,
            'score' => $score,
        ]);
    }

    foreach ([[1, 8], [2, 8], [3, 8]] as [$judgeNumber, $score]) {
        \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
            'poetry_slam_round_id' => $round->id,
            'poetry_slam_entry_id' => $entryOne->id,
            'judge_number' => $judgeNumber,
            'score' => $score,
        ]);
    }

    \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
        'poetry_slam_round_id' => $round->id,
        'poetry_slam_entry_id' => $entryThree->id,
        'judge_number' => 1,
        'score' => 10,
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.rounds.leaderboard', [
        'event' => $event,
        'round' => $round,
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data.leaderboard')
        ->assertJsonPath('data.leaderboard.0.entry_id', $entryTwo->id)
        ->assertJsonPath('data.leaderboard.0.rank', 1)
        ->assertJsonPath('data.leaderboard.0.result_value', 27)
        ->assertJsonPath('data.leaderboard.1.entry_id', $entryOne->id)
        ->assertJsonPath('data.leaderboard.1.rank', 2)
        ->assertJsonPath('data.leaderboard.1.result_value', 24)
        ->assertJsonPath('data.leaderboard.2.entry_id', $entryThree->id)
        ->assertJsonPath('data.leaderboard.2.rank', null)
        ->assertJsonPath('data.leaderboard.2.result_value', null);
});

it('lists cumulative poetry slam leaderboard across rounds', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entryOne = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
        'performance_order' => 2,
    ]);

    $entryTwo = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Due',
        'status' => 'registered',
        'performance_order' => 1,
    ]);

    $roundOne = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 3,
        'scoring_type' => 'sum',
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $roundTwo = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 2,
        'name' => 'Round 2',
        'judges_count' => 3,
        'scoring_type' => 'average',
        'status' => 'active',
        'started_at' => now(),
    ]);

    foreach ([[1, 9], [2, 9], [3, 9]] as [$judgeNumber, $score]) {
        \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
            'poetry_slam_round_id' => $roundOne->id,
            'poetry_slam_entry_id' => $entryOne->id,
            'judge_number' => $judgeNumber,
            'score' => $score,
        ]);
    }

    foreach ([[1, 8], [2, 8], [3, 8]] as [$judgeNumber, $score]) {
        \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
            'poetry_slam_round_id' => $roundOne->id,
            'poetry_slam_entry_id' => $entryTwo->id,
            'judge_number' => $judgeNumber,
            'score' => $score,
        ]);
    }

    foreach ([[1, 10], [2, 9], [3, 8]] as [$judgeNumber, $score]) {
        \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
            'poetry_slam_round_id' => $roundTwo->id,
            'poetry_slam_entry_id' => $entryTwo->id,
            'judge_number' => $judgeNumber,
            'score' => $score,
        ]);
    }

    $response = $this->getJson(route('api.v1.poetry-slam.leaderboard', [
        'event' => $event,
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data.leaderboard')
        ->assertJsonPath('data.leaderboard.0.entry_id', $entryTwo->id)
        ->assertJsonPath('data.leaderboard.0.rank', 1)
        ->assertJsonPath('data.leaderboard.0.completed_rounds_count', 2)
        ->assertJsonPath('data.leaderboard.0.cumulative_result_value', 33)
        ->assertJsonPath('data.leaderboard.0.round_results.0.result_value', 24)
        ->assertJsonPath('data.leaderboard.0.round_results.1.result_value', 9)
        ->assertJsonPath('data.leaderboard.1.entry_id', $entryOne->id)
        ->assertJsonPath('data.leaderboard.1.rank', 2)
        ->assertJsonPath('data.leaderboard.1.completed_rounds_count', 1)
        ->assertJsonPath('data.leaderboard.1.cumulative_result_value', 27);
});

it('stores anonymous scores for a round entry and calculates trimmed sum', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'trimmed_sum',
        'status' => 'active',
        'started_at' => now(),
    ]);

    $response = $this->putJson(route('api.v1.poetry-slam.rounds.entries.scores.upsert', [
        'event' => $event,
        'round' => $round,
        'entry' => $entry,
    ]), [
        'scores' => [
            ['judge_number' => 1, 'score' => 9.5],
            ['judge_number' => 2, 'score' => 8.7],
            ['judge_number' => 3, 'score' => 10.0],
            ['judge_number' => 4, 'score' => 9.2],
            ['judge_number' => 5, 'score' => 9.8],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.result.judges_count', 5)
        ->assertJsonPath('data.result.scores_count', 5)
        ->assertJsonPath('data.result.is_complete', true)
        ->assertJsonPath('data.result.scoring_type', 'trimmed_sum')
        ->assertJsonPath('data.result.effective_scoring_type', 'trimmed_sum')
        ->assertJsonPath('data.result.raw_scores.0', 9.5)
        ->assertJsonPath('data.result.discarded_scores.0', 8.7)
        ->assertJsonPath('data.result.discarded_scores.1', 10)
        ->assertJsonPath('data.result.counted_scores.0', 9.2)
        ->assertJsonPath('data.result.counted_scores.2', 9.8)
        ->assertJsonPath('data.result.result_value', 28.5);

    $this->assertDatabaseHas('poetry_slam_scores', [
        'poetry_slam_round_id' => $round->id,
        'poetry_slam_entry_id' => $entry->id,
        'judge_number' => 3,
        'score' => 10.00,
    ]);
});

it('falls back to sum for round score calculation when judges are fewer than three', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Due',
        'status' => 'registered',
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 2,
        'scoring_type' => 'trimmed_sum',
        'status' => 'active',
        'started_at' => now(),
    ]);

    $response = $this->putJson(route('api.v1.poetry-slam.rounds.entries.scores.upsert', [
        'event' => $event,
        'round' => $round,
        'entry' => $entry,
    ]), [
        'scores' => [
            ['judge_number' => 1, 'score' => 9.5],
            ['judge_number' => 2, 'score' => 8.5],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.result.effective_scoring_type', 'sum')
        ->assertJsonPath('data.result.result_value', 18);
});

it('lists round score results for all entries', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entryOne = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
        'performance_order' => 2,
    ]);

    $entryTwo = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Due',
        'status' => 'registered',
        'performance_order' => 1,
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 3,
        'scoring_type' => 'average',
        'status' => 'active',
        'started_at' => now(),
    ]);

    foreach ([[1, 8.0], [2, 9.0], [3, 10.0]] as [$judgeNumber, $score]) {
        \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
            'poetry_slam_round_id' => $round->id,
            'poetry_slam_entry_id' => $entryTwo->id,
            'judge_number' => $judgeNumber,
            'score' => $score,
        ]);
    }

    \Domain\PoetrySlam\Models\PoetrySlamScore::query()->create([
        'poetry_slam_round_id' => $round->id,
        'poetry_slam_entry_id' => $entryOne->id,
        'judge_number' => 1,
        'score' => 7.5,
    ]);

    $response = $this->getJson(route('api.v1.poetry-slam.rounds.scores.index', [
        'event' => $event,
        'round' => $round,
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.round.effective_scoring_type', 'average')
        ->assertJsonCount(2, 'data.results')
        ->assertJsonPath('data.results.0.entry_id', $entryTwo->id)
        ->assertJsonPath('data.results.0.is_complete', true)
        ->assertJsonPath('data.results.0.result_value', 9)
        ->assertJsonPath('data.results.1.entry_id', $entryOne->id)
        ->assertJsonPath('data.results.1.is_complete', false)
        ->assertJsonPath('data.results.1.result_value', null);
});

it('rejects score submission for draft rounds', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'sum',
        'status' => 'draft',
    ]);

    $response = $this->putJson(route('api.v1.poetry-slam.rounds.entries.scores.upsert', [
        'event' => $event,
        'round' => $round,
        'entry' => $entry,
    ]), [
        'scores' => [
            ['judge_number' => 1, 'score' => 9.5],
        ],
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'round',
                ],
            ],
        ]);
});

it('rejects duplicate judge numbers when submitting scores', function () {
    $user = poetrySlamTestAuthor();
    Sanctum::actingAs($user);

    $category = poetrySlamCategory();
    $event = makePoetrySlamEvent($user, $category);
    $poetrySlam = PoetrySlam::query()->create([
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    $entry = PoetrySlamEntry::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'entry_source' => 'external',
        'display_name' => 'Poeta Uno',
        'status' => 'registered',
    ]);

    $round = PoetrySlamRound::query()->create([
        'poetry_slam_id' => $poetrySlam->id,
        'round_number' => 1,
        'name' => 'Round 1',
        'judges_count' => 5,
        'scoring_type' => 'sum',
        'status' => 'active',
        'started_at' => now(),
    ]);

    $response = $this->putJson(route('api.v1.poetry-slam.rounds.entries.scores.upsert', [
        'event' => $event,
        'round' => $round,
        'entry' => $entry,
    ]), [
        'scores' => [
            ['judge_number' => 1, 'score' => 9.5],
            ['judge_number' => 1, 'score' => 8.0],
        ],
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure([
            'data',
            'meta',
            'errors' => [
                'message',
                'fields' => [
                    'scores',
                ],
            ],
        ]);
});
