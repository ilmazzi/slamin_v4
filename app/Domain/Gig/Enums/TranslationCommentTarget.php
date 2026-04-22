<?php

namespace Domain\Gig\Enums;

enum TranslationCommentTarget: string
{
    case SourceText = 'source_text';
    case TranslatedText = 'translated_text';
    case General = 'general';
}
