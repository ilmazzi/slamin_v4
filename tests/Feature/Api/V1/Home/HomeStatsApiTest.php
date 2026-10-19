<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('translates community stats for german french spanish and portuguese', function (string $locale, string $title, string $views) {
    $this->getJson("/api/v1/home/stats?locale={$locale}")
        ->assertOk()
        ->assertJsonPath('data.community_stats.title', $title)
        ->assertJsonPath('data.community_stats.cards.0.label', $views);
})->with([
    ['de', 'Die Community in Zahlen', 'Aufrufe'],
    ['fr', 'La communauté en chiffres', 'Vues'],
    ['es', 'La comunidad en cifras', 'Visualizaciones'],
    ['pt', 'A comunidade em números', 'Visualizações'],
]);

it('falls back to the request locale when the home stats query locale is unknown', function () {
    $this->withHeader('X-Locale', 'it')
        ->getJson('/api/v1/home/stats?locale=xx')
        ->assertOk()
        ->assertJsonPath('data.community_stats.title', 'La community in numeri');
});
