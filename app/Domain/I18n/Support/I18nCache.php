<?php

namespace Domain\I18n\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

final class I18nCache
{
    public static function bump(): void
    {
        Cache::forever('i18n.cache_version', (string) (int) round(microtime(true) * 1000));

        try {
            app('translator')->setLoaded([]);
        } catch (Throwable) {
        }
    }

    public static function version(): string
    {
        return (string) Cache::get('i18n.cache_version', '0');
    }

    public static function key(string $suffix): string
    {
        return 'i18n.'.self::version().'.'.$suffix;
    }
}
