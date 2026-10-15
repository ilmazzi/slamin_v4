<?php

use App\Mail\Notifications\ChatMessageMail;
use App\Mail\Notifications\LikesSummaryMail;
use App\Mail\Notifications\NotificationMail;
use App\Mail\Notifications\WeeklyDigestMail;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function emailTestAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->syncWithoutDetaching([
        Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Admin'])->id,
    ]);

    return $admin;
}

it('lets only admins send test emails', function () {
    Mail::fake();
    $recipient = User::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/admin/notification-emails/test', [
        'category' => NotificationCategory::Comments->value,
        'user_ids' => [$recipient->public_id],
    ])->assertForbidden();

    Mail::assertNothingSent();
});

it('sends a sample of every category right away, even to users who opted out', function (NotificationCategory $category, string $mailable) {
    Mail::fake();
    $recipient = User::factory()->create(['notification_email_opt_outs' => [$category->value]]);
    Sanctum::actingAs(emailTestAdmin());

    $this->postJson('/api/v1/admin/notification-emails/test', [
        'category' => $category->value,
        'user_ids' => [$recipient->public_id],
    ])
        ->assertOk()
        ->assertJsonPath('data.results.0.email', $recipient->email)
        ->assertJsonPath('data.results.0.sent', true)
        ->assertJsonPath('data.results.0.opted_out', true)
        ->assertJsonPath('data.results.0.uses_real_data', false);

    Mail::assertSent($mailable, fn ($mail) => $mail->hasTo($recipient->email));
})->with([
    [NotificationCategory::Comments, NotificationMail::class],
    [NotificationCategory::EventUpdates, NotificationMail::class],
    [NotificationCategory::ChatMessages, ChatMessageMail::class],
    [NotificationCategory::Likes, LikesSummaryMail::class],
    [NotificationCategory::WeeklyDigest, WeeklyDigestMail::class],
]);

it('uses the latest real notification of the category when the user has one', function () {
    $recipient = User::factory()->create();
    app(CreateNotificationAction::class)->execute(
        userId: $recipient->id,
        type: 'poem_commented',
        title: 'Giulia ha commentato',
        message: 'Bella poesia',
        data: [],
    );
    Mail::fake();
    Sanctum::actingAs(emailTestAdmin());

    $this->postJson('/api/v1/admin/notification-emails/test', [
        'category' => NotificationCategory::Comments->value,
        'user_ids' => [$recipient->public_id],
    ])
        ->assertOk()
        ->assertJsonPath('data.results.0.uses_real_data', true);

    Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail) => $mail->notification->title === 'Giulia ha commentato');
});

it('rejects unknown categories, unknown users and too many recipients', function () {
    Sanctum::actingAs(emailTestAdmin());
    $users = User::factory()->count(11)->create();

    $this->postJson('/api/v1/admin/notification-emails/test', [
        'category' => 'nope',
        'user_ids' => ['missing-id'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['category', 'user_ids.0'], 'errors.fields');

    $this->postJson('/api/v1/admin/notification-emails/test', [
        'category' => NotificationCategory::Comments->value,
        'user_ids' => $users->pluck('public_id')->all(),
    ])->assertUnprocessable()->assertJsonValidationErrors(['user_ids'], 'errors.fields');
});
