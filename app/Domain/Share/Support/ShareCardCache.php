<?php

namespace Domain\Share\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class ShareCardCache
{
    public const VERSION = 10;

    public static function remember(string $key, Closure $render): string
    {
        $encoded = Cache::remember(
            $key,
            now()->addDay(),
            fn (): string => base64_encode($render()),
        );

        $png = base64_decode($encoded, true);
        if ($png === false) {
            Cache::forget($key);

            throw new RuntimeException('Unable to decode the cached share card.');
        }

        return $png;
    }
}
