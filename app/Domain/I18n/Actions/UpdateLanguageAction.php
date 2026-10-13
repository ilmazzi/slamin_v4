<?php

namespace Domain\I18n\Actions;

use Domain\I18n\Models\Language;
use Domain\I18n\Support\I18nCache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateLanguageAction
{
    /**
     * @param  array{name?: string, flag?: string, is_active?: bool, is_default?: bool, sort_order?: int}  $data
     */
    public function execute(Language $language, array $data): Language
    {
        DB::transaction(function () use ($language, $data): void {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                Language::query()->where('id', '!=', $language->id)->where('is_default', true)->update(['is_default' => false]);
                $language->is_default = true;
                $language->is_active = true;
            } elseif (array_key_exists('is_default', $data) && ! $data['is_default'] && $language->is_default) {
                throw new InvalidArgumentException('default_language');
            }

            if (array_key_exists('is_active', $data) && ! $data['is_active'] && $language->is_default) {
                throw new InvalidArgumentException('default_language');
            }

            if (array_key_exists('name', $data)) {
                $language->name = $data['name'];
            }
            if (array_key_exists('flag', $data)) {
                $language->flag = $data['flag'] ?: '🌐';
            }
            if (array_key_exists('is_active', $data) && ! $language->is_default) {
                $language->is_active = (bool) $data['is_active'];
            }
            if (array_key_exists('sort_order', $data)) {
                $language->sort_order = (int) $data['sort_order'];
            }

            $language->save();
        });

        I18nCache::bump();

        return $language->refresh();
    }
}
