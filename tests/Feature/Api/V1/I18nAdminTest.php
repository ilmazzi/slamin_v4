<?php

use Domain\I18n\Actions\ImportLangFilesAction;
use Domain\I18n\Models\Language;
use Domain\User\Models\Role;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ImportLangFilesAction::class)->execute();
});

function i18nAdminUser(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function i18nTranslatorUser(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'translator'], ['label' => 'Translator']);
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

it('serves the ui bundle from the database after import', function () {
    $response = $this->getJson('/api/v1/i18n/it.json');

    $response->assertOk()
        ->assertJsonPath('data.locale', 'it')
        ->assertJsonPath('data.translations.articles.title', 'articoli')
        ->assertJsonPath('data.translations.auth.unauthenticated', 'Utente non autenticato.')
        ->assertJsonPath('data.translations.i18n.panel.title', 'Traduzioni');
});

it('uses a database translation for laravel messages after an update', function () {
    Sanctum::actingAs(i18nTranslatorUser());

    $this->putJson('/api/v1/admin/i18n/translations', [
        'key' => 'auth.unauthenticated',
        'locale' => 'it',
        'value' => 'Accesso richiesto dal DB.',
    ])->assertOk();

    App::setLocale('it');

    expect(__('auth.unauthenticated'))->toBe('Accesso richiesto dal DB.');

    $this->getJson('/api/v1/i18n/it.json')
        ->assertOk()
        ->assertJsonPath('data.translations.auth.unauthenticated', 'Accesso richiesto dal DB.');
});

it('allows a translator to list keys and save a translation', function () {
    Sanctum::actingAs(i18nTranslatorUser());

    $this->getJson('/api/v1/admin/i18n/languages')
        ->assertOk()
        ->assertJsonFragment(['code' => 'it'])
        ->assertJsonFragment(['code' => 'en']);

    $this->getJson('/api/v1/admin/i18n/keys?locale=en&q=articles.title')
        ->assertOk()
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.source_locale', 'it');

    $this->putJson('/api/v1/admin/i18n/translations', [
        'key' => 'articles.title',
        'locale' => 'en',
        'value' => 'Stories',
    ])->assertOk()
        ->assertJsonPath('data.translation.value', 'Stories');

    $this->getJson('/api/v1/i18n/en.json')
        ->assertOk()
        ->assertJsonPath('data.translations.articles.title', 'Stories');
});

it('allows an admin to create a language and a translation key', function () {
    Sanctum::actingAs(i18nAdminUser());

    $this->postJson('/api/v1/admin/i18n/languages', [
        'code' => 'de',
        'name' => 'Deutsch',
        'flag' => '🇩🇪',
    ])->assertCreated()
        ->assertJsonPath('data.language.code', 'de');

    expect(Language::query()->where('code', 'de')->exists())->toBeTrue();

    $this->postJson('/api/v1/admin/i18n/keys', [
        'key' => 'common.brand_new_label',
        'translations' => [
            'it' => 'Nuova etichetta',
            'de' => 'Neues Label',
        ],
    ])->assertCreated()
        ->assertJsonPath('data.key.key', 'common.brand_new_label');

    $this->getJson('/api/v1/i18n/de.json')
        ->assertOk()
        ->assertJsonPath('data.locale', 'de')
        ->assertJsonPath('data.translations.common.brand_new_label', 'Neues Label');
});

it('does not overwrite existing database translations on import', function () {
    Sanctum::actingAs(i18nTranslatorUser());

    $this->putJson('/api/v1/admin/i18n/translations', [
        'key' => 'articles.title',
        'locale' => 'it',
        'value' => 'Titolo dal pannello',
    ])->assertOk();

    app(ImportLangFilesAction::class)->execute();

    $this->getJson('/api/v1/i18n/it.json')
        ->assertOk()
        ->assertJsonPath('data.translations.articles.title', 'Titolo dal pannello');
});

it('forbids translators from creating languages', function () {
    Sanctum::actingAs(i18nTranslatorUser());

    $this->postJson('/api/v1/admin/i18n/languages', [
        'code' => 'es',
        'name' => 'Espanol',
    ])->assertForbidden();
});

it('forbids audience users from the translation panel', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/i18n/languages')->assertForbidden();
});

it('applies x-locale to laravel validation messages', function () {
    $response = $this->withHeaders(['X-Locale' => 'it'])
        ->postJson('/api/v1/login', []);

    $response->assertStatus(422);
    expect($response->json('errors.message'))->toBeString()->not->toBe('');
});
