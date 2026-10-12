<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Exceptions\InvalidLocaleException;
use Domain\I18n\Models\Language;
use Domain\I18n\Models\TranslationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTranslationKeysAction
{
    /**
     * @return array{
     *     keys: LengthAwarePaginator,
     *     groups: list<string>,
     *     locale: string,
     *     source_locale: string
     * }
     */
    public function execute(
        string $locale,
        ?string $group = null,
        ?string $search = null,
        bool $missingOnly = false,
        int $perPage = 30,
    ): array {
        $language = Language::query()->where('code', $locale)->first();
        if (! $language) {
            throw new InvalidLocaleException;
        }

        $source = Language::query()->where('is_default', true)->first() ?? $language;

        $query = TranslationKey::query()
            ->with([
                'translations' => fn ($translations) => $translations->whereIn('language_id', array_unique([$language->id, $source->id])),
            ]);

        if (is_string($group) && $group !== '') {
            $query->where('group', $group);
        }

        if (is_string($search) && $search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($inner) use ($like, $language, $source): void {
                $inner->where('key', 'like', $like)
                    ->orWhereHas('translations', function ($translations) use ($like, $language, $source): void {
                        $translations
                            ->whereIn('language_id', [$language->id, $source->id])
                            ->where('value', 'like', $like);
                    });
            });
        }

        if ($missingOnly) {
            $query->whereDoesntHave('translations', fn ($translations) => $translations->where('language_id', $language->id));
        }

        $keys = $query->orderBy('key')->paginate($perPage);

        $keys->getCollection()->transform(function (TranslationKey $key) use ($language, $source): array {
            $sourceValue = $key->translations->firstWhere('language_id', $source->id)?->value;
            $value = $key->translations->firstWhere('language_id', $language->id)?->value;

            return [
                'id' => $key->id,
                'key' => $key->key,
                'group' => $key->group,
                'notes' => $key->notes,
                'source_value' => $sourceValue,
                'value' => $value,
                'is_missing' => $value === null,
            ];
        });

        return [
            'keys' => $keys,
            'groups' => TranslationKey::query()->distinct()->orderBy('group')->pluck('group')->all(),
            'locale' => $language->code,
            'source_locale' => $source->code,
        ];
    }
}
