<?php

use Domain\Article\Models\Article;
use Domain\Poetry\Models\Poem;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function translationAuthor(): User
{
    return User::factory()->poetOrganizer()->create(['email_verified_at' => now()]);
}

function translatablePoem(User $owner, array $overrides = []): Poem
{
    return Poem::query()->create(array_merge([
        'user_id' => $owner->id,
        'title' => 'Mare d\'inverno',
        'slug' => 'mare-inverno-'.uniqid(),
        'content' => "Onde grigie\nsul molo",
        'description' => 'Una poesia sul mare.',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

function translatableArticle(User $owner, array $overrides = []): Article
{
    return Article::query()->create(array_merge([
        'user_id' => $owner->id,
        'title' => 'Cronaca dello slam',
        'slug' => 'cronaca-slam-'.uniqid(),
        'content' => 'Testo articolo',
        'excerpt' => 'Riassunto',
        'language' => 'it',
        'status' => 'published',
        'is_public' => true,
        'published_at' => now(),
    ], $overrides));
}

it('lets the owner translate a poem and serves it in the reader locale', function () {
    $owner = translationAuthor();
    $poem = translatablePoem($owner);
    Sanctum::actingAs($owner);

    $this->putJson("/api/v1/poems/{$poem->id}/translations/en", [
        'title' => 'Winter sea',
        'content' => "Grey waves\non the pier",
        'summary' => 'A poem about the sea.',
    ])->assertOk()->assertJsonPath('data.translation.locale', 'en');

    $this->putJson("/api/v1/poems/{$poem->id}/translations/en", [
        'title' => 'Winter Sea',
        'content' => "Grey waves\non the pier",
    ])->assertOk()->assertJsonPath('data.translation.summary', null);

    $this->putJson("/api/v1/poems/{$poem->id}/translations/es", [
        'title' => 'Mar de invierno',
        'content' => "Olas grises\nen el muelle",
    ])->assertOk();

    expect($poem->translations()->count())->toBe(2);

    Sanctum::actingAs(translationAuthor());

    $this->getJson("/api/v1/poems/{$poem->slug}", ['X-Locale' => 'en'])
        ->assertOk()
        ->assertJsonPath('data.poem.title', 'Mare d\'inverno')
        ->assertJsonPath('data.poem.translation.title', 'Winter Sea')
        ->assertJsonPath('data.poem.translations.0.locale', 'en')
        ->assertJsonPath('data.poem.translations.1.locale', 'es')
        ->assertJsonMissingPath('data.poem.translations.0.content');

    $this->getJson("/api/v1/poems/{$poem->slug}", ['X-Locale' => 'it'])
        ->assertOk()
        ->assertJsonPath('data.poem.translation', null);

    $this->getJson("/api/v1/poems/{$poem->slug}/translations/es")
        ->assertOk()
        ->assertJsonPath('data.translation.title', 'Mar de invierno');
});

it('lets the owner translate and delete an article translation', function () {
    $owner = translationAuthor();
    $article = translatableArticle($owner);
    Sanctum::actingAs($owner);

    $this->putJson("/api/v1/articles/{$article->id}/translations/fr", [
        'title' => 'Chronique du slam',
        'content' => 'Texte',
        'summary' => 'Résumé',
    ])->assertOk();

    $this->getJson("/api/v1/articles/{$article->slug}/translations/fr")
        ->assertOk()
        ->assertJsonPath('data.translation.summary', 'Résumé');

    $this->deleteJson("/api/v1/articles/{$article->id}/translations/fr")->assertOk();

    expect($article->translations()->count())->toBe(0);

    $this->deleteJson("/api/v1/articles/{$article->id}/translations/fr")->assertNotFound();
});

it('forbids translating content owned by someone else', function () {
    $poem = translatablePoem(translationAuthor());
    $article = translatableArticle(translationAuthor());
    Sanctum::actingAs(translationAuthor());

    $payload = ['title' => 'Stolen', 'content' => 'Nope'];

    $this->putJson("/api/v1/poems/{$poem->id}/translations/en", $payload)->assertForbidden();
    $this->putJson("/api/v1/articles/{$article->id}/translations/en", $payload)->assertForbidden();

    expect($poem->translations()->count())->toBe(0)
        ->and($article->translations()->count())->toBe(0);
});

it('rejects a translation in the original language or with an invalid locale', function () {
    $owner = translationAuthor();
    $poem = translatablePoem($owner);
    Sanctum::actingAs($owner);

    $this->putJson("/api/v1/poems/{$poem->id}/translations/it", [
        'title' => 'Stesso',
        'content' => 'Stesso',
    ])->assertUnprocessable()->assertJsonValidationErrors('locale', 'errors.fields');

    $this->putJson("/api/v1/poems/{$poem->id}/translations/EN", [
        'title' => 'Upper',
        'content' => 'Upper',
    ])->assertNotFound();
});

it('hides translations of content that is not visible to the reader', function () {
    $owner = translationAuthor();
    $poem = translatablePoem($owner, ['status' => 'draft', 'is_public' => false]);
    $poem->translations()->create(['locale' => 'en', 'title' => 'Draft', 'content' => 'Draft', 'user_id' => $owner->id]);

    $this->getJson("/api/v1/poems/{$poem->slug}/translations/en")->assertNotFound();

    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/poems/{$poem->slug}/translations/en")
        ->assertOk()
        ->assertJsonPath('data.translation.title', 'Draft');
});
