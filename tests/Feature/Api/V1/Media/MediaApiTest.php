<?php

use Domain\Media\Models\MediaCheckoutOrder;
use Domain\Media\Models\MediaFeaturePack;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Domain\Notification\Models\Notification;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function mediaPoetOrganizer(): User
{
    return User::factory()->poetOrganizer()->create();
}

function mediaAdmin(): User
{
    $user = User::factory()->poetOrganizer()->create();
    $role = Role::query()->firstOrCreate(
        ['name' => 'admin'],
        ['label' => 'Admin'],
    );
    $user->roles()->attach($role->id);

    return $user;
}

it('allows admin to update media settings and test peertube connection', function () {
    $admin = mediaAdmin();
    Sanctum::actingAs($admin);

    $this->patchJson('/api/v1/admin/media/settings', [
        'photo_limit' => 12,
        'video_limit' => 7,
        'peertube_base_url' => 'https://video.example.test',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
        'peertube_verify_ssl' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.photo_limit', 12)
        ->assertJsonPath('data.settings.video_limit', 7)
        ->assertJsonPath('data.settings.peertube_admin_password', null)
        ->assertJsonPath('data.settings.peertube_admin_password_set', true);

    $this->getJson('/api/v1/admin/media/settings')
        ->assertOk()
        ->assertJsonPath('data.settings.peertube_base_url', 'https://video.example.test')
        ->assertJsonPath('data.settings.peertube_admin_username', 'admin')
        ->assertJsonPath('data.settings.peertube_admin_password', null)
        ->assertJsonPath('data.settings.peertube_admin_password_set', true);

    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_admin_password' => '',
        'peertube_admin_username' => 'admin-updated',
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.peertube_admin_username', 'admin-updated')
        ->assertJsonPath('data.settings.peertube_admin_password_set', true);

    Http::fake([
        'https://video.example.test/api/v1/oauth-clients/local' => Http::response([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
        ]),
        'https://video.example.test/api/v1/users/token' => Http::response([
            'access_token' => 'token-123',
        ]),
    ]);

    $this->postJson('/api/v1/admin/media/settings/test-peertube')
        ->assertOk()
        ->assertJsonPath('data.connected', true);
});

it('allows admin to create feature packs and grant media entitlements that increase upload limits', function () {
    $admin = mediaAdmin();
    $user = mediaPoetOrganizer();

    Sanctum::actingAs($admin);

    $this->patchJson('/api/v1/admin/media/settings', [
        'video_limit' => 3,
        'photo_limit' => 10,
    ])->assertOk();

    $this->postJson('/api/v1/admin/media/feature-packs', [
        'code' => 'video_upload_bonus_7',
        'name' => 'Video Upload Bonus +7',
        'config' => [
            'video_limit_bonus' => 7,
        ],
        'price_amount' => 990,
        'currency' => 'EUR',
    ])
        ->assertCreated()
        ->assertJsonPath('data.feature_pack.code', 'video_upload_bonus_7')
        ->assertJsonPath('data.feature_pack.config.video_limit_bonus', 7);

    $featurePackId = MediaFeaturePack::query()->value('id');

    $this->postJson("/api/v1/admin/users/{$user->id}/media-entitlements", [
        'media_feature_pack_id' => $featurePackId,
        'source' => 'admin_grant',
    ])
        ->assertCreated()
        ->assertJsonPath('data.entitlement.feature_pack.code', 'video_upload_bonus_7');

    $this->getJson("/api/v1/admin/users/{$user->id}/media-entitlements")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.feature_pack.code', 'video_upload_bonus_7');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/my/media/limits')
        ->assertOk()
        ->assertJsonPath('data.limits.base_video_limit', 3)
        ->assertJsonPath('data.limits.video_limit_bonus', 7)
        ->assertJsonPath('data.limits.video_limit', 10)
        ->assertJsonPath('data.limits.remaining_video_uploads', 10)
        ->assertJsonPath('data.limits.peertube_configured', false);
});

it('allows users to create and list pending media checkout orders from active feature packs', function () {
    $admin = mediaAdmin();
    $user = mediaPoetOrganizer();

    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/admin/media/feature-packs', [
        'code' => 'video_upload_bonus_3',
        'name' => 'Video Upload Bonus +3',
        'config' => [
            'video_limit_bonus' => 3,
        ],
        'price_amount' => 490,
        'currency' => 'EUR',
    ])->assertCreated();

    $featurePackId = MediaFeaturePack::query()->value('id');

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/my/media/checkout-orders', [
        'media_feature_pack_id' => $featurePackId,
        'provider' => 'manual',
        'payload' => [
            'note' => 'premium upsell test',
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.order.status', 'pending')
        ->assertJsonPath('data.order.amount', 490)
        ->assertJsonPath('data.order.feature_code', 'video_upload_bonus_3');

    $this->getJson('/api/v1/my/media/checkout-orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.feature_pack.code', 'video_upload_bonus_3');
});

