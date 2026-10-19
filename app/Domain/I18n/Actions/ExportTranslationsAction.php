<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Exceptions\InvalidLocaleException;
use Domain\I18n\Models\Language;
use Domain\I18n\Models\Translation;
use Domain\I18n\Models\TranslationKey;

class ExportTranslationsAction
{
    /**
     * @return array{
     *     locale: string,
     *     source_locale: string,
     *     exported_at: string,
     *     translations: array<string, string>,
     *     catalog: list<array{key: string, value: string, source_value: string}>
     * }
     */
    public function execute(string $locale): array
    {
        $language = Language::query()->where('code', $locale)->first();
        if (! $language) {
            throw new InvalidLocaleException;
        }

        $source = Language::query()->where('is_default', true)->first()
            ?? Language::query()->where('code', 'it')->first()
            ?? $language;

        $sourceValues = Translation::query()
            ->where('language_id', $source->id)
            ->pluck('value', 'translation_key_id');
        $localeValues = Translation::query()
            ->where('language_id', $language->id)
            ->pluck('value', 'translation_key_id');

        $catalog = [];
        $translations = [];

        foreach (TranslationKey::query()->orderBy('key')->get(['id', 'key']) as $key) {
            $value = (string) ($localeValues[$key->id] ?? '');
            $sourceValue = (string) ($sourceValues[$key->id] ?? '');
            $translations[$key->key] = $value;
            $catalog[] = [
                'key' => $key->key,
                'value' => $value,
                'source_value' => $sourceValue,
            ];
        }

        return [
            'locale' => $language->code,
            'source_locale' => $source->code,
            'exported_at' => now()->toISOString(),
            'translations' => $translations,
            'catalog' => $catalog,
        ];
    }
}
