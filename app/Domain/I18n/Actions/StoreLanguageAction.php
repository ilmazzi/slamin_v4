<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Support\I18nCache;
use Illuminate\Support\Facades\DB;

class StoreLanguageAction
{
    /**
     * @param  array{code: string, name: string, flag?: string, is_active?: bool, is_default?: bool}  $data
     */
    public function execute(array $data): Language
    {
        $code = strtolower(trim($data['code']));

        $language = DB::transaction(function () use ($data, $code): Language {
            $isDefault = (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                Language::query()->where('is_default', true)->update(['is_default' => false]);
            } elseif (! Language::query()->where('is_default', true)->exists()) {
                $isDefault = true;
            }

            return Language::query()->create([
                'code' => $code,
                'name' => $data['name'],
                'flag' => $data['flag'] ?: '🌐',
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $isDefault,
                'sort_order' => (int) Language::query()->max('sort_order') + 1,
            ]);
        });

        I18nCache::bump();

        return $language;
    }
}