it('creates a stripe checkout session for a pending media checkout order', function () {
    $admin = mediaAdmin();
    $user = mediaPoetOrganizer();

    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'stripe_secret_key' => 'sk_test_123',
        'stripe_webhook_secret' => 'whsec_test_123',
        'stripe_checkout_success_url' => 'https://app.slamin.test/payments/success?order={CHECKOUT_ORDER_ID}',
        'stripe_checkout_cancel_url' => 'https://app.slamin.test/payments/cancel?order={CHECKOUT_ORDER_ID}',
        'stripe_api_base_url' => 'https://api.stripe.test',
    ])->assertOk();

    $this->postJson('/api/v1/admin/media/feature-packs', [
        'code' => 'video_upload_bonus_5',
        'name' => 'Video Upload Bonus +5',
        'config' => [
            'video_limit_bonus' => 5,
        ],
        'price_amount' => 790,
        'currency' => 'EUR',
    ])->assertCreated();

    Sanctum::actingAs($user);
    $this->postJson('/api/v1/my/media/checkout-orders', [
        'media_feature_pack_id' => MediaFeaturePack::query()->value('id'),
        'provider' => 'stripe',
    ])->assertCreated();

    $order = MediaCheckoutOrder::query()->firstOrFail();

    Http::fake([
        'https://api.stripe.test/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.test/c/cs_test_123',
        ]),
    ]);

    $this->postJson("/api/v1/my/media/checkout-orders/{$order->id}/stripe-session")
        ->assertCreated()
        ->assertJsonPath('data.checkout_session_id', 'cs_test_123')
        ->assertJsonPath('data.checkout_url', 'https://checkout.stripe.test/c/cs_test_123');

    $this->assertDatabaseHas('media_checkout_orders', [
        'id' => $order->id,
        'provider' => 'stripe',
        'provider_checkout_session_id' => 'cs_test_123',
    ]);
});

it('handles a stripe webhook and completes the checkout order with an entitlement', function () {
    $admin = mediaAdmin();
    $user = mediaPoetOrganizer();

    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'stripe_secret_key' => 'sk_test_123',
        'stripe_webhook_secret' => 'whsec_test_123',
        'stripe_checkout_success_url' => 'https://app.slamin.test/payments/success?order={CHECKOUT_ORDER_ID}',
        'stripe_checkout_cancel_url' => 'https://app.slamin.test/payments/cancel?order={CHECKOUT_ORDER_ID}',
        'stripe_api_base_url' => 'https://api.stripe.test',
    ])->assertOk();

    $this->postJson('/api/v1/admin/media/feature-packs', [
        'code' => 'video_upload_bonus_2',
        'name' => 'Video Upload Bonus +2',
        'config' => [
            'video_limit_bonus' => 2,
        ],
        'price_amount' => 390,
        'currency' => 'EUR',
    ])->assertCreated();

    Sanctum::actingAs($user);
    $this->postJson('/api/v1/my/media/checkout-orders', [
        'media_feature_pack_id' => MediaFeaturePack::query()->value('id'),
        'provider' => 'stripe',
    ])->assertCreated();

    $order = MediaCheckoutOrder::query()->firstOrFail();

    $payload = json_encode([
        'id' => 'evt_test_123',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_completed',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_123',
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'media_checkout_order_id' => (string) $order->id,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test_123');

    $this->call(
        'POST',
        '/api/v1/webhooks/stripe',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload,
    )
        ->assertOk()
        ->assertJsonPath('data.received', true);

    $this->assertDatabaseHas('media_checkout_orders', [
        'id' => $order->id,
        'status' => 'paid',
        'provider_checkout_session_id' => 'cs_test_completed',
        'provider_payment_intent_id' => 'pi_test_123',
    ]);

    $this->assertDatabaseHas('user_media_entitlements', [
        'user_id' => $user->id,
        'media_feature_pack_id' => $order->media_feature_pack_id,
        'source' => 'stripe_checkout',
    ]);
});

