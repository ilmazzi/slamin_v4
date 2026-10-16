<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class I18nTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_locale_returns_translations_payload(): void
    {
        $response = $this->getJson('/api/v1/i18n/it.json');

        $response->assertOk()
            ->assertJsonPath('data.locale', 'it')
            ->assertJsonStructure([
                'data' => [
                    'locale',
                    'default_locale',
                    'available_locales',
                    'available_languages',
                    'translations' => ['auth', 'common', 'login', 'articles'],
                ],
                'meta',
                'errors',
            ])
            ->assertJsonPath('data.translations.articles.title', 'articoli')
            ->assertJsonPath('data.translations.articles.filters.sort_newest', 'Più recenti')
            ->assertJsonPath('data.translations.contact.title', 'Contattaci');
    }

    public function test_unknown_locale_returns_404_with_api_envelope(): void
    {
        $response = $this->getJson('/api/v1/i18n/xx.json');

        $response->assertNotFound()
            ->assertJsonStructure(['data', 'meta', 'errors' => ['message', 'fields']]);
    }
}
