<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Models\Translation;
use Domain\I18n\Models\TranslationKey;
use Domain\I18n\Support\I18nCache;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StoreTranslationKeyAction
{
    /**
     * @param  array{key: string, notes?: string|null, translations?: array<string, string>}  $data
     */
    public function execute(array $data, ?User $user = null): TranslationKey
    {
        $fullKey = trim($data['key']);
        $group = explode('.', $fullKey, 2)[0] ?? $fullKey;

        if ($group === '') {
            throw new InvalidArgumentException('invalid_key');
        }

        $key = DB::transaction(function () use ($data, $fullKey, $group, $user): TranslationKey {
            $key = TranslationKey::query()->create([
                'key' => $fullKey,
                'group' => $group,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['translations'] ?? [] as $locale => $value) {
                $language = Language::query()->where('code', $locale)->first();
                if (! $language || ! is_string($value)) {
                    continue;
                }

                Translation::query()->create([
                    'translation_key_id' => $key->id,
                    'language_id' => $language->id,
                    'value' => $value,
                    'updated_by' => $user?->id,
                ]);
            }

            return $key;
        });

        I18nCache::bump();

        return $key;
    }
}
