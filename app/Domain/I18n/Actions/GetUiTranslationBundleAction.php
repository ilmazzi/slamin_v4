<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Exceptions\InvalidLocaleException;
use Domain\I18n\Models\Language;
use Domain\I18n\Support\FileTranslationCatalog;
use Domain\I18n\Support\I18nCache;
use Domain\I18n\Support\TranslationTree;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GetUiTranslationBundleAction
{
    public function __construct(
        private readonly FileTranslationCatalog $files,
        private readonly ResolveSupportedLocalesAction $supportedLocales,
        private readonly ResolveDefaultLocaleAction $defaultLocale,
    ) {}

    /**
     * @return array{
     *     locale: string,
     *     default_locale: string,
     *     available_locales: list<string>,
     *     available_languages: list<array{code: string, name: string, flag: string}>,
     *     translations: array<string, mixed>
     * }
     *
     * @throws InvalidLocaleException
     */
    public function execute(string $locale): array
    {
        $availableLocales = $this->supportedLocales->execute();
        if (! in_array($locale, $availableLocales, true)) {
            throw new InvalidLocaleException;
        }

        $defaultLocale = $this->defaultLocale->execute();
        $cacheKey = I18nCache::key('bundle.'.$locale);

        try {
            $translations = Cache::remember($cacheKey, 3600, fn (): array => $this->buildTranslations($locale, $defaultLocale));
        } catch (Throwable) {
            $translations = $this->buildTranslations($locale, $defaultLocale);
        }

        return [
            'locale' => $locale,
            'default_locale' => $defaultLocale,
            'available_locales' => $availableLocales,
            'available_languages' => $this->availableLanguages(),
            'translations' => $translations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTranslations(string $locale, string $defaultLocale): array
    {
        if ($this->databaseHasTranslations()) {
            $fallback = $this->dottedForLocale($defaultLocale);
            $current = $locale === $defaultLocale ? [] : $this->dottedForLocale($locale);
            $merged = [...$fallback, ...$current];

            if ($merged !== []) {
                return TranslationTree::nest($merged);
            }
        }

        $fileFallback = $this->files->translations($defaultLocale);
        $fileCurrent = $locale === $defaultLocale ? [] : $this->files->translations($locale);

        return array_replace_recursive($fileFallback, $fileCurrent);
    }

    /**
     * @return array<string, string>
     */
    private function dottedForLocale(string $locale): array
    {
        try {
            return DB::table('i18n_translations')
                ->join('i18n_languages', 'i18n_languages.id', '=', 'i18n_translations.language_id')
                ->join('i18n_translation_keys', 'i18n_translation_keys.id', '=', 'i18n_translations.translation_key_id')
                ->where('i18n_languages.code', $locale)
                ->pluck('i18n_translations.value', 'i18n_translation_keys.key')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{code: string, name: string, flag: string}>
     */
    private function availableLanguages(): array
    {
        if ($this->databaseReady()) {
            $languages = Language::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['code', 'name', 'flag'])
                ->map(fn (Language $language): array => [
                    'code' => $language->code,
                    'name' => $language->name,
                    'flag' => $language->flag,
                ])
                ->values()
                ->all();

            if ($languages !== []) {
                return $languages;
            }
        }

        return $this->files->availableLanguages();
    }

    private function databaseHasTranslations(): bool
    {
        if (! $this->databaseReady()) {
            return false;
        }

        try {
            return DB::table('i18n_translations')->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('i18n_languages') && Schema::hasTable('i18n_translations');
        } catch (Throwable) {
            return false;
        }
    }
}
