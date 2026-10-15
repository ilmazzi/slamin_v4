<?php

namespace Domain\ContentTranslation\Actions;

use Domain\ContentTranslation\Contracts\TranslatableContent;
use Domain\ContentTranslation\Models\ContentTranslation;

class UpsertContentTranslationAction
{
    /**
     * @param  array{title: string, content: string, summary?: string|null}  $data
     */
    public function execute(TranslatableContent $translatable, string $locale, array $data, int $userId): ContentTranslation
    {
        return $translatable->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'title' => $data['title'],
                'content' => $data['content'],
                'summary' => $data['summary'] ?? null,
                'user_id' => $userId,
            ],
        );
    }
}
