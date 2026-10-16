<?php

use Domain\Audit\Models\AuditLog;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

function makeEmailVerifyPayload(User $user, ?int $expires = null): array
{
    $expiresAt = $expires ?? now()->addMinutes(60)->timestamp;
    $hash = sha1($user->getEmailForVerification());
    $signature = hash_hmac(
        'sha256',
        "{$user->id}|{$hash}|{$expiresAt}",
        config('app.key')
    );

    return [
        'id' => $user->id,
        'hash' => $hash,
        'expires' => $expiresAt,
        'signature' => $signature,
    ];
}

it('registers a user and returns a bearer token with profile payload', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/register', [
        'email' => 'new-user@example.com',
        'password' => 'super-secure-password',
        'password_confirmation' => 'super-secure-password',
        'nickname' => 'new_user',
        'display_name' => 'New User',
        'account_type' => 'audience',
        'preferred_locale' => 'it',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'new-user@example.com')
        ->assertJsonPath('data.user.email_verified', false)
        ->assertJsonPath('data.user.profile.nickname', 'new_user')
        ->assertJsonPath('errors', null);

    $this->assertDatabaseHas('users', [
        'email' => 'new-user@example.com',
    ]);

    Notification::assertSentTo(
        User::query()->where('email', 'new-user@example.com')->firstOrFail(),
        VerifyEmail::class,
    );
});

it('logs in an existing user and returns token with user payload', function () {
    $user = User::factory()->create([
        'email' => 'login-user@example.com',
        'password' => Hash::make('password1234'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'login-user@example.com',
        'password' => 'password1234',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.public_id', $user->public_id)
        ->assertJsonPath('data.user.email', 'login-user@example.com')
        ->assertJsonPath('errors', null);
});

it('rejects login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'wrong-login@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'wrong-login@example.com',
        'password' => 'bad-password',
    ]);

    $response->assertStatus(401);
});

it('rejects login for suspended or banned users', function (string $status) {
    User::factory()->create([
        'email' => $status.'-user@example.com',
        'password' => Hash::make('password1234'),
        'status' => $status,
    ]);

    $this->postJson('/api/v1/login', [
        'email' => $status.'-user@example.com',
        'password' => 'password1234',
    ])->assertUnauthorized();
})->with(['suspended', 'banned']);

it('returns the authenticated user on me endpoint', function () {
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin']
    );
    $user->roles()->syncWithoutDetaching([$role->id]);
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/me');

    $response
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.public_id', $user->public_id)
        ->assertJsonPath('data.user.roles.0', 'admin')
        ->assertJsonPath('data.user.account_type', 'audience')
        ->assertJsonPath('errors', null);
});

it('requires authentication for me endpoint', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);
});

it('returns dashboard widgets payload for authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $poem = Poem::query()->create([
        'user_id' => $user->id,
        'title' => 'Notte di slam',
        'slug' => 'notte-di-slam',
        'content' => 'Versi',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);

    $deletedPoem = Poem::query()->create([
        'user_id' => $user->id,
        'title' => 'Poesia rimossa',
        'slug' => 'poesia-rimossa',
        'content' => 'Versi',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ]);
    $deletedPoem->delete();

    $log = fn (string $event, string $type, int $id, int $minutesAgo, array $newValues = []) => AuditLog::query()->create([
        'user_id' => $user->id,
        'event' => $event,
        'auditable_type' => $type,
        'auditable_id' => $id,
        'new_values' => $newValues ?: null,
        'created_at' => now()->subMinutes($minutesAgo),
    ]);

    $log('poem.created', Poem::class, $poem->id, 5);
    $log('event.updated', User::class, $user->id, 4, ['title' => 'Serata al Leoncavallo']);
    $log('poem.deleted', Poem::class, $deletedPoem->id, 3);
    $log('auth.login', User::class, $user->id, 2);
    $log('chat.thread_read', User::class, $user->id, 1);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/me/dashboard-widgets?activities_feed=mine');

    $response
        ->assertOk()
        ->assertJsonPath('errors', null)
        ->assertJsonPath('data.recent_activities_feed', 'mine')
        ->assertJsonPath('data.widgets.0.id', 'poems')
        ->assertJsonPath('data.widgets.1.id', 'events')
        ->assertJsonPath('data.widgets.2.id', 'views')
        ->assertJsonPath('data.widgets.3.id', 'likes')
        ->assertJsonPath('data.recent_activities.current_page', 1)
        ->assertJsonPath('data.recent_activities.total', 3)
        ->assertJsonCount(3, 'data.recent_activities.items')
        ->assertJsonPath('data.recent_activities.items.0.action', 'deleted')
        ->assertJsonPath('data.recent_activities.items.0.title', 'Poesia rimossa')
        ->assertJsonPath('data.recent_activities.items.0.url', null)
        ->assertJsonPath('data.recent_activities.items.1.type', 'event')
        ->assertJsonPath('data.recent_activities.items.1.title', 'Serata al Leoncavallo')
        ->assertJsonPath('data.recent_activities.items.2.label', __('home.dashboard_page.activity_labels.poem_created'))
        ->assertJsonPath('data.recent_activities.items.2.title', 'Notte di slam')
        ->assertJsonPath('data.recent_activities.items.2.url', '/poems/notte-di-slam');
});

it('requires authentication for dashboard widgets endpoint', function () {
    $this->getJson('/api/v1/me/dashboard-widgets')->assertStatus(401);
});

it('logs out the current token only', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('current')->plainTextToken;
    $user->createToken('second')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->postJson('/api/v1/logout');

    $response
        ->assertOk()
        ->assertJsonPath('errors', null);

    expect($user->fresh()->tokens()->count())->toBe(1);
});

it('sends password reset link for an existing user', function () {
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'reset@example.com',
    ]);

    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'reset@example.com',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('errors', null);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets password and revokes existing api tokens', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'reset-flow@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $user->createToken('old-token')->plainTextToken;
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/reset-password', [
        'token' => $token,
        'email' => 'reset-flow@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('errors', null);

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('verifies email through signed payload', function () {
    $user = User::factory()->unverified()->create();
    $payload = makeEmailVerifyPayload($user);

    $response = $this->postJson('/api/v1/email/verify', $payload);

    $response
        ->assertOk()
        ->assertJsonPath('errors', null);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects expired verify email payload', function () {
    Carbon::setTestNow(now());
    $user = User::factory()->unverified()->create();
    $payload = makeEmailVerifyPayload($user, now()->subMinute()->timestamp);

    $response = $this->postJson('/api/v1/email/verify', $payload);

    $response->assertStatus(422);
});

it('requires authentication to send verification notification', function () {
    $this->postJson('/api/v1/email/verification-notification')->assertStatus(401);
});

it('sends verification notification for authenticated unverified user', function () {
    Notification::fake();
    /** @var User $user */
    $user = User::factory()->unverified()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/email/verification-notification');

    $response
        ->assertOk()
        ->assertJsonPath('errors', null);

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        expect($mail->view)->toBe('emails.auth.verify-email')
            ->and($mail->viewData['verificationUrl'] ?? null)->toContain('/verify-email?');

        return true;
    });
});
