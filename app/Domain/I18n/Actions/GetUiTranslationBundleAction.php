<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Exceptions\InvalidLocaleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

class GetUiTranslationBundleAction
{
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
        $availableLocales = $this->getAvailableLocales();
        if (! in_array($locale, $availableLocales, true)) {
            throw new InvalidLocaleException;
        }

        $availableLanguages = $this->getAvailableLanguages();

        $cacheKey = sprintf('i18n.%s.%s', $locale, $this->translationFilesVersion($locale));
        $translations = [];
        try {
            $translations = Cache::remember($cacheKey, 3600, fn (): array => $this->loadLocaleTranslationFiles($locale));
        } catch (Throwable) {
            $translations = $this->loadLocaleTranslationFiles($locale);
        }

        return [
            'locale' => $locale,
            'default_locale' => (string) config('app.fallback_locale', 'en'),
            'available_locales' => $availableLocales,
            'available_languages' => array_values($availableLanguages),
            'translations' => $translations,
        ];
    }

    /**
     * @return list<string>
     */
    private function getAvailableLocales(): array
    {
        $langPath = lang_path();
        if (! is_dir($langPath)) {
            return [];
        }

        $locales = [];
        foreach (File::directories($langPath) as $dir) {
            $locales[] = basename($dir);
        }

        return $locales;
    }

    /**
     * @return array<string, array{code: string, name: string, flag: string}>
     */
    private function getAvailableLanguages(): array
    {
        $languages = [];
        $langPath = lang_path();
        if (! is_dir($langPath)) {
            return [];
        }

        foreach (File::directories($langPath) as $dir) {
            $code = basename($dir);
            $meta = $this->readLanguageMeta($code);
            $languages[$code] = [
                'code' => $code,
                'name' => $meta['name'],
                'flag' => $meta['flag'],
            ];
        }
        uasort($languages, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $languages;
    }

    /**
     * @return array{name: string, flag: string}
     */
    private function readLanguageMeta(string $locale): array
    {
        $metaPath = lang_path($locale.'/meta.php');
        $meta = File::exists($metaPath) ? require $metaPath : [];
        if (! is_array($meta)) {
            $meta = [];
        }

        $name = (string) ($meta['name'] ?? '');
        if ($name === '') {
            if (class_exists(\Locale::class)) {
                $displayName = \Locale::getDisplayLanguage($locale, $locale);
                $name = $displayName ? ucfirst($displayName) : strtoupper($locale);
            } else {
                $name = strtoupper($locale);
            }
        }

        $flag = (string) ($meta['flag'] ?? '🌐');
        if ($flag === '') {
            $flag = '🌐';
        }

        return [
            'name' => $name,
            'flag' => $flag,
        ];
    }

    private function translationFilesVersion(string $locale): string
    {
        $path = lang_path($locale);
        if (! is_dir($path)) {
            return '0';
        }

        $lastModified = 0;
        foreach (File::files($path) as $file) {
            $lastModified = max($lastModified, $file->getMTime());
        }

        return (string) $lastModified;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLocaleTranslationFiles(string $locale): array
    {
        $bundle = [];
        $path = lang_path($locale);
        if (! is_dir($path)) {
            return [];
        }

        foreach (File::files($path) as $file) {
            if (str_ends_with($file->getFilename(), '_ui.php')) {
                continue;
            }
            $key = $file->getFilenameWithoutExtension();
            $bundle[$key] = require $file->getPathname();
        }

        return $bundle;
    }
}