it('uploads a photo media item for poet organizers', function () {
    Storage::fake('public');

    $user = mediaPoetOrganizer();
    Sanctum::actingAs($user);

    $response = $this->post('/api/v1/media/photos', [
        'title' => 'My Photo',
        'description' => 'A nice shot',
        'visibility' => 'public',
        'file' => UploadedFile::fake()->image('photo.jpg', 1200, 800),
    ], [
        'Accept' => 'application/json',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.media.type', 'photo')
        ->assertJsonPath('data.media.title', 'My Photo')
        ->assertJsonPath('data.media.status', 'ready')
        ->assertJsonPath('data.media.visibility', 'public');

    $path = MediaItem::query()->where('type', 'photo')->value('file_path');
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('rejects photo upload when the configured photo limit is reached', function () {
    Storage::fake('public');

    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'photo_limit' => 0,
    ])->assertOk();

    $user = mediaPoetOrganizer();
    Sanctum::actingAs($user);

    $this->post('/api/v1/media/photos', [
        'title' => 'Blocked Photo',
        'visibility' => 'public',
        'file' => UploadedFile::fake()->image('blocked.jpg'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.file.0', __('media.validation.photo_limit_reached'));
});

it('uploads a video media item through peertube using admin settings', function () {
    Storage::fake('local');

    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_base_url' => 'https://video.example.test',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
        'video_allowed_mimes' => ['video/x-matroska'],
    ])->assertOk();

    Http::fake([
        'https://video.example.test/api/v1/oauth-clients/local' => Http::response([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
        ]),
        'https://video.example.test/api/v1/users/token' => Http::response([
            'access_token' => 'token-123',
        ]),
        'https://video.example.test/api/v1/videos/upload' => Http::response([
            'video' => [
                'id' => '77',
                'uuid' => 'uuid-77',
                'shortUUID' => 'short-77',
            ],
        ]),
        'https://video.example.test/api/v1/videos/77' => Http::response([
            'thumbnailPath' => '/static/thumbnails/77.jpg',
            'duration' => 123,
        ]),
    ]);

    $user = mediaPoetOrganizer();
    Sanctum::actingAs($user);

    $response = $this->post('/api/v1/media/videos', [
        'title' => 'My Video',
        'description' => '   ',
        'visibility' => 'private',
        'tags' => ['poetry', 'slam'],
        'file' => UploadedFile::fake()->create('recording.final.MKV', 1024, 'video/x-matroska'),
    ], [
        'Accept' => 'application/json',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.media.type', 'video')
        ->assertJsonPath('data.media.status', 'processing')
        ->assertJsonPath('data.media.peertube_video_id', '77')
        ->assertJsonPath('data.media.peertube_short_uuid', 'short-77')
        ->assertJsonPath('data.media.peertube_thumbnail_url', 'https://video.example.test/static/thumbnails/77.jpg')
        ->assertJsonPath('data.media.duration_seconds', 123);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://video.example.test/api/v1/videos/upload'
        && $request->hasFile('videofile', null, 'video.mkv')
        && str_contains($request->body(), 'Content-Type: video/x-matroska')
        && ! array_key_exists('description', $request->data()));
});

it('logs the underlying peertube upload failure without exposing it to the user', function () {
    Storage::fake('local');
    Log::spy();

    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_base_url' => 'https://video.example.test',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
        'video_allowed_mimes' => ['video/mp4'],
    ])->assertOk();

    Http::fake([
        'https://video.example.test/api/v1/oauth-clients/local' => Http::response([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
        ]),
        'https://video.example.test/api/v1/users/token' => Http::response([
            'access_token' => 'token-123',
        ]),
        'https://video.example.test/api/v1/videos/upload' => Http::response([
            'error' => 'PeerTube rejected the upload.',
        ], 500),
    ]);

    Sanctum::actingAs(mediaPoetOrganizer());

    $this->post('/api/v1/media/videos', [
        'title' => 'Failed Video',
        'visibility' => 'private',
        'file' => UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.file.0', __('media.validation.peertube_upload_failed'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'PeerTube video upload failed.'
            && $context['file_size'] > 0
            && $context['mime_type'] === 'video/mp4'
            && $context['peertube_base_url'] === 'https://video.example.test'
            && $context['exception'] instanceof Throwable);
});

it('allows the owner to view a processing video with a bearer token', function () {
    $user = mediaPoetOrganizer();
    $token = $user->createToken('auth_token')->plainTextToken;

    $media = MediaItem::query()->create([
        'user_id' => $user->id,
        'type' => 'video',
        'title' => 'Processing video',
        'visibility' => 'public',
        'status' => 'processing',
        'processing_status' => 'processing',
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'uploaded_at' => now(),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/media/{$media->id}")
        ->assertOk()
        ->assertJsonPath('data.media.title', 'Processing video')
        ->assertJsonPath('data.media.status', 'processing');
});

it('syncs a processing video to ready when peertube exposes files', function () {
    $owner = mediaPoetOrganizer();
    Sanctum::actingAs($owner);

    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_base_url' => 'https://video.example.test',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
    ])->assertOk();
    Sanctum::actingAs($owner);

    Http::fake([
        'https://video.example.test/api/v1/videos/ready-77' => Http::response([
            'uuid' => 'ready-77',
            'shortUUID' => 'ready-short-77',
            'thumbnailPath' => '/static/thumbnails/ready-77.jpg',
            'duration' => 200,
            'files' => [['fileUrl' => 'https://video.example.test/static/video.mp4']],
            'state' => ['id' => 1, 'label' => 'Published'],
        ]),
    ]);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Processing video',
        'visibility' => 'public',
        'status' => 'processing',
        'processing_status' => 'processing',
        'peertube_video_id' => 'ready-77',
        'peertube_uuid' => 'ready-77',
        'uploaded_at' => now(),
    ]);

    $this->getJson("/api/v1/media/{$media->id}/video-status")
        ->assertOk()
        ->assertJsonPath('data.media.status', 'ready')
        ->assertJsonPath('data.media.processing_status', 'ready')
        ->assertJsonPath('data.media.duration_seconds', 200);

    $this->assertDatabaseHas('media_items', [
        'id' => $media->id,
        'status' => 'ready',
        'processing_status' => 'ready',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'media_video_ready',
        'title' => __('media.notifications.video_ready_title'),
    ]);
});

it('syncs a processing video to failed when peertube returns an explicit error state', function () {
    $owner = mediaPoetOrganizer();
    Sanctum::actingAs($owner);

    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_base_url' => 'https://video.example.test',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
    ])->assertOk();
    Sanctum::actingAs($owner);

    Http::fake([
        'https://video.example.test/api/v1/videos/failed-77' => Http::response([
            'uuid' => 'failed-77',
            'shortUUID' => 'failed-short-77',
            'state' => ['id' => 9, 'label' => 'Transcoding error'],
        ]),
    ]);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Broken video',
        'visibility' => 'private',
        'status' => 'processing',
        'processing_status' => 'processing',
        'peertube_video_id' => 'failed-77',
        'peertube_uuid' => 'failed-77',
        'uploaded_at' => now(),
    ]);

    $this->getJson("/api/v1/media/{$media->id}/video-status")
        ->assertOk()
        ->assertJsonPath('data.media.status', 'failed')
        ->assertJsonPath('data.media.processing_status', 'failed');

    $this->assertDatabaseHas('media_items', [
        'id' => $media->id,
        'status' => 'failed',
        'processing_status' => 'failed',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'media_video_failed',
        'title' => __('media.notifications.video_failed_title'),
    ]);
});

