<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Models\Translation;
use Domain\I18n\Models\TranslationKey;
use Illuminate\Support\Facades\DB;

class ListLanguagesAction
{
    /**
     * @return list<array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     flag: string,
     *     is_active: bool,
     *     is_default: bool,
     *     sort_order: int,
     *     translated_count: int,
     *     total_keys: int
     * }>
     */
    public function execute(): array
    {
        $totalKeys = TranslationKey::query()->count();
        $counts = Translation::query()
            ->select('language_id', DB::raw('count(*) as translated_count'))
            ->groupBy('language_id')
            ->pluck('translated_count', 'language_id');

        return Language::query()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Language $language): array => [
                'id' => $language->id,
                'code' => $language->code,
                'name' => $language->name,
                'flag' => $language->flag,
                'is_active' => $language->is_active,
                'is_default' => $language->is_default,
                'sort_order' => $language->sort_order,
                'translated_count' => (int) ($counts[$language->id] ?? 0),
                'total_keys' => $totalKeys,
            ])
            ->all();
    }
}
