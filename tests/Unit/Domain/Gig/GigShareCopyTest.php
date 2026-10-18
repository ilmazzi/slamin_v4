<?php

use Domain\Gig\Models\Gig;
use Domain\Gig\Support\GigShareCopy;
use Tests\TestCase;

uses(TestCase::class);

it('describes how many people a gig is looking for', function () {
    app()->setLocale('it');

    $poets = new Gig(['type' => 'poet_artist', 'quantity' => 2]);
    $host = new Gig(['type' => 'mc_host', 'quantity' => 1]);
    $translator = new Gig(['type' => 'translator', 'quantity' => 1, 'language' => 'en']);

    expect(GigShareCopy::seeking($poets))->toBe('Cerchiamo 2 poeti')
        ->and(GigShareCopy::seeking($host))->toBe('Cerchiamo 1 host')
        ->and(GigShareCopy::seeking($translator))->toBe('Cerchiamo 1 traduttore · en');
});