it('promotes published peertube videos into the public media list', function () {
    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_base_url' => 'https://video.example.test',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
    ])->assertOk();

    $owner = mediaPoetOrganizer();
    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Live on PeerTube',
        'visibility' => 'public',
        'status' => 'processing',
        'processing_status' => 'processing',
        'peertube_video_id' => '99',
        'peertube_uuid' => 'uuid-99',
        'uploaded_at' => now(),
    ]);

    Http::fake([
        'https://video.example.test/api/v1/videos/99' => Http::response([
            'uuid' => 'uuid-99',
            'shortUUID' => 'short-99',
            'thumbnailPath' => '/static/thumbnails/99.jpg',
            'duration' => 42,
            'waitTranscoding' => false,
            'state' => ['id' => 1, 'label' => 'Published'],
            'streamingPlaylists' => [['playlistUrl' => 'https://video.example.test/static/master.m3u8']],
        ]),
    ]);

    $this->getJson('/api/v1/media?type=video')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Live on PeerTube')
        ->assertJsonPath('data.0.status', 'ready')
        ->assertJsonPath('data.0.peertube_thumbnail_url', 'https://video.example.test/static/thumbnails/99.jpg');

    $this->assertDatabaseHas('media_items', [
        'id' => $media->id,
        'status' => 'ready',
        'processing_status' => 'ready',
    ]);
});

