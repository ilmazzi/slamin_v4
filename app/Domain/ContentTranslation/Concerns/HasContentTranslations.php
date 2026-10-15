<?php

namespace Domain\ContentTranslation\Concerns;

use Domain\ContentTranslation\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasContentTranslations
{
    public function translations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }
}
