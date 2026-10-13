<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Support\FileTranslationCatalog;
use Domain\I18n\Support\I18nCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ResolveSupportedLocalesAction
{
    public function __construct(
        private readonly FileTranslationCatalog $files,
    ) {}

    /**
     * @return list<string>
     */
    public function execute(bool $activeOnly = true): array
    {
        $cacheKey = I18nCache::key($activeOnly ? 'locales.active' : 'locales.all');

        try {
            return Cache::remember($cacheKey, 3600, fn (): array => $this->resolve($activeOnly));
        } catch (Throwable) {
            return $this->resolve($activeOnly);
        }
    }

    /**
     * @return list<string>
     */
    private function resolve(bool $activeOnly): array
    {
        if ($this->databaseReady()) {
            $query = Language::query()->orderBy('sort_order')->orderBy('name');
            if ($activeOnly) {
                $query->where('is_active', true);
            }

            $codes = $query->pluck('code')->map(fn ($code) => (string) $code)->values()->all();
            if ($codes !== []) {
                return $codes;
            }
        }

        $fromFiles = $this->files->locales();
        if ($fromFiles !== []) {
            return $fromFiles;
        }

        return ['it', 'en'];
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
