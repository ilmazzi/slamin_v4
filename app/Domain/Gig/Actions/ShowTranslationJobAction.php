<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\TranslationJob;

class ShowTranslationJobAction
{
    public function execute(TranslationJob $job): TranslationJob
    {
        return $job->loadMissing([
            'poem',
            'owner.profile',
            'translator.profile',
            'comments.author.profile',
        ]);
    }
}
