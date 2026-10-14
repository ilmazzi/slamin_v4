<?php

use Domain\Event\Models\Event;
use Domain\Moderation\Actions\GetModerationDashboardStatsAction;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function listQueryCount(callable $callback): int
{
    $count = 0;
    DB::listen(function () use (&$count): void {
        $count++;
    });

    $callback();

    return $count;
}

function createDirectThreadsWithMessages(User $viewer, int $threads): void
{
    foreach (range(1, $threads) as $index) {
        $other = User::factory()->create();

        Sanctum::actingAs($viewer);
        $threadId = test()->postJson('/api/v1/chat/direct-threads', ['user_id' => $other->id])->json('data.thread.id');

        Sanctum::actingAs($other);
        test()->postJson("/api/v1/chat/threads/{$threadId}/messages", ['content' => "Messaggio {$index}"])->assertCreated();
    }

    Sanctum::actingAs($viewer);
}

function createOwnedEvents(User $owner, int $events, int $offset = 0): void
{
    foreach (range(1, $events) as $index) {
        $number = $offset + $index;

        $event = Event::query()->create([
            'user_id' => $owner->id,
            'title' => "Evento {$number}",
            'slug' => "evento-perf-{$number}",
            'description' => 'Evento',
            'schedule_mode' => 'fixed',
            'delivery_mode' => 'online',
            'status' => 'pending_review',
            'is_public' => true,
        ]);

        $event->audienceInvites()->create([
            'user_id' => User::factory()->create()->id,
            'invite_type' => 'internal',
            'status' => 'pending',
        ]);
    }
}

it('lists chat threads with a query count independent from the number of threads', function () {
    $viewer = User::factory()->create();

    createDirectThreadsWithMessages($viewer, 2);
    $fewThreads = listQueryCount(fn () => $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonPath('data.threads.0.unread_count', 1));

    createDirectThreadsWithMessages($viewer, 3);
    $manyThreads = listQueryCount(fn () => $this->getJson('/api/v1/chat/threads')
        ->assertOk()
        ->assertJsonCount(5, 'data.threads'));

    expect($manyThreads)->toBe($fewThreads);
});

it('lists my events with a query count independent from the number of events', function () {
    $owner = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($owner);

    createOwnedEvents($owner, 2);
    $this->getJson('/api/v1/my/events')->assertOk();
    $fewEvents = listQueryCount(fn () => $this->getJson('/api/v1/my/events')->assertOk());

    createOwnedEvents($owner, 4, 2);
    $manyEvents = listQueryCount(fn () => $this->getJson('/api/v1/my/events')
        ->assertOk()
        ->assertJsonCount(6, 'data'));

    expect($manyEvents)->toBe($fewEvents);
});

it('rejects per_page values above the allowed maximum', function () {
    Sanctum::actingAs(User::factory()->poetOrganizer()->create(['email_verified_at' => now()]));

    $this->getJson('/api/v1/my/events?per_page=500')->assertUnprocessable();
});

it('computes the moderation dashboard with a fixed number of queries', function () {
    $admin = User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
    $role = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    createOwnedEvents($admin, 3);

    $stats = null;
    $queries = listQueryCount(function () use (&$stats): void {
        $stats = app(GetModerationDashboardStatsAction::class)->execute();
    });

    expect($queries)->toBe(5)
        ->and($stats['events']['pending_review_count'])->toBe(3)
        ->and($stats['poems']['published_count'])->toBe(0)
        ->and($stats['reports']['open_count'])->toBe(0);
});