it('exposes stored relative peertube thumbnail paths as absolute urls', function () {
    $admin = mediaAdmin();
    Sanctum::actingAs($admin);
    $this->patchJson('/api/v1/admin/media/settings', [
        'peertube_base_url' => 'http://localhost:9000',
        'peertube_admin_username' => 'admin',
        'peertube_admin_password' => 'secret',
        'peertube_upload_channel_id' => '12',
    ])->assertOk();

    $owner = mediaPoetOrganizer();
    MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Relative thumb',
        'visibility' => 'public',
        'status' => 'ready',
        'processing_status' => 'ready',
        'peertube_thumbnail_url' => '/lazy-static/previews/abc.jpg',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $this->getJson('/api/v1/media?type=video')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Relative thumb')
        ->assertJsonPath('data.0.peertube_thumbnail_url', 'http://localhost:9000/lazy-static/previews/abc.jpg');
});

it('lists and shows only ready public media', function () {
    $owner = mediaPoetOrganizer();

    $publicMedia = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'photo',
        'title' => 'Public media',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/public.jpg',
        'file_url' => '/storage/media/photos/public.jpg',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'photo',
        'title' => 'Private media',
        'visibility' => 'private',
        'status' => 'ready',
        'file_path' => 'media/photos/private.jpg',
        'file_url' => '/storage/media/photos/private.jpg',
        'uploaded_at' => now(),
    ]);

    $this->getJson('/api/v1/media')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Public media');

    $this->getJson("/api/v1/media/{$publicMedia->id}")
        ->assertOk()
        ->assertJsonPath('data.media.title', 'Public media');
});

it('filters public media by type search and sort', function () {
    $owner = mediaPoetOrganizer();

    MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Night slam',
        'description' => 'Live set',
        'visibility' => 'public',
        'status' => 'ready',
        'published_at' => now()->subDay(),
        'uploaded_at' => now()->subDay(),
    ]);

    MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'photo',
        'title' => 'Street gallery',
        'description' => 'Ritratto',
        'visibility' => 'public',
        'status' => 'ready',
        'file_path' => 'media/photos/street.jpg',
        'published_at' => now(),
        'uploaded_at' => now(),
    ]);

    $this->getJson('/api/v1/media?type=video')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Night slam');

    $this->getJson('/api/v1/media?q=Street')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Street gallery');

    $owner->profile()->update([
        'nickname' => 'street_poet',
        'display_name' => 'Livia Media',
    ]);

    $this->getJson('/api/v1/media?q=Livia')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/media?sort=oldest')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Night slam')
        ->assertJsonPath('data.1.title', 'Street gallery');
});

it('allows media owner to clear description with null on update', function () {
    $owner = mediaPoetOrganizer();
    Sanctum::actingAs($owner);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'photo',
        'title' => 'Media with description',
        'description' => 'Da rimuovere',
        'visibility' => 'private',
        'status' => 'ready',
        'file_path' => 'media/photos/desc.jpg',
        'file_url' => '/storage/media/photos/desc.jpg',
        'uploaded_at' => now(),
    ]);

    $this->patchJson("/api/v1/media/{$media->id}", [
        'description' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.media.description', null);

    $this->assertDatabaseHas('media_items', [
        'id' => $media->id,
        'description' => null,
    ]);
});

