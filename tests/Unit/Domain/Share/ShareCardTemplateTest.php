<?php

use Domain\Share\Support\ShareCardTemplate;
use Tests\TestCase;

uses(TestCase::class);

it('resolves only templates allowed for a content type', function () {
    expect(ShareCardTemplate::resolve('event', 'poster'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::resolve('event', 'photo_clean'))->toBe(ShareCardTemplate::PhotoClean)
        ->and(ShareCardTemplate::resolve('media', 'photo_clean'))->toBe(ShareCardTemplate::PhotoClean)
        ->and(ShareCardTemplate::resolve('gig', 'promo'))->toBe(ShareCardTemplate::Promo)
        ->and(ShareCardTemplate::resolve('event', 'date_cut'))->toBe(ShareCardTemplate::DateCut)
        ->and(ShareCardTemplate::resolve('event', 'diagonal'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::resolve('gig', 'diagonal'))->toBe(ShareCardTemplate::Promo)
        ->and(ShareCardTemplate::resolve('poem', 'framed'))->toBe(ShareCardTemplate::Framed)
        ->and(ShareCardTemplate::resolve('article', 'verse'))->toBe(ShareCardTemplate::Verse)
        ->and(ShareCardTemplate::forContentType('event'))->toBe(ShareCardTemplate::catalog())
        ->and(ShareCardTemplate::forContentType('media'))->toBe(ShareCardTemplate::catalog())
        ->and(ShareCardTemplate::Framed->usesImage())->toBeTrue()
        ->and(ShareCardTemplate::DateCut->usesImage())->toBeFalse()
        ->and(ShareCardTemplate::fromRequest('event', 'brand', 'share.events.preview'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::fromRequest('event', 'brand', 'share.events.card'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::catalog())->not->toContain(ShareCardTemplate::Brand)
        ->and(ShareCardTemplate::Promo->isGraphic())->toBeTrue()
        ->and(ShareCardTemplate::Poster->isGraphic())->toBeFalse()
        ->and(ShareCardTemplate::Promo->usesImage())->toBeTrue()
        ->and(ShareCardTemplate::Brand->usesImage())->toBeFalse();
});
