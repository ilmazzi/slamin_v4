<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Models\Translation;
use Domain\I18n\Models\TranslationKey;
use Domain\I18n\Support\FileTranslationCatalog;
use Domain\I18n\Support\I18nCache;
use Domain\I18n\Support\TranslationTree;
use Illuminate\Support\Facades\DB;

class ImportLangFilesAction
{
    public function __construct(
        private readonly FileTranslationCatalog $files,
    ) {}

    /**
     * @return array{languages: int, keys: int, translations: int}
     */
    public function execute(bool $overwrite = false): array
    {
        $locales = $this->files->locales();
        $languageIds = [];
        $sort = 0;

        DB::transaction(function () use ($locales, &$languageIds, &$sort): void {
            foreach ($locales as $code) {
                $meta = $this->files->languageMeta($code);
                $language = Language::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $meta['name'],
                        'flag' => $meta['flag'],
                        'is_active' => true,
                        'sort_order' => $sort++,
                    ],
                );
                $languageIds[$code] = $language->id;
            }

            $this->ensureDefaultLanguage($languageIds);
        });

        $keysCount = 0;
        $translationsCount = 0;

        foreach ($locales as $code) {
            $nested = $this->files->translations($code);
            $flat = TranslationTree::flatten($nested);
            $languageId = $languageIds[$code] ?? null;
            if ($languageId === null) {
                continue;
            }

            foreach ($flat as $fullKey => $value) {
                $group = explode('.', $fullKey, 2)[0] ?? $fullKey;
                $key = TranslationKey::query()->firstOrCreate(
                    ['key' => $fullKey],
                    ['group' => $group],
                );
                $keysCount++;

                $existing = Translation::query()
                    ->where('translation_key_id', $key->id)
                    ->where('language_id', $languageId)
                    ->first();

                if ($existing && filled($existing->value) && ! $overwrite) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    [
                        'translation_key_id' => $key->id,
                        'language_id' => $languageId,
                    ],
                    [
                        'value' => $value,
                    ],
                );
                $translationsCount++;
            }
        }

        I18nCache::bump();

        return [
            'languages' => count($languageIds),
            'keys' => TranslationKey::query()->count(),
            'translations' => $translationsCount,
        ];
    }

    /**
     * @param  array<string, int>  $languageIds
     */
    private function ensureDefaultLanguage(array $languageIds): void
    {
        if (Language::query()->where('is_default', true)->exists()) {
            return;
        }

        $preferred = isset($languageIds['it']) ? 'it' : (isset($languageIds['en']) ? 'en' : array_key_first($languageIds));
        if (! is_string($preferred)) {
            return;
        }

        Language::query()->where('code', $preferred)->update(['is_default' => true]);
    }
}