it('allows liking and commenting a public media item', function () {
    $owner = mediaPoetOrganizer();
    $viewer = mediaPoetOrganizer();
    Sanctum::actingAs($viewer);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Commentable media',
        'visibility' => 'public',
        'status' => 'ready',
        'peertube_video_id' => '88',
        'peertube_uuid' => 'uuid-88',
        'peertube_short_uuid' => 'short-88',
        'peertube_url' => 'https://video.example.test/videos/watch/short-88',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $this->postJson("/api/v1/media/{$media->id}/like")
        ->assertOk()
        ->assertJsonPath('data.media.is_liked', true)
        ->assertJsonPath('data.media.likes_count', 1);

    $this->postJson("/api/v1/media/{$media->id}/comments", [
        'content' => 'Bellissimo video',
    ])
        ->assertOk()
        ->assertJsonPath('data.comment.content', 'Bellissimo video');

    $this->assertDatabaseHas('comments', [
        'commentable_type' => $media->getMorphClass(),
        'commentable_id' => $media->id,
        'content' => 'Bellissimo video',
    ]);
});

it('allows authenticated users to create, update, list and delete snaps on public videos', function () {
    $owner = mediaPoetOrganizer();
    $viewer = mediaPoetOrganizer();
    Sanctum::actingAs($viewer);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Snapable media',
        'visibility' => 'public',
        'status' => 'ready',
        'duration_seconds' => 120,
        'peertube_video_id' => '91',
        'peertube_uuid' => 'uuid-91',
        'peertube_short_uuid' => 'short-91',
        'peertube_url' => 'https://video.example.test/videos/watch/short-91',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $this->postJson("/api/v1/media/{$media->id}/snaps", [
        'timestamp_seconds' => 42,
        'kind' => 'highlight',
        'comment' => 'Qui spacca davvero',
    ])
        ->assertCreated()
        ->assertJsonPath('data.snap.timestamp_seconds', 42)
        ->assertJsonPath('data.snap.kind', 'highlight');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'media_snap_created',
        'title' => __('media.notifications.snap_created_title'),
    ]);

    $snapId = MediaSnap::query()->value('id');

    $this->patchJson("/api/v1/media/{$media->id}/snaps/{$snapId}", [
        'kind' => 'warning',
        'comment' => 'Audio da rivedere',
    ])
        ->assertOk()
        ->assertJsonPath('data.snap.kind', 'warning')
        ->assertJsonPath('data.snap.comment', 'Audio da rivedere');

    $this->getJson("/api/v1/media/{$media->id}")
        ->assertOk()
        ->assertJsonPath('data.media.snaps_count', 1);

    $this->getJson("/api/v1/media/{$media->id}/snaps")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.comment', 'Audio da rivedere');

    $this->deleteJson("/api/v1/media/{$media->id}/snaps/{$snapId}")
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('media_snaps', [
        'id' => $snapId,
    ]);
});

it('rejects snaps that exceed the video duration', function () {
    $owner = mediaPoetOrganizer();
    $viewer = mediaPoetOrganizer();
    Sanctum::actingAs($viewer);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Short media',
        'visibility' => 'public',
        'status' => 'ready',
        'duration_seconds' => 30,
        'peertube_video_id' => '92',
        'peertube_uuid' => 'uuid-92',
        'peertube_short_uuid' => 'short-92',
        'peertube_url' => 'https://video.example.test/videos/watch/short-92',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $this->postJson("/api/v1/media/{$media->id}/snaps", [
        'timestamp_seconds' => 31,
        'kind' => 'highlight',
        'comment' => 'Troppo avanti',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.fields.timestamp_seconds.0', __('media.validation.snap_timestamp_exceeds_duration'));
});

it('does not notify when the media owner creates a snap on their own video', function () {
    $owner = mediaPoetOrganizer();
    Sanctum::actingAs($owner);

    $media = MediaItem::query()->create([
        'user_id' => $owner->id,
        'type' => 'video',
        'title' => 'Own video',
        'visibility' => 'public',
        'status' => 'ready',
        'duration_seconds' => 60,
        'peertube_video_id' => '93',
        'peertube_uuid' => 'uuid-93',
        'peertube_short_uuid' => 'short-93',
        'peertube_url' => 'https://video.example.test/videos/watch/short-93',
        'uploaded_at' => now(),
        'published_at' => now(),
    ]);

    $this->postJson("/api/v1/media/{$media->id}/snaps", [
        'timestamp_seconds' => 10,
        'kind' => 'highlight',
        'comment' => 'Mi piace questo punto',
    ])->assertCreated();

    expect(Notification::query()->where('type', 'media_snap_created')->count())->toBe(0);
});
