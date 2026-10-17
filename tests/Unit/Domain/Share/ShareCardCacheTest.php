<?php

use Domain\Share\Support\ShareCardCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

it('stores binary share cards as database-safe base64 text', function () {
    config(['cache.default' => 'array']);
    Cache::flush();

    $binaryPng = "\x89PNG\r\n\x1a\n\x00\xFFshare-card";

    expect(ShareCardCache::remember('share-card-test', fn () => $binaryPng))
        ->toBe($binaryPng)
        ->and(Cache::get('share-card-test'))
        ->toBe(base64_encode($binaryPng));
});
