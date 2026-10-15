<?php

use App\Events\NotificationCreated;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventCategory;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function gigNotificationOrganizer(): User
{
    return User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
}

function gigNotificationGig(User $owner): Gig
{
    $category = EventCategory::query()->create([
        'slug' => 'notify-showcase',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $event = Event::query()->create([
        'user_id' => $owner->id,
        'event_category_id' => $category->id,
        'title' => 'Serata Notifiche',
        'slug' => 'serata-notifiche',
        'description' => 'Evento per test notifiche.',
        'schedule_mode' => 'fixed',
        'delivery_mode' => 'in_person',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    return $event->gigs()->create([
        'type' => 'poet_artist',
        'quantity' => 2,
        'filled_quantity' => 0,
        'status' => 'open',
        'opened_at' => now(),
        'created_by_user_id' => $owner->id,
        'updated_by_user_id' => $owner->id,
    ]);
}

it('notifies the gig owner in realtime when an application is received', function () {
    EventFacade::fake([NotificationCreated::class]);

    $owner = gigNotificationOrganizer();
    $candidate = gigNotificationOrganizer();
    $candidate->profile()->update(['display_name' => 'Luca Blu']);
    $candidate->refresh();
    $gig = gigNotificationGig($owner);

    Sanctum::actingAs($candidate);
    $this->postJson(route('api.v1.gigs.applications.store', $gig), ['message' => 'Ci sono.'])
        ->assertCreated();

    $notification = Notification::query()->where('user_id', $owner->id)->sole();

    expect($notification->type)->toBe('gig.application_received')
        ->and($notification->data['gig_id'])->toBe($gig->id)
        ->and($notification->data['gig_title'])->toBe('Serata Notifiche')
        ->and($notification->data['applicant_name'])->toBe('Luca Blu');

    EventFacade::assertDispatched(
        NotificationCreated::class,
        fn (NotificationCreated $event) => $event->notification->is($notification)
            && $event->broadcastOn()[0]->name === 'private-notifications.'.$owner->id,
    );
});

it('notifies the applicant when the organizer answers, only on real status changes', function () {
    EventFacade::fake([NotificationCreated::class]);

    $owner = gigNotificationOrganizer();
    $candidate = gigNotificationOrganizer();
    $gig = gigNotificationGig($owner);
    $application = GigApplication::query()->create([
        'gig_id' => $gig->id,
        'user_id' => $candidate->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($owner);
    $route = route('api.v1.gigs.applications.update', ['gig' => $gig, 'application' => $application]);

    $this->patchJson($route, ['status' => 'accepted'])->assertOk();
    $this->patchJson($route, ['status' => 'accepted'])->assertOk();

    $notifications = Notification::query()->where('user_id', $candidate->id)->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]->type)->toBe('gig.application_updated')
        ->and($notifications[0]->data['status'])->toBe('accepted')
        ->and($notifications[0]->data['gig_title'])->toBe('Serata Notifiche');

    EventFacade::assertDispatchedTimes(NotificationCreated::class, 1);
});

it('lets the recipient read the notification and keeps others out', function () {
    $owner = gigNotificationOrganizer();
    $stranger = gigNotificationOrganizer();
    $notification = Notification::query()->create([
        'user_id' => $owner->id,
        'type' => 'gig.application_received',
        'title' => 'Nuova candidatura',
        'message' => 'Test',
        'data' => ['gig_id' => 1],
    ]);

    Sanctum::actingAs($stranger);
    $this->patchJson(route('notifications.read', $notification))->assertNotFound();

    Sanctum::actingAs($owner);
    $this->getJson(route('notifications.unread-count'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1);
    $this->patchJson(route('notifications.read', $notification))->assertOk();
    $this->getJson(route('notifications.unread-count'))
        ->assertJsonPath('data.unread_count', 0);
});
