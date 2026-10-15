<?php

namespace Domain\ContentTranslation\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface TranslatableContent
{
    public function translations(): MorphMany;
}
