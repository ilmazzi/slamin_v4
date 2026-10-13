<?php

namespace Domain\I18n\Support;

use Illuminate\Support\Facades\File;

final class FileTranslationCatalog
{
    /**
     * @return list<string>
     */
    public function locales(): array
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
     * @return array{name: string, flag: string}
     */
    public function languageMeta(string $locale): array
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

    /**
     * @return array<string, mixed>
     */
    public function translations(string $locale): array
    {
        $bundle = [];
        $path = lang_path($locale);
        if (! is_dir($path)) {
            return [];
        }

        foreach (File::files($path) as $file) {
            $filename = $file->getFilename();
            if ($filename === 'meta.php' || str_ends_with($filename, '_ui.php')) {
                continue;
            }

            $key = $file->getFilenameWithoutExtension();
            $loaded = require $file->getPathname();
            $bundle[$key] = is_array($loaded) ? $loaded : [];
        }

        return $bundle;
    }

    /**
     * @return list<array{code: string, name: string, flag: string}>
     */
    public function availableLanguages(): array
    {
        $languages = [];

        foreach ($this->locales() as $code) {
            $meta = $this->languageMeta($code);
            $languages[] = [
                'code' => $code,
                'name' => $meta['name'],
                'flag' => $meta['flag'],
            ];
        }

        usort($languages, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $languages;
    }
}
