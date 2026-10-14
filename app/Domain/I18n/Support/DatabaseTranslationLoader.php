<?php

namespace Domain\I18n\Support;

use Domain\I18n\Actions\ResolveDefaultLocaleAction;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseTranslationLoader implements Loader
{
    public function __construct(
        private readonly Loader $fileLoader,
    ) {}

    public function load($locale, $group, $namespace = null): array
    {
        $fromFiles = $this->fileLoader->load($locale, $group, $namespace);

        if ($namespace !== null && $namespace !== '*') {
            return $fromFiles;
        }

        if ($group === '*' || $group === 'meta') {
            return $fromFiles;
        }

        $fromDatabase = $this->loadGroup((string) $locale, (string) $group);
        if ($fromDatabase === []) {
            return $fromFiles;
        }

        return array_replace_recursive($fromFiles, $fromDatabase);
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->fileLoader->addJsonPath($path);
    }

    public function addPath($path): void
    {
        if (method_exists($this->fileLoader, 'addPath')) {
            $this->fileLoader->addPath($path);
        }
    }

    public function namespaces(): array
    {
        return $this->fileLoader->namespaces();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGroup(string $locale, string $group): array
    {
        $cacheKey = I18nCache::key('laravel.'.$locale.'.'.$group);

        try {
            $cached = Cache::get($cacheKey);
        } catch (Throwable) {
            $cached = null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        if (! $this->databaseReady()) {
            return [];
        }

        $translations = $this->buildGroup($locale, $group, $this->defaultLocale());

        try {
            Cache::put($cacheKey, $translations, 3600);
        } catch (Throwable) {
        }

        return $translations;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGroup(string $locale, string $group, string $defaultLocale): array
    {
        $fallback = $locale === $defaultLocale ? [] : $this->dottedGroup($defaultLocale, $group);
        $current = $this->dottedGroup($locale, $group);

        return TranslationTree::nest([...$fallback, ...$current]);
    }

    /**
     * @return array<string, string>
     */
    private function dottedGroup(string $locale, string $group): array
    {
        try {
            $rows = DB::table('i18n_translations')
                ->join('i18n_languages', 'i18n_languages.id', '=', 'i18n_translations.language_id')
                ->join('i18n_translation_keys', 'i18n_translation_keys.id', '=', 'i18n_translations.translation_key_id')
                ->where('i18n_languages.code', $locale)
                ->where('i18n_translation_keys.group', $group)
                ->get([
                    'i18n_translation_keys.key as full_key',
                    'i18n_translations.value as value',
                ]);
        } catch (Throwable) {
            return [];
        }

        $dotted = [];
        $prefix = $group.'.';

        foreach ($rows as $row) {
            $fullKey = (string) $row->full_key;
            $relative = str_starts_with($fullKey, $prefix)
                ? substr($fullKey, strlen($prefix))
                : $fullKey;

            if ($relative === '') {
                continue;
            }

            $dotted[$relative] = (string) $row->value;
        }

        return $dotted;
    }

    private function defaultLocale(): string
    {
        try {
            return app(ResolveDefaultLocaleAction::class)->execute();
        } catch (Throwable) {
            return (string) config('app.fallback_locale', 'en');
        }
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('i18n_translations') && Schema::hasTable('i18n_translation_keys');
        } catch (Throwable) {
            return false;
        }
    }
}
