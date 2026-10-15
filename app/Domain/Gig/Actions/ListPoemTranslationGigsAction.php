<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigType;
use Domain\Poetry\Models\Poem;
use Illuminate\Database\Eloquent\Collection;

class ListPoemTranslationGigsAction
{
    public function execute(Poem $poem): Collection
    {
        return $poem->gigs()
            ->where('type', GigType::Translator->value)
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->get();
    }
}
