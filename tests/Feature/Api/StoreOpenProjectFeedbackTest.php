<?php

use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function configureOpenProject(): void
{
    config([
        'services.openproject.base_url' => 'https://projects.test',
        'services.openproject.api_token' => 'secret-token',
        'services.openproject.project' => 'slamin-feedback',
    ]);
}

it('creates an openproject work package from site feedback', function () {
    configureOpenProject();

    Http::fake([
        'https://projects.test/api/v3/projects/slamin-feedback/work_packages' => Http::response(['id' => 42], 201),
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->post('/api/feedback', [
        'subject' => 'Il video non parte',
        'description' => 'Nella scheda compare un riquadro vuoto.',
        'url' => 'https://slamin.on-forge.com/media/2',
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.work_package_id', 42);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://projects.test/api/v3/projects/slamin-feedback/work_packages'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request['subject'] === 'Il video non parte'
            && ! isset($request['_links']['type'])
            && str_contains($request['description']['raw'], 'https://slamin.on-forge.com/media/2');
    });
});

it('attaches the optional screenshot to the created work package', function () {
    configureOpenProject();

    Http::fake([
        'https://projects.test/api/v3/projects/slamin-feedback/work_packages' => Http::response(['id' => 42], 201),
        'https://projects.test/api/v3/work_packages/42/attachments' => Http::response(['id' => 7], 201),
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->post('/api/feedback', [
        'subject' => 'Layout rotto',
        'description' => 'Allego lo schermo.',
        'screenshot' => UploadedFile::fake()->image('schermo.png'),
    ], ['Accept' => 'application/json'])->assertCreated();

    Http::assertSent(fn ($request) => $request->url() === 'https://projects.test/api/v3/work_packages/42/attachments');
});

it('refuses feedback when openproject is not configured', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/feedback', [
        'subject' => 'Ciao',
        'description' => 'Manca la configurazione.',
    ])->assertStatus(503);
});

it('requires an authenticated user', function () {
    configureOpenProject();

    $this->postJson('/api/feedback', [
        'subject' => 'Ciao',
        'description' => 'Da fuori.',
    ])->assertUnauthorized();
});
