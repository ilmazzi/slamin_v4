<?php

use Domain\Share\Support\ShareCardTemplate;
use Tests\TestCase;

uses(TestCase::class);

it('resolves only templates allowed for a content type', function () {
    expect(ShareCardTemplate::resolve('event', 'poster'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::resolve('event', 'photo_clean'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::resolve('media', 'photo_clean'))->toBe(ShareCardTemplate::PhotoClean)
        ->and(ShareCardTemplate::resolve('gig', 'promo'))->toBe(ShareCardTemplate::Promo)
        ->and(ShareCardTemplate::fromRequest('event', 'brand', 'share.events.preview'))->toBe(ShareCardTemplate::Poster)
        ->and(ShareCardTemplate::fromRequest('event', 'brand', 'share.events.card'))->toBe(ShareCardTemplate::Brand)
        ->and(ShareCardTemplate::Promo->usesImage())->toBeTrue()
        ->and(ShareCardTemplate::Brand->usesImage())->toBeFalse();
});
