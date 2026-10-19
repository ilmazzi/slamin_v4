<?php

namespace Domain\I18n\Actions;

use Domain\I18n\DTOs\ImportTranslationsData;
use Domain\I18n\Exceptions\InvalidLocaleException;
use Domain\I18n\Models\Language;
use Domain\I18n\Models\Translation;
use Domain\I18n\Models\TranslationKey;
use Domain\I18n\Support\I18nCache;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class ImportTranslationsAction
{
    /**
     * @return array{imported: int, skipped: int, created_keys: int, unknown_keys: int}
     */
    public function execute(ImportTranslationsData $data, ?User $user = null): array
    {
        $language = Language::query()->where('code', $data->locale)->first();
        if (! $language) {
            throw new InvalidLocaleException;
        }

        $imported = 0;
        $skipped = 0;
        $createdKeys = 0;
        $unknownKeys = 0;

        DB::transaction(function () use ($data, $language, $user, &$imported, &$skipped, &$createdKeys, &$unknownKeys): void {
            $keys = TranslationKey::query()
                ->whereIn('key', array_keys($data->translations))
                ->get()
                ->keyBy('key');

            foreach ($data->translations as $fullKey => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    $skipped++;
                    continue;
                }

                $key = $keys->get($fullKey);
                if (! $key) {
                    if (! $data->createKeys || ! preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $fullKey)) {
                        $unknownKeys++;
                        continue;
                    }

                    $group = explode('.', $fullKey, 2)[0] ?? $fullKey;
                    $key = TranslationKey::query()->create([
                        'key' => $fullKey,
                        'group' => $group,
                    ]);
                    $keys->put($fullKey, $key);
                    $createdKeys++;
                }

                $existing = Translation::query()
                    ->where('translation_key_id', $key->id)
                    ->where('language_id', $language->id)
                    ->first();

                if ($existing && filled($existing->value) && ! $data->overwrite) {
                    $skipped++;
                    continue;
                }

                Translation::query()->updateOrCreate(
                    [
                        'translation_key_id' => $key->id,
                        'language_id' => $language->id,
                    ],
                    [
                        'value' => $value,
                        'updated_by' => $user?->id,
                    ],
                );
                $imported++;
            }
        });

        I18nCache::bump();

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'created_keys' => $createdKeys,
            'unknown_keys' => $unknownKeys,
        ];
    }
}
