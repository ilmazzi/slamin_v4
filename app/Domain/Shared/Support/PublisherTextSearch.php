<?php

namespace Domain\Shared\Support;

use Illuminate\Database\Eloquent\Builder;

final class PublisherTextSearch
{
    public static function constrain(Builder $builder, string $term, string $userRelation = 'user'): void
    {
        $builder->orWhereHas("{$userRelation}.profile", function (Builder $profile) use ($term): void {
            $profile
                ->whereRaw('LOWER(nickname) LIKE ?', [$term])
                ->orWhereRaw('LOWER(display_name) LIKE ?', [$term]);
        });
    }
}
