<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Exceptions\InvalidLocaleException;
use Domain\I18n\Models\Language;
use Domain\I18n\Models\Translation;
use Domain\I18n\Models\TranslationKey;
use Domain\I18n\Support\I18nCache;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpsertTranslationAction
{
    public function execute(string $key, string $locale, string $value, ?User $user = null): Translation
    {
        $translationKey = TranslationKey::query()->where('key', $key)->first();
        if (! $translationKey) {
            throw new ModelNotFoundException;
        }

        $language = Language::query()->where('code', $locale)->first();
        if (! $language) {
            throw new InvalidLocaleException;
        }

        $translation = Translation::query()->updateOrCreate(
            [
                'translation_key_id' => $translationKey->id,
                'language_id' => $language->id,
            ],
            [
                'value' => $value,
                'updated_by' => $user?->id,
            ],
        );

        I18nCache::bump();

        return $translation;
    }
}
