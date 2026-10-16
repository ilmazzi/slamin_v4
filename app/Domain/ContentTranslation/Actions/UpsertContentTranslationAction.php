<?php

namespace Domain\ContentTranslation\Actions;

use Domain\ContentTranslation\Contracts\TranslatableContent;
use Domain\ContentTranslation\Models\ContentTranslation;
use Domain\Shared\Support\RichTextSanitizer;

class UpsertContentTranslationAction
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array{title: string, content: string, summary?: string|null}  $data
     */
    public function execute(TranslatableContent $translatable, string $locale, array $data, int $userId): ContentTranslation
    {
        return $translatable->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'title' => $data['title'],
                'content' => $this->richTextSanitizer->sanitize($data['content']),
                'summary' => $data['summary'] ?? null,
                'user_id' => $userId,
            ],
        );
    }
}
