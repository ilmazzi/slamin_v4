<?php

namespace Domain\ContentTranslation\Actions;

use Domain\ContentTranslation\Contracts\TranslatableContent;
use Domain\ContentTranslation\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Model;

class LoadContentTranslationsAction
{
    /**
     * Loads the lightweight list of available translations and, when one matches
     * the requested locale, the full translation as `currentTranslation`.
     */
    public function execute(Model&TranslatableContent $translatable, string $locale): void
    {
        $translatable->load(['translations' => fn ($query) => $query
            ->select(['id', 'translatable_type', 'translatable_id', 'locale', 'title', 'updated_at'])
            ->orderBy('locale')]);

        $original = strtolower((string) $translatable->getAttribute('language'));
        $match = $locale !== $original
            ? $translatable->translations->firstWhere('locale', $locale)
            : null;

        $translatable->setRelation(
            'currentTranslation',
            $match ? ContentTranslation::query()->find($match->id) : null,
        );
    }
}
