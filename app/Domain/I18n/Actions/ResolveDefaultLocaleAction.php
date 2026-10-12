<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Support\I18nCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ResolveDefaultLocaleAction
{
    public function execute(): string
    {
        $cacheKey = I18nCache::key('default_locale');

        try {
            return Cache::remember($cacheKey, 3600, fn (): string => $this->resolve());
        } catch (Throwable) {
            return $this->resolve();
        }
    }

    private function resolve(): string
    {
        if ($this->databaseReady()) {
            $default = Language::query()->where('is_default', true)->where('is_active', true)->value('code');
            if (is_string($default) && $default !== '') {
                return $default;
            }

            $first = Language::query()->where('is_active', true)->orderBy('sort_order')->value('code');
            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return (string) config('app.fallback_locale', 'en');
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('i18n_languages');
        } catch (Throwable) {
            return false;
        }
    }
}
