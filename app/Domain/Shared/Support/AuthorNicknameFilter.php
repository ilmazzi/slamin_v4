<?php

namespace Domain\Shared\Support;

use Illuminate\Database\Eloquent\Builder;

final class AuthorNicknameFilter
{
    public static function apply(Builder $query, ?string $nickname, string $userRelation = 'user'): void
    {
        $normalized = mb_strtolower(trim((string) $nickname));
        if ($normalized === '') {
            return;
        }

        $query->whereHas("{$userRelation}.profile", function (Builder $builder) use ($normalized): void {
            $builder->whereRaw('LOWER(nickname) = ?', [$normalized]);
        });
    }
}
